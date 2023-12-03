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
        $user = $request->user();
        if ($request->user() && $user->user_role !== 'vendor')
        return response(['err' => 'Unauthorized'], 403);

        if ($user->account_status !== 'verified')
        return response(['err' => 'Unverified account'], 402);

        $next($request);        
    }
}
