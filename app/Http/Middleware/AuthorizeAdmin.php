<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $res = response(['err' => 'Unauthorized'], 403);
        $role = $request->user->user_role;

        if (!$request->user)
            return $res;
        if ($role !== 'admin' && $role !== 'superadmin')
            return $res;

        return $next($request);
    }
}
