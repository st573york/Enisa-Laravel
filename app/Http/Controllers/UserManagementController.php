<?php

namespace App\Http\Controllers;

use App\HelperFunctions\UserPermissions;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    const ERROR_NOT_AUTHORIZED = 'User cannot be deleted as you are not authorized for this action!';

    public function management()
    {
        return view('user.management');
    }

    public function list()
    {
        $users = User::getUsers();
        
        return response()->json(['data' => $users], 200);
    }

    public function userDetails($users)
    {
        $yourself = (count($users) == 1 && $users[0]->id == Auth::user()->id) ? true : false;

        return view('ajax.user-edit', [
            'users' => $users,
            'countries' => UserPermissions::getUserCountries('name'),
            'roles' => UserPermissions::getUserRoles('name', $yourself)
        ]);
    }

    public function getUser(User $user)
    {
        $user->country = Country::where('code', $user->country_code)->value('name');

        return $this->userDetails([$user]);
    }

    public function updateUser(Request $request, User $user)
    {
        $inputs = $request->all();
        $inputs['toggle'] = false;

        $validator = UserPermissions::validateInputsForEdit($inputs);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages(), 'type' => 'pageModalForm'], 400);
        }

        $dbPermissionCountryName = $user->permissions->first()->country['name'];
        $countryName = $inputs['country'];
        $dbPermissionRoleName = $user->permissions->first()->role['name'];
        $roleName = $inputs['role'];
        
        $resp = UserPermissions::canUpdateUser(
            $user,
            $dbPermissionCountryName,
            $countryName,
            $dbPermissionRoleName,
            $roleName);
        if ($resp['type'] == 'error') {
            return response()->json([$resp['type'] => $resp['msg']], $resp['status']);
        }

        $resp = UserPermissions::canUpdateUserStatus(
            $user,
            $inputs,
            $dbPermissionCountryName,
            $dbPermissionRoleName);
        if ($resp['type'] == 'error') {
            return response()->json([$resp['type'] => $resp['msg']], $resp['status']);
        }

        $resp = UserPermissions::canDowngradePrimaryPoC($countryName, $dbPermissionRoleName, $roleName);
        if ($resp['type'] == 'warning') {
            return response()->json([$resp['type'] => $resp['msg']], $resp['status']);
        }
        
        User::updateUserPermissions($user, $inputs);
        User::updateUserStatus($user, $inputs);

        // Logged in user?
        if ($user->id == Auth::user()->id)
        {
            // Downgrade to operator or viewer then redirect to home page
            if ($roleName == 'operator' ||
                $roleName == 'viewer')
            {
                return response()->json(['redirect' => '/'], 302);
            }
            // Downgrade to PoC then redirect to current page
            elseif ($dbPermissionRoleName == 'admin' &&
                    ($roleName == 'Primary PoC' ||
                     $roleName == 'PoC'))
            {
                return response()->json(['redirect' => '/user/management'], 302);
            }
        }

        // Update to Primary PoC then redirect to current page
        if (UserPermissions::canUpdateToPrimaryPoC($dbPermissionRoleName, $roleName)) {
            return response()->json(['redirect' => '/user/management'], 302);
        }
        
        return response()->json('ok', 200);
    }

    public function getUsers(Request $request)
    {
        $inputs = $request->all();

        $userIds = array_filter(explode(',', $inputs['users']));
        $users = User::whereIn('id', $userIds)->get();

        return $this->userDetails($users);
    }

    public function updateUsers(Request $request)
    {
        $inputs = $request->all();
        $inputs['toggle'] = false;
        
        $userIds = array_filter(explode(',', $inputs['datatable-selected']));
        $users = User::whereIn('id', $userIds)->get();

        $error = '';
        $status = 200;
        foreach ($users as $user)
        {
            $dbPermissionCountryName = ($user->permissions->first()) ? $user->permissions->first()->country['name'] : null;
            $dbPermissionRoleName = ($user->permissions->first()) ? $user->permissions->first()->role['name'] : null;

            $resp = UserPermissions::canUpdateUserStatus(
                $user,
                $inputs,
                $dbPermissionCountryName,
                $dbPermissionRoleName);
            if ($resp['type'] == 'error')
            {
                // Get only first error - skip the others if any
                if (empty($error))
                {
                    $error = $resp['msg'];
                    $status = $resp['status'];
                }

                continue;
            }
            
            User::updateUserStatus($user, $inputs);
        }

        if (!empty($error)) {
            return response()->json(['error' => $error], $status);
        }
        
        return response()->json('ok', $status);
    }

    public function toggleBlock(User $user)
    {
        $inputs['toggle'] = true;

        $dbPermissionCountryName = ($user->permissions->first()) ? $user->permissions->first()->country['name'] : null;
        $dbPermissionRoleName = ($user->permissions->first()) ? $user->permissions->first()->role['name'] : null;

        $resp = UserPermissions::canUpdateUserStatus(
            $user,
            $inputs,
            $dbPermissionCountryName,
            $dbPermissionRoleName);
        if ($resp['type'] == 'error') {
            return response()->json([$resp['type'] => $resp['msg']], $resp['status']);
        }
        
        User::updateUserStatus($user);
        
        return response()->json('ok', 200);
    }

    public function deleteUser(User $user)
    {
        if (!User::canDeleteUser()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        User::deleteUser($user);

        return response()->json('ok', 200);
    }

    public function deleteUsers(Request $request, User $user)
    {
        $inputs = $request->all();
        
        $userIds = array_filter(explode(',', $inputs['datatable-selected']));
        $users = User::whereIn('id', $userIds)->get();

        if (!User::canDeleteUser()) {
            return response()->json(['error' => self::ERROR_NOT_AUTHORIZED], 403);
        }

        foreach ($users as $user) {
            User::deleteUser($user);
        }

        return response()->json('ok', 200);
    }
}
