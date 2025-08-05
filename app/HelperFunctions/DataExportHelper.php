<?php

namespace App\HelperFunctions;

use App\Models\BaselineIndex;
use App\Models\Country;
use App\Models\EurostatIndicatorVariable;
use App\Models\Index;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\QuestionnaireCountry;
use App\Models\SurveyIndicator;
use App\Models\SurveyIndicatorOption;

class DataExportHelper
{
    const EXPORT_DATA_NAME = 'EUCSI';

    public static function getCountryCodes($indicatorValues, $sources = [])
    {
        $countryCodes = (count($sources) > 1) ? ['EU'] : [];

        foreach ($indicatorValues as $indicatorValue) {
            array_push($countryCodes, $indicatorValue->iso);
        }

        return array_unique($countryCodes);
    }

    public static function getIndexData($countries, $year)
    {
        return Index::select(
            'indices.id',
            'indices.country_id',
            'indices.index_configuration_id',
            'indices.name',
            'indices.description',
            'indices.json_data'
        )
            ->leftJoin('index_configurations', 'index_configurations.id', '=', 'indices.index_configuration_id')
            ->whereIn('country_id', $countries)
            ->where('index_configurations.year', $year)
            ->orderBy('indices.country_id')
            ->get();
    }

    public static function getBaselineData($year)
    {
        return BaselineIndex::select(
            'baseline_indices.id',
            'baseline_indices.index_configuration_id',
            'baseline_indices.name',
            'baseline_indices.description',
            'baseline_indices.json_data'
        )
            ->leftJoin('index_configurations', 'index_configurations.id', '=', 'baseline_indices.index_configuration_id')
            ->where('index_configurations.year', $year)
            ->first();
    }

    public static function getIndicatorValues($countries, $year, $categories, $sources = [])
    {
        return IndicatorValue::select(
            'indicators.identifier AS id',
            'indicators.source',
            'indicators.name',
            'indicators.report_year',
            'subareas.name AS default_subarea',
            'areas.name AS default_area',
            'indicator_values.value',
            'countries.code',
            'countries.iso'
        )
            ->leftJoin('indicators', 'indicators.id', '=', 'indicator_values.indicator_id')
            ->leftJoin('subareas', 'subareas.id', '=', 'indicators.default_subarea_id')
            ->leftJoin('areas', 'areas.id', '=', 'subareas.default_area_id')
            ->leftJoin('countries', 'countries.id', '=', 'indicator_values.country_id')
            ->where('indicator_values.year', $year)
            ->whereIn('indicator_values.country_id', $countries)
            ->whereIn('indicators.category', $categories)
            ->when(!empty($sources), function ($query) use ($sources) {
                $query->whereIn('indicators.source', $sources);
            })
            ->orderBy('indicators.identifier')
            ->orderBy('indicator_values.country_id')
            ->get();
    }

    public static function getSurveyIndicatorRawData($countries, $year)
    {
        return QuestionnaireCountry::select(
            'questionnaire_countries.id',
            'questionnaire_countries.country_id',
            'countries.iso'
        )
            ->leftJoin('questionnaires', 'questionnaires.id', '=', 'questionnaire_countries.questionnaire_id')
            ->leftJoin('countries', 'countries.id', '=', 'questionnaire_countries.country_id')
            ->where('questionnaires.year', $year)
            ->whereIn('questionnaire_countries.country_id', $countries)
            ->orderBy('countries.id')
            ->get();
    }

    public static function getEurostatIndicatorRawData($countries, $year)
    {
        return EurostatIndicatorVariable::select(
            'indicators.identifier AS id',
            'indicators.name',
            'indicators.report_year',
            'eurostat_indicator_variables.variable_identifier',
            'eurostat_indicator_variables.variable_code',
            'eurostat_indicator_variables.variable_name',
            'eurostat_indicator_variables.variable_value',
            'subareas.name AS default_subarea',
            'areas.name AS default_area',
            'countries.code',
            'countries.iso'
        )
            ->leftJoin('eurostat_indicators', 'eurostat_indicators.id', '=', 'eurostat_indicator_variables.eurostat_indicator_id')
            ->leftJoin('countries', 'countries.id', '=', 'eurostat_indicator_variables.country_id')
            ->leftJoin('indicators', 'indicators.identifier', '=', 'eurostat_indicators.identifier')
            ->leftJoin('subareas', 'subareas.id', '=', 'indicators.default_subarea_id')
            ->leftJoin('areas', 'areas.id', '=', 'subareas.default_area_id')
            ->where('indicators.year', $year)
            ->whereIn('eurostat_indicator_variables.country_id', $countries)
            ->orderBy('indicators.identifier')
            ->orderBy('eurostat_indicator_variables.country_id')
            ->orderBy('eurostat_indicator_variables.variable_code')
            ->get();
    }

