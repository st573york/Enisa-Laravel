<?php

namespace App\Http\Controllers;

use App\HelperFunctions\IndexConfigurationSubareaHelper;
use App\Models\Area;
use App\Models\IndexConfiguration;
use App\Models\Subarea;
use Illuminate\Http\Request;

class IndexConfigurationSubareaController extends Controller
{
    public function list()
    {
        $year = $_COOKIE['index-year'];

        return response()->json(['data' => Subarea::getSubareas($year)], 200);
    }

    public function createOrShowSubarea($data = null)
    {
        $year = $_COOKIE['index-year'];

        return view('ajax.index-configuration-subarea-management', ['selected_subarea' => $data, 'areas' => Area::getAreas($year)]);
    }

    public function storeSubarea(Request $request)
    {
        $inputs = $request->all();

        $validator = IndexConfigurationSubareaHelper::validateInputsForCreate($inputs);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        IndexConfigurationSubareaHelper::storeSubarea($inputs);
        
        IndexConfiguration::updateDraftIndexConfigurationJsonData($_COOKIE['index-year']);

        return response()->json('ok', 200);
    }

    public function showSubarea(Subarea $subarea)
    {
        $data = Subarea::getSubarea($subarea->id);

        return $this->createOrShowSubarea($data);
    }

    public function updateSubarea(Request $request, Subarea $subarea)
    {
        $inputs = $request->all();
        $inputs['id'] = $subarea->id;

        $validator = IndexConfigurationSubareaHelper::validateInputsForEdit($inputs);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        IndexConfigurationSubareaHelper::updateSubarea($inputs);

        IndexConfiguration::updateDraftIndexConfigurationJsonData($_COOKIE['index-year']);

        return response()->json('ok', 200);
    }

    public function deleteSubarea(Subarea $subarea)
    {
        if (!IndexConfigurationSubareaHelper::deleteSubarea($subarea->id)) {
            return response()->json(['error' => 'Subarea cannot be deleted as it is used by indicators.'], 405);
        }

        IndexConfiguration::updateDraftIndexConfigurationJsonData($_COOKIE['index-year']);

        return response()->json('ok', 200);
    }
}
