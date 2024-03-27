<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class SendGroupPushNotifs {

    public function __construct(ArrayEqual $groupList, $post)
    {
        $groups = $groupList->getList();

        // $serverKey = env('FIREBASE_SERVER_KEY');
        $serverKey = 'AAAAN2BTZ4c:APA91bECrwF9BAbVEWEVLiwfLEmoJO34RxEMlLMegjHuqLemMrzCtt5fVX_6Iq1WGOzpSA0pe7VTHmrhKzms19l-J-bBbHTdbY3T7Yrpk-aXj-l3lv2U8rLsBW5V08wTiOu4FTSjiI7t';

        foreach ($groups as $group) {
            // Notification payload
            $message = [
                'registration_ids' => $group,
                'notification' => [
                    'title' => $post->title,
                    'body' => $post->body,
                    'icon' => $post->icon
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
                Log::error('FCM request failed: ' . curl_error($ch));
            } else {
                // Decode and print the response
                Log::info($result);
            }

            // Close cURL session
            curl_close($ch);
        }
    }
}
