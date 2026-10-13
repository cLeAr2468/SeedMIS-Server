<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'organization',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'contact_number',
        'barangay',
        'municipality',
        'province',
        'region',
        'purok',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the client's full name (excluding "NA" middle names).
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
