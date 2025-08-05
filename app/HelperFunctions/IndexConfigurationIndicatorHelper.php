<?php

namespace App\HelperFunctions;

use App\Models\Indicator;
use App\Models\IndicatorAccordion;
use App\Models\IndicatorAccordionQuestion;
use App\Models\IndicatorAccordionQuestionOption;
use App\Models\SurveyIndicator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;

class IndexConfigurationIndicatorHelper
{
    public static function validateInputsForCreate($inputs)
    {
        return validator($inputs,
        [
            'indicator_link' => 'required',
            'category' => 'required',
            'default_subarea_id' => [Rule::when(
                (isset($inputs['category']) && $inputs['category'] != 'eu-wide'),
                'required'
            )],
            'name' => ['required', Rule::unique('indicators')->where('year', $_COOKIE['index-year'])],
            'default_weight' => 'required|numeric|regex:/^0(\.\d{1,20})?$/'
        ],
        [
            'indicator_link.required' => 'The link to past indicator field is required.',
            'category.required' => 'The indicator type field is required.',
            'default_subarea_id.required' => 'The subarea field is required.',
            'default_weight.regex' => 'The weight field must be between 0 and 0.99999999999999999999'
        ]);
    }

    public static function validateInputsForEdit($inputs)
    {
        return validator($inputs,
        [
            'indicator_link' => 'required',
            'category' => 'required',
            'default_subarea_id' => [Rule::when(
                (isset($inputs['category']) && $inputs['category'] != 'eu-wide'),
                'required'
            )],
            'name' => ['required', Rule::unique('indicators')->where('year', $_COOKIE['index-year'])->ignore($inputs['id'])],
            'default_weight' => 'required|numeric|regex:/^0(\.\d{1,20})?$/'
        ],
        [
            'indicator_link.required' => 'The link to past indicator field is required.',
            'category.required' => 'The indicator type field is required.',
            'default_subarea_id.required' => 'The subarea field is required.',
            'default_weight.regex' => 'The weight field must be between 0 and 0.99999999999999999999'
        ]);
    }

    public static function getIndicatorData($inputs, $indicator = null)
    {
        foreach ($inputs as $key => $value) {
            $inputs[$key] = $value;
        }

        if ((is_null($indicator) ||
             $indicator->category != 'survey') &&
            $inputs['category'] == 'survey')
        {
            $max_order = Indicator::where('year', $inputs['year'])->max('order');

            $inputs['order'] = $max_order + 1;
            $inputs['validated'] = false;
        }

        $inputs['algorithm'] = urldecode($inputs['algorithm']);

        unset($inputs['indicator_link'], $inputs['max_identifier']);

        return $inputs;
    }

    public static function storeIndicator($inputs)
    {
        $data = self::getIndicatorData($inputs);
        $data['short_name'] = substr($data['name'], 0, 20);

        if ($inputs['indicator_link'] != 'new_indicator') {
            $identifier = Indicator::find($inputs['indicator_link'])->identifier;
        }
        else {
            $identifier = $inputs['max_identifier'];
        }

        $data['identifier'] = $identifier;

        Indicator::create($data);
    }

    public static function updateIndicator($inputs)
    {
        $indicator = Indicator::getIndicator($inputs['id']);

        $data = self::getIndicatorData($inputs, $indicator);

        if ($data['category'] != 'survey')
        {
            $data['order'] = null;
            $data['validated'] = null;
        }
        if ($data['category'] == 'eu-wide') {
            $data['default_subarea_id'] = null;
        }
        
        $indicator->update($data);
    }

    public static function deleteIndicator($id)
    {
        $indicator = Indicator::getIndicator($id);

        // Delete all survey data for this indicator
        SurveyIndicator::where('indicator_id', $indicator->id)->delete();

        // Delete all survey configuration for this indicator
        $db_accordions = IndicatorAccordion::where('indicator_id', $indicator->id)->pluck('id')->toArray();
        IndicatorAccordion::whereIn('id', $db_accordions)->delete();
        
        return $indicator->delete();
    }

    public static function updateOrder($data)
    {
        foreach ($data as $row)
        {
            $parts = explode('row_', $row['id']);

            Indicator::find($parts[1])->update(['order' => $row['order']]);
        }
    }

    public static function reIndexOrder($year)
    {
        $indicators = Indicator::getIndicators($year, 'survey');

        $order = 1;

        foreach ($indicators as $indicator)
        {
            Indicator::where('id', $indicator->id)->update(['order' => $order]);

            $order++;
        }
    }

    public static function cloneSurveyConfiguration($year_from)
    {
        DB::beginTransaction();
        
        try
        {
            $year_to = $_COOKIE['index-year'];
            
            // Indicators
            $indicators = Indicator::getIndicators($year_from);
            foreach ($indicators as $indicator)
            {
                $replicate_indicator = Indicator::where('identifier', $indicator->identifier)->where('year', $year_to)->first();

                IndexConfigurationIndicatorHelper::cloneSurveyIndicator($indicator, $replicate_indicator);
            }
        }
        catch (Exception $e)
        {
            Log::debug($e->getMessage());

            DB::rollback();

            return [
                'type' => 'error',
                'status' => 500,
                'msg' => $e->getMessage()
            ];
        }

        DB::commit();

        return [
            'type' => 'success',
            'status' => 200,
            'msg' => 'Survey configuration have been successfully cloned!'
        ];
    }

    public static function cloneSurveyIndicator($indicator, $replicate_indicator)
    {
        $accordions = $indicator->accordions()->orderBy('order')->get();

        foreach ($accordions as $accordion)
        {
            $replicate_accordion = $accordion->replicate();
            $replicate_accordion->indicator_id = $replicate_indicator->id;

            IndicatorAccordion::disableAuditing();
            $replicate_accordion->save();
            IndicatorAccordion::enableAuditing();
            
            $questions = $accordion->questions()->orderBy('order')->get();

            foreach ($questions as $question)
            {
                $replicate_question = $question->replicate();
                $replicate_question->accordion_id = $replicate_accordion->id;

                IndicatorAccordionQuestion::disableAuditing();
                $replicate_question->save();
                IndicatorAccordionQuestion::enableAuditing();

                $options = $question->options()->get();

                foreach ($options as $option)
                {
                    $replicate_option = $option->replicate();
                    $replicate_option->question_id = $replicate_question->id;

                    IndicatorAccordionQuestionOption::disableAuditing();
                    $replicate_option->save();
                    IndicatorAccordionQuestionOption::enableAuditing();
                }
            }
        }
    }
}