<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyReportResponder extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_report_id',
        'responder_id',
        'role',
        'status',
        'assigned_at',
        'en_route_at',
        'on_scene_at',
        'completed_at',
        'cancelled_at',
        'notes',
        'responding_unit',
        'response_notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'en_route_at' => 'datetime',
        'on_scene_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the emergency report that this responder is assigned to.
     */
    public function emergencyReport()
    {
        return $this->belongsTo(EmergencyReport::class);
    }

    /**
     * Get the responder (user) assigned to this report.
     */
    public function responder()
    {
        return $this->belongsTo(User::class, 'responder_id');
    }
}

