<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardUses;
use App\Models\User;
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

        $vendor = User::where('product_id', $productId)->get();
        if (!$vendor) return response(['invalid merchant' => 'merchant not found'], 404);

        CardUses::create([
            'product_id' => $productId,
            'card_no' => $card->id,
            'customer_name' => $request->user->full_name,
            'total_price' => $request->total_price,
            'discount_amount' => $request->total_price * $request->discount_percent / 100
        ]);

        return ['status' => 'ok'];
    }

    public function getPurchaseRequests(Request $request)
    {
        return CardUses::where('product_id', $request->user->product_id)
            ->where('approved', false)
            ->orderBy('created_at')
            ->get();
    }

    public function verifyPurchase(Request $request, $purchaseId)
    {
        $res = response(['invalid item', 'item not found'], 400);

        $purchase = CardUses::find($purchaseId);
        if (!$purchase) return $res;

        if ($purchase->product_id !== $request->user->product_id)
            return $res;

        $purchase->approved = true;
        $purchase->save();

        return ['status' => 'ok'];
    }

    public function deletePurchase(Request $request, $purchaseId)
    {
        $purchase = CardUses::find($purchaseId);

        if (!$purchase)
            return response(['invalid item' => 'item not found']);

        if ($purchase->product_id === $request->user->product_id)
            $purchase->delete();

        return ['status' => 'ok'];
    }
    
}
