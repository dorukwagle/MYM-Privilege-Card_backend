<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\AuthorizationMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Monolog\Registry;
use Symfony\Component\HttpKernel\Profiler\Profile;

// Route::middleware('auth')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::get('/categories', [CategoriesController::class, 'getProductCategories']);
/**
 * Returns the list of categories:
 * [{id: '1', category: 'Cosmetics'}, .....]
 */

Route::middleware('auth')->group(function () {
        Route::middleware('auth.admin')
                ->post('/category', [CategoriesController::class, 'addCategory']);
        /**
         * body parameters:
         * category (name of the category)
         * 
         * ON SUCCESS: returns {status: 'ok'}
         * Errors:
         * validation errors with 400 code
         * 
         */
        Route::middleware('auth.admin')
                ->delete('/category/{id}', [CategoriesController::class, 'deleteCategory'])
                ->whereNumber('id');
        /**
         * ON SUCCESS: {status: ok}
         * 
         * Errors:
         * 400 with {err: 'category not found'}
         */
        Route::middleware('auth.customer')
                ->post('/kyc/customer', [RegistrationController::class, 'customerKyc']);

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

        Route::post('/profile/profile-icon', [ProfileController::class, 'profileIconUpdate']);

        Route::middleware('auth.vendor')
                ->post('/profile/banner-icon', [ProfileController::class, 'bannerIconUpdate']);

        Route::middleware('auth.vendor')
                ->post('/profile/org-bio', [ProfileController::class, 'orgBioUpdate']);

        Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);

        // get user's profile, accessible only to the user
        Route::get('/profile/get-profile', [ProfileController::class, 'getProfile']);

        Route::post('/profile/update-email', [ProfileController::class, 'updateEmail']);
        /**
         * Body parameters:
         * email (new email)
         */

        Route::post('/profile/verify/verify-email', [ProfileController::class, 'verifyEmail']);
        /**
         * Body parameters:
         * otp
         * email (new email)
         */

        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/info/vendor/{id}', [ProfileController::class, 'getVendorInfo'])
                ->whereNumber('id');

        Route::get('/info/admin', [ProfileController::class, 'getAdminInfo']);

        Route::middleware('auth.admin')
                ->get('/user-requests', [AdminController::class, 'getUserRequests']);
        /**
         * /api/users?type=customer (returns new unverified users who paid)
         * /api/users?type=customer&expired=yes (returns expired users who paid)
         * /api/users?type=customer&expired=yes&paid=no (returns expired users who haven't paid)
         * /api/users?type=customer&paid=no (returns new users who haven't paid)
         * /api/users?type=customer&expired=yes (returns card expired users)
         * /api/users?type=vendor 
         */

        Route::middleware('auth.admin')
                ->post('/users/verify/vendor/{id}', [AdminController::class, 'verifyVendor'])
                ->whereNumber('id');

        Route::middleware('auth.admin')
                ->post('/users/reject/vendor/{id}', [AdminController::class, 'rejectVendor'])
                ->whereNumber('id');

        Route::middleware('auth.admin')
                ->get('/card/generate', [AdminController::class, 'generateCardNumber']);
        // returns random card number of 16 digits

        Route::middleware('/auth.admin')
                ->post('/users/verify/customer/{id}', [AdminController::class, 'assignCard'])
                ->whereNumber('id');
        //assign card to the user
        // body: card_id

        Route::put('/profile/update-profile', [ProfileController::class, 'updateProfile']);
});

Route::post('/register/customer', [RegistrationController::class, 'registerCustomer']);

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

Route::post('/auth/forget-password', [ResetPasswordController::class, 'sendResetOtp']);
Route::put('/auth/reset-password', [ResetPasswordController::class, 'resetPassword']);

/**
 * TODO:
 * admin: reject customer /cancel on verify
 * admin: renew customer card
 * admin: get request details {return user info and payment status i.e. unpaid or paid amount}
 * admin: get user details {return user info and payment histories} {option to unverify/verify}
 * 
 * 
 * admin: search users by email or name
 * admin: search users and their payment details
 * admin: unverify mistakenly verified users 
 * 
 * admin: manual payment
 * 
 * user-> profile -> payment history
 * 
 * 
 * packages system {
 * 1 year - Rs 1000
 * 2 years - Rs 1800
 * } etc, editable and removable by admin
 */

 // change getUsers logic to return payment status, get profile logic to return if the user is dual_user
 // getUsers filters: new user: paid/unpaid // expired: paid/unpaid

 /**
  * LATERAL CHANGES
  * add new database table for packages
  * add new categories in users table: preferred_categories and product_categories to adjust the dual_users
  */