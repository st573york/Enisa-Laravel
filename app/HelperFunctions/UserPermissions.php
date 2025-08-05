<?php

namespace App\HelperFunctions;

use App\Models\Country;
use App\Models\Index;
use App\Models\IndexConfiguration;
use App\Models\QuestionnaireCountry;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserPermissions
{
    const ERROR_NOT_ALLOWED = 'User cannot be updated as the requested action is not allowed!';

    public static function validateInputsForEdit($inputs)
    {
        return validator(
            $inputs,
            [
                'country' => 'required',
                'role' => 'required',
                'blocked' => 'required'
            ]
        );
    }

    public static function canUpdateUser(
        $user,
        $dbPermissionCountryName,
        $countryName,
        $dbPermissionRoleName,
        $roleName)
    {
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
        
        if (($countryName != config('constants.USER_GROUP') && $roleName == 'admin') ||       // Admin/PoC updates user to COUNTRY/admin
            ($countryName == config('constants.USER_GROUP') && $roleName == 'Primary PoC') || // Admin/PoC updates user to ENISA/Primary PoC
            ($countryName == config('constants.USER_GROUP') && $roleName == 'PoC') ||         // Admin/PoC updates user to ENISA/PoC
            ($countryName == config('constants.USER_GROUP') && $roleName == 'operator'))      // Admin/PoC updates user to ENISA/operator
        {
            return [
                'type' => 'error',
                'msg' => self::ERROR_NOT_ALLOWED,
                'status' => 405
            ];
        }

        if ((Auth::user()->isPoC() && $dbPermissionCountryName != Auth::user()->permissions->first()->country->name) ||                  // PoC updates user with Permission country other than PoC's country
            (Auth::user()->isPoC() && $countryName != Auth::user()->permissions->first()->country->name) ||                              // PoC updates user to country other than PoC's country
            (Auth::user()->isPoC() && $roleName == 'admin') ||                                                                           // PoC updates user to admin
            (Auth::user()->isPoC() && !Auth::user()->isPrimaryPoC() && $roleName == 'Primary PoC') ||                                    // PoC (not Primary PoC) updates user to Primary PoC
            (Auth::user()->isPoC() && !Auth::user()->isPrimaryPoC() && $dbPermissionRoleName == 'Primary PoC') ||                        // PoC (not Primary PoC) updates Primary PoC
            (Auth::user()->isPoC() && !Auth::user()->isPrimaryPoC() && $roleName == 'PoC' && $user->id != Auth::user()->id) ||           // PoC (not Primary PoC, not himself) updates user to PoC
            (Auth::user()->isPoC() && !Auth::user()->isPrimaryPoC() && $dbPermissionRoleName == 'PoC' && $user->id != Auth::user()->id)) // PoC (not Primary PoC, not himself) updates PoC
        {
            return [
                'type' => 'error',
                'msg' => 'User cannot be updated as you are not authorized for this action!',
                'status' => 403
            ];
        }

        return [
            'type' => 'success',
            'msg' => 'User can be successfully updated!'
        ];
    }

    public static function canUpdateUserStatus(
        $user,
        $inputs,
        $dbPermissionCountryName,
        $dbPermissionRoleName)
    {
        $status_updated = (($inputs['toggle']) || (!$inputs['toggle'] && isset($inputs['blocked']) && $inputs['blocked'] != $user->blocked)) ? true : false;

        if ($status_updated)
        {
            if (Auth::user()->isAdmin() && Auth::user()->id == $user->id) { // Admin updates himself
                return [
                    'type' => 'error',
                    'msg' => self::ERROR_NOT_ALLOWED,
                    'status' => 405
                ];
            }

            if (Auth::user()->isPoC() && Auth::user()->id == $user->id ||                                                 // PoC updates himself
                Auth::user()->isPoC() && $dbPermissionCountryName != Auth::user()->permissions->first()->country->name || // PoC updates user with Permission country other than PoC's country
                Auth::user()->isPoC() && !Auth::user()->isPrimaryPoC() && $dbPermissionRoleName == 'Primary PoC')         // PoC (not Primary PoC) updates Primary PoC
            {
                return [
                    'type' => 'error',
                    'msg' => 'User status cannot be updated as you are not authorized for this action!',
                    'status' => 403
                ];
            }
        }

        return [
            'type' => 'success',
            'msg' => 'User status can be successfully updated!'
        ];
    }

    public static function canUpdateToPrimaryPoC($dbPermissionRoleName, $roleName)
    {
        if ($dbPermissionRoleName != 'Primary PoC' &&
            $roleName == 'Primary PoC')
        {
            return true;
        }

        return false;
    }

    public static function canDowngradePrimaryPoC($countryName, $dbPermissionRoleName, $roleName)
    {
        if ($dbPermissionRoleName == 'Primary PoC' &&
            $roleName != 'Primary PoC')
        {
            return [
                'type' => 'warning',
                'msg' => 'This user is the Primary PoC for ' . $countryName . ' and cannot be updated. Please first assign the Primary PoC role to another user for ' . $countryName . '!',
                'status' => 403
            ];
        }

        return [
            'type' => 'success',
            'msg' => 'Primary PoC can be successfully downgraded!'
        ];
    }

    public static function getUserAvailableIndicesByYear($year)
    {
        $countries = self::getUserCountries('id');
        
        return Index::select('indices.*')
            ->join('index_configurations', 'index_configuration_id', '=', 'index_configurations.id')
            ->whereIn('country_id', $countries)
            ->where('index_configurations.year', $year)
            ->orderBy('country_id')
            ->get();
    }

    public static function getUserQuestionnaires($questionnaire_country_id)
    {
        return QuestionnaireCountry::select(
            'questionnaire_countries.id AS questionnaire_country_id',
            'questionnaire_countries.questionnaire_id',
            'questionnaire_countries.json_data AS questionnaire_json_data',
            'questionnaire_countries.completed AS completed',
            'countries.id AS country_id',
            'countries.name AS country_name',
            'questionnaires.title AS questionnaire_title',
            'questionnaires.year AS questionnaire_year',
            'users1.name AS submitted_by',
            'users2.name AS approved_by',
            DB::raw('DATE_FORMAT(questionnaires.deadline, "%d-%m-%Y") AS questionnaire_deadline')
        )
            ->leftJoin('users AS users1', 'users1.id', '=', 'questionnaire_countries.submitted_by')
            ->leftJoin('users AS users2', 'users2.id', '=', 'questionnaire_countries.approved_by')
            ->leftJoin('questionnaires', 'questionnaires.id', '=', 'questionnaire_countries.questionnaire_id')
            ->leftJoin('countries', 'countries.id', '=', 'questionnaire_countries.country_id')
            ->whereIn('questionnaire_countries.country_id', UserPermissions::getUserCountries())
            ->when(!is_null($questionnaire_country_id), function ($query) use ($questionnaire_country_id) {
                $query->where('questionnaire_countries.id', $questionnaire_country_id);
            })
            ->groupBy('questionnaire_countries.id')
            ->get();
    }

    public static function getUserCountries($field = 'id', $user = null)
    {
        $countries = [];

        if (!$user) {
            $user = Auth::user();
        }

        if ($user->isAdmin() ||
            ($user->isViewer() && $user->isEnisa()))
        {
            if ($field == 'entity') {
                $countries = Country::all();
            }
            else {
                $countries = Country::pluck($field)->toArray();
            }
        }
        else
        {
            $permissions = $user->permissions;

            foreach ($permissions as $permission) {
                $countries = self::fetchCountryField($permission->country, $field, $countries);
            }
        }

        return $countries;
    }

    public static function fetchCountryField($country, $field, $countries = [])
    {
        if ($field == 'entity') {
            $countries[] = $country;
        }
        else {
            $countries[] = $country->{$field};
        }

        return $countries;
    }

    public static function getUserRoles($field = 'entity', $yourself = false)
    {
        if (Auth::user()->isAdmin())
        {
            return Role::where('id', '>=', 1)
                ->orderBy('order')
                ->when($field == 'entity', function ($query) {
                    return $query->get();
                })
                ->when($field != 'entity', function ($query) use ($field) {
                    return $query->pluck($field)->toArray();
                });
        }

        if (Auth::user()->isPoC())
        {
            return Role::when(Auth::user()->isPrimaryPoC(), function ($query) use ($yourself) {
                    $roles = [2, 3, 4];
                    if ($yourself) {
                        array_push($roles, 5);
                    }
                    $query->whereIn('id', $roles);
                })
                ->when(!Auth::user()->isPrimaryPoC(), function ($query) use ($yourself) {
                    $roles = [3, 4];
                    if ($yourself) {
                        array_push($roles, 2);
                    }
                    $query->whereIn('id', $roles);
                })
                ->orderBy('order')
                ->when($field == 'entity', function ($query) {
                    return $query->get();
                })
                ->when($field != 'entity', function ($query) use ($field) {
                    return $query->pluck($field)->toArray();
                });
        }

        return null;
    }

    public static function getCountriesByIndexValues($number = 5, $node = 'Index', $entityFlag = false)
    {
        $countryIndices = IndexConfiguration::getExistingPublishedConfigurationForYear($_COOKIE['index-year'])->index;
        $indexValues = [];

        foreach ($countryIndices as $index)
        {
            if ($node == 'Index')
            {
                if ($entityFlag) {
                    $indexValues[$index->country->id] = array_values($index->json_data['contents'][0]['global_index_values'][0]);
                }
                else {
                    $indexValues[$index->country->name] = array_values($index->json_data['contents'][0]['global_index_values'][0])[0];
                }
            }
            else
            {
                $nodeArray = explode('-', $node);
                $nodeType = $nodeArray[0];
                $nodeId = $nodeArray[1];

                foreach ($index->json_data['contents'] as $key => $area)
                {
                    if ($key == 0) {
                        continue;
                    }

                    if ($nodeType == "area" && $nodeId == $area['area']['id']) {
                        $indexValues[$index->country->name] = array_values($area['area']['values'][0])[0];
                    }
                    else
                    {
                        foreach ($area['area']['subareas'] as $subarea)
                        {
                            if ($nodeId == $subarea['id']) {
                                $indexValues[$index->country->name] = array_values($subarea['values'][0])[0];
                            }
                        }
                    }
                }
            }
        }

        arsort($indexValues);

        return [array_slice($indexValues, 0, $number, true), array_slice(array_reverse($indexValues, true), 0, $number, true)];
    }
}
