<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the carousel items created or managed by this admin.
     */
    public function carousels()
    {
        return $this->hasMany(Carousel::class);
    }

    /**
     * Get the products created or managed by this admin.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the categories created or managed by this admin.
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the orders processed by this admin.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
} 