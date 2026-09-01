<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $products = DB::table('products')
            ->select('id', 'category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->get();

        foreach ($products as $product) {
            $categoryName = trim($product->category);

            $category = DB::table('categories')
                ->where('name', $categoryName)
                ->first();

            if (!$category) {
                $categoryId = DB::table('categories')->insertGetId([
                    'name' => $categoryName,
                    'slug' => Str::slug($categoryName),
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $categoryId = $category->id;
            }

            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'category_id' => $categoryId,
                ]);
        }
    }

    public function down(): void
    {
        DB::table('products')
            ->update([
                'category_id' => null,
            ]);
    }
};
