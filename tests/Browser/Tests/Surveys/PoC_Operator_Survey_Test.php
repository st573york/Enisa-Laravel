<?php

namespace Tests\Browser\Survey;

use App\HelperFunctions\GeneralHelper;
use App\HelperFunctions\QuestionnaireCountryHelper;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use App\Models\Indicator;
use Carbon\Carbon;
use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\Survey\DashboardManagement;
use Tests\Browser\Pages\Survey\Management;
use Tests\Browser\Pages\Survey\View;
use Tests\Browser\Components\Alert;
use Tests\Browser\Components\Button;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PoC_Operator_Survey_Test extends DuskTestCase
{
    use DatabaseTransactions;

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
            $this->indicator_third => [
                'request_requested_changes' => Str::random(5),
                'provide_requested_changes' => Str::random(5)
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

    public function assignIndicatorsToPoC($browser)
    {
        $json_data = $this->questionnaire_country->json_data;
        $indicators = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(3)->get();

        $user = $browser->loginAsRole('poc', false)->user;
        
        foreach ($indicators as $indicator) {
            QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->questionnaire_country, $json_data, collect([$indicator]), [
                'action' => 'edit',
                'assignee' => $user->id,
                'deadline' => $this->deadline
            ]);
        }
    }

    public function assignIndicators($browser)
    {
        $json_data = $this->questionnaire_country->json_data;
        $indicators = Indicator::skip(1)->where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(2)->get();

        $user = $browser->loginAsRole('operator', false, false)->user;
        $browser->loginAsRole('poc', false);
        
        foreach ($indicators as $indicator) {
            QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->questionnaire_country, $json_data, collect([$indicator]), [
                'action' => 'edit',
                'assignee' => $user->id,
                'deadline' => $this->deadline
            ]);
        }
    }

    public function submitIndicators($browser, &$datatable_data)
    {
        $json_data = $this->questionnaire_country->json_data;
        $indicators = Indicator::skip(1)->where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(2)->get();

        $user = $browser->loginAsRole('operator', false)->user;

        $order = 2;
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
                'status' => 'Completed',
                'deadline' => $this->deadline,
                'edit' => 'enabled',
                'review' => 'enabled'
            ];

            $order++;
        }
        QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->questionnaire_country, $json_data, $indicators, [
            'action' => 'submit'
        ]);
    }

    public function fillInIndicator($browser, &$datatable_data)
    {
        $json_data = $this->questionnaire_country->json_data;
        $indicator = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->first();

        $user = $browser->loginAsRole('poc', false)->user;

        QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->questionnaire_country, $json_data, collect([$indicator]), [
            'indicator_answers' => $this->getIndicatorInputsForSave($json_data, $indicator),
            'action' => 'save'
        ]);
        $order = 1;
        $datatable_data[$order] = [
            'select' => 'enabled',
            'name' => $order . '. ' . $indicator->name,
            'assignee' => $user->name,
            'status' => 'In progress',
            'deadline' => $this->deadline,
            'edit' => 'enabled',
            'review' => 'enabled'
        ];
    }

    public function acceptAndRequestChangesIndicators($browser, &$datatable_data)
    {
        $json_data = $this->questionnaire_country->json_data;
        $indicators = Indicator::skip(1)->where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(2)->get();

        $user = $browser->loginAsRole('operator', false, false)->user;
        $browser->loginAsRole('poc', false);

        $indicator = $indicators[0];
        QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->questionnaire_country, $json_data, collect([$indicator]), [
            'action' => 'approve'
        ]);
        $order = 2;
        $datatable_data[$order] = [
            'select' => 'disabled',
            'name' => $order . '. ' . $indicator->name,
            'assignee' => $user->name,
            'status' => 'Approved',
            'deadline' => $this->deadline,
            'edit' => 'disabled',
            'review' => 'enabled'
        ];
        $indicator = $indicators[1];
        QuestionnaireCountryHelper::requestChangesQuestionnaireCountryIndicator($this->questionnaire_country, $indicator, [
            'changes' => $this->indicator_state[$indicator->id]['request_requested_changes'],
            'deadline' => $this->deadline
        ]);
        QuestionnaireCountryHelper::submitQuestionnaireCountryRequestedChanges($this->questionnaire_country);
        $order = 3;
        $datatable_data[$order] = [
            'select' => 'enabled',
            'name' => $order . '. ' . $indicator->name,
            'assignee' => $user->name,
            'status' => 'Assigned',
            'deadline' => $this->deadline,
            'edit' => 'enabled',
            'review' => 'enabled'
        ];

        $this->questionnaire_country->completed = false;
        $this->questionnaire_country->requested_changes_submitted_at = Carbon::now();
        $this->questionnaire_country->save();
    }

    public function provideRequestedChangesIndicator($browser, &$datatable_data)
    {
        $json_data = $this->questionnaire_country->json_data;
        $indicators = Indicator::skip(1)->where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(2)->get();

        $user = $browser->loginAsRole('operator', false)->user;

        $indicator = $indicators[1];
        $this->survey_inputs[$indicator->id]['questions'][1]['reference'] = $this->indicator_state[$indicator->id]['provide_requested_changes'];
        QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->questionnaire_country, $json_data, collect([$indicator]), [
            'indicator_answers' => $this->getIndicatorInputsForSave($json_data, $indicator),
            'action' => 'save'
        ]);
        $order = 3;
        $datatable_data[$order] = [
            'select' => 'enabled',
            'name' => $order . '. ' . $indicator->name,
            'assignee' => $user->name,
            'status' => 'Completed',
            'deadline' => $this->deadline,
            'edit' => 'enabled',
            'review' => 'enabled'
        ];
        QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->questionnaire_country, $json_data, $indicators, [
            'action' => 'submit'
        ]);
    }

    public function test_operator_not_assigned_indicators(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAsRole('operator')
                    ->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton()
                    ->on(new Alert('You haven\'t been assigned any indicators.'));
        });
    }

    public function test_poc_assigns_indicators_to_operator(): void
    {
        $this->browse(function (Browser $browser) {
            // Pre-conditions
            $this->assignIndicatorsToPoC($browser); // PoC

            $dashboard_data = [
                'progress' => '0',
                'edit_selected_indicators' => 'disabled',
                'submit_requested_changes' => 'disabled'
            ];
            $datatable_data = [];

            $user = $browser->loginAsRole('poc')->user;
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Pending');
            $management->clickDashboard($browser, $this->questionnaire_country->id);
            for ($i = 1; $i <= 3; $i++) {
                $datatable_data[$i] = [
                    'select' => 'enabled',
                    'name' => $i. '. ' . $this->indicators[$i - 1],
                    'assignee' => $user->name,
                    'status' => 'Assigned',
                    'deadline' => $this->deadline,
                    'edit' => 'enabled',
                    'review' => 'enabled'
                ];
            }
            $dashboard_management = new DashboardManagement($this->questionnaire_country->id, $this->questionnaire->title, $user);
            $dashboard_management->assert($browser);
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->selectIndicators($browser, [2, 3]);
            $dashboard_management->clickEditSelectedIndicators($browser);
            $dashboard_management->assertEditModal($browser, [
                'title' => 'Edit Indicators',
                'assignee' => '',
                'deadline' => $this->deadline,
                'actions' => true
            ]);
            $indicator_data = $dashboard_management->editSingleOrMultipleIndicators($browser, [
                'assignee' => 3,
                'deadline' => strtotime('-1 day', strtotime($this->deadline))
            ]);
            $datatable_data = [];
            for ($i = 2; $i <= 3; $i++) {
                $datatable_data[$i] = [
                    'select' => 'enabled',
                    'name' => $i. '. ' . $this->indicators[$i - 1],
                    'assignee' => $indicator_data['assignee'],
                    'status' => 'Assigned',
                    'deadline' => $indicator_data['deadline'],
                    'edit' => 'enabled',
                    'review' => 'enabled'
                ];
            }
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
        });
    }

    public function test_operator_fills_in_online_assigned_indicators(): void
    {
        $this->browse(function (Browser $browser) {
            // Pre-conditions
            $this->assignIndicatorsToPoC($browser); // PoC
            $this->assignIndicators($browser);      // PoC

            $user = $browser->loginAsRole('operator')->user;
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Pending');
            $management->clickFillInOnline($browser, $this->questionnaire_country->id);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user);
            $view->assert($browser);
            $view->startOrResume($browser);
            // Indicator 2
            $order = 2;
            $indicator_id = $this->indicator_second;
            $indicator_inputs = [
                'assignee' => $user->name,
                'last_saved' => false,
                'questions' => $this->survey_inputs[$indicator_id]['questions'],
                'comments' => $this->survey_inputs[$indicator_id]['comments'],
                'rating' => $this->survey_inputs[$indicator_id]['rating'],
                'comments_loaded' => $this->survey_inputs[$indicator_id]['comments_loaded'],
                'rating_loaded' => $this->survey_inputs[$indicator_id]['rating_loaded'],
                'status' => 'complete'
            ];
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickSave($browser, $indicator_id);
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $this->indicators[$order - 1], $indicator_inputs);
            $view->clickNext($browser, $indicator_id);
            // Indicator 3
            $order = 3;
            $indicator_id = $this->indicator_third;
            $indicator_inputs = [
                'assignee' => $user->name,
                'last_saved' => false,
                'questions' => $this->survey_inputs[$indicator_id]['questions'],
                'comments' => $this->survey_inputs[$indicator_id]['comments'],
                'rating' => $this->survey_inputs[$indicator_id]['rating'],
                'comments_loaded' => $this->survey_inputs[$indicator_id]['comments_loaded'],
                'rating_loaded' => $this->survey_inputs[$indicator_id]['rating_loaded'],
                'status' => 'complete'
            ];
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickSave($browser, $indicator_id);
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $this->indicators[$order - 1], $indicator_inputs);
            $view->clickReviewAndSubmit($browser);
            $view->assertReviewAndSubmitModal($browser, [
                'sections' =>
                [
                    '@survey_submit_complete_section' =>
                    [
                        'message' => 'You can now submit the survey to the PoC for review!'
                    ]
                ],
                'submit' => 'enabled'
            ]);
            $view->clickReviewAndSubmitButton($browser, View::SURVEY_REVIEW_AND_SUBMIT_MODAL_SUBMIT);
            $management->assert($browser);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Completed');
        });
    }

    public function test_poc_fills_in_online_assigned_indicators_and_accept_request_changes(): void
    {
        $this->browse(function (Browser $browser) {
            $dashboard_data = [
                'progress' => '0',
                'edit_selected_indicators' => 'disabled',
                'submit_requested_changes' => 'disabled'
            ];
            $datatable_data = [];

            // Pre-conditions
            $this->assignIndicatorsToPoC($browser);             // PoC
            $this->assignIndicators($browser);                  // PoC
            $this->submitIndicators($browser, $datatable_data); // Operator
            
            $user = $browser->loginAsRole('poc')->user;
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
            $dashboard_management->clickReviewSurvey($browser);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user);
            $view->assert($browser);
            $view->startOrResume($browser);
            // Indicator 1 - fill in
            $order = 1;
            $indicator_id = $this->indicator_first;
            $indicator_inputs = [
                'assignee' => $user->name,
                'last_saved' => false,
                'questions' => $this->survey_inputs[$indicator_id]['questions'],
                'comments' => $this->survey_inputs[$indicator_id]['comments'],
                'rating' => $this->survey_inputs[$indicator_id]['rating'],
                'comments_loaded' => $this->survey_inputs[$indicator_id]['comments_loaded'],
                'rating_loaded' => $this->survey_inputs[$indicator_id]['rating_loaded'],
                'status' => 'complete'
            ];
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickSave($browser, $indicator_id);
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $this->indicators[$order - 1], $indicator_inputs);
            $view->clickNext($browser, $indicator_id);
            // Indicator 2 - accept
            $order = 2;
            $indicator_id = $this->indicator_second;
            $assignee = $datatable_data[$order]['assignee'];
            $this->survey_inputs[$indicator_id]['assignee'] = $assignee;
            $view->assertIndicatorInputsAfterAction($browser, $order, $indicator_id, $this->survey_inputs[$indicator_id], 'submit');
            $view->clickAccept($browser, $indicator_id);
            $view->clickNext($browser, $indicator_id);
            // Indicator 3 - request changes
            $order = 3;
            $indicator_id = $this->indicator_third;
            $deadline = $datatable_data[$order]['deadline'];
            $changes = $this->indicator_state[$indicator_id]['request_requested_changes'];
            $assignee = $datatable_data[$order]['assignee'];
            $this->survey_inputs[$indicator_id]['assignee'] = $assignee;
            $view->assertIndicatorInputsAfterAction($browser, $order, $indicator_id, $this->survey_inputs[$indicator_id], 'submit');
            $view->clickRequestChanges($browser, $indicator_id, [
                'deadline' => $deadline,
                'changes' => $changes
            ]);
            $view->clickSaveRequestChanges($browser, $indicator_id, [
                'deadline' => $deadline,
                'changes' => $changes
            ]);
            $view->clickReviewAndSubmit($browser);
            $view->assertReviewAndSubmitModal($browser, [
                'sections' =>
                [
                    '@survey_submit_complete_section' =>
                    [
                        'message' => 'You can now submit the survey to the PoC for review!'
                    ]
                ],
                'submit' => 'enabled'
            ]);
            $view->clickReviewAndSubmitButton($browser, View::SURVEY_REVIEW_AND_SUBMIT_MODAL_CLOSE);
            $view->clickBreadcrumb($browser, '@survey_dashboard_management', 'Survey Dashboard - ' . $this->questionnaire->title);
            $dashboard_management->assertPath($browser);
            $dashboard_data = [
                'progress' => '33',
                'edit_selected_indicators' => 'disabled',
                'submit_requested_changes' => 'enabled'
            ];
            $order = 1;
            $datatable_data[$order] = [
                'select' => 'enabled',
                'name' => $order . '. ' . $this->indicators[0],
                'assignee' => $user->name,
                'status' => 'In progress',
                'deadline' => $this->deadline,
                'edit' => 'enabled',
                'review' => 'enabled'
            ];
            $order = 2;
            $datatable_data[$order] = [
                'select' => 'disabled',
                'name' => $order . '. ' . $this->indicators[1],
                'assignee' => $datatable_data[$order]['assignee'],
                'status' => 'Approved',
                'deadline' => $datatable_data[$order]['deadline'],
                'edit' => 'disabled',
                'review' => 'enabled'
            ];
            $order = 3;
            $datatable_data[$order] = [
                'select' => 'enabled',
                'name' => $order . '. ' . $this->indicators[2],
                'assignee' => $datatable_data[$order]['assignee'],
                'status' => 'Under review',
                'deadline' => $datatable_data[$order]['deadline'],
                'edit' => 'enabled',
                'review' => 'enabled'
            ];
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
            $dashboard_management->clickSubmitRequestedChanges($browser);
            $dashboard_data['submit_requested_changes'] = 'disabled';
            $datatable_data[$order]['status'] = 'Assigned';
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
        });
    }

    public function test_operator_provides_requested_changes(): void
    {
        $this->browse(function (Browser $browser) {
            $datatable_data = [];

            // Pre-conditions
            $this->assignIndicatorsToPoC($browser);                              // PoC
            $this->assignIndicators($browser);                                   // PoC
            $this->submitIndicators($browser, $datatable_data);                  // Operator
            $this->acceptAndRequestChangesIndicators($browser, $datatable_data); // PoC

            $user = $browser->loginAsRole('operator')->user;
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Pending');
            $management->clickFillInOnline($browser, $this->questionnaire_country->id);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user, $browser->loginAsRole('poc', false, false)->user);
            $view->assert($browser, 'Resume');
            $view->startOrResume($browser);
            // Indicator 2
            $order = 2;
            $indicator_id = $this->indicator_second;
            $assignee = $datatable_data[$order]['assignee'];
            $this->survey_inputs[$indicator_id]['assignee'] = $assignee;
            $view->assertIndicatorInputsAfterAction($browser, $order, $indicator_id, $this->survey_inputs[$indicator_id], 'submit');
            $view->assertIndicatorState($browser, $indicator_id, [
                'state' => $view::APPROVED
            ]);
            $view->clickNext($browser, $indicator_id);
            // Indicator 3
            $order = 3;
            $indicator_id = $this->indicator_third;
            $assignee = $datatable_data[$order]['assignee'];
            $this->survey_inputs[$indicator_id]['assignee'] = $assignee;
            $view->assertIndicatorInputsAfterAction($browser, $order, $indicator_id, $this->survey_inputs[$indicator_id], 'save');
            $view->assertIndicatorState($browser, $indicator_id, [
                'state' => $view::REQUESTED_CHANGES,
                'deadline' => $datatable_data[$order]['deadline'],
                'changes' => $this->indicator_state[$indicator_id]['request_requested_changes']
            ]);
            $indicator_inputs = [
                'assignee' => $assignee,
                'last_saved' => true,
                'questions' => [
                    1 =>
                    [
                        'reference' => $this->indicator_state[$indicator_id]['provide_requested_changes']
                    ]
                ],
                'status' => 'complete'
            ];
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickSave($browser, $indicator_id);
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $this->indicators[$order - 1], $indicator_inputs);
            $view->clickReviewAndSubmit($browser);
            $view->assertReviewAndSubmitModal($browser, [
                'sections' =>
                [
                    '@survey_submit_complete_section' =>
                    [
                        'message' => 'You can now submit the survey to the PoC for review!'
                    ]
                ],
                'submit' => 'enabled'
            ]);
            $view->clickReviewAndSubmitButton($browser, View::SURVEY_REVIEW_AND_SUBMIT_MODAL_SUBMIT);
            $management->assert($browser);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Completed');
        });
    }

    public function test_poc_submits_survey(): void
    {
        $this->browse(function (Browser $browser) {
            $dashboard_data = [
                'progress' => '33',
                'edit_selected_indicators' => 'disabled',
                'submit_requested_changes' => 'disabled'
            ];
            $datatable_data = [];

            // Pre-conditions
            $this->assignIndicatorsToPoC($browser);                              // PoC
            $this->assignIndicators($browser);                                   // PoC
            $this->submitIndicators($browser, $datatable_data);                  // Operator
            $this->fillInIndicator($browser, $datatable_data);                   // PoC
            $this->acceptAndRequestChangesIndicators($browser, $datatable_data); // PoC
            $this->provideRequestedChangesIndicator($browser, $datatable_data);  // Operator

            $user = $browser->loginAsRole('poc')->user;
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
            $dashboard_management->clickReviewIndicator($browser, 3);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user);
            $view->assertPath($browser);
            // Indicator 3 - accept
            $order = 3;
            $indicator_id = $this->indicator_third;
            $changes = $this->indicator_state[$indicator_id]['request_requested_changes'];
            $assignee = $datatable_data[$order]['assignee'];
            $this->survey_inputs[$indicator_id]['assignee'] = $assignee;
            $view->assertIndicatorInputsAfterAction($browser, $order, $indicator_id, $this->survey_inputs[$indicator_id], 'submit');
            $view->assertIndicatorState($browser, $indicator_id, [
                'state' => $view::REQUESTED_CHANGES,
                'deadline' => $datatable_data[$order]['deadline'],
                'changes' => $changes
            ]);
            $view->clickAccept($browser, $indicator_id, [
                'changes' => $changes
            ]);
            $view->clickReviewAndSubmit($browser);
            $view->assertReviewAndSubmitModal($browser, [
                'sections' =>
                [
                    '@survey_submit_complete_section' =>
                    [
                        'message' => 'You can now submit the survey to the PoC for review!'
                    ]
                ],
                'submit' => 'enabled'
            ]);
            $view->clickReviewAndSubmitButton($browser, View::SURVEY_REVIEW_AND_SUBMIT_MODAL_SUBMIT);
            $management->assert($browser);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Completed');
            $management->clickDashboard($browser, $this->questionnaire_country->id);
            $datatable_data[1]['status'] = 'Completed';
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
            $dashboard_data['progress'] = '67';
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
        });
    }
}
