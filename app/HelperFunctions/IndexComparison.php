<?php

namespace App\HelperFunctions;

use App\Models\BaselineIndex;
use App\Models\IndexConfiguration;
use App\Models\Index;
use App\Models\Indicator;
use Illuminate\Support\Facades\Auth;

class IndexComparison
{
    const SELECT_ALL = 'Select All';
    const ALMOST_WHITE = '#adb5bd';

    public static function isDataAvailable($index)
    {
        $baseline_indices = BaselineIndex::where('index_configuration_id', $index->id)->get()->toArray();
        $indices = Index::whereIn('country_id', UserPermissions::getUserCountries())->where('index_configuration_id', $index->id)->get()->toArray();

        return (!empty($baseline_indices) && !empty($indices));
    }

    public static function mergeIndexData($indexA, $indexB)
    {
        if (empty($indexA)) {
            return ($indexB) ? $indexB->json_data['contents'] : null;
        }
        if (!$indexB) {
            return $indexA;
        }

        $arrayB = $indexB->json_data['contents'];

        array_push($indexA[0]['global_index_values'], $arrayB[0]['global_index_values'][0]);

        foreach ($indexA as $areaKey => $area)
        {
            if ($areaKey == 0) {
                continue;
            }

            array_push($indexA[$areaKey]['area']['values'], $arrayB[$areaKey]['area']['values'][0]);

            foreach ($area['area']['subareas'] as $subKey => $subarea)
            {
                if (isset($arrayB[$areaKey]['area']['subareas'][$subKey]['values'])) {
                    array_push($indexA[$areaKey]['area']['subareas'][$subKey]['values'], $arrayB[$areaKey]['area']['subareas'][$subKey]['values'][0]);
                }
                
                foreach ($subarea['indicators'] as $indiKey => $indicator)
                {
                    if (isset($indexB[$areaKey]['area']['subareas'][$subKey]['indicators'][$indiKey]['values'])) {
                        array_push($indexA[$areaKey]['area']['subareas'][$subKey]['indicators'][$indiKey]['values'], $arrayB[$areaKey]['area']['subareas'][$subKey]['indicators'][$indiKey]['values'][0]);
                    }
                }
            }
        }

        return $indexA;
    }

    public static function prepareIndexConfigurationForTree($configuration, $disableSelectAll = false)
    {
        $finalArray = [];

        if (gettype($configuration) == 'string') {
            $configuration = json_decode($configuration, true);
        }
        if (!empty($configuration)) {
            $finalArray = self::addAreasNodesToTree($finalArray, $configuration, $disableSelectAll);
        }

        $finalJson = str_replace('name', 'text', json_encode(array_values($finalArray)));
        $finalJson = str_replace('subareas', 'children', $finalJson);
        $finalJson = str_replace('indicators', 'children', $finalJson);

        return json_decode($finalJson);
    }

    public static function addAreasNodesToTree($finalArray, $configuration, $disableSelectAll)
    {
        foreach ($configuration['contents'] as $areaKey => $area) {
            if (!$disableSelectAll) {
                $finalArray[0]['id'] = 0;
                $finalArray[0]['text'] = self::SELECT_ALL;
            }
            if (!isset($area['area'])) {
                continue;
            }
            $finalArray[$areaKey + 1]['id'] =  $areaKey + 1;
            $finalArray[$areaKey + 1]['text'] = $area['area']['name'];
            $finalArray[$areaKey + 1]['idx'] = $area['area']['id'];
            $finalArray[$areaKey + 1]['checked'] = true;
            if (!$disableSelectAll) {
                $finalArray[$areaKey + 1]['subareas'][0]['id'] = $areaKey + 1 . '-' . 0;
                $finalArray[$areaKey + 1]['subareas'][0]['name'] = self::SELECT_ALL;
                $finalArray[$areaKey + 1]['subareas'][0]['checked'] = false;
            }
            if (!isset($area['area']['subareas'])) {
                continue;
            }
            $finalArray = self::addSubareaNodesToTree($finalArray, $area, $areaKey, $disableSelectAll);
        }
        return $finalArray;
    }

