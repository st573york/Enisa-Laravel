<?php

namespace App\Console\Commands;

use App\HelperFunctions\IndicatorValueCalculationHelper;
use App\Models\Index;
use App\Models\IndicatorCalculationVariable;
use App\Models\IndicatorQuestionScore;
use App\Models\QuestionnaireCountry;
use App\Models\SurveyIndicator;
use App\Models\SurveyIndicatorAnswer;
use App\Models\SurveyIndicatorOption;
use Illuminate\Console\Command;

class CalculateIndicatorScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questionnaire:calculate-scores {questionnaire_id} {country_id?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate the indicator scores based on the questionnaire answers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $questionnaireIdArg = $this->argument('questionnaire_id');
        $countryIdArg = $this->argument('country_id');

        $questionnaireCountries = QuestionnaireCountry::where('questionnaire_id', $questionnaireIdArg)
            ->when(!empty($countryIdArg), function ($query) use ($countryIdArg) {
                $query->whereIn('country_id', $countryIdArg);
            })
            ->get();
        
        try
        {
            $bar = $this->output->createProgressBar($questionnaireCountries->count());
            $bar->start();

            foreach ($questionnaireCountries as $questionnaireCountry)
            {
                if (!self::canCalculate($questionnaireCountry)) {
                    continue;
                }

                $survey_indicators = SurveyIndicator::getSurveyIndicators($questionnaireCountry);

                foreach ($survey_indicators as $survey_indicator)
                {
                    $indicator = $survey_indicator->indicator;
                    $accordions = $indicator->accordions()->orderBy('order')->get();

                    foreach ($accordions as $accordion)
                    {
                        $questions = $accordion->questions()->orderBy('order')->get();

                        foreach ($questions as $question)
                        {
                            $options = $question->options()->orderBy('value')->pluck('score', 'value')->toArray();
                            
                            if ($question->type()->first()->type == 'free-text' ||
                                !count($options))
                            {
                                continue;
                            }

                            $questionIdentifier = $indicator->identifier . '-' . $accordion->order . '-' . $question->order;

                            $survey_indicator_answer = SurveyIndicatorAnswer::getSurveyIndicatorAnswer($survey_indicator, $question);
                            $survey_indicator_options = SurveyIndicatorOption::getSurveyIndicatorOptions($survey_indicator, $question);
                            
                            $data_not_available = ($survey_indicator_answer->choice_id == 3) ? true : false;
                            
                            $score = 0;
                            foreach ($options as $option_value => $option_score) {
                                $score += IndicatorValueCalculationHelper::getQuestionScore($survey_indicator_options, $option_value, $option_score);
                            }
                            
                            IndicatorQuestionScore::updateOrCreate(
                                [
                                    'country_id' => $questionnaireCountry->country_id,
                                    'indicator_id' => $indicator->id,
                                    'question_id' => $questionIdentifier
                                ],
                                [
                                    'score' => ($data_not_available) ? -1 : $score,
                                    'data_not_available' => $data_not_available
                                ]
                            );
                        }
                    }
                }

                $bar->advance();
            }

            $bar->finish();
            $this->line('');

            $bar = $this->output->createProgressBar($questionnaireCountries->count());
            $bar->start();
            
            foreach ($questionnaireCountries as $questionnaireCountry)
            {
                if (!self::canCalculate($questionnaireCountry)) {
                    continue;
                }
                
                $survey_indicators = SurveyIndicator::getSurveyIndicators($questionnaireCountry);

                foreach ($survey_indicators as $survey_indicator)
                {
                    $indicator = $survey_indicator->indicator;
                    $accordions = $indicator->accordions()->orderBy('order')->get();

                    foreach ($accordions as $accordion)
                    {
                        $questions = $accordion->questions()->orderBy('order')->get();

                        foreach ($questions as $question)
                        {
                            $options = $question->options()->orderBy('value')->pluck('score', 'value')->toArray();
                            
                            if ($question->type()->first()->type == 'free-text' ||
                                !count($options))
                            {
                                continue;
                            }

                            $questionIdentifier = $indicator->identifier . '-' . $accordion->order . '-' . $question->order;

                            $calculationValues = IndicatorQuestionScore::where('country_id', $questionnaireCountry->country_id)
                                ->where('indicator_id', $indicator->id)
                                ->where('question_id', $questionIdentifier)->first();
                            
                            $questionsScore = $calculationValues['score'];
                            if ($calculationValues->data_not_available)
                            {
                                // Countries that have answered the question
                                $scores = IndicatorQuestionScore::where('indicator_id', $indicator->id)
                                    ->where('question_id', $questionIdentifier)
                                    ->where('score', '>', '-1')->orderBy('score')->pluck('score')->toArray();
                                
                                if (count($scores))
                                {
                                    // Average score
                                    $avgScore = array_sum($scores) / count($scores);
                                    // Closest score
                                    $questionsScore = self::getClosestLowerScore(array_unique($scores), $avgScore);
                                }

                                IndicatorCalculationVariable::updateOrCreate(
                                    [
                                        'indicator_id' => $indicator->id,
                                        'question_id' => $questionIdentifier
                                    ],
                                    [
                                        'neutral_score' => $questionsScore
                                    ]
                                );
                            }

                            IndicatorQuestionScore::updateOrCreate(
                                [
                                    'country_id' => $questionnaireCountry->country_id,
                                    'indicator_id' => $indicator->id,
                                    'question_id' => $questionIdentifier
                                ],
                                [
                                    'score' => $questionsScore
                                ]
                            );
                        }
                    }
                }

                $bar->advance();
            }

            $bar->finish();
            $this->line('');
        }
        catch (\Exception $e)
        {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($questionnaireCountries->count()) {
            $this->info('The indicator scores have been successfully calculated.');
        }
        else {
            $this->line('No records found for given arguments!');
        }

        return Command::SUCCESS;
    }

    private static function canCalculate($questionnaireCountry)
    {
        $approvedCountry = Index::where('country_id', $questionnaireCountry->country_id)->where('status_id', 3)->where('index_configuration_id', $questionnaireCountry->questionnaire->configuration->id)->first();

        if (!is_null($approvedCountry)) {
            return false;
        }

        if (is_null($questionnaireCountry->approved_by)) {
            return false;
        }

        return true;
    }

    private static function getClosestLowerScore($scores, $avgScore)
    {
        $closest = null;

        foreach ($scores as $score)
        {
            if ($score > $avgScore) {
                break;
            }

            $closest = $score;
        }

        return $closest;
    }

    // private static function getClosestScore($scores, $search)
    // {
    //     $closest = null;

    //     foreach ($scores as $score)
    //     {
    //         if (is_null($closest) || abs($search - $closest) >= abs($score - $search)) {
    //             $closest = $score;
    //         }
    //     }

    //     return $closest;
    // }
}
