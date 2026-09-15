<?php

namespace App\Services;

use App\Contracts\Services\ProductServiceInterface;
use App\Models\Inventory;
use App\Models\InventoryHistory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;

class ProductService implements ProductServiceInterface
{
    public function create(array $validated, int $userId): Product
    {
        return DB::transaction(function () use ($validated, $userId) {
            $initialQuantity = (int) $validated['quantity'];

            $product = Product::create($validated);

            Inventory::create([
                'product_id' => $product->id,
            ]);

            if ($initialQuantity > 0) {
                InventoryHistory::create([
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'quantity_before' => 0,
                    'quantity_change' => $initialQuantity,
                    'quantity_after' => $initialQuantity,
                    'type' => 'stock_in',
                    'reason' => 'Initial stock',
                ]);
            }

            return $product;
        });
    }

    public function update(
        Product $product,
        array $validated,
        ?string $image = null,
        bool $removeImage = false
    ): Product {
        $oldImage = $product->image;

        $product->name = $validated['name'];
        $product->sku = $validated['sku'];
        $product->category_id = $validated['category_id'] ?? null;
        $product->supplier_id = $validated['supplier_id'] ?? null;
        $product->description = $validated['description'] ?? null;
        $product->price = $validated['price'];

        if ($removeImage && !empty($product->image)) {
            $product->image = null;
        }

        if ($image !== null) {
            $product->image = $image;
        }

        $product->save();

        if (($removeImage || $image !== null) && !empty($oldImage)) {
            Storage::disk('public')->delete($oldImage);
        }

        return $product;
    }

    public function bulkUpdate(
        array $products,
        array $images = [],
        array $removeImages = []
    ): void {
        DB::transaction(function () use (
            $products,
            $images,
            $removeImages
        ) {
            foreach ($products as $index => $data) {
                $product = Product::findOrFail($data['id']);

                $oldImage = $product->image;

                $product->name = $data['name'];
                $product->sku = $data['sku'];
                $product->category_id = $data['category_id'];
                $product->supplier_id = $data['supplier_id'];
                $product->description = $data['description'] ?? null;
                $product->price = $data['price'];

                if (!empty($removeImages[$index])) {
                    $product->image = null;
                }

                if (isset($images[$index])) {
                    $product->image = $images[$index]->store(
                        'products',
                        'public'
                    );
                }

                $product->save();

                if (
                    (!empty($removeImages[$index]) || isset($images[$index]))
                    && !empty($oldImage)
                ) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
        });
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function bulkDelete(array $ids): int
    {
        return Product::whereIn('id', $ids)->delete();
    }

    public function restore(int $id): Product
    {
        $product = Product::withTrashed()->findOrFail($id);

        $product->restore();

        return $product;
    }

    public function bulkRestore(array $ids): int
    {
        $restoredCount = 0;

        DB::transaction(function () use ($ids, &$restoredCount) {
            $products = Product::onlyTrashed()
                ->whereIn('id', $ids)
                ->get();

            foreach ($products as $product) {
                $product->restore();
                $restoredCount++;
            }
        });

        return $restoredCount;
    }

    public function forceDelete(int $id): void
    {
        $product = Product::onlyTrashed()->findOrFail($id);

        if ($product->invoiceItems()->exists()) {
            throw ValidationException::withMessages([
                'product' => [
                    'This product cannot be permanently deleted because it is used in one or more invoices.',
                ],
            ]);
        }

        $image = $product->image;

        $product->forceDelete();

        if (
            $image &&
            !filter_var($image, FILTER_VALIDATE_URL)
        ) {
            Storage::disk('public')->delete($image);
        }
    }

    public function bulkForceDelete(array $ids): array
    {
        $deletedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use (
            $ids,
            &$deletedCount,
            &$skippedCount
        ) {
            $products = Product::onlyTrashed()
                ->whereIn('id', $ids)
                ->get();

            foreach ($products as $product) {
                if ($product->invoiceItems()->exists()) {
                    $skippedCount++;
                    continue;
                }

                $image = $product->image;

                $product->forceDelete();

                if (
                    $image &&
                    !filter_var($image, FILTER_VALIDATE_URL)
                ) {
                    Storage::disk('public')->delete($image);
                }

                $deletedCount++;
            }
        });

        return [
            'deleted_count' => $deletedCount,
            'skipped_count' => $skippedCount,
        ];
    }

    public function import($file): void
    {
        Excel::import(
            new ProductsImport(),
            $file
        );
    }
}
