<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'title',
        'message',
        'type',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    public function isUnread()
    {
        return !$this->is_read;
    }

    // Scope for unread notifications
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    // Scope for specific user
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Scope for specific order
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    // Create notification for order status change
    public static function createOrderStatusNotification($orderId, $newStatus, $userId = null)
    {
        $statusMessages = [
            'waiting_for_payment' => 'Your order is waiting for payment.',
            'processing' => 'Your order is being processed.',
            'shipping' => 'Your order is being shipped.',
            'delivered' => 'Your order has been delivered.',
            'cancelled' => 'Your order has been cancelled.'
        ];

        $message = $statusMessages[$newStatus] ?? "Your order status has been updated to: $newStatus";

        return self::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'title' => 'Order Status Updated',
            'message' => $message,
            'type' => 'status_update',
            'is_read' => false
        ]);
    }

    // Create notification for payment completion
    public static function createPaymentNotification($orderId, $userId = null)
    {
        return self::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'title' => 'Payment Completed',
            'message' => "Your payment for order #$orderId has been completed successfully.",
            'type' => 'payment_success',
            'is_read' => false
        ]);
    }
} 