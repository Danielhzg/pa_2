<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;


// Route::get('/', function () {
//     return view('admin.home');
// });
Route::get('/', [AdminController::class, 'index'])->name('admin.home');
Route::get('/admin/products', [ProductController::class, 'index'])->name('admin.products.index');
Route::get('/products/create', [ProductController::class, 'create'])->name('admin.products.create');
Route::post('/products', [ProductController::class, 'store'])->name('admin.products.store');
Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
Route::put('/products/{product}', [ProductController::class, 'update'])->name('admin.products.update');
Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('home');
    // Route::resource('products', ProductController::class);
});
