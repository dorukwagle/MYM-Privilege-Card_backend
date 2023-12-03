<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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

        if(!$user->email_verified)
            return [
                'user_id' => $user->id,
                'email' => $user->email,
                'email_status' => 'unverified'
            ];
       
        // create a session cookie and insert it into the database
        $cookie = Hash::make(Carbon::now());
        Session::create([
            'session' => $cookie,
            'user_id' => $user->id,
            'expiry_date' => Carbon::now()->addHours(2)
        ]);

        // return the session cookie to the user
        return [
            'status' => 'ok',
            'access_token' => $cookie
        ];
    }

    public function logout(Request $request) {
        $cookie = $request->bearerToken();
        Session::where('session', '=', $cookie)->delete();
        return [
            'status' => 'ok'
        ];
    }
}
