<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $products = [
            [
                'name' => 'Wireless Bluetooth Headphones',
                'description' => 'Premium wireless headphones with clear sound and long battery life.',
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e',
            ],
            [
                'name' => 'Mechanical Gaming Keyboard',
                'description' => 'RGB mechanical keyboard designed for gaming and everyday use.',
                'image' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3',
            ],
            [
                'name' => 'Wireless Gaming Mouse',
                'description' => 'Ergonomic wireless gaming mouse with accurate tracking and adjustable sensitivity.',
                'image' => 'https://images.unsplash.com/photo-1527814050087-3793815479db',
            ],
            [
                'name' => 'Smart Watch Series 5',
                'description' => 'Modern smartwatch with fitness tracking, notifications, and health features.',
                'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30',
            ],
            [
                'name' => 'Portable Bluetooth Speaker',
                'description' => 'Compact Bluetooth speaker with powerful audio and portable design.',
                'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1',
            ],
            [
                'name' => 'Laptop Backpack',
                'description' => 'Durable laptop backpack with multiple compartments for work and travel.',
                'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62',
            ],
            [
                'name' => 'Stainless Steel Water Bottle',
                'description' => 'Insulated stainless steel bottle that keeps drinks cold or hot for hours.',
                'image' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8',
            ],
            [
                'name' => 'USB-C Fast Charger',
                'description' => 'Compact fast charger compatible with modern smartphones, tablets, and laptops.',
                'image' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0',
            ],
            [
                'name' => 'Wireless Charging Pad',
                'description' => 'Slim wireless charging pad designed for convenient everyday charging.',
                'image' => 'https://images.unsplash.com/photo-1586953208448-b95a79798f07',
            ],
            [
                'name' => 'Smart LED Desk Lamp',
                'description' => 'Adjustable LED desk lamp with multiple brightness levels for work and study.',
                'image' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c',
            ],
            [
                'name' => 'Portable SSD 1TB',
                'description' => 'Fast and compact external SSD for storing and transferring large files.',
                'image' => 'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b',
            ],
            [
                'name' => 'USB-C Hub Adapter',
                'description' => 'Multi-port USB-C hub with HDMI, USB, and memory card connectivity.',
                'image' => 'https://images.unsplash.com/photo-1625842268584-8f3296236761',
            ],
            [
                'name' => '1080p Web Camera',
                'description' => 'Full HD webcam suitable for video meetings, streaming, and online classes.',
                'image' => 'https://images.unsplash.com/photo-1587825140708-dfaf72ae4b04',
            ],
            [
                'name' => 'Wireless Office Keyboard',
                'description' => 'Comfortable wireless keyboard designed for productivity and office work.',
                'image' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3',
            ],
            [
                'name' => 'Ergonomic Office Chair',
                'description' => 'Adjustable ergonomic chair designed for comfortable long working sessions.',
                'image' => 'https://images.unsplash.com/photo-1580480055273-228ff5388ef8',
            ],
            [
                'name' => 'Adjustable Laptop Stand',
                'description' => 'Aluminum laptop stand with adjustable height and viewing angles.',
                'image' => 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9',
            ],
            [
                'name' => 'Noise Cancelling Earbuds',
                'description' => 'Compact wireless earbuds with active noise cancellation and clear audio.',
                'image' => 'https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1',
            ],
            [
                'name' => 'Smartphone Tripod Stand',
                'description' => 'Adjustable tripod stand for smartphones, photography, and video recording.',
                'image' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32',
            ],
            [
                'name' => 'Digital Camera',
                'description' => 'Compact digital camera designed for everyday photography and travel.',
                'image' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32',
            ],
            [
                'name' => 'Gaming Monitor 27 Inch',
                'description' => '27-inch gaming monitor with high refresh rate and sharp image quality.',
                'image' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf',
            ],
            [
                'name' => 'Full HD Monitor 24 Inch',
                'description' => '24-inch Full HD monitor suitable for office work, study, and entertainment.',
                'image' => 'https://images.unsplash.com/photo-1527443192006-8d7b6f5f6c1a',
            ],
            [
                'name' => 'Gaming Mouse Pad',
                'description' => 'Large gaming mouse pad with a smooth surface and anti-slip base.',
                'image' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7',
            ],
            [
                'name' => 'Smart Home Camera',
                'description' => 'Indoor smart camera with high-definition video and motion detection.',
                'image' => 'https://images.unsplash.com/photo-1558008258-3256797b43f3',
            ],
            [
                'name' => 'WiFi 6 Router',
                'description' => 'High-speed WiFi router designed for reliable home and office connectivity.',
                'image' => 'https://images.unsplash.com/photo-1606904825846-647eb07f5be2',
            ],
            [
                'name' => 'Power Bank 20000mAh',
                'description' => 'High-capacity portable power bank for charging devices while travelling.',
                'image' => 'https://images.unsplash.com/photo-1609592424526-8a4a1f8c2a1f',
            ],
            [
                'name' => 'Smartphone Screen Protector',
                'description' => 'Tempered glass screen protector designed to protect smartphones from scratches.',
                'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9',
            ],
            [
                'name' => 'Premium Phone Case',
                'description' => 'Durable protective phone case with a slim design and reinforced edges.',
                'image' => 'https://images.unsplash.com/photo-1601593346740-925612772716',
            ],
            [
                'name' => 'Desk Organizer',
                'description' => 'Compact desk organizer for keeping stationery and accessories neatly arranged.',
                'image' => 'https://images.unsplash.com/photo-1494438639946-1ebd1d20bf85',
            ],
            [
                'name' => 'Portable Projector',
                'description' => 'Compact portable projector suitable for presentations, movies, and entertainment.',
                'image' => 'https://images.unsplash.com/photo-1535016120720-40c646be5580',
            ],
            [
                'name' => 'Electric Coffee Maker',
                'description' => 'Compact coffee maker designed for quick and convenient home brewing.',
                'image' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085',
            ],
            [
                'name' => 'Stainless Steel Travel Mug',
                'description' => 'Insulated travel mug designed to keep beverages hot or cold for longer.',
                'image' => 'https://images.unsplash.com/photo-1514228742587-6b1558fcf93a',
            ],
            [
                'name' => 'LED Gaming Desk Light',
                'description' => 'Modern LED desk light with adjustable brightness for gaming and workspaces.',
                'image' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f',
            ],
            [
                'name' => 'External DVD Drive',
                'description' => 'Slim USB external DVD drive for reading and writing discs.',
                'image' => 'https://images.unsplash.com/photo-1591488320449-011701bb6704',
            ],
            [
                'name' => 'Wireless Presentation Remote',
                'description' => 'Compact presentation remote with navigation controls and laser pointer.',
                'image' => 'https://images.unsplash.com/photo-1587825140708-dfaf72ae4b04',
            ],
            [
                'name' => 'Portable Air Purifier',
                'description' => 'Compact air purifier designed to improve air quality in small rooms and workspaces.',
                'image' => 'https://images.unsplash.com/photo-1585771724684-38269d6639fd',
            ],
        ];

        $product = fake()->unique()->randomElement($products);

        return [
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => fake()->randomFloat(2, 500, 50000),
            'quantity' => fake()->numberBetween(1, 100),
            'image' => $product['image'],
        ];
    }
}
