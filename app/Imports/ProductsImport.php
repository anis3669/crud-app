<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryHistory;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements
    ToModel,
    WithHeadingRow,
    WithValidation
{
    public function model(array $row)
    {
        $productName = trim((string) ($row['product_name'] ?? ''));
        $sku = trim((string) ($row['sku'] ?? ''));
        $categoryName = trim((string) ($row['category'] ?? ''));
        $supplierName = trim((string) ($row['supplier'] ?? ''));

        $category = Category::where(
            'name',
            $categoryName
        )->first();

        $supplier = Supplier::where(
            'name',
            $supplierName
        )->first();

        $quantity = (int) $row['quantity'];

        $product = new Product([
            'name'        => $productName,
            'sku'         => $sku,
            'category_id' => $category?->id,
            'supplier_id' => $supplier?->id,
            'price'       => $row['price'],
            'quantity'    => $quantity,
            'description' => isset($row['description'])
                ? trim((string) $row['description'])
                : null,
        ]);

        $product->save();

        Inventory::create([
            'product_id' => $product->id,
        ]);

        if ($quantity > 0) {
            InventoryHistory::create([
                'product_id'      => $product->id,
                'user_id'         => auth()->id(),
                'quantity_before' => 0,
                'quantity_change' => $quantity,
                'quantity_after'  => $quantity,
                'type'            => 'stock_in',
                'reason'          => 'Initial stock via Excel import',
            ]);
        }

        return $product;
    }

    public function rules(): array
    {
        return [
            'product_name' => [
                'required',
                'string',
                'max:255',
            ],

            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku'),
            ],

            'category' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $categoryName = trim((string) $value);

                    if (
                        !Category::where(
                            'name',
                            $categoryName
                        )->exists()
                    ) {
                        $fail(
                            "The category '{$categoryName}' does not exist."
                        );
                    }
                },
            ],

            'supplier' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $supplierName = trim((string) $value);

                    if (
                        !Supplier::where(
                            'name',
                            $supplierName
                        )->exists()
                    ) {
                        $fail(
                            "The supplier '{$supplierName}' does not exist."
                        );
                    }
                },
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}
