<?php

namespace Database\Seeders;

use App\Models\Indicator;
use App\Models\IndicatorAccordion;
use App\Models\Questionnaire;
use App\Models\QuestionnaireIndicator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Seeder;

class DuskQuestionnaireIndicatorsSeeder extends Seeder
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
            $indicators = Indicator::where('category', 'survey')->where('year', $questionnaire_data->year)->orderBy('identifier')->limit(3)->get();

            $order = 0;
            foreach ($indicators as $indicator)
            {
                // Delete all survey configuration for this indicator
                $db_accordions = IndicatorAccordion::where('indicator_id', $indicator->id)->pluck('id')->toArray();
                IndicatorAccordion::whereIn('id', $db_accordions)->delete();
                
                $configuration_json_file = storage_path() . '/app/test_files/dusk-survey-indicator-' . ++$order . '.json';
                
                if (file_exists($configuration_json_file))
                {
                    $indicator->configuration_json = json_decode(file_get_contents($configuration_json_file), true);
                    $indicator->save();
                }

                QuestionnaireIndicator::create([
                    'questionnaire_id' => $questionnaire_data->id,
                    'indicator_id' => $indicator->id
                ]);
            }

            Artisan::call('db:seed --class=MigrateJsonDataToDatabaseTables');
        }
    }
}
