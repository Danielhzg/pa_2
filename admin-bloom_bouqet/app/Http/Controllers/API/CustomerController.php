<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Get all customers with pagination
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // Only fetch customers (users with role 'customer')
            $query = User::where('role', 'customer');
            
            // Search customers if search parameter is provided
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            // Add order statistics to each customer
            $customers = $query->withCount('orders')
                              ->withSum('orders', 'total_amount')
                              ->orderBy('created_at', 'desc')
                              ->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $customers,
                'message' => 'Customers fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customers: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific customer by ID with their order statistics
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $customer = User::where('id', $id)
                          ->where('role', 'customer')
                          ->firstOrFail();
            
            // Get customer's orders with pagination
            $orders = Order::where('user_id', $customer->id)
                          ->orderBy('created_at', 'desc')
                          ->paginate(5);
            
            // Calculate customer statistics
            $stats = [
                'total_orders' => Order::where('user_id', $id)->count(),
                'total_spent' => Order::where('user_id', $id)->sum('total_amount'),
                'last_order_date' => Order::where('user_id', $id)->latest()->first()?->created_at,
                'avg_order_value' => Order::where('user_id', $id)->avg('total_amount') ?? 0,
            ];
            
            // Get monthly stats for the past 6 months
            $monthlyStats = Order::where('user_id', $id)
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('COUNT(*) as orders_count'),
                    DB::raw('SUM(total_amount) as monthly_spent')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => $customer,
                    'stats' => $stats,
                    'orders' => $orders,
                    'monthly_stats' => $monthlyStats
                ],
                'message' => 'Customer details fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer details: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get overview customer statistics for dashboard
     * 
     * @return \Illuminate\Http\Response
     */
    public function getStatistics()
    {
        try {
            $stats = [
                'total_customers' => User::where('role', 'customer')->count(),
                'new_customers_this_month' => User::where('role', 'customer')
                                            ->where('created_at', '>=', now()->startOfMonth())
                                            ->count(),
                'most_active_customers' => User::where('role', 'customer')
                                          ->withCount('orders')
                                          ->orderBy('orders_count', 'desc')
                                          ->limit(5)
                                          ->get(),
                'top_spending_customers' => User::where('role', 'customer')
                                          ->withSum('orders', 'total_amount')
                                          ->orderBy('orders_sum_total_amount', 'desc')
                                          ->limit(5)
                                          ->get()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Customer statistics fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer statistics: ' . $e->getMessage()
            ], 500);
        }
    }
} 