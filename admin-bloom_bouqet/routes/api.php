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
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\NotificationController;

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
        
        // Chat routes
        Route::get('/chat', [ChatController::class, 'getChat']);
        Route::post('/chat/message', [ChatController::class, 'sendMessage']);
        Route::get('/chat/messages', [ChatController::class, 'getNewMessages']);
        Route::post('/chat/mark-as-read', [ChatController::class, 'markAsRead']);
        Route::post('/chat/typing', [ChatController::class, 'updateTypingStatus']);
        Route::get('/chat/order/{orderId}', [ChatController::class, 'getOrderMessages']);
        Route::get('/chat/admin-status', [ChatController::class, 'checkAdminStatus']);
        Route::post('/chat/check-admin-responses', [ChatController::class, 'checkAdminResponses']);
        Route::get('/chat/message/{messageId}/status', [ChatController::class, 'getMessageStatus']);
        
        // User profile
        Route::get('/user', [UserController::class, 'profile']);
        Route::put('/user', [UserController::class, 'updateProfile']);
        Route::post('/user/change-password', [UserController::class, 'changePassword']);
        
        // Cart
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart/add', [CartController::class, 'addToCart']);
        Route::put('/cart/{item}', [CartController::class, 'updateCartItem']);
        Route::delete('/cart/{item}', [CartController::class, 'removeFromCart']);
        Route::delete('/cart', [CartController::class, 'clearCart']);
        
        // Orders
        Route::get('/orders', [OrderController::class, 'getUserOrders']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::get('/orders/{order}/details', [OrderController::class, 'getOrderDetails']);
        
        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    });

    // Product endpoints
    Route::get('products', [ProductController::class, 'index']); // Get all products
    Route::get('products/search', [ProductController::class, 'search']); // Search products by query
    Route::get('products/category/{category}', [ProductController::class, 'getByCategory']); // Get products by category
    Route::post('products', [ProductController::class, 'store']); // Add product
    Route::post('products/check-stock', [ProductController::class, 'checkStock']); // Check stock availability
    Route::get('products/{id}/stock', [ProductController::class, 'getStockInfo']); // Get real-time stock info for a product
    Route::post('products/batch-stock', [ProductController::class, 'getBatchStockInfo']); // Get real-time stock info for multiple products
    
    // Order endpoints that don't require authentication
    Route::post('orders/create', [OrderController::class, 'createOrder']); // Create order without authentication
    Route::get('orders/{orderId}', [OrderController::class, 'getOrder']); // Get order details by ID
    Route::get('orders/{orderId}/details', [OrderController::class, 'getOrderDetails']); // Get detailed order information with customer data
    Route::post('orders/check-stock', [OrderController::class, 'checkStockAvailability']); // Check stock before ordering
    Route::get('users/{userId}/orders', [OrderController::class, 'getOrdersByUserId']); // Get all orders for a specific user
    
    // Order status notification endpoints
    Route::post('orders/{orderId}/notify', [OrderController::class, 'notifyOrderStatus']); // Send notification for order status change
    Route::post('orders/{orderId}/status/notify', [OrderController::class, 'notifyOrderStatus']); // Alternative endpoint for notifications
    Route::post('orders/{orderId}/refresh-cache', [OrderController::class, 'refreshCache']); // Refresh order cache (admin only)
    
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
    Route::post('payments/retry', [PaymentController::class, 'retryPayment']);
});

// Use auth:sanctum middleware with optional parameter to allow both authenticated and unauthenticated requests
Route::middleware(['auth.sanctum:optional'])->group(function () {
    // Order creation endpoint - accessible with or without authentication
    Route::post('orders/create', [OrderController::class, 'createOrder']);
    
    // Make API more robust by supporting various endpoint formats
    Route::post('v1/orders/create', [OrderController::class, 'createOrder']);
    Route::post('orders', [OrderController::class, 'createOrder']);
});

// Add a failsafe route for order creation that's more robust against errors
Route::post('failsafe/create-order', [OrderController::class, 'createOrder']);
Route::post('v1/failsafe/create-order', [OrderController::class, 'createOrder']);

// Midtrans notification handler
Route::post('payments/notification', [PaymentController::class, 'notification']);

// Manual update after Midtrans simulation
Route::post('payments/update-after-simulation', [PaymentController::class, 'updateAfterSimulation']);

// Direct payment for development testing (non-production only)
Route::post('payments/direct-payment', [PaymentController::class, 'directPayment']);

// Fallback QR code generator for troubleshooting
Route::get('payments/{orderId}/qr-code', [PaymentController::class, 'generateQRCode']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});