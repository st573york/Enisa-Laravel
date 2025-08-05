<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexPropertiesAreaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_index_area_list()
    {
        $response = $this->get('/index/area/list/' . date('Y'));
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_area_list()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/index/area/list/' . date('Y'));
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }
}
