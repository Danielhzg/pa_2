<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// Removing MustVerifyEmail interface temporarily to bypass email verification
class User extends Authenticatable 
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'full_name',
        'email',
        'phone',
        'password',
        'address',
        'birth_date',
        'email_verified_at',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'otp_expires_at' => 'datetime'
        ];
    }

    /**
     * Get the orders associated with the user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the cart items associated with the user.
     */
    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get the chats associated with the user.
     */
    public function chats()
    {
        return $this->hasMany(Chat::class);
    }

    /**
     * Get the favorite products for the user.
     */
    public function favoriteProducts()
    {
        return $this->hasMany(FavoriteProduct::class);
    }

    /**
     * Get the products that are favorited by the user.
     */
    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites');
    }

    /**
     * Check if the user is a customer.
     *
     * @return bool
     */
    public function isCustomer()
    {
        return $this->role === 'customer';
    }

    /**
     * Check if the user is an admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Get the user's display name.
     *
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        return $this->full_name ?: $this->username;
    }

    /**
     * Get the user's name (alias for full_name for compatibility).
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->full_name ?: $this->username ?: 'Customer';
    }

    /**
     * Check if the user has favorited a product.
     */
    public function hasFavorited($productId)
    {
        return $this->favoriteProducts()->where('product_id', $productId)->exists();
    }

    /**
     * Get the selected items in the user's cart.
     */
    public function selectedCartItems()
    {
        return $this->cartItems()->where('is_selected', true);
    }

    /**
     * Get the total price of selected items in the cart.
     *
     * @return float
     */
    public function getCartTotal()
    {
        return $this->selectedCartItems()
            ->join('products', 'carts.product_id', '=', 'products.id')
            ->selectRaw('SUM(products.price * carts.quantity) as total')
            ->value('total') ?? 0;
    }

    /**
     * Get the products ordered by the user through their orders.
     */
    public function orderedProducts()
    {
        return $this->hasManyThrough(
            OrderItem::class,
            Order::class,
            'user_id', // Foreign key on Order
            'order_id', // Foreign key on OrderItem
            'id', // Local key on User
            'id' // Local key on Order
        );
    }
}
