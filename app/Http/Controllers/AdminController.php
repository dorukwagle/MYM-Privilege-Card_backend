<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

use function PHPUnit\Framework\returnSelf;

class AdminController extends Controller
{

    private function notFound() {
        return response(['err' => 'not found'], 404);
    }

    private function isVendorAccount($user) {
        if (!$user) return false;

        if ($user->user_role === 'admin') return false;

        if ($user->user_role !== 'vendor' && (!$user->is_vend_cust))
            return false;

        return true;
    }

    public function getUsers(Request $request) {
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

    public function verifyVendor($vendorId) {
        $user = User::find($vendorId);
        if (!$this->isVendorAccount($user)) return $this->notFound();

        $user->account_status = 'verified';
        $user->save();

        return ['status' => 'ok'];
    }

    public function rejectVendor($vendorId) {
        $user = User::find($vendorId);
        if (!$this->isVendorAccount($user)) return $this->notFound();

        $vatCard = $user->org_vat_card;
        $registrationCard = $user->org_registration_card;
        
        if ($vatCard) File::delete($vatCard);
        if ($registrationCard) File::delete($registrationCard);

        $user->delete();

        return ['status' => 'ok'];
    }
}
