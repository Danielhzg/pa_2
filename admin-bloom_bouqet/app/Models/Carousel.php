<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carousel extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'is_active',
        'admin_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * Get full image URL for API responses
     */
    protected $appends = ['full_image_url'];
    
    /**
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    protected $visible = [
        'id',
        'title',
        'description',
        'image_url',
        'full_image_url',
        'is_active',
        'created_at',
        'updated_at',
    ];
    
    /**
     * Get the full URL for the image
     */
    public function getFullImageUrlAttribute()
    {
        if (!$this->image_url) {
            return null;
        }
        
        return url('storage/' . $this->image_url);
    }

    /**
     * Scope a query to only include active carousels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Get the admin that created this carousel.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
