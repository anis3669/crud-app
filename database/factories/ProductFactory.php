<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $products = [
            [
                'name' => 'Wireless Bluetooth Headphones',
                'description' => 'Premium wireless headphones with clear sound and long battery life.',
                'category' => 'Electronics',
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e',
            ],
            [
                'name' => 'Mechanical Gaming Keyboard',
                'description' => 'RGB mechanical keyboard designed for gaming and everyday use.',
                'category' => 'Electronics',
                'image' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3',
            ],
            [
                'name' => 'Wireless Gaming Mouse',
                'description' => 'Ergonomic wireless gaming mouse with accurate tracking and adjustable sensitivity.',
                'category' => 'Electronics',
                'image' => 'https://images.unsplash.com/photo-1527814050087-3793815479db',
            ],
            [
                'name' => 'Smart Watch Series 5',
                'description' => 'Modern smartwatch with fitness tracking and notifications.',
                'category' => 'Electronics',
                'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30',
            ],
            [
                'name' => 'Laptop Backpack',
                'description' => 'Durable laptop backpack with multiple compartments for work and travel.',
                'category' => 'Clothing',
                'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62',
            ],
            [
                'name' => 'Stainless Steel Water Bottle',
                'description' => 'Insulated stainless steel bottle that keeps drinks cold or hot for hours.',
                'category' => 'Sports',
                'image' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8',
            ],
            [
                'name' => 'Smart LED Desk Lamp',
                'description' => 'Adjustable LED desk lamp with multiple brightness levels for work and study.',
                'category' => 'Home & Kitchen',
                'image' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c',
            ],
            [
                'name' => 'Electric Coffee Maker',
                'description' => 'Compact coffee maker designed for quick and convenient home brewing.',
                'category' => 'Food & Beverage',
                'image' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085',
            ],
            [
                'name' => 'Portable SSD 1TB',
                'description' => 'Fast and compact external SSD for storing and transferring large files.',
                'category' => 'Electronics',
                'image' => 'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b',
            ],
            [
                'name' => 'Portable Projector',
                'description' => 'Compact portable projector suitable for presentations and entertainment.',
                'category' => 'Electronics',
                'image' => 'https://images.unsplash.com/photo-1535016120720-40c646be5580',
            ],
        ];

        $product = fake()->randomElement($products);
        $category = Category::where('name', $product['category'])->first();

        $supplier = Supplier::inRandomOrder()->first();

        return [
            'name' => $product['name'],
            'sku' => 'SKU-' . strtoupper(Str::random(8)),
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'description' => $product['description'],
            'price' => fake()->randomFloat(2, 500, 50000),
            'quantity' => fake()->numberBetween(1, 100),
            'image' => $product['image'],
        ];
    }
}
