<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyResponderLocationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'responder_id',
        'responder_name',
        'message',
        'summary',
        'severity_level',
        'latitude',
        'longitude',
    ];

    /**
     * Get the emergency report associated with this location log.
     */
    public function report()
    {
        return $this->belongsTo(EmergencyReport::class, 'report_id');
    }

    /**
     * Get the responder (user) associated with this location log.
     */
    public function responder()
    {
        return $this->belongsTo(User::class, 'responder_id');
    }
}

