<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UsersCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends Controller
{
    private $uploadPath = 'public/uploads/cdn';

    public function registerCustomer(Request $request) {
        $validation = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'regex:/^[\pL\s]+ [\pL\s]+$/u'],
            'contact_no' => ['required', 'regex:/^(\+?\d{6,15})$/'],
            'gender' => ['required', 'string', 'in:male,female,others'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if($validation->fails())
            return response($validation->errors(), 400);

        $user = User::create([
            'full_name' => $request->full_name,
            'gender' => $request->gender,
            'contact_no' => $request->contact_no,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_role' => 'customer'
        ]);

        return response()->json([
            'status' => 'ok',
            'user_id' => $user->id,
            'email' => $user->email
        ]);
    }

    public function customerKyc(Request $request) {
        $validation = Validator::make($request->all(), [
            'user_id' => ['required', 'number'],
            'location' => ['required', 'regex:/^-?([1-8]?\d(?:\.\d+)?|90(?:\.0+)?), -?(180(?:\.0+)?|1[0-7]\d(?:\.\d+)?|\d{1,2}(?:\.\d+)?)$/'],
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
        $user = User::find($request->user_id);

        if (!$user) return response(['err' => 'user_id not found'], 404);

        // convert location string to point
        $latLong = explode(", ", $request->location);
        $location = DB::raw("POINT($latLong[1], $latLong[0])");
        
        $user->full_name = $request->full_name;
        $user->dob = $request->dob;
        $user->gender = $request->gender;
        $user->address = $request->address;
        $user->coordinates = $location;
        $user->profile_icon = $profileIcon;
        $user->contact_no = $request->contact_no;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->user_role = 'customer';
        $user->account_status = 'requested';

        $user->save();
        
        foreach ($request->preferred_categories as $category_id) {
            UsersCategory::create([
                 'user_id' => $user->id,
                'category_id' => $category_id
            ]);
        }

        return response()->json([
            'status' => 'ok', 
            'user_id' => $user->id, 
            'email' => $user->email
        ]);
    }

    public function registerVendor(Request $request) {
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
            'user_role' => 'vendor'
        ]);

        foreach ($request->product_categories as $category_id) {
            UsersCategory::create([
                'user_id' => $user->id,
                'category_id' => $category_id
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'user_id' => $user->id,
            'email' => $user->email
        ]);
    }
}