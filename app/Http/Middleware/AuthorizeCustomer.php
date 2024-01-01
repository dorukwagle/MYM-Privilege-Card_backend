<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $res = response(['err' => 'Unauthorized'], 403);

        if (!$request->user || !$request->user->email_verified)
            return $res;
        if ($request->user->user_role !== 'customer' && !$request->user->is_vend_cust)
            return $res;
        

        return $next($request);
    }
}