    public static function addSubareaNodesToTree($finalArray, $area, $areaKey, $disableSelectAll)
    {
        foreach ($area['area']['subareas'] as $subKey => $subarea) {
            if (!isset($subarea['name'])) {
                continue;
            }
            $finalArray[$areaKey + 1]['subareas'][$subKey + 1]['id'] = $areaKey + 1 . '-' . $subKey + 1;
            $finalArray[$areaKey + 1]['subareas'][$subKey + 1]['idx'] = $subarea['id'];
            $finalArray[$areaKey + 1]['subareas'][$subKey + 1]['text'] = $subarea['name'];
            if (!$disableSelectAll) {
                $finalArray[$areaKey + 1]['subareas'][$subKey + 1]['indicators'][0]['id'] = $areaKey + 1 . '-' . $subKey + 1 . '-' . 0;
                $finalArray[$areaKey + 1]['subareas'][$subKey + 1]['indicators'][0]['text'] = self::SELECT_ALL;
            }
            if (!isset($subarea['indicators'])) {
                continue;
            }
            $finalArray = self::addIndicatorNodesToTree($finalArray, $areaKey, $subarea, $subKey);
        }
        return $finalArray;
    }

    public static function addIndicatorNodesToTree($finalArray, $areaKey, $subarea, $subKey)
    {
        foreach ($subarea['indicators'] as $indiKey => $indicator) {
            if (!isset($indicator['name'])) {
                continue;
            }
            $finalArray[$areaKey + 1]['subareas'][$subKey + 1]['indicators'][$indiKey + 1]['id'] = $areaKey + 1 . '-' . $subKey + 1 . '-' . $indiKey + 1;
            $finalArray[$areaKey + 1]['subareas'][$subKey + 1]['indicators'][$indiKey + 1]['text'] = $indicator['name'];
            $finalArray[$areaKey + 1]['subareas'][$subKey + 1]['indicators'][$indiKey + 1]['idx'] = $indicator['id'];
        }
        return $finalArray;
    }

    public static function getUserCountry($index, $indices)
    {
        return (!Auth::user()->isAdmin() && count($indices)) ? trim(str_replace($index->year, '', $indices[0]->name)) : '';
    }

    public static function getInitialDataForComparison($year)
    {
        $indices = UserPermissions::getUserAvailableIndicesByYear($year);
        $configurationEntity = IndexConfiguration::getExistingPublishedConfigurationForYear($year);

        $configurationJson = $configurationEntity->json_data;
        $configuration = self::prepareIndexConfigurationForTree($configurationJson);
        foreach ($indices as $index) {
            $index->json_data = null;
        }

        return [
            'euAverageName' => config('constants.EU_AVERAGE_NAME'),
            'countries' => self::splitCountriesToGroups($indices),
            'configurationEntity' => $configurationEntity,
            'indices' => $indices,
            'configuration' => [
                'id' => -1,
                'text' => $configurationEntity->name,
                'year' => $configurationEntity->year,
                'children' => $configuration,
                'checked' => false
            ],
            'userCountry' => self::getUserCountry($configurationEntity, $indices)
        ];
    }

    public static function loadSunburstData($year)
    {
        $sunburstData = null;

        $configurationEntity = IndexConfiguration::getExistingPublishedConfigurationForYear($year);
        $indices = UserPermissions::getUserAvailableIndicesByYear($year);

        foreach ($indices as $index) {
            $sunburstData[$index->country->name . ' ' . $index->configuration->year] = self::getSunburstData($index);
        }

        if ($configurationEntity)
        {
            $baselineIndex = BaselineIndex::where('index_configuration_id', $configurationEntity->id)->first();
            if ($baselineIndex) {
                $sunburstData[config('constants.EU_AVERAGE_NAME') . ' ' . $baselineIndex->configuration->year] = self::getSunburstData($baselineIndex, true, config('constants.EU_AVERAGE_NAME'));
            }
            $sunburstData['algorithms'] = self::getIndicatorAlgorithms();
        }

        return $sunburstData;
    }

