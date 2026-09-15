<?php

namespace App\Contracts\Services;

use App\Models\Product;

interface ProductServiceInterface
{
    public function create(array $validated, int $userId): Product;

    public function update(
        Product $product,
        array $validated,
        ?string $image = null,
        bool $removeImage = false
    ): Product;

    public function bulkUpdate(
        array $products,
        array $images = [],
        array $removeImages = []
    ): void;

    public function delete(Product $product): void;

    public function bulkDelete(array $ids): int;

    public function restore(int $id): Product;

    public function bulkRestore(array $ids): int;

    public function forceDelete(int $id): void;

    public function bulkForceDelete(array $ids): array;

    public function import($file): void;
}
