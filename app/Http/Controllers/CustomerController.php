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

    public function searchVendorPosts(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'search' => ['required', 'string'],
            'user_location' => ['required', 'regex:/^-?([1-8]?\d(?:\.\d+)?|90(?:\.0+)?), -?(180(?:\.0+)?|1[0-7]\d(?:\.\d+)?|\d{1,2}(?:\.\d+)?)$/']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $capitalizedSearch = ucwords($request->search);
        $latLang = explode(", ", $request->user_location);

        return DB::table('users')
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->join('categories', 'categories.id', '=', 'posts.category_id')
            ->selectRaw('users.id as vendor_id, users.org_name as org_name, st_astext(users.coordinates) as location, posts.*')
            ->whereRaw('users.user_role = ? or users.is_vend_cust = ?', ['vendor', true])
            ->whereRaw('match(categories.category) against(? in boolean mode)', [$capitalizedSearch])
            ->havingRaw('st_distance_sphere(users.coordinates, point(?, ?)) < ?', [$latLang[1], $latLang[0], $this->nearbySearchDistance]) // less than 401 meter
            ->orderBy('posts.created_at', 'desc')
            ->get();
    }

    public function getPreferredPosts(Request $request)
    {
        return $this->getPosts( $request, true);
    }

    // returns the posts from nearby vendors other than preferred categories
    public function getRecommendedPosts(Request $request)
    {
        return $this->getPosts($request, false);
    }

    private function getPosts(Request $request, $preferred)
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
        // $userLocation = $request->user->coordinates;
        // if (!$userLocation) return response(['err' => 'location not provided'], 404);

        $query = DB::table('users')
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->join('categories', 'categories.id', '=', 'posts.category_id')
            ->selectRaw(
                'users.id as vendor_id, users.org_name as org_name, st_astext(users.coordinates) as location, posts.*'
            )
            ->whereRaw('users.user_role = ? or users.is_vend_cust = ?', ['vendor', true]);

        if ($preferred)
            $query->whereIn('posts.category_id', QueryHelper::getPreferredCategories($userId));
        else $query->whereNotIn('posts.category_id', QueryHelper::getPreferredCategories($userId));

        return $query->orderBy('posts.created_at', 'desc')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get();
    }
}
