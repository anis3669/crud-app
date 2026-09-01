<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryHistory;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryApiController extends Controller
{
    /**
     * Get current inventory.
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with(['category', 'supplier'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->filter === 'in_stock', function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->when($request->filter === 'out_of_stock', function ($query) {
                $query->where('quantity', '<=', 0);
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 10));

        return response()->json($products);
    }

    /**
     * Adjust product stock.
     */
    public function adjust(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:stock_in,stock_out,adjustment'],
            'quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($validated, $product) {
            $quantityBefore = $product->quantity;

            switch ($validated['type']) {
                case 'stock_in':
                    $quantityChange = $validated['quantity'];
                    $quantityAfter = $quantityBefore + $quantityChange;
                    break;

                case 'stock_out':
                    $quantityChange = -$validated['quantity'];
                    $quantityAfter = $quantityBefore + $quantityChange;

                    if ($quantityAfter < 0) {
                        return response()->json([
                            'message' => 'Insufficient stock.',
                        ], 422);
                    }

                    break;

                case 'adjustment':
                    $quantityAfter = $validated['quantity'];
                    $quantityChange = $quantityAfter - $quantityBefore;
                    break;

                default:
                    abort(422, 'Invalid inventory adjustment type.');
            }

            $product->update([
                'quantity' => $quantityAfter,
            ]);

            $history = InventoryHistory::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'quantity_before' => $quantityBefore,
                'quantity_change' => $quantityChange,
                'quantity_after' => $quantityAfter,
                'type' => $validated['type'],
                'reason' => $validated['reason'] ?? null,
            ]);

            return response()->json([
                'message' => 'Inventory updated successfully.',
                'product' => $product->fresh(),
                'history' => $history,
            ]);
        });
    }

    /**
     * Get inventory history.
     */
    public function history(Request $request): JsonResponse
    {
        $history = InventoryHistory::query()
            ->with([
                'product:id,name,sku',
                'user:id,name,email',
            ])
            ->when($request->product_id, function ($query, $productId) {
                $query->where('product_id', $productId);
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($history);
    }
}
