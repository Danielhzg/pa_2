<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        try {
            $orders = DB::table('orders')
                ->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->select('orders.*', 'users.name as user_name', 'users.email as user_email')
                ->orderBy('orders.created_at', 'desc')
                ->get();

            // Get order items for each order
            foreach ($orders as $order) {
                $order->items = DB::table('order_items')
                    ->where('order_id', $order->id)
                    ->get();
            }

            return view('admin.orders.index', compact('orders'));
        } catch (\Exception $e) {
            Log::error('Error fetching orders: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to fetch orders. Please try again.');
        }
    }

    public function show($id)
    {
        try {
            $order = DB::table('orders')
                ->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->select('orders.*', 'users.name as user_name', 'users.email as user_email')
                ->where('orders.id', $id)
                ->first();

            if (!$order) {
                return redirect()->route('admin.orders.index')->with('error', 'Order not found.');
            }

            $order->items = DB::table('order_items')
                ->where('order_id', $order->id)
                ->get();

            return view('admin.orders.show', compact('order'));
        } catch (\Exception $e) {
            Log::error('Error fetching order details: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to fetch order details. Please try again.');
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'status' => 'required|string|in:pending,processing,completed,cancelled',
                'payment_status' => 'required|string|in:pending,success,failed,expired',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->with('error', 'Invalid status values.');
            }

            $updated = DB::table('orders')
                ->where('id', $id)
                ->update([
                    'status' => $request->status,
                    'payment_status' => $request->payment_status,
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                return redirect()->back()->with('error', 'Order not found.');
            }

            return redirect()->route('admin.orders.show', $id)
                ->with('success', 'Order status updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating order status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update order status. Please try again.');
        }
    }
} 