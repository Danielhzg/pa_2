<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\OrderNotificationService;
use Illuminate\Support\Facades\Log;

class OrderManagementController extends Controller
{
    protected $notificationService;

    public function __construct(OrderNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display orders dashboard
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $search = $request->get('search');
        
        $query = Order::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Search functionality
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate(20);

        // Get order statistics
        $stats = [
            'total' => Order::count(),
            'waiting_payment' => Order::where('status', Order::STATUS_WAITING_PAYMENT)->count(),
            'processing' => Order::where('status', Order::STATUS_PROCESSING)->count(),
            'shipping' => Order::where('status', Order::STATUS_SHIPPING)->count(),
            'delivered' => Order::where('status', Order::STATUS_DELIVERED)->count(),
            'cancelled' => Order::where('status', Order::STATUS_CANCELLED)->count(),
        ];

        // Get recent notifications
        $notifications = $this->notificationService->getAdminNotifications(10);

        // Extract individual counts for the view
        $waitingForPaymentCount = $stats['waiting_payment'];
        $processingCount = $stats['processing'];
        $shippingCount = $stats['shipping'];
        $deliveredCount = $stats['delivered'];
        $cancelledCount = $stats['cancelled'];

        return view('admin.orders.index', compact(
            'orders', 'stats', 'notifications', 'status', 'search',
            'waitingForPaymentCount', 'processingCount', 'shippingCount',
            'deliveredCount', 'cancelledCount'
        ));
    }

    /**
     * Display real-time dashboard
     */
    public function realtimeDashboard(Request $request)
    {
        $status = $request->get('status', 'all');

        $query = Order::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->limit(50)->get(); // Limit for performance

        // Get order statistics
        $stats = [
            'waiting_payment' => Order::where('status', Order::STATUS_WAITING_PAYMENT)->count(),
            'processing' => Order::where('status', Order::STATUS_PROCESSING)->count(),
            'shipping' => Order::where('status', Order::STATUS_SHIPPING)->count(),
            'delivered' => Order::where('status', Order::STATUS_DELIVERED)->count(),
        ];

        // Get recent notifications
        $notifications = $this->notificationService->getAdminNotifications(10);

        return view('admin.orders.realtime-dashboard', compact('orders', 'stats', 'notifications', 'status'));
    }

    /**
     * Show order details
     */
    public function show($id)
    {
        $order = Order::with('user')->findOrFail($id);
        
        // Mark order as read
        if (!$order->is_read) {
            $order->is_read = true;
            $order->save();
        }

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string|in:waiting_for_payment,processing,shipping,delivered,cancelled',
            ]);

            $order = Order::findOrFail($id);
            $oldStatus = $order->status;
            
            $order->updateStatus($request->status);

            Log::info("Admin updated order status: Order {$order->order_id} from {$oldStatus} to {$request->status}");

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => [
                    'order_id' => $order->order_id,
                    'old_status' => $oldStatus,
                    'new_status' => $order->status,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin order status update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'payment_status' => 'required|string|in:pending,paid,failed,expired',
            ]);

            $order = Order::findOrFail($id);
            $oldPaymentStatus = $order->payment_status;
            
            $order->updatePaymentStatus($request->payment_status);

            Log::info("Admin updated payment status: Order {$order->order_id} from {$oldPaymentStatus} to {$request->payment_status}");

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully',
                'data' => [
                    'order_id' => $order->order_id,
                    'old_payment_status' => $oldPaymentStatus,
                    'new_payment_status' => $order->payment_status,
                    'order_status' => $order->status,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin payment status update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get order statistics for dashboard
     */
    public function getStats()
    {
        try {
            $stats = [
                'total_orders' => Order::count(),
                'pending_orders' => Order::where('status', Order::STATUS_WAITING_PAYMENT)->count(),
                'processing_orders' => Order::where('status', Order::STATUS_PROCESSING)->count(),
                'completed_orders' => Order::where('status', Order::STATUS_DELIVERED)->count(),
                'total_revenue' => Order::where('payment_status', Order::PAYMENT_PAID)->sum('total_amount'),
                'today_orders' => Order::whereDate('created_at', today())->count(),
                'unread_orders' => Order::where('is_read', false)->count(),
            ];

            return response()->json(['success' => true, 'data' => $stats]);

        } catch (\Exception $e) {
            Log::error('Get order stats error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get notifications for admin
     */
    public function getNotifications()
    {
        try {
            $notifications = $this->notificationService->getAdminNotifications(20);
            $unreadCount = $this->notificationService->getUnreadNotificationsCount();

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => $unreadCount,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get notifications error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markNotificationsRead()
    {
        try {
            $this->notificationService->clearNotifications();

            return response()->json(['success' => true, 'message' => 'All notifications marked as read']);

        } catch (\Exception $e) {
            Log::error('Mark notifications read error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk update order status
     */
    public function bulkUpdateStatus(Request $request)
    {
        try {
            $request->validate([
                'order_ids' => 'required|array',
                'order_ids.*' => 'integer|exists:orders,id',
                'status' => 'required|string|in:waiting_for_payment,processing,shipping,delivered,cancelled',
            ]);

            $orders = Order::whereIn('id', $request->order_ids)->get();
            $updatedCount = 0;

            foreach ($orders as $order) {
                $oldStatus = $order->status;
                $order->updateStatus($request->status);
                $updatedCount++;
                
                Log::info("Bulk update: Order {$order->order_id} from {$oldStatus} to {$request->status}");
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} orders",
                'data' => ['updated_count' => $updatedCount]
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
