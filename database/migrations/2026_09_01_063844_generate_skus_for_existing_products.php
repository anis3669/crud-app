<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $products = DB::table('products')
            ->select('id')
            ->whereNull('sku')
            ->orderBy('id')
            ->get();

        foreach ($products as $product) {
            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'sku' => 'PRD-' . str_pad($product->id, 6, '0', STR_PAD_LEFT),
                ]);
        }
    }

    public function down(): void
    {
        DB::table('products')->update([
            'sku' => null,
        ]);
    }
};
