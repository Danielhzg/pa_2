<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryTracking extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'delivery_tracking';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'tracking_number',
        'courier',
        'status',
        'current_location',
        'tracking_history',
        'estimated_delivery',
        'shipped_at',
        'delivered_at',
        'receiver_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tracking_history' => 'array',
        'estimated_delivery' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order that this tracking belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if the delivery is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the delivery is in transit.
     */
    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }

    /**
     * Check if the delivery is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if the delivery has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Add a tracking update to the history.
     */
    public function addTrackingUpdate(string $status, string $location, string $message = null): self
    {
        $history = $this->tracking_history ?? [];
        
        $history[] = [
            'status' => $status,
            'location' => $location,
            'message' => $message,
            'timestamp' => now()->toDateTimeString(),
        ];
        
        $this->tracking_history = $history;
        $this->current_location = $location;
        $this->status = $status;
        
        if ($status === 'delivered') {
            $this->delivered_at = now();
        } elseif ($status === 'in_transit' && $this->shipped_at === null) {
            $this->shipped_at = now();
        }
        
        $this->save();
        
        return $this;
    }
} 