<?php

namespace App\HelperFunctions;

use App\Models\BaselineIndex;
use App\Models\IndexConfiguration;

class IndexReportsHelper
{
    public static function getReportChartData($index)
    {
        $reportData = $index->report_json[0];
        $countryName = $index->country->name;
        $chartData = [];

        foreach ($reportData['areas'] as $idx => $area)
        {
            $chartData['area-' . $idx] = [
                'name' => $countryName,
                'indicator' => [],
                'country' => [],
                'eu' => []
            ];

            foreach ($area['subareas'] as $subarea)
            {
                $chartData['area-' . $idx]['indicator'][] =
                    [
                        'name' => wordwrap($subarea['name'], 20, "\n"),
                        'nameTextStyle' => [
                        'fontSize' => 12,
                        'height' => 100
                    ],
                    'max' => 100
                ];
                $chartData['area-' . $idx]['country'][] = $subarea['scores']['country'];
                $chartData['area-' . $idx]['eu'][] = $subarea['scores']['euAverage'];
            }
        }

        return $chartData;
    }

    public static function getIndiceReports($loaded_index_data)
    {
        $indices = UserPermissions::getUserAvailableIndicesByYear($loaded_index_data->year);
        $indices = $indices->filter(function ($index) {
            return !empty($index->report_json);
        });

        return $indices->map(function ($index) {
            $country = $index->country;
            $year = $index->configuration->year;

            return [
                'id' => $index->id,
                'country_id' => $country->id,
                'value' => $country->name,
                'year' => $year
            ];
        })->groupBy('year')->map(function ($yearData) {
            return $yearData->unique('value')->sortBy('value')->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'country_id' => $item['country_id'],
                    'value' => $item['value']
                ];
            })->values()->toArray();
        })->toArray();
    }

    public static function getEUReportChartData()
    {
        $loaded_index_data = IndexConfiguration::getLoadedPublishedConfiguration();
        $baseline_index = BaselineIndex::where('index_configuration_id', $loaded_index_data->id)->first();

        $reportData = $baseline_index->report_json[0];
        $chartData = [];

        foreach ($reportData['areas'] as $idx => $area)
        {
            $chartData['area-' . $idx] = [
                'indicator' => [],
                'eu' => []
            ];

            foreach ($area['subareas'] as $subarea)
            {
                $chartData['area-' . $idx]['indicator'][] =
                    [
                        'name' => wordwrap($subarea['name'], 20, "\n"),
                        'nameTextStyle' => [
                        'fontSize' => 12,
                        'height' => 100
                    ],
                    'max' => 100
                ];
                $chartData['area-' . $idx]['eu'][] = $subarea['scores'][0]['euAverage'];
            }
        }

        return $chartData;
    }
}
