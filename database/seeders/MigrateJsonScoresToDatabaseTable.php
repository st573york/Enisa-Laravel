<?php

namespace Database\Seeders;

use App\Models\Indicator;
use App\Models\IndicatorAccordion;
use App\Models\IndicatorAccordionQuestion;
use App\Models\IndicatorAccordionQuestionOption;
use Illuminate\Database\Seeder;

class MigrateJsonScoresToDatabaseTable extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $years = config('constants.YEARS_TO_DATE');

        // Indicators
        foreach($years as $year)
        {
            $indicators = Indicator::whereNotNull('configuration_json')->where('category', 'survey')->where('year', $year)->orderBy('identifier')->get();

            $this->command->getOutput()->write('Migrating json scores for year ' . $year . '...', true);
            $this->command->getOutput()->progressStart($indicators->count());

            foreach ($indicators as $indicator)
            {
                $this->command->getOutput()->progressAdvance();

                foreach ($indicator->configuration_json['form'] as $accordions)
                {
                    $db_accordion = IndicatorAccordion::getIndicatorAccordion($indicator, $accordions['order']);

                    foreach ($accordions['contents'] as $question)
                    {
                        $db_question = IndicatorAccordionQuestion::getIndicatorAccordionQuestion($db_accordion, $question['order']);

                        if (!isset($question['options'])) {
                            continue;
                        }

                        foreach ($question['options'] as $option) {
                            IndicatorAccordionQuestionOption::updateOrCreateIndicatorAccordionQuestionOption(
                                [
                                    'question_id' => $db_question->id,
                                    'score' => $option['score'],
                                    'value' => $option['value']
                                ]
                            );
                        }
                    }
                }
            }

            $this->command->getOutput()->progressFinish();
        }
    }
}
