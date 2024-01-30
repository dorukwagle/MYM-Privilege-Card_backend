<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardUses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CardUsesController extends Controller
{
    public function getVendorQRData(Request $request)
    {
        return [
            'title' => 'mym-privilege-card',
            'full_name' => $request->user->full_name,
            'product_id' => $request->user->product_id
        ];
    }

    public function claimPurchase(Request $request, $productId)
    {
        $res = response(['card rejected' => 'Please make sure that your card is issued and is not expired'], 403);

        $validation = Validator::make($request->all(), [
            'total_price' => ['required', 'numeric'],
            'discount_percent' => ['required', 'numeric']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        if ($request->user->account_status != 'verified')
            return $res;

        if (Carbon::parse($request->user->expires)->isPast())
            return $res;

        $card = Card::where('user_id', $request->user->id)->get();

        CardUses::create([
            'product_id' => $productId,
            'card_no' => $card->id,
            'customer_name' => $request->user->full_name,
            'total_price' => $request->total_price,
            'discount_amount' => $request->total_price * $request->discount_percent / 100
        ]);

        return ['status' => 'ok'];
    }

    public function getPurchaseRequests(Request $request) {

    }

    public function verifyPurchase($productId)
    {
    }

    public function deletePurchase($productId)
    {
    }
}
