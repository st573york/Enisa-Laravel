<?php

namespace App\HelperFunctions;

use App\Models\User;
use Ecas\Client;
use Ecas\Parser\ServiceResponseBuilder;
use Ecas\Properties\JsonProperties;
use Illuminate\Support\Facades\Auth;

class EcasTestHelper
{
    public static function validateTestUser($role)
    {
        self::logoutEcasUser();
        if (is_object($role)) {
            $json = '{"user":"' . $role->userName . '","departmentNumber":"DIGIT.A.3.001","email":"' . $role->email . '","employeeType":"n","firstName":"' . $role->firstName . '","lastName":"' . $role->lastName . '","domain":"external","domainUsername":"' . $role->userName . '","telephoneNumber":"123456","locale":"en","assuranceLevel":"10","uid":"' . $role->userName . '","groups":["LICENSE_TO_KILL","AIDA_SELFRG","DG_DIGIT","DOUBLE_KILL","MI6"],"authenticationLevel":"BASIC","strengths":["BASIC"],"authenticationFactors":{"moniker":"' . $role->email . '"},"loginDate":"2022-11-04T07:00:35.744+01:00","sso":true,"ticketType":"SERVICE"}';
        }
        else
        {
            switch ($role)
            {
                case 'admin':
                    $json = '{"user":"jamesbond","departmentNumber":"DIGIT.A.3.001","email":"007@mi6.eu","employeeType":"n","firstName":"James","lastName":"BOND","domain":"external","domainUsername":"jamesbond","telephoneNumber":"123456","locale":"en","assuranceLevel":"10","uid":"jamesbond","groups":["LICENSE_TO_KILL","AIDA_SELFRG","DG_DIGIT","DOUBLE_KILL","MI6"],"authenticationLevel":"BASIC","strengths":["BASIC"],"authenticationFactors":{"moniker":"007@mi6.eu"},"loginDate":"2022-11-04T07:00:35.744+01:00","sso":true,"ticketType":"SERVICE"}';

                    break;
                case 'ppoc':
                    $json = '{"user":"jasonbourne","departmentNumber":"DIGIT.X.1.001","email":"Jason.BOURNE@ec.europa.eu","employeeNumber":"00000001","employeeType":"f","firstName":"Jason","lastName":"BOURNE","domain":"eu.europa.ec","domainUsername":"jasonbourne","telephoneNumber":"00002","locale":"en","assuranceLevel":"40","uid":"jasonbourne","orgId":"1234567890","groups":["MEDUSA","TREADSTONE","DG_DIGIT"],"authenticationLevel":"BASIC","strengths":["BASIC"],"authenticationFactors":{"moniker":"Jason.BOURNE@ec.europa.eu"},"loginDate":"2022-11-04T10:24:29.420+01:00","sso":true,"ticketType":"SERVICE"}';

                    break;
                case 'poc':
                    $json = '{"user":"jackbauer","departmentNumber":"DIGIT.A.3.001","email":"Jack.Bauer@ctu.eu","employeeType":"f","firstName":"Jack","lastName":"BAUER","domain":"eu.europa.curia","domainUsername":"jackbauer","telephoneNumber":"(310) 597-3781","locale":"en","assuranceLevel":"40","uid":"jackbauer","groups":["CTU","CTU_RETIRED","DG_DIGIT","CTU_DIRECTOR"],"authenticationLevel":"BASIC","strengths":["BASIC"],"authenticationFactors":{"moniker":"Jack.Bauer@ctu.eu"},"loginDate":"2022-11-04T10:36:59.631+01:00","sso":true,"ticketType":"SERVICE"}';

                    break;
                case 'operator':
                    $json = '{"user":"chucknorris","departmentNumber":"DIGIT.A.3.001","email":"texasranger@chuck.norris.com.eu","employeeType":"f","firstName":"Chuck","lastName":"NORRIS","domain":"eu.europa.ec","domainUsername":"chucknorris","telephoneNumber":"1","locale":"en","assuranceLevel":"40","uid":"chucknorris","groups":["INTERNET","DG_DIGIT","LIVENEWS","TEXAS_RANGER"],"authenticationLevel":"BASIC","strengths":["BASIC"],"authenticationFactors":{"moniker":"texasranger@chuck.norris.com.eu"},"loginDate":"2022-11-04T10:35:00.121+01:00","sso":true,"ticketType":"SERVICE"}';

                    break;
                case 'viewer':
                    $viewer = TestHelper::createNewUser([
                        'permissions' => [
                            'role' => 'viewer',
                            'country' => config('constants.USER_GROUP')
                        ]
                    ]);

                    $json = '{"user":"' . $viewer->userName . '","departmentNumber":"DIGIT.A.3.001","email":"' . $viewer->email . '","employeeType":"n","firstName":"' . $viewer->firstName . '","lastName":"' . $viewer->lastName . '","domain":"external","domainUsername":"' . $viewer->userName . '","telephoneNumber":"123456","locale":"en","assuranceLevel":"10","uid":"' . $viewer->userName . '","groups":["LICENSE_TO_KILL","AIDA_SELFRG","DG_DIGIT","DOUBLE_KILL","MI6"],"authenticationLevel":"BASIC","strengths":["BASIC"],"authenticationFactors":{"moniker":"' . $viewer->email . '"},"loginDate":"2022-11-04T07:00:35.744+01:00","sso":true,"ticketType":"SERVICE"}';

                    break;
                default:
                    $json = '{"user":"jamesbond","departmentNumber":"DIGIT.A.3.001","email":"007@mi6.eu","employeeType":"n","firstName":"James","lastName":"BOND","domain":"external","domainUsername":"jamesbond","telephoneNumber":"123456","locale":"en","assuranceLevel":"10","uid":"jamesbond","groups":["LICENSE_TO_KILL","AIDA_SELFRG","DG_DIGIT","DOUBLE_KILL","MI6"],"authenticationLevel":"BASIC","strengths":["BASIC"],"authenticationFactors":{"moniker":"007@mi6.eu"},"loginDate":"2022-11-04T07:00:35.744+01:00","sso":true,"ticketType":"SERVICE"}';
                    
                    break;
            }
        }

        $_config_file = env('ECAS_CONFIG_FILE', base_path() . '/app/ecas-config/ecas-config-dev.json');
        $_ecas_client = new Client(JsonProperties::getInstance($_config_file));
        $builder =
            ServiceResponseBuilder::fromJson(
                json_decode($json),
                false,
                1,
                $_ecas_client->getProperties(),
                $_ecas_client->getCache()
            );

        $_ecas_client->setAuthenticatedUser($builder);
        $details = $_ecas_client->getAuthenticatedUser();
        $_ecas_client->setLastActivity();
        
        if (!Auth::check())
        {
            $dbUser = User::where('email', $details->getEmail())->first();

            if ($role == 'ppoc')
            {
                $dbUser->permissions->first()->role_id = 5;
                $dbUser->permissions->first()->save();
            }
            elseif ($role == 'poc' &&
                    $dbUser->permissions->first()->role_id == 5)
            {
                $dbUser->permissions->first()->role_id = 2;
                $dbUser->permissions->first()->save();
            }

            Auth::loginUsingId($dbUser->id);
        }

        return Auth::user();
    }

    public static function logoutEcasUser()
    {
        $_config_file = env('ECAS_CONFIG_FILE', base_path() . '/app/ecas-config/ecas-config-dev.json');
        $_ecas_client = new Client(JsonProperties::getInstance($_config_file));

        $_ecas_client->logout();
        Auth::logout();
    }
}
