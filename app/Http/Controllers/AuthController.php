<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Expr\Cast\Object_;

class AuthController extends Controller
{

    private function getErrMsg() {
        return response(['err' => 'incorrect email or password'], 400);
    }

    public function login(Request $request) {
        $validation = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required'
        ]);

        if($validation->fails())
           return $this->getErrMsg();

        $user = User::where('email', '=', $request->email)->first();
        if (!$user) return $this->getErrMsg();

        $passCheck = Hash::check($request->password, $user->password);

        if(!$user || !$passCheck)
            return $this->getErrMsg();
        
        // create a session cookie and insert it into the database
        $cookie = Hash::make(Carbon::now());
        Session::create([
            'session' => $cookie,
            'user_id' => $user->id,
            'expiry_date' => Carbon::now()->addDays(5)->endOfDay()
        ]);
        
        $res = $this->getResponse($user, $cookie);
        if ($user->has_logged_in) {
            $user->has_logged_in = true;
            $user->save();
        }
        return $res;
    }

    public function checkLoggedIn(Request $request)  {
        return $this->getResponse($request->user, "");
    }

    public function logout(Request $request) {
        $cookie = $request->bearerToken();

        Session::where('session', '=', $cookie)->delete();
        
        $id = $request->user->id;
        $user = User::find($id);
        $user->device_token = null;
        $user->save();

        return [
            'status' => 'ok'
        ];
    }

    private function getResponse($user, $cookie) {
        $response = [
            'email_status' => $user->email_verified,
            'access_token' => $cookie,
            'user_role' => $user->user_role,
            'is_vend_cust' => $user->is_vend_cust,
            'user_id' => $user->id,
            'is_first_login' => !$user->has_logged_in,
            'email' => $user->email
        ];

        if(!$user->email_verified)
            return response($response, 401);

        return response($response, 200);
    }
}
