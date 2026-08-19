<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductApiController;

Route::middleware('auth:sanctum')->group(function () {
    // for single product actions
    Route::get('/products', [ProductApiController::class, 'index']);
    Route::post('/products', [ProductApiController::class, 'store']);
    Route::get('/products/{product}', [ProductApiController::class, 'show']);
    Route::put('/products/{product}', [ProductApiController::class, 'update']);
    Route::delete('/products/{product}', [ProductApiController::class, 'destroy']);
    // for bulk actions
    Route::delete('/products/bulk-delete', [ProductApiController::class, 'bulkDelete']);
    Route::put('/products/bulk-update', [ProductApiController::class, 'bulkUpdate']);
});
