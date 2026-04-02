<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department',
        'station_address',
        'office_location_latitude',
        'office_location_longitude',
        'vehicle_type',
        'license_number',
        'status',
        'emergency_types',
        'auto_assign',
    ];

    protected $casts = [
        'emergency_types' => 'array',
        'auto_assign' => 'boolean',
        'office_location_latitude' => 'decimal:7',
        'office_location_longitude' => 'decimal:7',
    ];

    /**
     * Get the user that owns this responder detail.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

