<?php

namespace App\HelperFunctions;

use App\Models\IndicatorValue;
use App\Models\EurostatIndicator;
use App\Models\Country;
use App\Models\Index;
use App\Models\Indicator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Exception;

class IndexDataCollectionHelper
{
    const IMPORTDATACOLLECTIONTASK = 'ImportDataCollection';

    public static function validateInputs($request)
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

    public static function getDataCollectionCountries($index, $questionnaire_id)
    {
        return Country::select(
            'countries.id AS country_id',
            'countries.name AS country_name',
            'questionnaire_countries.id AS questionnaire_country_id',
            'indices.status_id AS status_id'
        )
            ->leftJoin('questionnaire_countries', function ($query) use ($questionnaire_id) {
                $query->on('countries.id', '=', 'questionnaire_countries.country_id')->where('questionnaire_countries.questionnaire_id', $questionnaire_id);
            })
            ->leftJoin('indices', function ($query) use ($index) {
                $query->on('countries.id', '=', 'indices.country_id')->where('indices.index_configuration_id', $index->id);
            })
            ->whereNot('countries.name', config('constants.USER_GROUP'))
            ->groupBy('countries.id', 'countries.name', 'indices.status_id', 'questionnaire_countries.id')
            ->get();
    }

    public static function getDataCollectionIndicators($index, $country, $category)
    {
        return IndicatorValue::with('indicator')->whereHas('indicator', function ($query) use ($index, $category) {
                $query->where('category', $category)->where('indicators.year', $index->year);
            })
            ->where('country_id', $country->country_id)
            ->count();
    }

    public static function getEurostatData($inputs)
    {
        return EurostatIndicator::select(
            'eurostat_indicators.id AS indicator_id',
            'eurostat_indicators.name AS indicator_name',
            'eurostat_indicators.value AS indicator_value',
            'countries.name AS country_name',
            'indices.status_id AS country_status'
        )
            ->leftJoin('countries', 'countries.id', '=', 'eurostat_indicators.country_id')
            ->leftJoin('indices', function ($query) use ($inputs) {
                $query->on('eurostat_indicators.country_id', '=', 'indices.country_id')->where('indices.index_configuration_id', $inputs['index']->id);
            })
            ->when(isset($inputs['indicator']) && $inputs['indicator'] != 'All', function ($query) use ($inputs) {
                $query->where('eurostat_indicators.name', $inputs['indicator']);
            })
            ->when(isset($inputs['indicator']) && $inputs['country'] != 'All', function ($query) use ($inputs) {
                $query->where('countries.name', $inputs['country']);
            })
            ->whereYear('eurostat_indicators.updated_at', $inputs['index']->year)
            ->get();
    }

