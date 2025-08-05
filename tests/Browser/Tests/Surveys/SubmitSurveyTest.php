<?php

namespace Tests\Browser\Survey;

use App\HelperFunctions\GeneralHelper;
use App\HelperFunctions\QuestionnaireCountryHelper;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use App\Models\Indicator;
use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\Survey\DashboardManagement;
use Tests\Browser\Pages\Survey\Management;
use Tests\Browser\Pages\Survey\View;
use Tests\Browser\Components\Button;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SubmitSurveyTest extends DuskTestCase
{
    use DatabaseTransactions;

    protected $questionnaire;
    protected $year;
    protected $questionnaire_country;
    protected $deadline;
    protected $indicator_first;
    protected $indicator_second;
    protected $indicator_third;
    protected $survey_inputs;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed --class=TestQuestionnaireUsersSeeder');
        
        $this->questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $this->year = $this->questionnaire->year;
        $this->questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $this->questionnaire->id)->first();
        $this->deadline = GeneralHelper::dateFormat($this->questionnaire->deadline, 'd-m-Y');
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
                            '1'
                        ],
                        'reference' => ''
                    ],
                    2 =>
                    [
                        'choice' => '-2',
                        'answers' => [],
                        'reference' => ''
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
                        'validation' => [
                            'answers' => [
                                'required' => true
                            ],
                            'reference' => [
                                'required' => true
                            ]
                        ],
                        'choice' => '',
                        'answers' => [
                            '2'
                        ],
                        'reference' => Str::random(5)
                    ],
                    2 =>
                    [
                        'validation' => [
                            'answers' => [
                                'required' => true
                            ],
                            'reference' => [
                                'required' => true
                            ]
                        ],
                        'choice' => '',
                        'answers' => [
                            '2'
                        ],
                        'reference' => Str::random(5)
                    ]
                ],
                'validation' => [
                    'rating' => [
                        'required' => true
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
                            '3',
                            '4',
                            '5'
                        ],
                        'reference' => Str::random(5),
                        'accordion' => true
                    ],
                    2 =>
                    [
                        'accordion' => true,
                        'choice' => '',
                        'answers' => [
                            '3'
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

    public function test_submit_complete(): void
    {
        $this->browse(function (Browser $browser) {
            // Pre-conditions
            $this->assignIndicatorsToPoC($browser); // PoC
            
            $indicators = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(3)->pluck('name')->toArray();

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
            $management->clickFillInOnline($browser, $this->questionnaire_country->id);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $indicators, $user);
            $view->assert($browser);
            $view->startOrResume($browser);
            // Indicator 1
            $order = 1;
            $indicator_id = $this->indicator_first;
            $indicator_inputs = [
                'assignee' => $user->name,
                'last_saved' => false,
                'questions' => $this->survey_inputs[$indicator_id]['questions'],
                'comments' => $this->survey_inputs[$indicator_id]['comments'],
                'rating' => $this->survey_inputs[$indicator_id]['rating'],
                'status' => 'incomplete'
            ];
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickSave($browser, $indicator_id);
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $indicators[$order - 1], $indicator_inputs);
            $view->clickNext($browser, $indicator_id);
            // Indicator 2
            $order = 2;
            $indicator_id = $this->indicator_second;
            $indicator_inputs['status'] = 'incomplete';
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $indicators[$order - 1], $indicator_inputs);
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
                'status' => 'complete'
            ];
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickSave($browser, $indicator_id);
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $indicators[$order - 1], $indicator_inputs);
            $view->clickReviewAndSubmit($browser);
            $view->assertReviewAndSubmitModal($browser, [
                'sections' =>
                [
                    '@survey_submit_incomplete_section' =>
                    [
                        'message' => 'Incomplete sections',
                        'indicators' => [0, 1]
                    ]
                ],
                'submit' => 'disabled'
            ]);
            // Indicator 2
            $order = 2;
            $indicator_id = $this->indicator_second;
            $indicator_inputs = [
                'assignee' => $user->name,
                'last_saved' => false,
                'questions' => $this->survey_inputs[$indicator_id]['questions'],
                'validation' => $this->survey_inputs[$indicator_id]['validation'],
                'comments' => $this->survey_inputs[$indicator_id]['comments'],
                'rating' => $this->survey_inputs[$indicator_id]['rating'],
                'status' => 'complete'
            ];
            $view->clickReviewAndSubmitGoTo($browser, '@survey_submit_incomplete_section', '@survey_submit_incomplete_indicator_' . $this->indicator_second);
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickSave($browser, $indicator_id);
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $indicators[$order - 1], $indicator_inputs);
            $view->clickPrevious($browser, $indicator_id);
            // Indicator 1
            $order = 1;
            $indicator_id = $this->indicator_first;
            $reference = $this->survey_inputs[$indicator_id]['questions'][1]['reference'] = Str::random(5);
            $indicator_inputs = [
                'assignee' => $user->name,
                'last_saved' => true,
                'questions' => [
                    1 =>
                    [
                        'reference' => $reference
                    ]
                ],
                'status' => 'complete'
            ];
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickSave($browser, $indicator_id);
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $indicators[$order - 1], $indicator_inputs);
            $view->clickSurveyNavigation($browser);
            $view->clickSurveyNavigationGoTo($browser, $this->indicator_third);
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
            for ($i = 1; $i <= 3; $i++) {
                $datatable_data[$i] = [
                    'select' => 'enabled',
                    'name' => $i. '. ' . $indicators[$i - 1],
                    'assignee' => $user->name,
                    'status' => 'Completed',
                    'deadline' => $this->deadline,
                    'edit' => 'enabled',
                    'review' => 'enabled'
                ];
            }
            $dashboard_management = new DashboardManagement($this->questionnaire_country->id, $this->questionnaire->title, $user);
            $dashboard_management->assertDataTable($browser, $dashboard_data, $datatable_data);
        });
    }
}
