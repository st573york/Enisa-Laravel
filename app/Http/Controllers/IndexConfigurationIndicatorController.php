<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\HelperFunctions\IndexConfigurationIndicatorHelper;
use App\Models\Area;
use App\Models\IndexConfiguration;
use App\Models\Indicator;
use App\Models\IndicatorAccordion;
use App\Models\IndicatorAccordionQuestion;
use App\Models\IndicatorAccordionQuestionOption;
use App\Models\IndicatorQuestionType;
use App\Models\Subarea;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class IndexConfigurationIndicatorController extends Controller
{
    const VALIDATION_EXCEPTION_CODE = 400;

    public function list(Request $request)
    {
        $inputs = $request->all();
        
        $year = $_COOKIE['index-year'];

        return response()->json(['data' => Indicator::getIndicators($year, ($inputs['category']))], 200);
    }

    public function createOrShowIndicator($data = null)
    {
        $year = $_COOKIE['index-year'];
        $clone_year = null;

        $current_indicators = Indicator::getIndicators($year);
        $current_indicators_identifiers = array_column($current_indicators->toArray(), 'identifier');
        if ($current_indicators->whereNotNull('clone_year')->count()) {
            $clone_year = $current_indicators->whereNotNull('clone_year')->first()->clone_year;
        }
        if (is_null($clone_year))
        {
            $last_indicator = Indicator::getLastIndicator($year);
            
            $clone_year = $last_indicator->year;
        }
        $clone_from_indicators = Indicator::getIndicators($clone_year);
        $clone_from_indicators_identifiers = array_column($clone_from_indicators->toArray(), 'identifier');
        
        $not_linked_indicators = [];
        foreach ($clone_from_indicators as $clone_from_indicator)
        {
            if (($data && $data->identifier == $clone_from_indicator->identifier) ||
                !in_array($clone_from_indicator->identifier, $current_indicators_identifiers))
            {
                array_push($not_linked_indicators, $clone_from_indicator);
            }
        }

        return view('ajax.index-configuration-indicator-management', [
            'selected_indicator' => $data,
            'max_identifier' => Indicator::max('identifier') + 1,
            'not_linked_indicators' => collect($not_linked_indicators)->sortBy('name'),
            'is_identifier_linked' => ($data && in_array($data->identifier, $clone_from_indicators_identifiers)) ? true : false,
            'subareas' => Subarea::getSubareas($year),
            'categories' => config('constants.DEFAULT_CATEGORIES')
        ]);
    }

    public function storeIndicator(Request $request)
    {
        $inputs = $request->all();
        $inputs['year'] = $_COOKIE['index-year'];

        $validator = IndexConfigurationIndicatorHelper::validateInputsForCreate($inputs);
        if ($validator->fails()) {
            return response()->json($validator->messages(), self::VALIDATION_EXCEPTION_CODE);
        }

        IndexConfigurationIndicatorHelper::storeIndicator($inputs);
        
        IndexConfiguration::updateDraftIndexConfigurationJsonData($inputs['year']);

        return response()->json('ok', 200);
    }

    public function showIndicator(Indicator $indicator)
    {
        $data = Indicator::getIndicator($indicator->id);

        return $this->createOrShowIndicator($data);
    }

    public function updateIndicator(Request $request, Indicator $indicator)
    {
        $inputs = $request->all();
        $inputs['id'] = $indicator->id;
        $inputs['year'] = $_COOKIE['index-year'];

        $validator = IndexConfigurationIndicatorHelper::validateInputsForEdit($inputs);
        if ($validator->fails()) {
            return response()->json($validator->messages(), self::VALIDATION_EXCEPTION_CODE);
        }

        IndexConfigurationIndicatorHelper::updateIndicator($inputs);

        if ($inputs['category'] != 'survey') {
            IndexConfigurationIndicatorHelper::reIndexOrder($inputs['year']);
        }

        IndexConfiguration::updateDraftIndexConfigurationJsonData($_COOKIE['index-year']);

        return response()->json('ok', 200);
    }

    public function deleteIndicator(Indicator $indicator)
    {
        $year = $_COOKIE['index-year'];

        IndexConfigurationIndicatorHelper::deleteIndicator($indicator->id);
        IndexConfigurationIndicatorHelper::reIndexOrder($year);

        IndexConfiguration::updateDraftIndexConfigurationJsonData($year);

        return response()->json('ok', 200);
    }

    public function updateIndicatorsOrder(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        
        Indicator::disableAuditing();
        IndexConfigurationIndicatorHelper::updateOrder($data);
        Indicator::enableAuditing();

        return response()->json('ok', 200);
    }

    public function getIndicatorSurvey(Indicator $indicator)
    {
        $indicator_survey_data = [];

        $accordions = $indicator->accordions()->orderBy('order')->get();

        foreach ($accordions as $accordion)
        {
            array_push($indicator_survey_data, [
                'type' => 'header',
                'subtype' => 'h1',
                'label' => (!empty($accordion->title)) ? htmlspecialchars_decode($accordion->title) : ' '
            ]);

            $questions = $accordion->questions()->orderBy('order')->get();

            foreach ($questions as $question)
            {
                $question_type = '';
                if ($question->type_id == 1) {
                    $question_type = 'radio-group';
                }
                elseif ($question->type_id == 2) {
                    $question_type = 'checkbox-group';
                }
                elseif ($question->type_id == 3) {
                    $question_type = 'text';
                }

                $name = $question_type . '-' . $accordion->order . '-' . $question->order;

                $question_data = [
                    'type' => $question_type,
                    'required' => ($question->answers_required && $question->reference_required) ? 1 : 0,
                    'description' => $question->info,
                    'label' => (!empty($question->title)) ? htmlspecialchars_decode($question->title) : ' ',
                    'name' => $name,
                    'compatible' => ($question->compatible) ? 1 : 0
                ];

                if ($question->type_id == 1 ||
                    $question->type_id == 2)
                {
                    if ($question->type_id == 2)
                    {
                        $question_data['master'] = $name;
                        $question_data['master_options'] = [];
                    }

                    $options = $question->options()->get();

                    $values = [];
                    foreach ($options as $key => $option)
                    {
                        array_push($values, [
                            'label' => htmlspecialchars_decode($option->text),
                            'value' => (string)$option->score
                        ]);

                        if ($question->type_id == 2)
                        {
                            array_push($question_data['master_options'], [
                                'label' => htmlspecialchars_decode($option->text)
                            ]);

                            if ($option->master) {
                                $question_data['master_options'][$key]['selected'] = true;
                            }
                        }
                    }

                    $question_data['values'] = $values;
                }

                array_push($indicator_survey_data, $question_data);
            }
        }

        $indicators_with_survey = Indicator::getIndicatorsWithSurvey($indicator->year);

        $canLoadLastIndicatorSurvey = false;
        $last_indicator = Indicator::getLastIndicator($indicator->year, $indicator->identifier);
        if (!is_null($last_indicator))
        {
            $last_indicator_accordions = $last_indicator->accordions()->get();
            if ($last_indicator_accordions->count()) {
                $canLoadLastIndicatorSurvey = true;
            }
        }
        
        return view('index.indicator-survey', [
            'canPreviewIndicator' => (isset($indicators_with_survey[$indicator->id]) ? true : false),
            'canPreviewSurvey' => (!empty($indicators_with_survey) ? true : false),
            'canLoadLastIndicatorSurvey' => $canLoadLastIndicatorSurvey,
            'areas' => Area::getAreas($indicator->year),
            'indicator' => $indicator,
            'indicator_survey_data' => json_encode($indicator_survey_data)]);
    }

    public function storeIndicatorSurvey(Request $request, Indicator $indicator)
    {
        $inputs = $request->all();
        $save = ($inputs['action'] == 'save') ? true : false;
        $exceptions = [];

        DB::beginTransaction();
        
        try
        {
            // Delete all survey configuration for this indicator
            $db_accordions = IndicatorAccordion::where('indicator_id', $indicator->id)->pluck('id')->toArray();
            IndicatorAccordion::whereIn('id', $db_accordions)->delete();

            if (isset($inputs['indicator_survey_data']) &&
                $inputs['indicator_survey_data'] != '[]' )
            {
                $indicator_survey_data = json_decode(html_entity_decode($inputs['indicator_survey_data']), true);

                $qkey = 0;

                foreach ($indicator_survey_data as $fkey => $element)
                {
                    $type = $element['type'];
                    if ($type == 'radio-group') {
                        $type = 'single-choice';
                    }
                    elseif ($type == 'checkbox-group') {
                        $type = 'multiple-choice';
                    }
                    elseif ($type == 'text') {
                        $type = 'free-text';
                    }
                    $next_element = $indicator_survey_data[$fkey + 1] ?? null;

                    // Accordion
                    if ($type == 'header')
                    {
                        $qkey = 0;

                        if (empty($element['label'])) {
                            $element['label'] = 'Questions';
                        }

                        if (is_null($next_element) ||
                            (!is_null($next_element) &&
                             $next_element['type'] == 'header'))
                        {
                            if ($save)
                            {
                                array_push($exceptions, new CustomException(
                                    'Sections must be followed by at least one question. Please add a question or remove empty sections!',
                                    self::VALIDATION_EXCEPTION_CODE,
                                    null,
                                    [
                                        'element_type' => 'alert'
                                    ]
                                ));

                                break;
                            }
                            else {
                                continue;
                            }
                        }

                        IndicatorAccordion::disableAuditing();
                        $db_accordion = IndicatorAccordion::updateOrCreateIndicatorAccordion(
                            [
                                'indicator_id' => $indicator->id,
                                'title' => (isset($element['label'])) ? $element['label'] : '',
                                'order' => $fkey
                            ]
                        );
                        IndicatorAccordion::enableAuditing();
                    }
                    // Single-choice
                    // Multiple-choice
                    // Free-text
                    else
                    {
                        if (!isset($db_accordion))
                        {
                            if ($save)
                            {
                                array_push($exceptions, new CustomException(
                                    'Section is mandatory. Please add at least one section!',
                                    self::VALIDATION_EXCEPTION_CODE,
                                    null,
                                    [
                                        'element_type' => 'alert'
                                    ]
                                ));

                                break;
                            }
                            else
                            {
                                IndicatorAccordion::disableAuditing();
                                $db_accordion = IndicatorAccordion::updateOrCreateIndicatorAccordion(
                                    [
                                        'indicator_id' => $indicator->id,
                                        'title' => 'Section Title',
                                        'order' => $fkey
                                    ]
                                );
                                IndicatorAccordion::enableAuditing();
                            }
                        }

                        if ($save &&
                            empty($element['label']))
                        {
                            array_push($exceptions, new CustomException(
                                'Question title is required!',
                                self::VALIDATION_EXCEPTION_CODE,
                                null,
                                [
                                    'field_id' => $element['name'],
                                    'element_type' => 'question'
                                ]
                            ));
                        }
                
                        IndicatorAccordionQuestion::disableAuditing();
                        $db_question = IndicatorAccordionQuestion::updateOrCreateIndicatorAccordionQuestion(
                            [
                                'accordion_id' => $db_accordion->id,
                                'title' => (isset($element['label'])) ? $element['label'] : '',
                                'order' => $qkey++,
                                'type_id' => IndicatorQuestionType::where('type', $type)->value('id'),
                                'info' => (isset($element['description'])) ? $element['description'] : '',
                                'compatible' => filter_var($element['compatible'], FILTER_VALIDATE_BOOLEAN),
                                'answers_required' => filter_var($element['required'], FILTER_VALIDATE_BOOLEAN),
                                'reference_required' => filter_var($element['required'], FILTER_VALIDATE_BOOLEAN)
                            ]
                        );
                        IndicatorAccordionQuestion::enableAuditing();
                
                        if ($type == 'single-choice' ||
                            $type == 'multiple-choice')
                        {
                            foreach ($element['values'] as $okey => $option)
                            {
                                if ($save)
                                {
                                    $exception_data = [
                                        'field_id' => $element['name'],
                                        'element_type' => 'option',
                                        'element_id' => $okey
                                    ];

                                    if (empty($option['label'])) {
                                        array_push($exceptions, new CustomException(
                                            'Option label is required!',
                                            self::VALIDATION_EXCEPTION_CODE,
                                            null,
                                            $exception_data
                                        ));
                                    }

                                    if (!strlen($option['value'])) {
                                        array_push($exceptions, new CustomException(
                                            'Option score is required!',
                                            self::VALIDATION_EXCEPTION_CODE,
                                            null,
                                            $exception_data
                                        ));
                                    }
                                    elseif (!is_numeric($option['value'])) {
                                        array_push($exceptions, new CustomException(
                                            'Option score must be integer!',
                                            self::VALIDATION_EXCEPTION_CODE,
                                            null,
                                            $exception_data
                                        ));
                                    }
                                }

                                IndicatorAccordionQuestionOption::disableAuditing();
                                IndicatorAccordionQuestionOption::updateOrCreateIndicatorAccordionQuestionOption(
                                    [
                                        'question_id' => $db_question->id,
                                        'text' => $option['label'],
                                        'master' => (isset($element['master']) && in_array($option['label'], $element['master'])) ? true : false,
                                        'score' => (strlen($option['value']) && is_numeric($option['value'])) ? $option['value'] : null,
                                        'value' => ++$okey
                                    ]
                                );
                                IndicatorAccordionQuestionOption::enableAuditing();
                            }
                        }
                    }
                }
            }
            else {
                array_push($exceptions, new CustomException(
                    'Please add one or more sections/questions!',
                    self::VALIDATION_EXCEPTION_CODE,
                    null,
                    [
                        'element_type' => 'alert'
                    ]
                ));
            }
            
            if (!empty($exceptions)) {
                throw new CustomException('Custom exception occurred.', self::VALIDATION_EXCEPTION_CODE);
            }
        }
        catch (CustomException $e)
        {
            DB::rollback();

            Indicator::find($indicator->id)->update(['validated' => false]);

            $data = [];
            foreach ($exceptions as $exception) {
                array_push($data, [
                    'error' => $exception->getMessage(),
                    'data' => $exception->getExceptionData()
                ]);
            }

            return response()->json($data, $e->getCode());
        }

        DB::commit();
        
        Indicator::find($indicator->id)->update(['validated' => $save]);

        return response()->json(['success' => 'Indicator survey have been successfully saved!'], 200);
    }

    public function loadIndicatorSurvey(Indicator $indicator)
    {
        // Delete all survey data for this indicator
        $db_accordions = IndicatorAccordion::where('indicator_id', $indicator->id)->pluck('id')->toArray();
        IndicatorAccordion::whereIn('id', $db_accordions)->delete();

        $last_indicator = Indicator::getLastIndicator($indicator->year, $indicator->identifier);
        IndexConfigurationIndicatorHelper::cloneSurveyIndicator($last_indicator, $indicator);

        return response()->json(['success' => 'Indicator survey from last year have been successfully loaded!'], 200);
    }
}
