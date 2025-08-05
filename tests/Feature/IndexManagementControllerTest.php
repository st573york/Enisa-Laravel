<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use App\Models\IndexConfiguration;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_index_management()
    {
        $response = $this->get('/index/management');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_non_admin_user_index_management()
    {
        $user = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($user)->get('/index/management');
        $response->assertRedirect('/access/denied/');
    }

    public function test_authorized_index_management()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/index/management');
        $response->assertOk()->assertViewIs('index.management');
    }

    public function test_unauthenticated_index_list()
    {
        $response = $this->get('/index/list');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_list()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/index/list');
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_index_create()
    {
        $response = $this->get('/index/create');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_create()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/index/create');
        $response->assertOk()->assertViewIs('ajax.index-create')->assertViewHasAll(['years']);
    }

    public function test_unauthenticated_index_store()
    {
        $response = $this->post('/index/store', []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_required_index_store()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/index/store', [
            'name' => '',
            'year' => ''
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'name' => [
                'The name field is required.'
            ],
            'year' => [
                'The year field is required.'
            ]
        ]);
    }

    public function test_unique_index_store()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $index = IndexConfiguration::first();
        $response = $this->actingAs($user)->post('/index/store', [
            'name' => $index->name,
            'year' => $index->year
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'name' => [
                'The name has already been taken.'
            ]
        ]);
    }

    public function test_authenticated_index_store()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        
        $response = $this->actingAs($user)->post('/index/store', [
            'name' => Str::random(40),
            'year' => date('Y')
        ]);
        $response->assertOk();
    }

    public function test_unauthenticated_index_delete()
    {
        $index =  IndexConfiguration::where('draft', false)->first();
        $response = $this->post('/index/delete/' . $index->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_draft_delete()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $index = IndexConfiguration::where('draft', true)->first();
        $response = $this->actingAs($user)->post('/index/delete/' . $index->id);
        $response->assertOk();
    }

    public function test_authenticated_index_published_delete()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $index = IndexConfiguration::where('draft', false)->first();
        $response = $this->actingAs($user)->post('/index/delete/' . $index->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => 'Index cannot be deleted as it is published.'
        ]);
    }

    public function test_unauthenticated_show_index()
    {
        $index =  IndexConfiguration::where('draft', false)->first();
        $response = $this->get('/index/show/' . $index->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_show_index()
    {
        $user = EcasTestHelper::validateTestUser('admin');

        $index =  IndexConfiguration::where('draft', false)->first();
        $response = $this->actingAs($user)->get('/index/show/' . $index->id);
        $response->assertOk()->assertViewIs('index.view')->assertViewHasAll(['index', 'years']);

        $index = IndexConfiguration::where('draft', true)->first();
        $response = $this->actingAs($user)->get('/index/show/' . $index->id);
        $response->assertOk()->assertViewIs('index.view')->assertViewHasAll(['index', 'years']);
    }

    public function test_unauthenticated_get_index_json()
    {
        $index =  IndexConfiguration::where('draft', false)->first();
        $response = $this->get('/index/json/' . $index->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_get_index_json()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $index =  IndexConfiguration::where('draft', false)->first();
        $response = $this->actingAs($user)->call('get', '/index/json/' . $index->id, [
            'indexYear' => null
        ]);
        $response->assertOk()->assertJsonStructure(['areas', 'subareas', 'indicators', 'indexJson']);
    }

    public function test_unauthenticated_index_tree()
    {
        $index =  IndexConfiguration::where('draft', false)->first();
        $response = $this->post('/index/tree/' . $index->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_index_tree_with_empty_json()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $index =  IndexConfiguration::where('draft', false)->first();
        $response = $this->actingAs($user)->post('/index/tree/' . $index->id, []);
        $response->assertOk()->assertViewIs('ajax.index-tree')->assertViewHasAll(['index', 'tree']);
    }

    public function test_authenticated_index_tree_with_json()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $index =  IndexConfiguration::where('draft', false)->first();
        $response = $this->actingAs($user)->post('/index/tree/' . $index->id, [
            'indexJson' => $index->json_data
        ]);
        $response->assertOk()->assertViewIs('ajax.index-tree')->assertViewHasAll(['index', 'tree']);
    }

    public function test_unauthenticated_edit_index()
    {
        $index =  IndexConfiguration::where('draft', false)->first();
        $response = $this->post('/index/edit/' . $index->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_edit_index()
    {
        // $this->markTestIncomplete('This test requires data in order to be completed.');
        
        $user = EcasTestHelper::validateTestUser('admin');
        $draft_index = IndexConfiguration::where('draft', true)->latest('year')->first();
        $published_index = IndexConfiguration::where('draft', false)->orderBy('year')->first();
        $latest_published_index = IndexConfiguration::getLatestPublishedConfiguration();

        $draft_index->json_data = $latest_published_index->json_data;
        $draft_index->save();

        $published_index->draft = true;
        $published_index->save();
        
        $response = $this->actingAs($user)->post('/index/edit/' . $draft_index->id, [
            'id' => $draft_index->id,
            'name' => '',
            'description' => $draft_index->description,
            'year' => '',
            'draft' => $draft_index->draft,
            'eu_published' => $draft_index->eu_published,
            'ms_published' => $draft_index->ms_published,
            'json_data' => $draft_index->json_data
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'name' => [
                'The name field is required.'
            ],
            'year' => [
                'The year field is required.'
            ]
        ]);

        $response = $this->actingAs($user)->post('/index/edit/' . $draft_index->id, [
            'id' => $draft_index->id,
            'name' => $latest_published_index->name,
            'year' => $draft_index->year,
            'draft' => $draft_index->draft,
            'eu_published' => $draft_index->eu_published,
            'ms_published' => $draft_index->ms_published,
            'json_data' => $draft_index->json_data
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'name' => [
                'The name has already been taken.'
            ]
        ]);

        $response = $this->actingAs($user)->post('/index/edit/' . $draft_index->id, [
            'id' => $draft_index->id,
            'name' => $draft_index->name,
            'year' => $draft_index->year,
            'draft' => 'false',
            'eu_published' => 'false',
            'ms_published' => 'false',
            'json_data' => $draft_index->json_data
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => 'Another official configuration already exists for this year!'
        ]);

        $response = $this->actingAs($user)->post('/index/edit/' . $latest_published_index->id, [
            'id' => $latest_published_index->id,
            'name' => $latest_published_index->name,
            'year' => $latest_published_index->year,
            'draft' => 'true',
            'eu_published' => 'true',
            'ms_published' => 'true',
            'json_data' => $latest_published_index->json_data
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => 'There are no other official configurations exist in the tool!'
        ]);
        
        $response = $this->actingAs($user)->post('/index/edit/' . $draft_index->id, [
            'id' => $draft_index->id,
            'name' => $draft_index->name,
            'year' => $draft_index->year + 1,
            'draft' => 'false',
            'eu_published' => 'false',
            'ms_published' => 'false',
            'json_data' => $draft_index->json_data
        ]);
        $response->assertOk();
        
        $response = $this->actingAs($user)->post('/index/edit/' . $latest_published_index->id, [
            'id' => $latest_published_index->id,
            'name' => $latest_published_index->name,
            'year' => $latest_published_index->year,
            'draft' => 'true',
            'eu_published' => 'true',
            'ms_published' => 'true',
            'json_data' => $latest_published_index->json_data
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => 'This configuration has active indexes!'
        ]);

        $response = $this->actingAs($user)->post('/index/edit/' . $latest_published_index->id, [
            'id' => $latest_published_index->id,
            'draft' => $latest_published_index->draft,
            'eu_published' => $latest_published_index->eu_published,
            'ms_published' => $latest_published_index->ms_published,
            'json_data' => $latest_published_index->json_data
        ]);
        $response->assertOk();
    }
}
