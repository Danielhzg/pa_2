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
use App\Services\WebSocketService;
use Carbon\Carbon;  
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService = null)
    {
        if ($notificationService) {
            $this->notificationService = $notificationService;
        } else {
            // Create WebSocketService first, then pass it to NotificationService
            $webSocketService = new WebSocketService();
            $this->notificationService = new NotificationService($webSocketService);
        }
    }

    /**
     * Create a new order 
     */
    public function createOrder(Request $request)
    {
        try {
            Log::info('Starting order creation process');
            Log::info('Request data: ' . json_encode($request->all()));
            
            // Check for authorization header
            $authHeader = $request->header('Authorization');
            Log::info('Authorization header present: ' . ($authHeader ? 'Yes' : 'No'));
            
            // Log token details if present
            $authenticatedUser = null;
            if ($authHeader) {
                $token = str_replace('Bearer ', '', $authHeader);
                Log::info('Token received: ' . substr($token, 0, 10) . '...');
                
                // Check if token is valid
                try {
                    $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                    if ($personalAccessToken) {
                        $authenticatedUser = $personalAccessToken->tokenable;
                        Log::info('Token is valid, user ID: ' . $authenticatedUser->id);
                    } else {
                        Log::warning('Invalid token received');
                    }
                } catch (\Exception $e) {
                    Log::warning('Error validating token: ' . $e->getMessage());
                    // Continue without authenticated user
                }
            }

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
                'user_id' => 'nullable', // Allow explicit user_id in request
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
            $orderId = $request->id ?? $request->order_id ?? 'ORDER-' . $timestamp . '-' . $random;
            Log::info('Generated order ID: ' . $orderId);

            // Cek apakah order dengan ID tersebut sudah ada
            $existingOrder = Order::where('order_id', $orderId)->first();
            if ($existingOrder) {
                // Jika order sudah ada dan masih dalam status waiting_for_payment, kembalikan data order tersebut
                if ($existingOrder->status === 'waiting_for_payment' && $existingOrder->payment_status === 'pending') {
                    Log::info("Order dengan ID {$orderId} sudah ada dan masih pending, mengembalikan data order yang ada");
                    
                    // Ambil data items dengan aman
                    $orderItems = [];
                    try {
                        $orderItems = $existingOrder->getItemsCollection();
                    } catch (\Exception $e) {
                        Log::error('Error getting order items: ' . $e->getMessage());
                        // Gunakan data dari request sebagai fallback
                        $orderItems = collect($request->items);
                    }
                    
                    $orderData = [
                        'id' => $existingOrder->order_id,
                        'items' => $orderItems,
                        'deliveryAddress' => json_decode($existingOrder->shipping_address, true),
                        'subtotal' => $existingOrder->subtotal,
                        'shippingCost' => $existingOrder->shipping_cost,
                        'total' => $existingOrder->total_amount,
                        'paymentMethod' => $existingOrder->payment_method,
                        'paymentStatus' => $existingOrder->payment_status,
                        'orderStatus' => $existingOrder->status,
                        'createdAt' => $existingOrder->created_at,
                        'paymentDeadline' => $existingOrder->payment_deadline,
                        'is_duplicate' => true
                    ];
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Order already exists',
                        'data' => $orderData
                    ], 200);
                } else {
                    // Jika order sudah ada tapi status berbeda, buat order ID baru
                    $orderId = 'ORDER-' . $timestamp . '-' . $random . '-' . Str::random(4);
                    Log::info("Order dengan ID tersebut sudah ada dengan status berbeda, membuat order ID baru: {$orderId}");
                }
            }

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

            // Determine user ID in this priority order:
            // 1. Authenticated user from token
            // 2. Explicit user_id in request
            // 3. Email lookup
            $userId = null;
            $userName = null;
            $userEmail = null;
            
            // 1. First use authenticated user if available (from token validation above)
            if ($authenticatedUser) {
                $userId = $authenticatedUser->id;
                $userName = $authenticatedUser->name ?? $authenticatedUser->full_name ?? $authenticatedUser->username;
                $userEmail = $authenticatedUser->email;
                Log::info('Using authenticated user from token: ' . ($userName ?? 'Unknown') . ' (ID: ' . $userId . ')');
            } 
            // 2. If no authenticated user, try to get user ID from the request body
            else if ($request->has('user_id') && !empty($request->user_id)) {
                $userId = $request->user_id;
                $existingUser = \App\Models\User::find($userId);
                
                if ($existingUser) {
                    $userName = $existingUser->name ?? $existingUser->full_name ?? $existingUser->username;
                    $userEmail = $existingUser->email;
                    Log::info('Using user ID from request body: ' . ($userName ?? 'Unknown') . ' (ID: ' . $userId . ')');
                } else {
                    Log::warning('Invalid user_id provided in request: ' . $userId);
                    $userId = null;
                }
            }
            // 3. Try to find user by email if provided in delivery address
            else if (isset($request->deliveryAddress['email'])) {
                $userEmail = $request->deliveryAddress['email'];
                $existingUser = \App\Models\User::where('email', $userEmail)->first();
                if ($existingUser) {
                    $userId = $existingUser->id;
                    $userName = $existingUser->name ?? $existingUser->full_name ?? $existingUser->username;
                    Log::info('Found existing user by email in deliveryAddress: ' . ($userName ?? 'Unknown') . ' (ID: ' . $userId . ')');
                }
            } 
            // 4. Try with direct email field if provided
            else if (isset($request->email)) {
                $userEmail = $request->email;
                $existingUser = \App\Models\User::where('email', $userEmail)->first();
                if ($existingUser) {
                    $userId = $existingUser->id;
                    $userName = $existingUser->name ?? $existingUser->full_name ?? $existingUser->username;
                    Log::info('Found existing user by direct email field: ' . ($userName ?? 'Unknown') . ' (ID: ' . $userId . ')');
                }
            }
            
            if ($userId) {
                Log::info('Order will be created with user_id: ' . $userId);
            } else {
                Log::info('Order being created for guest user' . ($userEmail ? ' with email: ' . $userEmail : ''));
            }

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
            
            // Create the order directly with transaction handling
            try {
                DB::beginTransaction();
                
                // Store order in database with fixed statuses using Eloquent model
                $order = new Order();
                $order->order_id = $orderId;
                
                // Make sure user_id is an integer or null
                if ($userId !== null) {
                    try {
                        // Cast to integer explicitly 
                        $userId = (int)$userId;
                        // Validate that it's a positive number
                        if ($userId <= 0) {
                            Log::warning('Invalid user_id (not a positive integer), setting to null');
                            $userId = null;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to convert user_id to integer: ' . $e->getMessage());
                        $userId = null;
                    }
                }
                
                $order->user_id = $userId; // Will be null for guest orders
                
                // Log the user_id being set
                Log::info('Setting user_id in order model: ' . ($userId ?? 'null (guest order)'));
                
                $order->shipping_address = $shippingAddress;
                $order->phone_number = $phoneNumber;
                $order->subtotal = $subtotal;
                $order->shipping_cost = $shippingCost;
                $order->total_amount = $totalAmount;
                $order->payment_method = $paymentMethod;
                $order->status = 'waiting_for_payment';
                $order->payment_status = 'pending';
                $order->is_read = false;
                $order->payment_deadline = $paymentDeadline;
                
                try {
                    $order->save();
                    Log::info('Order created with ID: ' . $order->id . ' and order_id: ' . $order->order_id . ', user_id: ' . ($order->user_id ?? 'guest'));
                } catch (\Exception $saveEx) {
                    Log::error('Order save error: ' . $saveEx->getMessage());
                    Log::error('SQL: ' . $saveEx->getTraceAsString());
                    throw $saveEx;
                }

                // Store order items in JSON format directly in order_items column
                $orderItemsArray = [];
                
                foreach ($request->items as $item) {
                    $productId = $item['id'] ?? $item['product_id'] ?? null;
                    if (!$productId) continue;

                    try {
                        // Cast product ID to integer
                        $productId = (int)$productId;
                        
                        // Create item array
                        $orderItemData = [
                            'id' => mt_rand(10000, 99999),
                            'product_id' => $productId,
                            'name' => $item['name'] ?? 'Product',
                            'price' => $item['price'] ?? 0,
                            'quantity' => $item['quantity'] ?? 1,
                            'subtotal' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1),
                            'created_at' => now()->toDateTimeString(),
                            'updated_at' => now()->toDateTimeString(),
                        ];
                        
                        // Add to items array
                        $orderItemsArray[] = $orderItemData;
                        
                        // Update product stock
                        $product = Product::find($productId);
                        if ($product) {
                            // Gunakan metode reduceStock yang baru
                            if (!$product->reduceStock($item['quantity'] ?? 1)) {
                                Log::warning("Failed to reduce stock for product ID: $productId. Insufficient stock.");
                            }
                        } else {
                            Log::warning("Product with ID $productId not found when creating order");
                        }
                    } catch (\Exception $itemEx) {
                        Log::error('Order item processing error: ' . $itemEx->getMessage());
                        Log::error('Item data: ' . json_encode($item));
                        // Continue with other items instead of failing the whole order
                    }
                }
                
                // Store the order items array as JSON
                $order->order_items = $orderItemsArray;
                $order->save();
                
                DB::commit();
                Log::info('Order transaction committed successfully');
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Order Creation DB Error: ' . $e->getMessage());
                Log::error('SQL Error: ' . $e->getTraceAsString());
                
                // Check for specific error types to provide better error messages
                $errorMessage = 'Database error when creating order';
                
                // Try to determine the type of error based on error message
                if (strpos($e->getMessage(), 'user_id') !== false || 
                    strpos($e->getMessage(), 'foreign key constraint') !== false) {
                    
                    Log::info('Detected user_id or foreign key constraint issue. Attempting guest order.');
                    // Try creating the order with user_id set to NULL
                    return $this->createOrderAsGuest($request, $orderId, $shippingAddress, $phoneNumber, 
                                                     $subtotal, $shippingCost, $totalAmount, $paymentMethod, 
                                                     $paymentDeadline);
                } else if (strpos($e->getMessage(), 'order_items') !== false) {
                    $errorMessage = 'Error with order items table';
                } else if (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                    $errorMessage = 'Data integrity error - Please check your input data';
                } else if (strpos($e->getMessage(), 'Connection refused') !== false || 
                           strpos($e->getMessage(), 'timeout') !== false) {
                    $errorMessage = 'Database connection error - Please try again later';
                }
                
                return response()->json([
                    'success' => false, 
                    'message' => $errorMessage,
                    'error_details' => $e->getMessage()
                ], 500);
            }

            // Send notification for new order
            try {
                if ($this->notificationService) {
                    Log::info('Attempting to send order notification for order ID: ' . $order->id);
                    $this->notificationService->sendNewOrderNotification($order);
                    Log::info('Order notification sent successfully');
                } else {
                    Log::warning('NotificationService not available, skipping notification');
                }
            } catch (\Exception $e) {
                Log::error('Failed to send order notification: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue execution even if notification fails
            }

            // Log order data for debugging
            Log::info('Created order details', [
                'order_id' => $order->id,
                'order_number' => $order->order_id,
                'user_id' => $order->user_id,
                'items_count' => count($order->getItemsCollection()),
                'total' => $order->total_amount,
                'status' => $order->status,
                'payment_status' => $order->payment_status
            ]);

            // Get the created order with items
            $orderItems = $order->getItemsCollection();

            $orderData = [
                'id' => $order->order_id,
                'items' => $orderItems,
                'deliveryAddress' => json_decode($order->shipping_address, true),
                'subtotal' => $order->subtotal,
                'shippingCost' => $order->shipping_cost,
                'total' => $order->total_amount,
                'paymentMethod' => $order->payment_method,
                'paymentStatus' => $order->payment_status,
                'orderStatus' => $order->status,
                'createdAt' => $order->created_at,
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
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fallback method to create order as guest when user_id causes problems
     */
    private function createOrderAsGuest(Request $request, $orderId, $shippingAddress, $phoneNumber, 
                                      $subtotal, $shippingCost, $totalAmount, $paymentMethod, 
                                      $paymentDeadline)
    {
        Log::info('Attempting to create order as guest after user_id error');
        
        try {
            DB::beginTransaction();
            
            $order = new Order();
            $order->order_id = $orderId . '-guest';
            $order->user_id = null; // Explicitly set as null for guest
            $order->shipping_address = $shippingAddress;
            $order->phone_number = $phoneNumber;
            $order->subtotal = $subtotal;
            $order->shipping_cost = $shippingCost;
            $order->total_amount = $totalAmount;
            $order->payment_method = $paymentMethod;
            $order->status = 'waiting_for_payment';
            $order->payment_status = 'pending';
            $order->is_read = false;
            $order->payment_deadline = $paymentDeadline;
            
            // Add additional checks to ensure we don't have NULL values in critical fields
            if (empty($order->shipping_address)) {
                $order->shipping_address = json_encode(['address' => 'No address provided']);
            }
            
            if (empty($order->phone_number)) {
                $order->phone_number = '000000000';
            }
            
            // Ensure numeric values are valid
            $order->subtotal = max(0, (float)$order->subtotal);
            $order->shipping_cost = max(0, (float)$order->shipping_cost);
            $order->total_amount = max(0, (float)$order->total_amount);
            
            // Save the order
            $order->save();
            Log::info('Guest order created with ID: ' . $order->id . ' and order_id: ' . $order->order_id);
            
            // Store order items
            $orderItemsArray = [];
            
            foreach ($request->items as $item) {
                $productId = $item['id'] ?? $item['product_id'] ?? null;
                if (!$productId) continue;

                // Create item array
                $orderItemData = [
                    'id' => mt_rand(10000, 99999),
                    'product_id' => (int)$productId,
                    'name' => $item['name'] ?? 'Product',
                    'price' => $item['price'] ?? 0,
                    'quantity' => $item['quantity'] ?? 1,
                    'subtotal' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1),
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ];
                
                // Add to items array
                $orderItemsArray[] = $orderItemData;
                    
                // Update product stock (handled in a try-catch to avoid failure)
                try {
                    $product = Product::find($productId);
                    if ($product) {
                        $product->reduceStock($item['quantity'] ?? 1);
                    }
                } catch (\Exception $stockEx) {
                    Log::warning('Failed to update product stock: ' . $stockEx->getMessage());
                    // Continue with order creation even if stock update fails
                }
            }
            
            // Store the order items array as JSON
            $order->order_items = $orderItemsArray;
            $order->save();
            
            DB::commit();
            Log::info('Guest order transaction committed successfully');
            
            // Get the created order with items
            $orderItems = $order->getItemsCollection();

            $orderData = [
                'id' => $order->order_id,
                'items' => $orderItems,
                'deliveryAddress' => json_decode($order->shipping_address, true),
                'subtotal' => $order->subtotal,
                'shippingCost' => $order->shipping_cost,
                'total' => $order->total_amount,
                'paymentMethod' => $order->payment_method,
                'paymentStatus' => $order->payment_status,
                'orderStatus' => $order->status,
                'createdAt' => $order->created_at,
                'paymentDeadline' => $order->payment_deadline,
                'is_guest' => true
            ];

            Log::info('Guest order created successfully: ' . json_encode($orderData));
            return response()->json([
                'success' => true,
                'message' => 'Order created successfully as guest',
                'data' => $orderData
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Guest Order Creation Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create guest order: ' . $e->getMessage(),
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
            // Get order details using Eloquent with eager loading for user
            $order = Order::with(['user'])
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
            try {
                $orderItems = $order->getItemsCollection();
                
                foreach ($orderItems as $item) {
                    $product = null;
                    if (isset($item->product_id)) {
                        $product = Product::find($item->product_id);
                    }
                    
                    $formattedItems[] = [
                        'id' => $item->id ?? null,
                        'name' => $item->name ?? 'Unknown Product',
                        'price' => $item->price ?? 0,
                        'quantity' => $item->quantity ?? 1,
                        'subtotal' => ($item->price ?? 0) * ($item->quantity ?? 1),
                        'product_id' => $item->product_id ?? null,
                        'product_image' => $product ? $product->main_image : null,
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error formatting order items: ' . $e->getMessage());
                
                // Fallback: try to get items from order_items JSON
                if ($order->order_items && is_array($order->order_items)) {
                    foreach ($order->order_items as $item) {
                        $product = null;
                        if (isset($item['product_id'])) {
                            $product = Product::find($item['product_id']);
                        }
                        
                        $formattedItems[] = [
                            'id' => $item['id'] ?? null,
                            'name' => $item['name'] ?? 'Unknown Product',
                            'price' => $item['price'] ?? 0,
                            'quantity' => $item['quantity'] ?? 1,
                            'subtotal' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1),
                            'product_id' => $item['product_id'] ?? null,
                            'product_image' => $product ? $product->main_image : null,
                        ];
                    }
                }
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
                'payment_details' => $paymentDetails,
                'items' => $formattedItems,
                'total_items' => count($formattedItems),
            ];

            return response()->json([
                'success' => true,
                'data' => $orderData,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting order: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed order information for both mobile app and admin panel
     *
     * @param string $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderDetails($orderId)
    {
        try {
            // Support multiple ID formats - either a numeric ID or a string order_id
            $order = Order::with(['user'])
                ->where(function($query) use ($orderId) {
                    $query->where('id', $orderId)
                          ->orWhere('order_id', $orderId);
                })
                ->first();

            if (!$order) {
                Log::error("Order not found with ID: $orderId");
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Get detailed user information
            $userData = null;
            if ($order->user) {
                $userData = [
                    'id' => $order->user->id,
                    'name' => $order->user->name ?? $order->user->full_name ?? $order->user->username ?? 'Guest',
                    'email' => $order->user->email,
                    'phone' => $order->user->phone ?? $order->phone_number ?? null,
                    'address' => $order->user->address ?? null,
                    'profile_image' => $order->user->profile_image ?? null,
                    'created_at' => $order->user->created_at,
                    'order_count' => Order::where('user_id', $order->user->id)->count(),
                ];
            }

            // Format items with product details
            $formattedItems = [];
            try {
                $orderItems = method_exists($order, 'getFormattedItems') 
                    ? $order->getFormattedItems() 
                    : $order->getItemsCollection();
                
                foreach ($orderItems as $item) {
                    $product = null;
                    $productId = isset($item['product_id']) ? $item['product_id'] : (isset($item->product_id) ? $item->product_id : null);
                    
                    if ($productId) {
                        $product = Product::find($productId);
                    }
                    
                    $formattedItems[] = [
                        'id' => isset($item['id']) ? $item['id'] : (isset($item->id) ? $item->id : null),
                        'name' => isset($item['name']) ? $item['name'] : (isset($item->name) ? $item->name : 'Unknown Product'),
                        'price' => isset($item['price']) ? $item['price'] : (isset($item->price) ? $item->price : 0),
                        'quantity' => isset($item['quantity']) ? $item['quantity'] : (isset($item->quantity) ? $item->quantity : 1),
                        'subtotal' => (isset($item['price']) ? $item['price'] : (isset($item->price) ? $item->price : 0)) * 
                                     (isset($item['quantity']) ? $item['quantity'] : (isset($item->quantity) ? $item->quantity : 1)),
                        'product_id' => $productId,
                        'product_image' => $product ? $product->main_image : null,
                        'image_url' => $product ? (isset($product->main_image) ? asset('storage/' . $product->main_image) : null) : null,
                        'product' => $product,
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error formatting order items: ' . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Fallback: try to get items from order_items JSON
                if ($order->order_items) {
                    $items = is_string($order->order_items) ? json_decode($order->order_items, true) : $order->order_items;
                    
                    if (is_array($items)) {
                        foreach ($items as $item) {
                        $product = null;
                        if (isset($item['product_id'])) {
                            $product = Product::find($item['product_id']);
                        }
                        
                        $formattedItems[] = [
                            'id' => $item['id'] ?? null,
                            'name' => $item['name'] ?? 'Unknown Product',
                            'price' => $item['price'] ?? 0,
                            'quantity' => $item['quantity'] ?? 1,
                            'subtotal' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1),
                            'product_id' => $item['product_id'] ?? null,
                            'product_image' => $product ? $product->main_image : null,
                                'image_url' => $product ? (isset($product->main_image) ? asset('storage/' . $product->main_image) : null) : null,
                                'product' => $product,
                        ];
                        }
                    }
                }
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
            
            // Get shipping information
            $shippingInfo = [
                'courier_name' => $order->courier_name ?? null,
                'tracking_number' => $order->tracking_number ?? null,
                'shipped_at' => $order->shipped_at,
                'estimated_delivery' => $order->estimated_delivery ?? null,
            ];
            
            // Status history
            $statusHistory = [];
            if (method_exists($order, 'statusHistory') && $order->statusHistory) {
                $statusHistory = $order->statusHistory()->orderBy('created_at', 'desc')->get();
            } else {
                // Create simple status history from timestamps
                if ($order->created_at) {
                    $statusHistory[] = [
                        'status' => 'created',
                        'label' => 'Pesanan Dibuat',
                        'timestamp' => $order->created_at->format('Y-m-d H:i:s'),
                        'created_at' => $order->created_at,
                        'updated_by' => 'system'
                    ];
                }
                
                if ($order->paid_at) {
                    $statusHistory[] = [
                        'status' => 'paid',
                        'label' => 'Pembayaran Berhasil',
                        'timestamp' => $order->paid_at->format('Y-m-d H:i:s'),
                        'created_at' => $order->paid_at,
                        'updated_by' => 'payment_system'
                    ];
                }
                
                if ($order->status === 'processing' && $order->status_updated_at) {
                    $statusHistory[] = [
                        'status' => 'processing',
                        'label' => 'Pesanan Diproses',
                        'timestamp' => $order->status_updated_at->format('Y-m-d H:i:s'),
                        'created_at' => $order->status_updated_at,
                        'updated_by' => $order->status_updated_by ?? 'system'
                    ];
                }
                
                if ($order->shipped_at) {
                    $statusHistory[] = [
                        'status' => 'shipping',
                        'label' => 'Pesanan Dikirim',
                        'timestamp' => $order->shipped_at->format('Y-m-d H:i:s'),
                        'created_at' => $order->shipped_at,
                        'updated_by' => $order->status_updated_by ?? 'system'
                    ];
                }
                
                if ($order->delivered_at) {
                    $statusHistory[] = [
                        'status' => 'delivered',
                        'label' => 'Pesanan Selesai',
                        'timestamp' => $order->delivered_at->format('Y-m-d H:i:s'),
                        'created_at' => $order->delivered_at,
                        'updated_by' => $order->status_updated_by ?? 'system'
                    ];
                }
                
                if ($order->cancelled_at) {
                    $statusHistory[] = [
                        'status' => 'cancelled',
                        'label' => 'Pesanan Dibatalkan',
                        'timestamp' => $order->cancelled_at->format('Y-m-d H:i:s'),
                        'created_at' => $order->cancelled_at,
                        'updated_by' => $order->status_updated_by ?? 'system'
                    ];
                }
            }

            $orderData = [
                'id' => $order->id,
                'order_id' => $order->order_id,
                'user_id' => $order->user_id,
                'customer' => $userData,
                'shipping_address' => $shippingAddress,
                'phone_number' => $order->phone_number,
                'total_amount' => $order->total_amount,
                'subtotal' => $order->subtotal,
                'shipping_cost' => $order->shipping_cost,
                'payment_method' => $order->payment_method,
                'status' => $order->status,
                'status_label' => method_exists($order, 'getStatusLabelAttribute') ? $order->getStatusLabelAttribute() : $order->status_label,
                'payment_status' => $order->payment_status,
                'payment_status_label' => method_exists($order, 'getPaymentStatusLabelAttribute') ? $order->getPaymentStatusLabelAttribute() : $order->payment_status_label,
                'created_at' => $order->created_at,
                'created_at_formatted' => $order->created_at->format('d M Y H:i'),
                'payment_deadline' => $order->payment_deadline,
                'paid_at' => $order->paid_at,
                'shipped_at' => $order->shipped_at,
                'delivered_at' => $order->delivered_at,
                'cancelled_at' => $order->cancelled_at,
                'status_updated_at' => $order->status_updated_at,
                'payment_details' => $paymentDetails,
                'items' => $formattedItems,
                'total_items' => count($formattedItems),
                'notes' => $order->notes,
                'customer_notes' => $order->customer_notes,
                'shipping_info' => $shippingInfo,
                'status_history' => $statusHistory,
            ];

            return response()->json([
                'success' => true,
                'data' => $orderData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Order Details Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'order_id' => $orderId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get order details: ' . $e->getMessage(),
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
                Log::warning('User not authenticated when trying to access orders');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            Log::info('Getting orders for user: ' . $user->id . ' (' . $user->name . ', ' . $user->email . ')');

            // Get user orders using Eloquent with explicit columns to avoid any issues
            $orders = Order::select('id', 'order_id', 'user_id', 'shipping_address', 'phone_number', 
                                  'subtotal', 'shipping_cost', 'total_amount', 'payment_method', 
                                  'status', 'payment_status', 'created_at', 'payment_deadline', 'order_items')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
                
            Log::info('Found ' . count($orders) . ' orders for user ID: ' . $user->id);

            // Transform the data for the response
            $ordersWithItems = $orders->map(function ($order) {
                Log::info('Processing order: ' . $order->id . ' (' . $order->order_id . ')');
                
                // Format items with product details
                $formattedItems = [];
                
                try {
                    $orderItems = $order->getItemsCollection();
                    Log::info('Order ' . $order->id . ' has ' . count($orderItems) . ' items');
                    
                    foreach ($orderItems as $item) {
                        $product = null;
                        if (isset($item->product_id)) {
                            $product = Product::find($item->product_id);
                        }
                        
                        $formattedItems[] = [
                            'id' => $item->id ?? null,
                            'name' => $item->name ?? 'Unknown Product',
                            'price' => $item->price ?? 0,
                            'quantity' => $item->quantity ?? 1,
                            'subtotal' => ($item->price ?? 0) * ($item->quantity ?? 1),
                            'product_id' => $item->product_id ?? null,
                            'product_image' => $product ? $product->main_image : null,
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error formatting order items: ' . $e->getMessage(), [
                        'order_id' => $order->id,
                        'exception' => $e,
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Fallback: try to get items from order_items JSON
                    if ($order->order_items && is_array($order->order_items)) {
                        Log::info('Using fallback for order items from JSON data for order: ' . $order->id);
                        foreach ($order->order_items as $item) {
                            $product = null;
                            if (isset($item['product_id'])) {
                                $product = Product::find($item['product_id']);
                            }
                            
                            $formattedItems[] = [
                                'id' => $item['id'] ?? null,
                                'name' => $item['name'] ?? 'Unknown Product',
                                'price' => $item['price'] ?? 0,
                                'quantity' => $item['quantity'] ?? 1,
                                'subtotal' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1),
                                'product_id' => $item['product_id'] ?? null,
                                'product_image' => $product ? $product->main_image : null,
                            ];
                        }
                    }
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

    /**
     * Send notification for order status update
     * This endpoint is specifically for mobile clients to notify users of status changes
     */
    public function notifyOrderStatus(Request $request, $orderId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'old_status' => 'required|string',
                'new_status' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find the order
            $order = Order::where('order_id', $orderId)->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Check if the user exists
            if (!$order->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order has no associated user',
                ], 404);
            }

            $oldStatus = $request->old_status;
            $newStatus = $request->new_status;
            
            // Force notification even if status is the same (for testing or re-sending notifications)
            $forceNotification = $request->has('force_notification') ? (bool)$request->force_notification : false;
            
            // Skip notification if status is the same and not forcing
            if ($oldStatus === $newStatus && !$forceNotification) {
                return response()->json([
                    'success' => true,
                    'message' => 'No status change, notification not sent',
                ], 200);
            }

            // Get admin info if available
            $adminName = $request->input('admin_name', 'Admin');
            $adminEmail = $request->input('admin_email', 'admin@bloombouquet.com');
            $notes = $request->input('notes');
            
            // Send notification
            $this->notificationService->sendOrderStatusNotification($order, $oldStatus, $newStatus);
            
            // Store status update in history if needed
            if ($request->filled('notes') && Schema::hasColumn('orders', 'status_history')) {
                $statusHistory = $order->status_history ? 
                    (is_string($order->status_history) ? json_decode($order->status_history, true) : $order->status_history) : 
                    [];
                
                $statusHistory[] = [
                    'status' => $newStatus,
                    'previous_status' => $oldStatus,
                    'notes' => $notes,
                    'updated_by' => 'admin:' . $adminName,
                    'updated_at' => now()->toDateTimeString()
                ];
                
                $order->status_history = $statusHistory;
                $order->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Order status notification sent successfully',
                'notification_details' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'admin_name' => $adminName,
                    'sent_at' => now()->toIso8601String(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error sending order status notification: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh the cache for a specific order
     * This endpoint is called by the admin panel when an order status is updated
     * Instead of triggering a full order refresh on all clients, this simply updates
     * the cache on the server and sends a targeted notification
     *
     * @param string $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshCache(Request $request, $orderId)
    {
        try {
            Log::info('Refreshing order cache for order ID: ' . $orderId);
            
            // Validate admin access
            $adminKey = $request->header('X-Admin-Key');
            if (!$adminKey || $adminKey !== env('API_ADMIN_KEY', 'admin-secret-key')) {
                Log::warning('Unauthorized access attempt to refresh order cache');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            // Find the order
            $order = Order::where('id', $orderId)->orWhere('order_id', $orderId)->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }
            
            // We don't actually need to do anything with the cache here
            // This endpoint exists primarily to receive the refresh signal
            // The actual refresh will happen when clients request order data
            
            // Optionally clear any cache if using a caching system
            $cacheKey = 'order_' . $order->id;
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                Log::info('Cleared cache for order ID: ' . $order->id);
            }
            
            // Return success without triggering any client-side refreshes
            return response()->json([
                'success' => true,
                'message' => 'Order cache refreshed successfully',
                'order_id' => $order->id,
                'updated_at' => $order->updated_at->toIso8601String()
            ]);
        } catch (\Exception $e) {
            Log::error('Error refreshing order cache: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error refreshing order cache: ' . $e->getMessage()
            ], 500);
        }
    }
} 