    public static function getIndicatorData($indicator)
    {
        $indicatorData = [
            'number' => $indicator->order,
            'identifier' => $indicator->identifier,
            'name' => $indicator->name,
            'questions' => []
        ];

        $accordions = $indicator->accordions()->orderBy('order')->get();

        foreach ($accordions as $accordion)
        {
            $questions = $accordion->questions()->orderBy('order')->get();

            foreach ($questions as $question)
            {
                $questionId = $indicator->identifier . '-' . $accordion->order . '-' . $question->order;
                $questionData = &$indicatorData['questions'][$questionId];
                $questionData = [
                    'id' => $question->id,
                    'section' => $accordion->title,
                    'type' => $question->type()->first()->type,
                    'name' => $question->title,
                    'compatible' => ($question->compatible) ? 'Yes' : 'No',
                    'required' => ($question->answers_required && $question->reference_required) ? 'Yes' : 'No',
                    'info' => $question->info
                ];

                $options = $question->options()->orderBy('value')->get();

                if ($options->count())
                {
                    $questionData['options'] = [];
                    $optionData = &$questionData['options'];

                    foreach ($options as $option) {
                        $optionData[$option->value] = [
                            'id' => $option->id,
                            'name' => $option->text,
                            'master' => ($option->master) ? 'Yes' : 'No',
                            'score' => $option->score
                        ];
                    }
                }
            }
        }

        return $indicatorData;
    }

    public static function getQuestionnaireData($questionnaire, $questionnaireData)
    {
        $survey_indicators = SurveyIndicator::getSurveyIndicators($questionnaire);

        foreach ($survey_indicators as $survey_indicator)
        {
            $indicator = $survey_indicator->indicator;
            $accordions = $indicator->accordions()->orderBy('order')->get();

            $identifier = $indicator->identifier;

            $questionnaireData[$identifier]['id'] = $identifier;
            $questionnaireData[$identifier]['name'] = $indicator->name;

            if (!isset($questionnaireData[$identifier]['questions'])) {
                $questionnaireData[$identifier]['questions'] = [];
            }

            foreach ($accordions as $accordion)
            {
                $questions = $accordion->questions()->orderBy('order')->get();

                foreach ($questions as $question)
                {
                    $questionId = $identifier . '-' . $accordion->order . '-' . $question->order;
                    $questionData = &$questionnaireData[$identifier]['questions'][$questionId];
                    $questionData['section'] = $accordion->title;
                    $questionData['type'] = $question->type()->first()->type;
                    $questionData['name'] = $question->title;
                    
                    if (!isset($questionData['options'])) {
                        $questionData['options'] = [];
                    }
                    
                    $options = $question->options()->orderBy('value')->get();
                    $survey_indicator_options = SurveyIndicatorOption::where('survey_indicator_id', $survey_indicator->id)->pluck('option_id')->toArray();

                    if ($options->count())
                    {
                        $optionData = &$questionData['options'];

                        foreach ($options as $option)
                        {
                            $optionData[$option->value]['name'] = $option->text;
                            $optionData[$option->value]['score'] = $option->score;

                            if (!isset($optionData[$option->value]['selected'])) {
                                $optionData[$option->value]['selected'] = [];
                            }

                            if (in_array($option->id, $survey_indicator_options)) {
                                array_push($optionData[$option->value]['selected'], $questionnaire->country->iso);
                            }
                        }
                    }
                }
            }
        }

        return $questionnaireData;
    }

