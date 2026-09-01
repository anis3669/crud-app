<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\SupplierApiController;
use App\Http\Controllers\Api\InventoryApiController;

// Authentication
Route::post('/login', [AuthController::class, 'apiLogin']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

// Authenticated user
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/picture', [ProfileController::class, 'uploadPicture']);
    Route::delete('/profile/picture', [ProfileController::class, 'deletePicture']);

    // Bulk actions
    Route::delete(
        '/products/bulk-delete',
        [ProductApiController::class, 'bulkDelete']
    )->middleware('permission:products.delete');

    Route::post(
        '/products/bulk-update',
        [ProductApiController::class, 'bulkUpdate']
    )->middleware('permission:products.update');

    // Products
    Route::get('/products', [ProductApiController::class, 'index'])
        ->middleware('permission:products.view');
    Route::post('/products', [ProductApiController::class, 'store'])
        ->middleware('permission:products.create');
    Route::get('/products/{product}', [ProductApiController::class, 'show'])
        ->middleware('permission:products.view');
    Route::put('/products/{product}', [ProductApiController::class, 'update'])
        ->middleware('permission:products.update');
    Route::delete('/products/{product}', [ProductApiController::class, 'destroy'])
        ->middleware('permission:products.delete');
    Route::get('/trash', [ProductApiController::class, 'trash'])
        ->middleware('permission:products.delete');
    Route::post('/trash/{id}/restore', [ProductApiController::class, 'restore'])
        ->middleware('permission:products.update');
    Route::delete('/trash/{id}', [ProductApiController::class, 'forceDelete'])
        ->middleware('permission:products.delete');

    // Categories
    Route::get('/categories', [CategoryApiController::class, 'index'])
        ->middleware('permission:categories.view');

    Route::post('/categories', [CategoryApiController::class, 'store'])
        ->middleware('permission:categories.create');

    Route::get('/categories/{category}', [CategoryApiController::class, 'show'])
        ->middleware('permission:categories.view');

    Route::put('/categories/{category}', [CategoryApiController::class, 'update'])
        ->middleware('permission:categories.update');

    Route::delete('/categories/{category}', [CategoryApiController::class, 'destroy'])
        ->middleware('permission:categories.delete');

    // Suppliers
    Route::get('/suppliers', [SupplierApiController::class, 'index'])
        ->middleware('permission:suppliers.view');

    Route::post('/suppliers', [SupplierApiController::class, 'store'])
        ->middleware('permission:suppliers.create');

    Route::get('/suppliers/{supplier}', [SupplierApiController::class, 'show'])
        ->middleware('permission:suppliers.view');

    Route::put('/suppliers/{supplier}', [SupplierApiController::class, 'update'])
        ->middleware('permission:suppliers.update');

    Route::delete('/suppliers/{supplier}', [SupplierApiController::class, 'destroy'])
        ->middleware('permission:suppliers.delete');

    // Inventory
    Route::get('/inventory', [InventoryApiController::class, 'index'])
        ->middleware('permission:inventory.view');

    Route::post('/inventory/{product}/adjust', [InventoryApiController::class, 'adjust'])
        ->middleware('permission:inventory.adjust');

    Route::get('/inventory/history', [InventoryApiController::class, 'history'])
        ->middleware('permission:inventory.history');
});
