<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\ProductServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;
use Illuminate\Support\Facades\Cache;

class ProductApiController extends Controller
{
    public function __construct(
        private ProductServiceInterface $productService
    ) {}

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
            'inventory:id,product_id',
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
        $stats = Cache::remember(
            'products.stats',
            now()->addSeconds(60),
            function () {
                return [
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
            }
        );
        return response()->json([
            'products' => $products,
            'stats' => $stats,
        ]);
    }

    // Create product with initial inventory

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

        $product = $this->productService->create(
            $validated,
            $request->user()->id
        );

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product->load([
                'category:id,name',
                'supplier:id,name',
                'inventory:id,product_id',
            ]),
        ], 201);
    }


    // Get single product
    public function show(Product $product)
    {
        $product->load([
            'category:id,name',
            'supplier:id,name',
            'inventory:id,product_id',
        ]);

        return response()->json($product);
    }

    // Update product details only
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

        $image = null;

        if ($request->hasFile('image')) {
            $image = $request
                ->file('image')
                ->store('products', 'public');
        }

        $removeImage = in_array(
            $request->input('remove_image'),
            ['1', 1, true, 'true'],
            true
        );

        $product = $this->productService->update(
            $product,
            $validated,
            $image,
            $removeImage
        );

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product->load([
                'category:id,name',
                'supplier:id,name',
                'inventory:id,product_id',
            ]),
        ]);
    }

    // Delete product
    public function destroy(Product $product)
    {
        $this->productService->delete($product);

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

        $deletedCount = $this->productService->bulkDelete(
            $validated['ids']
        );

        return response()->json([
            'message' => 'Selected products moved to trash successfully.',
            'deleted_count' => $deletedCount,
        ]);
    }
    // Bulk update product details only
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

        $images = [];
        $removeImages = [];

        foreach ($validated['products'] as $index => $data) {
            $removeImages[$index] =
                $request->input("products.$index.removeImage") === '1';

            if ($request->hasFile("products.$index.image")) {
                $images[$index] = $request->file(
                    "products.$index.image"
                );
            }
        }

        $this->productService->bulkUpdate(
            $validated['products'],
            $images,
            $removeImages
        );

        return response()->json([
            'message' => 'Products updated successfully.',
        ]);
    }

    // Restore product from trash

    public function restore($id)
    {
        $product = $this->productService->restore((int) $id);

        return response()->json([
            'message' => 'Product restored successfully.',
            'product' => $product->load([
                'category:id,name',
                'supplier:id,name',
                'inventory:id,product_id',
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

        $restoredCount = $this->productService->bulkRestore(
            $validated['ids']
        );

        return response()->json([
            'message' => 'Selected products restored successfully.',
            'restored_count' => $restoredCount,
        ]);
    }

    // Permanently delete product
    public function forceDelete($id)
    {
        try {
            $this->productService->forceDelete((int) $id);

            return response()->json([
                'message' => 'Product permanently deleted.',
            ]);
        } catch (LaravelValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Permanent delete failed.',
            ], 500);
        }
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

        $result = $this->productService->bulkForceDelete(
            $validated['ids']
        );

        return response()->json([
            'message' => 'Bulk permanent delete completed.',
            'deleted_count' => $result['deleted_count'],
            'skipped_count' => $result['skipped_count'],
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
                'inventory:id,product_id',
            ])
            ->latest('deleted_at')
            ->paginate($perPage);

        return response()->json($products);
    }
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
            ],
        ]);

        if ($validator->fails()) {
            return $this->importErrorResponse(
                'Import failed. No products were imported.',
                [
                    [
                        'row' => null,
                        'messages' => $validator->errors()->all(),
                    ],
                ]
            );
        }

        try {
            $this->productService->import(
                $request->file('file')
            );

            return response()->json([
                'message' => 'Products imported successfully.',
                'errors' => [],
            ]);
        } catch (ExcelValidationException $e) {
            $errors = collect($e->failures())
                ->map(function ($failure) {
                    return [
                        'row' => $failure->row(),
                        'messages' => collect($failure->errors())
                            ->filter(fn($message) => is_string($message))
                            ->unique()
                            ->values()
                            ->all(),
                    ];
                })
                ->filter(fn($error) => !empty($error['messages']))
                ->unique(
                    fn($error) =>
                    $error['row'] . '|' . implode('|', $error['messages'])
                )
                ->values()
                ->all();

            return $this->importErrorResponse(
                'Import failed. No products were imported.',
                $errors
            );
        } catch (LaravelValidationException $e) {
            return $this->importErrorResponse(
                'Import failed. No products were imported.',
                [
                    [
                        'row' => null,
                        'messages' => collect($e->errors())
                            ->flatten()
                            ->values()
                            ->all(),
                    ],
                ]
            );
        } catch (\Throwable $e) {
            report($e);

            return $this->importErrorResponse(
                'Unable to import products right now. Please try again.',
                [
                    [
                        'row' => null,
                        'messages' => [
                            'The import could not be completed. No products were imported.',
                        ],
                    ],
                ],
                500
            );
        }
    }

    private function importErrorResponse(
        string $message,
        array $errors,
        int $status = 422
    ) {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
