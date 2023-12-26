<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function getNotifications(Request $request) {
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

    public function markNotificationAsRead(Request $request, $notifId) {
        $userId = $request->user->id;
        $notif = Notification::find($notifId);

        if (!$notif) return ['status' => 'ok'];

        Notification::where('user_id', $userId)
            ->where('created_at', '<=', $notif->created_at)
            ->update(['read' => true]);

        return ['status' => 'ok'];
    }
}
