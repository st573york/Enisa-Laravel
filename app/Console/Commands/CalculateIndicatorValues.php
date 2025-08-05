<?php

namespace App\Console\Commands;

use App\HelperFunctions\IndicatorValueCalculationHelper;
use App\Models\Index;
use App\Models\IndicatorCalculationVariable;
use App\Models\QuestionnaireCountry;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\SurveyIndicator;
use App\Models\SurveyIndicatorAnswer;
use App\Models\SurveyIndicatorOption;
use Illuminate\Console\Command;

class CalculateIndicatorValues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questionnaire:calculate-values {questionnaire_id} {country_id?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate the indicator values based on the questionnaire answers and the indicator calculation variables for given questionnaire and countries';

    /**
     * Execute the console command.
     *
     * @return int
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

                    $indicatorScore = 0;

                    foreach ($accordions as $accordion)
                    {
                        $questions = $accordion->questions()->orderBy('order')->get();

                        $questionsScore = 0;
                        $questionsDivider = 0;
                        
                        foreach ($questions as $question)
                        {
                            $options = $question->options()->orderBy('value')->pluck('score', 'value')->toArray();
                            
                            if ($question->type()->first()->type == 'free-text' ||
                                !count($options))
                            {
                                continue;
                            }

                            $questionIdentifier = $indicator->identifier . '-' . $accordion->order . '-' . $question->order;
                            $indicatorQuestionVariable = IndicatorCalculationVariable::where('indicator_id', $indicator->id)->where('question_id', $questionIdentifier)->first();
                            
                            $survey_indicator_answer = SurveyIndicatorAnswer::getSurveyIndicatorAnswer($survey_indicator, $question);
                            $survey_indicator_options = SurveyIndicatorOption::getSurveyIndicatorOptions($survey_indicator, $question);

                            // Data not available/Not willing to share
                            if ($survey_indicator_answer->choice_id == 3) {
                                $score = $indicatorQuestionVariable->neutral_score;
                            }
                            else
                            {
                                $score = 0;
                                foreach ($options as $option_value => $option_score) {
                                    $score += IndicatorValueCalculationHelper::getQuestionScore($survey_indicator_options, $option_value, $option_score);
                                }
                            }

                            $maxScore = IndicatorValueCalculationHelper::getQuestionMaxScore($question, $options);
                            
                            if ($indicatorQuestionVariable->normalize)
                            {
                                $questionsScore += $score / $maxScore;
                                $questionsDivider = $questions->count();
                            }
                            else
                            {
                                $questionsScore += $score;
                                $questionsDivider += $maxScore;
                            }
                        }

                        if ($questionsDivider > 0) {
                            $indicatorScore += $questionsScore / $questionsDivider;
                        }
                    }

                    if ($indicatorQuestionVariable->predefined_divider > 0) {
                        $indicatorScore = $indicatorScore / $indicatorQuestionVariable->predefined_divider;
                    }

                    IndicatorValue::updateOrCreateIndicatorValue([
                        'indicator_id' => Indicator::where('identifier', $indicator->identifier)->where('year', $questionnaireCountry->questionnaire->year)->value('id'),
                        'country_id' => $questionnaireCountry->country_id,
                        'year' => $questionnaireCountry->questionnaire->year,
                        'value' => $indicatorScore * 100
                    ]);
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
            $this->info('The indicator values have been successfully calculated.');
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
}
