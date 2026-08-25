<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    // display all the products
    public function index()
    {
        $products = Product::all();

        return response()->json($products);
    }
    // store newly created product
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // Store image if provided
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product,
        ], 201);
    }
    // display a single product
    public function show(Product $product)
    {
        return response()->json($product);
    }

    // update a single product
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product,
        ]);
    }

    // delete a single product
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    // delete multiple products
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:products,id',
        ]);

        $deletedCount = Product::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'message' => 'Selected products deleted successfully.',
            'deleted_count' => $deletedCount,
        ]);
    }

    // update multiple products individually
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'products' => 'required|array|min:1',

            'products.*.id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'products.*.name' => [
                'required',
                'string',
                'max:255',
            ],

            'products.*.description' => [
                'nullable',
                'string',
            ],

            'products.*.price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'products.*.quantity' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        $updatedProducts = [];

        foreach ($validated['products'] as $productData) {
            $product = Product::findOrFail($productData['id']);

            $product->update([
                'name' => $productData['name'],
                'description' => $productData['description'] ?? null,
                'price' => $productData['price'],
                'quantity' => $productData['quantity'],
            ]);

            $updatedProducts[] = $product->fresh();
        }

        return response()->json([
            'message' => 'Selected products updated successfully.',
            'products' => $updatedProducts,
        ]);
    }
}
