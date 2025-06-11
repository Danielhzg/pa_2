<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notification for new order
     */
    public function sendNewOrderNotification(Order $order)
    {
        try {
            // Get all admins
            $admins = Admin::all();
            
            // Get customer name from order or shipping address
            $customerName = 'Pelanggan';
            if ($order->user) {
                $customerName = $order->user->name;
            } elseif ($order->shipping_address && isset($order->shipping_address['name'])) {
                $customerName = $order->shipping_address['name'];
            }
            
            foreach ($admins as $admin) {
                // Create notification for each admin
                Notification::create([
                    'admin_id' => $admin->id,
                    'type' => 'order',
                    'title' => 'Pesanan Baru',
                    'message' => 'Pesanan baru telah dibuat',
                    'status' => 'unread',
                    'data' => [
                        'order_id' => $order->id,
                        'customer_name' => $customerName,
                        'total' => $order->total_amount,
                        'message' => "Pesanan baru #{$order->id} telah dibuat oleh {$customerName}",
                        'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                        'items_count' => $order->items->count(),
                        'payment_status' => $order->payment_status,
                        'order_status' => $order->status,
                    ],
                ]);
            }
            
            Log::info('New order notification sent for order #' . $order->id);
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending new order notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification for order status change
     */
    public function sendOrderStatusNotification(Order $order, $oldStatus, $newStatus)
    {
        try {
            // Get all admins
            $admins = Admin::all();
            
            // Get customer name from order or shipping address
            $customerName = 'Pelanggan';
            if ($order->user) {
                $customerName = $order->user->name;
            } elseif ($order->shipping_address && isset($order->shipping_address['name'])) {
                $customerName = $order->shipping_address['name'];
            }
            
            // Get readable status labels
            $oldStatusLabel = $this->getStatusLabel($oldStatus);
            $newStatusLabel = $this->getStatusLabel($newStatus);
            
            foreach ($admins as $admin) {
                // Create notification for each admin
                Notification::create([
                    'admin_id' => $admin->id,
                    'type' => 'order',
                    'title' => 'Status Pesanan Diubah',
                    'message' => "Status pesanan #{$order->id} telah diubah dari {$oldStatusLabel} menjadi {$newStatusLabel}",
                    'status' => 'unread',
                    'data' => [
                        'order_id' => $order->id,
                        'customer_name' => $customerName,
                        'total' => $order->total_amount,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'old_status_label' => $oldStatusLabel,
                        'new_status_label' => $newStatusLabel,
                        'message' => "Status pesanan #{$order->id} telah diubah dari {$oldStatusLabel} menjadi {$newStatusLabel}",
                        'updated_at' => now()->format('Y-m-d H:i:s'),
                        'items_count' => $order->items->count(),
                        'payment_status' => $order->payment_status,
                    ],
                ]);
            }
            
            Log::info('Order status notification sent for order #' . $order->id);
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending order status notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification for payment status change
     */
    public function sendPaymentStatusNotification(Order $order, $oldStatus, $newStatus)
    {
        try {
            // Get all admins
            $admins = Admin::all();
            
            // Get customer name from order or shipping address
            $customerName = 'Pelanggan';
            if ($order->user) {
                $customerName = $order->user->name;
            } elseif ($order->shipping_address && isset($order->shipping_address['name'])) {
                $customerName = $order->shipping_address['name'];
            }
            
            // Get readable status labels
            $oldStatusLabel = $this->getPaymentStatusLabel($oldStatus);
            $newStatusLabel = $this->getPaymentStatusLabel($newStatus);
            
            foreach ($admins as $admin) {
                // Create notification for each admin
                Notification::create([
                    'admin_id' => $admin->id,
                    'type' => 'payment',
                    'title' => 'Status Pembayaran Diubah',
                    'message' => "Status pembayaran pesanan #{$order->id} telah diubah dari {$oldStatusLabel} menjadi {$newStatusLabel}",
                    'status' => 'unread',
                    'data' => [
                        'order_id' => $order->id,
                        'customer_name' => $customerName,
                        'total' => $order->total_amount,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'old_status_label' => $oldStatusLabel,
                        'new_status_label' => $newStatusLabel,
                        'message' => "Status pembayaran pesanan #{$order->id} telah diubah dari {$oldStatusLabel} menjadi {$newStatusLabel}",
                        'updated_at' => now()->format('Y-m-d H:i:s'),
                        'items_count' => $order->items->count(),
                        'order_status' => $order->status,
                    ],
                ]);
            }
            
            Log::info('Payment status notification sent for order #' . $order->id);
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending payment status notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get readable status label for order status
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'waiting_for_payment' => 'Menunggu Pembayaran',
            'processing' => 'Pesanan Sedang Diproses',
            'shipping' => 'Pesanan Sedang Diantar',
            'delivered' => 'Pesanan Selesai',
            'cancelled' => 'Pesanan Dibatalkan'
        ];

        return $labels[$status] ?? $status;
    }
    
    /**
     * Get readable status label for payment status
     */
    private function getPaymentStatusLabel(string $status): string
    {
        $labels = [
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Pembayaran Diterima',
            'failed' => 'Pembayaran Gagal',
            'expired' => 'Pembayaran Kedaluwarsa',
            'refunded' => 'Pembayaran Dikembalikan'
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Send notification for Shopee order
     */
    public function sendShopeeOrderNotification(Order $order)
    {
        try {
            // Get all admins
            $admins = Admin::all();
            
            // Get customer name from order or shipping address
            $customerName = 'Pelanggan Shopee';
            if ($order->user) {
                $customerName = $order->user->name;
            } elseif ($order->shipping_address && isset($order->shipping_address['name'])) {
                $customerName = $order->shipping_address['name'];
            }
            
            // Create a single notification for all admins (since we removed admin_id)
            Notification::create([
                'user_id' => null, // For admin notifications
                'order_id' => $order->order_id ?? 'ORDER-' . $order->id,
                'type' => 'new_order',
                'title' => 'Pesanan Baru dari Shopee',
                'message' => "Pesanan baru dari Shopee #{$order->order_id} telah dibuat oleh {$customerName}",
                'is_read' => false,
            ]);
            
            Log::info('New Shopee order notification sent for order #' . $order->id);
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending Shopee order notification: ' . $e->getMessage());
            return false;
        }
    }
} 