<?php

namespace App\HelperFunctions;

use App\Imports\IndexDataImport;
use App\Models\BaselineIndex;
use App\Models\Country;
use App\Models\Index;
use App\Models\IndexConfiguration;
use App\Models\Indicator;
use App\Models\IndicatorQuestionScore;
use App\Models\IndicatorValue;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class IndexCalculationHelper
{
    const INDEX_CALCULATION_PATH = 'index_calculations';
    const EUCSI_RESULTS = 'EUCSI-results.xlsx';
    const JSON_FILES_PATH = 'results/reports/json-files';

    public static function calculateIndex(IndexConfiguration $index)
    {
        if (isset($index->json_data['contents']))
        {
            $data = [];
            $CSIndMetaData = [];
            $CSAggMetaData = [];
            $CSIndData = [];
            $CSDNAData = [];
            $EUwideIndicatorsData = [];

            $countries = Country::whereNot('name', config('constants.USER_GROUP'))->get();
            foreach ($countries as $country)
            {
                $CSIndData['UnitName'][] = $country->name;
                $CSIndData['UnitCode'][] = $country->code;

                $CSDNAData['CountryID'][] = $country->iso;
                $CSDNAData['CountryName'][] = $country->name;
            }

            $CSAggMetaData['AgLevel'][] = 4;
            $CSAggMetaData['Code'][] = 'Index';
            $CSAggMetaData['Name'][] = 'Index';
            $CSAggMetaData['Description'][] = 'Index';
            $CSAggMetaData['Weight'][] = 1;
            $CSAggMetaData['FriendlyName'][] = 'Index';

            foreach ($index->json_data['contents'] as $value)
            {
                $area = $value['area'];
                
                $area_id = 'AREA_' . $area['identifier'];
                $area_name = $area['name'];

                $CSAggMetaData['AgLevel'][] = 3;
                $CSAggMetaData['Code'][] = $area_id;
                $CSAggMetaData['Name'][] = $area_name;
                $CSAggMetaData['Description'][] = $area['description'];
                $CSAggMetaData['FriendlyName'][] = $area_name;
                $CSAggMetaData['Weight'][] = $area['weight'];

                foreach ($area['subareas'] as $subarea)
                {
                    $subarea_id = 'SUBAREA_' . $subarea['identifier'];

                    $CSAggMetaData['AgLevel'][] = 2;
                    $CSAggMetaData['Code'][] = $subarea_id;
                    $CSAggMetaData['Name'][] = $subarea['name'];
                    $CSAggMetaData['Description'][] = $subarea['description'];
                    $CSAggMetaData['Weight'][] = $subarea['weight'];
                    $CSAggMetaData['FriendlyName'][] = $subarea['short_name'];

                    foreach ($subarea['indicators'] as $indicator)
                    {
                        $identifier = 'IND_' . $indicator['identifier'];

                        $CSIndMetaData['IndName'][] = $indicator['name'];
                        $CSIndMetaData['IndCode'][] = $identifier;
                        $CSIndMetaData['IndWeight'][] = $indicator['weight'];
                        $CSIndMetaData['Agg1'][] = $subarea_id;
                        $CSIndMetaData['Agg2'][] = $area_id;
                        $CSIndMetaData['Agg3'][] = 'Index';
                        $CSIndMetaData['FriendlyName'][] = $indicator['short_name'];
                        $CSIndMetaData['Algorithm'][] = $indicator['algorithm'];
                        $CSIndMetaData['Source'][] = $indicator['source'];
                        $CSIndMetaData['ReferenceYear'][] = $indicator['report_year'];
                        $CSIndMetaData['w100meansEU'][] = $indicator['disclaimers']['what_100_means_eu'];
                        $CSIndMetaData['w100meansMS'][] = $indicator['disclaimers']['what_100_means_ms'];
                        $CSIndMetaData['FracMaxNorm'][] = $indicator['disclaimers']['frac_max_norm'];
                        $CSIndMetaData['RankNorm'][] = $indicator['disclaimers']['rank_norm'];
                        $CSIndMetaData['Target100'][] = $indicator['disclaimers']['target_100'];
                        $CSIndMetaData['Target75'][] = $indicator['disclaimers']['target_75'];
                        $CSIndMetaData['Direction'][] = $indicator['disclaimers']['direction'];

                        $CSIndData[$identifier] = [];
                        $CSDNAData[$identifier] = [];

                        foreach ($countries as $country)
                        {
                            $indicatorValue = IndicatorValue::where('indicator_id', $indicator['id'])
                                ->where('country_id', $country->id)
                                ->where('year', $index->year)
                                ->value('value');

                            $CSIndData[$identifier][] = (!is_null($indicatorValue)) ? $indicatorValue : 'ΝΑ';

                            $dataNotAvailable = IndicatorQuestionScore::where('indicator_id', $indicator['id'])
                                ->where('country_id', $country->id)
                                ->sum('data_not_available');

                            $CSDNAData[$identifier][] = intval($dataNotAvailable);
                        }
                    }
                }
            }

            $EUwideIndicators = Indicator::where('category', 'eu-wide')->where('year', $index->year)->get();
            foreach ($EUwideIndicators as $EUwideIndicator)
            {
                $indicatorValue = IndicatorValue::where('indicator_id', $EUwideIndicator->id)
                    ->where('country_id', Country::where('name', config('constants.USER_GROUP'))->value('id'))
                    ->where('year', $index->year)
                    ->value('value');

                $EUwideIndicatorsData['IndicatorID'][] = $EUwideIndicator->identifier;
                $EUwideIndicatorsData['IndicatorName'][] = $EUwideIndicator->name;
                $EUwideIndicatorsData['IndicatorAlgorithm'][] = $EUwideIndicator->algorithm;
                $EUwideIndicatorsData['IndicatorValue'][] = $indicatorValue;
                $EUwideIndicatorsData['ReferenceYear'][] = $EUwideIndicator->report_year;
            }
        }

        $data['CSIndMetaData'] = $CSIndMetaData;
        $data['CSAggMetaData'] = $CSAggMetaData;
        $data['CSIndData'] = $CSIndData;
        $data['dna-scores-indicators-countries'] = $CSDNAData;
        $data['EUwideIndicators'] = $EUwideIndicatorsData;

        $response = IndexCalculationHelper::postCalculationRequest($data);
        $result = json_decode($response, true);

        if ($result['retval'] == 0)
        {
            $ref = $result['ref'];
            $data_path = self::INDEX_CALCULATION_PATH . '/' . $index->year . '/' . $index->id;
            $path = storage_path('app') . '/' . $data_path;

            File::ensureDirectoryExists($path);

            self::getCalculatedData($ref, $data_path, $path);
            self::importCalculationData($index, $path);
            self::updateReportingData($index, $path);
        }

        return $result;
    }

    public static function postCalculationRequest($data)
    {
        $ch = curl_init(env('CALC_MODULE_URL', 'http://calculator-module/calculate.php'));

        # Setup request to send json via POST
        $payload = json_encode($data);
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        # Return response instead of printing
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        # Send request
        $result = curl_exec($ch);
        Log::info($result);
        curl_close($ch);

        return $result;
    }

    public static function getCalculatedData($ref, $data_path, $path)
    {
        $file = $path . '/calculation.zip';

        system("curl http://calculator-module/return.php?ref=$ref --output $file");

        $zip = new ZipArchive;
        $res = $zip->open($file);

        if ($res === true)
        {
            $zip->extractTo($path);
            $zip->close();
        }

        $current_data = $data_path . '/' . $ref;
        $destination_data = $data_path . '/files/';

        foreach (Storage::files($current_data) as $file) {
            Storage::copy($file, $destination_data . basename($file));
        }

        File::copyDirectory($path . '/' . $ref . '/results', $path . '/results');
    }

    public static function importCalculationData($index, $path)
    {
        if (file_exists($path . '/results/' . self::EUCSI_RESULTS)) {
            Excel::import(new IndexDataImport($index), $path . '/results/' . self::EUCSI_RESULTS);
        }
    }

    public static function updateReportingData($index, $path)
    {
        $file = $path . '/' . self::JSON_FILES_PATH . '/' . config('constants.EU_AVERAGE_CODE') . '.json';
        if (file_exists($file))
        {
            $baseline_index = BaselineIndex::where('index_configuration_id', $index->id)->first();

            $baseline_index->report_json = json_decode(file_get_contents($file), true);
            $baseline_index->report_date = now();
            $baseline_index->save();
        }

        $indices = Index::where('index_configuration_id', $index->id)->get();
        foreach ($indices as $index)
        {
            $file = $path . '/' . self::JSON_FILES_PATH . '/' . $index->country->code . '.json';

            if (file_exists($file))
            {
                $index->report_json = json_decode(file_get_contents($file), true);
                $index->report_date = now();
                $index->save();
            }
        }
    }
}
