<?php

namespace App\Helpers;

use App\Mail\OtpMail;
use App\Models\Otp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class OtpHelper {
    public static function sendOtp($user_id,  $email)
    {
        $otp =  OtpHelper::generateOtp();

        Mail::to($email)->send(new OtpMail($otp));
        
        // save the otp in the database
        Otp::create([
            'otp' => $otp,
            'user_id' => $user_id,
            'expiry_date' => Carbon::now()->addMinutes(3),
            'sent_to' => $email
        ]);
    }

    private static function generateOtp()
    {
        return random_int(100000, 999999);
    }

    public static function verifyOtp($user_id, $otp, $email=null): bool {
        $otp = Otp::where('user_id', $user_id)->where('otp', $otp)->first();

        if ($email) {
            if ($otp->sent_to !== $email)
                return false;
        }

        if (!$otp) return false;

        if (Carbon::parse($otp->expiry_date)->isPast())
           return false;

        return true;
    }
}