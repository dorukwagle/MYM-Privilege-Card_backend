<?php

namespace App\Http\Controllers;

use App\Helpers\PaymentsHelper;
use App\Models\Card;
use App\Models\PaymentHistory;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;


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
        function queryItems($query, $page, $size)
        {
            $defaultPage = $page ? $page : 1;
            $defaultSize = $size ? $size : 9;
            $offset = ($defaultPage - 1) * $defaultSize;

            return $query->selectRaw('id, profile_icon, contact_no, email, full_name, org_name, payment_status, st_astext(coordinates) as location')
                ->offset($offset)
                ->limit($defaultSize)
                ->get();
        }

        $validation = Validator::make($request->all(), [
            'type' => ['required', 'in:customer,vendor'],
            'expired' => ['sometimes', 'nullable', 'in:yes,no'],
            'paid' => ['sometimes', 'nullable', 'in:yes,no'],
            'size' => ['sometimes', 'nullable', 'numeric', 'min:1'],
            'page' => ['sometimes', 'nullable', 'numeric', 'min:1']
        ]);

        $expired = false;
        $paid = true;
        $size = $request->query('size');
        $page = $request->query('page');

        if ($validation->fails())
            return response($validation->errors(), 400);

        $userType = $request->query('type');

        if ($request->query('expired') && $request->query('expired') === 'yes')
            $expired = true;

        if ($request->query('paid') && $request->query('paid') === 'no')
            $paid = false;

        $users = User::where('user_role', $userType);
        if ($userType === 'vendor') {
            $users->where('account_status', 'pending');
            return queryItems($users, $page, $size);
        }

        $users->where('payment_status', $paid ? 'paid' : 'pending');

        if ($expired)
            $users->whereDate('expires', '<', Carbon::now());
        else $users->where('account_status', 'requested');

        return queryItems($users, $page, $size);
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
        try {
            if ($vatCard) unlink(storage_path("/app/" . $vatCard));
            if ($registrationCard) unlink(storage_path("/app/" . $registrationCard));
            if ($profileIcon) unlink(storage_path("/app/" . $profileIcon));
        } catch (Exception $e) {
        }

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
            'card_id' => ['required', 'unique:cards,id', 'regex:/^\d{4}-\d{4}-\d{4}-\d{4}$/'],
            'valid_duration' => ['required', 'numeric', 'min:1', 'max:20']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::find($userId);
        if (!$this->isValidAccount($user, 'customer')) return $this->notFound();

        $user->account_status = 'verified';
        $user->expires = Carbon::now()->addYears($request->valid_duration)->endOfDay()->toDateTime();
        $user->payment_status = 'pending';

        Card::create([
            'user_id' => $user->id,
            'id' => $request->card_id
        ]);

        $user->save();

        return ['status' => 'ok'];
    }

    public function rejectCustomer($custId)
    {
        $user = User::find($custId);
        if (!$this->isValidAccount($user, 'customer')) return $this->notFound();

        $profileIcon = $user->profile_icon;
        if ($profileIcon) File::delete($profileIcon);

        $user->profile_icon = null;
        $user->account_status = 'rejected';
        $user->save();

        return ['status' => 'ok'];
    }

    public function renewCard(Request $request, $userId)
    {
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

    public function expireCard($userId)
    {
        $user = User::find($userId);
        if (!$this->isValidAccount($user, 'customer')) return $this->notFound();

        $user->expires = Carbon::now()->yesterday();
        $user->save();

        return ['status' => 'ok'];
    }

    public function getUserRequestDetails($userId)
    {
        $user = User::where('id', $userId)
            ->selectRaw('*, st_astext(coordinates) as location')
            ->first();
    
        if (!$user)
            return $this->notFound();

        unset($user['coordinates']);
        unset($user['updated_at']);
        unset($user['password']);

        $user->expired = false;
        $user->last_paid_amount = null;

        if ($user->expires && Carbon::parse($user->expires)->isPast())
            $user->expired = true;

        if ($this->isValidAccount($user, 'customer'))
            $user->last_paid_amount = PaymentsHelper::getLastPayment($user->id);

        return $user;
    }

    public function manualPayment(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'user_id' => 'required',
            'payment_amount' => ['required', 'numeric']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::find($request->user_id);
        if (!$user)
            return $this->notFound();

        PaymentHistory::create([
            'user_id' => $user->id,
            'payment_amount' => $request->payment_amount
        ]);

        $user->payment_status = 'paid';
        $user->save();
        return ['status' => 'ok'];
    }

    public function getUserPaymentHistory($userId)
    {
        $user = User::find($userId);
        if (!$this->isValidAccount($user, 'customer'))
            return $this->notFound();

        return PaymentsHelper::getHistory($userId);
    }

    public function searchUsers(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'value' => ['required', 'string'],
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $value = $request->query('value');

        $users = User::where('full_name', 'like', '%' . $value . '%')
            ->orWhere('email', 'like', '%' . $value . '%')
            ->get([
                'id',
                'full_name',
                'email',
                'contact_no',
                'account_status',
            ]);

        return $users;
    }

    public function getUserAnalytics(Request $request)
    {
        $totalUsers = User::selectRaw('count(*) as total_users')
        ->where('user_role', '!=', 'admin')
            ->first();

        $totalVendors = User::selectRaw('count(*) as total_vendors')
            ->where('user_role', 'vendor')
            ->first();

        $totalCustomers = User::selectRaw('count(*) as total_customers')
            ->where('user_role', 'customer')
            ->first();

        $totalCardRequested = User::selectRaw('count(*) as total_card_requests')
            ->where('account_status', 'requested')
            ->first();

        $totalVerifiedUsers = User::selectRaw('count(*) as total_verified_users')
        ->where('user_role', '!=', 'admin')
            ->where('account_status', 'verified')
            ->first();

        $totalVerifiedCustomers = User::selectRaw('count(*) as total_verified_customers')
            ->where('account_status', 'verified')
            ->where('user_role', 'customer')
            ->first();

        $totalVerifiedVendors = User::selectRaw('count(*) as total_verified_vendors')
            ->where('account_status', 'verified')
            ->where('user_role', 'vendor')
            ->first();

        $totalUnverifiedUsers = User::selectRaw('count(*) as total_unverified_users')
        ->where('user_role', '!=', 'admin')
            ->where('account_status', 'pending')
            ->first();

        $totalUnverifiedCustomers = User::selectRaw('count(*) as total_unverified_customers')
            ->where('account_status', 'pending')
            ->where('user_role', 'customer')
            ->first();

        $totalUnverifiedVendors = User::selectRaw('count(*) as total_unverified_vendors')
            ->where('account_status', 'pending')
            ->where('user_role', 'vendor')
            ->first();

        return [
            'total_users' => $totalUsers->total_users,
            'total_vendors' => $totalVendors->total_vendors,
            'total_customers' => $totalCustomers->total_customers,
            'total_card_requests' => $totalCardRequested->total_card_requests,

            'total_verified_users' => $totalVerifiedUsers->total_verified_users,
            'total_verified_customers' => $totalVerifiedCustomers->total_verified_customers,
            'total_verified_vendors' => $totalVerifiedVendors->total_verified_vendors,

            'total_unverified_users' => $totalUnverifiedUsers->total_unverified_users,
            'total_unverified_customers' => $totalUnverifiedCustomers->total_unverified_users,
            'total_unverified_vendors' => $totalUnverifiedVendors->total_unverified_vendors
        ];
    }
}