    public static function getIndexDataForComparison($node, $year)
    {
        $chartData = null;
        $mapData = null;

        $indices = UserPermissions::getUserAvailableIndicesByYear($year);
        $configurationEntity = IndexConfiguration::getExistingPublishedConfigurationForYear($year);
        $userCountry = self::getUserCountry($configurationEntity, $indices);
        $configuration = self::prepareIndexConfigurationForTree($configurationEntity->json_data);

        foreach ($indices as $index)
        {
            $chartData = self::mergeIndexData($chartData, $index);
            $index->json_data = null;
        }

        $baselineIndex = BaselineIndex::where('index_configuration_id', $configurationEntity->id)->first();
        if ($baselineIndex)
        {
            $chartData = self::mergeIndexData($chartData, $baselineIndex);
            $mapData = self::getMapData($chartData, $baselineIndex->configuration->year, $node, $userCountry);
        }

        return [
            'euAverageName' => config('constants.EU_AVERAGE_NAME'),
            'countries' => self::splitCountriesToGroups($indices),
            'indices' => $indices,
            'configuration' => [
                'id' => -1,
                'text' => $configurationEntity->name,
                'year' => $configurationEntity->year,
                'eu_published' => $configurationEntity->eu_published,
                'ms_published' => $configurationEntity->ms_published,
                'children' => $configuration,
                'checked' => false
            ],
            'mapData' => $mapData,
            'chartData' => $chartData,
            'isAdmin' => Auth::user()->isAdmin(),
            'isEnisa' => Auth::user()->isEnisa()
        ];
    }

