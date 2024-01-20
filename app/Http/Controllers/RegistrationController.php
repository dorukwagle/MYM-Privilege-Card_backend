<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\UsersCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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

        $user = User::create([
            'full_name' => $request->full_name,
            'gender' => $request->gender,
            'contact_no' => $request->contact_no,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_role' => 'customer',
            'dob' => $request->dob,
            'address' => $request->address,
            'account_status' => 'requested' 
        ]);

        $this->addCategories($request->preferred_categories, $user->id);

        return [
            'status' => 'ok',
            'user_id' => $user->id,
            'email' => $user->email
        ];
    }

    public function registerCustomer(Request $request)
    {
        $validation = $this->initialCustomerValidation($request);

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

        $this->addCategories($request->preferred_categories, $user->id);
    
        return response()->json([
            'status' => 'ok',
            'user_id' => $user->id,
            'email' => $user->email
        ]);
    }

    // add new vendor account by admin
    public function addVendorAccount(Request $request)
    {
        return $this->createVendor($request, false);
    }

    // vendor registration
    public function registerVendor(Request $request)
    {
        return $this->createVendor($request, true);
    }

    private function createVendor(Request $request, $isLoggedIn)
    {
        $validation = Validator::make($request->all(), [
            'location' => ['required', 'regex:/^-?([1-8]?\d(?:\.\d+)?|90(?:\.0+)?), -?(180(?:\.0+)?|1[0-7]\d(?:\.\d+)?|\d{1,2}(?:\.\d+)?)$/'],
            'org_name' => 'required',
            'full_name' => ['required', 'string', 'regex:/^[\pL\s]+ [\pL\s]+$/u'],
            'contact_no' => ['required', 'regex:/^(\+?\d{6,15})$/'],
            'address' => 'required',
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:6'],
            'product_categories' => ['required', 'array'],
            'product_categories.*' => 'exists:categories,id',
            'org_vat_card' => 'required',
            'org_registration_card' => 'required',
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $signupOffer = $this->extractSignupOffer($request);
        if ($signupOffer && $signupOffer->fails)
            return response($signupOffer->errors, 400);

        $fileErrs = [];

        if (!$request->hasFile('org_vat_card'))
            $fileErrs[] = ['org_vat_card' => 'file required'];

        if (!$request->hasFile('org_registration_card'))
            $fileErrs[] = ['org_registration_card' => 'file required'];

        if (!!$fileErrs)
            return response($fileErrs, 400);

        $orgVatCard = $request->file('org_vat_card')->store($this->uploadPath);
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
            'user_role' => 'vendor',
            'has_logged_in' => $isLoggedIn
        ]);

        $this->addCategories($request->product_categories, $user->id);

        if ($signupOffer) $this->createSignupOffer($user->id, $signupOffer);

        return response()->json([
            'status' => 'ok',
            'user_id' => $user->id,
            'email' => $user->email
        ]);
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

        return (object) array(
            'title' => $request->title,
            'body' => $request->body,
            'category_id' => $request->category_id,
            'icon' => $icon,
            'errors' => $validation->errors(),
            'fails' => $validation->fails()
        );
    }

    private function createSignupOffer($userId, $offer)
    {
        Post::create([
            'icon' => $offer->icon,
            'body' => $offer->body,
            'category_id' => $offer->category_id,
            'title' => $offer->title,
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
            'password' => ['required', 'string', 'min:6'],
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
}
