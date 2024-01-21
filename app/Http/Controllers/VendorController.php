<?php

namespace App\Http\Controllers;

use App\Jobs\SendPostNotifications;
use App\Models\Post;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Notifications\NotificationSender;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class VendorController extends Controller
{
    private $uploadPath = 'public/uploads/cdn';

    public function createPost(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'title' => ['required', 'string', 'min:3'],
            'body' => ['required', 'string', 'min:10'],
            'category_id' => ['required', 'exists:categories,id'],
            'icon' => 'required'
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        if (!$request->hasFile('icon'))
            return response(['icon' => 'icon file required'], 400);

        $icon = $request->file('icon')->store($this->uploadPath);

        Post::create([
            'icon' => $icon,
            'body' => $request->body,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'user_id' => $request->user->id
        ]);

        return ['status' => 'ok'];
    }

    public function updatePost(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'post_id' => ['required'],
            'title' => ['required', 'string', 'min:3'],
            'body' => ['required', 'string', 'min:10'],
            'category_id' => ['required', 'exists:categories,id'],
            'icon' => ['sometimes', 'nullable']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $post = Post::find($request->post_id);
        if (!$post)
            return response(['err' => 'not found'], 404);

        $post->title = $request->title;
        $post->body = $request->body;
        $post->category_id = $request->category_id;

        if ($request->hasFile('icon')) {
            $icon = $request->file('icon')->store($this->uploadPath);
            File::delete($post->icon);
            $post->icon = $icon;
        }
        $post->save();

        return ['status' => 'ok'];
    }

    public function deletePost($postId)
    {
        $post = Post::find($postId);

        if (!$post)
            return response(['err' => 'not found'], 404);
        try {
            unlink(storage_path("/app/" . $post->icon));
        } catch (Exception $e) {
        }
        $post->delete();

        return ['status' => 'ok'];
    }

    public function getPosts(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'size' => ['sometimes', 'nullable', 'numeric', 'min:1'],
            'page' => ['sometimes', 'nullable', 'numeric', 'min:1']
        ]);

        $size = $request->filled('size') ? $request->query('size') : 1;
        $page = $request->filled('page') ? $request->query('page') : 1;

        if ($validation->fails())
            return response($validation->errors(), 400);

        return Post::where('user_id', $request->user->id)
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get();
    }
}
