<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model OrderItem sekarang bekerja sebagai model presentasi
 * untuk data yang disimpan dalam kolom order_items JSON di tabel orders.
 * Tidak ada tabel order_items yang digunakan lagi.
 */
class OrderItem extends Model
{
    use HasFactory;

    /**
     * Indikasi bahwa model ini tidak terkait dengan tabel database.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'name',
        'description',
        'price',
        'quantity',
        'image_url',
        'options',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'options' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order that the item belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product associated with this order item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the subtotal for the item.
     */
    public function getSubtotalAttribute(): float
    {
        return $this->price * $this->quantity;
    }
} 