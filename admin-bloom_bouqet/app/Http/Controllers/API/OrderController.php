<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\NotificationService;
use Carbon\Carbon;

class OrderController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService = null)
    {
        $this->notificationService = $notificationService ?? new NotificationService();
    }

    /**
     * Create a new order 
     */
    public function createOrder(Request $request)
    {
        try {
            Log::info('Starting order creation process');
            Log::info('Request data: ' . json_encode($request->all()));
            
            $validator = Validator::make($request->all(), [
                'id' => 'nullable|string',
                'order_id' => 'nullable|string',
                'items' => 'required|array',
                'deliveryAddress' => 'nullable|array',
                'shipping_address' => 'nullable|string',
                'subtotal' => 'nullable|numeric',
                'shippingCost' => 'nullable|numeric',
                'shipping_cost' => 'nullable|numeric',
                'total' => 'nullable|numeric',
                'total_amount' => 'nullable|numeric',
                'paymentMethod' => 'nullable|string',
                'payment_method' => 'nullable|string',
                'qrCodeUrl' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::error('Validation failed: ' . json_encode($validator->errors()));
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate a unique order ID with timestamp and random number
            $timestamp = time();
            $random = mt_rand(1000, 9999);
            $orderId = $request->id ?? $request->order_id ?? 'ORDER-' . $timestamp . $random;
            Log::info('Generated order ID: ' . $orderId);

            // Handle different formats for shipping address
            $shippingAddress = null;
            $phoneNumber = null;

            if ($request->has('deliveryAddress')) {
                $shippingAddress = is_array($request->deliveryAddress) 
                    ? json_encode($request->deliveryAddress)
                    : $request->deliveryAddress;
                $phoneNumber = is_array($request->deliveryAddress) && isset($request->deliveryAddress['phone']) 
                    ? $request->deliveryAddress['phone'] 
                    : null;
            } else if ($request->has('shipping_address')) {
                $shippingAddress = $request->shipping_address;
                $phoneNumber = $request->phone_number ?? null;
            }

            // Ensure we have valid shipping address and phone
            if (empty($shippingAddress)) {
                $shippingAddress = json_encode(['address' => 'No address provided']);
            }
            
            if (empty($phoneNumber)) {
                $phoneNumber = '000000000';
            }

            // Get the total amount from request
            $totalAmount = $request->total ?? $request->total_amount ?? 0;
            $shippingCost = $request->shippingCost ?? $request->shipping_cost ?? 0;
            $subtotal = $request->subtotal ?? ($totalAmount - $shippingCost);

            // Get the payment method from request
            $paymentMethod = $request->paymentMethod ?? $request->payment_method ?? 'unknown';
            
            Log::info('Order details prepared: ' . json_encode([
                'shipping_address' => $shippingAddress,
                'phone_number' => $phoneNumber,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
            ]));

            // Check stock availability first
            $insufficientItems = [];
            try {
                Log::info('Checking stock availability for items: ' . json_encode($request->items));
                
                foreach ($request->items as $item) {
                    $productId = $item['id'] ?? $item['product_id'] ?? null;
                    if (!$productId) {
                        Log::warning('Product ID not found in item: ' . json_encode($item));
                        continue;
                    }

                    // Try to find the product
                    $product = Product::find($productId);
                    
                    if (!$product) {
                        Log::warning('Product not found with ID: ' . $productId);
                        $insufficientItems[] = [
                            'product_id' => $productId,
                            'name' => $item['name'] ?? 'Unknown Product',
                            'quantity' => $item['quantity'] ?? 1,
                            'available' => 0,
                            'error' => 'Product not found'
                        ];
                        continue;
                    }
                    
                    // Log product details for debugging
                    Log::info('Product found: ID=' . $product->id . ', Name=' . $product->name . ', Stock=' . $product->stock);
                    
                    $requestedQuantity = $item['quantity'] ?? 1;
                    if (!$product->hasEnoughStock($requestedQuantity)) {
                        // Product has insufficient stock
                        $insufficientItems[] = [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'quantity' => $requestedQuantity,
                            'available' => $product->stock,
                        ];
                        Log::warning('Insufficient stock for product: ' . $product->name . ' (ID: ' . $product->id . '). Requested: ' . $requestedQuantity . ', Available: ' . $product->stock);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error during stock check: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to check stock availability. Please try again.',
                    'error_details' => $e->getMessage()
                ], 500);
            }
            
            if (count($insufficientItems) > 0) {
                // Some products have insufficient stock
                Log::warning('Insufficient stock for items: ' . json_encode($insufficientItems));
                return response()->json([
                    'success' => false,
                    'message' => 'Some products have insufficient stock',
                    'insufficient_items' => $insufficientItems,
                ], 400);
            }

            // Get user ID if authenticated
            $user = $request->user();
            $userId = $user ? $user->id : null;
            $userName = null;
            $userEmail = null;
            
            // If user is authenticated, get their name
            if ($user) {
                $userName = $user->name;
                $userEmail = $user->email;
                Log::info('Order being created for authenticated user: ' . $userName . ' (ID: ' . $userId . ')');
            } else {
                // Try to find user by email if provided in delivery address
                if (isset($request->deliveryAddress['email'])) {
                    $userEmail = $request->deliveryAddress['email'];
                    $existingUser = \App\Models\User::where('email', $userEmail)->first();
                    if ($existingUser) {
                        $userId = $existingUser->id;
                        $userName = $existingUser->name;
                        Log::info('Found existing user by email: ' . $userName . ' (ID: ' . $userId . ')');
                    }
                } else if (isset($request->email)) {
                    // Try with direct email field if provided
                    $userEmail = $request->email;
                    $existingUser = \App\Models\User::where('email', $userEmail)->first();
                    if ($existingUser) {
                        $userId = $existingUser->id;
                        $userName = $existingUser->name;
                        Log::info('Found existing user by direct email field: ' . $userName . ' (ID: ' . $userId . ')');
                    }
                }
                
                Log::info('Order being created for guest user' . ($userEmail ? ' with email: ' . $userEmail : ''));
            }
            
            Log::info('User ID for order: ' . ($userId ?? 'guest'));

            // Set payment deadline for QR payments (15 minutes from now)
            $paymentDeadline = Carbon::now()->addMinutes(15);
            if (strtolower($paymentMethod) === 'qris' || 
                strtolower($paymentMethod) === 'qr' ||
                strtolower($paymentMethod) === 'qr_code') {
                $paymentDeadline = Carbon::now()->addMinutes(15);
            } else if (strpos(strtolower($paymentMethod), 'va') !== false || 
                      in_array(strtolower($paymentMethod), ['bca', 'bni', 'bri', 'mandiri', 'permata'])) {
                // Untuk metode Virtual Account, juga beri batas waktu 1 jam
                $paymentDeadline = Carbon::now()->addHours(1);
            }

            // Check if payment_deadline is explicitly set in the request
            if ($request->has('payment_deadline') && !empty($request->payment_deadline)) {
                try {
                    $paymentDeadline = Carbon::parse($request->payment_deadline);
                } catch (\Exception $e) {
                    Log::warning('Invalid payment_deadline format in request: ' . $e->getMessage());
                    // Keep the default value
                }
            }

            Log::info('Creating order in database');
            
            // Create the order directly without disabling foreign key checks
            try {
                DB::beginTransaction();
                
                // Store order in database with fixed statuses using Eloquent model
                $order = new Order();
                $order->order_id = $orderId;
                $order->user_id = $userId; // null for guest orders
                $order->shipping_address = $shippingAddress;
                $order->phone_number = $phoneNumber;
                $order->subtotal = $subtotal;
                $order->shipping_cost = $shippingCost;
                $order->total_amount = $totalAmount;
                $order->payment_method = $paymentMethod;
                $order->status = 'waiting_for_payment';
                $order->payment_status = 'pending';
                $order->qr_code_url = $request->qrCodeUrl ?? null;
                $order->is_read = false;
                $order->payment_deadline = $paymentDeadline;
                
                $order->save();
                Log::info('Order created with ID: ' . $order->id . ' and order_id: ' . $order->order_id);

                // Store order items and update product stock
                foreach ($request->items as $item) {
                    $productId = $item['id'] ?? $item['product_id'] ?? null;
                    if (!$productId) continue;

                    $orderItem = new OrderItem();
                    $orderItem->order_id = $order->id;
                    $orderItem->product_id = $productId;
                    $orderItem->name = $item['name'] ?? 'Product';
                    $orderItem->price = $item['price'] ?? 0;
                    $orderItem->quantity = $item['quantity'] ?? 1;
                    $orderItem->save();
                    
                    // Update product stock
                    $product = Product::find($productId);
                    if ($product) {
                        $product->stock -= $orderItem->quantity;
                        $product->save();
                    }
                }
                
                DB::commit();
                Log::info('Order transaction committed successfully');
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Order Creation DB Error: ' . $e->getMessage());
                throw new \Exception('Database error when creating order: ' . $e->getMessage());
            }

            // Send notification for new order
            try {
                $this->notificationService->sendNewOrderNotification($order);
            } catch (\Exception $e) {
                Log::error('Failed to send order notification: ' . $e->getMessage());
                // Continue execution even if notification fails
            }

            // Get the created order with items
            $order->load('items');

            $orderData = [
                'id' => $order->order_id,
                'items' => $order->items,
                'deliveryAddress' => json_decode($order->shipping_address, true),
                'subtotal' => $order->subtotal,
                'shippingCost' => $order->shipping_cost,
                'total' => $order->total_amount,
                'paymentMethod' => $order->payment_method,
                'paymentStatus' => $order->payment_status,
                'orderStatus' => $order->status,
                'createdAt' => $order->created_at,
                'qrCodeUrl' => $order->qr_code_url,
                'paymentDeadline' => $order->payment_deadline,
            ];

            Log::info('Order created successfully: ' . json_encode($orderData));
            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $orderData
            ], 201);

        } catch (\Exception $e) {
            Log::error('Order Creation Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Try one more time with a simplified approach for guest orders
            try {
                Log::info('Trying simplified order creation as fallback');
                
                // First check stock again to make sure it's available
                $hasStockIssue = false;
                if (isset($request->items) && is_array($request->items) && count($request->items) > 0) {
                    foreach ($request->items as $item) {
                        $productId = $item['id'] ?? $item['product_id'] ?? null;
                        if (!$productId) continue;
                        
                        $product = Product::find($productId);
                        if (!$product || !$product->hasEnoughStock($item['quantity'] ?? 1)) {
                            $hasStockIssue = true;
                            Log::warning('Stock issue detected in fallback: Product ID=' . $productId);
                            break;
                        }
                    }
                }
                
                if ($hasStockIssue) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock check failed. Please refresh and try again.',
                    ], 400);
                }
                
                // Generate a completely new order ID to avoid duplicates
                $timestamp = time();
                $random = mt_rand(10000, 99999);
                $orderId = 'ORDER-' . $timestamp . $random;
                
                DB::beginTransaction();
                
                $order = new Order();
                $order->order_id = $orderId;
                $order->user_id = null; // Always guest for fallback
                $order->shipping_address = $shippingAddress ?? json_encode(['address' => 'No address provided']);
                $order->phone_number = $phoneNumber ?? '000000000';
                $order->subtotal = $subtotal ?? 0;
                $order->shipping_cost = $shippingCost ?? 0;
                $order->total_amount = $totalAmount ?? 0;
                $order->payment_method = $paymentMethod ?? 'unknown';
                $order->status = 'waiting_for_payment';
                $order->payment_status = 'pending';
                $order->is_read = false;
                $order->payment_deadline = Carbon::now()->addMinutes(15);
                $order->save();
                
                // Add at least one order item if we have items
                if (isset($request->items) && is_array($request->items) && count($request->items) > 0) {
                    $item = $request->items[0];
                    $productId = $item['id'] ?? $item['product_id'] ?? null;
                    
                    if ($productId) {
                        $orderItem = new OrderItem();
                        $orderItem->order_id = $order->id;
                        $orderItem->product_id = $productId;
                        $orderItem->name = $item['name'] ?? 'Product';
                        $orderItem->price = $item['price'] ?? 0;
                        $orderItem->quantity = $item['quantity'] ?? 1;
                        $orderItem->save();
                    }
                }
                
                DB::commit();
                
                // Return success response
                Log::info('Fallback order created successfully with ID: ' . $orderId);
                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully (fallback method)',
                    'data' => [
                        'id' => $order->order_id,
                        'subtotal' => $order->subtotal,
                        'shippingCost' => $order->shipping_cost,
                        'total' => $order->total_amount,
                        'paymentMethod' => $order->payment_method,
                        'paymentStatus' => $order->payment_status,
                        'orderStatus' => $order->status,
                        'createdAt' => $order->created_at,
                        'paymentDeadline' => $order->payment_deadline,
                    ]
                ], 201);
                
            } catch (\Exception $innerEx) {
                DB::rollBack();
                Log::error('Fallback order creation failed: ' . $innerEx->getMessage());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order after multiple attempts. Please try again later.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $orderId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string',
                'payment_status' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find the order using Eloquent
            $order = Order::where('order_id', $orderId)->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Save old status for notification
            $oldStatus = $order->status;
            $oldPaymentStatus = $order->payment_status;

            // Update order status
            $order->status = $request->status;
            $order->payment_status = $request->payment_status;
            $order->save();

            // Send notifications for status changes
            if ($oldStatus !== $request->status) {
                $this->notificationService->sendOrderStatusNotification($order, $oldStatus, $request->status);
            }
            
            if ($oldPaymentStatus !== $request->payment_status) {
                $this->notificationService->sendPaymentStatusNotification($order, $oldPaymentStatus, $request->payment_status);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Order Update Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order details
     */
    public function getOrder($orderId)
    {
        try {
            // Get order details using Eloquent with eager loading for user and items
            $order = Order::with(['items', 'user'])
                ->where('order_id', $orderId)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Get user information
            $userData = null;
            if ($order->user) {
                $userData = [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                    'phone' => $order->user->phone ?? null,
                    'address' => $order->user->address ?? null,
                    'created_at' => $order->user->created_at,
                    'order_count' => Order::where('user_id', $order->user->id)->count(),
                ];
            }

            // Format items with product details
            $formattedItems = [];
            foreach ($order->items as $item) {
                $product = null;
                if ($item->product_id) {
                    $product = Product::find($item->product_id);
                }
                
                $formattedItems[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->price * $item->quantity,
                    'product_id' => $item->product_id,
                    'product_image' => $product ? $product->main_image : null,
                ];
            }

            // Parse shipping address
            $shippingAddress = $order->shipping_address;
            if (is_string($shippingAddress)) {
                try {
                    $shippingAddress = json_decode($shippingAddress, true);
                } catch (\Exception $e) {
                    // Keep as string if not valid JSON
                }
            }

            // Format payment details
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

            $orderData = [
                'id' => $order->id,
                'order_id' => $order->order_id,
                'user' => $userData,
                'shipping_address' => $shippingAddress,
                'phone_number' => $order->phone_number,
                'total_amount' => $order->total_amount,
                'subtotal' => $order->subtotal,
                'shipping_cost' => $order->shipping_cost,
                'payment_method' => $order->payment_method,
                'status' => $order->status,
                'status_label' => $order->getStatusLabelAttribute(),
                'payment_status' => $order->payment_status,
                'payment_status_label' => $order->getPaymentStatusLabelAttribute(),
                'created_at' => $order->created_at,
                'payment_deadline' => $order->payment_deadline,
                'paid_at' => $order->paid_at,
                'shipped_at' => $order->shipped_at,
                'delivered_at' => $order->delivered_at,
                'cancelled_at' => $order->cancelled_at,
                'qr_code_url' => $order->qr_code_url,
                'payment_details' => $paymentDetails,
                'items' => $formattedItems,
                'total_items' => count($formattedItems),
            ];

            return response()->json([
                'success' => true,
                'data' => $orderData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Order Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user orders
     */
    public function getUserOrders(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            // Get user orders using Eloquent with eager loading
            $orders = Order::with(['items.product'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
                
            Log::info('Found ' . count($orders) . ' orders for user ID: ' . $user->id);

            // Transform the data for the response
            $ordersWithItems = $orders->map(function ($order) {
                // Format items with product details
                $formattedItems = [];
                foreach ($order->items as $item) {
                    $product = $item->product;
                    
                    $formattedItems[] = [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->price * $item->quantity,
                        'product_id' => $item->product_id,
                        'product_image' => $product ? $product->main_image : null,
                    ];
                }
                
                // Parse shipping address
                $shippingAddress = $order->shipping_address;
                if (is_string($shippingAddress)) {
                    try {
                        $shippingAddress = json_decode($shippingAddress, true);
                    } catch (\Exception $e) {
                        // Keep as string if not valid JSON
                    }
                }
                
                return [
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'shipping_address' => $shippingAddress,
                    'phone_number' => $order->phone_number,
                    'total_amount' => $order->total_amount,
                    'subtotal' => $order->subtotal,
                    'shipping_cost' => $order->shipping_cost,
                    'payment_method' => $order->payment_method,
                    'status' => $order->status,
                    'status_label' => $order->getStatusLabelAttribute(),
                    'payment_status' => $order->payment_status,
                    'payment_status_label' => $order->getPaymentStatusLabelAttribute(),
                    'created_at' => $order->created_at,
                    'payment_deadline' => $order->payment_deadline,
                    'qr_code_url' => $order->qr_code_url,
                    'items' => $formattedItems,
                    'total_items' => count($formattedItems),
                    'can_cancel' => $order->status === 'waiting_for_payment' && $order->payment_status === 'pending',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $ordersWithItems,
                'count' => count($ordersWithItems),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get User Orders Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user orders: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check stock availability for products before placing an order
     */
    public function checkStockAvailability(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            Log::info('Checking stock availability for items: ' . json_encode($request->items));
            
            $insufficientItems = [];
            $availableItems = [];
            
            foreach ($request->items as $item) {
                $productId = $item['id'] ?? $item['product_id'] ?? null;
                if (!$productId) {
                    Log::warning('Product ID not found in item: ' . json_encode($item));
                    continue;
                }

                // Try to find the product
                $product = Product::find($productId);
                
                if (!$product) {
                    Log::warning('Product not found with ID: ' . $productId);
                    $insufficientItems[] = [
                        'product_id' => $productId,
                        'name' => $item['name'] ?? 'Unknown Product',
                        'quantity' => $item['quantity'] ?? 1,
                        'available' => 0,
                        'error' => 'Product not found'
                    ];
                    continue;
                }
                
                // Log product details for debugging
                Log::info('Product found: ID=' . $product->id . ', Name=' . $product->name . ', Stock=' . $product->stock);
                
                $requestedQuantity = $item['quantity'] ?? 1;
                if (!$product->hasEnoughStock($requestedQuantity)) {
                    // Product has insufficient stock
                    $insufficientItems[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'quantity' => $requestedQuantity,
                        'available' => $product->stock,
                    ];
                    Log::warning('Insufficient stock for product: ' . $product->name . ' (ID: ' . $product->id . '). Requested: ' . $requestedQuantity . ', Available: ' . $product->stock);
                } else {
                    $availableItems[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'quantity' => $requestedQuantity,
                        'available' => $product->stock,
                    ];
                }
            }
            
            if (count($insufficientItems) > 0) {
                // Some products have insufficient stock
                Log::warning('Insufficient stock for items: ' . json_encode($insufficientItems));
                return response()->json([
                    'success' => false,
                    'message' => 'Some products have insufficient stock',
                    'insufficient_items' => $insufficientItems,
                    'available_items' => $availableItems
                ], 400);
            }
            
            // All products have sufficient stock
            return response()->json([
                'success' => true,
                'message' => 'All products have sufficient stock',
                'available_items' => $availableItems
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error during stock check: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check stock availability. Please try again.',
                'error_details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orders by user ID
     */
    public function getOrdersByUserId($userId)
    {
        try {
            // Verify user exists
            $user = \App\Models\User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Get user orders using Eloquent with eager loading
            $orders = Order::with(['items.product'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Transform the data for the response
            $ordersWithItems = $orders->map(function ($order) {
                // Format items with product details
                $formattedItems = [];
                foreach ($order->items as $item) {
                    $product = $item->product;
                    
                    $formattedItems[] = [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->price * $item->quantity,
                        'product_id' => $item->product_id,
                        'product_image' => $product ? $product->main_image : null,
                    ];
                }
                
                // Parse shipping address
                $shippingAddress = $order->shipping_address;
                if (is_string($shippingAddress)) {
                    try {
                        $shippingAddress = json_decode($shippingAddress, true);
                    } catch (\Exception $e) {
                        // Keep as string if not valid JSON
                    }
                }
                
                return [
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'shipping_address' => $shippingAddress,
                    'phone_number' => $order->phone_number,
                    'total_amount' => $order->total_amount,
                    'subtotal' => $order->subtotal,
                    'shipping_cost' => $order->shipping_cost,
                    'payment_method' => $order->payment_method,
                    'status' => $order->status,
                    'status_label' => $order->getStatusLabelAttribute(),
                    'payment_status' => $order->payment_status,
                    'payment_status_label' => $order->getPaymentStatusLabelAttribute(),
                    'created_at' => $order->created_at,
                    'payment_deadline' => $order->payment_deadline,
                    'qr_code_url' => $order->qr_code_url,
                    'items' => $formattedItems,
                    'total_items' => count($formattedItems),
                ];
            });

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'data' => $ordersWithItems,
                'total_orders' => count($ordersWithItems),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get User Orders Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user orders: ' . $e->getMessage(),
            ], 500);
        }
    }
} 