<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'size' => ['sometimes', 'nullable', 'numeric', 'min:1'],
            'page' => ['sometimes', 'nullable', 'numeric', 'min:1']
        ]);

        $size = $request->filled('size') ? $request->query('size') : 9;
        $page = $request->filled('page') ? $request->query('page') : 1;

        if ($validation->fails())
            return response($validation->errors(), 400);

        return DB::table('users')
            ->join('notifications', 'notifications.user_id', '=', 'users.id')
            ->join('posts', 'posts.id', '=', 'notifications.post_id')
            ->where('notifications.user_id', $request->user->id)
            ->selectRaw('notifications.id as id, notifications.read as seen, posts.title, posts.icon, posts.body, posts.category_id')
            ->orderBy('notifications.created_at', 'desc')
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

    public function setDeviceId(Request $request)
    {
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
