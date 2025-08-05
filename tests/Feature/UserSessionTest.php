<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use Illuminate\Support\Facades\Auth;
use Ecas\Client;
use Ecas\Properties\JsonProperties;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSessionTest extends TestCase
{
    use RefreshDatabase;

    protected $_ecas_client;

    protected function setUp(): void
    {
        parent::setUp();
        $_config_file = env('ECAS_CONFIG_FILE', base_path() . '/app/ecas-config/ecas-config-dev.json');
        $this->_ecas_client = new Client(JsonProperties::getInstance($_config_file));
    }

    public function test_authenticated_user_session_expired()
    {
        $user = EcasTestHelper::validateTestUser('admin');

        config(['SESSION_LIFETIME' => '0.1']); // i.e. 60 * 0.1 + last activity from validateTestUser

        sleep(10);

        $response = $this->actingAs($user)->get('/index/access');
        $response->assertOk()->assertViewIs('components.logout')->assertViewHasAll(['reason' => 'due to inactivity timeout']);

        $this->assertNotTrue($this->_ecas_client->getAuthenticatedUser());
        $this->assertNotTrue(Auth::check());
    }

    public function test_authenticated_user_session_expired_ajax()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $poc = EcasTestHelper::validateTestUser('poc');

        config(['SESSION_LIFETIME' => '0.1']); // i.e. 60 * 0.1 + last activity from validateTestUser

        sleep(10);

        $response = $this->ajaxPost($admin, '/user/block/toggle/' . $poc->id);
        $response->assertUnauthorized();
        $response->assertJson([
            'error' => 'Unauthenticated'
        ]);

        $response = $this->actingAs($admin)->get('/index/access');
        $response->assertOk()->assertViewIs('components.logout')->assertViewHasAll(['reason' => 'due to inactivity timeout']);

        $this->assertNotTrue($this->_ecas_client->getAuthenticatedUser());
        $this->assertNotTrue(Auth::check());
    }
}
