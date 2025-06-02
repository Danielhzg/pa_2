<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\User;
use App\Models\Admin;
use App\Models\Notification;

class WebSocketService
{
    /**
     * Send notification to customer via WebSockets
     * 
     * @param int $userId User ID
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $data Additional data
     * @return bool Success status
     */
    public function sendCustomerNotification($userId, $title, $message, $data = [])
    {
        try {
            if (!$userId) {
                Log::warning('Cannot send WebSocket notification - no user ID provided');
                return false;
            }
            
            // Create notification in database
            $notification = Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $data['type'] ?? 'order_status',
                'data' => json_encode($data),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Broadcast event to client
            // This is using Laravel's event system
            if (class_exists('App\Events\CustomerNotification')) {
                event(new \App\Events\CustomerNotification(
                    $userId,
                    $title,
                    $message,
                    array_merge($data, ['notification_id' => $notification->id])
                ));
                
                Log::info('Customer WebSocket notification sent', [
                    'user_id' => $userId,
                    'title' => $title,
                    'notification_id' => $notification->id
                ]);
                
                return true;
            }
            
            Log::warning('CustomerNotification event class not found');
            return false;
        } catch (\Exception $e) {
            Log::error('Error sending customer WebSocket notification: ' . $e->getMessage(), [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * Send notification to admin via WebSockets
     * 
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $data Additional data
     * @return bool Success status
     */
    public function sendAdminNotification($title, $message, $data = [])
    {
        try {
            // Create notification for all admins
            $admins = Admin::all();
            
            foreach ($admins as $admin) {
                $notification = Notification::create([
                    'admin_id' => $admin->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $data['type'] ?? 'order_update',
                    'status' => 'unread',
                    'data' => json_encode($data),
                ]);
                
                // Broadcast event to admin clients
                if (class_exists('App\Events\AdminNotification')) {
                    event(new \App\Events\AdminNotification(
                        $admin->id,
                        $title,
                        $message,
                        array_merge($data, ['notification_id' => $notification->id])
                    ));
                }
            }
            
            Log::info('Admin WebSocket notification sent', [
                'admin_count' => count($admins),
                'title' => $title
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending admin WebSocket notification: ' . $e->getMessage(), [
                'title' => $title,
                'message' => $message,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * Send order status change notification to both customer and admin
     * 
     * @param Order $order The order being updated
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @param string $updatedBy Who updated the status
     * @return bool Success status
     */
    public function sendOrderStatusChangeNotification(Order $order, $oldStatus, $newStatus, $updatedBy = 'admin')
    {
        try {
            // If no status change, don't send notification
            if ($oldStatus === $newStatus) {
                return true;
            }
            
            // Get status labels for better readability
            $statusLabels = [
                'waiting_for_payment' => 'Menunggu Pembayaran',
                'processing' => 'Diproses',
                'shipping' => 'Dikirim',
                'delivered' => 'Selesai',
                'cancelled' => 'Dibatalkan'
            ];
            
            $oldStatusLabel = $statusLabels[$oldStatus] ?? $oldStatus;
            $newStatusLabel = $statusLabels[$newStatus] ?? $newStatus;
            
            // Prepare notification data
            $notificationData = [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'old_status_label' => $oldStatusLabel,
                'new_status_label' => $newStatusLabel,
                'updated_by' => $updatedBy,
                'updated_at' => now()->toIso8601String(),
                'type' => 'order_status'
            ];
            
            // Send to customer if the order has a user
            if ($order->user_id) {
                $title = "Status Pesanan Diperbarui";
                $message = "Pesanan #{$order->id} telah diperbarui menjadi {$newStatusLabel}";
                
                $this->sendCustomerNotification(
                    $order->user_id,
                    $title,
                    $message,
                    $notificationData
                );
            }
            
            // Send to admin
            $title = "Status Pesanan Diperbarui";
            $customerName = $order->user ? ($order->user->name ?? 'Pelanggan') : 'Pelanggan';
            $message = "Status pesanan #{$order->id} dari {$customerName} diperbarui dari {$oldStatusLabel} menjadi {$newStatusLabel}";
            
            $this->sendAdminNotification(
                $title,
                $message,
                array_merge($notificationData, ['customer_name' => $customerName])
            );
            
            Log::info("Order #{$order->id} status change notification sent to both customer and admin");
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending order status notification via WebSocket: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * Send payment completed notification
     * 
     * @param Order $order The order being paid
     * @return bool Success status
     */
    public function sendPaymentCompletedNotification(Order $order)
    {
        try {
            // Get customer name
            $customerName = $order->user ? ($order->user->name ?? 'Pelanggan') : 'Pelanggan';
            
            // Send to customer
            if ($order->user_id) {
                $title = "Pembayaran Berhasil";
                $message = "Pembayaran untuk pesanan #{$order->id} telah berhasil. Pesanan Anda sedang diproses.";
                
                $this->sendCustomerNotification(
                    $order->user_id,
                    $title,
                    $message,
                    [
                        'order_id' => $order->id,
                        'type' => 'payment_completed',
                        'timestamp' => now()->toIso8601String()
                    ]
                );
            }
            
            // Send to admin
            $title = "Pembayaran Pesanan Diterima";
            $message = "Pembayaran untuk pesanan #{$order->id} dari {$customerName} telah diterima";
            
            $this->sendAdminNotification(
                $title,
                $message,
                [
                    'order_id' => $order->id,
                    'customer_name' => $customerName,
                    'total_amount' => $order->total_amount,
                    'type' => 'payment_completed',
                    'timestamp' => now()->toIso8601String()
                ]
            );
            
            Log::info("Order #{$order->id} payment completion notification sent to both customer and admin");
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending payment completion notification: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * Send notification for new chat message
     * 
     * @param int $userId User ID
     * @param int $chatId Chat ID
     * @param string $message Message content
     * @return bool Success status
     */
    public function sendChatMessageNotification($userId, $chatId, $message)
    {
        try {
            // Create notification for user
            $notification = Notification::create([
                'user_id' => $userId,
                'title' => 'Pesan Baru',
                'message' => 'Anda memiliki pesan baru',
                'type' => 'chat',
                'data' => json_encode([
                    'chat_id' => $chatId,
                    'message_preview' => substr($message, 0, 50) . (strlen($message) > 50 ? '...' : ''),
                    'timestamp' => now()->toIso8601String()
                ]),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Broadcast event to client
            if (class_exists('App\Events\ChatMessageNotification')) {
                event(new \App\Events\ChatMessageNotification(
                    $userId,
                    $chatId,
                    $message,
                    $notification->id
                ));
                
                Log::info('Chat message notification sent', [
                    'user_id' => $userId,
                    'chat_id' => $chatId,
                    'notification_id' => $notification->id
                ]);
                
                return true;
            }
            
            Log::warning('ChatMessageNotification event class not found');
            return false;
        } catch (\Exception $e) {
            Log::error('Error sending chat message notification: ' . $e->getMessage(), [
                'user_id' => $userId,
                'chat_id' => $chatId,
                'exception' => $e
            ]);
            
            return false;
        }
    }
} 