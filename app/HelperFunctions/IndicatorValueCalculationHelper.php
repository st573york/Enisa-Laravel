<?php

namespace App\HelperFunctions;

class IndicatorValueCalculationHelper
{
    public static function getQuestionMaxScore($question, $options)
    {
        $max_score = 0;

        if ($question->type()->first()->type == 'single-choice') {
            $max_score = max($options);
        }
        elseif ($question->type()->first()->type == 'multiple-choice') {
            $max_score = array_sum($options);
        }
        
        return $max_score;
    }

    public static function getQuestionScore($survey_indicator_options, $option_value, $option_score)
    {
        if (in_array($option_value, $survey_indicator_options)) {
            return $option_score;
        }

        return 0;
    }
}
