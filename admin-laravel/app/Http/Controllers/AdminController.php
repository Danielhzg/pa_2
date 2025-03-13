<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Get total sales with single query
        $salesData = Order::select([
            DB::raw('SUM(CASE WHEN status = "completed" THEN total ELSE 0 END) as total_sales'),
            DB::raw('SUM(CASE WHEN status = "completed" AND MONTH(created_at) = MONTH(CURRENT_DATE()) THEN total ELSE 0 END) as monthly_sales'),
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_orders')
        ])->first();

        // Get recent orders with efficient loading
        $recentOrders = Order::withBasicRelations()
            ->latest()
            ->take(5)
            ->get();

        // Get popular products using joins
        $popularProducts = Product::select('products.*', DB::raw('COUNT(order_items.id) as orders_count'))
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'completed')
            ->groupBy('products.id')
            ->orderByDesc('orders_count')
            ->take(5)
            ->get();

        // Get customer stats
        $customerStats = User::select([
            DB::raw('COUNT(*) as total_customers'),
            DB::raw('COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_customers')
        ])
            ->where('role', 'customer')
            ->first();

        $stats = [
            'total_sales' => $salesData->total_sales ?? 0,
            'monthly_sales' => $salesData->monthly_sales ?? 0,
            'total_orders' => $salesData->total_orders ?? 0,
            'pending_orders' => $salesData->pending_orders ?? 0,
            'recent_orders' => $recentOrders,
            'popular_products' => $popularProducts,
            'total_customers' => $customerStats->total_customers ?? 0,
            'new_customers' => $customerStats->new_customers ?? 0,
        ];

        return view('dashboard', compact('stats'));
    }

    public function getChartData()
    {
        // Get daily sales for the last 30 days
        $dailySales = Order::select([
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total) as total_sales'),
            DB::raw('COUNT(*) as orders_count')
        ])
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get sales by category
        $categorySales = Product::select([
            'category',
            DB::raw('SUM(order_items.quantity * order_items.price) as total_sales'),
            DB::raw('COUNT(DISTINCT orders.id) as orders_count')
        ])
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'completed')
            ->groupBy('category')
            ->get();

        return response()->json([
            'dailySales' => $dailySales,
            'categorySales' => $categorySales
        ]);
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }
}
