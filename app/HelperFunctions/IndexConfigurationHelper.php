<?php

namespace App\HelperFunctions;

use App\Models\Area;
use App\Models\IndexConfiguration;
use App\Models\Indicator;
use App\Models\IndicatorAccordion;
use App\Models\IndicatorAccordionQuestion;
use App\Models\IndicatorAccordionQuestionOption;
use App\Models\IndicatorCalculationVariable;
use App\Models\IndicatorDisclaimer;
use App\Models\IndicatorQuestionType;
use App\Models\Subarea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Exception;

class IndexConfigurationHelper
{
    public static function validateInputsForCreate($inputs)
    {
        return validator($inputs, [
            'name' => ['required', Rule::unique('index_configurations')->whereNull('deleted_at')],
            'year' => [Rule::when(
                empty($inputs['year']),
                'required'
            )]
        ]);
    }

    public static function validateInputsForEdit($inputs)
    {
        return validator($inputs, [
            'name' => ['required', Rule::unique('index_configurations')->ignore($inputs['id'])->whereNull('deleted_at')],
            'year' => [Rule::when(
                empty($inputs['year']),
                'required'
            )]
        ]);
    }

    public static function validateIndexConfigurationStatusAndData(IndexConfiguration $index, $inputs)
    {
        $officialIndex = IndexConfiguration::getExistingPublishedConfigurationForYear($inputs['year']);

        if ($inputs['draft'] == 'false' && $officialIndex && $officialIndex->id != $inputs['id']) {
            return 'Another official configuration already exists for this year!';
        }
        
        if ($inputs['draft'] == 'true')
        {
            $published_indexes = IndexConfiguration::getPublishedConfigurations($index);
            
            if (empty($published_indexes->toArray())) {
                return 'There are no other official configurations exist in the tool!';
            }
            
            if ($index->index()->count() &&
                $index->baseline()->count())
            {
                return 'This configuration has active indexes!';
            }
        }

        return null;
    }

    public static function validateIndexPropertiesUpload($request)
    {
        return validator(
            $request->all(),
            [
                'file' => 'required|file|mimes:xlsx|max:2048',
                'extension' => ['nullable', Rule::in(['xlsx'])]
            ],
            [
                'extension' => 'The file must be of type .xlsx'
            ]
        );
    }

    public static function validateIndexConfigurationClone($inputs)
    {
        return validator(
            $inputs,
            [
                'clone-index' => 'required'
            ],
            [
                'clone-index.required' => 'The index year is required.'
            ]
        );
    }

    public static function canCreateIndexConfiguration($year)
    {
        $areas = Area::getAreas($year);
        $subareas = Subarea::getSubareas($year);
        $indicators = Indicator::getIndicators($year);

        if (!$areas->count() ||
            !$subareas->count() ||
            !$indicators->count())
        {
            return false;
        }

        return true;
    }

    public static function canDeleteIndexConfiguration($index)
    {
        return ($index->draft) ? true : false;
    }

    public static function getIndexData($data)
    {
        return [
            'name' => $data['name'],
            'description' => (isset($data['description'])) ? $data['description'] : '',
            'year' => $data['year'],
            'draft' => (isset($data['draft'])) ? filter_var($data['draft'], FILTER_VALIDATE_BOOLEAN) : true,
            'eu_published' => (isset($data['eu_published'])) ? filter_var($data['eu_published'], FILTER_VALIDATE_BOOLEAN) : false,
            'ms_published' => (isset($data['ms_published'])) ? filter_var($data['ms_published'], FILTER_VALIDATE_BOOLEAN) : false,
            'user_id' => Auth::user()->id,
            'json_data' => $data['json_data']
        ];
    }

    public static function storeIndexConfiguration($data)
    {
        IndexConfiguration::create(self::getIndexData($data));
    }

    public static function updateIndexConfiguration($index, $data)
    {
        IndexConfiguration::find($index->id)->update(self::getIndexData($data));
    }

    public static function deleteIndexConfiguration($index)
    {
        $index->delete();
    }

