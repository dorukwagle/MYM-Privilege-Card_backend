<?php

namespace App\Helpers;

use App\Models\PaymentHistory;

class PaymentsHelper
{
    public static function getHistory($userId)
    {
        return PaymentHistory::where('user_id', $userId)
                                    ->orderBy('created_at', 'DESC')
                                    ->get();
    }

    public static function getLastPayment($userId)
    {
         return PaymentHistory::where('user_id', $userId)
                                    ->orderBy('created_at', 'DESC')
                                    ->first();
    }
}
