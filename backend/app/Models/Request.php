<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'seedling_type',
        'quantity',
        'purpose',
        'contact_number',
        'requested_date',
        'price_per_unit',
        'total_price',
        'status',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'quantity' => 'integer',
        'price_per_unit' => 'decimal:2',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the client that owns the request.
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the requester's full name.
     */
    public function getRequesterNameAttribute()
    {
        if ($this->client) {
            return trim($this->client->first_name . ' ' . 
                       ($this->client->middle_name ? $this->client->middle_name . ' ' : '') . 
                       $this->client->last_name);
        }
        return null;
    }

    /**
     * Get the organization.
     */
    public function getOrganizationAttribute()
    {
        return $this->client ? $this->client->organization : null;
    }
}
