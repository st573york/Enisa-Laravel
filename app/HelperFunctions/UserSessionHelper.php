<?php

namespace App\HelperFunctions;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Ecas\Client;
use Ecas\Properties\JsonProperties;

class UserSessionHelper
{
    public static function hasSessionTimedOut()
    {
        $session_lifetime = (!is_null(config('SESSION_LIFETIME'))) ? config('SESSION_LIFETIME') : env('SESSION_LIFETIME');
        $_config_file = env('ECAS_CONFIG_FILE', base_path(). '/app/ecas-config/ecas-config-dev.json');
        $_ecas_client = new Client(JsonProperties::getInstance($_config_file));
        
        if ($session_lifetime &&
            $_ecas_client->getLastActivity() &&
            (int)$_ecas_client->getLastActivity() + 60 * $session_lifetime < time())
        {
            return true;
        }

        return false;
    }

    public static function logout($request, $reason = null)
    {
        Log::debug('logout requested');
        
        // Add custom audit logout event because the default audit logout event
        // made remember_token changes in the audits.new_values
        $data = ['event' => 'logged out'];
        if ($reason) {
            $data['audit'] = [
                'reason' => $reason
            ];
        }
        Audit::setCustomAuditEvent(
            User::find(Auth::user()->id),
            $data
        );

        User::disableAuditing();

        Auth::logout();
        $request->session()->invalidate();
       
        $_config_file = env('ECAS_CONFIG_FILE', base_path(). '/app/ecas-config/ecas-config-dev.json');
        $_ecas_client = new Client(JsonProperties::getInstance($_config_file));
        $_ecas_client->logout();
    }
}