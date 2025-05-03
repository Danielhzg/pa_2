<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'report_number',
        'type',
        'title',
        'description',
        'details',
        'period_start',
        'period_end',
        'total_amount',
        'total_items',
        'status',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'details' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'total_amount' => 'decimal:2',
        'total_items' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order associated with this report.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the admin who created this report.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Generate a unique report number.
     */
    public static function generateReportNumber(): string
    {
        $prefix = 'RPT';
        $timestamp = now()->format('YmdHis');
        $random = mt_rand(1000, 9999);
        
        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Scope a query to only include reports of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include reports with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include reports for a specific period.
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('period_start', [$startDate, $endDate])
              ->orWhereBetween('period_end', [$startDate, $endDate]);
        });
    }

    /**
     * Scope a query to only include reports created in a date range.
     */
    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get a summary of the report details.
     */
    public function getSummaryAttribute()
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'period' => $this->period_start && $this->period_end 
                ? $this->period_start->format('d M Y') . ' - ' . $this->period_end->format('d M Y')
                : 'N/A',
            'total_amount' => $this->total_amount,
            'total_items' => $this->total_items,
            'status' => $this->status,
        ];
    }
} 