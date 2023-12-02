<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/categories', [CategoriesController::class, 'getProductCategories']);

Route::post('/register/customer', [RegistrationController::class, 'registerCustomer']);
Route::post('/register/vendor', [RegistrationController::class, 'registerVendor']);

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

Route::put('/update-email', []); //update profile email

// change unverified email in case mistakenly entered and submitted
Route::post('/verify/change-email', []); 

Route::post('/verify/send-otp', []);
Route::post('/verify/verify-email', []);

// InfoController for getting information of a customer or vendor or admin for contact purposes
// ProfileController for user's profile updating and retrieving