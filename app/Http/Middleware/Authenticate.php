<?php

namespace App\Http\Middleware;

use App\HelperFunctions\InvitationHelper;
use App\Models\Invitation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate
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
        if (Auth::check())
        {
            $invitation = null;
            if ($request->has('hash'))
            {
                $invitation = Invitation::getLatestPendingInvitation(Auth::user()->email);
                $hash = $request->query('hash');
                
                if (!is_null($invitation))
                {
                    $resp = InvitationHelper::checkInvitation($invitation, $hash);
                    if ($resp['type'] == 'error') {
                        return response()->view($resp['view']);
                    }
                }
            }

            // user has received invitation
            if (!is_null($invitation)) {
                InvitationHelper::markInvitationRegistered($invitation);
            }

            return $next($request);
        }
        else {
            return redirect('login');
        }
    }
}
