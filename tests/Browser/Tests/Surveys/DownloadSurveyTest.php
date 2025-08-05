<?php

namespace Tests\Browser\Survey;

use App\HelperFunctions\QuestionnaireHelper;
use App\Models\Indicator;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use App\Models\SurveyIndicator;
use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\Survey\Management;
use Tests\Browser\Components\Button;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DownloadSurveyTest extends DuskTestCase
{
    use DatabaseTransactions;

    protected $questionnaire;
    protected $year;
    protected $questionnaire_country;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed --class=TestQuestionnaireUsersSeeder');

        $this->questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
        $this->year = $this->questionnaire->year;
        $this->questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $this->questionnaire->id)->first();
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

    public function test_poc_download()
    {
        $this->browse(function (Browser $browser) {
            // Pre-conditions
            $this->assignIndicatorsToPoC($browser); // PoC

            $questionnaire = Questionnaire::getLatestPublishedQuestionnaire();
            $questionnaire_country = QuestionnaireCountry::where('questionnaire_id', $questionnaire->id)->first();
            
            QuestionnaireHelper::createQuestionnaireTemplate($questionnaire->year);

            $user = $browser->loginAsRole('poc')->user;

            $browser->visit('/')
                    ->on(new Login)
                    ->on(new Button('@surveys'))
                    ->clickButton();
            $management = new Management($user);
            $management->clickFillInOffline($browser, $questionnaire_country->id);
            $management->assertFillInOfflineModal($browser);
            $management->clickDownloadTemplate($browser);
        });
    }
}
