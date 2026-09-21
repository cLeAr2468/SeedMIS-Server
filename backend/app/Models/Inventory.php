<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'seedling_type',
        'classification',
        'total_quantity',
        'reserved_quantity',
        'price_per_unit',
        'unit',
        'min_stock_level',
        'location',
        'image_url',
    ];

    protected $casts = [
        'total_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'price_per_unit' => 'decimal:2',
        'min_stock_level' => 'integer',
    ];

    public function batches()
    {
        return $this->hasMany(InventoryBatch::class);
    }
}
