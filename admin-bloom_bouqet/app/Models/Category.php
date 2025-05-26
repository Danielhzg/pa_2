<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'admin_id', 'is_active'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (!$category->slug) {
                $slug = Str::slug($category->name);
                
                // Make sure the slug is unique
                $count = 1;
                $originalSlug = $slug;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }
                
                $category->slug = $slug;
            }
        });
    }

    /**
     * Get the products in this category.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the admin that created or manages this category.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
