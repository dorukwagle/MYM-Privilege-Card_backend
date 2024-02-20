<?php

namespace App\Http\Controllers;

use App\Helpers\CredentialHelper;
use App\Models\Post;
use App\Models\User;
use App\Models\UsersCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    private $uploadPath = 'public/uploads/cdn';

    // add customer account by admin
    public function addCustomerAccount(Request $request) {
        $initialValidation = $this->initialCustomerValidation($request);
        $kycValidation = $this->customerKycValidation($request);

        if ($initialValidation->fails())
            return response($initialValidation->errors(), 400);
        if ($kycValidation->fails())
            return response($kycValidation->errors(), 400);

        $creds = new CredentialHelper($request->email);

        $user = User::create([
            'full_name' => $request->full_name,
            'gender' => $request->gender,
            'contact_no' => $request->contact_no,
            'email' => $request->email,
            'password' => Hash::make($creds->getPassword()),
            'user_role' => 'customer',
            'dob' => $request->dob,
            'address' => $request->address,
            'account_status' => 'requested',
            'email_verified' => true,
            'has_logged_in' => false,
            'referred_by' => $request->referred_by,
            'referral_code' => $this->generateReferralCode($request->email)
        ]);

        if ($request->preferred_categories)
            $this->addCategories($user->id, $request->preferred_categories);

        $creds->sendCredentials();

        return [
            'status' => 'ok',
            'user_id' => $user->id,
            'email' => $user->email
        ];
    }

    public function registerCustomer(Request $request)
    {
        $validation = $this->initialCustomerValidation($request);
        $pwValidation = Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:8']
        ]);

        if ($pwValidation->fails())
            return response($pwValidation->errors(), 400);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::create([
            'full_name' => $request->full_name,
            'gender' => $request->gender,
            'contact_no' => $request->contact_no,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_role' => 'customer',
            'has_logged_in' => true,
            'referred_by' => $request->referred_by,
            'referral_code' => $this->generateReferralCode($request->email)
        ]);

        return [
            'status' => 'ok',
            'user_id' => $user->id,
            'email' => $user->email
        ];
    }

    public function customerKyc(Request $request)
    {
        $validation = $this->customerKycValidation($request);

        if ($validation->fails())
            return response($validation->errors(), 400);

        if (!$request->hasFile('profile_icon'))
            return response(['profile_icon' => 'file required'], 400);

        $profileIcon = $request->file('profile_icon')->store($this->uploadPath);
        $user = User::find($request->user->id);

        if (!$user) return response(['err' => 'user_id not found'], 404);

        $user->dob = $request->dob;
        $user->address = $request->address;
        $user->profile_icon = $profileIcon;
        $user->account_status = 'requested';

        $user->save();

        $this->addCategories($user->id, $request->preferred_categories);
    
        return response()->json([
            'status' => 'ok',
            'user_id' => $user->id,
            'email' => $user->email
        ]);
    }

    // add new vendor account by admin
    public function addVendorAccount(Request $request)
    {
        $validation = $this->vendorValidation($request);
        if ($validation->fails())
            return response($validation->errors(), 400);

        $creds = new CredentialHelper($request->email);
        $request->merge(['password' => $creds->getPassword()]) ;

        $vendor = $this->createVendor($request, true);
        if ($vendor['status']== 'ok')
            $creds->sendCredentials();

        return $vendor;
    }

    // vendor registration
    public function registerVendor(Request $request)
    {
        $validation = $this->vendorValidation($request);
        $pwValidation = Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:8']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);
        if ($pwValidation->fails())        
            return response($pwValidation->errors(), 400);

        return $this->createVendor($request, false);
    }

    private function createVendor(Request $request, $addedByAdmin)
    {
        $signupOffer = $this->extractSignupOffer($request);
        if ($signupOffer && $signupOffer['fails'])
            return response($signupOffer['errors'], 400);

        $orgVatCard = '';
        if ($request->hasFile('org_vat_card'))
            $orgVatCard = $request->file('org_vat_card')->store($this->uploadPath);

        $registrationCertificate = '';
        if ($request->hasFile('org_registration_card'))
            $registrationCertificate = $request->file('org_registration_card')->store($this->uploadPath);

        // convert location string to point
        $latLong = explode(", ", $request->location);
        $location = DB::raw("POINT($latLong[1], $latLong[0])");

        $user = User::create([
            'full_name' => $request->full_name,
            'org_name' => $request->org_name,
            'address' => $request->address,
            'coordinates' => $location,
            'contact_no' => $request->contact_no,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'org_vat_card' => $orgVatCard,
            'org_registration_card' => $registrationCertificate,
            'org_pan_no' => $request->org_pan_no,
            'user_role' => 'vendor',
            'account_status' => $addedByAdmin ? 'verified' : 'pending',
            'email_verified' => $addedByAdmin,
            'has_logged_in' => !$addedByAdmin,
            'referred_by' => $request->referred_by,
            'referral_code' => $this->generateReferralCode($request->email),
            'product_id' => Str::orderedUuid()
        ]);

        $this->addCategories($user->id, $request->product_categories);

        if ($signupOffer) $this->createSignupOffer($user->id, $signupOffer);

        return [
            'status' => 'ok',
            'user_id' => $user->id,
            'email' => $user->email
        ];
    }

    private function extractSignupOffer(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'title' => ['required', 'string', 'min:3'],
            'body' => ['required', 'string', 'min:10'],
            'category_id' => ['required', 'exists:categories,id'],
            'icon' => ['sometimes', 'nullable']
        ]);

        if (!$request->filled('title') || !$request->filled('body'))
            return null;

        $icon = '';
        if ($request->hasFile('icon'))
            $icon = $request->file('icon')->store($this->uploadPath);

        return [
            'title' => $request->title,
            'body' => $request->body,
            'category_id' => $request->category_id,
            'icon' => $icon,
            'errors' => $validation->errors(),
            'fails' => $validation->fails()
        ];
    }

    private function createSignupOffer($userId, $offer)
    {
        Post::create([
            'icon' => $offer["icon"],
            'body' => $offer["body"],
            'category_id' => $offer["category_id"],
            'title' => $offer["title"],
            'user_id' => $userId,
            'is_signup_offer' => true
        ]);
    }

    private function initialCustomerValidation(Request $request)
    {
        return Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'regex:/^[\pL\s]+ [\pL\s]+$/u'],
            'contact_no' => ['required', 'regex:/^(\+?\d{6,15})$/'],
            'gender' => ['required', 'string', 'in:male,female,others'],
            'email' => ['required', 'email', 'unique:users'],
            'referred_by' => ['sometimes', 'nullable', 'exists:users,referral_code']
        ]);
    }

    private function vendorValidation(Request $request) {
        return Validator::make($request->all(), [
            'location' => ['required', 'regex:/^-?([1-8]?\d(?:\.\d+)?|90(?:\.0+)?), -?(180(?:\.0+)?|1[0-7]\d(?:\.\d+)?|\d{1,2}(?:\.\d+)?)$/'],
            'org_name' => 'required',
            'full_name' => ['required', 'string', 'regex:/^[\pL\s]+ [\pL\s]+$/u'],
            'contact_no' => ['required', 'regex:/^(\+?\d{6,15})$/'],
            'address' => 'required',
            'email' => ['required', 'email', 'unique:users'],
            'org_pan_no' => ['required'],
            'product_categories' => ['required', 'array'],
            'product_categories.*' => 'exists:categories,id',
            'org_vat_card' => ['sometimes', 'nullable'],
            'org_registration_card' => ['sometimes', 'nullable'],
            'referred_by' => ['sometimes', 'nullable', 'exists:users,referral_code']
        ]);
    }

    private function customerKycValidation(Request $request)
    {
        return Validator::make($request->all(), [
            'address' => 'required',
            'dob' => ['required', 'date'],
            'preferred_categories' => ['required', 'array'],
            'preferred_categories.*' => 'exists:categories,id'
        ]);
    }

    private function addCategories($userId, $categories) {
        foreach ($categories as $category_id) {
            UsersCategory::create([
                'user_id' => $userId,
                'category_id' => $category_id
            ]);
        }
    }

    private function generateReferralCode($string) {
        $str = substr($string, 0, 8);
        return str_shuffle($str);
    }
}
