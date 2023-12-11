<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeVerifiedVendor
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

        if ($user->user_role !== 'vendor' && !$user->is_vend_cust)
            return $resUn;

        if ($user->account_status !== 'verified')
            return $resVerified;

        return $next($request);        
    }
}
