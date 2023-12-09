<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert(
            [
                'full_name' => 'Doruk Wagle',
                'contact_no' => '+9779829293466',
                'gender' => 'male',
                'email' => 'doruk',
                'user_role' => 'admin',
                'email_verified' => true,
                'password' => Hash::make('adminpass')
            ]
        );
    }
}
