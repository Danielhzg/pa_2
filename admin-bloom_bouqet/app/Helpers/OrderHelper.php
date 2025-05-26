<?php

namespace App\Helpers;

use App\Models\Order;
use Illuminate\Support\Facades\Schema;

class OrderHelper
{
    /**
     * Get order items consistently regardless of storage method
     *
     * @param Order $order
     * @return array
     */
    public static function getOrderItems(Order $order)
    {
        // First try to get items from the JSON column
        if (Schema::hasColumn('orders', 'order_items') && !empty($order->order_items)) {
            return $order->order_items;
        }
        
        // Fall back to the relationship
        return $order->items->map(function($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'subtotal' => $item->price * $item->quantity,
                'image' => $item->product ? $item->product->getPrimaryImage() : null,
            ];
        })->toArray();
    }
    
    /**
     * Get the total number of items in an order
     *
     * @param Order $order
     * @return int
     */
    public static function getTotalItems(Order $order)
    {
        $items = self::getOrderItems($order);
        
        return collect($items)->sum('quantity');
    }
    
    /**
     * Get the subtotal of an order (sum of item prices * quantities)
     *
     * @param Order $order
     * @return float
     */
    public static function getSubtotal(Order $order)
    {
        $items = self::getOrderItems($order);
        
        return collect($items)->sum(function($item) {
            return $item['price'] * $item['quantity'];
        });
    }
} 