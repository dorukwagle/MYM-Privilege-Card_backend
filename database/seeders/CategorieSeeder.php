<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert(
            [
                ['id' => 1, 'category' => 'Cosmetics'],
                ['id' => 2, 'category' => 'Groceries'],
                ['id' => 3, 'category' => 'Cold Drinks'],
                ['id' => 4, 'category' => 'Hard Drinks'],
                ['id' => 5, 'category' => 'Undergarments']
            ]
            );
    }
}
