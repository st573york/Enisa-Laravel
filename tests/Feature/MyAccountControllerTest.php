<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use App\HelperFunctions\TestHelper;
use App\Models\Country;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_my_account()
    {
        $response = $this->get('/my-account');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authorized_my_account()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/my-account');
        $response->assertOk()->assertViewIs('components.my-account')->assertViewHasAll(['data', 'countries']);
    }

    public function test_unauthenticated_my_account_update()
    {
        $response = $this->post('/my-account/update', []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_required_my_account_update()
    {
        $user = EcasTestHelper::validateTestUser('admin');

        $response = $this->actingAs($user)->post('/my-account/update', [
            'name' => Str::random(5),
            'email' => Str::random(5),
            'country_code' => ''
        ]);
        $response->assertStatus(400);
        $response->assertJson([
            'name' => [
                'Name can\'t be updated.'
            ],
            'email' => [
                'Email can\'t be updated.'
            ],
            'country_code' => [
                'The country field is required.'
            ]
        ]);
    }

    public function test_authenticated_my_account_update_new_registration()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'exclude_fields' => true,
            'permissions' => [
                'role' => $poc->permissions->first()->role->name,
                'country' => $poc->permissions->first()->country->name
            ]
        ]);
        $response = $this->actingAs($new_user)->post('/my-account/update', [
            'country_code' => $poc->permissions->first()->country->code
        ]);
        $response->assertOk();
        $response->assertExactJson([
            'success' => 'User details have been successfully updated!'
        ]);
    }

    public function test_authenticated_my_account_update_new_registration_blocked()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $new_user = TestHelper::createNewUser([
            'blocked' => 1,
            'exclude_fields' => true,
            'permissions' => [
                'role' => $poc->permissions->first()->role->name,
                'country' => $poc->permissions->first()->country->name
            ]
        ]);
        $response = $this->actingAs($new_user)->post('/my-account/update', [
            'country_code' => $poc->permissions->first()->country->code
        ]);
        $response->assertOk();
        $response->assertExactJson([
            'success' => 'User details have been successfully updated!'
        ]);
    }

    public function test_authenticated_my_account_update()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $country_second = Country::skip(1)->first();
        $response = $this->actingAs($user)->post('/my-account/update', [
            'country_code' => $country_second->code
        ]);
        $response->assertOk();
        $response->assertExactJson([
            'success' => 'User details have been successfully updated!'
        ]);
    }
}
