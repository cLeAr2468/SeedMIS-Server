<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_type',
        'action',
        'module',
        'description',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action
     */
    public function user()
    {
        if ($this->user_type === 'admin') {
            return $this->belongsTo(Admin::class, 'user_id');
        }
        return $this->belongsTo(Staff::class, 'user_id');
    }

    /**
     * Get formatted user name
     */
    public function getUserNameAttribute()
    {
        if ($this->user_type === 'admin') {
            // Admin uses numeric ID
            $admin = Admin::find($this->user_id);
            return $admin ? $admin->name : 'Unknown Admin';
        } else {
            // Staff uses staff_id (e.g., "STF-0001")
            $staff = Staff::where('staff_id', $this->user_id)->first();
            if (!$staff) {
                // Fallback: try as numeric ID
                $staff = Staff::find($this->user_id);
            }
            return $staff ? trim($staff->first_name . ' ' . $staff->last_name) : 'Unknown Staff';
        }
    }
}
