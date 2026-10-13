<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'customer_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'contact_number',
        'organization',
        'barangay',
        'municipality',
        'province',
        'region',
        'purok',
        'status',
        'upgraded_to_client_id',
        'is_active',
        'upgraded_at',
    ];

    protected $hidden = [];
    
    protected $casts = [
        'is_active' => 'boolean',
        'upgraded_at' => 'datetime',
    ];
    
    /**
     * Relationship to Client if upgraded
     */
    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class, 'upgraded_to_client_id');
    }

    /**
     * Get the customer's full name (excluding "NA" middle names).
     */
    public function getFullNameAttribute()
    {
        $middleName = $this->middle_name;
        $suffix = $this->suffix;
        
        $name = $this->first_name;
        
        // Add middle name if it's not "NA" or similar
        if ($middleName && !in_array(strtoupper(trim($middleName)), ['NA', 'N/A', 'NONE', 'N.A.', 'N.A'])) {
            $name .= ' ' . $middleName;
        }
        
        $name .= ' ' . $this->last_name;
        
        // Add suffix if present
        if ($suffix) {
            $name .= ' ' . $suffix;
        }
        
        return trim($name);
    }
}
