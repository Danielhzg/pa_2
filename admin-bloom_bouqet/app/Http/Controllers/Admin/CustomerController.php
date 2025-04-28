<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function index()
    {
        try {
            $customers = DB::table('users')
                ->select('users.*', 
                    DB::raw('(SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id) as total_orders'),
                    DB::raw('(SELECT SUM(total_amount) FROM orders WHERE orders.user_id = users.id) as total_spent'))
                ->orderBy('users.created_at', 'desc')
                ->get();

            return view('admin.customers.index', compact('customers'));
        } catch (\Exception $e) {
            Log::error('Error fetching customers: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to fetch customers. Please try again.');
        }
    }

    public function show($id)
    {
        try {
            $customer = DB::table('users')
                ->where('id', $id)
                ->first();

            if (!$customer) {
                return redirect()->route('admin.customers.index')->with('error', 'Customer not found.');
            }

            // Get customer orders
            $orders = DB::table('orders')
                ->where('user_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Get order items for each order
            foreach ($orders as $order) {
                $order->items = DB::table('order_items')
                    ->where('order_id', $order->id)
                    ->get();
            }

            return view('admin.customers.show', compact('customer', 'orders'));
        } catch (\Exception $e) {
            Log::error('Error fetching customer details: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to fetch customer details. Please try again.');
        }
    }
} 