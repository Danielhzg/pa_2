<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'is_selected',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_selected' => 'boolean',
    ];

    /**
     * Get the user that owns the cart item.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product in the cart.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the subtotal of this cart item.
     */
    public function getSubtotalAttribute()
    {
        return $this->product->price * $this->quantity;
    }
} 