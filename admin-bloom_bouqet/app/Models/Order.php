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
        'is_read',
        'payment_deadline',
    ];

    // Order status constants
    const STATUS_WAITING_FOR_PAYMENT = 'waiting_for_payment';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPING = 'shipping';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    
    // Payment status constants
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_EXPIRED = 'expired';
    const PAYMENT_REFUNDED = 'refunded';

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
        'order_items' => 'array',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'payment_deadline' => 'datetime',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Guest User',
            'email' => 'guest@example.com'
        ]);
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
     * This maintains backward compatibility with the old relationship
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
     * Get the carts associated with this order.
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get formatted status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $statusLabels = [
            self::STATUS_WAITING_FOR_PAYMENT => 'Menunggu Pembayaran',
            self::STATUS_PROCESSING => 'Pesanan Sedang Diproses',
            self::STATUS_SHIPPING => 'Pesanan Sedang Diantar',
            self::STATUS_DELIVERED => 'Pesanan Selesai',
            self::STATUS_CANCELLED => 'Pesanan Dibatalkan',
        ];
        
        return $statusLabels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get formatted payment status label.
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        $paymentLabels = [
            self::PAYMENT_PENDING => 'Menunggu Pembayaran',
            self::PAYMENT_PAID => 'Pembayaran Berhasil',
            self::PAYMENT_FAILED => 'Pembayaran Gagal',
            self::PAYMENT_EXPIRED => 'Pembayaran Kadaluarsa',
            self::PAYMENT_REFUNDED => 'Pembayaran Dikembalikan',
        ];
        
        return $paymentLabels[$this->payment_status] ?? ucfirst($this->payment_status);
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
     * Check if order is waiting for payment.
     */
    public function isWaitingForPayment(): bool
    {
        return $this->status === 'waiting_for_payment';
    }

    /**
     * Check if order is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if order is being shipped.
     */
    public function isShipping(): bool
    {
        return $this->status === 'shipping';
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
     * Update order status with appropriate timestamp
     */
    public function updateStatus(string $status)
    {
        $this->status = $status;
        
        switch ($status) {
            case self::STATUS_PROCESSING:
                // No specific timestamp for processing
                break;
                
            case self::STATUS_SHIPPING:
                $this->shipped_at = now();
                break;
                
            case self::STATUS_DELIVERED:
                $this->delivered_at = now();
                break;
                
            case self::STATUS_CANCELLED:
                $this->cancelled_at = now();
                break;
        }
        
        $this->save();
    }
    
    /**
     * Update payment status with appropriate timestamp
     */
    public function updatePaymentStatus(string $paymentStatus)
    {
        $this->payment_status = $paymentStatus;
        
        if ($paymentStatus === self::PAYMENT_PAID) {
            $this->paid_at = now();
            
            // If payment is successful, automatically update order status to processing
            $this->status = self::STATUS_PROCESSING;
        }
        
        $this->save();
    }

    /**
     * Generate a sales report for this order.
     */
    public function generateSalesReport($title = null, $description = null)
    {
        $reportNumber = Report::generateReportNumber();
        $reportTitle = $title ?? 'Sales Report for Order #' . $this->order_id;
        $reportDescription = $description ?? 'Automatically generated sales report for order ' . $this->order_id;
        
        // Get item details for the report - support both JSON and relation
        if (isset($this->order_items) && !empty($this->order_items)) {
            // Use JSON data
            $itemsCollection = collect($this->order_items);
            $itemDetails = $itemsCollection->map(function($item) {
                return [
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['name'] ?? 'Unknown Product',
                    'quantity' => $item['quantity'] ?? 0,
                    'price' => $item['price'] ?? 0,
                    'subtotal' => $item['subtotal'] ?? ($item['price'] * $item['quantity']),
                ];
            })->toArray();
            $totalItems = $itemsCollection->sum('quantity');
        } else {
            // Fall back to relation
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
            $totalItems = $items->sum('quantity');
        }
        
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
            'total_items' => $totalItems,
            'status' => 'generated',
            'created_by' => auth()->guard('admin')->id(), // If an admin is creating the report
        ]);
    }

    /**
     * Get the order items from the JSON column.
     * This method should be used for new code.
     */
    public function getOrderItemsAttribute($value)
    {
        // If the value is already decoded, return it
        if (is_array($value)) {
            return $value;
        }
        
        // Check if we have json data
        if (!empty($value)) {
            return json_decode($value, true);
        }
        
        // If no JSON data, try to get items from the relationship
        $relatedItems = $this->items()->get();
        if ($relatedItems->isNotEmpty()) {
            // Convert the collection to an array and cache it
            $itemsArray = $relatedItems->toArray();
            $this->attributes['order_items'] = json_encode($itemsArray);
            return $itemsArray;
        }
        
        return [];
    }

    /**
     * Set the order items JSON attribute.
     */
    public function setOrderItemsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['order_items'] = json_encode($value);
        } else {
            $this->attributes['order_items'] = $value;
        }
    }

    /**
     * Get a collection of order items as OrderItem models.
     * This provides full model functionality for the items.
     */
    public function getItemsCollection()
    {
        $items = $this->order_items;
        
        if (empty($items)) {
            return collect([]);
        }
        
        // Convert array items to OrderItem models
        return collect($items)->map(function($item) {
            return new OrderItem((array) $item);
        });
    }
}
