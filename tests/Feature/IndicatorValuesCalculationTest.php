<?php

namespace Tests\Feature;

use App\Models\QuestionnaireCountry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorValuesCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_commands()
    {
        $questionnaireCountry = QuestionnaireCountry::first();
        $questionnaireCountry->approved_by = 1;
        $questionnaireCountry->save();

        $this->artisan('questionnaire:calculate-scores', ['questionnaire_id' => $questionnaireCountry->id])
             ->expectsOutput('The indicator scores have been successfully calculated.')
             ->assertExitCode(0);

        $this->artisan('questionnaire:calculate-values', ['questionnaire_id' => $questionnaireCountry->id])
             ->expectsOutput('The indicator values have been successfully calculated.')
             ->assertExitCode(0);
    }
}
