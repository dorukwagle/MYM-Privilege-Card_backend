<?php

namespace App\Jobs;

use App\Helpers\QueryHelper;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendPostNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;
    protected $vendor;

    /**
     * Create a new job instance.
     */
    public function __construct($vendor, $post)
    {
        $this->vendor = $vendor;
        $this->post = $post;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $vendorLocation = $this->vendor->coordinates;
        $postCategory = $this->post->category_id;

        $users = User::whereRaw('users.user_role = ? or users.is_vend_cust = ?', ['customer', true])
            ->selectRaw(
                'id, st_distance_sphere(coordinates, point(?, ?)) as distance',
                [$vendorLocation->longitude, $vendorLocation->latitude]
            )
            ->having('distance', '<', 4001)
            ->pluck('id')
            ->toArray();

        // find those users whose preferred categories include the post category
        foreach ($users as $userId) {
            $preferredCategories = QueryHelper::getPreferredCategories($userId);

            // Check if the post category is in the user's preferred categories
            if (in_array($postCategory, $preferredCategories)) {
                // send notification to them
                $filteredUsers[] = $userId;
                Notification::create([
                    'post_id' => $this->post->id,
                    'user_id' => $userId,
                    'read' => false
                ]);
            }
        }

    }
}
