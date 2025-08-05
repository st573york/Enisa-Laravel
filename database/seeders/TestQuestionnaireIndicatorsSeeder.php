<?php

namespace Database\Seeders;

use App\Models\Indicator;
use App\Models\Questionnaire;
use App\Models\QuestionnaireIndicator;
use Illuminate\Database\Seeder;

class TestQuestionnaireIndicatorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $years = (getenv('YEARS_SEEDER') !== false) ? preg_split('/ |, |,/', env('YEARS_SEEDER')) : config('constants.LAST_2_YEARS');
        
        foreach($years as $year)
        {
            $questionnaire_data = Questionnaire::getExistingPublishedQuestionnaireForYear($year);
            $indicators = Indicator::where('category', 'survey')->where('year', $questionnaire_data->year)->orderBy('identifier')->get();
        
            foreach ($indicators as $indicator) {
                QuestionnaireIndicator::create([
                    'questionnaire_id' => $questionnaire_data->id,
                    'indicator_id' => $indicator->id
                ]);
            }
        }
    }
}
