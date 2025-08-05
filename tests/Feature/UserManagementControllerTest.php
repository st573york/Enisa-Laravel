<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use App\HelperFunctions\TestHelper;
use App\Models\User;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    const ERROR_NOT_AUTHORIZED = 'User cannot be updated as you are not authorized for this action!';
    const ERROR_STATUS_NOT_AUTHORIZED = 'User status cannot be updated as you are not authorized for this action!';
    const ERROR_NOT_ALLOWED = 'User cannot be updated as the requested action is not allowed!';
    
    public function assertUsers($users)
    {
        $actual_users = TestHelper::getActualUsers($users);
        $expected_users = TestHelper::getExpectedUsers([], true);

        sort($actual_users);
        sort($expected_users);

        $this->assertEquals($expected_users, $actual_users,
            'Expected users -> ' . implode(', ', $expected_users) . "\n" .
            'Actual users -> ' . implode(', ', $actual_users));
    }

    public function test_unauthenticated_user_management()
    {
        $response = $this->get('/user/management');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_unauthorized_user_management()
    {
        $operator = EcasTestHelper::validateTestUser('operator');
        $response = $this->actingAs($operator)->get('/user/management');
        $response->assertRedirect('/access/denied/');
    }

    public function test_authenticated_user_management()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->get('/user/management');
        $response->assertOk()->assertViewIs('user.management');

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/user/management');
        $response->assertOk()->assertViewIs('user.management');
    }

    public function test_unauthenticated_user_list()
    {
        $response = $this->get('/user/list');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_user_list()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $admin = EcasTestHelper::validateTestUser('admin');
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();
        
        $response = $this->actingAs($poc)->get('/user/list');
        $response->assertOk();
        $response->assertJsonStructure(['data']);
               
        $decodedJSON = $response->decodeResponseJson();
        $this->assertUsers($decodedJSON['data']);
        
        $response = $this->actingAs($admin)->get('/user/list');
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        $decodedJSON = $response->decodeResponseJson();
        $this->assertUsers($decodedJSON['data']);

        TestHelper::createNewUser([
            'permissions' => [
                'role' => $poc->permissions->first()->role->name,
                'country' => $poc->permissions->first()->country->name
            ]
        ]);

        $response = $this->actingAs($poc)->get('/user/list');
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        $decodedJSON = $response->decodeResponseJson();
        $this->assertUsers($decodedJSON['data']);

        $response = $this->actingAs($admin)->get('/user/list');
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        $decodedJSON = $response->decodeResponseJson();
        $this->assertUsers($decodedJSON['data']);

        TestHelper::createNewUser([
            'permissions' => [
                'role' => $poc->permissions->first()->role->name,
                'country' => $other_country->name
            ]
        ]);

        $response = $this->actingAs($poc)->get('/user/list');
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        $decodedJSON = $response->decodeResponseJson();
        $this->assertUsers($decodedJSON['data']);

        $response = $this->actingAs($admin)->get('/user/list');
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        $decodedJSON = $response->decodeResponseJson();
        $this->assertUsers($decodedJSON['data']);
    }

    public function test_unauthenticated_user_edit()
    {
        $user = User::first();
        $response = $this->get('/user/edit/single/' . $user->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_user_edit()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->get('/user/edit/single/' . $poc->id);
        $response->assertOk()->assertViewIs('ajax.user-edit')->assertViewHasAll([
            'users', 'countries', 'roles'
        ]);

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/user/edit/single/' . $admin->id);
        $response->assertOk()->assertViewIs('ajax.user-edit')->assertViewHasAll([
            'users', 'countries', 'roles'
        ]);
    }

    public function test_unauthenticated_user_update()
    {
        $user = User::first();
        $response = $this->post('/user/update/single/' . $user->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_required_user_update()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => $poc->permissions->first()->role->name,
                'country' => $poc->permissions->first()->country->name
            ]
        ]);

        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => '',
            'role' => '',
            'blocked' => ''
        ]);
        $response->assertStatus(400);
        $response->assertJson(['errors' => [
                'country' => [
                    'The country field is required.'
                ],
                'role' => [
                    'The role field is required.'
                ],
                'blocked' => [
                    'The blocked field is required.'
                ]
            ],
            'type' => 'pageModalForm'
        ]);

        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => '',
            'role' => '',
            'blocked' => ''
        ]);
        $response->assertStatus(400);
        $response->assertJson(['errors' => [
                'country' => [
                    'The country field is required.'
                ],
                'role' => [
                    'The role field is required.'
                ],
                'blocked' => [
                    'The blocked field is required.'
                ]
            ],
            'type' => 'pageModalForm'
        ]);
    }

    // Admin updates himself to ENISA/admin
    public function test_authenticated_admin_user_update_to_enisa_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/user/update/single/' . $admin->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates himself to other COUNTRY/admin
    public function test_authenticated_admin_user_update_to_other_country_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $other_country = Country::where('name', '!=', $admin->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $admin->id, [
            'country' => $other_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates himself to ENISA/admin and blocked
    public function test_authenticated_admin_user_update_to_enisa_admin_blocked()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/user/update/single/' . $admin->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'admin',
            'blocked' => 1
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates himself to ENISA/PoC
    public function test_authenticated_admin_user_update_to_enisa_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/user/update/single/' . $admin->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates himself to other COUNTRY/PoC
    public function test_authenticated_admin_user_update_to_other_country_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $other_country = Country::where('name', '!=', $admin->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $admin->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(302);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('redirect', '/user/management')
        );
    }

    // Admin updates himself to ENISA/operator
    public function test_authenticated_admin_user_update_to_enisa_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/user/update/single/' . $admin->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates himself to other COUNTRY/operator
    public function test_authenticated_admin_user_update_to_other_country_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $other_country = Country::where('name', '!=', $admin->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $admin->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(302);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('redirect', '/')
        );
    }

    // Admin updates himself to ENISA/viewer
    public function test_authenticated_admin_user_update_to_enisa_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/user/update/single/' . $admin->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(302);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('redirect', '/')
        );
    }

    // Admin updates himself to other COUNTRY/viewer
    public function test_authenticated_admin_user_update_to_other_country_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $other_country = Country::where('name', '!=', $admin->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $admin->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(302);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('redirect', '/')
        );
    }

    // Admin updates admin to ENISA/admin
    public function test_authenticated_admin_user_update_admin_to_enisa_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates admin to other COUNTRY/admin
    public function test_authenticated_admin_user_update_admin_to_other_country_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => Country::first()->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates admin to ENISA/admin and blocked
    public function test_authenticated_admin_user_update_admin_to_enisa_admin_blocked()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'admin',
            'blocked' => 1
        ]);
        $response->assertOk();
    }

    // Admin updates admin to ENISA/PoC
    public function test_authenticated_admin_user_update_admin_to_enisa_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates admin to other COUNTRY/PoC
    public function test_authenticated_admin_user_update_admin_to_other_country_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $admin->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates admin to ENISA/operator
    public function test_authenticated_admin_user_update_admin_to_enisa_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates admin to other COUNTRY/operator
    public function test_authenticated_admin_user_update_admin_to_other_country_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $admin->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates admin to ENISA/viewer
    public function test_authenticated_admin_user_update_admin_to_enisa_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates admin to other COUNTRY/viewer
    public function test_authenticated_admin_user_update_admin_to_other_country_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $admin->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates PoC to ENISA/admin
    public function test_authenticated_admin_user_update_poc_to_enisa_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates PoC to COUNTRY/admin
    public function test_authenticated_admin_user_update_poc_to_country_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates PoC to other COUNTRY/admin
    public function test_authenticated_admin_user_update_poc_to_other_country_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $new_user->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates PoC to ENISA/PoC
    public function test_authenticated_admin_user_update_poc_to_enisa_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates PoC to COUNTRY/PoC
    public function test_authenticated_admin_user_update_poc_to_country_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates PoC to other COUNTRY/PoC
    public function test_authenticated_admin_user_update_poc_to_other_country_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $new_user->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates PoC to ENISA/operator
    public function test_authenticated_admin_user_update_poc_to_enisa_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates PoC to COUNTRY/operator
    public function test_authenticated_admin_user_update_poc_to_country_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates PoC to other COUNTRY/operator
    public function test_authenticated_admin_user_update_poc_to_other_country_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $new_user->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates PoC to ENISA/viewer
    public function test_authenticated_admin_user_update_poc_to_enisa_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates PoC to COUNTRY/viewer
    public function test_authenticated_admin_user_update_poc_to_country_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates PoC to other COUNTRY/viewer
    public function test_authenticated_admin_user_update_poc_to_other_country_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $new_user->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates operator to ENISA/admin
    public function test_authenticated_admin_user_update_operator_to_enisa_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates operator to COUNTRY/admin
    public function test_authenticated_admin_user_update_operator_to_country_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates operator to other COUNTRY/admin
    public function test_authenticated_admin_user_update_operator_to_other_country_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $new_user->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates operator to ENISA/PoC
    public function test_authenticated_admin_user_update_operator_to_enisa_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates operator to COUNTRY/PoC
    public function test_authenticated_admin_user_update_operator_to_country_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates operator to other COUNTRY/PoC
    public function test_authenticated_admin_user_update_operator_to_other_country_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $new_user->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates operator to ENISA/operator
    public function test_authenticated_admin_user_update_operator_to_enisa_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates operator to COUNTRY/operator
    public function test_authenticated_admin_user_update_operator_to_country_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates operator to other COUNTRY/operator
    public function test_authenticated_admin_user_update_operator_to_other_country_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $new_user->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates operator to ENISA/viewer
    public function test_authenticated_admin_user_update_operator_to_enisa_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates operator to COUNTRY/viewer
    public function test_authenticated_admin_user_update_operator_to_country_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates operator to other COUNTRY/viewer
    public function test_authenticated_admin_user_update_operator_to_other_country_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $new_user->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates viewer to ENISA/admin
    public function test_authenticated_admin_user_update_viewer_to_enisa_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates viewer to COUNTRY/admin
    public function test_authenticated_admin_user_update_viewer_to_country_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates viewer to other COUNTRY/admin
    public function test_authenticated_admin_user_update_viewer_to_other_country_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $new_user->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates viewer to ENISA/PoC
    public function test_authenticated_admin_user_update_viewer_to_enisa_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates viewer to COUNTRY/PoC
    public function test_authenticated_admin_user_update_viewer_to_country_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates viewer to other COUNTRY/PoC
    public function test_authenticated_admin_user_update_viewer_to_other_country_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $new_user->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates viewer to ENISA/operator
    public function test_authenticated_admin_user_update_viewer_to_enisa_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin updates viewer to COUNTRY/operator
    public function test_authenticated_admin_user_update_viewer_to_country_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates viewer to other COUNTRY/operator
    public function test_authenticated_admin_user_update_viewer_to_other_country_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $new_user->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates viewer to ENISA/viewer
    public function test_authenticated_admin_user_update_viewer_to_enisa_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $admin->permissions->first()->country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates viewer to COUNTRY/viewer
    public function test_authenticated_admin_user_update_viewer_to_country_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // Admin updates viewer to other COUNTRY/viewer
    public function test_authenticated_admin_user_update_viewer_to_other_country_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $other_country = Country::where('name', '!=', $new_user->permissions->first()->country->name)->first();
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // PoC updates himself to ENISA/admin
    public function test_authenticated_poc_user_update_to_enisa_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates himself to COUNTRY/admin
    public function test_authenticated_poc_user_update_to_country_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => $poc->permissions->first()->country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates himself to other COUNTRY/admin
    public function test_authenticated_poc_user_update_to_other_country_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => $other_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates himself to ENISA/PoC
    public function test_authenticated_poc_user_update_to_enisa_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates himself to COUNTRY/PoC
    public function test_authenticated_poc_user_update_to_country_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => $poc->permissions->first()->country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertOk();
    }

    // PoC updates himself to other COUNTRY/PoC
    public function test_authenticated_poc_user_update_to_other_country_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates himself to COUNTRY/poc and blocked
    public function test_authenticated_poc_user_update_to_country_poc_blocked()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => $poc->permissions->first()->country->name,
            'role' => 'PoC',
            'blocked' => 1
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_STATUS_NOT_AUTHORIZED
        ]);
    }

    // PoC updates himself to ENISA/operator
    public function test_authenticated_poc_user_update_to_enisa_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates himself to COUNTRY/operator
    public function test_authenticated_poc_user_update_to_country_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => $poc->permissions->first()->country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(302);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('redirect', '/')
        );
    }

    // PoC updates himself to other COUNTRY/operator
    public function test_authenticated_poc_user_update_to_other_country_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates himself to ENISA/viewer
    public function test_authenticated_poc_user_update_to_enisa_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates himself to COUNTRY/viewer
    public function test_authenticated_poc_user_update_to_country_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => $poc->permissions->first()->country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(302);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('redirect', '/')
        );
    }

    // PoC updates himself to other COUNTRY/viewer
    public function test_authenticated_poc_user_update_to_other_country_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();
        $response = $this->actingAs($poc)->post('/user/update/single/' . $poc->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates admin to ENISA/admin
    public function test_authenticated_poc_user_update_admin_to_enisa_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates admin to COUNTRY/admin
    public function test_authenticated_poc_user_update_admin_to_country_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc->permissions->first()->country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates admin to other COUNTRY/admin
    public function test_authenticated_poc_user_update_admin_to_other_country_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates admin to ENISA/PoC
    public function test_authenticated_poc_user_update_admin_to_enisa_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates admin to COUNTRY/PoC
    public function test_authenticated_poc_user_update_admin_to_country_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc->permissions->first()->country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates admin to other COUNTRY/PoC
    public function test_authenticated_poc_user_update_admin_to_other_country_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates admin to ENISA/operator
    public function test_authenticated_poc_user_update_admin_to_enisa_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates admin to COUNTRY/operator
    public function test_authenticated_poc_user_update_admin_to_country_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc->permissions->first()->country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates admin to other COUNTRY/operator
    public function test_authenticated_poc_user_update_admin_to_other_country_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates admin to ENISA/viewer
    public function test_authenticated_poc_user_update_admin_to_enisa_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates admin to COUNTRY/viewer
    public function test_authenticated_poc_user_update_admin_to_country_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc->permissions->first()->country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates admin to other COUNTRY/viewer
    public function test_authenticated_poc_user_update_admin_to_other_country_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates PoC to ENISA/admin
    public function test_authenticated_poc_user_update_poc_to_enisa_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates PoC to COUNTRY/admin
    public function test_authenticated_poc_user_update_poc_to_country_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();
        
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates PoC to other COUNTRY/admin
    public function test_authenticated_poc_user_update_poc_to_other_country_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates PoC to ENISA/Primary PoC
    public function test_authenticated_poc_user_update_poc_to_enisa_primary_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'Primary PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'Primary PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates PoC to COUNTRY/Primary PoC
    public function test_authenticated_poc_user_update_poc_to_country_primary_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'Primary PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'Primary PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates PoC to other COUNTRY/Primary PoC
    public function test_authenticated_poc_user_update_poc_to_other_country_primary_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'Primary PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'Primary PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }
    
    // PoC updates PoC to ENISA/PoC
    public function test_authenticated_poc_user_update_poc_to_enisa_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates PoC to COUNTRY/PoC
    public function test_authenticated_poc_user_update_poc_to_country_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates PoC to other COUNTRY/PoC
    public function test_authenticated_poc_user_update_poc_to_other_country_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates PoC to ENISA/operator
    public function test_authenticated_poc_user_update_poc_to_enisa_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates PoC to COUNTRY/operator
    public function test_authenticated_poc_user_update_poc_to_country_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates PoC to other COUNTRY/operator
    public function test_authenticated_poc_user_update_poc_to_other_country_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates PoC to ENISA/viewer
    public function test_authenticated_poc_user_update_poc_to_enisa_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates PoC to COUNTRY/viewer
    public function test_authenticated_poc_user_update_poc_to_country_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();
        
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates PoC to other COUNTRY/viewer
    public function test_authenticated_poc_user_update_poc_to_other_country_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }
    
    // PoC updates operator to ENISA/admin
    public function test_authenticated_poc_user_update_operator_to_enisa_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates operator to COUNTRY/admin
    public function test_authenticated_poc_user_update_operator_to_country_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates operator to other COUNTRY/admin
    public function test_authenticated_poc_user_update_operator_to_other_country_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates operator to ENISA/PoC
    public function test_authenticated_poc_user_update_operator_to_enisa_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates operator to COUNTRY/PoC
    public function test_authenticated_poc_user_update_operator_to_country_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates operator to other COUNTRY/PoC
    public function test_authenticated_poc_user_update_operator_to_other_country_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates operator to ENISA/operator
    public function test_authenticated_poc_user_update_operator_to_enisa_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates operator to COUNTRY/operator
    public function test_authenticated_poc_user_update_operator_to_country_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertOk();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates operator to other COUNTRY/operator
    public function test_authenticated_poc_user_update_operator_to_other_country_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates operator to ENISA/viewer
    public function test_authenticated_poc_user_update_operator_to_enisa_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates operator to COUNTRY/viewer
    public function test_authenticated_poc_user_update_operator_to_country_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates operator to other COUNTRY/viewer
    public function test_authenticated_poc_user_update_operator_to_other_country_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }
    
    // PoC updates viewer to ENISA/admin
    public function test_authenticated_poc_user_update_viewer_to_enisa_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates viewer to COUNTRY/admin
    public function test_authenticated_poc_user_update_viewer_to_country_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates viewer to other COUNTRY/admin
    public function test_authenticated_poc_user_update_viewer_to_other_country_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'admin',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates viewer to ENISA/PoC
    public function test_authenticated_poc_user_update_viewer_to_enisa_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates viewer to COUNTRY/PoC
    public function test_authenticated_poc_user_update_viewer_to_country_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates viewer to other COUNTRY/PoC
    public function test_authenticated_poc_user_update_viewer_to_other_country_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates viewer to ENISA/operator
    public function test_authenticated_poc_user_update_viewer_to_enisa_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // PoC updates viewer to COUNTRY/operator
    public function test_authenticated_poc_user_update_viewer_to_country_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertOk();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates viewer to other COUNTRY/operator
    public function test_authenticated_poc_user_update_viewer_to_other_country_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'operator',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates viewer to ENISA/viewer
    public function test_authenticated_poc_user_update_viewer_to_enisa_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => config('constants.USER_GROUP'),
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates viewer to COUNTRY/viewer
    public function test_authenticated_poc_user_update_viewer_to_country_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertOk();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates viewer to other COUNTRY/viewer
    public function test_authenticated_poc_user_update_viewer_to_other_country_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $other_country->name,
            'role' => 'viewer',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }
       
    public function test_unauthenticated_user_block()
    {
        $user = User::first();
        $response = $this->post('/user/block/toggle/' . $user->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    // Admin blocks himself
    public function test_authenticated_admin_user_block()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/user/block/toggle/' . $admin->id);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin blocks admin
    public function test_authenticated_admin_user_block_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/block/toggle/' . $new_user->id);
        $response->assertOk();
    }

    // Admin blocks PoC
    public function test_authenticated_admin_user_block_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/block/toggle/' . $new_user->id);
        $response->assertOk();
    }

    // Admin blocks operator
    public function test_authenticated_admin_user_block_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/block/toggle/' . $new_user->id);
        $response->assertOk();
    }

    // Admin blocks viewer
    public function test_authenticated_admin_user_block_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/block/toggle/' . $new_user->id);
        $response->assertOk();
    }

    // PoC blocks himself
    public function test_authenticated_poc_user_block()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->post('/user/block/toggle/' . $poc->id);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_STATUS_NOT_AUTHORIZED
        ]);
    }

    // PoC blocks admin
    public function test_authenticated_poc_user_block_admin()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/block/toggle/' . $new_user->id);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_STATUS_NOT_AUTHORIZED
        ]);
    }

    // PoC blocks PoC
    public function test_authenticated_poc_user_block_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/block/toggle/' . $new_user->id);
        $response->assertOk();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/block/toggle/' . $new_user->id);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_STATUS_NOT_AUTHORIZED
        ]);
    }

    // PoC blocks operator
    public function test_authenticated_poc_user_block_operator()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/block/toggle/' . $new_user->id);
        $response->assertOk();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/block/toggle/' . $new_user->id);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_STATUS_NOT_AUTHORIZED
        ]);
    }

    // PoC blocks viewer
    public function test_authenticated_poc_user_block_viewer()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $other_country = Country::where('name', '!=', $poc->permissions->first()->country->name)->first();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/block/toggle/' . $new_user->id);
        $response->assertOk();

        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'viewer',
                'country' => $other_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/block/toggle/' . $new_user->id);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_STATUS_NOT_AUTHORIZED
        ]);
    }

    public function test_unauthenticated_user_edit_multiple()
    {
        $response = $this->get('/user/edit/multiple', [
            'users' => ''
        ]);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_user_edit_multiple()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $admin = EcasTestHelper::validateTestUser('admin');
        $poc_country = $poc->permissions->first()->country;
        $user_first = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $user_second = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);

        $response = $this->actingAs($poc)->call('get', '/user/edit/multiple', [
            'users' => $user_first->id . ',' . $user_second->id
        ]);
        $response->assertOk()->assertViewIs('ajax.user-edit')->assertViewHasAll([
            'users', 'countries', 'roles'
        ]);
                
        $response = $this->actingAs($admin)->call('get', '/user/edit/multiple', [
            'users' => $user_first->id . ',' . $user_second->id
        ]);
        $response->assertOk()->assertViewIs('ajax.user-edit')->assertViewHasAll([
            'users', 'countries', 'roles'
        ]);
    }

    public function test_unauthenticated_user_update_multiple()
    {
        $response = $this->post('/user/update/multiple', [
            'datatable-selected' => ''
        ]);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_user_update_multiple()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $admin = EcasTestHelper::validateTestUser('admin');
        $poc_country = $poc->permissions->first()->country;
        $user_first = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $user_second = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc_country->name
            ]
        ]);

        $response = $this->actingAs($poc)->post('/user/update/multiple', [
            'blocked' => 1,
            'datatable-selected' => $user_first->id . ',' . $user_second->id
        ]);
        $response->assertOk();
        
        $response = $this->actingAs($admin)->post('/user/update/multiple', [
            'blocked' => 1,
            'datatable-selected' => $user_first->id . ',' . $user_second->id
        ]);
        $response->assertOk();

        $response = $this->actingAs($poc)->post('/user/update/multiple', [
            'blocked' => 1,
            'datatable-selected' => $poc->id . ',' . $user_first->id . ',' . $user_second->id
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_STATUS_NOT_AUTHORIZED
        ]);
    }

    // PoC updates admin to blocked
    public function test_authenticated_poc_user_update_multiple_admin_to_blocked()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/multiple/', [
            'blocked' => 1,
            'datatable-selected' => $new_user->id
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_STATUS_NOT_AUTHORIZED
        ]);
    }

    public function test_authenticated_admin_user_block_authenticated_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $poc = EcasTestHelper::validateTestUser('poc');

        $response = $this->actingAs($admin)->post('/user/block/toggle/' . $poc->id);
        $response->assertOk();

        $poc->refresh();

        $response = $this->actingAs($poc)->get('/index/access');
        $response->assertOk()->assertViewIs('components.my-account')->assertViewHasAll(['data', 'countries']);
    }

    public function test_unauthenticated_user_delete()
    {
        $user = User::first();
        $response = $this->post('/user/delete/single/' . $user->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_user_delete()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);

        $response = $this->actingAs($poc)->post('/user/delete/single/' . $new_user->id);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'User cannot be deleted as you are not authorized for this action!'
        ]);
                
        $response = $this->actingAs($admin)->post('/user/delete/single/' . $new_user->id);
        $response->assertOk();
    }

    public function test_unauthenticated_user_delete_multiple()
    {
        $response = $this->post('/user/delete/multiple', [
            'datatable-selected' => ''
        ]);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_user_delete_multiple()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $poc = EcasTestHelper::validateTestUser('poc');
        $user_first = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);
        $user_second = TestHelper::createNewUser([
            'permissions' => [
                'role' => $admin->permissions->first()->role->name,
                'country' => $admin->permissions->first()->country->name
            ]
        ]);
        
        $response = $this->actingAs($poc)->post('/user/delete/multiple', [
            'datatable-selected' => $user_first->id . ',' . $user_second->id
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'User cannot be deleted as you are not authorized for this action!'
        ]);
         
        $response = $this->actingAs($admin)->post('/user/delete/multiple', [
            'datatable-selected' => $user_first->id . ',' . $user_second->id
        ]);
        $response->assertOk();
    }

    // Admin updates himself to Primary PoC
    public function test_authenticated_admin_user_update_to_primary_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/user/update/single/' . $admin->id, [
            'country' => Country::first()->name,
            'role' => 'Primary PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(302);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('redirect', '/user/management')
        );
    }

    // Admin updates PoC to Primary PoC
    public function test_authenticated_admin_user_update_poc_to_primary_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $new_user->permissions->first()->country->name,
            'role' => 'Primary PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(302);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('redirect', '/user/management')
        );
    }

    // Admin updates Primary PoC to PoC
    public function test_authenticated_admin_user_update_primary_poc_to_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $country = Country::first()->name;
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'Primary PoC',
                'country' => $country
            ]
        ]);
        $response = $this->actingAs($admin)->post('/user/update/single/' . $new_user->id, [
            'country' => $country,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'warning' => 'This user is the Primary PoC for ' . $country . ' and cannot be updated. Please first assign the Primary PoC role to another user for ' . $country . '!'
        ]);
    }

    // PoC updates PoC to Primary PoC
    public function test_authenticated_poc_user_update_poc_to_primary_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'Primary PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // PoC updates Primary PoC to PoC
    public function test_authenticated_poc_user_update_primary_poc_to_poc()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $poc_country = $poc->permissions->first()->country;
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'Primary PoC',
                'country' => $poc_country->name
            ]
        ]);
        $response = $this->actingAs($poc)->post('/user/update/single/' . $new_user->id, [
            'country' => $poc_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // Primary PoC updates himself to PoC
    public function test_authenticated_primary_poc_user_update_to_poc()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $ppoc_country = $ppoc->permissions->first()->country;
        $response = $this->actingAs($ppoc)->post('/user/update/single/' . $ppoc->id, [
            'country' => $ppoc_country->name,
            'role' => 'PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'warning' => 'This user is the Primary PoC for ' . $ppoc_country->name . ' and cannot be updated. Please first assign the Primary PoC role to another user for ' . $ppoc_country->name . '!'
        ]);
    }

    // Primary PoC updates PoC to Primary PoC
    public function test_authenticated_primary_poc_user_update_poc_to_primary_poc()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $ppoc_country = $ppoc->permissions->first()->country;
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $ppoc_country->name
            ]
        ]);
        $response = $this->actingAs($ppoc)->post('/user/update/single/' . $new_user->id, [
            'country' => $ppoc_country->name,
            'role' => 'Primary PoC',
            'blocked' => 0
        ]);
        $response->assertStatus(302);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('redirect', '/user/management')
        );
    }
}
