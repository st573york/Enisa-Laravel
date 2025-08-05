<?php

namespace App\HelperFunctions;

use App\Models\Country;
use App\Models\Invitation;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InvitationHelper
{
    const ERROR_NOT_ALLOWED = 'User cannot be invited as the requested action is not allowed!';

    public static function validateInputsForCreate($inputs)
    {
        return validator(
            $inputs,
            [
                'firstname' => 'required',
                'lastname' => 'required',
                'email' => ['required', Rule::unique('users')->whereNull('deleted_at'), 'email:rfc,dns'],
                'country' => 'required',
                'role' => 'required'
            ],
            [
                'firstname.required' => 'The first name field is required.',
                'lastname.required' => 'The last name field is required.',
                'email.unique' => 'The email is already registered.'
            ]
        );
    }

    public static function canInviteUser($inputs)
    {
        $countryName = $inputs['country'];
        $roleName = $inputs['role'];

        $availableCountries = Country::pluck('name')->toArray();
        $availableRoles = Role::pluck('name')->toArray();
        
        if (!in_array($countryName, $availableCountries) ||
            !in_array($roleName, $availableRoles))
        {
            return [
                'type' => 'error',
                'msg' => self::ERROR_NOT_ALLOWED,
                'status' => 405
            ];
        }

        if (($countryName != config('constants.USER_GROUP') && $roleName == 'admin') ||       // Admin/Primary PoC invites user to COUNTRY/admin
            ($countryName == config('constants.USER_GROUP') && $roleName == 'Primary PoC') || // Admin/Primary PoC invites user to ENISA/Primary PoC
            ($countryName == config('constants.USER_GROUP') && $roleName == 'PoC') ||         // Admin/Primary PoC invites user to ENISA/PoC
            ($countryName == config('constants.USER_GROUP') && $roleName == 'operator'))      // Admin/Primary PoC invites user to ENISA/operator
        {
            return [
                'type' => 'error',
                'msg' => self::ERROR_NOT_ALLOWED,
                'status' => 405
            ];
        }

        if ((Auth::user()->isPrimaryPoC() && $countryName != Auth::user()->permissions->first()->country->name) || // Primary PoC invites user to country other than PoC's country
            (Auth::user()->isPrimaryPoC() && $roleName == 'admin') ||                                              // Primary PoC invites user to admin
            (Auth::user()->isPrimaryPoC() && $roleName == 'Primary PoC'))                                          // Primary PoC invites user to Primary PoC
        {
            return [
                'type' => 'error',
                'msg' => 'User cannot be invited as you are not authorized for this action!',
                'status' => 403
            ];
        }

        return [
            'type' => 'success',
            'msg' => 'User can be successfully invited!'
        ];
    }

    public static function getInvitationData($data)
    {
        $role = Role::where('name', $data['role'])->first();
        $country = Country::where('name', $data['country'])->first();
        $registration_deadline = env('REGISTRATION_DEADLINE', 48);

        return [
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $role->id,
            'country_id' => $country->id,
            'invited_by' => Auth::user()->id,
            'invited_at' => Carbon::now(),
            'expired_at' => date('Y-m-d H:i:s', strtotime('now +' . $registration_deadline . ' hours')),
            'status_id' => 1,
            'hash' => hash('sha256', Str::random(16) . $data['email'])
        ];
    }

    public static function storeInvitation($data)
    {
        return Invitation::create(self::getInvitationData($data));
    }

    public static function checkInvitation($invitation, $hash)
    {
        if ($invitation->hash != $hash) {
            return [
                'type' => 'error',
                'view' => 'components.auth-failed'
            ];
        }
        elseif (strtotime($invitation->expired_at) < strtotime(Carbon::now()))
        {
            self::markInvitationExpired($invitation);

            return [
                'type' => 'error',
                'view' => 'components.registration-expired'
            ];
        }

        return [
            'type' => 'success',
            'view' => null
        ];
    }

    public static function markInvitationRegistered($invitation)
    {
        Invitation::disableAuditing();
        Invitation::find($invitation->id)->update([
            'registered_at' =>  Carbon::now(),
            'status_id' => 2
        ]);
        Invitation::enableAuditing();
    }

    public static function markInvitationExpired($invitation)
    {
        Invitation::disableAuditing();
        Invitation::find($invitation->id)->update([
            'status_id' => 3
        ]);
        Invitation::enableAuditing();
    }
}