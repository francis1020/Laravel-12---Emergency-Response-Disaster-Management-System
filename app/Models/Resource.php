<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'identifier',
        'description',
        'status',
        'responder_id',
        'assigned_to',
        'current_report_id',
        'latitude',
        'longitude',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user this resource is assigned to.
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the current emergency report this resource is assigned to.
     */
    public function currentReport()
    {
        return $this->belongsTo(EmergencyReport::class, 'current_report_id');
    }

    /**
     * Get the responder who owns this resource.
     */
    public function responder()
    {
        return $this->belongsTo(User::class, 'responder_id');
    }
}

