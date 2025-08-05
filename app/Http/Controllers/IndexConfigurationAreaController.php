<?php

namespace App\Http\Controllers;

use App\HelperFunctions\IndexConfigurationAreaHelper;
use App\Models\Area;
use App\Models\IndexConfiguration;
use Illuminate\Http\Request;

class IndexConfigurationAreaController extends Controller
{
    public function list()
    {
        $year = $_COOKIE['index-year'];
        
        return response()->json(['data' => Area::getAreas($year)], 200);
    }

    public function createOrShowArea($data = null)
    {
        return view('ajax.index-configuration-area-management', ['selected_area' => $data]);
    }

    public function storeArea(Request $request)
    {
        $inputs = $request->all();
                
        $validator = IndexConfigurationAreaHelper::validateInputsForCreate($inputs);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        IndexConfigurationAreaHelper::storeArea($inputs);
        
        IndexConfiguration::updateDraftIndexConfigurationJsonData($_COOKIE['index-year']);

        return response()->json('ok', 200);
    }

    public function showArea(Area $area)
    {
        $data = Area::getArea($area->id);

        return $this->createOrShowArea($data);
    }

    public function updateArea(Request $request, Area $area)
    {
        $inputs = $request->all();
        $inputs['id'] = $area->id;

        $validator = IndexConfigurationAreaHelper::validateInputsForEdit($inputs);
        if( $validator->fails() ) {
            return response()->json($validator->messages(), 400);
        }

        IndexConfigurationAreaHelper::updateArea($inputs);

        IndexConfiguration::updateDraftIndexConfigurationJsonData($_COOKIE['index-year']);

        return response()->json('ok', 200);
    }

    public function deleteArea(Area $area)
    {
        if (!IndexConfigurationAreaHelper::deleteArea($area->id)) {
            return response()->json(['error' => 'Area cannot be deleted as it is used by subareas.'], 405);
        }

        IndexConfiguration::updateDraftIndexConfigurationJsonData($_COOKIE['index-year']);

        return response()->json('ok', 200);
    }
}
