<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

use function PHPUnit\Framework\returnSelf;

class AdminController extends Controller
{
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
}
