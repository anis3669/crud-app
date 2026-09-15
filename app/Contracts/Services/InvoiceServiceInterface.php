<?php

namespace App\Contracts\Services;

use App\Models\Invoice;

interface InvoiceServiceInterface
{
    public function create(array $validated, int $userId): array;

    public function delete(Invoice $invoice, int $userId): void;
}
