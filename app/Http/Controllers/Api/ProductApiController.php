<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductApiController extends Controller
{
    // =========================================================
    // GET ALL PRODUCTS
    // SEARCH + PAGINATION
    // =========================================================

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        // Keep pagination between 1 and 100 products per page
        $perPage = max(1, min($perPage, 100));

        $search = trim(
            $request->input('search', '')
        );

        $query = Product::query();

        // Search product name and description
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'description',
                    'like',
                    "%{$search}%"
                );
            });
        }

        // Paginate products
        $products = $query
            ->latest()
            ->paginate($perPage);

        return response()->json($products);
    }


    // =========================================================
    // CREATE PRODUCT
    // =========================================================

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
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

            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
        ]);


        // =====================================================
        // STORE IMAGE
        // =====================================================

        if ($request->hasFile('image')) {
            $validated['image'] = $request
                ->file('image')
                ->store('products', 'public');
        }


        // =====================================================
        // CREATE PRODUCT
        // =====================================================

        $product = Product::create($validated);


        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product->fresh(),
        ], 201);
    }


    // =========================================================
    // GET SINGLE PRODUCT
    // =========================================================

    public function show(Product $product)
    {
        return response()->json($product);
    }


    // =========================================================
    // UPDATE SINGLE PRODUCT
    // =========================================================

    public function update(
        Request $request,
        Product $product
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
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

            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],

            'remove_image' => [
                'nullable',
            ],
        ]);


        // =====================================================
        // BASIC FIELDS
        // =====================================================

        $product->name =
            $validated['name'];

        $product->description =
            $validated['description'] ?? null;

        $product->price =
            $validated['price'];

        $product->quantity =
            $validated['quantity'];


        // =====================================================
        // CHECK REMOVE IMAGE
        // =====================================================

        $removeImage =
            $request->input('remove_image');

        $removeImage =
            $removeImage === '1' ||
            $removeImage === 1 ||
            $removeImage === true ||
            $removeImage === 'true';


        // =====================================================
        // REMOVE EXISTING IMAGE
        // =====================================================

        if (
            $removeImage &&
            !empty($product->image)
        ) {
            Storage::disk('public')->delete(
                $product->image
            );

            $product->image = null;
        }


        // =====================================================
        // NEW IMAGE
        // =====================================================

        if ($request->hasFile('image')) {

            // Delete old image
            if (!empty($product->image)) {
                Storage::disk('public')->delete(
                    $product->image
                );
            }

            // Store new image
            $product->image =
                $request
                    ->file('image')
                    ->store(
                        'products',
                        'public'
                    );
        }


        // =====================================================
        // SAVE
        // =====================================================

        $product->save();


        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product->fresh(),
        ]);
    }


    // =========================================================
    // DELETE SINGLE PRODUCT
    // =========================================================

    public function destroy(Product $product)
    {
        // Delete image from storage
        if (!empty($product->image)) {
            Storage::disk('public')->delete(
                $product->image
            );
        }


        // Delete product
        $product->delete();


        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }


    // =========================================================
    // BULK DELETE
    // =========================================================

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => [
                'required',
                'array',
                'min:1',
            ],

            'ids.*' => [
                'required',
                'integer',
                'exists:products,id',
            ],
        ]);


        $products = Product::whereIn(
            'id',
            $validated['ids']
        )->get();


        // =====================================================
        // DELETE PRODUCT IMAGES
        // =====================================================

        foreach ($products as $product) {

            if (!empty($product->image)) {
                Storage::disk('public')->delete(
                    $product->image
                );
            }
        }


        // =====================================================
        // DELETE PRODUCTS
        // =====================================================

        $deletedCount = Product::whereIn(
            'id',
            $validated['ids']
        )->delete();


        return response()->json([
            'message' =>
                'Selected products deleted successfully.',

            'deleted_count' =>
                $deletedCount,
        ]);
    }


    // =========================================================
    // BULK UPDATE
    // =========================================================

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'products' => [
                'required',
                'array',
                'min:1',
            ],

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

            'products.*.remove_image' => [
                'nullable',
            ],

            'products.*.image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
        ]);


        $updatedProducts = [];


        // =====================================================
        // UPDATE EACH PRODUCT
        // =====================================================

        foreach (
            $validated['products']
            as $index => $productData
        ) {

            $product = Product::findOrFail(
                $productData['id']
            );


            // =================================================
            // BASIC FIELDS
            // =================================================

            $product->name =
                $productData['name'];

            $product->description =
                $productData['description'] ?? null;

            $product->price =
                $productData['price'];

            $product->quantity =
                $productData['quantity'];


            // =================================================
            // REMOVE IMAGE FLAG
            // =================================================

            $removeImage =
                $request->input(
                    "products.$index.remove_image"
                );

            $removeImage =
                $removeImage === '1' ||
                $removeImage === 1 ||
                $removeImage === true ||
                $removeImage === 'true';


            // =================================================
            // REMOVE EXISTING IMAGE
            // =================================================

            if (
                $removeImage &&
                !empty($product->image)
            ) {

                Storage::disk('public')->delete(
                    $product->image
                );

                $product->image = null;
            }


            // =================================================
            // NEW IMAGE
            // =================================================

            $imageKey =
                "products.$index.image";


            if ($request->hasFile($imageKey)) {

                // Delete current image
                if (!empty($product->image)) {

                    Storage::disk('public')->delete(
                        $product->image
                    );
                }


                // Store new image
                $product->image =
                    $request
                        ->file($imageKey)
                        ->store(
                            'products',
                            'public'
                        );
            }


            // =================================================
            // SAVE
            // =================================================

            $product->save();


            // =================================================
            // ADD FRESH PRODUCT
            // =================================================

            $updatedProducts[] =
                $product->fresh();
        }


        // =====================================================
        // RESPONSE
        // =====================================================

        return response()->json([
            'message' =>
                'Selected products updated successfully.',

            'products' =>
                $updatedProducts,
        ]);
    }
}
