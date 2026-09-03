<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

        $query = Product::with([
            'category:id,name',
            'supplier:id,name',
        ]);

        // Search
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
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

            'sku' => [
                'required',
                'string',
                'max:100',
                'unique:products,sku',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'supplier_id' => [
                'nullable',
                'integer',
                'exists:suppliers,id',
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

        if ($request->hasFile('image')) {
            $validated['image'] = $request
                ->file('image')
                ->store('products', 'public');
        }

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product->load([
                'category:id,name',
                'supplier:id,name',
            ]),
        ], 201);
    }

    // Get single product
    public function show(Product $product)
    {
        $product->load([
            'category:id,name',
            'supplier:id,name',
        ]);

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

            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->ignore($product->id),
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'supplier_id' => [
                'nullable',
                'integer',
                'exists:suppliers,id',
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
        $product->sku = $validated['sku'];
        $product->category_id = $validated['category_id'] ?? null;
        $product->supplier_id = $validated['supplier_id'] ?? null;
        $product->description = $validated['description'] ?? null;
        $product->price = $validated['price'];
        $product->quantity = $validated['quantity'];

        // Remove existing image
        $removeImage = $request->input('remove_image');

        $removeImage =
            $removeImage === '1' ||
            $removeImage === 1 ||
            $removeImage === true ||
            $removeImage === 'true';

        if ($removeImage && !empty($product->image)) {
            Storage::disk('public')->delete($product->image);

            $product->image = null;
        }

        // Upload new image
        if ($request->hasFile('image')) {
            if (!empty($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $product->image = $request
                ->file('image')
                ->store('products', 'public');
        }

        $product->save();

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product->load([
                'category:id,name',
                'supplier:id,name',
            ]),
        ]);
    }

    // Delete product
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product moved to trash successfully.',
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
                'distinct',
                'exists:products,id',
            ],
        ]);

        $deletedCount = Product::whereIn(
            'id',
            $validated['ids']
        )->delete();

        return response()->json([
            'message' => 'Selected products moved to trash successfully.',
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

            'products.*.sku' => [
                'required',
                'string',
                'max:100',
            ],

            'products.*.category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],

            'products.*.supplier_id' => [
                'required',
                'integer',
                'exists:suppliers,id',
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

            'products.*.removeImage' => [
                'nullable',
                'in:0,1',
            ],

            'products.*.image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['products'] as $index => $data) {
                $product = Product::findOrFail($data['id']);

                $product->name = $data['name'];
                $product->sku = $data['sku'];
                $product->category_id = $data['category_id'];
                $product->supplier_id = $data['supplier_id'];
                $product->description = $data['description'] ?? null;
                $product->price = $data['price'];
                $product->quantity = $data['quantity'];

                // Check image removal directly from request
                $removeImage = $request->input(
                    "products.$index.removeImage"
                );

                if (
                    $removeImage === '1' ||
                    $removeImage === 1 ||
                    $removeImage === true ||
                    $removeImage === 'true'
                ) {
                    if (!empty($product->image)) {
                        Storage::disk('public')->delete(
                            $product->image
                        );
                    }

                    $product->image = null;
                }

                // Upload new image
                if ($request->hasFile("products.$index.image")) {
                    if (!empty($product->image)) {
                        Storage::disk('public')->delete(
                            $product->image
                        );
                    }

                    $image = $request->file(
                        "products.$index.image"
                    );

                    $product->image = $image->store(
                        'products',
                        'public'
                    );
                }

                $product->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Products updated successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update products.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    // Restore product from trash
    public function restore($id)
    {
        $product = Product::withTrashed()
            ->findOrFail($id);

        $product->restore();

        return response()->json([
            'message' => 'Product restored successfully.',
            'product' => $product->load([
                'category:id,name',
                'supplier:id,name',
            ]),
        ]);
    }

    // Bulk restore products from trash
    public function bulkRestore(Request $request)
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
                'distinct',
            ],
        ]);

        $restoredCount = 0;

        DB::transaction(function () use (
            $validated,
            &$restoredCount
        ) {
            $products = Product::onlyTrashed()
                ->whereIn('id', $validated['ids'])
                ->get();

            foreach ($products as $product) {
                $product->restore();
                $restoredCount++;
            }
        });

        return response()->json([
            'message' => 'Selected products restored successfully.',
            'restored_count' => $restoredCount,
        ]);
    }

    // Permanent delete
    public function forceDelete($id)
    {
        $product = Product::withTrashed()
            ->findOrFail($id);

        if ($product->image) {
            Storage::disk('public')->delete(
                $product->image
            );
        }

        $product->forceDelete();

        return response()->json([
            'message' => 'Product permanently deleted.',
        ]);
    }

    // Bulk permanently delete products
    public function bulkForceDelete(Request $request)
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
                'distinct',
            ],
        ]);

        $deletedCount = 0;

        DB::transaction(function () use (
            $validated,
            &$deletedCount
        ) {
            $products = Product::onlyTrashed()
                ->whereIn('id', $validated['ids'])
                ->get();

            foreach ($products as $product) {
                if ($product->image) {
                    Storage::disk('public')->delete(
                        $product->image
                    );
                }

                $product->forceDelete();
                $deletedCount++;
            }
        });

        return response()->json([
            'message' => 'Selected products permanently deleted.',
            'deleted_count' => $deletedCount,
        ]);
    }

    // Get trashed products
    public function trash(Request $request)
    {
        $validated = $request->validate([
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $perPage = $validated['per_page'] ?? 10;

        $products = Product::onlyTrashed()
            ->with([
                'category:id,name',
                'supplier:id,name',
            ])
            ->latest('deleted_at')
            ->paginate($perPage);

        return response()->json($products);
    }
}
