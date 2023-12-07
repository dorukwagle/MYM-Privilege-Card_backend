<?php

namespace App\Http\Controllers;

use App\Helpers\OtpHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{
    public function sendResetOtp(Request $request) {
        $validation = Validator::make($request->all(), [
            'email' => ['required', 'email']
        ]);

        if ($validation->fails()) 
            return response($validation->errors(), 400);

        $user = User::where('email', $request->email)->first();
        if ($user) 
            OtpHelper::sendOtp($user->id, $user->email);
        
        return ['status' => 'ok'];
    }

    public function resetPassword(Request $request) {
        $err = response(['err' => 'invalid otp']);
        $validation = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp' => ['required', 'numeric'],
            'password' => ['required', 'string', 'min:6']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::where('email', $request->email)->first();
        if (!$user) return $err;

        $verified = OtpHelper::verifyOtp($user->id, $request->otp, $request->email);
        if (!$verified) return $err;

        $user->password = Hash::make($request->password);
        $user->save();
        
        return ['status' => 'ok'];
    }
}
