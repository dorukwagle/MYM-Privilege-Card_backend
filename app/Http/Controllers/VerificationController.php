<?php

namespace App\Http\Controllers;

use App\Helpers\OtpHelper;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    private function sendErrorResponse() {
        return response(['err' => 'email or user does not exist'], 400);
    }

    public function startEmailVerification(Request $request) {
        $validation = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
            'email' => 'required'
        ]);

        if($validation->fails())
            return $this->sendErrorResponse();

        $user = User::find($request->user_id);
        if (($user->email != $request->email) || $user->email_verified)
            return $this->sendErrorResponse();
        
        // send the otp to the user
        OtpHelper::sendOtp($user->id, $user->email);

        return ['status' => 'ok'];
    }

    public function verifyEmail(Request $request) {
        $validation = Validator::make($request->all(), [
            'user_id' => 'required',
            'otp' => ['required', 'min:6', 'max:6'],
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $verified = OtpHelper::verifyOtp($request->user_id, $request->otp);
        if (!$verified) return response(['err' => 'failed to verify otp', 400]);

        $user = User::find($request->user_id);
        $user->email_verified = true;
        $user->save();

        return ['status' => 'ok', 'user_role' => $user->user_role];
    }

    public function changeEmail(Request $request) {
        $validation = Validator::make($request->all(), [
            'user_id' => 'required',
            'email' => ['required', 'email'],
            'password' => 'required'
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::find($request->user_id);

        if (!$user) return response(['err' => 'invalid user_id'], 400);
        if (!Hash::check($request->password, $user->password)) return response(['err' => 'incorrect password'], 400);
        if ($user->email_verified) return response(['err' => 'email already verified'], 400);
        
        $user->email = $request->email;
        $user->save();

        return ['status' => 'ok'];
    }
}
