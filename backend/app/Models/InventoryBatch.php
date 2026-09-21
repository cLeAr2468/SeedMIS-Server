<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'batch_number',
        'production_batch_id',
        'quantity',
        'date_received',
        'date_sown',
        'expected_ready',
        'location',
        'quality_status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'date_received' => 'date',
        'date_sown' => 'date',
        'expected_ready' => 'date',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