    public static function prepareIndexOverviewData($countries, $year)
    {
        $data = [];
        $countriesData = self::getIndexData($countries, $year);
        $indiceRows = [
            'index' => []
        ];
        $countryCodes = [];
        $baseLineData = self::getBaseLineData($year);
        if (!is_null($baseLineData)) {
            $data['baseline'] = $baseLineData->json_data;
        }

        foreach ($countriesData as $countryData) {
            $data[$countryData->country_id] = $countryData->json_data;
        }

        foreach ($data as $countryId => $countryData)
        {
            if ($countryId == 'baseline') {
                $country = new Country(
                    [
                        'name' => 'European Average',
                        'code' => 'EU',
                        'iso' => 'EU'
                    ]
                );
            }
            else {
                $country = Country::find($countryId);
            }

            if (!isset($countryData['contents'])) {
                continue;
            }

            $valueField = $country->name . ' ' . $year;
            array_push($countryCodes, $country->iso);
            $contents = $countryData['contents'];

            $indiceRows['index'][$country->iso] = $contents[0]['global_index_values'][0][$valueField];

            foreach ($contents as $key => $content)
            {
                if ($key === 0) {
                    continue;
                }

                $area = $content['area'];
                $areaName = $area['name'];
                $indiceRows['areas'][$areaName]['weight'] = (isset($area['normalized_weight'])) ? $area['normalized_weight'] : 'N/A';
                $indiceRows['areas'][$areaName][$country->iso] = $area['values'][0][$valueField];

                foreach ($area['subareas'] as $subarea)
                {
                    $subareaName = $subarea['name'];
                    $indiceRows['subareas'][$subareaName]['weight'] = (isset($subarea['normalized_weight'])) ? $subarea['normalized_weight'] : 'N/A';
                    $indiceRows['subareas'][$subareaName]['area'] = $areaName;
                    $indiceRows['subareas'][$subareaName][$country->iso] = $subarea['values'][0][$valueField];

                    foreach ($subarea['indicators'] as $indicator)
                    {
                        if (!isset($indicator['values'])) {
                            continue;
                        }

                        $indiceRows['indicators'][$indicator['name']]['weight'] = (isset($indicator['normalized_weight'])) ? $indicator['normalized_weight'] : 'N/A';
                        $indiceRows['indicators'][$indicator['name']][$country->iso] = $indicator['values'][0][$valueField];
                        $indiceRows['indicators'][$indicator['name']]['subarea'] = $subareaName;
                        $indiceRows['indicators'][$indicator['name']]['area'] = $areaName;
                    }
                }
            }
        }

        return [
            'countryCodes' => $countryCodes,
            'indiceRows' => $indiceRows
        ];
    }

    public static function prepareIndicatorValuesData($countries, $year, $sources, $data)
    {
        $indiceRows = $data['indiceRows'];

        $indicatorValues = self::getIndicatorValues($countries, $year, $sources);
        $countryCodes = self::getCountryCodes($indicatorValues, $sources);
        
        $indicatorValuesData = [];
        foreach ($indicatorValues as $indicatorValue)
        {
            $identifier = $indicatorValue->id;

            $indicatorValuesData[$identifier]['name'] = $indicatorValue->name;
            $indicatorValuesData[$identifier]['area'] = $indicatorValue->default_area ?? 'N/A';
            $indicatorValuesData[$identifier]['subarea'] = $indicatorValue->default_subarea ?? 'N/A';
            $indicatorValuesData[$identifier]['source'] = $indicatorValue->source;
            $indicatorValuesData[$identifier]['year'] = $indicatorValue->report_year;
            if (count($sources) > 1) {
                $indicatorValuesData[$identifier]['EU'] = $indiceRows['indicators'][$indicatorValue->name]['EU'];
            }
            $indicatorValuesData[$identifier][$indicatorValue->iso] = $indicatorValue->value;
        }

        return [
            'countryCodes' => $countryCodes,
            'indicatorValuesData' => $indicatorValuesData
        ];
    }

    public static function prepareEUWideIndicatorValuesData($countries, $year, $sources)
    {
        $indicatorValues = self::getIndicatorValues($countries, $year, $sources);

        $indicatorValuesData = [];
        foreach ($indicatorValues as $indicatorValue)
        {
            $identifier = $indicatorValue->id;

            $indicatorValuesData[$identifier]['name'] = $indicatorValue->name;
            $indicatorValuesData[$identifier]['source'] = $indicatorValue->source;
            $indicatorValuesData[$identifier]['year'] = $indicatorValue->report_year;
            $indicatorValuesData[$identifier]['value'] = $indicatorValue->value;
        }
        
        return [
            'indicatorValuesData' => $indicatorValuesData
        ];
    }

