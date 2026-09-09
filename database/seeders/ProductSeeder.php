<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryHistory;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Product::factory(35)->create()->each(function (Product $product) {
                // Create inventory record
                Inventory::create([
                    'product_id' => $product->id,
                ]);

                // Create initial stock history
                if ($product->quantity > 0) {
                    InventoryHistory::create([
                        'product_id' => $product->id,
                        'user_id' => null,
                        'quantity_before' => 0,
                        'quantity_change' => $product->quantity,
                        'quantity_after' => $product->quantity,
                        'type' => 'stock_in',
                        'reason' => 'Initial stock',
                    ]);
                }
            });
        });
    }
}
