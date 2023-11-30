<?php

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