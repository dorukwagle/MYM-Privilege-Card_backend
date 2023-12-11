<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeVerifiedCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $resUn = response(['err' => 'Unauthorized'], 403);
        $resVerified = response(['err' => 'Unverified account'], 403);

        $user = $request->user;
        if (!$user) return $resUn;

        if ($user->user_role !== 'customer' && !$user->is_vend_cust)
            return $resUn;

        if ($user->account_status !== 'verified')
            return $resVerified;

        if (Carbon::parse($user->expires)->isPast())
            return response(['err' => 'Account Expired, please renew it'], 403);

        return $next($request);        
    }
}
