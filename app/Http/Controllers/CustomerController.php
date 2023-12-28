<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
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

        return Notification::where('user_id', $request->user->id)
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
            ->havingRaw('st_distance_sphere(users.coordinates, point(?, ?)) < ?', [$latLang[1], $latLang[0], 401]) // less than 401 meter
            ->orderBy('posts.created_at', 'desc')
            ->get();
    }

    public function getPreferredPosts(Request $request)
    {
        $userLocation = $request->user->coordinates;
        return $this->getPosts($request->user->is, $userLocation, 0, 4001, true);
    }

    // returns the posts from nearby vendors other than preferred categories
    public function getNearbyPosts(Request $request)
    {
        $userLocation = $request->user->coordinates;
        return $this->getPosts($request->user->is, $userLocation, 0, 4001, false);
    }

    // returns the posts from preferred categories but beyond the nearby distance
    public function getPreferredPostsBeyondNear(Request $request)
    {
        $userLocation = $request->user->coordinates;
        return $this->getPosts($request->user->is, $userLocation,4001,  9001, true);
    }

    private function getPosts($userId, $userLocation, $minDistance, $maxDistance, $preferred)
    {
        $query = DB::table('users')
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->join('categories', 'categories.id', '=', 'posts.category_id')
            ->selectRaw('users.id as vendor_id, users.org_name as org_name, users.coordinates as location, posts.*, st_distance_sphere(users.coordinates, point(?, ?)) as distance', [$userLocation->longitude, $userLocation->latitude])
            ->whereRaw('users.user_role = ? or users.is_vend_cust = ?', ['vendor', true]);

        if ($preferred)
            $query->whereIn('posts.category_id', $this->getPreferredCategories($userId));
        else $query->whereNotIn('posts.category_id', $this->getPreferredCategories($userId));

        return $query->havingBetween('distance', [$minDistance, $maxDistance])
            ->orderBy('posts.created_at', 'desc')
            ->get();
    }

    private function getPreferredCategories($userId)
    {
        return DB::table('users_categories')
            ->join('categories', 'users_categories.category_id', '=', 'categories.id')
            ->select('categories.id')
            ->where('users_categories.user_id', '=', $userId)
            ->pluck('id')
            ->toArray();
    }
}
