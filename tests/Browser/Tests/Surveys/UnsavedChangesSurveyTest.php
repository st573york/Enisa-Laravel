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

class UnsavedChangesSurveyTest extends DuskTestCase
{
    use DatabaseTransactions;

    protected $questionnaire;
    protected $year;
    protected $questionnaire_country;
    protected $deadline;
    protected $indicator_first;
    protected $indicator_second;
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

        $this->survey_inputs = [
            $this->indicator_first => [
                'questions' => [
                    1 =>
                    [
                        'choice' => '',
                        'answers' => [
                            '1'
                        ],
                        'reference' => Str::random(5)
                    ],
                    2 =>
                    [
                        'choice' => '',
                        'answers' => [
                            '1'
                        ],
                        'reference' => Str::random(5)
                    ]
                ],
                'comments' => Str::random(5),
                'rating' => 5,
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

    public function test_unsaved_changes(): void
    {
        $this->browse(function (Browser $browser) {
            // Pre-conditions
            $this->assignIndicatorsToPoC($browser); // PoC

            $indicators = Indicator::where('category', 'survey')->where('year', $this->questionnaire->year)->orderBy('identifier')->limit(3)->pluck('name')->toArray();

            $user = $browser->loginAsRole('poc')->user;
            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->clickFillInOnline($browser, $this->questionnaire_country->id);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $indicators, $user);
            $view->startOrResume($browser);
            // Indicator 1
            $order = 1;
            $indicator_id = $this->indicator_first;
            $this->survey_inputs[$indicator_id]['assignee'] = $user->name;
            $indicator_inputs = [
                'assignee' => $user->name,
                'last_saved' => false,
                'questions' => $this->survey_inputs[$indicator_id]['questions'],
                'comments' => $this->survey_inputs[$indicator_id]['comments'],
                'rating' => $this->survey_inputs[$indicator_id]['rating']
            ];
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickNext($browser, $indicator_id);
            $view->assertUnsavedChangesModal($browser);
            $view->clickSaveUnsavedChanges($browser);
            // Indicator 2
            $view->clickPrevious($browser, $this->indicator_second);
            // Indicator 1
            $order = 1;
            $indicator_id = $this->indicator_first;
            $view->assertIndicatorInputsAfterAction($browser, $order, $indicator_id, $this->survey_inputs[$indicator_id], 'save');
            $indicator_inputs['status'] = 'complete';
            $view->clickAndAssertSurveyNavigation($browser, $indicator_id, $indicators[$order - 1], $indicator_inputs);
            $indicator_inputs = [
                'assignee' => $user->name,
                'last_saved' => true,
                'questions' => [
                    1 =>
                    [
                        'answers' => [
                            '1'
                        ]
                    ],
                    2 =>
                    [
                        'reference' => Str::random(5)
                    ]
                ]
            ];
            $view->fillInOnlineIndicator($browser, $order, $indicator_id, $indicator_inputs);
            $view->clickNext($browser, $indicator_id);
            $view->assertUnsavedChangesModal($browser);
            $view->clickDiscardUnsavedChanges($browser);
            // Indicator 2
            $view->clickPrevious($browser, $this->indicator_second);
            // Indicator 1
            $order = 1;
            $indicator_id = $this->indicator_first;
            $view->assertIndicatorInputsAfterAction($browser, $order, $indicator_id, $this->survey_inputs[$indicator_id], 'save');
        });
    }
}
