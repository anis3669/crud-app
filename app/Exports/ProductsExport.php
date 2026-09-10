<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Product::with(['category', 'supplier'])
            ->get()
            ->map(function ($product) {
                return [
                    $product->id,
                    $product->name,
                    $product->sku,
                    $product->category?->name,
                    $product->supplier?->name,
                    $product->price,
                    $product->quantity,
                    $product->description,
                    $product->created_at?->format('Y-m-d H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Product Name',
            'SKU',
            'Category',
            'Supplier',
            'Price',
            'Quantity',
            'Description',
            'Created At',
        ];
    }
}
