<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionHistory extends Model
{
    use HasFactory;

    protected $table = 'production_history';

    public $timestamps = false;

    protected $fillable = [
        'production_id',
        'batch_id',
        'seedling_type',
        'classification',
        'action_type',
        'previous_stage',
        'new_stage',
        'previous_quantity',
        'new_quantity',
        'changed_by',
        'notes',
        'metadata',
        'changed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'changed_at' => 'datetime',
    ];

    /**
     * Get the production that owns the history record.
     */
    public function production()
    {
        return $this->belongsTo(Production::class);
    }
}
