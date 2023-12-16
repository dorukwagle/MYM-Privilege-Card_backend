<?php

namespace App\Http\Controllers;

use App\Models\Card;
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
        /**
         * /api/users?type=customer (returns new unverified users who paid)
         * /api/users?type=customer&expired=true (returns expired users who paid)
         * /api/users?type=customer&expired=true&paid=false (returns expired users who haven't paid)
         * /api/users?type=customer&paid=false (returns new users who haven't paid)
         * /api/users?type=customer&expired=true (returns card expired users)
         * /api/users?type=vendor 
         */

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
}
