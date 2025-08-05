<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\HelperFunctions\UserSessionHelper;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        UserSessionHelper::logout($request);        

        return view('components.logout');
    }
}
