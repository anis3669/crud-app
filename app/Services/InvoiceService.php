<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function create(array $validated, int $userId): array
    {
        $taxPercentage = (float) ($validated['tax_percentage'] ?? 0);
        $discountPercentage = (float) ($validated['discount_percentage'] ?? 0);

        return DB::transaction(function () use (
            $validated,
            $userId,
            $taxPercentage,
            $discountPercentage
        ) {
            $subtotal = 0;
            $invoiceItems = [];
            $stockChanges = [];

            foreach ($validated['items'] as $item) {
                $product = Product::query()
                    ->lockForUpdate()
                    ->find($item['product_id']);

                if (!$product) {
                    abort(
                        422,
                        "Product #{$item['product_id']} was not found."
                    );
                }

                $quantity = (int) $item['quantity'];
                $stockBefore = (int) $product->quantity;

                if ($quantity > $stockBefore) {
                    abort(
                        422,
                        "Insufficient stock for {$product->name}. Available stock: {$stockBefore}."
                    );
                }

                $unitPrice = (float) $product->price;

                $itemSubtotal = round(
                    $quantity * $unitPrice,
                    2
                );

                $subtotal += $itemSubtotal;

                $invoiceItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                ];

                $stockChanges[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'quantity_before' => $stockBefore,
                ];
            }

            $subtotal = round($subtotal, 2);

            $tax = round(
                $subtotal * ($taxPercentage / 100),
                2
            );

            $discount = round(
                $subtotal * ($discountPercentage / 100),
                2
            );

            if ($discount > ($subtotal + $tax)) {
                abort(
                    422,
                    'Discount cannot be greater than the invoice amount.'
                );
            }

            $total = round(
                $subtotal + $tax - $discount,
                2
            );

            $invoice = Invoice::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'user_id' => $userId,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'status' => 'completed',
            ]);

            foreach ($invoiceItems as $item) {
                $invoice->items()->create($item);
            }

            foreach ($stockChanges as $stockChange) {
                $product = $stockChange['product'];
                $quantity = $stockChange['quantity'];
                $quantityBefore = $stockChange['quantity_before'];
                $quantityAfter = $quantityBefore - $quantity;

                $product->update([
                    'quantity' => $quantityAfter,
                ]);

                $product->inventoryHistories()->create([
                    'user_id' => $userId,
                    'type' => 'sale',
                    'quantity_before' => $quantityBefore,
                    'quantity_change' => -$quantity,
                    'quantity_after' => $quantityAfter,
                    'reason' => "Invoice #{$invoice->invoice_number}",
                ]);
            }

            $invoice->load([
                'user:id,name,email',
                'items.product',
            ]);

            return [
                'invoice' => $invoice,
                'calculation' => [
                    'subtotal' => $subtotal,
                    'tax_percentage' => $taxPercentage,
                    'tax' => $tax,
                    'discount_percentage' => $discountPercentage,
                    'discount' => $discount,
                    'total' => $total,
                ],
            ];
        });
    }

    public function delete(Invoice $invoice, int $userId): void
    {
        DB::transaction(function () use ($invoice, $userId) {
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
                    'user_id' => $userId,
                    'type' => 'adjustment',
                    'quantity_before' => $quantityBefore,
                    'quantity_change' => $quantityChange,
                    'quantity_after' => $quantityAfter,
                    'reason' => "Invoice deletion #{$invoice->invoice_number}",
                ]);
            }

            $invoice->delete();
        });
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
            Invoice::where('invoice_number', $number)->exists()
        );

        return $number;
    }
}
