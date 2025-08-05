<?php

namespace App\Http\Controllers;

use App\HelperFunctions\IndexComparison;
use App\HelperFunctions\IndexYearChoiceHelper;
use App\Models\IndexConfiguration;

class IndexComparisonController extends Controller
{
    public function view($year = null)
    {
        if (is_null($year))
        {
            $data['years'] = IndexYearChoiceHelper::getIndexYearChoices();

            return view('index.comparison', $data);
        }
        else
        {
            $data = IndexComparison::getInitialDataForComparison($year);
            $data['dataAvailable'] = IndexComparison::isDataAvailable($data['configurationEntity']);

            return view('index.comparison-data', $data);
        }
    }

    public function indices($node, $year)
    {
        $data = IndexComparison::getIndexDataForComparison($node, $year);

        return response()->json($data, 200);
    }

    public function getSunburstData()
    {
        $loaded_index_data = IndexConfiguration::getLoadedPublishedConfiguration();

        $data = IndexComparison::loadSunburstData($loaded_index_data->year);

        return response()->json($data, 200);
    }

    public function renderSliderChart($year)
    {
        $data = IndexComparison::getIndexDataForComparison('Index', $year);

        return view('ajax.index-comparison', ['data' => $data]);
    }
}
