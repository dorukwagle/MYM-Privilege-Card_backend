<?php

namespace App\Helpers;

use App\Models\PaymentHistory;
use Illuminate\Support\Facades\DB;

class QueryHelper
{
    static function getPreferredCategories($userId)
    {
        return DB::table('users_categories')
            ->join('categories', 'users_categories.category_id', '=', 'categories.id')
            ->select('categories.id')
            ->where('users_categories.user_id', '=', $userId)
            ->pluck('id')
            ->toArray();
    }
}
