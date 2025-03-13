<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ChatController;

// Authentication Routes
Route::get('/', [AdminController::class, 'showLoginForm'])->name('login');
Route::get('login', [AdminController::class, 'showLoginForm'])->name('login');
Route::post('login', [AdminController::class, 'login']);
Route::post('logout', [AdminController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Products
    Route::resource('products', ProductController::class);
    
    // Orders
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::put('orders/{order}/payment', [OrderController::class, 'updatePayment'])->name('orders.updatePayment');
    
    // Chats
    Route::get('chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('chats/{user}', [ChatController::class, 'show'])->name('chats.show');
    Route::post('chats/{user}/messages', [ChatController::class, 'sendMessage'])->name('chats.sendMessage');
    
    // Dashboard routes
    Route::get('/dashboard-data', [AdminController::class, 'getChartData'])
        ->name('dashboard.data');
});
