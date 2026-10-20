<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TargetDeletion extends Model
{
    protected $fillable = [
        'original_target_id',
        'year',
        'target_seedlings',
        'notes',
        'deleted_by',
        'deleted_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'target_seedlings' => 'decimal:2',
    ];

    /**
     * Get the admin who deleted this target
     */
    public function deletedBy()
    {
        return $this->belongsTo(Admin::class, 'deleted_by');
    }
}
