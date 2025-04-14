<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CarouselController;
use Illuminate\Support\Facades\Route;

// Redirect root URL to admin page
Route::get('/', function () {
    return redirect()->route('admin.home');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('home');
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/categories/store', [AdminController::class, 'storeCategory'])->name('categories.store');
    Route::resource('carousels', CarouselController::class);
});

Route::prefix('admin/products')->group(function () {
    Route::get('/', [AdminController::class, 'listProducts'])->name('admin.products.index');
    Route::delete('/{product}', [AdminController::class, 'deleteProduct'])->name('admin.products.delete');
});

Route::prefix('admin/categories')->group(function () {
    Route::get('/', [AdminController::class, 'listCategories'])->name('admin.categories.index');
    Route::get('/create', [AdminController::class, 'createCategory'])->name('admin.categories.create');
    Route::post('/store', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
    Route::get('/{category}/edit', [AdminController::class, 'editCategory'])->name('admin.categories.edit');
    Route::put('/{category}', [AdminController::class, 'updateCategory'])->name('admin.categories.update');
    Route::delete('/{category}', [AdminController::class, 'deleteCategory'])->name('admin.categories.delete');
});
