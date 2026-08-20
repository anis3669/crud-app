<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ProductApiController;

// Authentication
Route::post('/login', [AuthController::class, 'apiLogin']);

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('web');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('web');

Route::middleware('auth:sanctum')->group(function () {
     // Bulk actions
    Route::delete(
        '/products/bulk-delete',
        [ProductApiController::class, 'bulkDelete']
    );

    Route::put(
        '/products/bulk-update',
        [ProductApiController::class, 'bulkUpdate']
    );
    // Products
    Route::get('/products', [ProductApiController::class, 'index']);
    Route::post('/products', [ProductApiController::class, 'store']);
    Route::get('/products/{product}', [ProductApiController::class, 'show']);
    Route::put('/products/{product}', [ProductApiController::class, 'update']);
    Route::delete('/products/{product}', [ProductApiController::class, 'destroy']);

});
