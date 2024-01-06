<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    public function createFeedback(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'message' => ['required', 'string', 'min:10'],
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        Feedback::create([
            'message' => $request->message,
            'sendor_id' => $request->user->id
        ]);

        return ['status' => 'ok'];
    }

    public function getFeedbacks(Request $request)
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
            ->join('feedbacks', 'feedbacks.sendor_id', '=', 'users.id')
            ->selectRaw('
                feedbacks.id as feedback_id, 
                feedbacks.message as message, 
                users.user_role as user_type, 
                users.contact_no as contact, 
                users.email as email, 
                users.full_name as full_name, 
                users.org_name as org_name
            ')
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get();
    }
}
