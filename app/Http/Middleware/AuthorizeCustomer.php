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

        if (!$request->user)
            return $res;
        if ($request->user->user_role !== 'customer')
            return $res;

        return $next($request);
    }
}
