<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admin_id',
        'message',
        'sender_type',
        'is_read',
        'attachment',
        'status',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Get the user that owns the chat.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin that is assigned to the chat.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Check if the message is from a user.
     */
    public function isFromUser()
    {
        return $this->sender_type === 'user';
    }

    /**
     * Check if the message is from an admin.
     */
    public function isFromAdmin()
    {
        return $this->sender_type === 'admin';
    }

    /**
     * Get the sender of this message.
     */
    public function getSenderAttribute()
    {
        return $this->sender_type === 'user'
            ? $this->user
            : $this->admin;
    }

    /**
     * Scope a query to only include unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to get chat messages by conversation.
     * This helps get the conversation thread between a specific user and admin.
     */
    public function scopeConversation($query, $userId, $adminId = null)
    {
        return $query->where('user_id', $userId)
                    ->where('admin_id', $adminId);
    }
} 