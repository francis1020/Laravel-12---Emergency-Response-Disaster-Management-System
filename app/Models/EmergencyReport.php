<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'contact_name',
        'contact_number',
        'email',
        'latitude',
        'longitude',
        'address',
        'landmark',
        'severity_level',
        'reported_at',
        'priority_score',
        'is_duplicate',
        'original_report_id',
        'is_cancelled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reported_at' => 'datetime',
        'is_duplicate' => 'boolean',
        'priority_score' => 'integer',
        'is_cancelled' => 'boolean',
    ];

    /**
     * Get the emergency type details (relation to emergency_types.code).
     */
    public function emergencyType()
    {
        return $this->belongsTo(EmergencyType::class, 'type', 'code');
    }

    /**
     * Get the user who reported the emergency.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the primary responder.
     */
    public function primaryResponder()
    {
        return $this->hasOne(EmergencyReportResponder::class)
            ->where('role', 'primary')
            ->with('responder');
    }

    /**
     * Accessor to get the name of the emergency type easily.
     */
    public function getTypeNameAttribute(): string
    {
        return $this->emergencyType?->name ?? ucfirst($this->type);
    }

    /**
     * Accessor to get the icon of the emergency type easily.
     */
    public function getTypeIconAttribute(): string
    {
        return $this->emergencyType?->icon ?? 'bi bi-exclamation-triangle';
    }

    /**
     * Get all media files for this report.
     */
    public function media()
    {
        return $this->hasMany(EmergencyReportMedia::class);
    }

    /**
     * Get all assigned responders (multi-responder support).
     */
    public function responders()
    {
        return $this->hasMany(EmergencyReportResponder::class);
    }

    /**
     * Get all messages/chat for this report.
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the resource usage logs for this report.
     */
    public function resourceUsageLogs()
    {
        return $this->hasMany(ResourceUsageLog::class, 'emergency_report_id');
    }

    /**
     * Get the original report if this is a duplicate.
     */
    public function originalReport()
    {
        return $this->belongsTo(EmergencyReport::class, 'original_report_id');
    }

    /**
     * Get duplicate reports of this report.
     */
    public function duplicates()
    {
        return $this->hasMany(EmergencyReport::class, 'original_report_id');
    }

    /**
     * Calculate priority score based on severity and type.
     */
    public function calculatePriorityScore()
    {
        $score = 0;

        // Severity level scoring
        $severityScores = [
            'low' => 10,
            'moderate' => 30,
            'high' => 60,
            'critical' => 100,
        ];
        $score += $severityScores[$this->severity_level] ?? 30;

        // Emergency type scoring (critical types get higher priority)
        $typeScores = [
            'medical_emergency' => 20,
            'fire' => 25,
            'road_accident' => 20,
            'police_assistance' => 15,
            'rescue' => 20,
            'flood' => 15,
            'earthquake' => 25,
            'others' => 10,
        ];
        $score += $typeScores[$this->type] ?? 10;

        // Age of report (older reports get higher priority)
        $ageInMinutes = $this->reported_at->diffInMinutes(now());
        $score += min($ageInMinutes / 10, 20); // Max 20 points for age

        // If no responder assigned yet, increase priority
        if ($this->responders()->count() === 0) {
            $score += 15;
        }

        return (int) $score;
    }

    /**
     * Get the overall status of the report based on responder statuses.
     * Status is determined by the primary responder's status, or 'pending' if no responders.
     */
    public function getStatusAttribute()
    {
        $primaryResponder = $this->responders()->where('role', 'primary')->first();
        
        if (!$primaryResponder) {
            return 'pending';
        }

        // Map responder status to report status
        $statusMap = [
            'assigned' => 'acknowledged',
            'en_route' => 'dispatched',
            'on_scene' => 'in_progress',
            'completed' => 'resolved',
            'cancelled' => 'cancelled',
        ];

        return $statusMap[$primaryResponder->status] ?? 'pending';
    }

    /**
     * Check if report has a specific status (based on primary responder).
     */
    public function hasStatus($status)
    {
        return $this->status === $status;
    }
}
