<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['username', 'slug']; // Changed from 'name' to 'username'

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
