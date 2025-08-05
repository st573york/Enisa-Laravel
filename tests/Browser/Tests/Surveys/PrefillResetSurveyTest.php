<?php

namespace Tests\Browser\Survey;

use App\HelperFunctions\GeneralHelper;
use App\HelperFunctions\QuestionnaireCountryHelper;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use App\Models\Indicator;
use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\Survey\Management;
use Tests\Browser\Pages\Survey\View;
use Tests\Browser\Components\Button;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PrefillResetSurveyTest extends DuskTestCase
{
    use DatabaseTransactions;

    protected $questionnaire;
    protected $year;
    protected $questionnaire_country;
    protected $deadline;
    protected $last_year;
    protected $last_year_questionnaire;
    protected $last_year_questionnaire_country;
    protected $last_year_deadline;
    protected $indicators;
    protected $indicator_first;
    protected $indicator_second;
    protected $indicator_third;
    protected $last_year_indicator_first;
    protected $last_year_indicator_second;
    protected $last_year_indicator_third;
    protected $survey_inputs;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed --class=TestQuestionnaireUsersSeeder');

        $this->questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $this->year = $this->questionnaire->year;
        $this->questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $this->questionnaire->id)->first();
        $this->deadline = GeneralHelper::dateFormat($this->questionnaire->deadline, 'd-m-Y');
        $this->last_year = $this->year - 1;
        $this->last_year_questionnaire = Questionnaire::getExistingPublishedQuestionnaireForYear($this->last_year);
        $this->last_year_questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $this->last_year_questionnaire->id)->first();
        $this->last_year_deadline = GeneralHelper::dateFormat($this->last_year_questionnaire_country->deadline, 'd-m-Y');
        $this->indicators = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(3)->pluck('name')->toArray();
        $this->indicator_first = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->first();
        $this->indicator_second = Indicator::skip(1)->where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->first();
        $this->indicator_third = Indicator::skip(2)->where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->first();
        $this->last_year_indicator_first = Indicator::where('category', 'survey')->where('year', $this->last_year)->orderBy('identifier')->first();
        $this->last_year_indicator_second = Indicator::skip(1)->where('category', 'survey')->where('year', $this->last_year)->orderBy('identifier')->first();
        $this->last_year_indicator_third = Indicator::skip(2)->where('category', 'survey')->where('year', $this->last_year)->orderBy('identifier')->first();

        $indicator_first_inputs = [
            'questions' => [
                1 =>
                [
                    'compatible' => true,
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
                    'compatible' => true,
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
        ];
        $indicator_second_inputs =  [
            'questions' => [
                1 =>
                [
                    'compatible' => true,
                    'choice' => '',
                    'answers' => [
                        '5'
                    ],
                    'reference' => Str::random(5)
                ],
                2 =>
                [
                    'compatible' => true,
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
        ];
        $indicator_third_inputs = [
            'questions' => [
                1 =>
                [
                    'compatible' => true,
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
                    'compatible' => true,
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
        ];

        $this->survey_inputs = [
            $this->indicator_first->id => $indicator_first_inputs,
            $this->indicator_second->id => $indicator_second_inputs,
            $this->indicator_third->id => $indicator_third_inputs,
            $this->last_year_indicator_first->id => $indicator_first_inputs,
            $this->last_year_indicator_second->id => $indicator_second_inputs,
            $this->last_year_indicator_third->id => $indicator_third_inputs
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

    public function submitIndicatorsLastYear($browser)
    {
        $json_data = $this->last_year_questionnaire_country->json_data;
        $indicators = Indicator::where('category', 'survey')->where('year', $this->last_year)->orderBy('identifier')->limit(3)->get();

        $user = $browser->loginAsRole('poc', false)->user;
        foreach ($indicators as $indicator)
        {
            QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->last_year_questionnaire_country, $json_data, collect([$indicator]), [
                'action' => 'edit',
                'assignee' => $user->id,
                'deadline' => $this->last_year_deadline
            ]);

            QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->last_year_questionnaire_country, $json_data, collect([$indicator]), [
                'indicator_answers' => $this->getIndicatorInputsForSave($json_data, $indicator),
                'action' => 'save'
            ]);
        }
        QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorsData($this->last_year_questionnaire_country, $json_data, $indicators, [
            'action' => 'submit'
        ]);

        $this->last_year_questionnaire_country->submitted_by = $user->id;
        $this->last_year_questionnaire_country->save();
    }

    public function test_prefill_answers_compatible(): void
    {
        $this->browse(function (Browser $browser) {
            $load_reset_suffix = $this->last_year . ' Answers';

            // Pre-conditions
            $this->submitIndicatorsLastYear($browser); // PoC
            $this->assignIndicatorsToPoC($browser);    // PoC

            $user = $browser->loginAsRole('poc')->user;
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $management->assertDataTable($browser, $this->last_year_questionnaire_country->id, 'Completed', $user->name);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Pending');
            $management->clickFillInOnline($browser, $this->questionnaire_country->id);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user);
            $view->assert($browser);
            $view->startOrResume($browser);
            $order = 1;
            $indicator_id = $this->indicator_first->id;
            [, , $last_year_indicator_data] = QuestionnaireCountryHelper::getLastYearQuestionnaireCountryData($this->questionnaire_country, $this->indicator_first);
            $this->survey_inputs[$indicator_id]['assignee'] = $user->name;
            $this->survey_inputs[$indicator_id]['last_saved'] = GeneralHelper::dateFormat($last_year_indicator_data['last_saved'], 'd-m-Y');
            $this->survey_inputs[$indicator_id]['status'] = 'complete';
            $view->loadResetAnswers($browser, $indicator_id, 'Pre-fill ' . $load_reset_suffix, 'Reset ' . $load_reset_suffix);
            $view->assertIndicatorInputsAfterAction($browser, $order, $indicator_id, $this->survey_inputs[$indicator_id], 'pre-fill');
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $this->indicators[$order - 1], $this->survey_inputs[$indicator_id]);
            $browser->assertVisible('@survey_indicator_last_saved_' . $indicator_id);
        });
    }

    public function test_prefill_answers_incompatible(): void
    {
        $this->browse(function (Browser $browser) {
            $load_reset_suffix = $this->last_year . ' Answers';

            // Pre-conditions
            $this->submitIndicatorsLastYear($browser); // PoC
            $this->assignIndicatorsToPoC($browser);    // PoC
            $indicator_data = QuestionnaireCountryHelper::getQuestionnaireCountryIndicatorData($this->questionnaire_country, $this->indicator_first);
            $indicator_data['form'][0]['contents'][0]['compatible'] = false;
            QuestionnaireCountryHelper::updateQuestionnaireCountryIndicatorData($this->questionnaire_country, $this->indicator_first, $indicator_data);

            $user = $browser->loginAsRole('poc')->user;
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $management->assertDataTable($browser, $this->last_year_questionnaire_country->id, 'Completed', $user->name);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Pending');
            $management->clickFillInOnline($browser, $this->questionnaire_country->id);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user);
            $view->assert($browser);
            $view->startOrResume($browser);
            $order = 1;
            $indicator_id = $this->indicator_first->id;
            [, , $last_year_indicator_data] = QuestionnaireCountryHelper::getLastYearQuestionnaireCountryData($this->questionnaire_country, $this->indicator_first);
            $this->survey_inputs[$indicator_id]['assignee'] = $user->name;
            $this->survey_inputs[$indicator_id]['last_saved'] = GeneralHelper::dateFormat($last_year_indicator_data['last_saved'], 'd-m-Y');
            $this->survey_inputs[$indicator_id]['questions'][1] = [
                'compatible' => false,
                'choice' => '',
                'answers' => [],
                'reference' => ''
            ];
            $this->survey_inputs[$indicator_id]['status'] = 'incomplete';
            $view->loadResetAnswers($browser, $indicator_id, 'Pre-fill ' . $load_reset_suffix, 'Reset ' . $load_reset_suffix);
            $view->assertIndicatorInputsAfterAction($browser, $order, $indicator_id, $this->survey_inputs[$indicator_id], 'pre-fill');
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $this->indicators[$order - 1], $this->survey_inputs[$indicator_id]);
            $browser->assertVisible('@survey_indicator_last_saved_' . $indicator_id);
        });
    }

    public function test_fill_in_prefilled_answers_compatible(): void
    {
        $this->browse(function (Browser $browser) {
            // Pre-conditions
            $this->submitIndicatorsLastYear($browser); // PoC
            $this->assignIndicatorsToPoC($browser);    // PoC
            $indicator_data = QuestionnaireCountryHelper::getQuestionnaireCountryIndicatorData($this->questionnaire_country, $this->indicator_first);
            QuestionnaireCountryHelper::loadQuestionnaireCountryIndicatorData($this->questionnaire_country, $this->indicator_first, $indicator_data);

            $user = $browser->loginAsRole('poc')->user;
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $management->assertDataTable($browser, $this->last_year_questionnaire_country->id, 'Completed', $user->name);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Pending');
            $management->clickFillInOnline($browser, $this->questionnaire_country->id);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user);
            $view->assert($browser);
            $view->startOrResume($browser);
            $order = 1;
            $indicator_id = $this->indicator_first->id;
            $assignee = $user->name;
            $this->survey_inputs[$indicator_id]['questions'][1]['answers'] = ['1'];
            $this->survey_inputs[$indicator_id]['questions'][2]['reference'] = Str::random(5);
            $this->survey_inputs[$indicator_id]['comments'] = Str::random(10);
            $this->survey_inputs[$indicator_id]['rating'] = 1;
            $indicator_inputs = [
                'assignee' => $assignee,
                'last_saved' => true,
                'questions' => [
                    1 =>
                    [
                        'answers' => $this->survey_inputs[$indicator_id]['questions'][1]['answers']
                    ],
                    2 =>
                    [
                        'reference' => $this->survey_inputs[$indicator_id]['questions'][2]['reference']
                    ]
                ],
                'comments' => $this->survey_inputs[$indicator_id]['comments'],
                'rating' => $this->survey_inputs[$indicator_id]['rating']
            ];
            $this->survey_inputs[$indicator_id]['assignee'] = $assignee;
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickSave($browser, $indicator_id);
            $view->assertIndicatorInputsAfterAction($browser, $order, $indicator_id, $this->survey_inputs[$indicator_id], 'save');
        });
    }

    public function test_reset_answers_compatible(): void
    {
        $this->browse(function (Browser $browser) {
            $load_reset_suffix = $this->last_year . ' Answers';

            // Pre-conditions
            $this->submitIndicatorsLastYear($browser); // PoC
            $this->assignIndicatorsToPoC($browser);    // PoC
            $indicator_data = QuestionnaireCountryHelper::getQuestionnaireCountryIndicatorData($this->questionnaire_country, $this->indicator_first);
            QuestionnaireCountryHelper::loadQuestionnaireCountryIndicatorData($this->questionnaire_country, $this->indicator_first, $indicator_data);

            $user = $browser->loginAsRole('poc')->user;
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->assert($browser);
            $management->assertDataTable($browser, $this->last_year_questionnaire_country->id, 'Completed', $user->name);
            $management->assertDataTable($browser, $this->questionnaire_country->id, 'Pending');
            $management->clickFillInOnline($browser, $this->questionnaire_country->id);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user);
            $view->assert($browser);
            $view->startOrResume($browser);
            $order = 1;
            $indicator_id = $this->indicator_first->id;
            $indicator_inputs = [
                'assignee' => $user->name,
                'questions' => [
                    1 =>
                    [
                        'choice' => '',
                        'answers' => [],
                        'reference' => ''
                    ],
                    2 =>
                    [
                        'choice' => '',
                        'answers' => [],
                        'reference' => ''
                    ]
                ],
                'comments' => '',
                'rating' => 0,
                'status' => 'incomplete'
            ];
            $this->survey_inputs[$indicator_id]['status'] = 'incomplete';
            $view->loadResetAnswers($browser, $indicator_id, 'Reset ' . $load_reset_suffix, 'Pre-fill ' . $load_reset_suffix);
            $view->assertIndicatorInputsAfterAction($browser, $order, $indicator_id, $indicator_inputs, 'reset');
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $this->indicators[$order - 1], $indicator_inputs);
            $browser->assertMissing('@survey_indicator_last_saved_' . $indicator_id);
        });
    }
}
