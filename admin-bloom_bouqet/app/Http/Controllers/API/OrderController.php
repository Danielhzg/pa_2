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
                'id' => 'required|string',
                'items' => 'required|array',
                'deliveryAddress' => 'required|array',
                'subtotal' => 'required|numeric',
                'shippingCost' => 'required|numeric',
                'total' => 'required|numeric',
                'paymentMethod' => 'required|string',
                'paymentStatus' => 'required|string',
                'orderStatus' => 'required|string',
                'qrCodeUrl' => 'nullable|string',
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
                'order_id' => $request->id,
                'user_id' => $userId,
                'shipping_address' => json_encode($request->deliveryAddress),
                'phone_number' => $request->deliveryAddress['phone'] ?? '',
                'total_amount' => $request->total,
                'shipping_cost' => $request->shippingCost,
                'payment_method' => $request->paymentMethod,
                'status' => $request->orderStatus,
                'payment_status' => $request->paymentStatus,
                'qr_code_url' => $request->qrCodeUrl,
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

            // Get the created order with items
            $order = DB::table('orders')->where('id', $orderId)->first();
            $items = DB::table('order_items')->where('order_id', $orderId)->get();

            $orderData = [
                'id' => $order->order_id,
                'items' => $items,
                'deliveryAddress' => json_decode($order->shipping_address, true),
                'subtotal' => $order->total_amount - $order->shipping_cost,
                'shippingCost' => $order->shipping_cost,
                'total' => $order->total_amount,
                'paymentMethod' => $order->payment_method,
                'paymentStatus' => $order->payment_status,
                'orderStatus' => $order->status,
                'createdAt' => $order->created_at,
                'qrCodeUrl' => $order->qr_code_url,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $orderData
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