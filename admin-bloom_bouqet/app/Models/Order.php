<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'admin_id',
        'shipping_address',
        'phone_number',
        'subtotal',
        'shipping_cost',
        'total_amount',
        'status',
        'payment_status',
        'payment_method',
        'midtrans_token',
        'midtrans_redirect_url',
        'payment_details',
        'qr_code_data',
        'qr_code_url',
        'notes',
        'order_items',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who processed the order.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    
    /**
     * Get the products in this order.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'order_items')
                    ->withPivot('name', 'price', 'quantity')
                    ->withTimestamps();
    }
    
    /**
     * Get the reports associated with this order.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Get the delivery tracking for this order.
     */
    public function deliveryTracking(): HasMany
    {
        return $this->hasMany(DeliveryTracking::class);
    }

    /**
     * Get formatted status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get formatted payment status label.
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return ucfirst($this->payment_status);
    }

    /**
     * Scope a query to only include orders with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include orders with a specific payment status.
     */
    public function scopeWithPaymentStatus($query, string $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Check if order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if order is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if order is shipped.
     */
    public function isShipped(): bool
    {
        return $this->status === 'shipped';
    }

    /**
     * Check if order is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if order is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Check if payment is pending.
     */
    public function isPaymentPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Check if payment is paid.
     */
    public function isPaymentPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if payment failed.
     */
    public function isPaymentFailed(): bool
    {
        return $this->payment_status === 'failed';
    }

    /**
     * Check if payment expired.
     */
    public function isPaymentExpired(): bool
    {
        return $this->payment_status === 'expired';
    }

    /**
     * Check if payment is refunded.
     */
    public function isPaymentRefunded(): bool
    {
        return $this->payment_status === 'refunded';
    }

    /**
     * Get the total number of items in this order.
     */
    public function getTotalItemsAttribute()
    {
        // First try from items relationship if it exists
        if ($this->items()->exists()) {
            return $this->items()->sum('quantity');
        }
        
        // Otherwise, try to calculate from order_items JSON field
        $orderItems = json_decode($this->order_items ?? '', true);
        
        if (is_array($orderItems)) {
            return collect($orderItems)->sum('quantity');
        }
        
        // Default if no data is available
        return 0;
    }

    /**
     * Generate a sales report for this order.
     */
    public function generateSalesReport($title = null, $description = null)
    {
        $reportNumber = Report::generateReportNumber();
        $reportTitle = $title ?? 'Sales Report for Order #' . $this->order_id;
        $reportDescription = $description ?? 'Automatically generated sales report for order ' . $this->order_id;
        
        // Get item details for the report
        $items = $this->items()->with('product')->get();
        $itemDetails = $items->map(function($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
            ];
        })->toArray();
        
        // Create report details
        $details = [
            'order_date' => $this->created_at->format('Y-m-d H:i:s'),
            'customer' => [
                'id' => $this->user_id,
                'name' => $this->user ? $this->user->full_name : 'Guest',
                'address' => $this->shipping_address,
                'phone' => $this->phone_number,
            ],
            'payment' => [
                'method' => $this->payment_method,
                'status' => $this->payment_status,
            ],
            'items' => $itemDetails,
        ];
        
        // Create the report
        return Report::create([
            'order_id' => $this->id,
            'report_number' => $reportNumber,
            'type' => 'sales',
            'title' => $reportTitle,
            'description' => $reportDescription,
            'details' => $details,
            'period_start' => $this->created_at->startOfDay(),
            'period_end' => $this->created_at->endOfDay(),
            'total_amount' => $this->total_amount,
            'total_items' => $items->sum('quantity'),
            'status' => 'generated',
            'created_by' => auth()->guard('admin')->id(), // If an admin is creating the report
        ]);
    }
}
