<?php

namespace App\HelperFunctions;

use App\Models\BaselineIndex;
use App\Models\Country;
use App\Models\Index;

class IndexDataImportHelper
{
    public static function formatDataForSave($countryCode, $configuration, $countryData)
    {
        if ($countryCode == config('constants.EU_AVERAGE_CODE')) {
            $indexName = config('constants.EU_AVERAGE_NAME') . ' ' . $configuration->year;
        }
        else
        {
            $country = Country::where('code', $countryCode)->first();
            $indexName = $country->name . ' ' . $configuration->year;
        }

        $indexJson = $configuration->json_data;
        $indexJson = self::addValuesToJson($indexJson, $indexName, $countryData);
        $globalIndexValues = [
            'global_index_values' => [[$indexName => $countryData['Index']]]
        ];

        array_splice($indexJson['contents'], 0, 0, [$globalIndexValues]);

        if ($countryCode == config('constants.EU_AVERAGE_CODE')) {
            BaselineIndex::updateOrCreate(
                ['index_configuration_id' => $configuration->id],
                ['name' => $indexName,  'description' => '', 'json_data' => $indexJson]
            );
        }
        else {
            Index::updateOrCreate(
                ['country_id' => $country->id, 'index_configuration_id' => $configuration->id],
                [ 'name' => $indexName, 'description' => '', 'status_id' => 2, 'country_id' => $country->id , 'json_data' => $indexJson]
            );
        }
    }

    public static function addValuesToJson($indexJson, $indexName, $countryData)
    {
        foreach ($indexJson['contents'] as &$areas)
        {
            foreach ($areas as &$area)
            {
                if (isset($countryData['AREA_' . $area['identifier']]))
                {
                    $area['values'] = [[$indexName => $countryData['AREA_' . $area['identifier']]]];
                    foreach ($area['subareas'] as &$subarea)
                    {
                        if (isset($countryData['SUBAREA_' . $subarea['identifier']]))
                        {
                            $subarea['values'] = [[$indexName => $countryData['SUBAREA_' . $subarea['identifier']]]];
                            foreach ($subarea['indicators'] as &$indicator)
                            {
                                if (isset($countryData['IND_' . $indicator['identifier']])) {
                                    $indicator['values'] = [[$indexName => $countryData['IND_' . $indicator['identifier']]]];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $indexJson;
    }

    public static function addNormalizedWeightsToJson($indexJson, $countryData)
    {
        $field = 'normalized_weight';

        foreach ($indexJson['contents'] as &$areas)
        {
            foreach ($areas as $areaKey => &$area)
            {
                if ($areaKey == 'global_index_values') {
                    continue;
                }

                if (isset($countryData['AREA_' . $area['identifier']]))
                {
                    $area[$field] = floatval($countryData['AREA_' . $area['identifier']]);
                    foreach ($area['subareas'] as &$subarea)
                    {
                        if (isset($countryData['SUBAREA_' . $subarea['identifier']]))
                        {
                            $subarea[$field] = floatval($countryData['SUBAREA_' . $subarea['identifier']]);
                            foreach ($subarea['indicators'] as &$indicator)
                            {
                                if (isset($countryData['IND_' . $indicator['identifier']])) {
                                    $indicator[$field] = floatval($countryData['IND_' . $indicator['identifier']]);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $indexJson;
    }
}
