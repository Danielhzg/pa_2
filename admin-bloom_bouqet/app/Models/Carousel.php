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
        'image',
        'is_active',
        'admin_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * Get full image URL for API responses
     */
    protected $appends = ['image_url'];
    
    /**
     * Get the full URL for the image
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }
        
        return url('storage/' . $this->image);
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
