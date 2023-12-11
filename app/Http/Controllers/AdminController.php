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

    public function getUsers(Request $request)
    {
        /**
         * /api/users?type=customer
         * /api/users?type=customer&request=expired
         * /api/users?type=vendor
         * /api/users/user_id=1
         */
        $userId = $request->query('user_id');
        $userType = $request->query('type');
        $requestType = $request->query('request'); //expired or new

        if (!$userId && !$userType)
            return response(['err' => 'not found'], 404);

        $columns = [
            'id',
            'profile_icon',
            'contact_no',
            'email',
            'full_name',
            'org_name'
        ];

        if ($userId)
            return User::where('id', $userId)->first($columns);

        if ($userType == 'vendor')
            return User::where('user_role', 'vendor')
                ->where('account_status', 'pending')
                ->get($columns);

        if ($userType != 'customer')
            return response(['err' => 'not found'], 404);

        $user = User::where('user_role', 'customer');
        if ($requestType == 'expired')
            $user->whereDate('expires', '<', Carbon::now());
        else $user->where('account_status', 'pending');

        return $user->get($columns);
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
            'card_id' => ['required', 'min:19', 'max:19']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::find($userId);
        if (!$this->isValidAccount($user, 'customer')) return $this->notFound();

        $user->account_status = 'verified';
        $user->expires = Carbon::now()->addMinutes(5);
        $user->payment_status = 'unpaid';

        Card::create([
            'user_id' => $user->id,
            'id' => $request->card_id
        ]);

        $user->save();

        return ['status' => 'ok'];
    }
}
