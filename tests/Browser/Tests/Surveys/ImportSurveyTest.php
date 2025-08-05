<?php

namespace Tests\Browser\Survey;

use App\HelperFunctions\GeneralHelper;
use App\HelperFunctions\QuestionnaireCountryHelper;
use App\HelperFunctions\QuestionnaireHelper;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use App\Models\Indicator;
use App\Models\SurveyIndicator;
use Carbon\Carbon;
use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\Survey\Management;
use Tests\Browser\Pages\Survey\View;
use Tests\Browser\Components\Button;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ImportSurveyTest extends DuskTestCase
{
    use DatabaseTransactions;

    const FILENAME = 'QuestionnaireTemplate';
    const EXTENSION = '.xlsx';

    protected $questionnaire;
    protected $year;
    protected $questionnaire_country;
    protected $deadline;
    protected $indicators;
    protected $type;
    protected $filename_to_read;
    protected $filename_to_download;
    protected $file;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed --class=TestQuestionnaireUsersSeeder');

        $this->questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $this->year = $this->questionnaire->year;
        $this->questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $this->questionnaire->id)->first();
        $this->deadline = GeneralHelper::dateFormat($this->questionnaire->deadline, 'd-m-Y');
        $this->indicators = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(3)->pluck('identifier')->toArray();
        $this->type = 'survey_template';
        $this->filename_to_read = self::FILENAME . self::EXTENSION;
        $this->filename_to_download = self::FILENAME . $this->year . self::EXTENSION;
        $this->file = storage_path() . '/app/' . $this->filename_to_download;
        
        QuestionnaireHelper::createQuestionnaireTemplate($this->questionnaire->year);
    }

    public function assignIndicatorsToPoC($browser)
    {
        $indicators = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(3)->get();

        $user = $browser->loginAsRole('poc', false)->user;
        
        foreach ($indicators as $indicator)
        {
            $survey_indicator = SurveyIndicator::getSurveyIndicator($this->questionnaire_country, $indicator);
            
            $survey_indicator->assignee = $user->id;
            $survey_indicator->deadline = $this->questionnaire->deadline;
            $survey_indicator->save();
        }
    }

    public function test_indicators_not_assigned(): void
    {
        $this->browse(function (Browser $browser) {
            // Pre-conditions
            $this->assignIndicatorsToPoC($browser); // PoC

            $user = $browser->loginAsRole('poc')->user;

            QuestionnaireCountryHelper::downloadExcel($user, $this->questionnaire->year, $this->indicators, $this->type, $this->filename_to_read, $this->filename_to_download);
            $this->assertFileExists($this->file);

            // Assign indicators to operator
            $indicators = Indicator::skip(1)->where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(2)->get();

            $user = $browser->loginAsRole('operator', false, false)->user;

            $skipIndicatorNames = [];

            $browser->loginAsRole('poc', false);
            foreach ($indicators as $indicator)
            {
                $survey_indicator = SurveyIndicator::getSurveyIndicator($this->questionnaire_country, $indicator);
            
                $survey_indicator->assignee = $user->id;
                $survey_indicator->deadline = Carbon::parse($this->questionnaire->deadline)->subDay()->format('Y-m-d');
                $survey_indicator->save();

                array_push($skipIndicatorNames, $indicator->order . '. ' . $indicator->name);
            }

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->clickFillInOffline($browser, $this->questionnaire_country->id);
            $management->assertFillInOfflineModal($browser);
            $management->clickImportTemplate($browser, $this->file);
            $management->assertImportTemplateConflictModal($browser, 'The following indicators are no longer assigned to you and their values have been skipped:', $skipIndicatorNames);

            File::delete($this->file);
        });
    }

    public function test_indicators_assigned()
    {
        $this->browse(function (Browser $browser) {
            // Pre-conditions
            $this->assignIndicatorsToPoC($browser); // PoC

            $user = $browser->loginAsRole('poc')->user;

            QuestionnaireCountryHelper::downloadExcel($user, $this->questionnaire->year, $this->indicators, $this->type, $this->filename_to_read, $this->filename_to_download);
            $this->assertFileExists($this->file);

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->clickFillInOffline($browser, $this->questionnaire_country->id);
            $management->assertFillInOfflineModal($browser);
            $management->clickImportTemplate($browser, $this->file);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user);
            $view->assert($browser, 'Resume');

            File::delete($this->file);
        });
    }

    public function test_indicators_conflict()
    {
        $this->browse(function (Browser $browser) {
            // Pre-conditions
            $this->assignIndicatorsToPoC($browser); // PoC
            
            $indicators = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->limit(3)->pluck('name')->toArray();

            $user = $browser->loginAsRole('poc')->user;

            QuestionnaireCountryHelper::downloadExcel($user, $this->questionnaire->year, $this->indicators, $this->type, $this->filename_to_read, $this->filename_to_download);
            $this->assertFileExists($this->file);

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
            $indicator = Indicator::where('category', 'survey')->where('year', $this->year)->orderBy('identifier')->first();
            $indicator_inputs = [
                'assignee' => $user->name,
                'questions' => [
                    1 =>
                    [
                        'choice' => '1',
                        'answers' => [
                            '1'
                        ],
                        'reference' => ''
                    ]
                ]
            ];
            $view->fillInOnlineIndicator($browser, $order, $indicator->id, $indicator_inputs);
            $view->clickSave($browser, $indicator->id);
            $browser->on(new Button('@surveys'))
                    ->scrollAndClickButton('top');
            $management->clickFillInOffline($browser, $this->questionnaire_country->id);
            $management->assertFillInOfflineModal($browser);
            $management->clickImportTemplate($browser, $this->file);
            $management->assertImportTemplateConflictModal($browser, "The following indicators have already been answered.\nBy clicking Continue, their values will be overwritten.", [$indicator->order . '. ' . $indicator->name]);
            $management->clickContinue($browser);
            $view = new View($this->questionnaire_country->id, $this->questionnaire->title, $this->indicators, $user);
            $view->assert($browser, 'Resume');

            File::delete($this->file);
        });
    }
}
