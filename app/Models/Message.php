<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_report_id',
        'user_id',
        'message',
        'type',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    /**
     * Get the emergency report this message belongs to.
     */
    public function emergencyReport()
    {
        return $this->belongsTo(EmergencyReport::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all users who have read this message.
     */
    public function readBy()
    {
        return $this->belongsToMany(User::class, 'message_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Check if a specific user has read this message.
     */
    public function isReadBy($userId)
    {
        return $this->readBy()->where('user_id', $userId)->exists();
    }
}

