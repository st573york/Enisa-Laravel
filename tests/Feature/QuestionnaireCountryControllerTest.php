<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use App\HelperFunctions\TaskHelper;
use App\HelperFunctions\TestHelper;
use App\HelperFunctions\GeneralHelper;
use App\HelperFunctions\QuestionnaireCountryHelper;
use App\HelperFunctions\QuestionnaireHelper;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use App\Models\Indicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Carbon\Carbon;
use Tests\TestCase;

class QuestionnaireCountryControllerTest extends TestCase
{
    use RefreshDatabase;

    const ERROR_NOT_AUTHORIZED = 'Indicator cannot be updated as you are not authorized for this action!';
    const UPDATE_NOT_ALLOWED = 'Indicator cannot be updated as the requested action is not allowed!';
    const REQUESTED_CHANGES_NOT_ALLOWED = 'Changes cannot be requested as the requested action is not allowed!';
    const INDICATORVALUESCALCULATIONTASK = 'IndicatorValuesCalculation';

    protected $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed --class=TestQuestionnaireIndicatorsWithAnswersSeeder');
        $this->today = Carbon::now()->format('d-m-Y H:i:s');
    }

    public function getAnswers($configuration_json)
    {
        $answers = [];

        array_push($answers, $configuration_json['form'][0]['contents'][0]);

        return json_encode($answers);
    }

    public function assertQuestionnaireIndicatorValues($questionnaire, $status)
    {
        $response = Livewire::test(\App\Http\Livewire\QuestionnaireIndicatorValues::class, ['questionnaire' => $questionnaire]);
        $response->assertOk()
            ->assertViewIs('livewire.questionnaire-indicator-values')
            ->assertViewHas('published_questionnaires')
            ->assertViewHas('loaded_questionnaire_data')
            ->assertViewHas('latest_questionnaire_data')
            ->assertViewHas('task')
            ->assertSeeText($status)
            ->assertEmitted('indicatorValuesCalculation' . preg_replace('/[\s_]/', '', $status));
    }

    public function test_unauthenticated_questionnaire_management()
    {
        $response = $this->get('/questionnaire/management');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_management()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/questionnaire/management');
        $response->assertRedirect('/access/denied/');

        $viewer = EcasTestHelper::validateTestUser('viewer');
        $response = $this->actingAs($viewer)->get('/questionnaire/management');
        $response->assertRedirect('/access/denied/');

        $poc = EcasTestHelper::validateTestUser('poc');
        $response = $this->actingAs($poc)->get('/questionnaire/management');
        $response->assertOk()->assertViewIs('questionnaire.management')->assertViewHasAll(['questionnaires', 'questionnaires_assigned']);
        $data = $response->getOriginalContent()->getData();
        $this->assertNotEmpty($data['questionnaires_assigned']);

        $operator = EcasTestHelper::validateTestUser('operator');
        $response = $this->actingAs($operator)->get('/questionnaire/management');
        $response->assertOk()->assertViewIs('questionnaire.management')->assertViewHasAll(['questionnaires', 'questionnaires_assigned']);
        $data = $response->getOriginalContent()->getData();
        $this->assertEmpty($data['questionnaires_assigned']);
    }

    public function test_unauthenticated_questionnaire_admin_dashboard()
    {
        $questionnaire = Questionnaire::first();
        $response = $this->get('/questionnaire/admin/dashboard/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_admin_dashboard()
    {
        $questionnaire = Questionnaire::first();

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->get('/questionnaire/admin/dashboard/' . $questionnaire->id);
        $response->assertRedirect('/access/denied/');

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/questionnaire/admin/dashboard/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('questionnaire.admin-dashboard')->assertViewHasAll(['published_questionnaires', 'questionnaire']);
    }

    public function test_unauthenticated_questionnaire_admin_dashboard_list()
    {
        $questionnaire = Questionnaire::first();
        $response = $this->get('/questionnaire/admin/dashboard/list/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_admin_dashboard_list()
    {
        $questionnaire = QuestionnaireCountry::first();

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $configuration_json = Indicator::where('id', $data['indicators_assigned']['id'][0])->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);

        $response = $this->actingAs($ppoc)->get('/questionnaire/admin/dashboard/list/' . $questionnaire->questionnaire->id);
        $response->assertRedirect('/access/denied/');

        // PPoC - submit
        $response = $this->actingAs($ppoc)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $data['indicators_assigned']['id'][0],
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/questionnaire/admin/dashboard/list/' . $questionnaire->questionnaire->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        $decodedJSON = $response->decodeResponseJson();
        foreach ($decodedJSON['data'] as $json)
        {
            if ($json['questionnaire_country_id'] == $questionnaire->id)
            {
                $this->assertTrue($json['percentage_processed'] == 100);

                break;
            }
        }
    }

    public function test_unauthenticated_questionnaire_dashboard_management()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->get('/questionnaire/dashboard/management/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_dashboard_management()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/questionnaire/dashboard/management/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('questionnaire.dashboard-management')->assertViewHasAll(['questionnaire', 'indicators_percentage']);

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->get('/questionnaire/dashboard/management/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('questionnaire.dashboard-management')->assertViewHasAll(['questionnaire', 'indicators_percentage']);
    }

    public function test_unauthenticated_questionnaire_dashboard_management_list()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->get('/questionnaire/dashboard/management/list/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_dashboard_management_list()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/questionnaire/dashboard/management/list/' . $questionnaire->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);
        
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->get('/questionnaire/dashboard/management/list/' . $questionnaire->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_questionnaire_indicator_get()
    {
        $indicator = Indicator::first();
        $response = $this->call('get', '/questionnaire/indicator/get/' . $indicator->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_indicator_get()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($admin)->call('get', '/questionnaire/indicator/get/' . $data['indicators_assigned']['id'][0], [
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk()->assertViewIs('ajax.questionnaire-indicator-info')->assertViewHasAll(['indicator_data']);

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($ppoc)->call('get', '/questionnaire/indicator/get/' . $data['indicators_assigned']['id'][0], [
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk()->assertViewIs('ajax.questionnaire-indicator-info')->assertViewHasAll(['indicator_data']);
    }

    public function test_unauthenticated_questionnaire_indicator_edit()
    {
        $indicator = Indicator::first();
        $response = $this->call('get', '/questionnaire/indicator/edit/single/' . $indicator->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_indicator_edit()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($admin)->call('get', '/questionnaire/indicator/edit/single/' . $data['indicators_assigned']['id'][0], [
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertRedirect('/access/denied/');

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($ppoc)->call('get', '/questionnaire/indicator/edit/single/' . $data['indicators_assigned']['id'][0], [
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk()->assertViewIs('ajax.questionnaire-indicator-management')->assertViewHasAll(['questionnaire', 'indicators', 'users']);

        $data = $response->getOriginalContent()->getData();
        $actual_users = TestHelper::getActualUsers($data['users']);
        $expected_users = TestHelper::getExpectedUsers([2, 3, 5]);
        $this->assertEquals(
            sort($expected_users),
            sort($actual_users),
            'Expected users -> ' . implode(', ', $expected_users) . "\n" .
            'Actual users -> ' . implode(', ', $actual_users)
        );
    }

    public function test_unauthenticated_questionnaire_indicator_update()
    {
        $indicator = Indicator::first();
        $response = $this->post('/questionnaire/indicator/update/single/' . $indicator->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_required_questionnaire_indicator_update_edit()
    {
        $questionnaire = QuestionnaireCountry::first();
        $inputs = [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => '',
            'deadline' => '',
            'requested_changes' => false
        ];

        $admin = EcasTestHelper::validateTestUser('admin');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/update/single/' . $data['indicators_assigned']['id'][0], $inputs);
        $response->assertStatus(403);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $data['indicators_assigned']['id'][0], $inputs);
        $response->assertStatus(400);
        $response->assertExactJson([
            'assignee' => [
                'The assignee field is required.'
            ],
            'deadline' => [
                'The deadline field is required.',
                'The deadline field must be between ' . date('d-m-Y') . ' and ' . GeneralHelper::dateFormat($questionnaire->questionnaire->deadline, 'd-m-Y') . '.'
            ]
        ]);
    }

    public function test_authenticated_questionnaire_indicator_update_edit()
    {
        $questionnaire = QuestionnaireCountry::first();

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $indicator = $data['indicators_assigned']['id'][0];
        $configuration_json = Indicator::where('id', $indicator)->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);
        // Operator 1
        $operator1 = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);
        // Operator 2
        $operator2 = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);
        $deadline = date('Y-m-d');
        $new_deadline = date('Y-m-d', strtotime(date('Y-m-d') . ' + 1 days'));

        // PPoC - assign indicator to operator 1
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $operator1->id,
            'deadline' => $deadline,
            'requested_changes' => false
        ]);
        $response->assertOk();

        // Operator - save
        $operator = EcasTestHelper::validateTestUser($operator1);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'save',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator,
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        // PPoC - change only deadline indicator
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $operator1->id,
            'deadline' => $new_deadline,
            'requested_changes' => false
        ]);
        $response->assertOk();

        // PPoC - assign indicator to operator 2
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $operator2->id,
            'deadline' => $deadline,
            'requested_changes' => false
        ]);
        $response->assertOk();
    }

    public function test_unauthenticated_questionnaire_indicator_edit_multiple()
    {
        $response = $this->call('get', '/questionnaire/indicator/edit/multiple', []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_indicator_edit_multiple()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($admin)->call('get', '/questionnaire/indicator/edit/multiple', [
            'indicators' => implode(', ', $data['indicators_assigned']['id']),
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertRedirect('/access/denied/');

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($ppoc)->call('get', '/questionnaire/indicator/edit/multiple', [
            'indicators' => implode(', ', $data['indicators_assigned']['id']),
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk()->assertViewIs('ajax.questionnaire-indicator-management')->assertViewHasAll(['questionnaire', 'indicators', 'users']);

        $data = $response->getOriginalContent()->getData();
        $actual_users = TestHelper::getActualUsers($data['users']);
        $expected_users = TestHelper::getExpectedUsers([2, 3, 5]);
        $this->assertEquals(
            sort($expected_users),
            sort($actual_users),
            'Expected users -> ' . implode(', ', $expected_users) . "\n" .
            'Actual users -> ' . implode(', ', $actual_users)
        );
    }

    public function test_unauthenticated_questionnaire_indicator_update_edit_multiple()
    {
        $response = $this->post('/questionnaire/indicator/update/multiple', []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_required_questionnaire_indicator_update_edit_multiple()
    {
        $questionnaire = QuestionnaireCountry::first();
        $inputs = [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => '',
            'deadline' => '',
            'requested_changes' => false
        ];

        $admin = EcasTestHelper::validateTestUser('admin');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $inputs['datatable-selected'] = implode(', ', $data['indicators_assigned']['id']);
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/update/multiple', $inputs);
        $response->assertStatus(403);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $inputs['datatable-selected'] = implode(', ', $data['indicators_assigned']['id']);
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/multiple', $inputs);
        $response->assertStatus(400);
        $response->assertExactJson([
            'assignee' => [
                'The assignee field is required.'
            ],
            'deadline' => [
                'The deadline field is required.',
                'The deadline field must be between ' . date('d-m-Y') . ' and ' . GeneralHelper::dateFormat($questionnaire->questionnaire->deadline, 'd-m-Y') . '.'
            ]
        ]);
    }

    public function test_authenticated_questionnaire_indicator_update_edit_multiple()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        // Operator
        $operator = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);
        $deadline = date('Y-m-d');

        // Admin - edit multiple
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/update/multiple', [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'datatable-selected' => implode(', ', $data['indicators_assigned']['id']),
            'assignee' => $operator->id,
            'deadline' => $deadline,
            'requested_changes' => false
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        // PPoC - edit multiple
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/multiple', [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'datatable-selected' => implode(', ', $data['indicators_assigned']['id']),
            'assignee' => $operator->id,
            'deadline' => $deadline,
            'requested_changes' => false
        ]);
        $response->assertOk();
    }

    public function test_authenticated_required_questionnaire_indicator_update_request_changes()
    {
        $questionnaire = QuestionnaireCountry::first();
        $inputs = [
            'questionnaire_country_id' => $questionnaire->id,
            'changes' => '',
            'deadline' => ''
        ];

        $admin = EcasTestHelper::validateTestUser('admin');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/request_changes/' . $data['indicators_assigned']['id'][0], $inputs);
        $response->assertStatus(400);
        $response->assertExactJson([
            'changes' => [
                'The changes field is required.'
            ],
            'deadline' => [
                'The deadline field is required.'
            ]
        ]);

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/request_changes/' . $data['indicators_assigned']['id'][0], $inputs);
        $response->assertStatus(400);
        $response->assertExactJson([
            'changes' => [
                'The changes field is required.'
            ],
            'deadline' => [
                'The deadline field is required.'
            ]
        ]);
    }

    public function test_authenticated_poc_questionnaire_indicator_update_request_changes()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $poc = EcasTestHelper::validateTestUser('poc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $indicator1 = $data['indicators_assigned']['id'][0];
        $indicator2 = $data['indicators_assigned']['id'][1];
        $configuration_json = Indicator::where('id', $indicator1)->value('configuration_json');
        $indicator1_answers = $this->getAnswers($configuration_json);
        $configuration_json = Indicator::where('id', $indicator2)->value('configuration_json');
        $indicator2_answers = $this->getAnswers($configuration_json);
        // Other PoC
        $other_poc = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $poc->permissions->first()->country->name
            ]
        ]);
        // Operator 1
        $operator1 = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc->permissions->first()->country->name
            ]
        ]);
        // Operator 2
        $operator2 = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $poc->permissions->first()->country->name
            ]
        ]);
        $changes = Str::random(40);
        $deadline = date('Y-m-d');

        $new_changes = Str::random(40);
        $new_deadline = date('Y-m-d', strtotime(date('Y-m-d') . ' + 1 days'));

        // Other PoC - assign indicator 1 to operator 2
        $response = $this->actingAs($other_poc)->post('/questionnaire/indicator/update/single/' . $indicator1, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $operator2->id,
            'deadline' => $deadline,
            'requested_changes' => false
        ]);
        $response->assertOk();

        // Default PoC - assign indicator 1 to operator 1
        $response = $this->actingAs($poc)->post('/questionnaire/indicator/update/single/' . $indicator1, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $operator1->id,
            'deadline' => $deadline,
            'requested_changes' => false
        ]);
        $response->assertOk();

        // Operator 1 - save
        $operator = EcasTestHelper::validateTestUser($operator1);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'save',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator1,
            'questionnaire_answers' => $indicator1_answers,
            'indicator_answers' => $indicator1_answers
        ]);
        $response->assertOk();

        // Default PoC - request changes
        $response = $this->actingAs($poc)->post('/questionnaire/indicator/request_changes/' . $indicator1, [
            'questionnaire_country_id' => $questionnaire->id,
            'changes' => $changes,
            'deadline' => $deadline
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::REQUESTED_CHANGES_NOT_ALLOWED
        ]);

        // Admin - request changes
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/request_changes/' . $indicator1, [
            'questionnaire_country_id' => $questionnaire->id,
            'changes' => Str::random(40),
            'deadline' => date('Y-m-d', strtotime(date('Y-m-d') . ' + 1 days'))
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::REQUESTED_CHANGES_NOT_ALLOWED
        ]);

        // Operator 1 - submit
        $operator = EcasTestHelper::validateTestUser($operator1);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator1,
            'questionnaire_answers' => $indicator1_answers,
            'indicator_answers' => $indicator1_answers
        ]);
        $response->assertOk();

        // Other PoC - request changes
        $response = $this->actingAs($other_poc)->post('/questionnaire/indicator/request_changes/' . $indicator1, [
            'questionnaire_country_id' => $questionnaire->id,
            'changes' => $changes,
            'deadline' => $deadline
        ]);
        $response->assertOk();

        // Default PoC - request changes
        $response = $this->actingAs($poc)->post('/questionnaire/indicator/request_changes/' . $indicator1, [
            'questionnaire_country_id' => $questionnaire->id,
            'changes' => $changes,
            'deadline' => $deadline
        ]);
        $response->assertOk();

        // Operator 1 - save
        $operator = EcasTestHelper::validateTestUser($operator1);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'save',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator1,
            'questionnaire_answers' => $indicator1_answers,
            'indicator_answers' => $indicator1_answers
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'warning' => 'Indicator cannot be updated as there are requested changes that have not been submitted yet.'
        ]);

        // Default PoC - request new changes/deadline
        $response = $this->actingAs($poc)->post('/questionnaire/indicator/request_changes/' . $indicator1, [
            'questionnaire_country_id' => $questionnaire->id,
            'changes' => $new_changes,
            'deadline' => $new_deadline
        ]);
        $response->assertOk();

        // Admin - final approve
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/update/single/' . $indicator1, [
            'action' => 'final_approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::UPDATE_NOT_ALLOWED
        ]);

        // Default PoC - assign request changes indicator 1 to operator 2
        $response = $this->actingAs($poc)->post('/questionnaire/indicator/update/single/' . $indicator1, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $operator2->id,
            'deadline' => $deadline,
            'requested_changes' => true
        ]);
        $response->assertOk();

        // Operator 2 - submit
        $operator = EcasTestHelper::validateTestUser($operator2);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator1,
            'questionnaire_answers' => $indicator1_answers,
            'indicator_answers' => $indicator1_answers
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'warning' => 'Survey cannot be submitted as there are requested changes that have not been submitted yet.'
        ]);

        // Default PoC - submits requested changes
        $response = $this->actingAs($poc)->post('/questionnaire/submit_requested_changes', [
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk();

        // Operator 2 - submit
        $operator = EcasTestHelper::validateTestUser($operator2);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator1,
            'questionnaire_answers' => $indicator1_answers,
            'indicator_answers' => $indicator1_answers
        ]);
        $response->assertOk();

        // Default PoC - approve
        $response = $this->actingAs($poc)->post('/questionnaire/indicator/update/single/' . $indicator1, [
            'action' => 'approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk();

        // Default PoC - assign indicator 2 to operator 1
        $response = $this->actingAs($poc)->post('/questionnaire/indicator/update/single/' . $indicator2, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $operator1->id,
            'deadline' => $deadline,
            'requested_changes' => false
        ]);
        $response->assertOk();

        // Operator 1 - submit
        $operator = EcasTestHelper::validateTestUser($operator1);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator2,
            'questionnaire_answers' => $indicator2_answers,
            'indicator_answers' => $indicator2_answers
        ]);
        $response->assertOk();

        // Default PoC - request changes
        $response = $this->actingAs($poc)->post('/questionnaire/indicator/request_changes/' . $indicator2, [
            'questionnaire_country_id' => $questionnaire->id,
            'changes' => $changes,
            'deadline' => $deadline
        ]);
        $response->assertOk();
        
        // Delete operator 1
        $response = $this->actingAs($admin)->post('/user/delete/single/' . $operator1->id);
        $response->assertOk();

        // Default PoC - request changes
        $response = $this->actingAs($poc)->post('/questionnaire/indicator/request_changes/' . $indicator2, [
            'questionnaire_country_id' => $questionnaire->id,
            'changes' => $changes,
            'deadline' => $deadline
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'warning' => 'Request changes are not allowed as indicator assignee is inactive. Please re-assign indicator to an active user.'
        ]);

        // Default PoC - assign indicator 2 to himself
        $response = $this->actingAs($poc)->post('/questionnaire/indicator/update/single/' . $indicator2, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $poc->id,
            'deadline' => $deadline,
            'requested_changes' => false
        ]);
        $response->assertOk();

        // Default PoC - submits requested changes
        $response = $this->actingAs($poc)->post('/questionnaire/submit_requested_changes', [
            'questionnaire_country_id' => $questionnaire->id,
            'test' => true
        ]);
        $response->assertOk();

        // Default PoC - submit
        $poc = EcasTestHelper::validateTestUser('poc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($poc)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator2,
            'questionnaire_answers' => $indicator2_answers,
            'indicator_answers' => $indicator2_answers
        ]);
        $response->assertOk();
    }

    public function test_authenticated_ppoc_questionnaire_indicator_update_approve()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $indicator = $data['indicators_assigned']['id'][0];
        $configuration_json = Indicator::where('id', $indicator)->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);
        // Operator 1
        $operator1 = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);
        // Operator 2
        $operator2 = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);
        $deadline = date('Y-m-d');

        // PPoC - assign indicator to operator 1
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $operator1->id,
            'deadline' =>  $deadline,
            'requested_changes' => false
        ]);
        $response->assertOk();

        // Operator - save
        $operator = EcasTestHelper::validateTestUser($operator1);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'save',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator,
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        // PPoC - approve
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::UPDATE_NOT_ALLOWED
        ]);

        // Admin - approve
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        // Operator - submit
        $operator = EcasTestHelper::validateTestUser($operator1);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator,
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        // Admin - approve
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        // PPoC - approve
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk();

        // PPoC - assign approved indicator to operator 2
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $operator2->id,
            'deadline' => date('Y-m-d', strtotime(date('Y-m-d') . ' + 1 days')),
            'requested_changes' => false
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::UPDATE_NOT_ALLOWED
        ]);
    }

    public function test_authenticated_admin_questionnaire_indicator_update_request_changes()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $indicator = $data['indicators_assigned']['id'][0];
        $configuration_json = Indicator::where('id', $indicator)->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);
        // Other Admin
        $other_admin = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);
        // Operator 1
        $operator1 = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);
        $changes = Str::random(40);
        $deadline = date('Y-m-d');

        $new_changes = Str::random(40);
        $new_deadline = date('Y-m-d', strtotime(date('Y-m-d') . ' + 1 days'));

        // PPoC - assign indicator to operator 1
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $operator1->id,
            'deadline' => $deadline,
            'requested_changes' => false
        ]);
        $response->assertOk();

        // Operator - submit
        $operator = EcasTestHelper::validateTestUser($operator1);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator,
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        // PPoC - approve
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk();

        // PPoC - submit
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($ppoc)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator,
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        // Admin - request changes
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/request_changes/' . $indicator, [
            'questionnaire_country_id' => $questionnaire->id,
            'changes' => $changes,
            'deadline' => $deadline
        ]);
        $response->assertOk();

         // Other Admin - submits requested changes
         $response = $this->actingAs($other_admin)->post('/questionnaire/submit_requested_changes', [
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk();

        // Admin - request new changes/deadline
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/request_changes/' . $indicator, [
            'questionnaire_country_id' => $questionnaire->id,
            'changes' => $new_changes,
            'deadline' => $new_deadline
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::REQUESTED_CHANGES_NOT_ALLOWED
        ]);

        // PPoC - assign request changes indicator to operator 1
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $operator1->id,
            'deadline' => $deadline,
            'requested_changes' => true
        ]);
        $response->assertOk();

        // Operator - submit
        $operator = EcasTestHelper::validateTestUser($operator1);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator,
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        // PPoC - approve
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk();

        // PPoC - submit
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($ppoc)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator,
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        // Delete PPoC
        $response = $this->actingAs($admin)->post('/user/delete/single/' . $ppoc->id);
        $response->assertOk();

        // Admin - request changes
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/request_changes/' . $indicator, [
            'questionnaire_country_id' => $questionnaire->id,
            'changes' => $changes,
            'deadline' => $deadline
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'warning' => 'Request changes are not allowed as Primary PoC is inactive.'
        ]);

        // Admin - final approve
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'final_approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk();
    }

    public function test_authenticated_admin_questionnaire_indicator_update_final_approve()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $indicator = $data['indicators_assigned']['id'][0];
        $configuration_json = Indicator::where('id', $indicator)->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);
        // Operator 1
        $operator1 = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);
        $deadline = date('Y-m-d');

        // PPoC - assign indicator to operator 1
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $operator1->id,
            'deadline' =>  $deadline,
            'requested_changes' => false
        ]);
        $response->assertOk();

        // Operator - save
        $operator = EcasTestHelper::validateTestUser($operator1);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'save',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator,
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        // PPoC - final approve
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'final_approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        // Admin - final approve
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'final_approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::UPDATE_NOT_ALLOWED
        ]);

        // Operator - submit
        $operator = EcasTestHelper::validateTestUser($operator1);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator,
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        // PPoC - final approve
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'final_approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        // Admin - final approve
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'final_approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::UPDATE_NOT_ALLOWED
        ]);

        // PPoC - approve
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk();

        // PPoC - submit
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $response = $this->actingAs($ppoc)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator,
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        // Admin - final approve
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/update/single/' . $indicator, [
            'action' => 'final_approve',
            'questionnaire_country_id' => $questionnaire->id
        ]);
        $response->assertOk();

        $response = $this->actingAs($admin)->get('/index/datacollection/list/' . $questionnaire->questionnaire->configuration->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_authenticated_admin_questionnaire_indicator_update_final_approve_multiple()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $indicator = $data['indicators_assigned']['id'][0];
        $configuration_json = Indicator::where('id', $indicator)->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);

        // Admin - final approve multiple
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/update/multiple', [
            'action' => 'final_approve',
            'questionnaire_country_id' => $questionnaire->id,
            'datatable-selected' => implode(', ', $data['indicators_assigned']['id'])
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::UPDATE_NOT_ALLOWED
        ]);

        // PPoC - submit
        $response = $this->actingAs($ppoc)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $indicator,
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        // PPoC - final approve multiple
        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/multiple', [
            'action' => 'final_approve',
            'questionnaire_country_id' => $questionnaire->id,
            'datatable-selected' => implode(', ', $data['indicators_assigned']['id'])
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        // Admin - final approve multiple
        $response = $this->actingAs($admin)->post('/questionnaire/indicator/update/multiple', [
            'action' => 'final_approve',
            'questionnaire_country_id' => $questionnaire->id,
            'datatable-selected' => implode(', ', $data['indicators_assigned']['id'])
        ]);
        $response->assertOk();
    }

    public function test_unauthenticated_questionnaire_view()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->post('/questionnaire/view/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_view()
    {
        $questionnaire = QuestionnaireCountry::first();

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->post('/questionnaire/view/' . $questionnaire->id, [
            'action' => 'view'
        ]);
        $response->assertOk()->assertViewIs('questionnaire.view')->assertViewHasAll([
            'action',
            'questionnaire',
            'last_year_questionnaire_country',
            'questionnaire_started',
            'json_data',
            'requested_indicator',
            'requested_action',
            'indicators_assigned',
            'indicators_assigned_exact',
            'indicators_submitted'
        ]);
        $data = $response->getOriginalContent()->getData();
        $this->assertNotEmpty($data['indicators_assigned']);

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/questionnaire/view/' . $questionnaire->id, [
            'action' => 'view'
        ]);
        $response->assertOk()->assertViewIs('questionnaire.view')->assertViewHasAll([
            'action',
            'questionnaire',
            'last_year_questionnaire_country',
            'questionnaire_started',
            'json_data',
            'requested_indicator',
            'requested_action',
            'indicators_assigned',
            'indicators_assigned_exact',
            'indicators_submitted'
        ]);
        $data = $response->getOriginalContent()->getData();
        $this->assertNotEmpty($data['indicators_assigned']);
    }

    public function test_authenticated_questionnaire_view_submitted()
    {
        $questionnaire = QuestionnaireCountry::first();
        $user = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $configuration_json = Indicator::where('id', $data['indicators_assigned']['id'][0])->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);

        $response = $this->actingAs($user)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $data['indicators_assigned']['id'][0],
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        $response = $this->actingAs($user)->post('/questionnaire/view/' . $questionnaire->id, [
            'action' => 'view'
        ]);
        $response->assertOk()->assertViewIs('questionnaire.view')->assertViewHasAll([
            'action',
            'questionnaire',
            'last_year_questionnaire_country',
            'questionnaire_started',
            'json_data',
            'requested_indicator',
            'requested_action',
            'indicators_assigned',
            'indicators_assigned_exact',
            'indicators_submitted'
        ]);
        $data = $response->getOriginalContent()->getData();
        $this->assertNotEmpty($data['indicators_assigned']);
        $this->assertTrue($data['indicators_submitted'] == 1);
    }

    public function test_authenticated_questionnaire_export_submitted()
    {
        $questionnaire = QuestionnaireCountry::first();
        $user = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $configuration_json = Indicator::where('id', $data['indicators_assigned']['id'][0])->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);

        $response = $this->actingAs($user)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $data['indicators_assigned']['id'][0],
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        $response = $this->actingAs($user)->post('/questionnaire/view/' . $questionnaire->id, [
            'action' => 'export'
        ]);
        $response->assertOk()->assertViewIs('questionnaire.view')->assertViewHasAll([
            'action',
            'questionnaire',
            'last_year_questionnaire_country',
            'questionnaire_started',
            'json_data',
            'requested_indicator',
            'requested_action',
            'indicators_assigned',
            'indicators_assigned_exact',
            'indicators_submitted'
        ]);
        $data = $response->getOriginalContent()->getData();
        $this->assertNotEmpty($data['indicators_assigned']);
        $this->assertTrue($data['indicators_submitted'] == 1);
    }

    public function test_unauthenticated_questionnaire_indicator_validate()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->post('/questionnaire/indicator/validate/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_validate()
    {
        $questionnaire = QuestionnaireCountry::first();
        $user = EcasTestHelper::validateTestUser('ppoc');
        $indicator = Indicator::whereNotNull('configuration_json')->where('year', $questionnaire->questionnaire->year)->first();
        $answers = $this->getAnswers($indicator->configuration_json);
        $response = $this->actingAs($user)->post('/questionnaire/indicator/validate/' . $questionnaire->id, [
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();
    }

    public function test_authenticated_invalid_questionnaire_indicator_validate()
    {
        $questionnaire = QuestionnaireCountry::first();
        $user = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $active_indicator = $data['indicators_assigned']['id'][0];
        $configuration_json = Indicator::where('id', $active_indicator)->value('configuration_json');

        $configuration_json_tmp = $configuration_json;
        $configuration_json_tmp['form'][0]['contents'][0]['choice'] = null;
        $answers = $this->getAnswers($configuration_json_tmp);
        $response = $this->actingAs($user)->post('/questionnaire/indicator/validate/' . $questionnaire->id, [
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'errors' => [
                'choice' => [
                    'The choice field is required.'
                ]
            ]
        ]);

        $configuration_json_tmp = $configuration_json;
        $configuration_json_tmp['form'][0]['contents'][0]['answers'] = [];
        $answers = $this->getAnswers($configuration_json_tmp);
        $response = $this->actingAs($user)->post('/questionnaire/indicator/validate/' . $questionnaire->id, [
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'errors' => [
                'answers' => [
                    'The answers field is required.'
                ]
            ]
        ]);

        $configuration_json_tmp = $configuration_json;
        $configuration_json_tmp['form'][0]['contents'][0]['reference'] = '';
        $answers = $this->getAnswers($configuration_json_tmp);
        $response = $this->actingAs($user)->post('/questionnaire/indicator/validate/' . $questionnaire->id, [
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'errors' => [
                'reference' => [
                    'The reference field is required.'
                ]
            ]
        ]);

        $configuration_json_tmp = $configuration_json;
        $configuration_json_tmp['form'][0]['contents'][0]['rating'] = 0;
        $answers = $this->getAnswers($configuration_json_tmp);
        $response = $this->actingAs($user)->post('/questionnaire/indicator/validate/' . $questionnaire->id, [
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'errors' => [
                'rating' => [
                    'The rating field is required.'
                ]
            ]
        ]);
    }

    public function test_unauthenticated_questionnaire_save()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->post('/questionnaire/save/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_save_not_assigned()
    {
        $questionnaire = QuestionnaireCountry::first();
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);

        $indicators = [];
        foreach ($questionnaire->questionnaire->indicators as $indicator) {
            array_push($indicators, $indicator->indicator->id);
        }

        $operator = EcasTestHelper::validateTestUser($new_user);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $configuration_json = Indicator::where('id', $indicators[0])->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'save',
            'indicators_list' => $indicators,
            'active_indicator' => $indicators[0],
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'warning' => 'You haven\'t been assigned any indicators!',
            'indicators_assigned' => count($data['indicators_assigned'])
        ]);
    }

    public function test_authenticated_questionnaire_save_other_assigned()
    {
        $questionnaire = QuestionnaireCountry::first();

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);

        $indicators = [];
        foreach ($questionnaire->questionnaire->indicators as $indicator) {
            array_push($indicators, $indicator->indicator->id);
        }

        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $data['indicators_assigned']['id'][0], [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $new_user->id,
            'deadline' => date('Y-m-d'),
            'requested_changes' => false
        ]);
        $response->assertOk();

        $operator = EcasTestHelper::validateTestUser($new_user);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $configuration_json = Indicator::where('id', $indicators[1])->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'save',
            'indicators_list' => $indicators,
            'active_indicator' => $indicators[1],
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'warning' => 'You are no longer assigned this indicator. Please start the survey again!',
            'indicators_assigned' => count($data['indicators_assigned'])
        ]);
    }

    public function test_authenticated_questionnaire_save()
    {
        $questionnaire = QuestionnaireCountry::first();
        $user = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $configuration_json = Indicator::where('id', $data['indicators_assigned']['id'][0])->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);
        $response = $this->actingAs($user)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'save',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $data['indicators_assigned']['id'][0],
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();
    }

    public function test_authenticated_invalid_questionnaire_submit()
    {
        $questionnaire = QuestionnaireCountry::first();
        $user = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $configuration_json = Indicator::where('id', $data['indicators_assigned']['id'][0])->value('configuration_json');
        $configuration_json['form'][0]['contents'][0]['answers'] = [];
        $answers = $this->getAnswers($configuration_json);
        $response = $this->actingAs($user)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $data['indicators_assigned']['id'][0],
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => 'Survey answers are invalid. Please check the answers again!'
        ]);
    }

    public function test_authenticated_questionnaire_submit_other_assigned()
    {
        $questionnaire = QuestionnaireCountry::first();
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);

        $indicators = [];
        foreach ($questionnaire->questionnaire->indicators as $indicator) {
            array_push($indicators, $indicator->indicator->id);
        }

        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicator->indicator->id, [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'assignee' => $new_user->id,
            'deadline' => date('Y-m-d'),
            'requested_changes' => false
        ]);
        $response->assertOk();

        $operator = EcasTestHelper::validateTestUser($new_user);
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $configuration_json = Indicator::where('id', $indicator->indicator->id)->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);
        $response = $this->actingAs($operator)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $indicators,
            'active_indicator' => $indicator->indicator->id,
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'warning' => 'You are no longer assigned these indicators. Please start the survey again!',
            'indicators_assigned' => count($data['indicators_assigned'])
        ]);
    }

    public function test_authenticated_questionnaire_submit()
    {
        $questionnaire = QuestionnaireCountry::first();
        $user = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $configuration_json = Indicator::where('id', $data['indicators_assigned']['id'][0])->value('configuration_json');
        $answers = $this->getAnswers($configuration_json);

        $response = $this->actingAs($user)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'submit',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $data['indicators_assigned']['id'][0],
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertOk();

        $response = $this->actingAs($user)->post('/questionnaire/save/' . $questionnaire->id, [
            'action' => 'save',
            'indicators_list' => $data['indicators_assigned']['id'],
            'active_indicator' => $data['indicators_assigned']['id'][0],
            'questionnaire_answers' => $answers,
            'indicator_answers' => $answers
        ]);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::UPDATE_NOT_ALLOWED
        ]);
    }

    /**
     * @group questionnaire_excel
     */
    public function test_authenticated_questionnaire_conflict()
    {
        $questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $questionnaire->id)->first();

        QuestionnaireHelper::createQuestionnaireTemplate($questionnaire);

        $json_data = $questionnaire_country->json_data;
        foreach ($json_data['contents'] as &$indicator_data)
        {
            if (preg_match('/form-indicator-/', $indicator_data['type']))
            {
                $indicator_data['state'] = 3;

                break;
            }
        }
        $questionnaire_country->json_data = $json_data;
        $questionnaire_country->save();

        $filename = 'QuestionnaireTemplate.xlsx';
        $file = new UploadedFile(storage_path() . '/app/offline-survey/' . $questionnaire->year . '/' . $filename, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $user = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($user)->post('/questionnaire/upload/' . $questionnaire_country->id, [
            'file' => $file
        ]);
        $response->assertStatus(409)->assertJsonStructure([
            'list',
            'message'
        ]);
    }

    public function test_unauthenticated_questionnaire_offline_validate()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->get('/questionnaire/offline/validate/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_offline_validate_not_assigned()
    {
        $questionnaire = QuestionnaireCountry::first();

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire->id);
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);

        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/multiple', [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire->id,
            'datatable-selected' => implode(', ', $data['indicators_assigned']['id']),
            'assignee' => $new_user->id,
            'deadline' => date('Y-m-d'),
            'requested_changes' => false
        ]);
        $response->assertOk();

        $response = $this->actingAs($ppoc)->get('/questionnaire/offline/validate/' . $questionnaire->id);
        $response->assertStatus(403);
        $response->assertExactJson([
            'warning' => 'You haven\'t been assigned any indicators!'
        ]);
    }

    public function test_authenticated_questionnaire_offline_validate()
    {
        $questionnaire = QuestionnaireCountry::first();
        $user = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($user)->get('/questionnaire/offline/validate/' . $questionnaire->id);
        $response->assertOk();
    }

    /**
     * @group questionnaire_excel
     */
    public function test_unauthenticated_questionnaire_upload()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->post('/questionnaire/upload/' . $questionnaire->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    /**
     * @group questionnaire_excel
     */
    public function test_invalid_questionnaire_upload()
    {
        $questionnaire = QuestionnaireCountry::first();
        $user = EcasTestHelper::validateTestUser('ppoc');

        $file = UploadedFile::fake()->create(
            'document.pdf',
            2512,
            'application/pdf'
        );
        $response = $this->actingAs($user)->post('/questionnaire/upload/' . $questionnaire->id, [
            'file' => $file
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'extension' => [
                'The file must be of type .xlsx'
            ],
            'file' => [
                'The file must be a file of type: xlsx.',
                'The file must not be greater than 2048 kilobytes.'
            ]
        ]);

        $file = UploadedFile::fake()->create(
            'document.xlsx',
            512,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $response = $this->actingAs($user)->post('/questionnaire/upload/' . $questionnaire->id, [
            'file' => $file
        ]);
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => 'File could not be parsed correctly!'
        ]);
    }

    /**
     * @group questionnaire_excel
     */
    public function test_authenticated_questionnaire_upload_not_assigned()
    {
        $questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $questionnaire->id)->first();
        
        QuestionnaireHelper::createQuestionnaireTemplate($questionnaire);

        $filename = 'QuestionnaireTemplate.xlsx';
        $file = new UploadedFile(storage_path() . '/app/offline-survey/' . $questionnaire->year . '/' . $filename, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);

        $indicators = $questionnaire_country->questionnaire->indicators->toArray();

        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicators[0]['indicator_id'], [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire_country->id,
            'assignee' => $new_user->id,
            'deadline' => date('Y-m-d'),
            'requested_changes' => false
        ]);
        $response->assertOk();

        $operator = EcasTestHelper::validateTestUser($new_user);
        $response = $this->actingAs($operator)->post('/questionnaire/upload/' . $questionnaire_country->id, [
            'file' => $file
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'warning' => 'You are no longer assigned these indicators. Please download the template again!'
        ]);
    }

    /**
     * @group questionnaire_excel
     */
    public function test_authenticated_questionnaire_upload_default_not_assigned()
    {
        $questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $questionnaire->id)->first();
        
        QuestionnaireHelper::createQuestionnaireTemplate($questionnaire);

        $filename = 'QuestionnaireTemplate.xlsx';
        $file = new UploadedFile(storage_path() . '/app/offline-survey/' . $questionnaire->year . '/' . $filename, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $new_user = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'operator',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);

        $indicators = $questionnaire_country->questionnaire->indicators->toArray();

        $response = $this->actingAs($ppoc)->post('/questionnaire/indicator/update/single/' . $indicators[0]['indicator_id'], [
            'action' => 'edit',
            'questionnaire_country_id' => $questionnaire_country->id,
            'assignee' => $new_user->id,
            'deadline' => date('Y-m-d'),
            'requested_changes' => false
        ]);
        $response->assertOk();

        $response = $this->actingAs($ppoc)->post('/questionnaire/upload/' . $questionnaire_country->id, [
            'file' => $file
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'warning' => 'You are no longer assigned these indicators. Please download the template again!'
        ]);
    }

    /**
     * @group questionnaire_excel
     */
    public function test_authenticated_questionnaire_upload()
    {
        $questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $questionnaire->id)->first();
        
        QuestionnaireHelper::createQuestionnaireTemplate($questionnaire);

        $filename = 'QuestionnaireTemplate.xlsx';
        $file = new UploadedFile(storage_path() . '/app/offline-survey/' . $questionnaire->year . '/' . $filename, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->post('/questionnaire/upload/' . $questionnaire_country->id, [
            'file' => $file
        ]);
        $response->assertRedirect('/access/denied/');

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->post('/questionnaire/upload/' . $questionnaire_country->id, [
            'file' => $file
        ]);
        $response->assertOk();
    }

    public function test_unauthenticated_questionnaire_indicator_values()
    {
        $questionnaire = Questionnaire::first();
        $response = $this->get('/questionnaire/admin/dashboard/indicatorvalues/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_indicator_values()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $questionnaire = Questionnaire::first();
        $response = $this->actingAs($user)->get('/questionnaire/admin/dashboard/indicatorvalues/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('questionnaire.admin-dashboard-indicator-values')->assertViewHasAll([
            'published_questionnaires',
            'questionnaire',
            'task',
            'indicators',
            'countries',
            'table_data'
        ]);
    }

    public function test_unauthenticated_questionnaire_indicator_values_list()
    {
        $questionnaire = Questionnaire::first();
        $response = $this->call('get', '/questionnaire/admin/dashboard/indicatorvalues/list/' . $questionnaire->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_indicator_values_list()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $questionnaire = Questionnaire::first();
        $response = $this->actingAs($user)->call('get', '/questionnaire/admin/dashboard/indicatorvalues/list/' . $questionnaire->id, []);
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_questionnaire_indicator_values_calculate()
    {
        $questionnaire = Questionnaire::first();
        $response = $this->post('/questionnaire/admin/dashboard/indicatorvalues/calculate/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_indicator_values_calculate_fake()
    {
        Queue::fake();

        $user = EcasTestHelper::validateTestUser('admin');
        $questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $index = $questionnaire->configuration;
        $response = $this->actingAs($user)->post('/questionnaire/admin/dashboard/indicatorvalues/calculate/' . $questionnaire->id);
        $response->assertOk();

        Queue::assertPushed(function (\App\Jobs\IndicatorValuesCalculation $job) use ($index, $user, $questionnaire) {
            $this->assertTrue($questionnaire->id == $job->questionnaire->id);
            $this->assertTrue($index->id == $job->index->id);
            $this->assertTrue($user->id == $job->user->id);
            $this->assertQuestionnaireIndicatorValues($questionnaire, 'No Calculation');

            return $job;
        });
    }

    public function test_authenticated_questionnaire_indicator_values_calculate()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $latest_published_questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $published_questionnaire = $latest_published_questionnaire->replicate();
        $published_questionnaire->year = $latest_published_questionnaire->year - 1;
        $published_questionnaire->save();

        $response = $this->actingAs($user)->post('/questionnaire/admin/dashboard/indicatorvalues/calculate/' . $published_questionnaire->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::UPDATE_NOT_ALLOWED
        ]);

        $task = TaskHelper::updateOrCreateTask([
            'type' => self::INDICATORVALUESCALCULATIONTASK,
            'status_id' => 1,
            'index_configuration_id' => $latest_published_questionnaire->configuration->id,
            'payload' => [
                'last_indicator_values_calculation_by' => $user->id,
                'last_indicator_values_calculation_at' => $this->today
            ]
        ]);

        $this->assertQuestionnaireIndicatorValues($latest_published_questionnaire, 'In Progress');

        $response = $this->actingAs($user)->post('/questionnaire/admin/dashboard/indicatorvalues/calculate/' . $latest_published_questionnaire->id);
        $response->assertStatus(405);
        $response->assertExactJson([
            'error' => self::UPDATE_NOT_ALLOWED
        ]);

        TaskHelper::deleteTask($task);

        $this->assertQuestionnaireIndicatorValues($latest_published_questionnaire, 'No Calculation');

        $response = $this->actingAs($user)->post('/questionnaire/admin/dashboard/indicatorvalues/calculate/' . $latest_published_questionnaire->id);
        $response->assertOk();
    }

    public function test_unauthenticated_questionnaire_summary_data()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->get('/questionnaire/dashboard/summarydata/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_summary_data()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/questionnaire/dashboard/summarydata/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('questionnaire.dashboard-summary-data')->assertViewHasAll(['questionnaire']);

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->get('/questionnaire/dashboard/summarydata/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('questionnaire.dashboard-summary-data')->assertViewHasAll(['questionnaire']);
    }

    public function test_unauthenticated_questionnaire_summary_data_requested_changes_list()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->get('/questionnaire/dashboard/summarydata/requested_changes/list/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_summary_data_requested_changes_list()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/questionnaire/dashboard/summarydata/requested_changes/list/' . $questionnaire->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->get('/questionnaire/dashboard/summarydata/requested_changes/list/' . $questionnaire->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_questionnaire_summary_data_data_not_available_list()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->get('/questionnaire/dashboard/summarydata/data_not_available/list/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_summary_data_data_not_available_list()
    {
        $questionnaire = QuestionnaireCountry::first();

        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/questionnaire/dashboard/summarydata/data_not_available/list/' . $questionnaire->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->get('/questionnaire/dashboard/summarydata/data_not_available/list/' . $questionnaire->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_questionnaire_summary_data_comments_list()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->get('/questionnaire/dashboard/summarydata/comments/list/' . $questionnaire->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_summary_data_comments_list()
    {
        $questionnaire = QuestionnaireCountry::first();

        $admin = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($admin)->get('/questionnaire/dashboard/summarydata/comments/list/' . $questionnaire->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);

        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $response = $this->actingAs($ppoc)->get('/questionnaire/dashboard/summarydata/comments/list/' . $questionnaire->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_authenticated_questionnaire_preview()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $questionnaire = QuestionnaireCountry::first();
        $_COOKIE['index-year'] = $questionnaire->questionnaire->configuration->year;
        $response = $this->actingAs($user)->post('/questionnaire/view/' . $questionnaire->id, [
            'action' => 'preview'
        ]);
        $response->assertOk()->assertViewIs('questionnaire.view')->assertViewHasAll([
            'action',
            'questionnaire',
            'last_year_questionnaire_country',
            'questionnaire_started',
            'json_data',
            'requested_indicator',
            'requested_action',
            'indicators_assigned',
            'indicators_assigned_exact',
            'indicators_submitted'
        ]);
        $data = $response->getOriginalContent()->getData();
        $this->assertNotEmpty($data['indicators_assigned']);
        $this->assertNotEmpty($data['json_data']);
    }

    public function test_unauthenticated_questionnaire_load_data()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->post('/questionnaire/data/load/' . $questionnaire->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_unauthenticated_questionnaire_reset_data()
    {
        $questionnaire = QuestionnaireCountry::first();
        $response = $this->post('/questionnaire/data/reset/' . $questionnaire->id, []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_questionnaire_load_reset_data()
    {
        $admin = EcasTestHelper::validateTestUser('admin');
        $ppoc = EcasTestHelper::validateTestUser('ppoc');
        $questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $questionnaire->id)->first();
        $data = QuestionnaireCountryHelper::getAssigneeQuestionnairesCountryData($questionnaire_country->id);
        $indicator = Indicator::find($data['indicators_assigned']['id'][0]);
        $indicator_data = QuestionnaireCountryHelper::getQuestionnaireCountryIndicatorData($questionnaire_country, $indicator);
        // Other PoC
        $other_poc = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'PoC',
                'country' => $ppoc->permissions->first()->country->name
            ]
        ]);
        [$last_year_questionnaire_country, $last_year_indicator, $last_year_indicator_data] = QuestionnaireCountryHelper::getLastYearQuestionnaireCountryData($questionnaire_country, $indicator);
        $last_year_indicator_data['last_saved'] = date('d-m-Y H:i:s');

        QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorData($last_year_questionnaire_country, $last_year_indicator, $last_year_indicator_data);
        
        $response = $this->actingAs($admin)->post('/questionnaire/data/load/' . $questionnaire_country->id, [
            'active_indicator' => $indicator->id
        ]);
        $response->assertRedirect('/access/denied/');

        $response = $this->actingAs($other_poc)->post('/questionnaire/data/load/' . $questionnaire_country->id, [
            'active_indicator' => $indicator->id
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $response = $this->actingAs($ppoc)->post('/questionnaire/data/load/' . $questionnaire_country->id, [
            'active_indicator' => $indicator->id
        ]);
        $response->assertOk();

        $indicator_data['form'][0]['contents'][0]['compatible'] = false;

        QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorData($questionnaire_country, $indicator, $indicator_data);

        $response = $this->actingAs($ppoc)->post('/questionnaire/data/load/' . $questionnaire_country->id, [
            'active_indicator' => $indicator->id
        ]);
        $response->assertOk();
        
        $decodedJSON = $response->decodeResponseJson();
        $this->assertTrue(!$decodedJSON['form'][0]['contents'][0]['answers_loaded']);

        $response = $this->actingAs($admin)->post('/questionnaire/data/reset/' . $questionnaire_country->id, [
            'active_indicator' => $indicator->id
        ]);
        $response->assertRedirect('/access/denied/');

        $response = $this->actingAs($other_poc)->post('/questionnaire/data/reset/' . $questionnaire_country->id, [
            'active_indicator' => $indicator->id
        ]);
        $response->assertStatus(403);
        $response->assertExactJson([
            'error' => self::ERROR_NOT_AUTHORIZED
        ]);

        $response = $this->actingAs($ppoc)->post('/questionnaire/data/reset/' . $questionnaire_country->id, [
            'active_indicator' => $indicator->id
        ]);
        $response->assertOk();

        $last_year_indicator_data['comments'] = Str::random(10);
        $last_year_indicator_data['rating'] = 3;

        QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorData($last_year_questionnaire_country, $last_year_indicator, $last_year_indicator_data);

        $response = $this->actingAs($ppoc)->post('/questionnaire/data/load/' . $questionnaire_country->id, [
            'active_indicator' => $indicator->id
        ]);
        $response->assertOk();

        $response = $this->actingAs($ppoc)->post('/questionnaire/data/reset/' . $questionnaire_country->id, [
            'active_indicator' => $indicator->id
        ]);
        $response->assertOk();
    }
}
