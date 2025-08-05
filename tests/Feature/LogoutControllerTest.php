<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use Tests\TestCase;

class LogoutControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_logout()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->get('/logout');
        $response->assertStatus(200);
    }
}
