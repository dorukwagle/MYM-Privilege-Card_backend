<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    private $uploadPath = 'public/uploads/cdn';

    public function profileIconUpdate(Request $request) {
        if (!$request->hasFile('profile_icon'))
            return response(['profile_icon' => 'file required'], 400);

        $profileIcon = $request->file('profile_icon')->store($this->uploadPath);

        $user = User::find($request->user->id);
        $user->profile_icon = $profileIcon;
        $user->save();

        return ['status' => 'ok'];
    }

    public function bannerIconUpdate(Request $request) {
        if (!$request->hasFile('banner_icon'))
            return response(['banner_icon' => 'file required'], 400);

        $bannerIcon = $request->file('banner_icon')->store($this->uploadPath);

        $user = User::find($request->user->id);
        $user->banner_icon = $bannerIcon;
        $user->save();

        return ['status' => 'ok'];
    }

    public function orgBioUpdate(Request $request) {
        $validation = Validator::make($request->all(), [
            'about_org' => ['required', 'string', 'max:500', 'min:50']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::find($request->user->id);
        $user->about_org = $request->about_org;
        $user->save();

        return ['status' => 'ok'];
    }

    public function changePassword(Request $request) {
        $validation = Validator::make($request->all(), [
            'new_password' => ['required', 'string', 'min:6'],
            'current_password' => 'required',
            'logout_all' => ['required', 'boolean']
        ]);
        
        if ($validation->fails())
            return response($validation->errors(), 400);
    
        $user = User::find($request->user->id);

        if(!Hash::check($request->current_password, $user->password))
            return response(['err' => 'incorrect current password'], 400);

        $user->password = Hash::make($request->new_password);
        $user->save();

        if ($request->logout_all) 
            Session::where('user_id', $user->id)->delete();
        
        return ['status' => 'ok'];
    }
    
    public function getProfile(Request $request) {
        $info = array_filter($request->user->toArray(), function($value) {
            return $value !== null;
        });

        unset($info['user_role']);
        unset($info['is_vend_cust']);
        unset($info['updated_at']);
        unset($info['password']);
        
        return $info;
    }

    public function updateEmail(Request $request) {
        $validation = Validator::make($request->all(), [
            'email' => ['required', 'email']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        
    }

    public function verifyEmail(Request $request)  {
        
    }
}
