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
    private $unauthorized = ['err' => 'Unauthorized'];

    public function handle(Request $request, Closure $next): Response
    {
        // extract the bearer token
        $token = $request->bearerToken();
        if (!$token) abort(401, $this->errMsg);

        $session = Session::where('session', $token)->first();
        if (!$session || Carbon::parse($session->expiry_date)->isPast())
            abort(401, $this->errMsg);

        $user = User::find($session->user_id);
        $request->merge(['user' => $user]);

        return $next($request);
    }

    public function vendor(Request $request, Closure $next) {
        // any vendor
        if ($request->user() && $request->user()->user_role !== 'vendor')
            abort(403, $this->unauthorized);

        return $next($request);
    }

    public function customer(Request $request, Closure $next) {
        // any customer
         if ($request->user() && $request->user()->user_role !== 'customer')
            abort(403, $this->unauthorized);

        return $next($request);
    }

    public function verifiedVendor(Request $request, Closure $next) {
        // verified vendor only
        $user = $request->user();
        if ($request->user() && $user->user_role !== 'vendor')
            abort(403, $this->unauthorized);
        
        if ($user->account_status !== 'verified')
            abort(402, ['err', 'Unverified account']);

        $next($request);        
    }

    public function verifiedCustomer(Request $request, Closure $next) {
        // verified and not expired only
        $user = $request->user();
        if ($request->user() && $user->user_role !== 'customer')
            abort(403, $this->unauthorized);
        
        if ($user->account_status !== 'verified')
            abort(402, ['err', 'Unverified account']);

        if (Carbon::parse($user->expires)->isPast())
            abort(402, ['err', 'Account Expired, please renew it']);

        $next($request);        
    }

    public function admin(Request $request, Closure $next) {
        if ($request->user() && $request->user()->user_role !== 'admin')
            abort(403, $this->unauthorized);
        
        return $next($request);
    }

}
