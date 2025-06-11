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
// use App\Http\Controllers\CustomerOrderController; // Controller not implemented yet
// use App\Http\Controllers\OrderDetailController; // Controller not implemented yet
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
    Route::post('/orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->name('orders.updatePaymentStatus');
    Route::get('/orders-stats', [OrderController::class, 'getOrderStats'])->name('orders.stats');
    Route::get('/orders-check-new', [OrderController::class, 'checkNewOrders'])->name('orders.check-new');
    Route::get('/orders/{order}/api', [OrderController::class, 'getOrderApi'])->name('orders.api');
    Route::get('/orders/{order}/detail', [OrderController::class, 'getOrderDetail'])->name('orders.detail');

    // Enhanced Order Management Routes
    Route::get('/order-management', [App\Http\Controllers\Admin\OrderManagementController::class, 'index'])->name('admin.order-management.index');
    Route::get('/order-management/{id}', [App\Http\Controllers\Admin\OrderManagementController::class, 'show'])->name('admin.order-management.show');
    Route::post('/order-management/{id}/status', [App\Http\Controllers\Admin\OrderManagementController::class, 'updateStatus'])->name('admin.order-management.updateStatus');
    Route::post('/order-management/{id}/payment-status', [App\Http\Controllers\Admin\OrderManagementController::class, 'updatePaymentStatus'])->name('admin.order-management.updatePaymentStatus');
    Route::get('/order-management/stats/dashboard', [App\Http\Controllers\Admin\OrderManagementController::class, 'getStats'])->name('admin.order-management.stats');
    Route::get('/order-management/notifications/list', [App\Http\Controllers\Admin\OrderManagementController::class, 'getNotifications'])->name('admin.order-management.notifications');
    Route::post('/order-management/notifications/read', [App\Http\Controllers\Admin\OrderManagementController::class, 'markNotificationsRead'])->name('admin.order-management.notifications.read');
    Route::post('/order-management/bulk-update', [App\Http\Controllers\Admin\OrderManagementController::class, 'bulkUpdateStatus'])->name('admin.order-management.bulk-update');

    // Real-time dashboard route
    Route::get('/orders/realtime-dashboard', [App\Http\Controllers\Admin\OrderManagementController::class, 'realtimeDashboard'])->name('admin.orders.realtime-dashboard');

    // Report Routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
    
    // Customer Routes
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    
    // Chat Routes
    Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
    Route::post('/chats/{chat}/send', [ChatController::class, 'sendMessage'])->name('chats.send');
    Route::get('/chats/{chat}/new-messages', [ChatController::class, 'getNewMessages'])->name('chats.new-messages');
    Route::post('/chats/{chat}/clear', [ChatController::class, 'clearChat'])->name('chats.clear');
    Route::post('/chats/{chat}/read/{messageId?}', [ChatController::class, 'markMessagesAsRead'])->name('chats.read');
    Route::get('/chats/unread-count', [ChatController::class, 'getUnreadCount'])->name('chats.unread-count');
    Route::post('/chats/mark-all-read', [ChatController::class, 'markAllAsRead'])->name('chats.mark-all-read');
    Route::get('/chats/check-all', [ChatController::class, 'checkAllChatsForNewMessages'])->name('chats.check-all');
    Route::post('/chats/{chat}/typing', [ChatController::class, 'sendTypingIndicator'])->name('chats.typing');
    Route::delete('/chats/{chat}/typing/{typingMessageId}', [ChatController::class, 'removeTypingIndicator'])->name('chats.remove-typing');
});

// Customer routes with auth middleware
Route::middleware(['auth'])->group(function () {
    // Customer Dashboard
    Route::get('/dashboard', function () {
        return view('customer.dashboard');
    })->name('customer.dashboard');
    
    // Customer Profile
    Route::get('/profile', function () {
        return view('customer.profile');
    })->name('customer.profile');
    
    // Customer Addresses
    Route::get('/addresses', function () {
        return view('customer.addresses');
    })->name('customer.addresses');
    
    // Customer Wishlist
    Route::get('/wishlist', function () {
        return view('customer.wishlist');
    })->name('customer.wishlist');
    
    // Customer Orders (Controllers not implemented yet)
    // Route::get('/orders', [CustomerOrderController::class, 'index'])->name('customer.orders.index');
    // Route::get('/orders/{orderId}', [CustomerOrderController::class, 'show'])->name('customer.orders.show');
    // Route::get('/orders/{orderId}/track', [CustomerOrderController::class, 'track'])->name('customer.orders.track');
    // Route::post('/orders/{orderId}/cancel', [CustomerOrderController::class, 'cancel'])->name('customer.orders.cancel');
    // Route::post('/orders/{orderId}/complete', [CustomerOrderController::class, 'complete'])->name('customer.orders.complete');

    // Order Detail Routes (Controllers not implemented yet)
    // Route::get('/order/{orderId}', [OrderDetailController::class, 'show'])->name('order.detail');
    // Route::get('/order/{orderId}/track', [OrderDetailController::class, 'track'])->name('order.track');
    // Route::post('/order/{orderId}/cancel', [OrderDetailController::class, 'cancel'])->name('order.cancel');
    // Route::post('/order/{orderId}/complete', [OrderDetailController::class, 'complete'])->name('order.complete');
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
    Route::get('/notifications/latest', [App\Http\Controllers\Admin\NotificationController::class, 'getLatest'])->name('notifications.latest');
});

