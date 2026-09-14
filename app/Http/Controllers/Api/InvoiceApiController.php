<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Notifications\InvoiceCreatedNotification;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\InvoiceService;

class InvoiceApiController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:pending,completed,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $validated['search'] ?? null;
        $status = $validated['status'] ?? null;
        $perPage = $validated['per_page'] ?? 10;

        $query = Invoice::query()
            ->with('user:id,name,email')
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('customer_email', 'like', '%' . $search . '%')
                    ->orWhere('customer_phone', 'like', '%' . $search . '%');
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return response()->json(
            $query->paginate($perPage)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => [
                'required',
                'string',
                'max:255',
            ],

            'customer_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'customer_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'tax_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'discount_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        try {
            $result = $this->invoiceService->create(
                $validated,
                $request->user()->id
            );

            User::whereHas('role', function ($query) {
                $query->whereIn('slug', ['admin', 'manager']);
            })->each(function (User $user) use ($result) {
                $user->notify(
                    new InvoiceCreatedNotification($result['invoice'])
                );
            });

            return response()->json([
                'message' => 'Invoice created successfully.',
                'invoice' => $result['invoice'],
                'calculation' => $result['calculation'],
            ], 201);
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Invoice could not be created. No stock or invoice changes were saved.',
            ], 500);
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'user:id,name,email',
            'items.product',
        ]);

        return response()->json([
            'invoice' => $invoice,
        ]);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        try {
            DB::transaction(function () use ($invoice) {
                $invoice->load('items');

                foreach ($invoice->items as $item) {
                    $product = Product::query()
                        ->lockForUpdate()
                        ->find($item->product_id);

                    if (!$product) {
                        throw ValidationException::withMessages([
                            'invoice' => [
                                "Product '{$item->product_name}' no longer exists, so this invoice cannot be deleted safely.",
                            ],
                        ]);
                    }

                    $quantityBefore = (int) $product->quantity;
                    $quantityChange = (int) $item->quantity;
                    $quantityAfter = $quantityBefore + $quantityChange;

                    $product->update([
                        'quantity' => $quantityAfter,
                    ]);

                    $product->inventoryHistories()->create([
                        'user_id' => request()->user()->id,
                        'type' => 'adjustment',
                        'quantity_before' => $quantityBefore,
                        'quantity_change' => $quantityChange,
                        'quantity_after' => $quantityAfter,
                        'reason' => "Invoice deletion #{$invoice->invoice_number}",
                    ]);
                }

                $invoice->delete();
            });

            return response()->json([
                'message' => 'Invoice deleted and stock restored successfully.',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Invoice could not be deleted. No stock or invoice changes were saved.',
            ], 500);
        }
    }

    private function generateInvoiceNumber(): string
    {
        do {
            $number =
                'INV-' .
                now()->format('Ymd-His') .
                '-' .
                random_int(100, 999);
        } while (
            Invoice::where(
                'invoice_number',
                $number
            )->exists()
        );

        return $number;
    }
}
