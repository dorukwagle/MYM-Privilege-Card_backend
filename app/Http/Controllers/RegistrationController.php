<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\User;
use App\Models\UsersCategory;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegistrationController extends Controller
{
    public function registerCustomer(Request $request) {
        $request->validate([
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

        $profile_icon = $request->file('profile_icon')->store('public/uploads/cdn');

        $user = User::create([
            'full_name' => $request->full_name,
            'dob' => $request->dob,
            'gender' => $request->gender,
            'address' => $request->address,
            'location' => $request->location,
            'profile_icon' => $profile_icon,
            'contact_no' => $request->contact_no,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_role' => 'customer'
        ]);

        $preferred = [];
        foreach ($request->preferred_categories as $category_id) {
            $preferred[] = [
                'user_id' => $user->id,
                'category_id' => $category_id
            ];
        }

        UsersCategory::create($preferred);

        return response()->json(['status' => 'ok']);
    }

    public function registerVendor(Request $request) {
        $request->validate([
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

        
    }
}
