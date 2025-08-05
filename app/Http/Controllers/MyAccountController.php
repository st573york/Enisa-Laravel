<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\HelperFunctions\MyAccountHelper;

class MyAccountController extends Controller
{
    public function view()
    {
        return view('components.my-account', ['data' => Auth::user(), 'countries' => config('constants.EU_COUNTRIES')]);
    }

    public function update(Request $request)
    {
        $inputs = $request->all();
        
        $validator = MyAccountHelper::validateInputs($inputs);
        if( $validator->fails() ) {
            return response()->json($validator->messages(), 400);
        }
        
        MyAccountHelper::updateUserDetails($inputs);
      
        return response()->json(['success' => 'User details have been successfully updated!'], 200);
    }
}
