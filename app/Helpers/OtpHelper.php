<?php

namespace App\Helpers;

use App\Mail\OtpMail;
use App\Models\Otp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class OtpHelper {
    public static function sendOtp($email,  $otp)
    {
        Mail::to($email)->send(new OtpMail($otp));
    }

    public static function generateOtp()
    {
        return random_int(100000, 999999);
    }

    public static function verifyOtp($user_id, $otp): bool {
        $otp = Otp::where('user_id', $user_id)->where('otp', $otp)->first();

        if (!$otp) return false;

        if (Carbon::parse($otp->expiry_date)->isPast())
           return false;

        return true;
    }
}