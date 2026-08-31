<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\ProfileController;

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
    );

    Route::post(
        '/products/bulk-update',
        [ProductApiController::class, 'bulkUpdate']
    );

    // Products
    Route::get('/products', [ProductApiController::class, 'index']);
    Route::post('/products', [ProductApiController::class, 'store']);
    Route::get('/products/{product}', [ProductApiController::class, 'show']);
    Route::put('/products/{product}', [ProductApiController::class, 'update']);
    Route::delete('/products/{product}', [ProductApiController::class, 'destroy']);
    Route::get('/trash', [ProductApiController::class, 'trash']);
    Route::post('/trash/{id}/restore', [ProductApiController::class, 'restore']);
    Route::delete('/trash/{id}', [ProductApiController::class, 'forceDelete']);
});
