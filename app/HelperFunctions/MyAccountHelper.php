<?php

namespace App\HelperFunctions;

use App\Notifications\NotifyUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MyAccountHelper
{
    public static function validateInputs($inputs)
    {
        return validator($inputs,
        [
            'name' => 'prohibited',
            'email' => 'prohibited',
            'country_code' => 'required'
        ],
        [
            'name' => 'Name can\'t be updated.',
            'email' => 'Email can\'t be updated.',
            'country_code' => 'The country field is required.'
        ]
        );
    }

    public static function updateUserDetails($data)
    {
        $user = Auth::user();
        $dbCountryCode = $user->country_code;
        $user->update(['country_code' => $data['country_code']]);
        
        if ($user->blocked && !$dbCountryCode)
        {
            $data['role_id'] = 2;
            $countryPoCs = User::getUsersByCountryAndRole($data);
        
            foreach ($countryPoCs as $countryPoC) {
                $countryPoC->user->notify(new NotifyUser([
                    'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                    'subject' => 'EU-CSI New User Registration',
                    'markdown' => 'user-registration',
                    'maildata' => [
                        'email' => $user->email
                    ]
                ]));
            }
        }
    }
}