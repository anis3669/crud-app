<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    return view('welcome');
    // to get the products index page,use the following code:
    //  return redirect()->route('products.index');
});
Route::delete('/products/bulk-delete', [ProductController::class, 'bulkDelete'])
    ->name('products.bulkDelete');
Route::get('/products/bulk-edit', [ProductController::class, 'bulkEdit'])
    ->name('products.bulkEdit');
Route::put('/products/bulk-update', [ProductController::class, 'bulkUpdate'])
    ->name('products.bulkUpdate');

Route::resource('products', ProductController::class);