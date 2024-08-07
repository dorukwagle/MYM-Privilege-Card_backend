<?php

use App\Helpers\CredentialHelper;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BenefitController;
use App\Http\Controllers\CardUsesController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VerificationController;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Route;


Route::get('/categories', [CategoriesController::class, 'getProductCategories']);
/**
 * Returns the list of categories:
 * [{id: '1', category: 'Cosmetics'}, .....]
 */

Route::middleware('auth')->group(function () {
        Route::get('/auth/check-logged-in', [AuthController::class, 'checkLoggedIn']);
        // returns if the user is logged in 

        Route::post('/profile/profile-icon', [ProfileController::class, 'profileIconUpdate']);

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

        Route::delete('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/info/vendor/{id}', [ProfileController::class, 'getVendorInfo'])
                ->whereNumber('id');

        Route::get('/info/admin', [ProfileController::class, 'getAdminInfo']);

        Route::post('/profile/update-profile', [ProfileController::class, 'updateProfile']);

        Route::post('/feedbacks', [FeedbackController::class, 'createFeedback']);
        // body: message

        Route::get('/card/benefits', [BenefitController::class, 'getBenefits']);

        Route::get('/card/packages', [PackageController::class, 'getPackages']);

        Route::get('/profile/referrals', [ProfileController::class, 'getMyReferrals']);


        Route::post('/user/device-id', [NotificationController::class, 'setDeviceId']);

        Route::get('/user/notifications', [NotificationController::class, 'getNotifications']);
        // /card/notifications?page=1&size=9

        Route::post('/user/notifs/mark-as-read/{id}', [NotificationController::class, 'markNotificationAsRead'])
                ->whereNumber('id');
        // /cust/notifs-read/5  (marks all the notifications as seen upto this timestamp)

        Route::get('/user/unread-notifs-count', [NotificationController::class, 'getUnreadNotifsCount']);
        // returns the number new/unread notifications
});

Route::middleware(['auth', 'auth.admin'])->group(function () {
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

        Route::get('/user-requests', [AdminController::class, 'getUserRequests']);
        /**
         * /api/users?type=customer (returns new unverified users who paid) first 9 items only
         * /api/users?type=customer&page=2 (returns new unverified users who paid) page 2 with 9 items only
         * /api/users?type=customer&page=2&size=10 (returns new unverified users who paid) page 2 with 10 items only
         * /api/users?type=customer&expired=yes (returns expired users who paid)
         * /api/users?type=customer&expired=yes&paid=no (returns expired users who haven't paid)
         * /api/users?type=customer&paid=no (returns new users who haven't paid)
         * /api/users?type=customer&expired=yes (returns card expired users)
         * /api/users?type=vendor 
         */

        Route::post('/users/verify/vendor/{id}', [AdminController::class, 'verifyVendor'])
                ->whereNumber('id');

        Route::post('/users/reject/vendor/{id}', [AdminController::class, 'rejectVendor'])
                ->whereNumber('id');

        Route::post('/users/reject/customer/{id}', [AdminController::class, 'rejectCustomer'])
                ->whereNumber('id');

        Route::get('/card/generate', [AdminController::class, 'generateCardNumber']);
        // returns random card number of 16 digits

        Route::post('/users/verify/customer/{id}', [AdminController::class, 'assignCard'])
                ->whereNumber('id');
        //assign card to the user
        // body: card_id, valid_duration

        Route::post('/users/card/renew/{id}', [AdminController::class, 'renewCard'])
                ->whereNumber('id');
        // renew expired customer cards
        // body: valid_duration

        Route::post('/users/card/expire/{id}', [AdminController::class, 'expireCard'])
                ->whereNumber('id');
        //expire the card if needed

        Route::post('/payment/manual', [AdminController::class, 'manualPayment']);
        // body: user_id, amount

        Route::get('/users/detail/{id}', [AdminController::class, 'getUserRequestDetails'])
                ->whereNumber('id');
        // returns details about the user: expired or not, last payment details etc,

        Route::get('/payments/user/{id}', [AdminController::class, 'getUserPaymentHistory'])
                ->whereNumber('id');
        // returns the payment history of the given user

        Route::get('/users/search', [AdminController::class, 'searchUsers']);
        // returns the list of users matching the query
        // /users/search/?value=<full name> or <email>        

        Route::get('/feedbacks', [FeedbackController::class, 'getFeedbacks']);
        // supports pagination as other routes

        Route::get('/analytics/user-counts', [AdminController::class, 'getUserAnalytics']);

        Route::post('/register/add-vendor', [RegistrationController::class, 'addVendorAccount']);

        Route::post('/register/add-customer', [RegistrationController::class, 'addCustomerAccount']);

        Route::get('/posts', [AdminController::class, 'getPostRequests']);

        Route::post('/posts/approve/{postid}', [AdminController::class, 'approvePost'])
                ->whereNumber('postid');

        Route::post('/posts/approve-signup-post/{userid}', [AdminController::class, 'approveSignupPost'])
                ->whereNumber('userid');

        Route::post('/posts/reject/{postid}', [AdminController::class, 'rejectPost']);

        Route::post('/card/benefit', [BenefitController::class, 'addBenefit']);
        // body: title, body

        Route::delete('/card/benefit/{id}', [BenefitController::class, 'deleteBenefit'])
                ->whereNumber('id');

        Route::post('/card/package', [PackageController::class, 'addPackage']);
        // body: duration, rate

        Route::delete('/card/package/{id}', [PackageController::class, 'deletePackage'])
                ->whereNumber('id');

        Route::get('/user/referrals/{id}', [ProfileController::class, 'getUserReferrals'])
                ->whereNumber('id');

        Route::post('/admin/announce', [AdminController::class, 'makeAnnouncement']);
        //body: title, body, user_type
});

