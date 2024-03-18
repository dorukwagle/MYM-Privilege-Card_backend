<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use TarfinLabs\LaravelSpatial\Types\Point;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert(
            [
                'full_name' => 'Doruk Wagle',
                'contact_no' => '+9779829293466',
                'gender' => 'male',
                'email' => 'doruk',
                'user_role' => 'superadmin',
                'email_verified' => true,
                'account_status' => 'verified',
                'password' => Hash::make('adminpass'),
                'referral_code' => 'dorukwgl',
                'has_logged_in' => true
            ]
        );
    }
}
