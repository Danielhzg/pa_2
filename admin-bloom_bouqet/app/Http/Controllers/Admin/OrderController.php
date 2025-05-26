<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the orders.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            // Base query with user relationship
            $query = Order::with(['user']);
            
            // Conditionally load items if needed for backward compatibility
            // In the future, we can remove this since items will be in the order_items JSON column
            if (Schema::hasColumn('orders', 'order_items')) {
                // We have the JSON column, use it directly
                // No need to load the relationship
            } else {
                // Old way - load the relationship
                $query->with(['items.product']);
            }
            
            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    // Search in order ID
                    $q->where('id', 'like', "%{$search}%")
                      // Search in user information
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('full_name', 'like', "%{$search}%")
                                   ->orWhere('username', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      })
                      // Search in phone number
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      // Search in shipping address
                      ->orWhere('shipping_address', 'like', "%{$search}%");
                      
                    // Search in order items names - either in JSON or relationship
                    if (Schema::hasColumn('orders', 'order_items')) {
                        // JSON search approach
                        $q->orWhereRaw("JSON_SEARCH(LOWER(order_items), 'one', LOWER(?)) IS NOT NULL", ["%{$search}%"]);
                    } else {
                        // Relationship search approach
                        $q->orWhereHas('items', function($itemQuery) use ($search) {
                            $itemQuery->where('name', 'like', "%{$search}%");
                        });
                    }
                });
            }
            
            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            // Filter by payment method
            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }
            
            // Filter by payment status
            if ($request->filled('payment_status')) {
                if ($request->payment_status === 'paid') {
                    $query->where('payment_status', 'paid');
                } elseif ($request->payment_status === 'unpaid') {
                    $query->where('payment_status', '!=', 'paid');
                }
            }
            
            // Filter by date range
            if ($request->filled('start_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $query->where('created_at', '>=', $startDate);
            }
            
            if ($request->filled('end_date')) {
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $query->where('created_at', '<=', $endDate);
            }
            
            // Get status counts for summary stats - these should not be affected by filters
            $waitingForPaymentCount = Order::where('status', Order::STATUS_WAITING_FOR_PAYMENT)->count();
            $processingCount = Order::where('status', Order::STATUS_PROCESSING)->count();
            $shippingCount = Order::where('status', Order::STATUS_SHIPPING)->count();
            $deliveredCount = Order::where('status', Order::STATUS_DELIVERED)->count();
            $cancelledCount = Order::where('status', Order::STATUS_CANCELLED)->count();
            
            // Get orders with pagination
            $orders = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
            
            return view('admin.orders.index', compact(
                'orders', 
                'waitingForPaymentCount',
                'processingCount', 
                'shippingCount',
                'deliveredCount', 
                'cancelledCount'
            ));
        } catch (\Exception $e) {
            Log::error('Error in OrderController@index: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat daftar pesanan.');
        }
    }

    /**
     * Display the specified order.
     *
     * @param Order $order
     * @return \Illuminate\View\View
     */
    public function show(Order $order)
    {
        try {
            // Load order relationships
            $order->load(['user', 'items.product']);
            
            // Mark the order as read
            if (!$order->is_read) {
                $order->is_read = true;
                $order->save();
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
                    'order_count' => Order::where('user_id', $user->id)->count(),
                ];
            }
            
            return view('admin.orders.show', compact('order', 'shippingAddress', 'paymentDetails', 'userData'));
        } catch (\Exception $e) {
            Log::error('Error in OrderController@show: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat detail pesanan.');
        }
    }

    /**
     * Update the status of the specified order.
     *
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, Order $order)
    {
        try {
            // Validate the request
            $request->validate([
                'status' => 'required|in:waiting_for_payment,processing,shipping,delivered,cancelled'
            ]);
            
            // Check if payment has been completed
            if ($order->payment_status !== 'paid' && $request->status !== 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Status pesanan hanya dapat diubah setelah pembayaran selesai'
                ], 400);
            }
            
            // Don't allow changing back to waiting_for_payment if already paid
            if ($order->payment_status === 'paid' && $request->status === 'waiting_for_payment') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat mengubah status kembali ke menunggu pembayaran setelah dibayar'
                ], 400);
            }
            
            // Update the order status
            $oldStatus = $order->status;
            $oldStatusLabel = $order->status_label;
            
            // Use the updateStatus method from the Order model
            $order->updateStatus($request->status);
            
            // Record admin who made the change
            $order->admin_id = auth()->guard('admin')->id();
            $order->save();
            
            // Send notifications
            $this->notificationService->sendOrderStatusNotification($order, $oldStatus, $request->status);
            
            return response()->json([
                'success' => true,
                'message' => 'Status pesanan berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in OrderController@updateStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui status pesanan'
            ], 500);
        }
    }
    
    /**
     * Update the payment status of the specified order.
     *
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePaymentStatus(Request $request, Order $order)
    {
        try {
            // Validate the request
            $request->validate([
                'payment_status' => 'required|in:pending,paid,failed,expired,refunded'
            ]);
            
            // Update the payment status
            $oldPaymentStatus = $order->payment_status;
            $oldPaymentStatusLabel = $order->payment_status_label;
            
            // Use the updatePaymentStatus method from the Order model
            $order->updatePaymentStatus($request->payment_status);
            
            // If payment status changes to paid, automatically update order status to processing
            if ($request->payment_status === 'paid' && $oldPaymentStatus !== 'paid' && $order->status === 'waiting_for_payment') {
                $order->updateStatus(Order::STATUS_PROCESSING);
                
                // Send additional notification about status change
                $this->notificationService->sendOrderStatusNotification(
                    $order, 
                    Order::STATUS_WAITING_FOR_PAYMENT, 
                    Order::STATUS_PROCESSING
                );
            }
            
            // Record admin who made the change
            $order->admin_id = auth()->guard('admin')->id();
            $order->save();
            
            // Send notifications
            $this->notificationService->sendPaymentStatusNotification($order, $oldPaymentStatus, $request->payment_status);
            
            return response()->json([
                'success' => true,
                'message' => 'Status pembayaran berhasil diperbarui',
                'order_status' => $order->status,
                'payment_status' => $order->payment_status
            ]);
        } catch (\Exception $e) {
            Log::error('Error in OrderController@updatePaymentStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui status pembayaran'
            ], 500);
        }
    }

    /**
     * Get order statistics for dashboard.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderStats()
    {
        try {
            $stats = [
                'total_orders' => Order::count(),
                'waiting_for_payment_orders' => Order::where('status', Order::STATUS_WAITING_FOR_PAYMENT)->count(),
                'processing_orders' => Order::where('status', Order::STATUS_PROCESSING)->count(),
                'shipping_orders' => Order::where('status', Order::STATUS_SHIPPING)->count(),
                'delivered_orders' => Order::where('status', Order::STATUS_DELIVERED)->count(),
                'cancelled_orders' => Order::where('status', Order::STATUS_CANCELLED)->count(),
                'total_revenue' => Order::where('status', '!=', Order::STATUS_CANCELLED)->sum('total_amount'),
                'recent_orders' => Order::with('user')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
            ];
            
            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error in OrderController@getOrderStats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil statistik pesanan'
            ], 500);
        }
    }
    
    /**
     * Check for new orders that need admin attention.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkNewOrders()
    {
        // Get the last check time from session
        $lastCheck = session('last_order_check', now()->subMinutes(5));
        
        // Update the last check time
        session(['last_order_check' => now()]);
        
        // Find new orders since last check
        $newOrders = Order::where('created_at', '>', $lastCheck)->count();
        
        // Find orders with payment status changed to 'paid' since last check
        $paymentStatusChanged = Order::where('updated_at', '>', $lastCheck)
                                    ->where('payment_status', 'paid')
                                    ->whereColumn('updated_at', '>', 'created_at')
                                    ->count();
        
        return response()->json([
            'new_orders_count' => $newOrders,
            'payment_status_changed_count' => $paymentStatusChanged,
            'last_check' => $lastCheck->format('Y-m-d H:i:s'),
            'current_time' => now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get order API data for the modal view
     *
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderApi(Order $order)
    {
        try {
            // Load order with relationships for backward compatibility
            $order->load(['user', 'items.product']);
            
            // Get items either from the JSON column or from the relationship
            $orderItems = $order->order_items;
            
            // If items are not in the JSON column, get them from the relationship and enhance them
            if (empty($orderItems) && $order->items->isNotEmpty()) {
                $orderItems = $order->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->price * $item->quantity,
                        'image' => $item->product ? $item->product->getPrimaryImage() : null,
                    ];
                })->toArray();
            } else {
                // Enhance the JSON items with additional data if needed
                $orderItems = collect($orderItems)->map(function($item) {
                    $item = (array) $item;
                    $productId = $item['product_id'] ?? null;
                    $product = null;
                    
                    if ($productId) {
                        $product = \App\Models\Product::find($productId);
                    }
                    
                    return [
                        'id' => $item['id'] ?? null,
                        'product_id' => $productId,
                        'name' => $item['name'] ?? 'Unknown Product',
                        'price' => $item['price'] ?? 0,
                        'quantity' => $item['quantity'] ?? 1,
                        'subtotal' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1),
                        'image' => $product ? $product->getPrimaryImage() : null,
                    ];
                })->toArray();
            }
            
            // Format data for response
            $shippingAddress = is_string($order->shipping_address) ? json_decode($order->shipping_address, true) : $order->shipping_address;
            $customerEmail = $order->user ? $order->user->email : 'guest@example.com';
            
            // Try to get email from shipping address if it's guest@example.com
            if ($customerEmail === 'guest@example.com' && is_array($shippingAddress) && isset($shippingAddress['email'])) {
                $customerEmail = $shippingAddress['email'];
            }
            
            $orderData = [
                'id' => $order->id,
                'order_id' => $order->order_id,
                'user_id' => $order->user_id,
                'created_at' => $order->created_at->format('d M Y H:i'), // Use actual order time
                'status' => $order->status,
                'status_label' => $order->status_label,
                'payment_status' => $order->payment_status,
                'payment_status_label' => $order->payment_status_label,
                'payment_method' => $order->payment_method,
                'subtotal' => $order->subtotal,
                'shipping_cost' => $order->shipping_cost,
                'total_amount' => $order->total_amount,
                'shipping_address' => $shippingAddress,
                'phone_number' => $order->phone_number,
                'user' => [
                    'id' => $order->user->id ?? 0,
                    'name' => $order->user ? ($order->user->name ?? $order->user->full_name ?? 'Pelanggan') : ($shippingAddress['name'] ?? 'Pelanggan'),
                    'email' => $customerEmail,
                    'phone' => $order->user ? ($order->user->phone ?? $order->phone_number) : ($shippingAddress['phone'] ?? $order->phone_number),
                ],
                'items' => $orderItems,
            ];
            
            // Log successful data retrieval for debugging
            \Log::info('Order data retrieved successfully for ID: ' . $order->id);
            
            return response()->json($orderData);
        } catch (\Exception $e) {
            \Log::error('Error in OrderController@getOrderApi: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat memuat detail pesanan: ' . $e->getMessage()
            ], 500);
        }
    }
} 