<?php

namespace App\HelperFunctions;

use App\Models\Country;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TestHelper
{
    /**
     * Function creates new user with permissions.
     *
     * @var data [].
     *
    */
    public static function createNewUser($data)
    {
        $trashed = (isset($data['trashed']) && $data['trashed']) ? true : false;
        $attributes = [
            'blocked' => (isset($data['blocked'])) ? $data['blocked'] : 0,
            'country_code' => (isset($data['country_code'])) ? $data['country_code'] : null
        ];

        if ($trashed) {
            $user = User::factory()->trashed()->create($attributes);
        }
        else {
            $user = User::factory()->create($attributes);
        }

        $exclude_fields = (isset($data['exclude_fields']) && $data['exclude_fields']) ? true : false;
        if (!$exclude_fields)
        {
            $name = explode(' ', $user->name);
            $user->firstName = $name[0];
            $user->lastName = $name[1];
            $user->userName = fake()->userName();
        }

        $attributes = [
            'name' => $data['permissions']['role'],
            'country_id' => Country::where('name', $data['permissions']['country'])->value('id'),
            'user_id' => $user->id,
            'role_id' => Role::where('name', $data['permissions']['role'])->value('id')
        ];

        if ($trashed) {
            Permission::factory()->trashed()->create($attributes);
        }
        else {
            Permission::factory()->create($attributes);
        }

        return $user;
    }

    public static function getActualUsers($users)
    {
        $actual_users = [];

        foreach ($users as $user) {
            array_push($actual_users, $user['name']);
        }

        return $actual_users;
    }

    public static function getExpectedUsers($roles = [], $check_yourself = false)
    {
        $db_users = User::get();
        $expected_users = [];
        
        foreach ($db_users as $db_user)
        {
            $db_country_code = ($db_user->permissions->first()) ? $db_user->permissions->first()->country['code'] : $db_user->country_code;
            $db_role_id = ($db_user->permissions->first()) ? $db_user->permissions->first()->role['id'] : null;
            
            $user_roles = (empty($roles)) ? UserPermissions::getUserRoles('id') : $roles;

            if ((Auth::user()->isAdmin() && (is_null($db_country_code)) ||
                 (!is_null($db_country_code) && in_array($db_country_code, UserPermissions::getUserCountries('code')))) &&
                (is_null($db_role_id) ||
                 (!is_null($db_role_id) && in_array($db_role_id, $user_roles))) ||
                ($check_yourself && $db_user->id == Auth::user()->id))
            {
                array_push($expected_users, $db_user->name);
            }
        }
        
        return $expected_users;
    }

    public static function getRandomNumberByLength($length)
    {
        $tmp = mt_rand(1, 9);

        for ($i = 1; $i < $length; $i++) {
            $tmp .= mt_rand(0, 9);
        }

        return intval($tmp);
    }
}
