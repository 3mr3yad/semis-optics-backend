<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Main categories only
        Category::firstOrCreate(
            ['slug' => 'ttl'],
            [
                'name' => 'TTL',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        Category::firstOrCreate(
            ['slug' => 'light'],
            [
                'name' => 'Light',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        Category::firstOrCreate(
            ['slug' => 'adjustable-pd'],
            [
                'name' => 'Adjustable PD',
                'is_active' => true,
                'sort_order' => 3,
            ]
        );
    }
}
