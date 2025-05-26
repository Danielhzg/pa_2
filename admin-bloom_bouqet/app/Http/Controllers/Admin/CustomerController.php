<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        try {
            $query = User::query();
            
            // Filter out guest accounts
            $query->where(function($q) {
                $q->whereNull('email')
                  ->orWhere('email', 'not like', '%@guestgmail.com%');
            });
            
            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            // Get customer data with order counts and total spending
            $customers = $query->where('role', 'customer')
                ->withCount('orders')
                ->withSum('orders as orders_sum_total_amount', 'total_amount')
                ->latest()
                ->paginate(10);
            
            // Get statistics for dashboard widgets
            $statistics = [
                'total_customers' => User::where('role', 'customer')
                    ->where(function($q) {
                        $q->whereNull('email')
                          ->orWhere('email', 'not like', '%@guestgmail.com%');
                    })
                    ->count(),
                'new_customers_this_month' => User::where('role', 'customer')
                    ->where(function($q) {
                        $q->whereNull('email')
                          ->orWhere('email', 'not like', '%@guestgmail.com%');
                    })
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'top_spending_customers' => User::where('role', 'customer')
                    ->where(function($q) {
                        $q->whereNull('email')
                          ->orWhere('email', 'not like', '%@guestgmail.com%');
                    })
                    ->withSum('orders as orders_sum_total_amount', 'total_amount')
                    ->withCount('orders')
                    ->orderBy('orders_sum_total_amount', 'desc')
                    ->limit(5)
                    ->get(),
                'most_active_customers' => User::where('role', 'customer')
                    ->where(function($q) {
                        $q->whereNull('email')
                          ->orWhere('email', 'not like', '%@guestgmail.com%');
                    })
                    ->withCount('orders')
                    ->withSum('orders as orders_sum_total_amount', 'total_amount')
                    ->orderBy('orders_count', 'desc')
                    ->limit(5)
                    ->get()
            ];
            
            return view('admin.customers.index', compact('customers', 'statistics'));
        } catch (\Exception $e) {
            Log::error('Error in CustomerController@index: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat data pelanggan: ' . $e->getMessage());
        }
    }

    /**
     * Display customer details
     */
    public function show($id)
    {
        try {
            $customer = User::where('role', 'customer')
                ->where(function($q) {
                    $q->whereNull('email')
                      ->orWhere('email', 'not like', '%@guestgmail.com%');
                })
                ->findOrFail($id);
            
            // Get customer orders
            $orders = $customer->orders()->with('items')->latest()->paginate(10);
            
            // Calculate customer statistics
            $totalSpent = $customer->orders()->sum('total_amount');
            $totalOrders = $customer->orders()->count();
            $avgOrderValue = $totalOrders > 0 ? $totalSpent / $totalOrders : 0;
            $lastOrderDate = $customer->orders()->latest()->value('created_at');
            
            $stats = [
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
                'avg_order_value' => $avgOrderValue,
                'last_order_date' => $lastOrderDate,
            ];
            
            // Get monthly order data for chart
            $monthlyStats = DB::table('orders')
                ->select(DB::raw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as order_count, SUM(total_amount) as total_amount'))
                ->where('user_id', $customer->id)
                ->whereYear('created_at', '>=', now()->subYear()->year)
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
            
            return view('admin.customers.show', compact('customer', 'orders', 'stats', 'monthlyStats'));
        } catch (\Exception $e) {
            Log::error('Error in CustomerController@show: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat detail pelanggan: ' . $e->getMessage());
        }
    }

    /**
     * Export customers data
     */
    public function export(Request $request)
    {
        try {
            // To be implemented - export customer data to CSV/Excel
            return redirect()->route('admin.customers.index')
                ->with('error', 'Fitur export data pelanggan belum tersedia.');
        } catch (\Exception $e) {
            Log::error('Error in CustomerController@export: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mengekspor data pelanggan.');
        }
    }
} 