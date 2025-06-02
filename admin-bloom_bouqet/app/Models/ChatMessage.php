<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'message',
        'is_admin',
        'read_at',
        'attachment_url',
        'is_system',
        'user_id',
        'admin_id',
        'client_message_id',
        'product_images',
        'timestamp',
        'is_read',
        'status',
        'is_from_user',
        'order_id',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'read_at' => 'datetime',
        'is_system' => 'boolean',
        'is_read' => 'boolean',
        'is_from_user' => 'boolean',
        'product_images' => 'array',
        'timestamp' => 'datetime',
    ];

    /**
     * Get the chat that owns the message.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Get the user associated with this message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin associated with this message.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Get the order associated with this message.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope a query to only include unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope a query to only include admin messages.
     */
    public function scopeFromAdmin($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Scope a query to only include user messages.
     */
    public function scopeFromUser($query)
    {
        return $query->where('is_admin', false);
    }

    /**
     * Scope a query to only include system messages.
     */
    public function scopeSystemMessages($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to filter messages by order.
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }
} 