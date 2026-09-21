<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $fillable = [
        'batch_id',
        'seedling_type',
        'scientific_name',
        'classification',
        'date_sown',
        'expected_ready',
        'quantity_sown',
        'current_quantity',
        'survivability',
        'stage',
        'location',
        'assigned_staff',
        'image_url',
    ];

    protected $casts = [
        'date_sown' => 'date',
        'expected_ready' => 'date',
        'quantity_sown' => 'integer',
        'current_quantity' => 'integer',
        'survivability' => 'decimal:2',
    ];
}
