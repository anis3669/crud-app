<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryHistory;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        return DB::transaction(function () use ($row) {
            $category = Category::where(
                'name',
                trim($row['category'])
            )->first();

            $supplier = Supplier::where(
                'name',
                trim($row['supplier'])
            )->first();

            $quantity = (int) $row['quantity'];

            $product = Product::create([
                'name'        => trim($row['product_name']),
                'sku'         => trim($row['sku']),
                'category_id' => $category?->id,
                'supplier_id' => $supplier?->id,
                'price'       => $row['price'],
                'quantity'    => $quantity,
                'description' => isset($row['description'])
                    ? trim($row['description'])
                    : null,
            ]);

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
        });
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
                'exists:categories,name',
            ],

            'supplier' => [
                'required',
                'string',
                'exists:suppliers,name',
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
