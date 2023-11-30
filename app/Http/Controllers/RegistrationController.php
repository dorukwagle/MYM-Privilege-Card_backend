<?php

namespace App\Http\Controllers;

use App\Models\User;
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
            'user_role' => ['required', 'string', 'in:customer,vendor']
        ]);

        $profile_icon = $request->file('profile_icon')->store('public/uploads/cdn');

        User::create([
            'full_name' => $request->full_name,
            'dob' => $request->dob,
            'gender' => $request->gender,
            'address' => $request->address,
            'location' => $request->location,
            'profile_icon' => $profile_icon,
            'contact_no' => $request->contact_no,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_role' => $request->user_role
        ]);
    }

    public function registerVendor(Request $request) {

    }
}
