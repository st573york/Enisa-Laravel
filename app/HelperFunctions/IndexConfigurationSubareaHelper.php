<?php

namespace App\HelperFunctions;

use App\Models\Subarea;
use Illuminate\Validation\Rule;

class IndexConfigurationSubareaHelper
{
    public static function validateInputsForCreate($inputs)
    {
        return validator($inputs,
        [
            'name' => ['required', Rule::unique('subareas')->where('year', $_COOKIE['index-year'])],
            'default_area_id' => 'required',
            'default_weight' => 'required|numeric'
        ],
        [
            'default_area_id.required' => 'The area field is required.',
            'default_weight.required' => 'The weight field is required.',
            'default_weight.numeric' => 'The weight field must be a number.'
        ]);
    }

    public static function validateInputsForEdit($inputs)
    {
        return validator($inputs,
        [
            'name' => ['required', Rule::unique('subareas')->where('year', $_COOKIE['index-year'])->ignore($inputs['id'])],
            'default_area_id' => 'required',
            'default_weight' => 'required|numeric'
        ],
        [
            'default_area_id.required' => 'The area field is required.',
            'default_weight.required' => 'The weight field is required.',
            'default_weight.numeric' => 'The weight field must be a number.'
        ]);
    }

    public static function getSubareaData($inputs)
    {
        foreach ($inputs as $key => $value) {
            $inputs[$key] = $value;
        }
        
        $inputs['year'] = $_COOKIE['index-year'];

        return $inputs;
    }

    public static function storeSubarea($inputs)
    {
        $data = self::getSubareaData($inputs);
        $data['short_name'] = substr($inputs['name'], 0, 20);
        $data['identifier'] = Subarea::max('identifier') + 1;

        Subarea::create($data);
    }

    public static function updateSubarea($inputs)
    {
        $data = self::getSubareaData($inputs);

        Subarea::find($data['id'])->update($data);
    }

    public static function deleteSubarea($id)
    {
        $subarea = Subarea::getSubarea($id);
        
        if (!$subarea->default_indicator->count()) {
            return $subarea->delete();
        }

        return false;
    }
}