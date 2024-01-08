<?php

namespace App\Http\Controllers;

use App\Helpers\QueryHelper;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use MatanYadaev\EloquentSpatial\Objects\Point;

class CustomerController extends Controller
{
    private $nearbySearchDistance = 401; //meter
    private $nearbyHomeDistance = 4001; //meter

    public function getNotifications(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'size' => ['sometimes', 'nullable', 'numeric', 'min:1'],
            'page' => ['sometimes', 'nullable', 'numeric', 'min:1']
        ]);

        $size = $request->filled('size') ? $request->query('size') : 1;
        $page = $request->filled('page') ? $request->query('page') : 1;

        if ($validation->fails())
            return response($validation->errors(), 400);

        return DB::table('users')
            ->join('notifications', 'notifications.user_id', '=', 'users.id')
            ->where('notifications.user_id', $request->user->id)
            ->selectRaw('notifications.*')
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get();
    }

    public function markNotificationAsRead(Request $request, $notifId)
    {
        $userId = $request->user->id;
        $notif = Notification::find($notifId);

        if (!$notif) return ['status' => 'ok'];

        Notification::where('user_id', $userId)
            ->where('created_at', '<=', $notif->created_at)
            ->update(['read' => true]);

        return ['status' => 'ok'];
    }

    public function getUnreadNotifsCount(Request $request)
    {
        $userId = $request->user->id;

        return Notification::where('user_id', $userId)
            ->where('read', false)
            ->count();
    }

    public function searchVendorPosts(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'search' => ['required', 'string'],
            'user_location' => ['required', 'regex:/^-?([1-8]?\d(?:\.\d+)?|90(?:\.0+)?), -?(180(?:\.0+)?|1[0-7]\d(?:\.\d+)?|\d{1,2}(?:\.\d+)?)$/']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $capitalizedSearch = ucwords($request->category);
        $latLang = explode(", ", $request->user_location);

        return DB::table('users')
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->join('categories', 'categories.id', '=', 'posts.category_id')
            ->selectRaw('users.id as vendor_id, users.org_name as org_name, users.coordinates as location, posts.*')
            ->whereRaw('users.user_role = ? or users.is_vend_cust = ?', ['vendor', true])
            ->whereRaw('match(categories.category) against(? in boolean mode)', [$capitalizedSearch])
            ->havingRaw('st_distance_sphere(users.coordinates, point(?, ?)) < ?', [$latLang[1], $latLang[0], $this->nearbySearchDistance]) // less than 401 meter
            ->orderBy('posts.created_at', 'desc')
            ->get();
    }

    public function getPreferredPosts(Request $request)
    {
        return $this->getPosts( $request, 0, $this->nearbyHomeDistance, true);
    }

    // returns the posts from nearby vendors other than preferred categories
    public function getNearbyPosts(Request $request)
    {
        return $this->getPosts($request, 0, $this->nearbyHomeDistance, false);
    }

    // returns the posts from preferred categories but beyond the nearby distance
    public function getPreferredPostsBeyondNear(Request $request)
    {
        return $this->getPosts($request, $this->nearbyHomeDistance,  9001, true);
    }

    private function getPosts(Request $request, $minDistance, $maxDistance, $preferred)
    {
        $validation = Validator::make($request->all(), [
            'size' => ['sometimes', 'nullable', 'numeric', 'min:1'],
            'page' => ['sometimes', 'nullable', 'numeric', 'min:1']
        ]);

        $size = $request->filled('size') ? $request->query('size') : 1;
        $page = $request->filled('page') ? $request->query('page') : 1;

        if ($validation->fails())
            return response($validation->errors(), 400);

        $userId = $request->user->id;
        $userLocation = $request->user->coordinates;

        $query = User::
            join('posts', 'users.id', '=', 'posts.user_id')
            ->join('categories', 'categories.id', '=', 'posts.category_id')
            ->selectRaw(
                'users.id as vendor_id, users.org_name as org_name, users.coordinates as location, posts.*, 
                st_distance_sphere(users.coordinates, point(?, ?)) as distance',
                [$userLocation->longitude, $userLocation->latitude]
            )
            ->whereRaw('users.user_role = ? or users.is_vend_cust = ?', ['vendor', true]);

        if ($preferred)
            $query->whereIn('posts.category_id', QueryHelper::getPreferredCategories($userId));
        else $query->whereNotIn('posts.category_id', QueryHelper::getPreferredCategories($userId));

        return $query->havingBetween('distance', [$minDistance, $maxDistance])
            ->orderBy('posts.created_at', 'desc')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get();

        // return $data->map(function ($result) {
        //     $result->location = Point::fromWKT($result->location);
        //     return $result;
        // });
    }

    public function setDeviceId(Request $request) {
        $validation = Validator::make($request->all(), [
            'device_id' => ['required', 'min:90']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $user = User::find($request->user->id);
        $user->device_token = $request->device_id;
        $user->save();

        return ['status' => 'ok'];
    }
}
