<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use App\HelperFunctions\TestHelper;
use App\HelperFunctions\QuestionnaireHelper;
use App\Models\Country;
use App\Models\Questionnaire;
use App\Models\IndexConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuestionnaireControllerTest extends TestCase
{
    use RefreshDatabase;

    const ERROR_NOT_AUTHORIZED = 'Indicator cannot be updated as you are not authorized for this action!';
    const ERROR_NOT_ALLOWED = 'Indicator cannot be updated as the requested action is not allowed!';

    public function test_unauthenticated_questionnaire_admin_management()
    {
        $response = $this->get('/questionnaire/admin/management');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_admin_management()
    {
        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->get('/questionnaire/admin/management');
        $response->assertRedirect('/access/denied/');

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/questionnaire/admin/management');
        $response->assertOk()->assertViewIs('questionnaire.admin-management');
    }

    public function test_unauthenticated_questionnaire_admin_management_list()
    {
        $response = $this->get('/questionnaire/admin/management/list');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_admin_management_list()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/questionnaire/admin/management/list');
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_questionnaire_management_create()
    {
        $response = $this->get('/questionnaire/create');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_management_create()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/questionnaire/create');
        $response->assertOk()->assertViewIs('ajax.questionnaire-management')->assertViewHasAll(['action', 'data', 'published_indexes']);
    }

    public function test_unauthenticated_questionnaire_management_store()
    {
        $response = $this->post('/questionnaire/store', []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_required_questionnaire_management_store()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/questionnaire/store', [
            'index_configuration_id' => '',
            'title' => '',
            'deadline' => ''
        ]);
        $response->assertStatus(400);
        $response->assertExactJson(['errors' => [
                'index_configuration_id' => [
                    'The index field is required.'
                ],
                'title' => [
                    'The title field is required.'
                ],
                'deadline' => [
                    'The deadline field is required.'
                ]
            ],
            'type' => 'pageModalForm'
        ]);
    }

    public function test_unique_questionnaire_management_store()
    {
        $questionnaire = Questionnaire::first();
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/questionnaire/store', [
            'index_configuration_id' => $published_index->id,
            'title' => $questionnaire->title,
            'deadline' => date('Y-m-d')
        ]);
        $response->assertStatus(400);
        $response->assertExactJson(['errors' => [
                'title' => [
                    'The title has already been taken.'
                ]
            ],
            'type' => 'pageModalForm'
        ]);
    }

    public function test_authenticated_questionnaire_management_store()
    {
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/questionnaire/store', [
            'index_configuration_id' => $published_index->id,
            'title' => Str::random(40),
            'description' => Str::random(40),
            'deadline' => date('Y-m-d')
        ]);
        $response->assertOk();
    }

    public function test_unauthenticated_questionnaire_management_show()
    {
        $questionnaire = Questionnaire::first();
        $response = $this->get('/questionnaire/show/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_management_show()
    {
        $questionnaire = Questionnaire::first();
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/questionnaire/show/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('ajax.questionnaire-management')->assertViewHasAll(['action', 'data', 'published_indexes']);
    }

    public function test_unauthenticated_questionnaire_management_update()
    {
        $questionnaire = Questionnaire::first();
        $response = $this->post('/questionnaire/update/' . $questionnaire->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_required_questionnaire_management_update()
    {
        $questionnaire = Questionnaire::first();
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/questionnaire/update/' . $questionnaire->id, [
            'id' => $questionnaire->id,
            'index_configuration_id' => '',
            'title' => '',
            'deadline' => ''
        ]);
        $response->assertStatus(400);
        $response->assertExactJson(['errors' => [
                'index_configuration_id' => [
                    'The index field is required.'
                ],
                'title' => [
                    'The title field is required.'
                ],
                'deadline' => [
                    'The deadline field is required.'
                ]
            ],
            'type' => 'pageModalForm'
        ]);
    }

    public function test_unique_questionnaire_management_update()
    {
        $questionnaire_first = Questionnaire::first();
        $questionnaire_second = Questionnaire::skip(1)->first();
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/questionnaire/update/' . $questionnaire_first->id, [
            'id' => $questionnaire_first->id,
            'index_configuration_id' => $published_index->id,
            'title' => $questionnaire_second->title,
            'description' => $questionnaire_first->description,
            'deadline' => $questionnaire_first->deadline
        ]);
        $response->assertStatus(400);
        $response->assertExactJson(['errors' => [
                'title' => [
                    'The title has already been taken.'
                ]
            ],
            'type' => 'pageModalForm'
        ]);
    }

    public function test_authenticated_questionnaire_management_update()
    {
        $questionnaire = Questionnaire::first();
        $published_index = IndexConfiguration::getLatestPublishedConfiguration();
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/questionnaire/update/' . $questionnaire->id, [
            'id' => $questionnaire->id,
            'index_configuration_id' => $published_index->id,
            'title' => $questionnaire->title,
            'description' => $questionnaire->description,
            'deadline' => $questionnaire->deadline
        ]);
        $response->assertOk();
    }

    public function test_unauthenticated_questionnaire_management_publish_show()
    {
        $questionnaire = Questionnaire::first();
        $response = $this->get('/questionnaire/publish/show/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_management_publish_show()
    {
        $questionnaire = Questionnaire::first();
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/questionnaire/publish/show/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('ajax.questionnaire-management')->assertViewHasAll(['action', 'data']);
    }

    public function test_unauthenticated_questionnaire_management_users()
    {
        $questionnaire = Questionnaire::first();
        $response = $this->get('/questionnaire/users/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_management_users()
    {
        $questionnaire = Questionnaire::first();
        $admin = EcasTestHelper::validateTestUser('admin');
        $other_country = Country::where('name', '!=', $admin->permissions->first()->country->name)->first();
        TestHelper::createNewUser([
            'permissions' => [
                'role' => 'Primary PoC',
                'country' => $other_country->name
            ]
        ]);
        
        $response = $this->actingAs($admin)->get('/questionnaire/users/' . $questionnaire->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        $decodedJSON = $response->decodeResponseJson();
        $actual_users = TestHelper::getActualUsers($decodedJSON['data']);
        $expected_users = TestHelper::getExpectedUsers([5]);
        $this->assertEquals($expected_users, $actual_users,
            'Expected users -> ' . implode(', ', $expected_users) . "\n" .
            'Actual users -> ' . implode(', ', $actual_users));
    }

    public function test_unauthenticated_questionnaire_management_publish_create()
    {
        $questionnaire = Questionnaire::first();
        $response = $this->post('/questionnaire/publish/create/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_management_publish_create_not_selected_users()
    {
        $questionnaire = Questionnaire::first();
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/questionnaire/publish/create/' . $questionnaire->id, [
            'notify_users' => 'radio-specific',
            'datatable-selected' => '',
            'datatable-all' => ''
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => 'You haven\'t selected any users!',
            'type' => 'pageModalAlert'
        ]);
    }

    public function test_authenticated_questionnaire_management_publish_create_selected_users()
    {
        $questionnaire = Questionnaire::first();
        $admin = EcasTestHelper::validateTestUser('admin');
        $other_country = Country::where('name', '!=', $admin->permissions->first()->country->name)->first();
        TestHelper::createNewUser([
            'permissions' => [
                'role' => 'Primary PoC',
                'country' => $other_country->name
            ]
        ]);
        $questionnaire_users = QuestionnaireHelper::getQuestionnaireUsers($questionnaire);
        $selected_users = [];
        foreach ($questionnaire_users as $questionnaire_user) {
            array_push($selected_users, $questionnaire_user->id);
        }

        $response = $this->actingAs($admin)->post('/questionnaire/publish/create/' . $questionnaire->id, [
            'notify_users' => 'radio-specific',
            'datatable-selected' => $selected_users[0],
            'datatable-all' => $selected_users[0]
        ]);
        $response->assertOk();
    }

    public function test_authenticated_questionnaire_management_publish_create_all_users()
    {
        $questionnaire = Questionnaire::first();
        $admin = EcasTestHelper::validateTestUser('admin');
        $other_country = Country::where('name', '!=', $admin->permissions->first()->country->name)->first();
        TestHelper::createNewUser([
            'permissions' => [
                'role' => 'Primary PoC',
                'country' => $other_country->name
            ]
        ]);
        $questionnaire_users = QuestionnaireHelper::getQuestionnaireUsers($questionnaire);
        $selected_users = [];
        foreach ($questionnaire_users as $questionnaire_user) {
            array_push($selected_users, $questionnaire_user->id);
        }

        $response = $this->actingAs($admin)->post('/questionnaire/publish/create/' . $questionnaire->id, [
            'notify_users' => 'radio-all',
            'datatable-selected' => $selected_users[0],
            'datatable-all' => implode(',', $selected_users)
        ]);
        $response->assertOk();
    }

    public function test_unauthenticated_questionnaire_management_sendreminder()
    {
        $questionnaire = Questionnaire::first();
        $response = $this->post('/questionnaire/sendreminder/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_management_sendreminder()
    {
        $questionnaire = Questionnaire::first();
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/questionnaire/sendreminder/' . $questionnaire->id);
        $response->assertOk();
    }

    public function test_unauthenticated_questionnaire_management_delete()
    {
        $questionnaire = Questionnaire::first();
        $response = $this->post('/questionnaire/delete/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_management_draft_delete()
    {
        $questionnaire = Questionnaire::where('published', false)->first();
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/questionnaire/delete/' . $questionnaire->id);
        $response->assertOk();
    }

    public function test_authenticated_questionnaire_management_published_delete()
    {
        $questionnaire = Questionnaire::where('published', true)->first();
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->post('/questionnaire/delete/' . $questionnaire->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => 'Survey cannot be deleted as it is published!',
            'type' => 'pageAlert'
        ]);
    }
}
