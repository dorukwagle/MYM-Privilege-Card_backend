<?php

use App\Models\PaymentHistory;

class PaymentHelper
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
