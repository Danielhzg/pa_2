<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * Display a listing of the orders.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            $query = Order::with('user');
            
            // Filter by status
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }
            
            // Filter by date range
            if ($request->has('start_date') && $request->start_date) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $query->where('created_at', '>=', $startDate);
            }
            
            if ($request->has('end_date') && $request->end_date) {
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $query->where('created_at', '<=', $endDate);
            }
            
            // Get status counts for summary stats
            $waitingForPaymentCount = Order::where('status', Order::STATUS_WAITING_FOR_PAYMENT)->count();
            $processingCount = Order::where('status', Order::STATUS_PROCESSING)->count();
            $shippingCount = Order::where('status', Order::STATUS_SHIPPING)->count();
            $deliveredCount = Order::where('status', Order::STATUS_DELIVERED)->count();
            $cancelledCount = Order::where('status', Order::STATUS_CANCELLED)->count();
            
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
            // Load order with relationships
            $order->load(['user', 'items.product']);
            
            return view('admin.orders.show', compact('order'));
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
            
            // Update the order status
            $oldStatus = $order->status;
            $oldStatusLabel = $order->status_label;
            
            // Use the updateStatus method from the Order model
            $order->updateStatus($request->status);
            
            // Record admin who made the change
            $order->admin_id = auth()->guard('admin')->id();
            $order->save();
            
            // Send notification to the Flutter app
            $this->sendOrderStatusNotification(
                $order->user_id,
                $order->order_id, 
                $request->status, 
                "Status pesanan Anda telah berubah dari {$oldStatusLabel} menjadi {$order->status_label}."
            );
            
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
            
            // Record admin who made the change
            $order->admin_id = auth()->guard('admin')->id();
            $order->save();
            
            // Send notification to the Flutter app
            $this->sendOrderStatusNotification(
                $order->user_id,
                $order->order_id,
                null, // No status change
                "Status pembayaran pesanan Anda telah berubah dari {$oldPaymentStatusLabel} menjadi {$order->payment_status_label}."
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Status pembayaran berhasil diperbarui'
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
        try {
            // Get session variable that tracks the last time admin checked orders
            $lastCheckedTime = session('last_checked_orders_time', now()->subHours(1));
            
            // Count new orders since last check
            $newOrdersCount = Order::where('created_at', '>', $lastCheckedTime)
                ->where('status', Order::STATUS_WAITING_FOR_PAYMENT)
                ->count();
                
            // For orders that need immediate attention
            $needAttentionCount = Order::where('is_read', false)
                ->orWhere(function($query) {
                    $query->where('status', Order::STATUS_WAITING_FOR_PAYMENT)
                          ->where('created_at', '>', now()->subHours(24));
                })
                ->count();
            
            // Update last checked time if user is on the orders page
            $currentRoute = request()->route()->getName();
            if ($currentRoute === 'admin.orders.index') {
                session(['last_checked_orders_time' => now()]);
                
                // Mark orders as read if on the orders page
                Order::where('is_read', false)->update(['is_read' => true]);
            }
            
            return response()->json([
                'new_orders_count' => $newOrdersCount,
                'need_attention_count' => $needAttentionCount,
                'last_checked' => $lastCheckedTime
            ]);
        } catch (\Exception $e) {
            Log::error('Error in OrderController@checkNewOrders: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat memeriksa pesanan baru'
            ], 500);
        }
    }
    
    /**
     * Send notification about order status update to user's mobile app
     * 
     * @param int $userId
     * @param string $orderId
     * @param string|null $status
     * @param string $message
     */
    private function sendOrderStatusNotification($userId, $orderId, $status, $message)
    {
        try {
            // Implement your notification logic here
            // This might involve FCM (Firebase Cloud Messaging) or your own push notification system
            
            // For now, we'll just log the notification
            Log::info("Order status notification: User ID: {$userId}, Order ID: {$orderId}, Message: {$message}");
            
            // If you have a separate notification service or controller, you can call it here
            
        } catch (\Exception $e) {
            Log::error('Failed to send order status notification: ' . $e->getMessage());
        }
    }
} 