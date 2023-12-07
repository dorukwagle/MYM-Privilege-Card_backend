<?php

namespace App\Http\Controllers;

use App\Helpers\OtpHelper;
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

        OtpHelper::sendOtp($request->user->id, $request->email);
        return ['status' => 'ok'];
    }

    public function verifyEmail(Request $request)  {
        $validation = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp' => ['required', 'numeric']
        ]);

        if ($validation->fails()) 
            return response($validation->errors(), 400);
        
        $validOtp = OtpHelper::verifyOtp($request->user->id, $request->otp, $request->email);
        if (!$validOtp) return response(['err' => 'unable to verify otp']);

        User::where('id', $request->user->id)
                    ->update([
                        'email' => $request->email,
                        'email_verified' => true
                    ]);
        
        return ['status' => 'ok'];
    }

    public function updateProfile(Request $request)  {
        $validation = Validator::make($request->all(), [
            'location' => ['sometimes', 'nullable', 'regex:/^-?([1-8]?\d(?:\.\d+)?|90(?:\.0+)?)\s*,\s*-?(180(?:\.0+)?|1[0-7]\d(?:\.\d+)?|\d{1,2}(?:\.\d+)?)$/'],
            'full_name' => ['required', 'string', 'regex:/^[\pL\s]+ [\pL\s]+$/u'],
            'org_name' => 'sometimes',
            'contact_no' => ['required', 'regex:/^(\+?\d{6,15})$/'],
            'gender' => ['sometimes', 'nullable', 'in:male,female,others'],
            'address' => 'required',
            'dob' => ['sometimes', 'nullable', 'date'],
            'about_org' => ['sometimes', 'nullable', 'min:50', 'max:500']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::find($request->user->id);
        
        $user->full_name = $request->full_name;
        $user->contact_no = $request->contact_no;
        $user->address = $request->address;

        if ($request->location)
            $user->location = $request->location;
        if ($request->org_name)
            $user->org_name = $request->org_name;
        if ($request->gender)
            $user->gender = $request->gender;
        if ($request->dob)
            $user->dob = $request->dob;
        if ($request->about_org)
            $user->about_org = $request->about_org;

        $user->save();

        return ['status' => 'ok'];
    }
}