    public static function getIndicatorValueData($inputs)
    {
        return IndicatorValue::select(
            'indicators.name AS indicator_name',
            'indicator_values.value AS indicator_value',
            DB::raw('
                (
                    CASE
                        WHEN countries.name = \'' . config('constants.USER_GROUP') . '\' THEN \'EU-wide\'
                        ELSE countries.name
                    END
                ) AS country_name')
        )
            ->leftJoin('indicators', 'indicators.id', '=', 'indicator_values.indicator_id')
            ->leftJoin('countries', 'countries.id', '=', 'indicator_values.country_id')
            ->when(isset($inputs['indicator']) && $inputs['indicator'] != 'All', function ($query) use ($inputs) {
                $query->where('indicators.name', $inputs['indicator']);
            })
            ->when(isset($inputs['country']) && $inputs['country'] != 'All', function ($query) use ($inputs) {
                if ($inputs['country'] == 'EU-wide') {
                    $query->where('countries.name', config('constants.USER_GROUP'));
                }
                else {
                    $query->where('countries.name', $inputs['country']);
                }
            })
            ->when(is_array($inputs['category']), function ($query) use ($inputs) {
                $query->whereIn('indicators.category', $inputs['category']);
            })
            ->when(!is_array($inputs['category']), function ($query) use ($inputs) {
                $query->where('indicators.category', $inputs['category']);
            })
            ->where('indicator_values.year', $inputs['index']->year)
            ->when(isset($inputs['questionnaire']), function ($query) use ($inputs) {
                $query->whereIn('indicator_values.indicator_id', function ($query) use ($inputs) {
                    $query->select('indicator_id')
                          ->from('questionnaire_indicators')
                          ->where('questionnaire_id', $inputs['questionnaire']->id);
                });
            })
            ->get();
    }

    public static function getFilterData($values)
    {
        $data = [];
        $indicators = [];
        $countries = [];

        if (!empty($values))
        {
            foreach ($values as $value)
            {
                array_push($indicators, $value['indicator_name']);
                array_push($countries, $value['country_name']);
            }

            if (!empty($indicators)) {
                array_push($data, array_unique($indicators));
            }
            if (!empty($countries)) {
                array_push($data, array_unique($countries));
            }
        }

        return $data;
    }

    public static function approveIndexByCountry($index, $country)
    {
        Index::where('index_configuration_id', $index->id)->where('country_id', $country->id)->update(['status_id' => 3]);
    }

    public static function discardExternalDataCollection($index)
    {
        $interim_tables = ['eurostat_indicator_variables', 'eurostat_indicators'];

        foreach ($interim_tables as $interim_table) {
            DB::table($interim_table)->whereYear('updated_at', $index->year)->delete();
        }
    }

    public static function updateOrCreateManualOrEurostatIndicatorsByYear($rows, $index, $category)
    {
        $approvedCountries = Index::where('status_id', 3)->where('index_configuration_id', $index->id)->pluck('country_id')->toArray();
        $countries_codes = array_shift($rows);

        foreach ($rows as $row)
        {
            $identifier = array_shift($row);
            $countries = Country::pluck('id', 'code')->toArray();
            $indicators = Indicator::where('category', $category)->where('year', $index->year)->pluck('id', 'identifier')->toArray();
            
            foreach ($row as $key => $value)
            {
                $country_code = strtoupper($countries_codes[$key + 1]);
            
                if (!is_null($value) &&
                    array_key_exists($country_code, $countries) &&
                    array_key_exists($identifier, $indicators))
                {
                    if (in_array($countries[$country_code], $approvedCountries)) {
                        continue;
                    }
                
                    IndicatorValue::updateOrCreateIndicatorValue([
                        'indicator_id' => $indicators[$identifier],
                        'country_id' => $countries[$country_code],
                        'year' => $index->year,
                        'value' => $value
                    ]);
                }
            }
        }
    }

    public static function updateOrCreateEUWideIndicatorsByYear($rows, $index)
    {
        unset($rows[0]);
        
        foreach ($rows as $row)
        {
            $identifier = array_shift($row);
            $indicators = Indicator::where('category', 'eu-wide')->where('year', $index->year)->pluck('id', 'identifier')->toArray();

            foreach ($row as $value)
            {
                if (!is_null($value) &&
                    array_key_exists($identifier, $indicators))
                {
                    IndicatorValue::updateOrCreateIndicatorValue([
                        'indicator_id' => $indicators[$identifier],
                        'country_id' => Country::where('name', config('constants.USER_GROUP'))->value('id'),
                        'year' => $index->year,
                        'value' => $value
                    ]);
                }
            }
        }
    }

    public static function storeImportDataCollection($excel, $index)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($excel);

        DB::beginTransaction();

        try
        {
            $sheets = $spreadsheet->getAllSheets();

            foreach ($sheets as $sheet)
            {
                $title = strtolower($sheet->getTitle());
                $rows = $sheet->toArray();

                if (preg_match('/enisa/', $title)) {
                    self::updateOrCreateManualOrEurostatIndicatorsByYear($rows, $index, 'manual');
                }
                elseif (preg_match('/eu-wide/', $title)) {
                    self::updateOrCreateEUWideIndicatorsByYear($rows, $index);
                }
                elseif (preg_match('/eurostat/', $title)) {
                    self::updateOrCreateManualOrEurostatIndicatorsByYear($rows, $index, 'eurostat');
                }
                else {
                    throw new Exception("No indicators were imported! Please check the sheet names i.e. ENISA, EU-wide, Eurostat");
                }
            }
        }
        catch (Exception $e)
        {
            Log::debug($e->getMessage());

            DB::rollback();

            File::delete($excel);

            return [
                'type' => 'error',
                'msg' => $e->getMessage()
            ];
        }

        DB::commit();

        return [
            'type' => 'success',
            'msg' => 'Import data have been successfully imported!'
        ];
    }

    public static function discardImportDataCollection($index)
    {
        IndicatorValue::with('indicator')->whereHas('indicator', function ($query) {
            $query->whereIn('indicators.category', ['manual', 'eu-wide']);
        })
        ->where('year', $index->year)
        ->delete();
    }
}
