<?php

namespace App\Http\Middleware;

use App\HelperFunctions\InvitationHelper;
use Closure;
use Illuminate\Http\Request;
use App\HelperFunctions\UserSessionHelper;
use App\Models\Invitation;
use App\Models\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Ecas\Client;
use Ecas\Properties\JsonProperties;
use Ecas\Utils\StringUtils;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EULogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // create a new instance of the ECAS client. In our case,
        // it will use the JsonProperties implementation
        try
        {
            $_config_file = env('ECAS_CONFIG_FILE', base_path() . '/app/ecas-config/ecas-config-dev.json');
            $_ecas_client = new Client(JsonProperties::getInstance($_config_file));

            $_user_authenticated = $_ecas_client->hasUserBeenAuthenticated();

            // fetch the ticket from the query parameters
            $_ticket = isset($_GET['ticket']) ? $_GET['ticket'] : null;

            // fetch the request_new from the parameter, as example
            $request_renew = isset($_GET['request_renew']) && $_GET['request_renew'] === 'y';

            // assert if an ECAS authentication is required
            if ($request_renew)
            {
                //we're unsetting this variable to avoid an infinite loop.
                unset($_GET['request_renew']);
                // Request to re-authenticate the user
                $_ecas_client->requestECASAuthentication(true);
            }
            elseif ($_user_authenticated)
            {
                $details = $_ecas_client->getAuthenticatedUser();
                
                $user = null;
                if (!Auth::check())
                {
                    $dbUser = User::withTrashed()->where('email', $details->getEmail())->first();

                    if (is_null($dbUser)) {
                        return response()->view('components.auth-failed');
                    }

                    if ($dbUser->trashed()) {
                        return response()->view('components.auth-failed', ['deleted' => true]);
                    }

                    $dbUser->inactive_notified = 0;
                    $dbUser->inactive_deadline = null;
                    $dbUser->save();
                    
                    User::disableAuditing();
                    Auth::loginUsingId($dbUser->id);
                    User::enableAuditing();
                }
                $user = Auth::user();

                $url = $request->getRequestUri();
                $is_post = $request->isMethod('post');
                $is_ajax = $request->ajax();

                // Check if session has timed out
                if (UserSessionHelper::hasSessionTimedOut())
                {
                    if($is_ajax) {
                        return response()->json(['error' => 'Unauthenticated'], 401);
                    }
                    
                    UserSessionHelper::logout($request, 'Inactivity Timeout');
            
                    return response()->view('components.logout', ['reason' => 'due to inactivity timeout']);
                }

                if ((is_null($user) || $user->blocked) && !preg_match('/^\/(my-account\/update|logout)/', $url)) {
                    return response()->view('components.my-account', ['data' => $user, 'countries' => config('constants.EU_COUNTRIES')]);
                }
                
                // update last activity
                $_ecas_client->setLastActivity();

                // update previous url
                if (!preg_match('/^\/logout/', $url) &&
                    !$is_post &&
                    !$is_ajax)
                {
                    $_ecas_client->setPreviousUrl($url);
                }

                return $next($request);
            }
            elseif (!$_user_authenticated && StringUtils::isEmpty($_ticket)) {
                // Redirect to ECAS, using the service url from the current page and the settings from the Properties you defined
                $_ecas_client->requestECASAuthentication();
            }
            else
            {
                // check if the service ticket has been validated already
                if (!$_user_authenticated && isset($_ticket))
                {
                    // validate the service ticket, if it fails an Exception is thrown
                    $user = $_ecas_client->validateServiceTicket($_ticket, false);

                    // store the user
                    $_ecas_client->setAuthenticatedUser($user);
                    $details = $_ecas_client->getAuthenticatedUser();
                    
                    // update last activity
                    $_ecas_client->setLastActivity();

                    $email = $details->getEmail();
                    $name = $details->getFirstName() . ' ' . $details->getLastName();

                    $invitation = null;
                    if ($request->has('hash'))
                    {
                        $invitation = Invitation::getLatestPendingInvitation($email);
                        $hash = $request->query('hash');

                        if (!is_null($invitation))
                        {
                            $resp = InvitationHelper::checkInvitation($invitation, $hash);
                            if ($resp['type'] == 'error') {
                                return response()->view($resp['view']);
                            }
                        }
                    }

                    // user does not exist in DB
                    // user has received invitation
                    $user = User::withTrashed()->where('email', $email)->first();
                    if (is_null($user) &&
                        !is_null($invitation))
                    {
                        $data = [
                            'name' => $name,
                            'email' => $email,
                            'password' => Hash::make(uniqid()),
                            'blocked' => false
                        ];

                        $user = User::updateOrCreateUser($data);

                        if ($invitation->role_id == 5) {
                            User::updateCountryPrimaryPoCToPoC($invitation->country);
                        }

                        Permission::disableAuditing();
                        Permission::create([
                            'name' => $invitation->role->name,
                            'user_id' => $user->id,
                            'country_id' => $invitation->country_id,
                            'role_id' => $invitation->role_id
                        ]);
                        Permission::enableAuditing();

                        InvitationHelper::markInvitationRegistered($invitation);

                        if (!Auth::check())
                        {
                            User::disableAuditing();
                            Auth::loginUsingId($user->id);
                            User::enableAuditing();
                        }
                        $user = Auth::user();
                    }
                    else
                    {
                        $user = null;
                        if (!Auth::check())
                        {
                            $dbUser = User::withTrashed()->where('email', $email)->first();
                            
                            if (is_null($dbUser)) {
                                return response()->view('components.auth-failed');
                            }

                            if ($dbUser->trashed())
                            {
                                if (!is_null($invitation))
                                {
                                    User::restoreUser($dbUser);
                                    InvitationHelper::markInvitationRegistered($invitation);
                                }
                                else {
                                    return response()->view('components.auth-failed', ['deleted' => true]);
                                }
                            }

                            $dbUser->inactive_notified = 0;
                            $dbUser->inactive_deadline = null;
                            $dbUser->save();

                            User::disableAuditing();
                            Auth::loginUsingId($dbUser->id);
                            User::enableAuditing();
                        }
                        $user = Auth::user();

                        if (is_null($user) || $user->blocked) {
                            return response()->view('components.my-account', ['data' => $user, 'countries' => config('constants.EU_COUNTRIES')]);
                        }
                    }
                    
                    $previous = $_ecas_client->getPreviousUrl();
                    if (!is_null($previous)) {
                        return redirect($previous);
                    }

                    if (Auth::user()->isOperator() || Auth::user()->isViewer()) {
                        return redirect('/questionnaire/management');
                    }

                    return $next($request);
                }
            }
        }
        catch (\Exception $e) {
            Log::error($e);
        }

        return response()->view('components.auth-failed');
    }
}
