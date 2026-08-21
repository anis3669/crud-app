<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Display a listing of the resource.

    public function index()
    {
        $products = Product::all();

        return view('products.index', compact('products'));
    }

    //  Show the form for creating a new resource.
     
    public function create()
    {
        return view('products.create');
    }

    //  Store a newly created resource in storage.
     
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
        ]);

        Product::create($validated);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product created successfully.');
    }


    //  Display the specified resource.

    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }


    //  Show the form for editing the specified resource.

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    //  Update the specified resource in storage.
     
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
        ]);

        $product->update($validated);

        return redirect()
            ->route('products.show', $product);
    }

    //  Remove the specified resource from storage.
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
    // bulk deletion
    public function bulkDelete(Request $request)
{
    $ids = $request->input('selected_products', []);

    Product::whereIn('id', $ids)->delete();

    return redirect()
        ->route('products.index')
        ->with('success', 'Selected products deleted successfully.');
}
// bulk edit
public function bulkEdit(Request $request)
{
    $ids = $request->input('selected_products', []);

    if (empty($ids)) {
        return redirect()
            ->route('products.index')
            ->with('error', 'No products selected.');
    }

    $products = Product::whereIn('id', $ids)->get();

    return view('products.bulk-edit', compact('products'));
}

// bulk update
public function bulkUpdate(Request $request)
{
    $request->validate([
        'products' => 'required|array',
        'products.*.id' => 'required|exists:products,id',
        'products.*.name' => 'required|string|max:255',
        'products.*.price' => 'required|numeric|min:0',
        'products.*.quantity' => 'required|integer|min:0',
    ]);

    foreach ($request->products as $productData) {

        $product = Product::findOrFail($productData['id']);

        $product->update([
            'name' => $productData['name'],
            'price' => $productData['price'],
            'quantity' => $productData['quantity'],
        ]);
    }

    return redirect()
        ->route('products.index')
        ->with('success', 'Selected products updated successfully.');
}
}