<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class Order extends Model
{
    use HasFactory;

    // Order Status Constants
    const STATUS_WAITING_PAYMENT = 'waiting_for_payment';
    const STATUS_WAITING_FOR_PAYMENT = 'waiting_for_payment'; // Alias for compatibility
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPING = 'shipping';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    // Payment Status Constants
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_EXPIRED = 'expired';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'admin_id',
        'shipping_address',
        'phone_number',
        'subtotal',
        'shipping_cost',
        'total_amount',
        'status',
        'payment_status',
        'payment_method',
        'payment_details',
        'order_items',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'is_read',
        'payment_deadline',
        'status_updated_by',
        'status_updated_at',
    ];

    // Additional payment status constant
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
     * Now completely using the JSON column rather than a separate table
     */
    public function items()
    {
        try {
            // If we have direct data request for items, convert JSON to collection
            if ($this->relationLoaded('items')) {
                return $this->getRelation('items');
            }
            
            // Create a relationship from JSON data
            $items = $this->getItemsCollection();
            
            // Set the relation
            $this->setRelation('items', $items);
            
            return $this->getRelation('items');
        } catch (\Exception $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error('Error in Order items() method: ' . $e->getMessage());
            
            // Return empty collection as fallback
            $emptyCollection = new \Illuminate\Database\Eloquent\Collection();
            $this->setRelation('items', $emptyCollection);
            return $this->getRelation('items');
        }
    }
    
    /**
     * Get the products in this order.
     * Now uses the product_id from the JSON data
     */
    public function products(): BelongsToMany
    {
        // Extract product IDs from order_items JSON
        $productIds = collect($this->order_items ?? [])
            ->filter(function($item) {
                return !empty($item['product_id']);
            })
            ->pluck('product_id')
            ->unique()
            ->toArray();
        
        // Use a query to get related products
        return $this->belongsToMany(Product::class, 'orders', 'id', 'id')
            ->whereIn('products.id', $productIds)
            ->withPivot(['name', 'price', 'quantity'])
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
     * Get the chat messages associated with this order.
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Get formatted order ID with ORDER- prefix.
     */
    public function getFormattedIdAttribute(): string
    {
        return 'ORDER-' . $this->id;
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
        if ($this->relationLoaded('items') && $this->getRelation('items')->isNotEmpty()) {
            return $this->getRelation('items')->sum('quantity');
        }
        
        // Otherwise, try to calculate from order_items JSON field
        if (is_array($this->order_items) && !empty($this->order_items)) {
            return collect($this->order_items)->sum('quantity');
        }
        
        // Default if no data is available
        return 0;
    }
    

    
    /**
     * Update payment status with appropriate timestamp and auto status change
     */
    public function updatePaymentStatus(string $status, $updatedBy = 'payment_system')
    {
        $oldPaymentStatus = $this->payment_status;
        $oldOrderStatus = $this->status;
        $this->payment_status = $status;

        // If payment is marked as paid, record the timestamp
        if ($status === self::PAYMENT_PAID && !$this->paid_at) {
            $this->paid_at = now();

            // Automatically update order status if it's still waiting for payment
            if ($this->status === self::STATUS_WAITING_FOR_PAYMENT) {
                $this->status = self::STATUS_PROCESSING;
                $this->status_updated_at = now();
                $this->status_updated_by = $updatedBy;

                Log::info("Auto-updated order status from {$oldOrderStatus} to {$this->status} for order {$this->order_id} after payment completion");
            }
        }

        // If payment failed or expired, ensure order status reflects this
        if (in_array($status, [self::PAYMENT_FAILED, self::PAYMENT_EXPIRED])) {
            if ($this->status === self::STATUS_PROCESSING) {
                $this->status = self::STATUS_WAITING_FOR_PAYMENT;
                $this->status_updated_at = now();
                $this->status_updated_by = $updatedBy;

                Log::info("Auto-reverted order status from {$oldOrderStatus} to {$this->status} for order {$this->order_id} due to payment failure");
            }
        }

        $this->save();

        // Create notifications for status changes
        if ($status === self::PAYMENT_PAID && $oldPaymentStatus !== self::PAYMENT_PAID) {
            Notification::createPaymentNotification($this->order_id, $this->user_id);
        }

        if ($this->status !== $oldOrderStatus) {
            Notification::createOrderStatusNotification($this->order_id, $this->status, $this->user_id);
        }

        return [
            'old_payment_status' => $oldPaymentStatus,
            'new_payment_status' => $this->payment_status,
            'old_order_status' => $oldOrderStatus,
            'new_order_status' => $this->status,
            'status_changed' => $oldOrderStatus !== $this->status,
            'payment_status_changed' => $oldPaymentStatus !== $this->payment_status
        ];
    }

    /**
     * Update order status with proper validation and logging
     */
    public function updateStatus(string $newStatus, $updatedBy = null, $adminId = null)
    {
        $oldStatus = $this->status;

        // If status is the same, do nothing
        if ($oldStatus === $newStatus) {
            return [
                'old_status' => $oldStatus,
                'new_status' => $this->status,
                'updated_by' => $this->status_updated_by,
                'updated_at' => $this->status_updated_at,
                'changed' => false
            ];
        }

        // Validate status transition
        if (!$this->isValidStatusTransition($oldStatus, $newStatus)) {
            throw new \InvalidArgumentException("Invalid status transition from {$oldStatus} to {$newStatus}");
        }

        // Only allow status change if payment is completed (except for cancellation and waiting for payment)
        if ($newStatus !== self::STATUS_CANCELLED &&
            $this->payment_status !== self::PAYMENT_PAID &&
            $newStatus !== self::STATUS_WAITING_FOR_PAYMENT) {
            throw new \InvalidArgumentException("Cannot change order status to {$newStatus} when payment is not completed");
        }

        $this->status = $newStatus;
        $this->status_updated_at = now();

        // Set who updated the status
        if ($adminId) {
            $this->status_updated_by = "admin:{$adminId}";
        } elseif ($updatedBy) {
            $this->status_updated_by = $updatedBy;
        } elseif (auth()->guard('admin')->check()) {
            $this->status_updated_by = "admin:" . auth()->guard('admin')->id();
        } elseif (auth()->check()) {
            $this->status_updated_by = "user:" . auth()->id();
        } else {
            $this->status_updated_by = "system:auto";
        }

        // Set the appropriate timestamp based on status
        switch ($newStatus) {
            case self::STATUS_PROCESSING:
                $this->processing_started_at = now();
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

        // Create notification for status change
        try {
            if (class_exists('App\Models\Notification')) {
                \App\Models\Notification::createOrderStatusNotification($this->order_id, $newStatus, $this->user_id);
            }
        } catch (\Exception $e) {
            Log::error('Error creating status notification: ' . $e->getMessage());
        }

        // Create status history entry
        try {
            if (class_exists('App\Models\OrderStatusHistory')) {
                \App\Models\OrderStatusHistory::create([
                    'order_id' => $this->id,
                    'status' => $newStatus,
                    'previous_status' => $oldStatus,
                    'updated_by' => $this->status_updated_by,
                    'notes' => "Status changed from {$oldStatus} to {$newStatus}"
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error creating status history: ' . $e->getMessage());
        }

        Log::info("Order status updated: Order {$this->order_id} from {$oldStatus} to {$newStatus} by {$this->status_updated_by}");

        return [
            'old_status' => $oldStatus,
            'new_status' => $this->status,
            'updated_by' => $this->status_updated_by,
            'updated_at' => $this->status_updated_at,
            'changed' => true
        ];
    }

    /**
     * Check if status transition is valid
     */
    private function isValidStatusTransition(string $fromStatus, string $toStatus): bool
    {
        $validTransitions = [
            self::STATUS_WAITING_FOR_PAYMENT => [
                self::STATUS_PROCESSING,
                self::STATUS_CANCELLED
            ],
            self::STATUS_PROCESSING => [
                self::STATUS_SHIPPING,
                self::STATUS_DELIVERED, // Allow direct processing to delivered
                self::STATUS_CANCELLED
            ],
            self::STATUS_SHIPPING => [
                self::STATUS_DELIVERED,
                self::STATUS_CANCELLED
            ],
            self::STATUS_DELIVERED => [
                // Delivered is final state, no transitions allowed
            ],
            self::STATUS_CANCELLED => [
                // Cancelled is final state, no transitions allowed
            ]
        ];

        return in_array($toStatus, $validTransitions[$fromStatus] ?? []);
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
            $itemsCollection = $this->relationLoaded('items') ? 
                $this->getRelation('items') : 
                $this->getItemsCollection();
                
            $itemDetails = $itemsCollection->map(function($item) {
                return [
                    'product_id' => $item->product_id ?? null,
                    'product_name' => $item->name ?? 'Unknown Product',
                    'quantity' => $item->quantity ?? 0,
                    'price' => $item->price ?? 0,
                    'subtotal' => ($item->price ?? 0) * ($item->quantity ?? 0),
                ];
            })->toArray();
            $totalItems = $itemsCollection->sum('quantity');
        }
        
        // Create report details
        $details = [
            'order_date' => $this->created_at->format('Y-m-d H:i:s'),
            'customer' => [
                'id' => $this->user_id,
                'name' => $this->user ? $this->user->name : 'Guest',
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
     * This method ensures we always have consistent order items data.
     */
    public function getOrderItemsAttribute($value)
    {
        // If the value is already decoded, return it
        if (is_array($value)) {
            return $value;
        }
        
        // Check if we have json data
        if (!empty($value)) {
            try {
                return json_decode($value, true) ?: [];
            } catch (\Exception $e) {
                Log::error('Error decoding order_items JSON: ' . $e->getMessage());
                return [];
            }
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
        } else if ($value instanceof Collection) {
            $this->attributes['order_items'] = $value->toJson();
        } else {
            $this->attributes['order_items'] = $value;
        }
    }

    /**
     * Get formatted order items from JSON
     */
    public function getItemsCollection()
    {
        try {
            // Initialize empty collection
            $collection = new Collection();
            
            // If we have order_items as JSON, convert to collection
            if ($this->order_items && is_array($this->order_items)) {
                foreach ($this->order_items as $item) {
                    // Skip if item is not valid
                    if (!is_array($item)) {
                        continue;
                    }
                    
                    // Create a new stdClass object to mimic DB result
                    $itemObj = new \stdClass();
                    
                    // Copy all properties
                    foreach ($item as $key => $value) {
                        $itemObj->$key = $value;
                    }
                    
                    // Make sure we have required properties
                    if (!isset($itemObj->id)) $itemObj->id = isset($item['id']) ? $item['id'] : mt_rand(1000000, 9999999);
                    if (!isset($itemObj->order_id)) $itemObj->order_id = $this->id;
                    if (!isset($itemObj->product_id)) $itemObj->product_id = $item['product_id'] ?? null;
                    if (!isset($itemObj->name)) $itemObj->name = $item['name'] ?? 'Unknown Product';
                    if (!isset($itemObj->price)) $itemObj->price = $item['price'] ?? 0;
                    if (!isset($itemObj->quantity)) $itemObj->quantity = $item['quantity'] ?? 1;
                    
                    // Add to collection
                    $collection->push($itemObj);
                }
            }
            
            return $collection;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in getItemsCollection: ' . $e->getMessage());
            return new Collection(); // Return empty collection on error
        }
    }

    /**
     * Add an item to the order's items.
     * 
     * @param array $itemData The item data to add
     * @return $this
     */
    public function addItem(array $itemData)
    {
        $items = $this->order_items ?: [];
        
        // Add timestamps to the item
        $now = now()->toDateTimeString();
        $itemData['created_at'] = $itemData['created_at'] ?? $now;
        $itemData['updated_at'] = $now;
        
        // Add the item to the array
        $items[] = $itemData;
        
        // Update the order_items attribute
        $this->order_items = $items;
        
        // If the model exists, save it
        if ($this->exists) {
            $this->save();
        }
        
        return $this;
    }

    /**
     * Remove an item from the order by its position in the array.
     * 
     * @param int $index The index of the item to remove
     * @return $this
     */
    public function removeItem(int $index)
    {
        $items = $this->order_items ?: [];
        
        if (isset($items[$index])) {
            array_splice($items, $index, 1);
            $this->order_items = $items;
            
            if ($this->exists) {
                $this->save();
            }
        }
        
        return $this;
    }

    /**
     * Update an item in the order.
     * 
     * @param int $index The index of the item to update
     * @param array $data The data to update
     * @return $this
     */
    public function updateItem(int $index, array $data)
    {
        $items = $this->order_items ?: [];
        
        if (isset($items[$index])) {
            // Update the data
            $items[$index] = array_merge($items[$index], $data);
            
            // Add updated timestamp
            $items[$index]['updated_at'] = now()->toDateTimeString();
            
            $this->order_items = $items;
            
            if ($this->exists) {
                $this->save();
            }
        }
        
        return $this;
    }

    /**
     * Get formatted items for API and views
     * 
     * @return array
     */
    public function getFormattedItems()
    {
        // Get items either from the JSON column or from the relationship
        $orderItems = $this->order_items ?? [];
        
        // If items are not in the JSON column, get them from the relationship and enhance them
        if (empty($orderItems) && $this->items && $this->items->isNotEmpty()) {
            return $this->items->map(function($item) {
                $imageUrl = null;
                if ($item->product) {
                    $primaryImage = $item->product->getPrimaryImage();
                    if ($primaryImage) {
                        $imageUrl = asset('storage/' . $primaryImage);
                    }
                }

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->price * $item->quantity,
                    'image' => $item->product ? $item->product->getPrimaryImage() : null,
                    'imageUrl' => $imageUrl,
                ];
            })->toArray();
        } else {
            // Enhance the JSON items with additional data if needed
            return collect($orderItems)->map(function($item) {
                $item = (array) $item;
                $productId = $item['product_id'] ?? null;
                $product = null;
                
                if ($productId) {
                    $product = \App\Models\Product::find($productId);
                }
                
                $imageUrl = null;
                if ($product) {
                    $primaryImage = $product->getPrimaryImage();
                    if ($primaryImage) {
                        $imageUrl = asset('storage/' . $primaryImage);
                    }
                }

                return [
                    'id' => $item['id'] ?? null,
                    'product_id' => $productId,
                    'name' => $item['name'] ?? 'Unknown Product',
                    'price' => $item['price'] ?? 0,
                    'quantity' => $item['quantity'] ?? 1,
                    'subtotal' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1),
                    'image' => $product ? $product->getPrimaryImage() : null,
                    'imageUrl' => $imageUrl,
                ];
            })->toArray();
        }
    }
}
