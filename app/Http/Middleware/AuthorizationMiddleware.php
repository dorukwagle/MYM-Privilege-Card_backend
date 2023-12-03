<?php

namespace App\Http\Middleware;

use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    private $errMsg = ['err' => 'Authentication Required'];

    public function handle(Request $request, Closure $next): Response
    {
        // extract the bearer token
        $token = $request->bearerToken();
        if (!$token) return response($this->errMsg, 401);

        $session = Session::where('session', $token)->first();
        if (!$session || Carbon::parse($session->expiry_date)->isPast())
            return response($this->errMsg, 401);

        $user = User::find($session->user_id);
        $request->merge(['user' => $user]);

        return $next($request);
    }
}