    public static function getIndexPropertiesAreasByYear($sheet, $year)
    {
        $rows = array_slice($sheet->toArray(), 1);
        $areas = [];
        $entries = [];

        foreach($rows as $row)
        {
            $id = GeneralHelper::convertSpecialCharacters($row[0]);
            $name = GeneralHelper::convertSpecialCharacters($row[1]);

            if (in_array($name, $areas)) {
                throw new Exception("Area name '{$name}' already exists. Please check id '{$id}' in the Areas sheet!");
            }

            array_push($areas, $name);
            array_push($entries, [
                'name' => $name,
                'description' => GeneralHelper::convertSpecialCharacters($row[2]),
                'identifier' => GeneralHelper::convertSpecialCharacters($row[3]),
                'default_weight' => GeneralHelper::convertSpecialCharacters($row[4]),
                'default' => true,
                'year' => $year,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }

        return $entries;
    }

    public static function getIndexPropertiesSubareasByYear($sheet, $year)
    {
        $rows = array_slice($sheet->toArray(), 1);
        $subareas = [];
        $entries = [];

        foreach($rows as $row)
        {
            $id = GeneralHelper::convertSpecialCharacters($row[0]);
            $name = GeneralHelper::convertSpecialCharacters($row[1]);
            $area = GeneralHelper::convertSpecialCharacters($row[5]);

            if (in_array($name, $subareas)) {
                throw new Exception("Subarea name '{$name}' already exists. Please check id '{$id}' in the Subareas sheet!");
            }

            if (!strlen(trim($area))) {
                throw new Exception("Area name is missing. Please check id '{$id}' in the Subareas sheet!");
            }

            $dbArea = Area::where('name', $area)->where('year', $year)->first();

            if (is_null($dbArea)) {
                throw new Exception("Area name '{$area}' was not found in the Areas sheet. Please check id '{$id}' in the Subareas sheet!");
            }

            array_push($subareas, $name);
            array_push($entries, [
                'name' => $name,
                'short_name' => GeneralHelper::convertSpecialCharacters($row[2]),
                'description' => GeneralHelper::convertSpecialCharacters($row[3]),
                'identifier' => GeneralHelper::convertSpecialCharacters($row[4]),
                'default_area_id' => $dbArea->id,
                'default_weight' => GeneralHelper::convertSpecialCharacters($row[6]),
                'year' => $year,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }

        return $entries;
    }

    public static function getIndexPropertiesIndicatorsByYear($sheet, $year)
    {
        $rows = array_slice($sheet->toArray(), 1);
        $indicators = [];
        $entries = [
            'survey' => [],
            'eurostat' => [],
            'eu-wide' => [],
            'manual' => []
        ];
        $all_entries = [];
        $calculationVariables = [];
        $order = 0;

        foreach($rows as $row)
        {
            $id = GeneralHelper::convertSpecialCharacters($row[0]);
            $name = GeneralHelper::convertSpecialCharacters($row[1]);
            $identifier = GeneralHelper::convertSpecialCharacters($row[4]);
            $category = GeneralHelper::convertSpecialCharacters($row[6]);
            $algorithm = GeneralHelper::convertSpecialCharacters($row[7]);
            $comment = GeneralHelper::convertSpecialCharacters($row[8]);
            $predefined_divider = GeneralHelper::convertSpecialCharacters($row[13]);
            $subarea = GeneralHelper::convertSpecialCharacters($row[17]);

            if (in_array($name, $indicators)) {
                throw new Exception("Indicator name '{$name}' already exists. Please check id '{$id}' in the Indicators sheet!");
            }

            if (!strlen(trim($category))) {
                throw new Exception("Category name is missing. Please check id '{$id}' in the Indicators sheet!");
            }

            if (!in_array($category, ['survey', 'eurostat', 'manual', 'eu-wide'])) {
                throw new Exception("Category name is invalid. Please check id '{$id}' in the Indicators sheet!");
            }

            if ($category != 'eu-wide')
            {
                if (!strlen(trim($subarea))) {
                    throw new Exception("Subarea name is missing. Please check id '{$id}' in the Indicators sheet!");
                }

                $dbSubarea = Subarea::where('name', $subarea)->where('year', $year)->first();

                if (is_null($dbSubarea)) {
                    throw new Exception("Subarea name '{$subarea}' was not found in the Subareas sheet. Please check id '{$id}' in the Indicators sheet!");
                }
            }

            array_push($indicators, $name);

            $data = [
                'name' => $name,
                'short_name' => GeneralHelper::convertSpecialCharacters($row[2]),
                'description' => GeneralHelper::convertSpecialCharacters($row[3]),
                'identifier' => $identifier,
                'source' => GeneralHelper::convertSpecialCharacters($row[5]),
                'category' => $category,
                'algorithm' => $algorithm,
                'comment' => $comment,
                'disclaimers' => [
                    'direction' => GeneralHelper::convertSpecialCharacters($row[9]),
                    'new_indicator' => GeneralHelper::convertSpecialCharacters($row[10]),
                    'min_max_0037_1' => GeneralHelper::convertSpecialCharacters($row[11]),
                    'min_max' => GeneralHelper::convertSpecialCharacters($row[12])
                ],
                'report_year' => GeneralHelper::convertSpecialCharacters($row[16]),
                'default_subarea_id' => ($category != 'eu-wide') ? $dbSubarea->id : null,
                'default_weight' => GeneralHelper::convertSpecialCharacters($row[18]),
                'year' => $year,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];

            if ($category == 'survey')
            {
                $data['order'] = ++$order;
                $data['validated'] = 1;

                $entries['survey'][$identifier] = $data;
            }
            elseif ($category == 'eurostat') {
                $entries['eurostat'][$identifier] = $data;
            }
            elseif ($category == 'eu-wide') {
                $entries['eu-wide'][$identifier] = $data;
            }
            elseif ($category == 'manual') {
                $entries['manual'][$identifier] = $data;
            }

            if (strlen(trim($predefined_divider)) &&
                $predefined_divider == 0)
            {
                throw new Exception("Predefined divider cannot be zero! Please check id '{$id}' in the Indicators sheet!");
            }

            $calculationVariables[$identifier] = [
                'algorithm' => $algorithm,
                'predefined_divider' => $predefined_divider,
                'normalize' => GeneralHelper::convertSpecialCharacters($row[14]),
                'inverse_value' => GeneralHelper::convertSpecialCharacters($row[15])
            ];
        }

        ksort($entries['survey']);
        ksort($entries['eurostat']);
        ksort($entries['eu-wide']);
        ksort($entries['manual']);

        $all_entries = $entries['survey'] + $entries['eurostat'] + $entries['eu-wide'] + $entries['manual'];

        return [$all_entries, $calculationVariables];
    }

    public static function importIndexPropertiesIndicatorsSurveyData($sheet, $year, $calculationVariables)
    {
        $rows = array_slice($sheet->toArray(), 1);
        $identifier = null;
        $processed_accordions = [];
        $processed_questions = [];
        $value = 0;

        foreach($rows as $row)
        {
            if (is_null($identifier))
            {
                $identifier = GeneralHelper::convertSpecialCharacters($row[0]);

                $indicator = Indicator::where('identifier', $identifier)->where('year', $year)->first();
            }

            $question_type = GeneralHelper::convertSpecialCharacters($row[3]);
            $question_id = GeneralHelper::convertSpecialCharacters($row[5]);
            $parts = explode('-', $question_id);
            
            if (!in_array($question_id, $processed_accordions))
            {
                array_push($processed_accordions, $question_id);
                
                $db_accordion = IndicatorAccordion::updateOrCreateIndicatorAccordion(
                    [
                        'indicator_id' => $indicator->id,
                        'title' => GeneralHelper::convertSpecialCharacters($row[2]),
                        'order' => $parts[1]
                    ]
                );
            }

            if (!in_array($question_id, $processed_questions))
            {
                array_push($processed_questions, $question_id);

                $required = (strtolower($row[6]) == 'yes') ? true : false;
                
                $db_question = IndicatorAccordionQuestion::updateOrCreateIndicatorAccordionQuestion(
                    [
                        'accordion_id' => $db_accordion->id,
                        'title' => GeneralHelper::convertSpecialCharacters($row[4]),
                        'order' => $parts[2],
                        'type_id' => IndicatorQuestionType::where('type', $question_type)->value('id'),
                        'info' => GeneralHelper::convertSpecialCharacters($row[10]),
                        'compatible' => (strtolower($row[11]) == 'yes') ? true : false,
                        'answers_required' => $required,
                        'reference_required' => $required
                    ]
                );

                $data = $calculationVariables[$identifier];
                $data['question_id'] = $question_id;
                $data['type'] = $question_type;
                
                IndicatorCalculationVariable::create($data);
            }

            if ($question_type == 'single-choice' ||
                $question_type == 'multiple-choice')
            {
                IndicatorAccordionQuestionOption::updateOrCreateIndicatorAccordionQuestionOption(
                    [
                        'question_id' => $db_question->id,
                        'text' => GeneralHelper::convertSpecialCharacters($row[7]),
                        'master' => (strtolower($row[8]) == 'yes') ? true : false,
                        'score' => GeneralHelper::convertSpecialCharacters($row[9]),
                        'value' => ++$value
                    ]
                );
            }
        }
    }

    public static function importIndexProperties($year, $excel, $originalName, $skipDelete = false)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($excel);

        DB::beginTransaction();

        try
        {
           // Delete model and all references in the other tables using ON DELETE CASCADE
           $indicators = Indicator::getIndicators($year);
           foreach ($indicators as $indicator) {
               IndexConfigurationIndicatorHelper::deleteIndicator($indicator->id);
           }
           Subarea::where('year', $year)->delete();
           Area::where('year', $year)->delete();
           
            // Areas
            $sheet = $spreadsheet->getSheetByName('Areas');
            if (is_null($sheet)) {
                throw new Exception("Areas sheet was not found in the {$originalName}!");
            }
            $areas = self::getIndexPropertiesAreasByYear($sheet, $year);
            Area::disableAuditing();
            Area::insert($areas);
            Area::enableAuditing();

            // Subreas
            $sheet = $spreadsheet->getSheetByName('Subareas');
            if (is_null($sheet)) {
                throw new Exception("Subareas sheet was not found in the {$originalName}!");
            }
            $subareas = self::getIndexPropertiesSubareasByYear($sheet, $year);
            Subarea::disableAuditing();
            Subarea::insert($subareas);
            Subarea::enableAuditing();

            // Indicators
            $sheet = $spreadsheet->getSheetByName('Indicators');
            if (is_null($sheet)) {
                throw new Exception("Indicators sheet was not found in the {$originalName}!");
            }
            list($indicators, $calculationVariables) = self::getIndexPropertiesIndicatorsByYear($sheet, $year);
            Indicator::disableAuditing();
            foreach ($indicators as $indicator)
            {
                $disclaimers = $indicator['disclaimers'];
                unset($indicator['disclaimers']);

                $indicator_entry = Indicator::create($indicator);

                $disclaimers['indicator_id'] = $indicator_entry['id'];
                IndicatorDisclaimer::create($disclaimers);

                $calculationVariables[$indicator['identifier']]['indicator_id'] = $indicator_entry['id'];
            }
            Indicator::enableAuditing();

            // Indicators Json Data
            $sheets = $spreadsheet->getAllSheets();

            foreach ($sheets as $sheet)
            {
                $title = $sheet->getTitle();

                if (in_array($title, ['Areas', 'Subareas', 'Indicators'])) {
                    continue;
                }

                self::importIndexPropertiesIndicatorsSurveyData($sheet, $year, $calculationVariables);
            }

            IndexConfiguration::updateDraftIndexConfigurationJsonData($year);
        }
        catch (Exception $e)
        {
            Log::debug($e->getMessage());

            DB::rollback();

            if (!$skipDelete) {
                File::delete($excel);
            }

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
            'msg' => 'Index properties have been successfully imported!'
        ];
    }

    public static function cloneIndexConfiguration($year_from, $clone_survey)
    {
        DB::beginTransaction();
        
        try
        {
            $year_to = $_COOKIE['index-year'];

            // Delete model and all references in the other tables using ON DELETE CASCADE
            $indicators = Indicator::getIndicators($year_to);
            foreach ($indicators as $indicator) {
                IndexConfigurationIndicatorHelper::deleteIndicator($indicator->id);
            }
            Subarea::where('year', $year_to)->delete();
            Area::where('year', $year_to)->delete();

            // Areas
            $areas = Area::getAreas($year_from);
            Area::disableAuditing();
            foreach ($areas as $area)
            {
                $replicate_area = $area->replicate();
                $replicate_area->year = $year_to;
                $replicate_area->clone_year = $year_from;
        
                $replicate_area->save();
            }
            Area::enableAuditing();

            // Subreas
            $subareas = Subarea::getSubareas($year_from);
            Subarea::disableAuditing();
            foreach ($subareas as $subarea)
            {
                $replicate_subarea = $subarea->replicate();
                $replicate_subarea->year = $year_to;
                $replicate_subarea->clone_year = $year_from;
                
                if (!is_null($subarea->default_area))
                {
                    $area_from = Area::where('identifier', $subarea->default_area->identifier)->where('year', $year_to)->first();
                    $replicate_subarea->default_area_id = $area_from->id;
                }
        
                $replicate_subarea->save();
            }
            Subarea::enableAuditing();

            // Indicators
            $indicators = Indicator::getIndicators($year_from);
            Indicator::disableAuditing();
            foreach ($indicators as $indicator)
            {
                $replicate_indicator = $indicator->replicate();
                $replicate_indicator->year = $year_to;
                $replicate_indicator->clone_year = $year_from;
                if (!$clone_survey) {
                    $replicate_indicator->validated = false;
                }
                
                if (!is_null($indicator->default_subarea))
                {
                    $subarea_from = Subarea::where('identifier', $indicator->default_subarea->identifier)->where('year', $year_to)->first();
                    $replicate_indicator->default_subarea_id = $subarea_from->id;
                }
        
                $replicate_indicator->save();
            }
            Indicator::enableAuditing();

            IndexConfiguration::updateDraftIndexConfigurationJsonData($year_to);
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
            'msg' => 'Index configuration have been successfully cloned!'
        ];
    }
}
