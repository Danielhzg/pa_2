<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\CarouselController;
use App\Http\Controllers\API\FavoriteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->name('otp.verify');
    Route::post('resend-otp', [AuthController::class, 'resendOtp'])->name('otp.resend');
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('update-profile', [AuthController::class, 'updateProfile']);
        
        // Orders routes that require authentication
        Route::get('orders', [OrderController::class, 'getUserOrders']);
        Route::post('orders', [OrderController::class, 'createOrder']);
        Route::get('orders/{orderId}', [OrderController::class, 'getOrder']);
        Route::put('orders/{orderId}/status', [OrderController::class, 'updateStatus']);
        
        // Favorite products routes
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites/toggle', [FavoriteController::class, 'toggle']);
        Route::get('favorites/check/{productId}', [FavoriteController::class, 'check']);
    });

    // Product endpoints
    Route::get('products', [ProductController::class, 'index']); // Get all products
    Route::get('products/search', [ProductController::class, 'search']); // Search products by query
    Route::get('products/category/{category}', [ProductController::class, 'getByCategory']); // Get products by category
    Route::post('products', [ProductController::class, 'store']); // Add product
    
    // Customer endpoints
    Route::get('customers', [CustomerController::class, 'index']); // Get all customers
    Route::get('customers/{id}', [CustomerController::class, 'show']); // Get customer details
    Route::get('customers-stats', [CustomerController::class, 'getStatistics']); // Get customer statistics
    
    // Category endpoints
    Route::get('categories', [CategoryController::class, 'index']); // Get all categories

    // Carousel endpoints
    Route::get('carousels', [CarouselController::class, 'index']);
    Route::get('carousels/{id}', [CarouselController::class, 'show']);

    // Fetch user by email
    Route::get('user/{email}', [AuthController::class, 'getUserByEmail'])->name('user.get');
    
    // Payment routes
    Route::post('payments/create', [PaymentController::class, 'createPayment']);
    Route::get('payments/{orderId}/status', [PaymentController::class, 'checkStatus']);
    Route::get('payments/{orderId}/qr-code', [PaymentController::class, 'generateQRCode']);
});

// Public order creation endpoint for Midtrans
Route::post('orders/create', [OrderController::class, 'createOrder']);

// Midtrans notification handler
Route::post('payments/notification', [PaymentController::class, 'notification']);

// Fallback QR code generator for troubleshooting
Route::get('payments/{orderId}/qr-code', [PaymentController::class, 'generateQRCode']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});