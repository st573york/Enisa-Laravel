<?php

namespace Tests\Browser;

use App\HelperFunctions\TestHelper;
use App\HelperFunctions\GeneralHelper;
use App\HelperFunctions\QuestionnaireHelper;
use App\Models\Country;
use App\Models\IndexConfiguration;
use App\Models\Questionnaire;
use App\Models\User;
use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\Survey\AdminManagement;
use Tests\Browser\Components\Alert;
use Tests\Browser\Components\Button;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SurveysTest extends DuskTestCase
{
    use DatabaseTransactions;

    public function test_admin_create_survey()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAsRole('admin');

            $surveys = QuestionnaireHelper::getQuestionnaires();
            $published_indexes = IndexConfiguration::getPublishedConfigurations();

            $expected_indexes = [];
            foreach ($published_indexes as $index) {
                array_push($expected_indexes, $index->id);
            }

            $admin_management = new AdminManagement;

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $admin_management->assert($browser);
            $surveys_data = $admin_management->getDataTableData($surveys);
            $admin_management->assertSurveysDataTable($browser, $surveys_data);
            $admin_management->clickCreateSurvey($browser);
            $admin_management->assertCreateOrEditModal($browser, [
                'title' => 'New Survey',
                'index' => [
                    'expected_indexes' => $expected_indexes,
                    'value' => ''
                ],
                'name' => [
                    'value' => ''
                ],
                'deadline' => [
                    'value' => ''
                ],
                'scope' => [
                    'value' => ''
                ],
                'actions' => true
            ]);
            $admin_management->createOrEditSurvey($browser, [
                'action' => 'save'
            ]);
            $admin_management->assertCreateOrEditModal($browser, [
                'index' => [
                    'error' => 'The index field is required.'
                ],
                'name' => [
                    'error' => 'The title field is required.'
                ],
                'deadline' => [
                    'error' => 'The deadline field is required.'
                ]
            ]);
            $admin_management->createOrEditSurvey($browser, [
                'index' => $published_indexes->first()->id,
                'name' => $surveys->first()->title,
                'deadline' => strtotime(date('Y-m-d') . ' + 1 days'),
                'action' => 'save'
            ]);
            $admin_management->assertCreateOrEditModal($browser, [
                'name' => [
                    'error' => 'The title has already been taken.'
                ]
            ]);
            $admin_management->createOrEditSurvey($browser, [
                'name' => Str::random(5),
                'scope' => Str::random(10),
                'action' => 'save'
            ]);
            $surveys = QuestionnaireHelper::getQuestionnaires();
            $surveys_data = $admin_management->getDataTableData($surveys);
            $admin_management->assertSurveysDataTable($browser, $surveys_data);
        });
    }

    public function test_admin_edit_survey()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAsRole('admin');

            $surveys = QuestionnaireHelper::getQuestionnaires();
            $published_indexes = IndexConfiguration::getPublishedConfigurations();
            $survey = Questionnaire::find($surveys->first()->id);
            $survey->description = Str::random(20);
            $survey->save();

            $expected_indexes = [];
            foreach ($published_indexes as $index) {
                array_push($expected_indexes, $index->id);
            }

            $admin_management = new AdminManagement;

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $admin_management->assert($browser);
            $surveys_data = $admin_management->getDataTableData($surveys);
            $admin_management->assertSurveysDataTable($browser, $surveys_data);
            $admin_management->clickEditSurvey($browser, $survey->id);
            $admin_management->assertCreateOrEditModal($browser, [
                'title' => 'Edit Survey',
                'index' => [
                    'expected_indexes' => $expected_indexes,
                    'value' => $survey->configuration->id
                ],
                'name' => [
                    'value' => $survey->title
                ],
                'deadline' => [
                    'value' => GeneralHelper::dateFormat($survey->deadline, 'd-m-Y')
                ],
                'scope' => [
                    'value' => $survey->description
                ],
                'actions' => true
            ]);
            $admin_management->createOrEditSurvey($browser, [
                'name' => '',
                'action' => 'save'
            ]);
            $admin_management->assertCreateOrEditModal($browser, [
                'name' => [
                    'error' => 'The title field is required.'
                ]
            ]);
            $admin_management->createOrEditSurvey($browser, [
                'name' => $surveys->skip(1)->first()->title,
                'deadline' => $survey->deadline,
                'scope' => $survey->description,
                'action' => 'save'
            ]);
            $admin_management->assertCreateOrEditModal($browser, [
                'name' => [
                    'error' => 'The title has already been taken.'
                ]
            ]);
            $admin_management->createOrEditSurvey($browser, [
                'name' => Str::random(5),
                'action' => 'save'
            ]);
            $surveys = QuestionnaireHelper::getQuestionnaires();
            $surveys_data = $admin_management->getDataTableData($surveys);
            $admin_management->assertSurveysDataTable($browser, $surveys_data);
        });
    }

    public function test_admin_delete_survey()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAsRole('admin');

            $published_indexes = IndexConfiguration::getPublishedConfigurations();

            $survey = QuestionnaireHelper::storeQuestionnaire([
                'title' => Str::random(5),
                'description' => Str::random(10),
                'deadline' => date('Y-m-d'),
                'index_configuration_id' => $published_indexes->first()->id
            ]);

            $surveys = QuestionnaireHelper::getQuestionnaires();

            $admin_management = new AdminManagement;

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $admin_management->assert($browser);
            $surveys_data = $admin_management->getDataTableData($surveys);
            $admin_management->assertSurveysDataTable($browser, $surveys_data);
            $admin_management->clickDeleteSurvey($browser, $survey->id);
            $admin_management->assertDeleteModal($browser, [
                'title' => 'Delete Survey',
                'text' => "Survey '" . $survey->title . "' will be deleted. Are you sure?",
                'actions' => true
            ]);
            $admin_management->deleteSurvey($browser, [
                'action' => 'delete'
            ]);
            $surveys_data[$survey->id]['deleted'] = true;
            $admin_management->assertSurveysDataTable($browser, $surveys_data);
        });
    }

    public function test_admin_publish_survey_no_ppoc()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAsRole('admin');
            $ppoc = $browser->loginAsRole('ppoc', false, false)->user;

            $published_indexes = IndexConfiguration::getPublishedConfigurations();

            $survey = QuestionnaireHelper::storeQuestionnaire([
                'title' => Str::random(5),
                'description' => Str::random(10),
                'deadline' => date('Y-m-d'),
                'index_configuration_id' => $published_indexes->first()->id
            ]);

            $surveys = QuestionnaireHelper::getQuestionnaires();

            User::deleteUser($ppoc);

            $admin_management = new AdminManagement;

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $admin_management->assert($browser);
            $surveys_data = $admin_management->getDataTableData($surveys);
            $admin_management->assertSurveysDataTable($browser, $surveys_data);
            $admin_management->clickPublishSurvey($browser, $survey->id);
            $admin_management->assertPublishModal($browser, [
                'title' => 'Publish Survey',
                'text' => 'You are about to publish the following survey: ' . $survey->title,
                'options' => true,
                'actions' => true
            ]);
            $admin_management->publishSurvey($browser, [
                'action' => 'publish'
            ]);
            $browser->within($admin_management::SURVEY_MANAGE_MODAL, function ($modal) {
                $modal->on(new Alert("You haven't selected any users!"));
            });
            $admin_management->publishSurvey($browser, [
                'action' => 'close'
            ]);
            $admin_management->clickPublishSurvey($browser, $survey->id);
            $admin_management->publishSurvey($browser, [
                'option' => 'select_users'
            ]);
            $admin_management->assertUsersDataTable($browser);
            $admin_management->publishSurvey($browser, [
                'action' => 'publish'
            ]);
            $browser->within($admin_management::SURVEY_MANAGE_MODAL, function ($modal) {
                $modal->on(new Alert("You haven't selected any users!"));
            });
        });
    }

    public function test_admin_publish_survey_notify_all_with_ppoc()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAsRole('admin');

            $published_indexes = IndexConfiguration::getPublishedConfigurations();

            $survey = QuestionnaireHelper::storeQuestionnaire([
                'title' => Str::random(5),
                'description' => Str::random(10),
                'deadline' => date('Y-m-d'),
                'index_configuration_id' => $published_indexes->first()->id
            ]);

            $surveys = QuestionnaireHelper::getQuestionnaires();

            $admin_management = new AdminManagement;

            $ppoc1 = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'Primary PoC',
                    'country' => Country::first()->name
                ]
            ]);
            
            $ppoc2 = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'Primary PoC',
                    'country' => Country::skip(1)->first()->name
                ]
            ]);

            $users_data = [
                $ppoc1->id => [
                    'select' => true,
                    'name' => $ppoc1->name,
                    'email' => $ppoc1->email,
                    'role' => $ppoc1->permissions->first()->role->name,
                    'country' => $ppoc1->permissions->first()->country->name
                ],
                $ppoc2->id => [
                    'select' => true,
                    'name' => $ppoc2->name,
                    'email' => $ppoc2->email,
                    'role' => $ppoc2->permissions->first()->role->name,
                    'country' => $ppoc2->permissions->first()->country->name
                ]
            ];

            $data['role_id'] = [1, 2, 3];
            $users = User::getUsersByCountryAndRole($data);
            foreach ($users as $user) {
                $users_data += [
                    $user->user->id => [
                        'missing' => true
                    ]
                ];
            }

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $admin_management->assert($browser);
            $surveys_data = $admin_management->getDataTableData($surveys);
            $admin_management->assertSurveysDataTable($browser, $surveys_data);
            $admin_management->clickPublishSurvey($browser, $survey->id);
            $admin_management->publishSurvey($browser, [
                'option' => 'notify_all',
                'action' => 'publish'
            ]);
            $ppoc3 = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'Primary PoC',
                    'country' => Country::skip(2)->first()->name
                ]
            ]);
            $users_data += [
                $ppoc3->id => [
                    'select' => false,
                    'name' => $ppoc3->name,
                    'email' => $ppoc3->email,
                    'role' => $ppoc3->permissions->first()->role->name,
                    'country' => $ppoc3->permissions->first()->country->name
                ]
            ];
            $admin_management->clickPublishSurvey($browser, $survey->id);
            $admin_management->publishSurvey($browser, [
                'option' => 'select_users',
            ]);
            $admin_management->assertUsersDataTable($browser, $users_data);
        });
    }

    public function test_admin_publish_survey_select_users()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAsRole('admin');

            $published_indexes = IndexConfiguration::getPublishedConfigurations();

            $survey = QuestionnaireHelper::storeQuestionnaire([
                'title' => Str::random(5),
                'description' => Str::random(10),
                'deadline' => date('Y-m-d'),
                'index_configuration_id' => $published_indexes->first()->id
            ]);

            $surveys = QuestionnaireHelper::getQuestionnaires();

            $admin_management = new AdminManagement;

            $ppoc1 = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'Primary PoC',
                    'country' => Country::first()->name
                ]
            ]);

            $ppoc2 = TestHelper::createNewUser([
                'permissions' => [
                    'role' => 'Primary PoC',
                    'country' => Country::skip(1)->first()->name
                ]
            ]);

            $users_data = [
                $ppoc1->id => [
                    'select' => false,
                    'name' => $ppoc1->name,
                    'email' => $ppoc1->email,
                    'role' => $ppoc1->permissions->first()->role->name,
                    'country' => $ppoc1->permissions->first()->country->name
                ],
                $ppoc2->id => [
                    'select' => false,
                    'name' => $ppoc2->name,
                    'email' => $ppoc2->email,
                    'role' => $ppoc2->permissions->first()->role->name,
                    'country' => $ppoc2->permissions->first()->country->name
                ]
            ];

            $data['role_id'] = [1, 2, 3];
            $users = User::getUsersByCountryAndRole($data);
            foreach ($users as $user) {
                $users_data += [
                    $user->user->id => [
                        'missing' => true
                    ]
                ];
            }

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $admin_management->assert($browser);
            $surveys_data = $admin_management->getDataTableData($surveys);
            $admin_management->assertSurveysDataTable($browser, $surveys_data);
            $admin_management->clickPublishSurvey($browser, $survey->id);
            $admin_management->publishSurvey($browser, [
                'option' => 'select_users'
            ]);
            $admin_management->assertUsersDataTable($browser, $users_data);
            $admin_management->selectUsers($browser, [$ppoc1->id]);
            $admin_management->publishSurvey($browser, [
                'action' => 'publish'
            ]);
            $surveys_data[$survey->id]['status'] = 'Published';
            $surveys_data[$survey->id]['submitted'] = false;
            $admin_management->assertSurveysDataTable($browser, $surveys_data);
            $admin_management->clickPublishSurvey($browser, $survey->id);
            $admin_management->publishSurvey($browser, [
                'option' => 'select_users'
            ]);
            $users_data[$ppoc1->id]['select'] = true;
            $admin_management->assertUsersDataTable($browser, $users_data);
            $admin_management->selectUsers($browser, [$ppoc2->id]);
            $admin_management->publishSurvey($browser, [
                'action' => 'publish'
            ]);
            $admin_management->assertSurveysDataTable($browser, $surveys_data);
            $admin_management->clickPublishSurvey($browser, $survey->id);
            $admin_management->publishSurvey($browser, [
                'option' => 'select_users'
            ]);
            $users_data[$ppoc2->id]['select'] = true;
            $admin_management->assertUsersDataTable($browser, $users_data);
        });
    }
}
