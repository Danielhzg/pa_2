<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderItem;
use App\Models\Order;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'stock', 'category_id', 
        'image', 'images', 'admin_id', 'is_active', 'is_on_sale',
        'discount', 'rating', 'total_reviews', 'reviews', 'featured_until'
    ];

    protected $casts = [
        'images' => 'array',
        'reviews' => 'array',
        'price' => 'float',
        'stock' => 'integer',
        'discount' => 'integer',
        'rating' => 'float',
        'total_reviews' => 'integer',
        'is_active' => 'boolean',
        'is_on_sale' => 'boolean',
        'featured_until' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the admin that created or manages this product.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Get the primary image for this product.
     * 
     * @return string|null
     */
    public function getPrimaryImage()
    {
        // First try to get from images array
        if (!empty($this->images) && is_array($this->images) && count($this->images) > 0) {
            return $this->images[0];
        }
        
        // Fall back to the legacy image field
        return $this->image;
    }

    /**
     * Get additional images for this product.
     * 
     * @return array
     */
    public function getAdditionalImages()
    {
        if (!empty($this->images) && is_array($this->images) && count($this->images) > 1) {
            return array_slice($this->images, 1);
        }
        
        return [];
    }

    /**
     * Get all images for this product
     * 
     * @return array
     */
    public function getAllImages()
    {
        if (!empty($this->images) && is_array($this->images)) {
            return $this->images;
        }
        
        // Fall back to the legacy image field
        return $this->image ? [$this->image] : [];
    }

    /**
     * Get the order items for this product.
     */
    public function orderItems()
    {
        try {
            // Check if the OrderItem model and table exist
            if (class_exists(OrderItem::class)) {
                // Check if the table exists
                if (!\Schema::hasTable('order_items')) {
                    return $this->hasMany(Product::class, 'id', 'id')
                        ->where('id', '<', 0); // Return empty relation
                }
                return $this->hasMany(OrderItem::class);
            }
            
            // OrderItem class doesn't exist
            return $this->hasMany(Product::class, 'id', 'id')
                ->where('id', '<', 0); // Return empty relation
        } catch (\Exception $e) {
            // Return an empty relationship if there's an error
            return $this->hasMany(Product::class, 'id', 'id')
                ->where('id', '<', 0); // Return empty relation
        }
    }

    /**
     * Get the cart items for this product.
     */
    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Check if a product is in stock.
     *
     * @return bool
     */
    public function isInStock()
    {
        return $this->stock > 0;
    }

    /**
     * Get the final price after any discounts.
     *
     * @return float
     */
    public function getFinalPrice()
    {
        if ($this->is_on_sale && $this->discount > 0) {
            return $this->price * (1 - ($this->discount / 100));
        }
        
        return $this->price;
    }

    /**
     * Get all orders that contain this product.
     */
    public function orders()
    {
        try {
            // Check if the order_items table exists
            if (!Schema::hasTable('order_items')) {
                return $this->belongsToMany(Order::class, 'products', 'id', 'id')
                    ->where('id', '<', 0); // Return empty relation
            }
            
            return $this->belongsToMany(Order::class, 'order_items')
                    ->withPivot('name', 'price', 'quantity')
                    ->withTimestamps();
        } catch (\Exception $e) {
            // Return an empty relationship if there's an error
            return $this->belongsToMany(Order::class, 'products', 'id', 'id')
                ->where('id', '<', 0); // Return empty relation
        }
    }

    /**
     * Get the users who have favorited this product.
     */
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites', 'product_id', 'user_id');
    }

    /**
     * Get favorite count for this product.
     *
     * @return int
     */
    public function getFavoriteCountAttribute()
    {
        return $this->favoritedBy()->count();
    }
}