Route::middleware(['auth', 'auth.superadmin'])->group(function() {
        Route::post('/admin/create-admin', [AdminController::class, 'addAdminAccount']);

        Route::delete('/admin/delete/{id}', [AdminController::class, 'removeAdminAccount'])
                ->whereNumber('id');
        
        Route::get('/admin/get-all', [AdminController::class, 'getAdmins']);
});

Route::middleware(['auth', 'auth.vendor'])->group(function () {
        Route::get('/vendor/posts', [VendorController::class, 'getPosts']);
        // /vendor/posts?size=9&page=1

        Route::post('/vendor/post', [VendorController::class, 'createPost']);
        Route::post('/vendor/post/update', [VendorController::class, 'updatePost']);
        Route::delete('/vendor/post/{id}', [VendorController::class, 'deletePost']);
        Route::post('/profile/banner-icon', [ProfileController::class, 'bannerIconUpdate']);
        Route::get('/vendor/product-qr', [CardUsesController::class, 'getVendorQRData']);
        Route::get('/vendor/card-swipes', [CardUsesController::class, 'getPurchaseRequests']);
        Route::post('/vendor/approve-card-swipe/{id}', [CardUsesController::class, 'verifyPurchase'])
                ->whereNumber('id');
        Route::post('/vendor/reject-card-swipe/{id}', [CardUsesController::class, 'deletePurchase'])
                ->whereNumber('id');
        
        Route::get('/vendor/my-customers', [CardUsesController::class, 'myCustomers']);
        // supports pagination

        Route::get('/vendor/total-customer-swipes', [CardUsesController::class, 'getTotalCustomerSwipes']);
        Route::get('/vendor/search-customer', [CardUsesController::class, 'searchCustomer']);
        //body: value
        // Route::post('/profile/org-bio', [ProfileController::class, 'orgBioUpdate']);
});

Route::middleware(['auth', 'auth.customer'])->group(function () {
        Route::post('/kyc/customer', [RegistrationController::class, 'customerKyc']);

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

        Route::get('/profile/my-card', [ProfileController::class, 'getMyCard']);
        /**
         * Returns customer Card
         * return sample: 
         * {card: <card_number>, expires: <expiry date>, status: 'active'}
         * {status: 'not found' } if there is no card 404
         * {status: 'expired' } if card is expired 410
         */

        Route::get('/profile/payment-history', [ProfileController::class, 'getPaymentHistory']);
        // returns the payment history of the user

        Route::get('/cust/search', [CustomerController::class, 'searchVendorPosts']);
        // /cust/search?category=cosmetic (returns all cosmetic posts created by nearby vendors)

        Route::get('/cust/preferred-posts', [CustomerController::class, 'getPreferredPosts']);
        Route::get('/cust/recommended', [CustomerController::class, 'getRecommendedPosts']);

        // all posts routes support pagination i.e. ?page=2&size=9 for example

        Route::post('/customer/swipe-card/{productid}', [CardUsesController::class, 'claimPurchase']);
});

Route::post('/register/customer', [RegistrationController::class, 'registerCustomer']);

Route::post('/register/vendor', [RegistrationController::class, 'registerVendor']);

Route::get('/public/{path}', function($path){
        try {
                return response()->file(storage_path("/app/public/".$path));
        }catch(Exception $e) {
                return ['err' => 'requested asset not found', 404];
        }
})->where('path', '.+');
/**
 * Body parameters:
 * location
 * org_name (name of business or organization)
 * contact_no
 * address (a bit more detailed, as it may appear in shop info when user views it)
 * email
 * password
 * org_vat_card (registration pan no. of the business)
 * about_org (a short and comprehensive bio of the business)    
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
Route::post('/auth/reset-password', [ResetPasswordController::class, 'resetPassword']);

Route::get('/test/users/{userType}', function($userType) {
        $users = User::selectRaw('id, full_name, user_role');

        $filter = null;

        if ($userType == 'vendor')
        $filter = ['vendor', true];
        if ($userType == 'customer')
        $filter = ['customer', true];

        if ($filter)
                $users->whereRaw('users.user_role = ? or users.is_vend_cust = ?', $filter);

        return $users->get();
});

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * FUTURE UPGRADES
 * make acconts switchable to vendor from customer and vice versa
 * add new categories in users table: preferred_categories and product_categories to adjust the dual_users
 * do not delete entire user while rejecting vendor, as it can be customer upgrading to vendor
 * add one more account_status flag in database to keep track of dual users verification
 */

/**
 * TESTS:
 * create an offer, check if the notification is sent to customer (just to make sure, it isn't messed up)
 * The offer notification is sent to the customer only, not to the vendor.
 * 
 * create an announcement for 'all' then check if both customer&vendor receive the notifcation
 * The announcement is not sent to merchant
 * 
 * create an anouncement for vendor and check if vendor receivs the notification. customer shouldn't receive it
 * The announcement is sent.
 * 
 * create an announcement for customer, vendor shouldn't receive the notifs. only customer should.
 * The announcment is sent.
 * 
 * now open customer dashboard and check offers whether the announcement appears as offer. (if it does, that's a bug)
 * The announcement and offer section is currently same so the announcement also appears in the notification.
 * Different api should be created for announcements and offers in the future.
 */