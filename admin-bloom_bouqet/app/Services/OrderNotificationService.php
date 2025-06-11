<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

class OrderNotificationService
{
    /**
     * Send notification when new order is created
     */
    public function notifyNewOrder(Order $order)
    {
        try {
            Log::info("Sending new order notification for order: {$order->order_id}");

            // Store notification in cache for admin dashboard
            $customerName = $this->getCustomerName($order);
            $customerEmail = $this->getCustomerEmail($order);

            $this->storeAdminNotification([
                'type' => 'new_order',
                'order_id' => $order->order_id,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at->toISOString(),
                'message' => "Pesanan baru #{$order->order_id} dari {$customerName} ({$customerEmail}) - Rp " . number_format($order->total_amount, 0, ',', '.'),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'timestamp' => time(),
            ]);

            // Mark order as unread for admin attention
            $order->update(['is_read' => false]);

            // Trigger real-time event
            Event::dispatch('order.created', $order);

            Log::info("New order notification sent successfully");

        } catch (\Exception $e) {
            Log::error("Failed to send new order notification: " . $e->getMessage());
        }
    }
    
    /**
     * Send notification when payment status changes
     */
    public function notifyPaymentStatusChange(Order $order, string $oldStatus, string $newStatus)
    {
        try {
            Log::info("Sending payment status change notification for order: {$order->order_id}");
            
            // Store notification in cache for admin dashboard
            $this->storeAdminNotification([
                'type' => 'payment_status_change',
                'order_id' => $order->order_id,
                'customer_name' => $this->getCustomerName($order),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'created_at' => now()->toISOString(),
                'message' => "Status pembayaran pesanan #{$order->order_id} berubah dari {$oldStatus} ke {$newStatus}",
            ]);
            
            // If payment is completed, automatically update order status
            if ($newStatus === 'paid' && $order->status === 'waiting_for_payment') {
                $this->autoUpdateOrderStatus($order);
            }
            
            // Trigger real-time event
            Event::dispatch('order.payment_updated', $order);
            
            Log::info("Payment status change notification sent successfully");
            
        } catch (\Exception $e) {
            Log::error("Failed to send payment status change notification: " . $e->getMessage());
        }
    }
    
    /**
     * Send notification when order status changes
     */
    public function notifyOrderStatusChange(Order $order, string $oldStatus, string $newStatus)
    {
        try {
            Log::info("Sending order status change notification for order: {$order->order_id}");
            
            // Store notification in cache for admin dashboard
            $this->storeAdminNotification([
                'type' => 'order_status_change',
                'order_id' => $order->order_id,
                'customer_name' => $this->getCustomerName($order),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'created_at' => now()->toISOString(),
                'message' => "Status pesanan #{$order->order_id} berubah dari {$this->getStatusLabel($oldStatus)} ke {$this->getStatusLabel($newStatus)}",
            ]);
            
            // Trigger real-time event
            Event::dispatch('order.status_updated', $order);
            
            Log::info("Order status change notification sent successfully");
            
        } catch (\Exception $e) {
            Log::error("Failed to send order status change notification: " . $e->getMessage());
        }
    }
    
    /**
     * Automatically update order status when payment is completed
     */
    private function autoUpdateOrderStatus(Order $order)
    {
        try {
            $oldStatus = $order->status;
            $order->updateStatus(Order::STATUS_PROCESSING);
            $order->status_updated_by = 'payment_system';
            $order->save();
            
            Log::info("Auto-updated order status from {$oldStatus} to processing for order: {$order->order_id}");
            
            // Send notification for status change
            $this->notifyOrderStatusChange($order, $oldStatus, Order::STATUS_PROCESSING);
            
        } catch (\Exception $e) {
            Log::error("Failed to auto-update order status: " . $e->getMessage());
        }
    }
    
    /**
     * Store notification in cache for admin dashboard (public method for API)
     */
    public function storeAdminNotification(array $notification)
    {
        try {
            $cacheKey = 'admin_notifications';
            $notifications = Cache::get($cacheKey, []);
            
            // Add timestamp and unique ID
            $notification['id'] = uniqid();
            $notification['timestamp'] = now()->timestamp;
            
            // Add to beginning of array
            array_unshift($notifications, $notification);
            
            // Keep only last 50 notifications
            $notifications = array_slice($notifications, 0, 50);
            
            // Store in cache for 24 hours
            Cache::put($cacheKey, $notifications, 60 * 24);
            
            Log::info("Stored admin notification: " . $notification['message']);
            
        } catch (\Exception $e) {
            Log::error("Failed to store admin notification: " . $e->getMessage());
        }
    }
    
    /**
     * Get customer name from order
     */
    private function getCustomerName(Order $order): string
    {
        if ($order->user) {
            return $order->user->name ?? $order->user->full_name ?? $order->user->username ?? 'Customer';
        }

        // Try to get name from shipping address
        $shippingAddress = json_decode($order->shipping_address, true);
        if (is_array($shippingAddress) && isset($shippingAddress['name'])) {
            return $shippingAddress['name'];
        }

        return 'Guest Customer';
    }

    /**
     * Get customer email from order
     */
    private function getCustomerEmail(Order $order): string
    {
        // First try to get from associated user
        if ($order->user && $order->user->email) {
            return $order->user->email;
        }

        // Try to get email from shipping address
        $shippingAddress = json_decode($order->shipping_address, true);
        if (is_array($shippingAddress) && isset($shippingAddress['email'])) {
            return $shippingAddress['email'];
        }

        return 'customer@example.com';
    }
    
    /**
     * Get status label in Indonesian
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'waiting_for_payment' => 'Menunggu Pembayaran',
            'processing' => 'Sedang Diproses',
            'shipping' => 'Sedang Diantar',
            'delivered' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];
        
        return $labels[$status] ?? ucfirst($status);
    }
    
    /**
     * Get unread notifications count for admin
     */
    public function getUnreadNotificationsCount(): int
    {
        try {
            $notifications = Cache::get('admin_notifications', []);
            return count($notifications);
        } catch (\Exception $e) {
            Log::error("Failed to get unread notifications count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get all notifications for admin
     */
    public function getAdminNotifications(int $limit = 20): array
    {
        try {
            $notifications = Cache::get('admin_notifications', []);
            return array_slice($notifications, 0, $limit);
        } catch (\Exception $e) {
            Log::error("Failed to get admin notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clear all notifications
     */
    public function clearNotifications(): bool
    {
        try {
            Cache::forget('admin_notifications');
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to clear notifications: " . $e->getMessage());
            return false;
        }
    }
}
