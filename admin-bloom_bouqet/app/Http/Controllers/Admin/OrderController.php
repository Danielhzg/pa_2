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
use GuzzleHttp\Client;

class OrderController extends Controller
{
    protected $notificationService;

    /**
     * Create a new controller instance.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth:admin');
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
            // Pastikan $order ada dan valid
            if (!isset($order) || !$order) {
                return back()->with('error', 'Pesanan tidak ditemukan.');
            }

            // Load order relationships
            $order->load(['user']);
            
            // Ambil item pesanan dengan benar
            try {
                $orderItems = $order->getFormattedItems();
                
                // Tambahkan informasi product jika ada
                foreach ($orderItems as $key => $item) {
                    if (isset($item['product_id']) && $item['product_id']) {
                        $product = \App\Models\Product::find($item['product_id']);
                        if ($product) {
                            $orderItems[$key]['product'] = $product;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error formatting order items: ' . $e->getMessage());
                $orderItems = [];
            }
            
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
            
            return view('admin.orders.show', compact('order', 'shippingAddress', 'paymentDetails', 'userData', 'orderItems'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in OrderController@show: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat detail pesanan: ' . $e->getMessage());
        }
    }

    /**
     * Update the status of the specified order.
     *
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, Order $order)
    {
        try {
            // Validate request
            $request->validate([
                'status' => 'required|in:waiting_for_payment,processing,shipping,delivered,cancelled',
                'status_notes' => 'nullable|string|max:500',
                'send_notification' => 'nullable|boolean'
            ]);
            
            // Get the new status
            $newStatus = $request->status;
            
            // Save the old status for notification
            $oldStatus = $order->status;
            
            // Don't send notification if status didn't change
            if ($oldStatus === $newStatus) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Status pesanan tidak berubah'
                    ], 422);
                }
                return back()->with('info', 'Status pesanan tidak berubah');
            }
            
            // Check if the order is already cancelled
            if ($oldStatus === Order::STATUS_CANCELLED && $newStatus !== Order::STATUS_CANCELLED) {
                $errorMessage = 'Pesanan yang sudah dibatalkan tidak dapat diubah statusnya';
                
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 422);
                }
                
                return back()->with('error', $errorMessage);
            }
            
            // Check if payment is required for status change
            // Only check for non-cancelled orders and when moving from waiting_for_payment to another status
            if ($oldStatus === Order::STATUS_WAITING_FOR_PAYMENT && 
                $newStatus !== Order::STATUS_CANCELLED && 
                $newStatus !== Order::STATUS_WAITING_FOR_PAYMENT && 
                $order->payment_status !== 'paid') {
                
                $errorMessage = 'Pesanan harus dibayar terlebih dahulu sebelum status dapat diubah';
                
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 422);
                }
                
                return back()->with('error', $errorMessage);
            }
            
            // Record who updated the status
            $adminId = auth()->guard('admin')->id();
            $adminName = auth()->guard('admin')->user()->name ?? 'Unknown Admin';

            // Use the enhanced updateStatus method from the Order model
            try {
                $result = $order->updateStatus($newStatus, null, $adminId);

                if (!$result['changed']) {
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Status pesanan sudah ' . $newStatus,
                            'order' => [
                                'id' => $order->id,
                                'status' => $order->status,
                                'status_label' => $order->status_label
                            ]
                        ]);
                    }
                    return back()->with('info', 'Status pesanan sudah ' . $newStatus);
                }
            } catch (\InvalidArgumentException $e) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage()
                    ], 422);
                }
                return back()->with('error', $e->getMessage());
            }
            
            // Store status notes if provided
            if ($request->filled('status_notes')) {
                // First check if we have a status_history column
                if (Schema::hasColumn('orders', 'status_history')) {
                    // Get current status history or initialize as empty array
                    $statusHistory = $order->status_history ? 
                        (is_string($order->status_history) ? json_decode($order->status_history, true) : $order->status_history) : 
                        [];
                    
                    // Add new status change entry
                    $statusHistory[] = [
                        'status' => $newStatus,
                        'previous_status' => $oldStatus,
                        'notes' => $request->status_notes,
                        'updated_by' => 'admin:' . $adminId . ' (' . $adminName . ')',
                        'updated_at' => now()->toDateTimeString()
                    ];
                    
                    // Save status history back to order
                    $order->status_history = $statusHistory;
                } else {
                    // If no status_history column, store in status_notes column if it exists
                    if (Schema::hasColumn('orders', 'status_notes')) {
                        $order->status_notes = $request->status_notes;
                    }
                }
            }
            
            // Set appropriate timestamps based on new status
            switch ($newStatus) {
                case Order::STATUS_PROCESSING:
                    if (!$order->processing_started_at) {
                        $order->processing_started_at = now();
                    }
                    break;
                case Order::STATUS_SHIPPING:
                    if (!$order->shipped_at) {
                        $order->shipped_at = now();
                    }
                    break;
                case Order::STATUS_DELIVERED:
                    if (!$order->delivered_at) {
                        $order->delivered_at = now();
                    }
                    break;
                case Order::STATUS_CANCELLED:
                    if (!$order->cancelled_at) {
                        $order->cancelled_at = now();
                    }
                    break;
            }
            
            // Save the order
            $order->save();
            
            // Handle special status changes
            if ($newStatus === Order::STATUS_DELIVERED) {
                $this->handleCompletedOrder($order);
            } elseif ($newStatus === Order::STATUS_CANCELLED) {
                // Handle cancellation - may need to refund payment, restore stock, etc.
                // This would be implemented based on your business logic
            }
            
            // Determine if we should send a notification
            $sendNotification = $request->has('send_notification') ? (bool)$request->send_notification : true;
            
            // Send notification to user if enabled
            if ($sendNotification) {
                $this->sendOrderStatusNotification($order, $oldStatus, $newStatus);
                $notificationSent = true;
            } else {
                $notificationSent = false;
            }
            
            // Forcefully sync to API cache for real-time update on Flutter app
            try {
                $this->forceRefreshOrderInAPI($order->id);
            } catch (\Exception $e) {
                Log::warning('Failed to force refresh order in API: ' . $e->getMessage());
                // Don't stop execution if this fails
            }
            
            // Return response based on request type
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status pesanan berhasil diperbarui menjadi ' . $order->status_label,
                    'notification_sent' => $notificationSent,
                    'order' => [
                        'id' => $order->id,
                        'status' => $order->status,
                        'status_label' => $order->status_label,
                        'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                        'status_updated_at' => $order->status_updated_at ? $order->status_updated_at->format('Y-m-d H:i:s') : null,
                        'status_updated_by' => $order->status_updated_by
                    ]
                ]);
            }
            
            $successMessage = 'Status pesanan berhasil diperbarui menjadi ' . $order->status_label;
            if ($notificationSent) {
                $successMessage .= ' dan notifikasi telah dikirim ke pelanggan';
            }
            
            return back()->with('success', $successMessage);
        } catch (\Exception $e) {
            Log::error('Error in OrderController@updateStatus: ' . $e->getMessage());
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memperbarui status pesanan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Terjadi kesalahan saat memperbarui status pesanan.');
        }
    }

    /**
     * Handle additional tasks when an order is marked as delivered
     * 
     * @param Order $order
     * @return void
     */
    protected function handleCompletedOrder(Order $order)
    {
        try {
            // Mark customer service as completed if applicable
            $this->markCustomerServiceComplete($order);
            
            // Additional actions for completed orders, like analytics tracking, etc.
            Log::info("Order #{$order->id} marked as completed with additional handling");
            
            // Update delivered timestamp if not already set
            if (!$order->delivered_at) {
                $order->delivered_at = now();
                $order->save();
            }
        } catch (\Exception $e) {
            Log::error('Error in OrderController@handleCompletedOrder: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e
            ]);
        }
    }
    
    /**
     * Mark customer service as complete for an order
     * 
     * @param Order $order
     * @return void
     */
    protected function markCustomerServiceComplete(Order $order)
    {
        try {
            // If we have a customer service system, mark this order's service as complete
            // This would be implemented based on your customer service system
            
            // For example, update a customer_service record
            $customerService = \DB::table('customer_service')
                ->where('order_id', $order->id)
                ->where('status', '!=', 'completed')
                ->first();
                
            if ($customerService) {
                \DB::table('customer_service')
                    ->where('id', $customerService->id)
                    ->update([
                        'status' => 'completed',
                        'completion_reason' => 'Order delivered successfully',
                        'completed_at' => now(),
                        'completed_by' => auth()->guard('admin')->id()
                    ]);
                    
                Log::info("Customer service for order #{$order->id} marked as complete");
            }
        } catch (\Exception $e) {
            Log::error('Error marking customer service as complete: ' . $e->getMessage(), [
                'order_id' => $order->id
            ]);
        }
    }
    
    /**
     * Send order status notifications to relevant parties
     * 
     * @param Order $order
     * @param string $oldStatus
     * @param string $newStatus
     * @return void
     */
    protected function sendOrderStatusNotification($order, $oldStatus, $newStatus)
    {
        try {
            // Don't send notification if status didn't change
            if ($oldStatus === $newStatus) {
                return;
            }
            
            // Use NotificationService if available
            if (isset($this->notificationService) && method_exists($this->notificationService, 'sendOrderStatusNotification')) {
                $this->notificationService->sendOrderStatusNotification($order, $oldStatus, $newStatus);
                Log::info("Order #{$order->id} status notification sent via NotificationService: {$oldStatus} -> {$newStatus}");
                return;
            }
            
            // Fallback notification logic if service not available
            $user = $order->user;
            if (!$user || !$user->id) {
                Log::warning("Cannot send notification for order #{$order->id} - no user found");
                return;
            }
            
            // Prepare notification data
            $statusLabels = [
                'waiting_for_payment' => 'Menunggu Pembayaran',
                'processing' => 'Diproses',
                'shipping' => 'Dikirim',
                'delivered' => 'Selesai',
                'cancelled' => 'Dibatalkan'
            ];
            
            $title = "Status Pesanan #{$order->id} Diperbarui";
            $message = "Pesanan Anda telah diperbarui dari {$statusLabels[$oldStatus]} menjadi {$statusLabels[$newStatus]}.";
            
            // Store notification in database with additional metadata
            $notificationData = [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'updated_by' => auth()->guard('admin')->id() ?? 'system',
                'update_time' => now()->toIso8601String()
            ];
            
            // Insert into notifications table
            $notificationId = \DB::table('notifications')->insertGetId([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => 'order',
                'data' => json_encode($notificationData),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info("Notification #{$notificationId} stored in database for order #{$order->id}");
            
            // Send push notification if possible
            $this->sendPushNotification($user->id, $title, $message, $notificationData);
            
            // Send notification via WebSockets for real-time updates if available
            try {
                $this->sendWebSocketNotification($user->id, $title, $message, $notificationData);
            } catch (\Exception $e) {
                Log::warning("WebSocket notification failed for order #{$order->id}: " . $e->getMessage());
            }
            
            // Try sending via FCM for Flutter apps
            try {
                $this->sendFirebaseNotification($user->id, $title, $message, $notificationData);
            } catch (\Exception $e) {
                Log::warning("Firebase notification failed for order #{$order->id}: " . $e->getMessage());
            }
            
            Log::info("Notification sent for order #{$order->id} status change: {$oldStatus} -> {$newStatus}");
        } catch (\Exception $e) {
            Log::error('Error sending order status notification: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e
            ]);
        }
    }
    
    /**
     * Send push notification to user
     * 
     * @param int $userId
     * @param string $title
     * @param string $message
     * @param array $data
     * @return void
     */
    protected function sendPushNotification($userId, $title, $message, $data = [])
    {
        try {
            // Get user's device tokens
            $deviceTokens = \DB::table('device_tokens')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->pluck('token')
                ->toArray();
                
            if (empty($deviceTokens)) {
                Log::info("No active device tokens found for user #{$userId}");
                return;
            }
            
            // Prepare notification data with high priority for better delivery
            $notificationData = [
                'title' => $title,
                'body' => $message,
                'data' => $data,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'sound' => 'default',
                'badge' => '1',
                'priority' => 'high',
                'content_available' => true
            ];
            
            // Log the notification attempt
            Log::info("Push notification prepared for user #{$userId}", [
                'title' => $title,
                'device_count' => count($deviceTokens)
            ]);
            
            // Attempt to send using multiple channels to ensure delivery
            $this->sendNotificationToMultipleChannels($deviceTokens, $notificationData);
            
        } catch (\Exception $e) {
            Log::error('Error sending push notification: ' . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e
            ]);
        }
    }
    
    /**
     * Send notification to multiple channels
     * 
     * @param array $deviceTokens
     * @param array $notificationData
     * @return void
     */
    protected function sendNotificationToMultipleChannels($deviceTokens, $notificationData)
    {
        // Group tokens by platform if that info is available
        $androidTokens = [];
        $iosTokens = [];
        
        // In a real implementation, you'd have device platform stored
        // For now, we'll send to all tokens via multiple methods to maximize chances
        
        // 1. Send via FCM directly if credentials are available
        try {
            $this->sendFCMNotification($deviceTokens, $notificationData);
        } catch (\Exception $e) {
            Log::warning('FCM notification failed: ' . $e->getMessage());
        }
        
        // 2. Send via any notification service you might be using
        // This could be a third-party service like OneSignal, Firebase, etc.
        
        // 3. Store in a notification queue for retry mechanism
        try {
            \DB::table('notification_queue')->insert([
                'tokens' => json_encode($deviceTokens),
                'data' => json_encode($notificationData),
                'attempts' => 0,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to queue notification: ' . $e->getMessage());
        }
    }
    
    /**
     * Send notification via WebSockets for real-time updates
     * 
     * @param int $userId
     * @param string $title
     * @param string $message
     * @param array $data
     * @return void
     */
    protected function sendWebSocketNotification($userId, $title, $message, $data = [])
    {
        // Implementation would depend on your WebSocket setup
        // Example using Laravel's event broadcasting:
        try {
            if (class_exists('\App\Events\OrderStatusChanged')) {
                event(new \App\Events\OrderStatusChanged($userId, $title, $message, $data));
                Log::info("WebSocket notification event dispatched for user #{$userId}");
            }
        } catch (\Exception $e) {
            Log::warning('WebSocket notification failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Send notification via Firebase for Flutter apps
     * 
     * @param int $userId
     * @param string $title
     * @param string $message
     * @param array $data
     * @return void
     */
    protected function sendFirebaseNotification($userId, $title, $message, $data = [])
    {
        try {
            // Get user's FCM tokens
            $fcmTokens = \DB::table('fcm_tokens')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->pluck('token')
                ->toArray();
                
            if (empty($fcmTokens)) {
                // Try getting from device_tokens table as fallback
                $fcmTokens = \DB::table('device_tokens')
                    ->where('user_id', $userId)
                    ->where('is_active', true)
                    ->pluck('token')
                    ->toArray();
                    
                if (empty($fcmTokens)) {
                    Log::info("No FCM tokens found for user #{$userId}");
                    return;
                }
            }
            
            // Send using FCM
            $this->sendFCMNotification($fcmTokens, [
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                    'sound' => 'default',
                    'badge' => '1',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ],
                'data' => array_merge($data, [
                    'type' => 'order_update',
                    'order_id' => $data['order_id'] ?? '',
                ]),
                'priority' => 'high',
                'content_available' => true
            ]);
            
            Log::info("Firebase notification sent to user #{$userId} with " . count($fcmTokens) . " tokens");
        } catch (\Exception $e) {
            Log::warning('Firebase notification failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Send notification via Firebase Cloud Messaging
     * 
     * @param array $tokens
     * @param array $data
     * @return void
     */
    protected function sendFCMNotification($tokens, $data)
    {
        // This is a placeholder implementation
        // In a real application, you would use the Firebase Admin SDK or HTTP v1 API
        
        try {
            $fcmApiKey = env('FCM_SERVER_KEY');
            
            if (empty($fcmApiKey)) {
                Log::warning('FCM server key not configured');
                return;
            }
            
            $client = new \GuzzleHttp\Client();
            
            $response = $client->post('https://fcm.googleapis.com/fcm/send', [
                'headers' => [
                    'Authorization' => 'key=' . $fcmApiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'registration_ids' => $tokens,
                    'notification' => [
                        'title' => $data['title'] ?? ($data['notification']['title'] ?? ''),
                        'body' => $data['body'] ?? ($data['notification']['body'] ?? ''),
                        'sound' => 'default',
                        'badge' => '1',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                    ],
                    'data' => $data['data'] ?? [],
                    'priority' => 'high',
                    'content_available' => true
                ],
                'timeout' => 5,
                'connect_timeout' => 5
            ]);
            
            $responseData = json_decode((string) $response->getBody(), true);
            
            if (isset($responseData['success']) && $responseData['success'] > 0) {
                Log::info("FCM notification sent successfully to " . count($tokens) . " devices");
            } else {
                Log::warning("FCM notification may have failed", ['response' => $responseData]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending FCM notification: ' . $e->getMessage());
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
            // Pastikan $order ada dan valid
            if (!isset($order) || !$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan tidak ditemukan'
                ], 404);
            }
            
            // Validate the request
            $request->validate([
                'payment_status' => 'required|in:pending,paid,failed,expired,refunded',
                'send_notification' => 'boolean', // Optional parameter to control notification sending
                'payment_notes' => 'nullable|string|max:500' // Optional notes about payment
            ]);
            
            // Update the payment status
            $oldPaymentStatus = $order->payment_status;
            $oldPaymentStatusLabel = $order->payment_status_label;
            
            // Use the updatePaymentStatus method from the Order model
            $order->updatePaymentStatus($request->payment_status);
            
            // Record payment notes if provided
            if ($request->filled('payment_notes')) {
                // Check if we have payment_history column
                if (Schema::hasColumn('orders', 'payment_history')) {
                    // Get current payment history or initialize as empty array
                    $paymentHistory = $order->payment_history ? 
                        (is_string($order->payment_history) ? json_decode($order->payment_history, true) : $order->payment_history) : 
                        [];
                    
                    // Add new payment status change entry
                    $paymentHistory[] = [
                        'status' => $request->payment_status,
                        'previous_status' => $oldPaymentStatus,
                        'notes' => $request->payment_notes,
                        'updated_by' => 'admin:' . auth()->guard('admin')->id(),
                        'updated_at' => now()->toDateTimeString()
                    ];
                    
                    // Save payment history back to order
                    $order->payment_history = $paymentHistory;
                    $order->save();
                }
            }
            
            // Set paid_at timestamp if payment status is paid and it's not already set
            if ($request->payment_status === 'paid' && !$order->paid_at) {
                $order->paid_at = now();
                $order->save();
            }
            
            // Control whether to send notifications based on the parameter
            $sendNotification = $request->has('send_notification') ? (bool)$request->input('send_notification') : true;
            
            // If payment status changes to paid, automatically update order status to processing
            if ($request->payment_status === 'paid' && $oldPaymentStatus !== 'paid' && $order->status === 'waiting_for_payment') {
                $order->updateStatus(Order::STATUS_PROCESSING);
                
                // Send additional notification about status change if notifications are enabled
                if ($sendNotification && isset($this->notificationService) && method_exists($this->notificationService, 'sendOrderStatusNotification')) {
                    $this->notificationService->sendOrderStatusNotification(
                        $order, 
                        Order::STATUS_WAITING_FOR_PAYMENT, 
                        Order::STATUS_PROCESSING
                    );
                }
            }
            
            // Record admin who made the change
            $order->admin_id = auth()->guard('admin')->id();
            $order->save();
            
            // Send notifications if enabled
            if ($sendNotification && isset($this->notificationService) && method_exists($this->notificationService, 'sendPaymentStatusNotification')) {
                $this->notificationService->sendPaymentStatusNotification($order, $oldPaymentStatus, $request->payment_status);
            } else if ($sendNotification) {
                // Fallback notification if service not available
                try {
                    $user = $order->user;
                    if ($user && $user->id) {
                        // Create notification in database
                        \App\Models\Notification::create([
                            'user_id' => $user->id,
                            'title' => 'Status Pembayaran Diperbarui',
                            'message' => "Status pembayaran pesanan #{$order->id} diperbarui menjadi " . $order->payment_status_label,
                            'type' => 'payment',
                            'data' => [
                                'order_id' => $order->id,
                                'payment_status' => $order->payment_status
                            ],
                            'is_read' => false
                        ]);
                        
                        // Try to send push notification if possible
                        $this->sendPushNotification(
                            $user->id,
                            'Status Pembayaran Diperbarui',
                            "Status pembayaran pesanan #{$order->id} diperbarui menjadi " . $order->payment_status_label,
                            [
                                'order_id' => $order->id,
                                'payment_status' => $order->payment_status,
                                'type' => 'payment_update'
                            ]
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('Error sending payment status notification: ' . $e->getMessage());
                    // Don't stop execution if notification fails
                }
            }
            
            // Forcefully sync to API cache for real-time update on Flutter app
            try {
                $this->forceRefreshOrderInAPI($order->id);
            } catch (\Exception $e) {
                Log::warning('Failed to force refresh order in API after payment update: ' . $e->getMessage());
                // Don't stop execution if this fails
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Status pembayaran berhasil diperbarui',
                'notification_sent' => $sendNotification,
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
            // Ensure we have the order
            if (!isset($order) || !$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan tidak ditemukan'
                ], 404);
            }
            
            // Load order user relationship
            $order->load(['user']);
            
            // Parse shipping address if stored as JSON
            $shippingAddress = $order->shipping_address;
            if (is_string($shippingAddress)) {
                try {
                    $shippingAddress = json_decode($shippingAddress, true);
                } catch (\Exception $e) {
                    // Keep as string if not valid JSON
                }
            }
            
            // Determine who updated the status
            $statusUpdatedBy = 'Sistem';
            if ($order->status_updated_by) {
                if (strpos($order->status_updated_by, 'admin:') !== false) {
                    $adminId = str_replace('admin:', '', $order->status_updated_by);
                    $admin = \App\Models\Admin::find($adminId);
                    $statusUpdatedBy = 'Admin ' . ($admin ? $admin->name : 'Unknown');
                } else if (strpos($order->status_updated_by, 'payment_system') !== false) {
                    $statusUpdatedBy = 'Sistem Pembayaran (otomatis)';
                } else {
                    $statusUpdatedBy = $order->status_updated_by;
                }
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
                'status_updated_by' => $statusUpdatedBy,
                'status_updated_at' => $order->status_updated_at ? $order->status_updated_at->format('d M Y H:i') : null,
                'items' => $order->getFormattedItems(),
                'customer' => [
                    'name' => $order->user->name ?? 'Guest Customer',
                    'email' => $order->user->email ?? 'guest@example.com',
                    'phone' => $order->user->phone ?? ($order->phone_number ?? 'Tidak tersedia')
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $orderData
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in OrderController@getOrderApi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memuat data pesanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed order information including items, customer data, and status history.
     *
     * @param Order $order
     * @return \Illuminate\Http\Response
     */
    public function getOrderDetail(Order $order)
    {
        try {
            // Ensure we have complete data
            $order->load(['user']);
            
            // Get formatted items with product details
            $items = $order->getFormattedItems();
            
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
            
            // Log for debugging
            Log::info('Rendering order detail modal content', [
                'order_id' => $order->id,
                'items_count' => count($items),
                'has_user' => $order->user ? true : false
            ]);
            
            // Return the modal content view
            return view('admin.orders.order_detail_modal_content', compact('order', 'items', 'shippingAddress', 'paymentDetails'));
        } catch (\Exception $e) {
            Log::error('Error in OrderController@getOrderDetail: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->view('admin.orders.order_detail_error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Force refresh order in API cache to ensure real-time update on Flutter app
     * Using a dedicated cache refresh endpoint to avoid triggering unnecessary re-renders
     *
     * @param string $orderId
     * @return bool
     */
    protected function forceRefreshOrderInAPI($orderId)
    {
        try {
            // Use the dedicated cache refresh endpoint
            $apiUrl = env('API_URL', config('app.url') . '/api') . '/v1/orders/' . $orderId . '/refresh-cache';
            
            $client = new Client();
            $response = $client->post($apiUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Admin-Key' => env('API_ADMIN_KEY', 'admin-secret-key')
                ],
                'json' => [
                    'order_id' => $orderId,
                    'refresh_type' => 'status_update',
                    'initiated_by' => 'admin',
                    'timestamp' => now()->toIso8601String()
                ],
                'timeout' => 3, // Short timeout to avoid blocking
                'connect_timeout' => 3
            ]);
            
            Log::info('Successfully refreshed order cache in API for order ID: ' . $orderId);
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to refresh order cache in API: ' . $e->getMessage());
            return false;
        }
    }
} 