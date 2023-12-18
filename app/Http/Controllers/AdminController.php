<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\PaymentHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\returnSelf;

class AdminController extends Controller
{

    private function notFound()
    {
        return response(['err' => 'not found'], 404);
    }

    private function isValidAccount($user, $userType)
    {
        if (!$user) return false;

        if ($user->user_role === 'admin') return false;

        if ($user->user_role !== $userType && (!$user->is_vend_cust))
            return false;

        return true;
    }

    public function getUserRequests(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'type' => ['required', 'in:customer,vendor'],
            'expired' => ['sometimes', 'nullable', 'in:yes,no'],
            'paid' => ['sometimes', 'nullable', 'in:yes,no']
        ]);

        $expired = false;
        $paid = true;

        if ($validation->fails())
            return response($validation->errors(), 400);

        $userType = $request->query('type');

        if ($request->query('expired') && $request->query('expired') === 'yes')
            $expired = true;

        if ($request->query('paid') && $request->query('paid') === 'no')
            $paid = false;

        $columns = [
            'id',
            'profile_icon',
            'contact_no',
            'email',
            'full_name',
            'org_name',
            'payment_status'
        ];

        $users = User::where('user_role', $userType);
        $users->where('payment_status', $paid ? 'paid' : 'pending');
        
        if ($expired)
            $users->whereDate('expires', '<', Carbon::now());
        else $users->where('account_status', 'pending');

        return $users->get($columns);
    }

    public function verifyVendor($vendorId)
    {
        $user = User::find($vendorId);
        if (!$this->isValidAccount($user, 'vendor')) return $this->notFound();

        $user->account_status = 'verified';
        $user->save();

        return ['status' => 'ok'];
    }

    public function rejectVendor($vendorId)
    {
        $user = User::find($vendorId);
        if (!$this->isValidAccount($user, 'vendor')) return $this->notFound();

        $vatCard = $user->org_vat_card;
        $registrationCard = $user->org_registration_card;
        $profileIcon = $user->profile_icon;

        if ($vatCard) File::delete($vatCard);
        if ($registrationCard) File::delete($registrationCard);
        if ($profileIcon) File::delete($profileIcon);

        $user->delete();

        return ['status' => 'ok'];
    }

    public function generateCardNumber(Request $request)
    {
        $numbers = [];

        for ($i = 0; $i < 4; $i++) {
            $numbers[] = random_int(1000, 9999);
        }

        return ['id' => implode('-', $numbers)];
    }

    public function assignCard(Request $request, $userId)
    {
        $validation = Validator::make($request->all(), [
            'card_id' => ['required', 'min:19', 'max:19', 'unique:card,id'],
            'valid_duration' => ['required', 'numeric', 'min:1']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::find($userId);
        if (!$this->isValidAccount($user, 'customer')) return $this->notFound();

        $user->account_status = 'verified';
        $user->expires = Carbon::now()->addYears($request->valid_duration);
        $user->payment_status = 'pending';

        Card::create([
            'user_id' => $user->id,
            'id' => $request->card_id
        ]);

        $user->save();

        return ['status' => 'ok'];
    }

    public function rejectCustomer($custId) {
        $user = User::find($custId);
        if (!$this->isValidAccount($user, 'customer')) return $this->notFound();

        $profileIcon = $user->profile_icon;
        if ($profileIcon) File::delete($profileIcon);

        $user->profile_icon = null;
        $user->account_status = 'rejected';
        $user->save();

        return ['status' => 'ok'];
    }

    public function renewCard(Request $request, $userId) {
        $validation = Validator::make($request->all(), [
            'valid_duration' => ['required', 'numeric', 'min:1']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::find($userId);
        if (!$this->isValidAccount($user, 'customer')) return $this->notFound();

        $user->payment_status = 'pending';
        $user->expires = Carbon::now()->addYears($request->valid_duration);
        $user->save();

        return ['status' => 'ok'];
    }

    public function expireCard($userId) {
        $user = User::find($userId);
        if (!$this->isValidAccount($user, 'customer')) return $this->notFound();

        $user->expires = Carbon::now()->yesterday();
        $user->save();

        return ['status' => 'ok'];
    }

    public function manualPayment(Request $request) {
        $validation = Validator::make($request->all(), [
            'user_id' => 'required',
            'payment_amount' => ['required', 'numeric']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::find($request->user_id);
        if (!$user)
            return response(['err' => 'user not found'], 404);
    
        PaymentHistory::create([
            'user_id' => $user->id,
            'payment_amount' => $request->payment_amount
            ]);

        $user->payment_status = 'paid';
        $user->save();
        return ['status' => 'ok'];
    }
}
