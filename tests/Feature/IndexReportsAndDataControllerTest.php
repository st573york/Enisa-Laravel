<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use App\Models\Country;
use App\Models\Index;
use App\Models\IndexConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexReportsAndDataControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_export_data()
    {
        $response = $this->get('/index/report/export_data');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authorized_export_data()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $_COOKIE['index-year'] = IndexConfiguration::getLatestPublishedConfiguration();
        $response = $this->actingAs($user)->get('/index/report/export_data');
        $response->assertOk()->assertViewIs('components.export_data')->assertViewHasAll([
            'loaded_index_data',
            'years',
            'indices',
            'baseline_index'
        ]);
    }

    public function test_unauthorized_index_report_json()
    {
        $country = Country::first();

        $response = $this->get('/index/report/json/' . $country->id);
        $response->assertOk()->assertViewIs('components.auth-failed');

        $user = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($user)->get('/index/report/json/' . $country->id);
        $response->assertStatus(403)->assertSee('You are not authorized!');
    }

    public function test_authorized_index_report_json()
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');

        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $index = Index::where('index_configuration_id', $published_index->id)->first();
        $response = $this->actingAs($user)->get('/index/report/json/' . $index->id);
        $response->assertOk()->assertViewIs('components.reports')->assertViewHasAll(['index', 'data', 'country']);
    }

    public function test_unauthorized_index_report_chart_data()
    {
        $country = Country::first();

        $response = $this->get('/index/report/chartData/' . $country->id);
        $response->assertOk()->assertViewIs('components.auth-failed');

        $user = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($user)->get('/index/report/chartData/' . $country->id);
        $response->assertStatus(403)->assertSee('You are not authorized!');
    }

    public function test_authorized_index_report_chart_data()
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');
        
        $user = EcasTestHelper::validateTestUser('admin');
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $index = Index::where('index_configuration_id', $published_index->id)->first();
        $response = $this->actingAs($user)->get('/index/report/chartData/' . $index->id);
        $response->assertOk();
    }
}
