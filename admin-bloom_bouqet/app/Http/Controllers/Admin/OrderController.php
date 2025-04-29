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
            $pendingCount = Order::where('status', 'pending')->count();
            $processingCount = Order::where('status', 'processing')->count();
            $completedCount = Order::where('status', 'completed')->count();
            $cancelledCount = Order::where('status', 'cancelled')->count();
            
            // Get orders with pagination
            $orders = $query->orderBy('created_at', 'desc')->paginate(10);
            
            return view('admin.orders.index', compact(
                'orders', 
                'pendingCount', 
                'processingCount', 
                'completedCount', 
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
                'status' => 'required|in:pending,processing,completed,cancelled'
            ]);
            
            // Update the order status
            $order->status = $request->status;
            $order->save();
            
            // Placeholder for sending push notification to the Flutter app
            // TODO: Implement notification to mobile device
            
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
     * Get order statistics for dashboard.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderStats()
    {
        try {
            $stats = [
                'total_orders' => Order::count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'processing_orders' => Order::where('status', 'processing')->count(),
                'completed_orders' => Order::where('status', 'completed')->count(),
                'cancelled_orders' => Order::where('status', 'cancelled')->count(),
                'total_revenue' => Order::where('status', '!=', 'cancelled')->sum('total_amount'),
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
} 