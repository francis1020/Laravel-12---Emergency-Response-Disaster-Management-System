<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyReportMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_report_id',
        'user_id',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'thumbnail_path',
        'description',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Get the emergency report that owns this media.
     */
    public function emergencyReport()
    {
        return $this->belongsTo(EmergencyReport::class);
    }

    /**
     * Get the user who uploaded this media.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full URL for the media file.
     */
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Get the thumbnail URL if available.
     */
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail_path) {
            return asset('storage/' . $this->thumbnail_path);
        }
        return $this->url;
    }
}

