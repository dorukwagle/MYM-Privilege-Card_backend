<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CardUsesController extends Controller
{
    public function getVendorQRData(Request $request) {
        return [
            'title' => 'mym-privilege-card',
            'full_name' => $request->user->full_name,
            'product_id' => $request->user->product_id
        ];
    }

    public function claimPurchase(Request $request) {

    }

    public function verifyPurchase($purchaseId) {

    }

    public function deletePurchase($purchaseId) {
        
    }
}
