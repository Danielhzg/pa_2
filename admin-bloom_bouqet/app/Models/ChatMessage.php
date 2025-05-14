<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'read_at' => 'datetime',
        'is_system' => 'boolean',
    ];

    /**
     * Get the chat that owns the message.
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class);
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
} 