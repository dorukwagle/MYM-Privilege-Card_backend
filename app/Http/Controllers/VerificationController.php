<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\returnSelf;

class VerificationController extends Controller
{
    
    private function sendOtp($email,  $otp) {
        Mail::to($email)->send(new OtpMail($otp));
    }

    private function generateOtp() {
        return random_int(100000, 999999);
    }

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

        $user = User::where('id', '=', $request->user_id)->get();

        if (($user->email != $request->email) || $user->email_verified)
            return $this->sendErrorResponse();

        $otp = $this->generateOtp();

        // save the otp in the database
        Otp::create([
            'otp' => $otp,
            'user_id' => $user->id,
            'expiry_date' =>Carbon::now()->addMinutes(1)
        ]);

        // send the otp to the user
        $this->sendOtp($user->email, $otp);

        return ['status' => 'ok'];
    }

    public function verifyEmail(Request $request) {
        $validation = Validator::make($request->all(), [
            'user_id' => 'required',
            'otp' => ['required', 'min:6', 'max:6'],
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $otp = Otp::where('user_id', '=', $request->user_id)->where('otp', '=', $request->otp);

        if (!$otp) return response(['err' => 'user not found'], 400);
        if (Carbon::parse($otp->expiry_date)->isPast()) 
            return response(['err' => 'otp expired'], 400);

        User::where('id', $request->user_id)
                    ->update(['email_verified' => true]);

        return ['status' => 'ok'];
    }

    public function changeEmail(Request $request) {
        $validation = Validator::make($request->all(), [
            'user_id' => 'required',
            'email' => ['required', 'email', 'unique:users'],
            'password' => 'required'
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::find($request->user_id);

        if (!$user) return response(['err' => 'invalid user_id'], 400);
        if (!Hash::check($request->password, $user->password)) return response(['err' => 'incorrect password']);
        
        $user->email = $request->email;
        $user->save();

        return ['status' => 'ok'];
    }
}
