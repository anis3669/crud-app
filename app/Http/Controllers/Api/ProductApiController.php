<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductApiController extends Controller
{
    // GET ALL PRODUCTS
    // SEARCH + PAGINATION

    public function index(Request $request)
    {
        // Pagination
        $perPage = (int) $request->input('per_page', 10);

        // Keep pagination between 1 and 100
        $perPage = max(1, min($perPage, 100));

        // Search
        $search = trim(
            $request->input('search', '')
        );

        // Filter
        $filter = $request->input('filter', 'all');

        // Start query
        $query = Product::query();


// search

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

// filter

        switch ($filter) {

            // Products with quantity greater than 0
            case 'in_stock':

                $query->where(
                    'quantity',
                    '>',
                    0
                );

                break;


            // Products with quantity equal to 0
            case 'out_of_stock':

                $query->where(
                    'quantity',
                    '=',
                    0
                );

                break;


            // Newest products first
            case 'latest':

                $query->latest();

                break;


            // Oldest products first
            case 'oldest':

                $query->oldest();

                break;


            // All products
            case 'all':
            default:

                $query->latest();

                break;
        }



// Pagination
$products = $query->paginate($perPage);

$totalProducts = Product::count();

$inStock = Product::where('quantity', '>', 0)->count();

$outOfStock = Product::where('quantity', '=', 0)->count();

$totalQuantity = Product::sum('quantity');

// Response
return response()->json([
    'products' => $products,
    'stats' => [
        'total_products' => $totalProducts,
        'in_stock' => $inStock,
        'out_of_stock' => $outOfStock,
        'total_quantity' => $totalQuantity,
    ],
]);
    }

    // CREATE PRODUCT


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


        // STORE IMAGE


        if ($request->hasFile('image')) {
            $validated['image'] = $request
                ->file('image')
                ->store('products', 'public');
        }


        // CREATE PRODUCT

        $product = Product::create($validated);


        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product->fresh(),
        ], 201);
    }


    // GET SINGLE PRODUCT

    public function show(Product $product)
    {
        return response()->json($product);
    }


    // UPDATE SINGLE PRODUCT

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


        // BASIC FIELDS

        $product->name =
            $validated['name'];

        $product->description =
            $validated['description'] ?? null;

        $product->price =
            $validated['price'];

        $product->quantity =
            $validated['quantity'];


        // CHECK REMOVE IMAGE

        $removeImage =
            $request->input('remove_image');

        $removeImage =
            $removeImage === '1' ||
            $removeImage === 1 ||
            $removeImage === true ||
            $removeImage === 'true';


        // REMOVE EXISTING IMAGE

        if (
            $removeImage &&
            !empty($product->image)
        ) {
            Storage::disk('public')->delete(
                $product->image
            );

            $product->image = null;
        }


        // NEW IMAGE

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


        // SAVE

        $product->save();


        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product->fresh(),
        ]);
    }


    // DELETE SINGLE PRODUCT
    public function destroy(Product $product)
    {
        // Remember the image path before deleting the product
        $image = $product->image;

        // Delete product from database
        $product->delete();

        // Delete product image from Laravel storage
        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    // BULK DELETE

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:products,id',
        ]);

        // Get products before deleting them
        $products = Product::whereIn(
            'id',
            $validated['ids']
        )->get();

        // Delete database records
        $deletedCount = Product::whereIn(
            'id',
            $validated['ids']
        )->delete();

        // Delete their images from storage
        foreach ($products as $product) {
            if ($product->image) {
                Storage::disk('public')->delete(
                    $product->image
                );
            }
        }

        return response()->json([
            'message' => 'Selected products deleted successfully.',
            'deleted_count' => $deletedCount,
        ]);
    }

    // BULK UPDATE

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


        // UPDATE EACH PRODUCT

        foreach (
            $validated['products']
            as $index => $productData
        ) {

            $product = Product::findOrFail(
                $productData['id']
            );


            // BASIC FIELDS

            $product->name =
                $productData['name'];

            $product->description =
                $productData['description'] ?? null;

            $product->price =
                $productData['price'];

            $product->quantity =
                $productData['quantity'];


            // REMOVE IMAGE FLAG

            $removeImage =
                $request->input(
                    "products.$index.remove_image"
                );

            $removeImage =
                $removeImage === '1' ||
                $removeImage === 1 ||
                $removeImage === true ||
                $removeImage === 'true';


            // REMOVE EXISTING IMAGE

            if (
                $removeImage &&
                !empty($product->image)
            ) {

                Storage::disk('public')->delete(
                    $product->image
                );

                $product->image = null;
            }


            // NEW IMAGE

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


            // SAVE

            $product->save();


            // ADD FRESH PRODUCT

            $updatedProducts[] =
                $product->fresh();
        }


        // RESPONSE

        return response()->json([
            'message' =>
            'Selected products updated successfully.',

            'products' =>
            $updatedProducts,
        ]);
    }
}