    public static function prepareSurveyIndicatorRawData($countries, $year)
    {
        $questionnaires = self::getSurveyIndicatorRawData($countries, $year);

        $indicatorValues = [];
        $countryCodes = [];
        foreach ($questionnaires as $questionnaire)
        {
            $indicatorValues = self::getQuestionnaireData($questionnaire, $indicatorValues);
            array_push($countryCodes, $questionnaire->iso);
        }

        $indicatorValuesData = [];
        foreach ($indicatorValues as $indicatorValue)
        {
            $identifier = $indicatorValue['id'];

            $indicator = Indicator::where('identifier', $identifier)->where('year', $year)->first();

            $indicatorValuesData[$identifier]['name'] = $indicator->name;
            $indicatorValuesData[$identifier]['area'] = $indicator->default_subarea->default_area->name ?? 'N/A';
            $indicatorValuesData[$identifier]['subarea'] = $indicator->default_subarea->name ?? 'N/A';
            $indicatorValuesData[$identifier]['questions'] = $indicatorValue['questions'];
        }

        return [
            'countryCodes' => $countryCodes,
            'indicatorValuesData' => $indicatorValuesData
        ];
    }

    public static function prepareEurostatIndicatorRawData($countries, $year)
    {
        $indicatorValues = self::getEurostatIndicatorRawData($countries, $year);
        $countryCodes = self::getCountryCodes($indicatorValues);

        $indicatorValuesData = [];
        foreach ($indicatorValues as $indicatorValue)
        {
            $identifier = $indicatorValue->id;
            $variable_identifier = $indicatorValue->variable_identifier;

            if (!isset($indicatorValuesData[$variable_identifier])) {
                $indicatorValuesData[$variable_identifier] = [
                    'identifier' => $identifier,
                    'name' => $indicatorValue->name,
                    'variable_name' => $indicatorValue->variable_name,
                    'variable_code' => $indicatorValue->variable_code,
                    'area' => $indicatorValue->default_area ?? 'N/A',
                    'subarea' => $indicatorValue->default_subarea ?? 'N/A',
                    'year' => $indicatorValue->report_year
                ];
            }

            $indicatorValuesData[$variable_identifier][$indicatorValue->iso] = $indicatorValue->variable_value;
        }

        return [
            'countryCodes' => $countryCodes,
            'indicatorValuesData' => $indicatorValuesData
        ];
    }

    public static function prepareShodanIndicatorRawData($countries, $year, $categories, $sources)
    {
        $indicatorValues = self::getIndicatorValues($countries, $year, $categories, $sources);
        $countryCodes = self::getCountryCodes($indicatorValues);

        $indicatorValuesData = [];
        foreach ($indicatorValues as $indicatorValue) {
            $indicatorValuesData[$indicatorValue->id] = [
                'name' => $indicatorValue->name,
                'area' => $indicatorValue->default_area ?? 'N/A',
                'subarea' => $indicatorValue->default_subarea ?? 'N/A',
                'year' => $indicatorValue->report_year,
                $indicatorValue->iso => $indicatorValue->value
            ];
        }
        
        return [
            'countryCodes' => $countryCodes,
            'indicatorValuesData' => $indicatorValuesData
        ];
    }

    public static function createFilename($user, $year, $countries, $sources)
    {
        $countryText = '';
        $sourceText = '';

        if (count($countries) == 1)
        {
            $countryId = (int)$countries[0];
            $country = Country::find($countryId);
            $countryText = $country->iso;
        }

        if (count($sources) == 1) {
            $sourceText = implode('-', $sources) . '-indicators';
        }
        else {
            $sourceText = 'all-data';
        }

        return implode(
            '-',
            array_filter(
                [
                    self::EXPORT_DATA_NAME,
                    $user,
                    $year,
                    $sourceText,
                    $countryText
                ]
            )
        ) . '.xlsx';
    }
}
