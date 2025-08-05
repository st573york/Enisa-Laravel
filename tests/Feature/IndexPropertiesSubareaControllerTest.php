<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexPropertiesSubareaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_index_subarea_list()
    {
        $response = $this->get('/index/subarea/list/year');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_subarea_list()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/index/subarea/list/' . date('Y'));
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }
}
