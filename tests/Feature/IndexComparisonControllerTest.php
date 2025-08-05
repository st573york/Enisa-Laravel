<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use App\Models\IndexConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexComparisonControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_index_comparison_view()
    {
        $response = $this->get('/index/access');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authorized_index_comparison_view()
    {
        $user = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($user)->get('/index/access');
        $response->assertOk()->assertViewIs('index.comparison')->assertViewHasAll(['years']);

        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/index/access');
        $response->assertOk()->assertViewIs('index.comparison')->assertViewHasAll(['years']);
    }

    public function test_unauthorized_index_call()
    {
        $response = $this->get('/index/configurations/get/Index/year');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_unauthorized_sunburst_call()
    {
        $response = $this->get('/index/sunburst/get');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authorized_index_call()
    {
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();

        $user = EcasTestHelper::validateTestUser('operator');
        $response = $this->actingAs($user)->get('/index/configurations/get/Index/' . $published_index->year);
        $response->assertOk();

        $user = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($user)->get('/index/configurations/get/Index/' . $published_index->year);
        $response->assertOk();

        $user = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($user)->get('/index/configurations/get/subarea-1/' . $published_index->year);
        $response->assertOk();

        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/index/configurations/get/area-1/' . $published_index->year);
        $response->assertOk();
    }

    public function test_authorized_sunburst_call()
    {
        $_COOKIE['index-year'] = IndexConfiguration::getLatestPublishedConfiguration();

        $user = EcasTestHelper::validateTestUser('operator');
        $response = $this->actingAs($user)->get('/index/sunburst/get');
        $response->assertOk();

        $user = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($user)->get('/index/sunburst/get');
        $response->assertOk();

        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/index/sunburst/get');
        $response->assertOk();
    }

    public function test_tree_chart_view()
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');
        
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();

        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/index/render/slider/' . $published_index->year);
        $response->assertOk()->assertViewIs('ajax.index-comparison')->assertViewHasAll(['data']);

        $user = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($user)->get('/index/render/slider/' . $published_index->year);
        $response->assertOk()->assertViewIs('ajax.index-comparison')->assertViewHasAll(['data']);
    }
}
