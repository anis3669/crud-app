<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
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
        $category = Category::where(
            'name',
            trim($row['category'])
        )->first();

        $supplier = Supplier::where(
            'name',
            trim($row['supplier'])
        )->first();

        return new Product([
            'name'        => trim($row['product_name']),
            'sku'         => trim($row['sku']),
            'category_id' => $category?->id,
            'supplier_id' => $supplier?->id,
            'price'       => $row['price'],
            'quantity'    => $row['quantity'],
            'description' => isset($row['description'])
                ? trim($row['description'])
                : null,
        ]);
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
