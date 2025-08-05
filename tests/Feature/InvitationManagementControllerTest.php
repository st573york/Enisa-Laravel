<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use App\HelperFunctions\TestHelper;
use App\HelperFunctions\UserPermissions;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvitationManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    const ERROR_NOT_AUTHORIZED = 'User cannot be invited as you are not authorized for this action!';
    const ERROR_NOT_ALLOWED = 'User cannot be invited as the requested action is not allowed!';

    public function assertInvitations($invitations)
    {
        $expected_countries = UserPermissions::getUserCountries('name');
        $unexpected_countries = [];

        foreach ($invitations as $invitation)
        {
            if (!in_array($invitation['country'], $expected_countries)) {
                array_push($unexpected_countries, $invitation['country']);
            }
        }

        $this->assertEmpty($unexpected_countries,
            'Expected countries -> ' . implode(', ', $expected_countries) . "\n" .
            'Unexpected countries -> ' . implode(', ', $unexpected_countries));
    }

    public function test_unauthenticated_invitation_management()
    {
        $response = $this->get('/invitation/management');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_invitation_management()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->get('/invitation/management');
        $response->assertRedirect('/access/denied/');

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->get('/invitation/management');
        $response->assertOk()->assertViewIs('invitation.management');

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/invitation/management');
        $response->assertOk()->assertViewIs('invitation.management');
    }

    public function test_unauthenticated_invitation_create()
    {
        $response = $this->get('/invitation/create');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_invitation_create()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->get('/invitation/create');
        $response->assertOk()->assertViewIs('ajax.invitation-create')->assertViewHasAll(['countries', 'roles']);

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/invitation/create');
        $response->assertOk()->assertViewIs('ajax.invitation-create')->assertViewHasAll(['countries', 'roles']);
    }

    public function test_unauthenticated_invitation_store()
    {
        $response = $this->post('/invitation/store', []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_required_invitation_store()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/invitation/store', [
            'firstname' => '',
            'lastname' => '',
            'email' => '',
            'country' => '',
            'role' => ''
        ]);
        $response->assertStatus(400);
        $response->assertExactJson(['errors' => [
                'firstname' => [
                    'The first name field is required.'
                ],
                'lastname' => [
                    'The last name field is required.'
                ],
                'email' => [
                    'The email field is required.'
                ],
                'country' => [
                    'The country field is required.'
                ],
                'role' => [
                    'The role field is required.'
                ]
            ],
            'type' => 'pageModalForm'
        ]);
    }

    public function test_authenticated_invalid_email_invitation_store()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => Str::random(10),
            'country' => Country::first()->name,
            'role' => 'PoC'
        ]);
        $response->assertStatus(400);
        $response->assertExactJson(['errors' => [
                'email' => [
                    'The email must be a valid email address.'
                ]
            ],
            'type' => 'pageModalForm'
        ]);
    }

    public function test_authenticated_registered_email_invitation_store()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => $user->email,
            'country' => Country::first()->name,
            'role' => 'PoC'
        ]);
        $response->assertStatus(400);
        $response->assertExactJson(['errors' => [
                'email' => [
                    'The email is already registered.'
                ]
            ],
            'type' => 'pageModalForm'
        ]);
    }

    public function test_authenticated_deleted_user_invitation_store()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $new_user = TestHelper::createNewUser([
            'trashed' => true,
            'permissions' => [
                'role' => 'PoC',
                'country' => Country::first()->name
            ]
        ]);

        $response = $this->actingAs($user)->post('/invitation/store', [
            'firstname' => $new_user->firstName,
            'lastname' => $new_user->lastName,
            'email' => $new_user->email,
            'country' => $new_user->permissions()->withTrashed()->first()->country->name,
            'role' => $new_user->permissions()->withTrashed()->first()->role->name
        ]);
        $response->assertOk();
    }

    public function test_authenticated_invitation_store()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => Country::first()->name,
            'role' => 'PoC'
        ]);
        $response->assertOk();
    }

    public function test_unauthenticated_invitation_list()
    {
        $response = $this->get('/invitation/list');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_invitation_list()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $admin = EcasTestHelper::validateTestUser('admin');
        $other_country = Country::where('name', '!=', $ppoc->permissions->first()->country->name)->first();

        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => $ppoc->permissions->first()->country->name,
            'role' => 'PoC'
        ]);
        $response->assertOk();

        $response = $this->actingAs($admin)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => $other_country->name,
            'role' => 'PoC'
        ]);
        $response->assertOk();
        
        $response = $this->actingAs($ppoc)->get('/invitation/list');
        $response->assertOk();
        $response->assertJsonStructure(['data']);
               
        $decodedJSON = $response->decodeResponseJson();
        $this->assertInvitations($decodedJSON['data']);

        $response = $this->actingAs($admin)->get('/invitation/list');
        $response->assertOk();
        $response->assertJsonStructure(['data']);
               
        $decodedJSON = $response->decodeResponseJson();
        $this->assertInvitations($decodedJSON['data']);
    }

    // Admin invites ENISA/admin
    public function test_authenticated_admin_invites_enisa_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => config('constants.USER_GROUP'),
            'role' => 'admin'
        ]);
        $response->assertOk();
    }

    // Admin invites COUNTRY/admin
    public function test_authenticated_admin_invites_country_admin()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => Country::first()->name,
            'role' => 'admin'
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin invites ENISA/Primary PoC
    public function test_authenticated_admin_invites_enisa_primary_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => config('constants.USER_GROUP'),
            'role' => 'Primary PoC'
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin invites COUNTRY/Primary PoC
    public function test_authenticated_admin_invites_country_primary_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => Country::first()->name,
            'role' => 'Primary PoC'
        ]);
        $response->assertOk();
    }

    // Admin invites ENISA/PoC
    public function test_authenticated_admin_invites_enisa_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => config('constants.USER_GROUP'),
            'role' => 'PoC'
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin invites COUNTRY/PoC
    public function test_authenticated_admin_invites_country_poc()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => Country::first()->name,
            'role' => 'PoC'
        ]);
        $response->assertOk();
    }

    // Admin invites ENISA/operator
    public function test_authenticated_admin_invites_enisa_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => config('constants.USER_GROUP'),
            'role' => 'operator'
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Admin invites COUNTRY/operator
    public function test_authenticated_admin_invites_country_operator()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => Country::first()->name,
            'role' => 'operator'
        ]);
        $response->assertOk();
    }

    // Admin invites ENISA/viewer
    public function test_authenticated_admin_invites_enisa_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => config('constants.USER_GROUP'),
            'role' => 'viewer'
        ]);
        $response->assertOk();
    }

    // Admin invites COUNTRY/viewer
    public function test_authenticated_admin_invites_country_viewer()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => Country::first()->name,
            'role' => 'viewer'
        ]);
        $response->assertOk();
    }

    // Primary PoC invites ENISA/admin
    public function test_authenticated_primary_poc_invites_enisa_admin()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => config('constants.USER_GROUP'),
            'role' => 'admin'
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // Primary PoC invites COUNTRY/admin
    public function test_authenticated_primary_poc_invites_country_admin()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => $ppoc->permissions->first()->country->name,
            'role' => 'admin'
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Primary PoC invites other COUNTRY/admin
    public function test_authenticated_primary_poc_invites_other_country_admin()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $other_country = Country::where('name', '!=', $ppoc->permissions->first()->country->name)->first();
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => $other_country->name,
            'role' => 'admin'
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Primary PoC invites ENISA/Primary PoC
    public function test_authenticated_primary_poc_invites_enisa_primary_poc()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => config('constants.USER_GROUP'),
            'role' => 'Primary PoC'
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Primary PoC invites COUNTRY/Primary PoC
    public function test_authenticated_primary_poc_invites_country_primary_poc()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => $ppoc->permissions->first()->country->name,
            'role' => 'Primary PoC'
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // Primary PoC invites other COUNTRY/Primary PoC
    public function test_authenticated_primary_poc_invites_other_country_primary_poc()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $other_country = Country::where('name', '!=', $ppoc->permissions->first()->country->name)->first();
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => $other_country->name,
            'role' => 'Primary PoC'
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // Primary PoC invites ENISA/PoC
    public function test_authenticated_primary_poc_invites_enisa_poc()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => config('constants.USER_GROUP'),
            'role' => 'PoC'
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Primary PoC invites COUNTRY/PoC
    public function test_authenticated_primary_poc_invites_country_poc()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => $ppoc->permissions->first()->country->name,
            'role' => 'PoC'
        ]);
        $response->assertOk();
    }

    // Primary PoC invites other COUNTRY/PoC
    public function test_authenticated_primary_poc_invites_other_country_poc()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $other_country = Country::where('name', '!=', $ppoc->permissions->first()->country->name)->first();
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => $other_country->name,
            'role' => 'PoC'
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // Primary PoC invites ENISA/operator
    public function test_authenticated_primary_poc_invites_enisa_operator()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => config('constants.USER_GROUP'),
            'role' => 'operator'
        ]);
        $response->assertStatus(405);
        $response->assertJson([
            'error' => self::ERROR_NOT_ALLOWED
        ]);
    }

    // Primary PoC invites COUNTRY/operator
    public function test_authenticated_primary_poc_invites_country_operator()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => $ppoc->permissions->first()->country->name,
            'role' => 'operator'
        ]);
        $response->assertOk();
    }

    // Primary PoC invites other COUNTRY/operator
    public function test_authenticated_primary_poc_invites_other_country_operator()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $other_country = Country::where('name', '!=', $ppoc->permissions->first()->country->name)->first();
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => $other_country->name,
            'role' => 'operator'
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // Primary PoC invites ENISA/viewer
    public function test_authenticated_primary_poc_invites_enisa_viewer()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => config('constants.USER_GROUP'),
            'role' => 'viewer'
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }

    // Primary PoC invites COUNTRY/viewer
    public function test_authenticated_primary_poc_invites_country_viewer()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => $ppoc->permissions->first()->country->name,
            'role' => 'viewer'
        ]);
        $response->assertOk();
    }

    // Primary PoC invites other COUNTRY/viewer
    public function test_authenticated_primary_poc_invites_other_country_viewer()
    {
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $other_country = Country::where('name', '!=', $ppoc->permissions->first()->country->name)->first();
        $response = $this->actingAs($ppoc)->post('/invitation/store', [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'country' => $other_country->name,
            'role' => 'viewer'
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);
    }
}
