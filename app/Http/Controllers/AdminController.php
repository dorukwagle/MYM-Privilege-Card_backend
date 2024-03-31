<?php

namespace App\Http\Controllers;

use App\Helpers\CredentialHelper;
use App\Helpers\PaymentsHelper;
use App\Jobs\MakeAnnouncements;
use App\Jobs\SendPostNotifications;
use App\Models\Card;
use App\Models\PaymentHistory;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
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

    public function generateCardNumber()
    {
        $totalCards = Card::count();
        $firstPart = Carbon::now()->format('Ym');

        return ['id'  => $firstPart. "-" . sprintf('%06d', ($totalCards + 1))];
    }

    public function assignCard(Request $request, $userId)
    {
        $validation = Validator::make($request->all(), [
            'card_id' => ['required', 'unique:cards,id'],
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
                'user_role'
            ]);

        return $users;
    }

    public function getPostRequests(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'size' => ['sometimes', 'nullable', 'numeric', 'min:1'],
            'page' => ['sometimes', 'nullable', 'numeric', 'min:1'],
            'signup' => ['sometimes', 'nullable', 'in:yes,no']
        ]);

        $size = $request->filled('size') ? $request->query('size') : 1;
        $page = $request->filled('page') ? $request->query('page') : 1;
        $signup = $request->filled('signup') && $request->query('signup') == 'yes' ? true : false;

        if ($validation->fails())
            return response($validation->errors(), 400);

        return Post::where('is_signup_offer', $signup)
            ->where('approved', false)
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get();
    }

    public function approvePost($postId)
    {
        $post = Post::find($postId);
        if (!$post) return response(['err' => 'not found'], 404);

        $post->approved = true;
        $post->save();

        // send notification to nearby customers with preferred categories
        SendPostNotifications::dispatch($post);

        return ['status' => 'ok'];
    }

    public function makeAnnouncement(Request $request) {
        $validation = Validator::make($request->all(), [
            'title' => ['required', 'string', 'min:3'],
            'body' => ['required', 'string', 'min:10'],
            'user_type' => ['required', 'in:all,customer,vendor']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $post = Post::create([
            'body' => $request->body,
            'category_id' => 0,
            'title' => $request->title,
            'user_id' => $request->user->id,
            'approved' => true
        ]);

        MakeAnnouncements::dispatch($post, $request->user_type);
        return ['status' => 'ok'];
    }

    public function rejectPost($postId) {
        Post::find($postId)->delete();
        return ['status' => 'ok'];
    }

    public function approveSignupPost($userId)
    {
        $post = Post::where('user_id', $userId)
            ->where('signup', true)
            ->update([
                'approved' => true
            ]);

        // send notification to nearby customers with preferred categories
        SendPostNotifications::dispatch($post);
        return ['status' => 'ok'];
    }

    public function getAdmins() {
        return User::where('user_role', 'admin')->get([
            'id',
            'full_name',
            'contact_no',
            'email',
            'gender',
            'created_at'
        ]);
    }

    public function addAdminAccount(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'regex:/^[\pL\s]+ [\pL\s]+$/u'],
            'contact_no' => ['required', 'regex:/^(\+?\d{6,15})$/'],
            'email' => ['required', 'email', 'unique:users'],
            'gender' => ['required', 'in:male,female,others']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $creds = new CredentialHelper($request->email);
        $password = $creds->getPassword();

        User::create([
            'full_name' => $request->full_name,
            'contact_no' => $request->contact_no,
            'email' => $request->email,
            'gender' => $request->gender,
            'password' => Hash::make($password),
            'user_role' => 'admin',
            'account_status' => 'verified',
            'email_verified' => true,
            'has_logged_in' => false,
            'referral_code' => Carbon::now()->timestamp
        ]);

        $creds->sendCredentials();
        return ['status' => 'ok'];
    }

    public function removeAdminAccount($id)
    {
        User::where('id', $id)->delete();
        return ['status' => 'pk'];
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
