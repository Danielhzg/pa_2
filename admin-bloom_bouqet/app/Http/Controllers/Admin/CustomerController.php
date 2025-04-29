<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        try {
            $query = User::query()->where('role', 'customer');
            
            // Search by name or email
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            $customers = $query->withCount('orders')
                              ->withSum('orders', 'total_amount')
                              ->orderBy('created_at', 'desc')
                              ->paginate(10);
            
            return view('admin.customers.index', compact('customers'));
        } catch (\Exception $e) {
            Log::error('Error in CustomerController@index: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat data pelanggan.');
        }
    }

    /**
     * Display customer details
     */
    public function show(User $customer)
    {
        try {
            // Ensure this is a customer
            if ($customer->role !== 'customer') {
                return redirect()->route('admin.customers.index')
                    ->with('error', 'User bukan merupakan pelanggan.');
            }
            
            // Get customer's orders with pagination
            $orders = Order::where('user_id', $customer->id)
                          ->orderBy('created_at', 'desc')
                          ->paginate(5);
            
            // Get customer statistics
            $stats = [
                'total_orders' => Order::where('user_id', $customer->id)->count(),
                'total_spent' => Order::where('user_id', $customer->id)->sum('total_amount'),
                'last_order' => Order::where('user_id', $customer->id)->latest()->first(),
                'avg_order_value' => Order::where('user_id', $customer->id)->avg('total_amount') ?? 0,
            ];
            
            return view('admin.customers.show', compact('customer', 'orders', 'stats'));
        } catch (\Exception $e) {
            Log::error('Error in CustomerController@show: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat detail pelanggan.');
        }
    }
} 