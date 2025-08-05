<?php

namespace Database\Seeders;

use App\HelperFunctions\GeneralHelper;
use App\Models\Indicator;
use App\Models\IndicatorAccordion;
use App\Models\IndicatorAccordionQuestion;
use App\Models\IndicatorAccordionQuestionOption;
use App\Models\IndicatorQuestionChoice;
use App\Models\IndicatorQuestionType;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCountry;
use App\Models\SurveyIndicator;
use App\Models\SurveyIndicatorAnswer;
use App\Models\SurveyIndicatorOption;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MigrateJsonDataToDatabaseTables extends Seeder
{
    protected $starting_time;
    protected $finished_time;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $years = config('constants.LAST_2_YEARS');

        $this->starting_time = microtime(true);

        // Indicators
        foreach($years as $year)
        {
            $indicators = Indicator::whereNotNull('configuration_json')->where('category', 'survey')->where('year', $year)->orderBy('identifier')->get();

            $this->command->getOutput()->write('Migrating indicators json data for year ' . $year . '...', true);
            $this->command->getOutput()->progressStart($indicators->count());

            foreach ($indicators as $indicator)
            {
                $this->command->getOutput()->progressAdvance();

                foreach ($indicator->configuration_json['form'] as $accordions)
                {
                    $db_accordion = IndicatorAccordion::updateOrCreateIndicatorAccordion(
                        [
                            'indicator_id' => $indicator->id,
                            'title' => (!empty($accordions['title'])) ? $accordions['title'] : 'Questions',
                            'order' => $accordions['order']
                        ]
                    );

                    foreach ($accordions['contents'] as $question)
                    {
                        $db_question = IndicatorAccordionQuestion::updateOrCreateIndicatorAccordionQuestion(
                            [
                                'accordion_id' => $db_accordion->id,
                                'title' => $question['question'],
                                'order' => $question['order'],
                                'type_id' => IndicatorQuestionType::where('type', $question['type'])->value('id'),
                                'info' => $question['info'],
                                'compatible' => (isset($question['compatible'])) ? $question['compatible'] : true,
                                'answers_required' => $question['validation']['answers']['required'],
                                'reference_required' => $question['validation']['reference']['required']
                            ]
                        );

                        if (!isset($question['options'])) {
                            continue;
                        }

                        foreach ($question['options'] as $option) {
                            IndicatorAccordionQuestionOption::updateOrCreateIndicatorAccordionQuestionOption(
                                [
                                    'question_id' => $db_question->id,
                                    'text' => $option['text'],
                                    'master' => (isset($option['type']) && $option['type'] == 'master') ? true : false,
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

        // Questionnaire Countries
        foreach($years as $year)
        {
            $questionnaire = Questionnaire::getExistingPublishedQuestionnaireForYear($year);
            $questionnaire_countries = QuestionnaireCountry::where('questionnaire_id', $questionnaire->id)->get();

            $this->command->getOutput()->write('Migrating questionnaire_countries json data for year ' . $year . '...', true);
            $this->command->getOutput()->progressStart($questionnaire_countries->count());

            foreach ($questionnaire_countries as $questionnaire_country)
            {
                $this->command->getOutput()->progressAdvance();

                $questionnaire_country->default_assignee = $questionnaire_country->json_data['default_assignee'];
                $questionnaire_country->save();

                foreach ($questionnaire_country->json_data['contents'] as $indicator)
                {
                    if (!preg_match('/form-indicator-/', $indicator['type'])) {
                        continue;
                    }

                    $last_saved = (isset($indicator['last_saved'])) ?
                        Carbon::parse($indicator['last_saved'])->format('Y-m-d H:i:s') :
                        Carbon::parse($indicator['deadline'] . date('H:i:s'))->format('Y-m-d H:i:s');
                    
                    $db_survey_indicator = SurveyIndicator::updateOrCreateSurveyIndicator(
                        [
                            'questionnaire_country_id' => $questionnaire_country->id,
                            'indicator_id' => $indicator['id'],
                            'assignee' => $indicator['assignee'],
                            'state_id' => $indicator['state'],
                            'dashboard_state_id' => $indicator['dashboard_state'],
                            'rating' => $indicator['rating'],
                            'comments' => $indicator['comments'],
                            'deadline' => GeneralHelper::dateFormat($indicator['deadline'], 'Y-m-d'),
                            'last_saved' => $last_saved,
                            'approved_by' => $indicator['approved']['author']['id']
                        ]
                    );
                    
                    foreach ($indicator['form'] as $accordion)
                    {
                        $db_accordion = IndicatorAccordion::getIndicatorAccordion($db_survey_indicator->indicator, $accordion['order']);

                        foreach ($accordion['contents'] as $question)
                        {
                            $db_question = IndicatorAccordionQuestion::getIndicatorAccordionQuestion($db_accordion, $question['order']);
                            
                            foreach ($question['choice'] as $choice)
                            {
                                if (isset($choice['selected']) &&
                                    $choice['selected'])
                                {
                                    break;
                                }
                            }
                            
                            SurveyIndicatorAnswer::updateOrCreateSurveyIndicatorAnswer(
                                [
                                    'survey_indicator_id' => $db_survey_indicator->id,
                                    'question_id' => $db_question->id,
                                    'choice_id' => IndicatorQuestionChoice::where('text', $choice['text'])->value('id'),
                                    'free_text' => ($question['type'] == 'free-text' && !empty($question['answers'])) ? $question['answers'][0] : null,
                                    'reference' => $question['reference'],
                                    'last_saved' => $last_saved
                                ]
                            );

                            if ($question['type'] == 'single-choice' ||
                                $question['type'] == 'multiple-choice')
                            {
                                foreach ($question['options'] as $option)
                                {
                                    if (isset($option['selected']) &&
                                        $option['selected'])
                                    {
                                        $option_id = IndicatorAccordionQuestionOption::where('question_id', $db_question->id)->where('value', $option['value'])->value('id');
                                        
                                        SurveyIndicatorOption::updateOrCreateSurveyIndicatorOption(
                                            [
                                                'survey_indicator_id' => $db_survey_indicator->id,
                                                'option_id' => $option_id,
                                                'last_saved' => $last_saved
                                            ]
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->command->getOutput()->progressFinish();
        }

        $this->finished_time = microtime(true);

        $this->command->getOutput()->info('Migration time: ' . gmdate('H:i:s', $this->finished_time - $this->starting_time));
    }
}
