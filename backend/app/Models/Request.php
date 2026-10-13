<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'customer_id',
        'requester_type',
        'inventory_id',
        'seedling_type',
        'quantity',
        'purpose',
        'contact_number',
        'requested_date',
        'price_per_unit',
        'total_price',
        'status',
        'request_source',
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
     * Get the customer that owns the request.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the inventory that owns the request.
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    /**
     * Get the seedling type from inventory or fallback to stored value
     */
    public function getSeedlingTypeAttribute($value)
    {
        // If inventory relationship exists, use its seedling_type
        if ($this->inventory) {
            return $this->inventory->seedling_type;
        }
        
        // Otherwise use stored value (for backward compatibility)
        return $value;
    }

    /**
     * Get the requester's full name.
     */
    public function getRequesterNameAttribute()
    {
        $requester = $this->requester_type === 'client' ? $this->client : $this->customer;
        
        if ($requester) {
            $middleName = $requester->middle_name;
            $suffix = $requester->suffix ?? '';
            
            // Skip middle name if it's "NA" or similar
            if ($middleName && !in_array(strtoupper(trim($middleName)), ['NA', 'N/A', 'NONE', 'N.A.', 'N.A'])) {
                return trim($requester->first_name . ' ' . $middleName . ' ' . $requester->last_name . ($suffix ? ' ' . $suffix : ''));
            }
            
            return trim($requester->first_name . ' ' . $requester->last_name . ($suffix ? ' ' . $suffix : ''));
        }
        return null;
    }

    /**
     * Get the organization.
     */
    public function getOrganizationAttribute()
    {
        $requester = $this->requester_type === 'client' ? $this->client : $this->customer;
        return $requester ? $requester->organization : null;
    }

    /**
     * Get the seedling type from inventory or fallback to stored value.
     * This accessor ensures we always get the latest seedling_type from inventory.
     */
    public function getSeedlingTypeDisplayAttribute()
    {
        // If inventory relationship exists, use its current seedling_type
        if ($this->inventory) {
            return $this->inventory->seedling_type;
        }
        
        // Otherwise use stored value (for backward compatibility or if inventory deleted)
        return $this->attributes['seedling_type'] ?? 'Unknown';
    }
}
