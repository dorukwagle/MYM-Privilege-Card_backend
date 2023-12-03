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
        $user = $request->user();
        if ($request->user() && $user->user_role !== 'customer')
        return response(['err' => 'Unauthorized'], 403);

        if ($user->account_status !== 'verified')
        return response(['err' => 'Unverified account'], 403);

        if (Carbon::parse($user->expires)->isPast())
            return response(['err' => 'Account Expired, please renew it'], 403);

        $next($request);        
    }
}
