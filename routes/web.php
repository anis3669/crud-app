<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
    // to get the products index page,use the following code:
    //  return redirect()->route('products.index');
});
// route for bulk delete, bulk edit, and bulk update
Route::delete('/products/bulk-delete', [ProductController::class, 'bulkDelete'])
    ->name('products.bulkDelete');
Route::get('/products/bulk-edit', [ProductController::class, 'bulkEdit'])
    ->name('products.bulkEdit');
Route::put('/products/bulk-update', [ProductController::class, 'bulkUpdate'])
    ->name('products.bulkUpdate');
// route for register 
Route::get('/register', [AuthController::class, 'showRegister'])
    ->name('register');

Route::post('/register', [AuthController::class, 'register']);
// route for login
Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::middleware('auth.custom')->group(function () {

    Route::resource('products', ProductController::class);

});
