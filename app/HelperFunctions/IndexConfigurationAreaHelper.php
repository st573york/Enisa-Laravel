<?php

namespace App\HelperFunctions;

use App\Models\Area;
use Illuminate\Validation\Rule;

class IndexConfigurationAreaHelper
{
    public static function validateInputsForCreate($inputs)
    {
        return validator($inputs,
        [
            'name' => ['required', Rule::unique('areas')->where('year', $_COOKIE['index-year'])],
            'default_weight' => 'required|numeric'
        ],
        [
            'default_weight.required' => 'The weight field is required.',
            'default_weight.numeric' => 'The weight field must be a number.'
        ]);
    }

    public static function validateInputsForEdit($inputs)
    {
        return validator($inputs,
        [
            'name' => ['required', Rule::unique('areas')->where('year', $_COOKIE['index-year'])->ignore($inputs['id'])],
            'default_weight' => 'required|numeric'
        ],
        [
            'default_weight.required' => 'The weight field is required.',
            'default_weight.numeric' => 'The weight field must be a number.'
        ]);
    }

    public static function getAreaData($inputs)
    {
        foreach ($inputs as $key => $value) {
            $inputs[$key] = $value;
        }

        $inputs['default'] = true;
        $inputs['year'] = $_COOKIE['index-year'];

        return $inputs;
    }

    public static function storeArea($inputs)
    {
        $data = self::getAreaData($inputs);
        $data['identifier'] = Area::max('identifier') + 1;

        Area::create($data);
    }

    public static function updateArea($inputs)
    {
        $data = self::getAreaData($inputs);

        Area::find($data['id'])->update($data);
    }

    public static function deleteArea($id)
    {
        $area = Area::getArea($id);
        
        if (!$area->default_subarea->count()) {
            return $area->delete();
        }

        return false;
    }
}