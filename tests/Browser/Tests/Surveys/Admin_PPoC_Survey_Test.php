<?php

namespace Tests\Browser\Survey;

use App\HelperFunctions\GeneralHelper;
use App\HelperFunctions\QuestionnaireCountryHelper;
use App\HelperFunctions\QuestionnaireHelper;
use App\HelperFunctions\TestHelper;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use App\Models\Indicator;
use Carbon\Carbon;
use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\Survey\AdminManagement;
use Tests\Browser\Pages\Survey\AdminDashboard;
use Tests\Browser\Pages\Survey\DashboardManagement;
use Tests\Browser\Pages\Survey\Management;
use Tests\Browser\Pages\Survey\View;
use Tests\Browser\Components\Button;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class Admin_PPoC_Survey_Test extends DuskTestCase
{
    use DatabaseTransactions;

    protected $other_admin;
    protected $questionnaire;
    protected $year;
    protected $questionnaire_country;
    protected $deadline;
    protected $indicators;
    protected $indicator_first;
    protected $indicator_second;
    protected $indicator_third;
    protected $survey_inputs;
    protected $indicator_state;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed --class=TestQuestionnaireUsersSeeder');
        
        $this->other_admin = TestHelper::createNewUser([
            'permissions' => [
                'role' => 'admin',
                'country' => config('constants.USER_GROUP')
            ]
        ]);

        $this->questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $this->year = $this->questionnaire->year;
        $this->questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $this->questionnaire->id)->first();
        $this->deadline = GeneralHelper::dateFormat($this->questionnaire->deadline, 'd-m-Y');
        $this->indicators = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(3)->pluck('name')->toArray();
        $this->indicator_first = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->value('id');
        $this->indicator_second = Indicator::skip(1)->where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->value('id');
        $this->indicator_third = Indicator::skip(2)->where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->value('id');

        $this->survey_inputs = [
            $this->indicator_first => [
                'questions' => [
                    1 =>
                    [
                        'choice' => '',
                        'answers' => [
                            '3',
                            '4',
                            '5'
                        ],
                        'reference' => Str::random(5)
                    ],
                    2 =>
                    [
                        'choice' => '',
                        'answers' => [
                            '3',
                            '4',
                            '5'
                        ],
                        'reference' => Str::random(5)
                    ]
                ],
                'comments' => Str::random(5),
                'rating' => 5,
                'comments_loaded' => false,
                'rating_loaded' => false
            ],
            $this->indicator_second => [
                'questions' => [
                    1 =>
                    [
                        'choice' => '',
                        'answers' => [
                            '5'
                        ],
                        'reference' => Str::random(5)
                    ],
                    2 =>
                    [
                        'choice' => '',
                        'answers' => [
                            '5'
                        ],
                        'reference' => Str::random(5)
                    ]
                ],
                'comments' => Str::random(5),
                'rating' => 4,
                'comments_loaded' => false,
                'rating_loaded' => false
            ],
            $this->indicator_third => [
                'questions' => [
                    1 =>
                    [
                        'choice' => '',
                        'answers' => [
                            '1',
                            '2',
                            '3'
                        ],
                        'reference' => Str::random(5),
                        'accordion' => true
                    ],
                    2 =>
                    [
                        'accordion' => true,
                        'choice' => '',
                        'answers' => [
                            '1'
                        ],
                        'reference' => Str::random(5)
                    ]
                ],
                'comments' => Str::random(5),
                'rating' => 3,
                'comments_loaded' => false,
                'rating_loaded' => false
            ]
        ];

        $this->indicator_state = [
            $this->indicator_second => [
                'request_requested_changes' => Str::random(5),
                'provide_requested_changes' => Str::random(5),
                'request_requested_edit_changes' => Str::random(5),
                'request_requested_other_changes' => Str::random(5)
            ],
            $this->indicator_third => [
                'request_requested_changes' => Str::random(5)
            ]
        ];
    }

    public function getIndicatorInputsForSave($json_data, $indicator)
    {
        $inputs = [];

        foreach ($json_data['contents'] as $indicator_data)
        {
            if ($indicator_data['id'] == $indicator->id)
            {
                $qcount = 0;

                foreach ($indicator_data['form'] as $form)
                {
                    foreach ($form['contents'] as $question)
                    {
                        $number = ++$qcount;

                        array_push($inputs, [
                            'id' => $indicator_data['id'],
                            'accordion' => $form['order'],
                            'order' => $question['order'],
                            'choice' => $this->survey_inputs[$indicator->id]['questions'][$number]['choice'],
                            'answers' => $this->survey_inputs[$indicator->id]['questions'][$number]['answers'],
                            'reference' => $this->survey_inputs[$indicator->id]['questions'][$number]['reference'],
                            'comments' => $this->survey_inputs[$indicator->id]['comments'],
                            'rating' => $this->survey_inputs[$indicator->id]['rating'],
                            'comments_loaded' => $this->survey_inputs[$indicator->id]['comments_loaded'],
                            'rating_loaded' => $this->survey_inputs[$indicator->id]['rating_loaded']
                        ]);
                    }
                }

                break;
            }
        }

        return $inputs;
    }

    public function submitSurvey($browser, &$datatable_data)
    {
        $json_data = $this->questionnaire_country->json_data;
        $indicators = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(3)->get();

        $user = $browser->loginAsRole('ppoc', false)->user;

        $order = 1;
        foreach ($indicators as $indicator)
        {
            QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->questionnaire_country, $json_data, collect([$indicator]), [
                'indicator_answers' => $this->getIndicatorInputsForSave($json_data, $indicator),
                'action' => 'save'
            ]);
            $datatable_data[$order] = [
                'select' => 'enabled',
                'name' => $order . '. ' . $indicator->name,
                'assignee' => $user->name,
                'status' => 'Approved',
                'deadline' => $this->deadline,
                'approve' => 'enabled',
                'edit' => 'disabled',
                'review' => 'enabled'
            ];
            $this->survey_inputs[$indicator->id]['assignee'] = $user->name;

            $order++;
        }
        QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->questionnaire_country, $json_data, $indicators, [
            'action' => 'submit'
        ]);
        
        $this->questionnaire_country->completed = true;
        $this->questionnaire_country->submitted_by = $user->id;
        $this->questionnaire_country->submitted_at = Carbon::now();
        $this->questionnaire_country->save();
    }

    public function acceptFirstIndicator($browser, &$datatable_data)
    {
        $json_data = $this->questionnaire_country->json_data;
        $indicator = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->first();

        $browser->loginAsRole('admin', false);

        $order = 1;
        $datatable_data[$order]['select'] = $datatable_data[$order]['approve'] = $datatable_data[$order]['edit'] = 'disabled';
        QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->questionnaire_country, $json_data, collect([$indicator]), [
            'action' => 'final_approve'
        ]);
    }

    public function requestChangesSecondIndicator($browser, &$datatable_data, $user)
    {
        $indicator = Indicator::skip(1)->where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->first();

        if ($user == 'admin')
        {
            $browser->loginAsRole('admin', false);

            $requested_changes = $this->indicator_state[$indicator->id]['request_requested_changes'];
        }
        elseif ($user == 'other_admin')
        {
            Auth::loginUsingId($this->other_admin->id);

            $requested_changes = $this->indicator_state[$indicator->id]['request_requested_other_changes'];
        }

        $order = 2;
        $datatable_data[$order]['select'] = $datatable_data[$order]['approve'] = 'disabled';
        $datatable_data[$order]['status'] = 'Under review';
        QuestionnaireCountryHelper::requestChangesQuestionnaireCountryIndicator($this->questionnaire_country, $indicator, [
            'changes' => $requested_changes,
            'deadline' => $datatable_data[$order]['deadline']
        ]);
    }

    public function requestChangesThirdIndicator($browser, &$datatable_data)
    {
        $indicator = Indicator::skip(2)->where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->first();
        
        $browser->loginAsRole('admin', false);

        $order = 3;
        $datatable_data[$order]['select'] = $datatable_data[$order]['approve'] = 'disabled';
        $datatable_data[$order]['status'] = 'Under review';
        QuestionnaireCountryHelper::requestChangesQuestionnaireCountryIndicator($this->questionnaire_country, $indicator, [
            'changes' => $this->indicator_state[$indicator->id]['request_requested_changes'],
            'deadline' => $datatable_data[$order]['deadline']
        ]);
    }

    public function submitRequestedChanges($browser, &$datatable_data)
    {
        $browser->loginAsRole('admin', false);

        $order = 2;
        $datatable_data[$order]['select'] = $datatable_data[$order]['edit'] = 'enabled';
        $datatable_data[$order]['status'] = 'Assigned';

        $order = 3;
        $datatable_data[$order]['select'] = $datatable_data[$order]['edit'] = 'disabled';

        QuestionnaireCountryHelper::submitQuestionnaireCountryRequestedChanges($this->questionnaire_country);

        $this->questionnaire_country->submitted_by = null;
        $this->questionnaire_country->requested_changes_submitted_at = Carbon::now();
        $this->questionnaire_country->save();
    }

    public function test_admin_accept_request_changes_without_submit_request_changes(): void
    {
        $this->browse(function (Browser $browser) {
            $dashboard_data = [
                'progress' => '100',
                'approve_selected_indicators' => 'disabled',
                'submit_requested_changes' => 'disabled'
            ];
            $datatable_data = [];

            // Pre-conditions
            $this->submitSurvey($browser, $datatable_data); // PPoC

            $user = $browser->loginAsRole('admin')->user;

            $surveys = QuestionnaireHelper::getQuestionnaires();
            $questionnaire_countries = QuestionnaireCountryHelper::getQuestionnaireCountries($this->questionnaire);

            foreach ($questionnaire_countries as $questionnaire_country) {
                QuestionnaireCountryHelper::getQuestionnaireCountryData($questionnaire_country);
            }

            $admin_management = new AdminManagement;
            $admin_dashboard = new AdminDashboard($this->questionnaire->id, $this->questionnaire->title);
            $dashboard_management = new DashboardManagement($this->questionnaire_country->id, $this->questionnaire_country->name, $user);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user);
            
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $admin_management->assert($browser);
            $surveys_data = $admin_management->getDataTableData($surveys);
            $admin_management->assertSurveysDataTable($browser, $surveys_data);
            $admin_management->clickDashboard($browser, $this->questionnaire->id);
            $admin_dashboard->assert($browser);
            $admin_dashboard->assertActions($browser);
            $questionnaire_countries_data = $admin_dashboard->getDataTableData($questionnaire_countries);
            $admin_dashboard->assertDataTable($browser, $questionnaire_countries_data);
            $admin_dashboard->clickDashboard($browser, $this->questionnaire_country->id);
            $dashboard_management->assert($browser);
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->clickApproveIndicator($browser, 1);
            // Indicator 1 - accept
            $order = 1;
            $datatable_data[$order]['select'] = $datatable_data[$order]['approve'] = 'disabled';
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->clickReviewIndicator($browser, 2);
            $view->assertPath($browser);
            // Indicator 2 - request changes
            $order = 2;
            $indicator_id = $this->indicator_second;
            $deadline = $datatable_data[$order]['deadline'];
            $changes = $this->indicator_state[$indicator_id]['request_requested_changes'];
            $datatable_data[$order]['select'] = $datatable_data[$order]['approve'] = 'disabled';
            $datatable_data[$order]['status'] = 'Under review';
            $view->clickRequestChanges($browser, $indicator_id, [
                'deadline' => $deadline,
                'changes' => $changes
            ]);
            $view->clickSaveRequestChanges($browser, $indicator_id, [
                'deadline' => $deadline,
                'changes' => $changes
            ]);
            $view->clickNext($browser, $indicator_id);
            // Indicator 3 - request changes
            $order = 3;
            $indicator_id = $this->indicator_third;
            $deadline = $datatable_data[$order]['deadline'];
            $changes = $this->indicator_state[$indicator_id]['request_requested_changes'];
            $datatable_data[$order]['select'] = $datatable_data[$order]['approve'] = 'disabled';
            $datatable_data[$order]['status'] = 'Under review';
            $view->clickRequestChanges($browser, $indicator_id, [
                'deadline' => $deadline,
                'changes' => $changes
            ]);
            $view->clickSaveRequestChanges($browser, $indicator_id, [
                'deadline' => $deadline,
                'changes' => $changes
            ]);
            $view->clickBackToPage($browser, 'Back to Dashboard');
            $dashboard_management->assertPath($browser);
            $dashboard_data['progress'] = '33';
            $dashboard_data['submit_requested_changes'] = 'enabled';
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
        });
    }

    public function test_other_admin_request_changes_edit_discard_and_submit_request_changes(): void
    {
        $this->browse(function (Browser $browser) {
            $dashboard_data = [
                'progress' => '33',
                'approve_selected_indicators' => 'disabled',
                'submit_requested_changes' => 'enabled'
            ];
            $datatable_data = [];

            // Pre-conditions
            $this->submitSurvey($browser, $datatable_data);                           // PPoC
            $this->acceptFirstIndicator($browser, $datatable_data);                   // Admin
            $this->requestChangesSecondIndicator($browser, $datatable_data, 'admin'); // Admin
            $this->requestChangesThirdIndicator($browser, $datatable_data);           // Admin

            $admin = $browser->loginAsRole('admin', false, false)->user;
            $other_admin = $this->other_admin;
            $browser->loginAs($other_admin);

            Auth::loginUsingId($other_admin->id);

            $questionnaire_countries = QuestionnaireCountryHelper::getQuestionnaireCountries($this->questionnaire);

            foreach ($questionnaire_countries as $questionnaire_country) {
                QuestionnaireCountryHelper::getQuestionnaireCountryData($questionnaire_country);
            }

            $admin_management = new AdminManagement;
            $admin_dashboard = new AdminDashboard($this->questionnaire->id, $this->questionnaire->title, $questionnaire_countries);
            $dashboard_management = new DashboardManagement($this->questionnaire_country->id, $this->questionnaire_country->name, $other_admin);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $other_admin, $admin);
            
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $admin_management->clickDashboard($browser, $this->questionnaire->id);
            $admin_dashboard->clickDashboard($browser, $this->questionnaire_country->id);
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->clickReviewIndicator($browser, 2);
            $view->assertPath($browser);
            // Indicator 2 - edit requested changes
            $order = 2;
            $indicator_id = $this->indicator_second;
            $deadline = $datatable_data[$order]['deadline'];
            $changes = $this->indicator_state[$indicator_id]['request_requested_changes'];
            $edit_changes = $this->indicator_state[$indicator_id]['request_requested_edit_changes'];
            $view->assertIndicatorRequestChangesAlert($browser);
            $view->clickEditRequestedChanges($browser, $indicator_id, [
                'deadline' => $deadline,
                'changes' => $changes,
                'edit_changes' => $edit_changes
            ]);
            $view->clickSaveRequestChanges($browser, $indicator_id, [
                'deadline' => $deadline,
                'changes' => $edit_changes
            ]);
            $view->clickNext($browser, $indicator_id);
            $view->setAuthorUser($admin);
            // Indicator 3 - discard requested changes
            $order = 3;
            $indicator_id = $this->indicator_third;
            $view->assertIndicatorRequestChangesAlert($browser);
            $view->clickDiscardRequestedChanges($browser, $indicator_id, [
                'previous_state' => $view::ACCEPT_REQUEST_CHANGES,
                'deadline' => $datatable_data[$order]['deadline'],
                'changes' => $this->indicator_state[$indicator_id]['request_requested_changes']
            ]);
            $view->clickBackToPage($browser, 'Back to Dashboard');
            $dashboard_management->assertPath($browser);
            $datatable_data[$order]['select'] = $datatable_data[$order]['approve'] = 'enabled';
            $datatable_data[$order]['status'] = 'Approved';
            $dashboard_data['progress'] = '67';
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->clickSubmitRequestedChanges($browser);
            $dashboard_data['submit_requested_changes'] = 'disabled';
            for ($i = 2; $i <= 3; $i++)
            {
                $datatable_data[$i]['select'] = 'disabled';
                if ($i == 2) {
                    $datatable_data[$i]['status'] = 'Assigned';
                }
                $datatable_data[$i]['approve'] = 'disabled';
            }
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
        });
    }

    public function test_ppoc_provide_changes_and_submit_survey(): void
    {
        $this->browse(function (Browser $browser) {
            $dashboard_data = [
                'progress' => '67',
                'edit_selected_indicators' => 'disabled',
                'submit_requested_changes' => 'disabled'
            ];
            $datatable_data = [];
            
            // Pre-conditions
            $this->submitSurvey($browser, $datatable_data);                                 // PPoC
            $this->acceptFirstIndicator($browser, $datatable_data);                         // Admin
            $this->requestChangesSecondIndicator($browser, $datatable_data, 'other_admin'); // Admin
            $this->submitRequestedChanges($browser, $datatable_data);                       // Admin

            $user = $browser->loginAsRole('ppoc')->user;
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Pending');
            $management->clickDashboard($browser, $this->questionnaire_country->id);
            $dashboard_management = new DashboardManagement($this->questionnaire_country->id, $this->questionnaire->title, $user);
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->clickReviewIndicator($browser, 1);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user, $browser->loginAsRole('admin', false, false)->user);
            $view->assertPath($browser);
            // Indicator 1
            $indicator_id = $this->indicator_first;
            $view->assertIndicatorState($browser, $indicator_id, [
                'state' => $view::FINAL_APPROVED
            ]);
            $view->clickNext($browser, $indicator_id);
            // Indicator 2
            $order = 2;
            $indicator_id = $this->indicator_second;
            $provide_requested_changes = $this->indicator_state[$indicator_id]['provide_requested_changes'];
            $view->assertIndicatorState($browser, $indicator_id, [
                'state' => $view::REQUESTED_CHANGES,
                'deadline' => $datatable_data[$order]['deadline'],
                'changes' => $this->indicator_state[$indicator_id]['request_requested_other_changes']
            ]);
            $indicator_inputs = [
                'assignee' => $datatable_data[$order]['assignee'],
                'last_saved' => true,
                'questions' => [
                    1 =>
                    [
                        'reference' => $provide_requested_changes
                    ]
                ],
                'status' => 'complete'
            ];
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickSave($browser, $indicator_id);
            $view->clickNext($browser, $indicator_id);
            $view->setAuthorUser($user);
            $this->survey_inputs[$indicator_id]['questions'][1]['reference'] = $provide_requested_changes;
            // Indicator 3
            $indicator_id = $this->indicator_third;
            $view->assertIndicatorState($browser, $indicator_id, [
                'state' => $view::APPROVED
            ]);
            $view->clickReviewAndSubmit($browser);
            $view->assertReviewAndSubmitModal($browser, [
                'sections' =>
                [
                    '@survey_submit_complete_section' =>
                    [
                        'message' => 'You can now submit the survey to ' . config('constants.USER_GROUP') . ' for review!'
                    ]
                ],
                'submit' => 'enabled'
            ]);
            $view->clickReviewAndSubmitButton($browser, View::SURVEY_REVIEW_AND_SUBMIT_MODAL_SUBMIT);
            $view->assertPath($browser);
            $order = 1;
            foreach ($this->survey_inputs as $indicator_id => $indicator_inputs)
            {
                $view->assertIndicatorInputsAfterAction($browser, $order, $indicator_id, $indicator_inputs, 'submit');

                $order++;
            }
            $view->clickBackToPage($browser, 'Back to Dashboard');
            $dashboard_management->assertPath($browser);
            for ($i = 2; $i <= 3; $i++) {
                $datatable_data[$i] = [
                    'select' => 'disabled',
                    'name' => $datatable_data[$i]['name'],
                    'assignee' => $datatable_data[$i]['assignee'],
                    'status' => 'Approved',
                    'deadline' => $datatable_data[$i]['deadline'],
                    'edit' => 'disabled',
                    'review' => 'enabled'
                ];
            }
            $dashboard_data['progress'] = '100';
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->clickBreadcrumb($browser, '@survey_management', 'Surveys');
            $management->assertPath($browser);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Submitted', $user->name);
        });
    }

    public function test_admin_request_more_changes_and_submit_request_changes(): void
    {
        $this->browse(function (Browser $browser) {
            $dashboard_data = [
                'progress' => '100',
                'approve_selected_indicators' => 'disabled',
                'submit_requested_changes' => 'disabled'
            ];
            $datatable_data = [];

            // Pre-conditions
            $this->submitSurvey($browser, $datatable_data);                                 // PPoC
            $this->acceptFirstIndicator($browser, $datatable_data);                         // Admin
            $this->requestChangesSecondIndicator($browser, $datatable_data, 'other_admin'); // Admin
            $this->submitRequestedChanges($browser, $datatable_data);                       // Admin
            $this->submitSurvey($browser, $datatable_data);                                 // PPoC

            $admin = $browser->loginAsRole('admin')->user;
            $other_admin = $this->other_admin;

            $questionnaire_countries = QuestionnaireCountryHelper::getQuestionnaireCountries($this->questionnaire);

            foreach ($questionnaire_countries as $questionnaire_country) {
                QuestionnaireCountryHelper::getQuestionnaireCountryData($questionnaire_country);
            }

            $admin_management = new AdminManagement;
            $admin_dashboard = new AdminDashboard($this->questionnaire->id, $this->questionnaire->title, $questionnaire_countries);
            $dashboard_management = new DashboardManagement($this->questionnaire_country->id, $this->questionnaire_country->name, $admin);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $admin, $other_admin);
            
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $admin_management->clickDashboard($browser, $this->questionnaire->id);
            $admin_dashboard->clickDashboard($browser, $this->questionnaire_country->id);
            $datatable_data[1]['select'] = $datatable_data[1]['approve'] = 'disabled';
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->clickReviewIndicator($browser, 2);
            $view->assertPath($browser);
            // Indicator 2 - request changes
            $order = 2;
            $indicator_id = $this->indicator_second;
            $deadline = $datatable_data[$order]['deadline'];
            $changes = $this->indicator_state[$indicator_id]['request_requested_changes'];
            $other_changes = $this->indicator_state[$indicator_id]['request_requested_other_changes'];
            $datatable_data[$order]['select'] = $datatable_data[$order]['approve'] = 'disabled';
            $datatable_data[$order]['status'] = 'Under review';
            $view->assertIndicatorState($browser, $indicator_id, [
                'state' => $view::REQUESTED_CHANGES,
                'deadline' => $deadline,
                'changes' => $other_changes
            ]);
            $view->clickRequestChanges($browser, $indicator_id, [
                'deadline' => $deadline,
                'changes' => $changes
            ]);
            $view->clickSaveRequestChanges($browser, $indicator_id, [
                'deadline' => $deadline,
                'changes' => $changes,
                'history_changes' => $other_changes
            ]);
            $view->clickNext($browser, $indicator_id);
            $view->clickBackToPage($browser, 'Back to Dashboard');
            $dashboard_management->assertPath($browser);
            $dashboard_data['progress'] = '67';
            $dashboard_data['submit_requested_changes'] = 'enabled';
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->clickSubmitRequestedChanges($browser);
            $dashboard_data['submit_requested_changes'] = 'disabled';
            for ($i = 2; $i <= 3; $i++)
            {
                $datatable_data[$i]['select'] = 'disabled';
                if ($i == 2) {
                    $datatable_data[$i]['status'] = 'Assigned';
                }
                $datatable_data[$i]['approve'] = 'disabled';
            }
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
        });
    }

    public function test_ppoc_provide_more_changes_and_submit_survey(): void
    {
        $this->browse(function (Browser $browser) {
            $dashboard_data = [
                'progress' => '67',
                'edit_selected_indicators' => 'disabled',
                'submit_requested_changes' => 'disabled'
            ];
            $datatable_data = [];
            
            // Pre-conditions
            $this->submitSurvey($browser, $datatable_data);                                 // PPoC
            $this->acceptFirstIndicator($browser, $datatable_data);                         // Admin
            $this->requestChangesSecondIndicator($browser, $datatable_data, 'other_admin'); // Admin
            $this->submitRequestedChanges($browser, $datatable_data);                       // Admin
            $this->submitSurvey($browser, $datatable_data);                                 // PPoC
            $this->requestChangesSecondIndicator($browser, $datatable_data, 'admin');       // Admin
            $this->submitRequestedChanges($browser, $datatable_data);                       // Admin

            $user = $browser->loginAsRole('ppoc')->user;
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Pending');
            $management->clickDashboard($browser, $this->questionnaire_country->id);
            $dashboard_management = new DashboardManagement($this->questionnaire_country->id, $this->questionnaire->title, $user);
            $datatable_data[1]['select'] = $datatable_data[1]['edit'] = 'disabled';
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->clickReviewIndicator($browser, 2);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user, $browser->loginAsRole('admin', false, false)->user);
            $view->assertPath($browser);
            // Indicator 2
            $order = 2;
            $indicator_id = $this->indicator_second;
            $provide_requested_changes = $this->indicator_state[$indicator_id]['provide_requested_changes'];
            $view->assertIndicatorState($browser, $indicator_id, [
                'state' => $view::REQUESTED_CHANGES,
                'deadline' => $datatable_data[$order]['deadline'],
                'changes' => $this->indicator_state[$indicator_id]['request_requested_changes']
            ]);
            $view->clickSurveyRequestedChangesHistory($browser, $indicator_id);
            $view->assertSurveyRequestedChangesHistory($browser, $indicator_id, $this->indicator_state[$indicator_id]['request_requested_other_changes']);
            $indicator_inputs = [
                'assignee' => $datatable_data[$order]['assignee'],
                'last_saved' => true,
                'questions' => [
                    1 =>
                    [
                        'reference' => $provide_requested_changes
                    ]
                ],
                'status' => 'complete'
            ];
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickSave($browser, $indicator_id);
            $view->clickNext($browser, $indicator_id);
            $view->clickReviewAndSubmit($browser);
            $view->assertReviewAndSubmitModal($browser, [
                'sections' =>
                [
                    '@survey_submit_complete_section' =>
                    [
                        'message' => 'You can now submit the survey to ' . config('constants.USER_GROUP') . ' for review!'
                    ]
                ],
                'submit' => 'enabled'
            ]);
            $view->clickReviewAndSubmitButton($browser, View::SURVEY_REVIEW_AND_SUBMIT_MODAL_SUBMIT);
            $view->assertPath($browser);
            $view->clickBackToPage($browser, 'Back to Dashboard');
            $dashboard_management->assertPath($browser);
            for ($i = 2; $i <= 3; $i++) {
                $datatable_data[$i] = [
                    'select' => 'disabled',
                    'name' => $datatable_data[$i]['name'],
                    'assignee' => $datatable_data[$i]['assignee'],
                    'status' => 'Approved',
                    'deadline' => $datatable_data[$i]['deadline'],
                    'edit' => 'disabled',
                    'review' => 'enabled'
                ];
            }
            $dashboard_data['progress'] = '100';
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
        });
    }

    public function test_other_admin_accept_all(): void
    {
        $this->browse(function (Browser $browser) {
            $dashboard_data = [
                'progress' => '100',
                'approve_selected_indicators' => 'disabled',
                'submit_requested_changes' => 'disabled'
            ];
            $datatable_data = [];

            // Pre-conditions
            $this->submitSurvey($browser, $datatable_data);                                 // PPoC
            $this->acceptFirstIndicator($browser, $datatable_data);                         // Admin
            $this->requestChangesSecondIndicator($browser, $datatable_data, 'other_admin'); // Admin
            $this->submitRequestedChanges($browser, $datatable_data);                       // Admin
            $this->submitSurvey($browser, $datatable_data);                                 // PPoC
            $this->requestChangesSecondIndicator($browser, $datatable_data, 'admin');       // Admin
            $this->submitRequestedChanges($browser, $datatable_data);                       // Admin
            $this->submitSurvey($browser, $datatable_data);                                 // PPoC

            $admin = $browser->loginAsRole('admin', false, false)->user;
            $other_admin = $this->other_admin;
            $browser->loginAs($other_admin);

            Auth::loginUsingId($other_admin->id);

            $questionnaire_countries = QuestionnaireCountryHelper::getQuestionnaireCountries($this->questionnaire);

            foreach ($questionnaire_countries as $questionnaire_country) {
                QuestionnaireCountryHelper::getQuestionnaireCountryData($questionnaire_country);
            }

            $admin_management = new AdminManagement;
            $admin_dashboard = new AdminDashboard($this->questionnaire->id, $this->questionnaire->title);
            $dashboard_management = new DashboardManagement($this->questionnaire_country->id, $this->questionnaire_country->name, $admin);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $other_admin, $admin);
            
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@management'))
                    ->clickButton()
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $admin_management->clickDashboard($browser, $this->questionnaire->id);
            $admin_dashboard->clickDashboard($browser, $this->questionnaire_country->id);
            $datatable_data[1]['select'] = $datatable_data[1]['approve'] = 'disabled';
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->clickReviewIndicator($browser, 2);
            $view->assertPath($browser);
            // Indicator 2 - accept
            $order = 2;
            $indicator_id = $this->indicator_second;
            $changes = $this->indicator_state[$indicator_id]['request_requested_changes'];
            $other_changes = $this->indicator_state[$indicator_id]['request_requested_other_changes'];
            $datatable_data[$order]['select'] = $datatable_data[$order]['approve'] = 'disabled';
            $datatable_data[$order]['status'] = 'Under review';
            $view->assertIndicatorState($browser, $indicator_id, [
                'state' => $view::REQUESTED_CHANGES,
                'deadline' => $datatable_data[$order]['deadline'],
                'changes' => $changes
            ]);
            $view->clickSurveyRequestedChangesHistory($browser, $indicator_id);
            $view->assertSurveyRequestedChangesHistory($browser, $indicator_id, $other_changes);
            $view->clickSurveyRequestedChangesHistory($browser, $indicator_id);
            $view->clickAccept($browser, $indicator_id, [
                'changes' => $changes
            ]);
            $view->clickNext($browser, $indicator_id);
            // Indicator 3 - accept
            $view->clickAccept($browser, $this->indicator_third, [], false);
            $view->clickBackToPage($browser, 'Back to Dashboard');
            $dashboard_management->assertPath($browser);
            for ($i = 1; $i <= 3; $i++)
            {
                $datatable_data[$i]['select'] = $datatable_data[$i]['approve'] = 'disabled';
                $datatable_data[$i]['status'] = 'Approved';
            }
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->clickBreadcrumb($browser, '@survey_admin_dashboard', 'Survey Dashboard - ' . $this->questionnaire->title);
            $admin_dashboard->assertPath($browser);
            $questionnaire_countries_data = $admin_dashboard->getDataTableData($questionnaire_countries);
            $questionnaire_countries_data[$this->questionnaire_country->id]['status'] = 'Approved';
            $admin_dashboard->assertDataTable($browser, $questionnaire_countries_data);
        });
    }
}