    public static function getMapData($chartData, $year, $node, $userCountry)
    {
        $mapData = [];
        $all_countries =
            [config('constants.EU_AVERAGE_NAME') => config('constants.EU_AVERAGE_NAME')] +
            config('constants.EU_COUNTRIES') +
            config('constants.NON_EU_COUNTRIES');
        $cyprusValue = $cyprusChildren = $countryValue = null;

        foreach ($all_countries as $country)
        {
            $value = null;
            if ($node == 'Index')
            {
                foreach ($chartData[0]['global_index_values'] as $mapValue)
                {
                    $countryName = trim(str_replace($year, '', array_key_first($mapValue)));
                    if ($countryName == $country)
                    {
                        $value = array_values($mapValue)[0];
                        if (!$userCountry) break;

                        if ($countryName == $userCountry)
                        {
                            $countryValue = $value;

                            break;
                        }
                    }
                }
            }
            else
            {
                $nodeArray = explode('-', $node);
                $nodeType = $nodeArray[0];
                $nodeId = $nodeArray[1];

                foreach ($chartData as $key => $area)
                {
                    if ($key == 0) {
                        continue;
                    }

                    if ($nodeType == "area" && $nodeId == $area['area']['id'])
                    {
                        foreach ($area['area']['values'] as $mapValue)
                        {
                            $countryName = trim(str_replace($year, '', array_key_first($mapValue)));
                            if ($countryName == $country)
                            {
                                $value = array_values($mapValue)[0];
                                if (!$userCountry) break;

                                if ($countryName == $userCountry)
                                {
                                    $countryValue = $value;

                                    break;
                                }
                            }
                        }
                    }
                    else
                    {
                        foreach ($area['area']['subareas'] as $subarea)
                        {
                            if ($nodeId == $subarea['id'])
                            {
                                foreach ($subarea['values'] as $mapValue)
                                {
                                    $countryName = trim(str_replace($year, '', array_key_first($mapValue)));
                                    if ($countryName == $country)
                                    {
                                        $value = array_values($mapValue)[0];
                                        if (!$userCountry) break;

                                        if ($countryName == $userCountry)
                                        {
                                            $countryValue = $value;

                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $children = self::fillChildrenForMap($chartData, $year, $country, $node, $userCountry);

            if ($country == 'Cyprus')
            {
                $cyprusValue = $value;
                $cyprusChildren = $children;
            }
            if ($country == 'N. Cyprus')
            {
                $value = $cyprusValue;
                $children = $cyprusChildren;
            }

            $label = [];
            if ($country == 'Cyprus') {
                $label = ['offset' => [0, 14]];
            }
            elseif ($country == 'Malta') {
                $label = ['offset' => [0, 10]];
            }
            elseif ($country == 'N. Cyprus') {
                $label = ['show' => false];
            }
            if (!$value) {
                $label = ['backgroundColor' => '', 'borderWidth' => 0];
            }

            $mapData[] = [
                'name' => $country,
                'selection_name' => $country . ' ' . $year,
                'value' => $value,
                'emphasis' => (!$value) ? ['disabled' => true] : null,
                'label' => $label,
                'children' => $children
            ];
        }
        if ($countryValue) {
            $mapData[0]['country_value'] = $countryValue;
        }

        return $mapData;
    }

    public static function fillChildrenForMap($chartData, $year, $country, $node = "Index", $userCountry)
    {
        $children = [];
        foreach ($chartData as $key => $area) {
            if ($key > 0) {
                if ($node == 'Index') {
                    $name = $value = $countryValue = null;
                    foreach ($area['area']['values'] as $val) {
                        $countryName = trim(str_replace($year, '', array_key_first($val)));
                        if ($countryName == $country) {
                            $name = $area['area']['name'];
                            $value = array_values($val)[0];
                            if (!$userCountry) break;
                        }
                        if ($countryName == $userCountry) {
                            $countryValue = array_values($val)[0];
                            if (!is_null($name) && !is_null($value)) break;
                        }
                    }
                    if (!is_null($name) && !is_null($value)) {
                        $children[] =
                            [
                                'name' => $name,
                                'value' => $value,
                                'country_value' => $countryValue
                            ];
                    }
                } else {
                    $nodeArray = explode('-', $node);
                    $nodeType = $nodeArray[0];
                    $nodeId = $nodeArray[1];
                    if ($nodeType == "area" && $nodeId == $area['area']['id']) {
                        foreach ($area['area']['subareas'] as $subarea) {
                            $name = $value = $countryValue = null;
                            foreach ($subarea['values'] as $val) {
                                $countryName = trim(str_replace($year, '', array_key_first($val)));
                                if ($countryName == $country) {
                                    $name = $subarea['name'];
                                    $value = array_values($val)[0];
                                    if (!$userCountry) break;
                                }
                                if ($countryName == $userCountry) {
                                    $countryValue = array_values($val)[0];
                                    if (!is_null($name) && !is_null($value)) break;
                                }
                            }
                            if (!is_null($name) && !is_null($value)) {
                                $children[] =
                                    [
                                        'name' => $name,
                                        'value' => $value,
                                        'country_value' => $countryValue
                                    ];
                            }
                        }
                    } else {
                        foreach ($area['area']['subareas'] as $subarea) {
                            if ($nodeId == $subarea['id']) {
                                foreach ($subarea['indicators'] as $indicator) {
                                    $name = $value = $countryValue = null;
                                    foreach ($indicator['values'] as $val) {
                                        $countryName = trim(str_replace($year, '', array_key_first($val)));
                                        if ($countryName == $country) {
                                            $name = $indicator['name'];
                                            $value = array_values($val)[0];
                                            if (!$userCountry) break;
                                        }
                                        if ($countryName == $userCountry) {
                                            $countryValue = array_values($val)[0];
                                            if (!is_null($name) && !is_null($value)) break;
                                        }
                                    }
                                    if (!is_null($name) && !is_null($value)) {
                                        $children[] =
                                            [
                                                'name' => $name,
                                                'value' => $value,
                                                'country_value' => $countryValue
                                            ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $children;
    }

    public static function value2color($value)
    {
        switch ($value) {
            case ($value == '#NUM!'):
                return self::ALMOST_WHITE;
            case ($value < 20):
                return '#f5f7fd';
            case ($value < 40):
                return '#d6dff6';
            case ($value < 60):
                return '#8ea7e6';
            case ($value < 80):
                return '#3a65d3';
            case ($value <= 100):
                return '#254AA5';
            default:
                return self::ALMOST_WHITE;
        }
    }

    public static function lineargradient($ra, $ga, $ba, $rz, $gz, $bz, $iterationnr)
    {
        $colorindex = array();
        for ($iterationc = 1; $iterationc <= $iterationnr; $iterationc++) {
            $iterationdiff = $iterationnr - $iterationc;
            $colorindex[] = '#' .
                dechex(intval((($ra * $iterationc) + ($rz * $iterationdiff)) / $iterationnr)) .
                dechex(intval((($ga * $iterationc) + ($gz * $iterationdiff)) / $iterationnr)) .
                dechex(intval((($ba * $iterationc) + ($bz * $iterationdiff)) / $iterationnr));
        }
        return $colorindex;
    }

    public static function getIndicatorAlgorithms()
    {
        $indicators = [];
        $indicatorCollection = Indicator::all();
        foreach ($indicatorCollection as $indicator) {
            $indicators[$indicator->name] = $indicator->algorithm;
        }

        return $indicators;
    }

    public static function getSunburstData($index, $baselineFlag = false, $baselineName = false)
    {
        $colorindex = self::lineargradient(
            37,
            74,
            165, // rgb of the start color

            245,
            247,
            253, // rgb of the end color

            101  // number of colors in your linear gradient
        );

        $indexCountryName = ($baselineFlag ? $baselineName  :  $index->country->name) . " " . $index->configuration->year;
        $sunbBurstArray = [
            'name' => $indexCountryName,
            'fullName' => $indexCountryName,
            'val' => 0,
            'value' => 0,
            'children' => []
        ];

        foreach ($index->json_data['contents'] as $key => $row) {
            if ($key == 0) {
                $sunbBurstArray['value'] = 1;
                $sunbBurstArray['val'] =  array_values($row['global_index_values'][0])[0];
                $sunbBurstArray['itemStyle'] = [
                    'color' => $colorindex[round($sunbBurstArray['val'])]
                ];
            } else {
                $value = '#NUM!';
                if (isset($row['area']['values'])) {
                    $value = array_values($row['area']['values'][0])[0];
                }
                $areaArray = [
                    'name' => $row['area']['name'],
                    'fullName' => $row['area']['name'],
                    'val' => ($value == '#NUM!') ? 'Not available' : $value,
                    'value' => $row['area']['normalized_weight'],
                    'weight' => round($row['area']['weight'], 2),
                    'itemStyle' => [
                        'color' =>  self::value2color($value)
                    ],
                    'children' => []
                ];

                foreach ($row['area']['subareas'] as $subarea) {
                    $value = '#NUM!';
                    if (isset($subarea['values'])) {
                        $value = array_values($subarea['values'][0])[0];
                    }
                    $subareaArray = [
                        'name' => $subarea['short_name'],
                        'fullName' => $subarea['name'],
                        'val' => ($value == '#NUM!') ? 'Not available' : $value,
                        'value' => $subarea['normalized_weight'],
                        'weight' => round($subarea['weight'], 2),
                        'itemStyle' => [
                            'color' => self::value2color($value)
                        ],
                        'children' => []
                    ];

                    foreach ($subarea['indicators'] as $indicator) {
                        $value = '#NUM!';
                        if (isset($indicator['values'])) {
                            $value = array_values($indicator['values'][0])[0];
                        }
                        $indicatorArray = [
                            'name' => $indicator['short_name'],
                            'fullName' => $indicator['name'],
                            'val' => ($value == '#NUM!') ? 'Not available' : $value,
                            'value' => $subarea['normalized_weight'] / count($subarea['indicators']), //temporary solution till actual weights are fixed
                            'algorithm' => '',
                            'itemStyle' => [
                                'color' => self::value2color($value)
                            ],
                            'weight' => round($indicator['weight'], 2),
                            'nodeClick' => 'false'

                        ];
                        $subareaArray['children'][] =  $indicatorArray;
                    }
                    $areaArray['children'][] =  $subareaArray;
                }
                $sunbBurstArray['children'][] =  $areaArray;
            }
        }

        return $sunbBurstArray;
    }

    public static function splitCountriesToGroups($indices)
    {
        $indicesArray = [
            'top' => [],
            'rest' => []
        ];
        foreach ($indices as $index) {

            if ($index->top) {
                $indicesArray['top'][] = $index;
            } else {
                $indicesArray['rest'][] = $index;
            }
        }
        return $indicesArray;
    }
}
