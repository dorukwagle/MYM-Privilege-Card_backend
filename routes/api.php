<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::middleware('auth')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::get('/categories', [CategoriesController::class, 'getProductCategories']);
/**
 * Returns the list of categories:
 * [{id: '1', category: 'Cosmetics'}, .....]
 */
Route::post('/category', [CategoriesController::class, 'addCategory']);
/**
 * body parameters:
 * category (name of the category)
 * 
 * ON SUCCESS: returns {status: 'ok'}
 * Errors:
 * validation errors with 400 code
 * 
 */
Route::delete('/category/{id}', [CategoriesController::class, 'deleteCategory'])
            ->whereNumber('id');
/**
 * ON SUCCESS: {status: ok}
 * 
 * Errors:
 * 400 with {err: 'category not found'}
 */

Route::post('/register/customer', [RegistrationController::class, 'registerCustomer']);
/**
 * Body parameters:
 * location (coordinates of the actual location of the user, from google map)
 * full_name (full name of user at least two words required)
 * contact_no (user phone no.)
 * gender   
 * address  (name of city or locality)
 * dob
 * email (sign up email )
 * password
 * preferred_categories (list of categories id. e.g. [1, 2, 3])
 * profile_icon (photo of the user)
 * 
 * ON SUCCESS: returns e.g. {'status' : 'ok', user_id: 2, email: 'example@email'}
 * 
 * ERROR:
 * returns 400 code with validation error if invalid field data are sent
 * returns 400 if photo not uploaded
 */

Route::post('/register/vendor', [RegistrationController::class, 'registerVendor']);
/**
 * Body parameters:
 * location
 * org_name (name of business or organization)
 * contact_no
 * address (a bit more detailed, as it may appear in shop info when user views it)
 * estd (date of establishment of the org.)
 * email
 * password
 * org_pan_no (registration pan no. of the business)
 * about_org (a short and comprehensive bio of the business)
 * 
 * profile_icon (profile photo or the shop logo)
 * banner_icon (background page photo)
 * org_registration_card (photo of business registration certificate)
 * 
 * ON SUCCESS: same as above
 * ERROR: same as above
 */

Route::post('/auth/login', [AuthController::class, 'login']);
/**
 * Body parameters:
 * email
 * password
 * 
 * Success: {'status' : 'ok', access_token: '<token>'}
 * 
 * Errors:
 *  if fields not sent, user not found, incorrect password: returns {'err': 'incorrect email or password'} with 400
 * if email not verified:
 *  {'user_id' : <id>, email: <email>, email_status: 'unverified'}
 * 
 * 
 * */

Route::post('/auth/logout', [AuthController::class, 'logout']);
/**
 * ON success: {status: 'ok'}
 */

// Route::put('/update-email', []); //update profile email

Route::post('/verify/change-email', [VerificationController::class, 'changeEmail']); 
/**
 * change unverified email in case mistakenly entered and submitted
 * Body parameters: 
 * user_id (id of the user)
 * email (new email )
 * password (user's current password)
 * 
 * On Success: {'status': 'ok'}
 * Errors:
 * code 400 with validation errors
 * user not found: 400 with {err: 'invalid user_id'}
 * incorrect password: 400 with {err: 'incorrect password'}
 * if email is already verified: 400 with {err: 'email already verified'}
 */

Route::post('/verify/send-otp', [VerificationController::class, 'startEmailVerification']);
/**
 * Body Parameters:
 * user_id
 * email
 * 
 * Success: {status: 'ok'}
 * 
 * Errors:
 * if validation error, user not found, or email already verified:
 *  400 with {'err' => 'email or user does not exist'}
 */

Route::post('/verify/verify-email', [VerificationController::class, 'verifyEmail']);
/**
 * Body Parameters:
 * user_id
 * otp (otp that is sent to email)
 * 
 * ON SUCCESS : {'status':  ok}
 * 
 * ON ERROR:
 * validation errors with 400
 * user not found: 400 with {err: 'user not found'}
 * if otp expires: 400 with {err: 'otp expired'}
 */


// InfoController for getting information of a customer or vendor or admin for contact purposes
// ProfileController for user's profile updating and retrieving

/**
 * TODO:
 * implement authorization role based
 * route protection
 * 
 */

//test reamining routes:
// logout