<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductApiController extends Controller
{
    // Get products with search, filters, price filters and pagination

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',

            'filter' => [
                'nullable',
                'string',
                'in:all,latest,oldest,in_stock,low_stock,out_of_stock',
            ],

            'min_price' => 'nullable|numeric|min:0',

            'max_price' => 'nullable|numeric|min:0',

            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $search = trim($validated['search'] ?? '');

        $filter = $validated['filter'] ?? 'all';

        $minPrice = $validated['min_price'] ?? null;

        $maxPrice = $validated['max_price'] ?? null;

        $perPage = $validated['per_page'] ?? 10;

        $query = Product::query();

        // Search

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere(
                        'description',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        // Product filter

        switch ($filter) {
            case 'latest':

                $query->orderBy('created_at', 'desc');

                break;

            case 'oldest':

                $query->orderBy('created_at', 'asc');

                break;

            case 'in_stock':

                $query->where('quantity', '>', 5)
                    ->orderBy('created_at', 'desc');

                break;

            case 'low_stock':

                $query->whereBetween('quantity', [1, 5])
                    ->orderBy('created_at', 'desc');

                break;

            case 'out_of_stock':

                $query->where('quantity', 0)
                    ->orderBy('created_at', 'desc');

                break;

            case 'all':

            default:

                $query->orderBy('created_at', 'desc');

                break;
        }

        // Minimum price

        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }

        // Maximum price

        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        // Pagination

        $products = $query->paginate($perPage);

        // Complete inventory statistics

        $stats = [
            'total_products' => Product::count(),

            'in_stock' => Product::where('quantity', '>', 5)->count(),

            'low_stock' => Product::whereBetween(
                'quantity',
                [1, 5]
            )->count(),

            'out_of_stock' => Product::where(
                'quantity',
                0
            )->count(),

            'total_quantity' => Product::sum('quantity'),

            'total_inventory_value' => DB::table('products')
                ->selectRaw(
                    'COALESCE(SUM(price * quantity), 0) as total'
                )
                ->value('total'),
        ];

        return response()->json([
            'products' => $products,

            'stats' => $stats,
        ]);
    }

    // Create product

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'category' => 'nullable|string|max:100',

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

        if ($request->hasFile('image')) {
            $validated['image'] = $request
                ->file('image')
                ->store('products', 'public');
        }

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully.',

            'product' => $product->fresh(),
        ], 201);
    }

    // Get single product

    public function show(Product $product)
    {
        return response()->json($product);
    }

    // Update product

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'category' => [
                'nullable',
                'string',
                'max:100',
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

        $product->name = $validated['name'];
        $product->category = $validated['category'] ?? null;

        $product->description =
            $validated['description'] ?? null;

        $product->price = $validated['price'];

        $product->quantity = $validated['quantity'];

        // Remove existing image

        $removeImage = $request->input('remove_image');

        $removeImage =
            $removeImage === '1' ||
            $removeImage === 1 ||
            $removeImage === true ||
            $removeImage === 'true';

        if (
            $removeImage &&
            !empty($product->image)
        ) {
            Storage::disk('public')->delete(
                $product->image
            );

            $product->image = null;
        }

        // Upload new image

        if ($request->hasFile('image')) {
            if (!empty($product->image)) {
                Storage::disk('public')->delete(
                    $product->image
                );
            }

            $product->image = $request
                ->file('image')
                ->store('products', 'public');
        }

        $product->save();

        return response()->json([
            'message' => 'Product updated successfully.',

            'product' => $product->fresh(),
        ]);
    }

    // Delete product

    public function destroy(Product $product)
    {
        $image = $product->image;

        $product->delete();

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    // Bulk delete

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

        $deletedCount = Product::whereIn(
            'id',
            $validated['ids']
        )->delete();

        foreach ($products as $product) {
            if ($product->image) {
                Storage::disk('public')->delete(
                    $product->image
                );
            }
        }

        return response()->json([
            'message' =>
            'Selected products deleted successfully.',

            'deleted_count' => $deletedCount,
        ]);
    }

    // Bulk update

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

        foreach (
            $validated['products']
            as $index => $productData
        ) {
            $product = Product::findOrFail(
                $productData['id']
            );

            $product->name =
                $productData['name'];

            $product->description =
                $productData['description'] ?? null;

            $product->price =
                $productData['price'];

            $product->quantity =
                $productData['quantity'];

            // Remove image

            $removeImage = $request->input(
                "products.$index.remove_image"
            );

            $removeImage =
                $removeImage === '1' ||
                $removeImage === 1 ||
                $removeImage === true ||
                $removeImage === 'true';

            if (
                $removeImage &&
                !empty($product->image)
            ) {
                Storage::disk('public')->delete(
                    $product->image
                );

                $product->image = null;
            }

            // Upload new image

            $imageKey =
                "products.$index.image";

            if ($request->hasFile($imageKey)) {
                if (!empty($product->image)) {
                    Storage::disk('public')->delete(
                        $product->image
                    );
                }

                $product->image = $request
                    ->file($imageKey)
                    ->store('products', 'public');
            }

            $product->save();

            $updatedProducts[] =
                $product->fresh();
        }

        return response()->json([
            'message' =>
            'Selected products updated successfully.',

            'products' =>
            $updatedProducts,
        ]);
    }
}
