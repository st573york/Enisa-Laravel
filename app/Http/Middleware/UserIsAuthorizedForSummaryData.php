<?php

namespace App\Http\Middleware;

use App\HelperFunctions\UserPermissions;
use Closure;
use Illuminate\Http\Request;

class UserIsAuthorizedForSummaryData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $country = $request->route()->parameter('questionnaire')->country_id;
        $countries = UserPermissions::getUserCountries();
        
        if (in_array($country, $countries)) {
            return $next($request);
        }
        else {
            return redirect('/access/denied/', 302);
        }
    }
}
