<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Create a new order 
     */
    public function createOrder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|string',
                'items' => 'required|array',
                'shipping_address' => 'required|string',
                'phone_number' => 'required|string',
                'total_amount' => 'required|numeric',
                'shipping_cost' => 'required|numeric',
                'payment_method' => 'required|string',
                'status' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get user ID if authenticated
            $user = $request->user();
            $userId = $user ? $user->id : null;

            // Store order in database
            $orderId = DB::table('orders')->insertGetId([
                'order_id' => $request->order_id,
                'user_id' => $userId,
                'shipping_address' => $request->shipping_address,
                'phone_number' => $request->phone_number,
                'total_amount' => $request->total_amount,
                'shipping_cost' => $request->shipping_cost,
                'payment_method' => $request->payment_method,
                'status' => $request->status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Store order items
            foreach ($request->items as $item) {
                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $item['id'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'id' => $orderId,
                    'order_id' => $request->order_id,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Order Creation Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $orderId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:pending,processing,completed,cancelled',
                'payment_status' => 'required|string|in:pending,success,failed,expired',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update order status
            $updated = DB::table('orders')
                ->where('order_id', $orderId)
                ->update([
                    'status' => $request->status,
                    'payment_status' => $request->payment_status,
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Order Update Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order details
     */
    public function getOrder($orderId)
    {
        try {
            // Get order details
            $order = DB::table('orders')
                ->where('order_id', $orderId)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Get order items
            $items = DB::table('order_items')
                ->where('order_id', $order->id)
                ->get();

            $orderData = [
                'id' => $order->id,
                'order_id' => $order->order_id,
                'user_id' => $order->user_id,
                'shipping_address' => $order->shipping_address,
                'phone_number' => $order->phone_number,
                'total_amount' => $order->total_amount,
                'shipping_cost' => $order->shipping_cost,
                'payment_method' => $order->payment_method,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'created_at' => $order->created_at,
                'items' => $items,
            ];

            return response()->json([
                'success' => true,
                'data' => $orderData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Order Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user orders
     */
    public function getUserOrders(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            // Get user orders
            $orders = DB::table('orders')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Get order items for each order
            $ordersWithItems = $orders->map(function ($order) {
                $items = DB::table('order_items')
                    ->where('order_id', $order->id)
                    ->get();

                return [
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'shipping_address' => $order->shipping_address,
                    'phone_number' => $order->phone_number,
                    'total_amount' => $order->total_amount,
                    'shipping_cost' => $order->shipping_cost,
                    'payment_method' => $order->payment_method,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'created_at' => $order->created_at,
                    'items' => $items,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $ordersWithItems,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get User Orders Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user orders: ' . $e->getMessage(),
            ], 500);
        }
    }
} 