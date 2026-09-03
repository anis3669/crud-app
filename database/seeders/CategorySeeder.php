<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Electronic devices, accessories and gadgets.',
            ],
            [
                'name' => 'Clothing',
                'description' => 'Clothing, apparel and fashion products.',
            ],
            [
                'name' => 'Sports',
                'description' => 'Sports equipment and accessories.',
            ],
            [
                'name' => 'Home & Kitchen',
                'description' => 'Home, kitchen and household products.',
            ],
            [
                'name' => 'Food & Beverage',
                'description' => 'Food, beverages and related products.',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