// Allow access to storage files
Route::get('/storage/{path}', function ($path) {
    return response()->file(storage_path('app/public/' . $path));
})->where('path', '.*')->middleware('cors');

// Add this new route for updating product stock
Route::post('admin/products/{product}/update-stock', [App\Http\Controllers\Admin\ProductController::class, 'updateStock'])
    ->name('admin.products.update-stock')
    ->middleware(['auth:admin']);

// Add this new route for testing orders page
Route::get('/test-orders', function () {
    // Base query with user relationship
    $query = App\Models\Order::with(['user']);
    
    // Get status counts for summary stats
    $waitingForPaymentCount = App\Models\Order::where('status', App\Models\Order::STATUS_WAITING_FOR_PAYMENT)->count();
    $processingCount = App\Models\Order::where('status', App\Models\Order::STATUS_PROCESSING)->count();
    $shippingCount = App\Models\Order::where('status', App\Models\Order::STATUS_SHIPPING)->count();
    $deliveredCount = App\Models\Order::where('status', App\Models\Order::STATUS_DELIVERED)->count();
    $cancelledCount = App\Models\Order::where('status', App\Models\Order::STATUS_CANCELLED)->count();
    
    // Get orders with pagination
    $orders = $query->orderBy('created_at', 'desc')->paginate(10);
    
    return view('admin.orders.index', compact(
        'orders', 
        'waitingForPaymentCount',
        'processingCount', 
        'shippingCount',
        'deliveredCount', 
        'cancelledCount'
    ));
});

// Add this new route for testing order detail page
Route::get('/test-order/{order}', function (App\Models\Order $order) {
    // Load order relationships
    $order->load(['user']);
    
    // Get order items
    try {
        $orderItems = $order->getFormattedItems();
        
        // Add product information if available
        foreach ($orderItems as $key => $item) {
            if (isset($item['product_id']) && $item['product_id']) {
                $product = App\Models\Product::find($item['product_id']);
                if ($product) {
                    $orderItems[$key]['product'] = $product;
                }
            }
        }
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Error formatting order items: ' . $e->getMessage());
        $orderItems = [];
    }
    
    // Parse shipping address if it's stored as JSON
    $shippingAddress = $order->shipping_address;
    if (is_string($shippingAddress)) {
        try {
            $shippingAddress = json_decode($shippingAddress, true);
        } catch (\Exception $e) {
            // Keep as string if not valid JSON
        }
    }
    
    // Format payment details if available
    $paymentDetails = null;
    if ($order->payment_details) {
        try {
            $paymentDetails = is_string($order->payment_details) ? 
                json_decode($order->payment_details, true) : 
                $order->payment_details;
        } catch (\Exception $e) {
            // Keep as null if not valid JSON
        }
    }
    
    // Get user details
    $user = $order->user;
    $userData = null;
    if ($user) {
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'address' => $user->address ?? null,
            'created_at' => $user->created_at,
            'order_count' => App\Models\Order::where('user_id', $user->id)->count(),
        ];
    }
    
    return view('admin.orders.show', compact('order', 'shippingAddress', 'paymentDetails', 'userData', 'orderItems'));
});

// Add this route for quick admin login (for testing only)
Route::get('/test-login', function () {
    // Find the first admin user
    $admin = App\Models\Admin::first();
    
    if (!$admin) {
        // Create a default admin if none exists
        $admin = App\Models\Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
    }
    
    // Login as this admin
    auth()->guard('admin')->login($admin);
    
    return redirect()->route('admin.orders.index')->with('success', 'Logged in as admin for testing');
});
