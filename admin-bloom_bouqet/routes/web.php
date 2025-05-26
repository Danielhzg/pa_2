<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CarouselController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\NotificationController;
use Illuminate\Support\Facades\Route;

// Redirect root URL to admin page
Route::get('/', function () {
    return redirect()->route('admin.home');
});

// Define the login route
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin routes with auth:admin middleware
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('home');
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    
    // Profile Routes
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::post('/profile', [AdminController::class, 'updateProfile'])->name('profile.update');
    
    // Product Routes
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    
    Route::post('/categories/store', [AdminController::class, 'storeCategory'])->name('categories.store');
    Route::resource('carousels', CarouselController::class);
    Route::patch('/carousels/{carousel}/toggle-active', [CarouselController::class, 'toggleActive'])->name('carousels.toggle-active');

    // Order Routes
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::get('/orders-stats', [OrderController::class, 'getOrderStats'])->name('orders.stats');
    Route::get('/orders-check-new', [OrderController::class, 'checkNewOrders'])->name('orders.check-new');
    Route::get('/orders/{order}/api', [OrderController::class, 'getOrderApi'])->name('orders.api');

    // Report Routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    
    // Customer Routes
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    
    // Chat Routes
    Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
    Route::post('/chats/{chat}/messages', [ChatController::class, 'sendMessage'])->name('chats.sendMessage');
    Route::get('/chats/{chat}/new-messages', [ChatController::class, 'getNewMessages'])->name('chats.getNewMessages');
});

Route::prefix('admin/categories')->group(function () {
    Route::get('/', [AdminController::class, 'listCategories'])->name('admin.categories.index');
    Route::get('/create', [AdminController::class, 'createCategory'])->name('admin.categories.create');
    Route::post('/store', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
    Route::get('/{category}/edit', [AdminController::class, 'editCategory'])->name('admin.categories.edit');
    Route::put('/{category}', [AdminController::class, 'updateCategory'])->name('admin.categories.update');
    Route::delete('/{category}', [AdminController::class, 'deleteCategory'])->name('admin.categories.delete');
    Route::delete('/{category}/delete-with-products', [CategoryController::class, 'deleteWithProducts'])->name('admin.categories.delete-with-products');
});

// Notification routes
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
});

// Admin notification routes
Route::middleware(['auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/mark-as-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notifications/mark-all-as-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
    Route::get('/notifications/unread-count', [App\Http\Controllers\Admin\NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
});

// Allow access to storage files
Route::get('/storage/{path}', function ($path) {
    return response()->file(storage_path('app/public/' . $path));
})->where('path', '.*')->middleware('cors');
