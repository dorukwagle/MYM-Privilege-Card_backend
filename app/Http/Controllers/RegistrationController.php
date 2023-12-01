<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UsersCategory;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends Controller
{
    private $uploadPath = 'public/uploads/cdn';

    public function registerCustomer(Request $request) {
        $validation = Validator::make($request->all(), [
            'location' => 'required',
            'profile_icon' => 'required',
            'full_name' => ['required', 'string', 'regex:/^[\pL\s]+ [\pL\s]+$/u'],
            'contact_no' => ['required', 'regex:/^(\+?\d{6,15})$/'],
            'gender' => ['required', 'string', 'in:male,female,others'],
            'address' => 'required',
            'dob' => ['required', 'date'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:6'],
            'preferred_categories' => ['required', 'array'],
            'preferred_categories.*' => 'exists:categories,id'
        ]);

        if($validation->fails())
            return response($validation->errors(), 400);
        
        if (!$request->hasFile('profile_icon'))
            return response(['profile_icon' => 'file required'], 400);

        $profileIcon = $request->file('profile_icon')->store($this->uploadPath);

        $user = User::create([
            'full_name' => $request->full_name,
            'dob' => $request->dob,
            'gender' => $request->gender,
            'address' => $request->address,
            'location' => $request->location,
            'profile_icon' => $profileIcon,
            'contact_no' => $request->contact_no,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_role' => 'customer'
        ]);

        foreach ($request->preferred_categories as $category_id) {
            UsersCategory::create([
                 'user_id' => $user->id,
                'category_id' => $category_id
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function registerVendor(Request $request) {
        $validation = Validator::make($request->all(), [
            'location' => 'required',
            'profile_icon' => 'required',
            'banner_icon' => 'required',
            'org_name' => 'required',
            'contact_no' => ['required', 'regex:/^(\+?\d{6,15})$/'],
            'address' => 'required',
            'estd' => ['required', 'date'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:6'],
            'product_categories' => ['required', 'array'],
            'product_categories.*' => 'exists:categories,id',
            'org_pan_no' => 'required',
            'org_registration_card' => 'required',
            'about_org' => ['required', 'max:500']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);
        $fileErrs = [];

        if (!$request->hasFile('profile_icon'))
            $fileErrs[] = ['profile_icon' => 'file required'];
        
        if (!$request->hasFile('banner_icon'))
            $fileErrs[] = ['banner_icon' => 'file required'];

         if (!$request->hasFile('org_registration_card'))
            $fileErrs[] = ['org_registration_card' => 'file required'];

        if (!!$fileErrs)
            return response($fileErrs, 400);

        $profileIcon = $request->file('profile_icon')->store($this->uploadPath);
        $bannerIcon = $request->file('banner_icon')->store($this->uploadPath);
        $registrationCertificate = $request->file('org_registration_card')->store($this->uploadPath);

        $user = User::create([
            'full_name' => $request->org_name,
            'dob' => $request->estd,
            'address' => $request->address,
            'location' => $request->location,
            'profile_icon' => $profileIcon,
            'contact_no' => $request->contact_no,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_role' => 'vendor',
            'banner_icon' => $bannerIcon,
            'org_registration_card' => $registrationCertificate,
            'about_org' => $request->about_org,
            'org_pan_no' => $request->org_pan_number,
        ]);

        $productCategories = [];
        foreach ($request->product_categories as $category_id) {
            UsersCategory::create([
                'user_id' => $user->id,
                'category_id' => $category_id
            ]);
        }
        
        return response()->json(['status' => 'ok']);
    }
}
