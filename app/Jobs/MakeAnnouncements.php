<?php

namespace App\Jobs;

use App\Helpers\ArrayEqual;
use App\Helpers\SendGroupPushNotifs;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MakeAnnouncements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userType;
    protected $post;
    /**
     * Create a new job instance.
     */
    public function __construct($post, $userType)
    {
        $this->userType = $userType;
        $this->post = $post;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $users = null;
        $filter = ['customer', true];

        if ($this->userType == 'all')
            $filter = [];
        else if ($this->userType == 'vendor')
            $filter = ['vendor', true];
        else $filter = $filter;

        $users = $filter ? User::whereRaw('users.user_role = ? or users.is_vend_cust = ?', $filter) : User::all()
        ->selectRaw('id, device_token')
        ->get();

        // initialize array to group all device_id in array
        $groups = new ArrayEqual(480);

        // find those users whose preferred categories include the post category
        foreach ($users as $user) {
            // $preferredCategories = QueryHelper::getPreferredCategories($user->id);

            // Check if the post category is in the user's preferred categories
            // if (in_array($postCategory, $preferredCategories)) {
            Notification::create([
                'post_id' => $this->post->id,
                'user_id' => $user->id,
                'read' => false
            ]);

            // store the user device_id
            if ($user->device_token)
                $groups->push($user->device_token);
        }

        // send push notifications
        new SendGroupPushNotifs($groups, $this->post);
    }
}
