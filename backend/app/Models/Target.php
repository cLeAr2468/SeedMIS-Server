<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    protected $fillable = [
        'target_type',
        'seedling_type',
        'target_value',
        'period',
        'description',
        'is_active',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active targets
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific period
     */
    public function scopeForPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Scope for specific target type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('target_type', $type);
    }
}
