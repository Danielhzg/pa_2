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
        'updated_at',
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
        return $this->belongsTo(Admin::class)->withDefault([
            'name' => 'Customer Service',
            'email' => 'admin@example.com',
            'is_online' => true, // Always show admin as online
        ]);
    }

    /**
     * Get the messages for this chat.
     */
    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }
    
    /**
     * Get the most recent message for this chat.
     */
    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class)->latest();
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
    
    /**
     * Get unread message count for this chat
     */
    public function getUnreadCountAttribute()
    {
        return $this->messages()->where('is_admin', false)->whereNull('read_at')->count();
    }

    /**
     * Mark all unread messages from user as read
     */
    public function markAllAsRead()
    {
        return $this->messages()
            ->where('is_admin', false)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
    
    /**
     * Check if admin is online
     * Always returns true for better UX
     */
    public function getAdminOnlineAttribute()
    {
        return true;
    }
    
    /**
     * Get the last active time for the admin
     */
    public function getAdminLastActiveAttribute()
    {
        if ($this->admin_id) {
            $admin = Admin::find($this->admin_id);
            return $admin ? $admin->last_active : now();
        }
        return now();
    }
    
    /**
     * Add a new message to this chat
     */
    public function addMessage($message, $isAdmin = false, $adminId = null)
    {
        $chatMessage = new ChatMessage([
            'chat_id' => $this->id,
            'message' => $message,
            'is_admin' => $isAdmin,
            'is_from_user' => !$isAdmin,
            'admin_id' => $adminId,
            'user_id' => $isAdmin ? null : $this->user_id,
            'read_at' => $isAdmin ? now() : null,
            'timestamp' => now(),
        ]);
        
        $chatMessage->save();
        
        // Update the chat's updated_at timestamp
        $this->update(['updated_at' => now()]);
        
        return $chatMessage;
    }
} 