<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_report_id',
        'resource_id',
        'responder_id',
        'assigned_by',
        'assigned_at',
        'released_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    /**
     * Get the emergency report this log belongs to.
     */
    public function emergencyReport()
    {
        return $this->belongsTo(EmergencyReport::class);
    }

    /**
     * Get the resource this log is for.
     */
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * Get the responder who used this resource.
     */
    public function responder()
    {
        return $this->belongsTo(User::class, 'responder_id');
    }

    /**
     * Get the user who assigned this resource.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
