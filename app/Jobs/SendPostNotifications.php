<?php

namespace App\Jobs;

use App\Helpers\ArrayEqual;
use App\Helpers\QueryHelper;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
                'id, device_id, st_distance_sphere(coordinates, point(?, ?)) as distance',
                [$vendorLocation->longitude, $vendorLocation->latitude]
            )
            ->having('distance', '<', 4001)  
            ->get();

        // initialize array to group all device_id in array
        $groups = new ArrayEqual(480);

        // find those users whose preferred categories include the post category
        foreach ($users as $user) {
            $preferredCategories = QueryHelper::getPreferredCategories($user->id);

            // Check if the post category is in the user's preferred categories
            if (in_array($postCategory, $preferredCategories)) {
                // send notification to them
                $filteredUsers[] = $user->id;
                Notification::create([
                    'post_id' => $this->post->id,
                    'user_id' => $user->id,
                    'read' => false
                ]);

                // store the user device_id
                $groups->push($user->device_id);
            }
        }

        // send push notifications
        $this->sendPushNotification($groups);
    }

    private function sendPushNotification($groups) {
        $serverKey = env('FIREBASE_SERVER_KEY');

        foreach ($groups as $group) {
            // Notification payload
            $message = [
                'registration_ids' => $group,
                'notification' => [
                    'title' => 'Your Notification ',
                    'body' => 'Your Notification Body',
                ],
                // Additional data fields can be added here
            ];

            // Headers
            $headers = [
                'Authorization: key=' . $serverKey,
                'Content-Type: application/json',
            ];

            // Initialize cURL session
            $ch = curl_init();

            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // Only for testing, remove in production
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));

            // Execute cURL session
            $result = curl_exec($ch);

            // Check for errors
            if (curl_errno($ch)) {
                echo 'FCM request failed: ' . curl_error($ch);
            } else {
                // Decode and print the response
                $response = json_decode($result, true);
                print_r($response);
            }

            // Close cURL session
            curl_close($ch);
        }
    }
}
