<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'icon',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get all reports of this emergency type.
     */
    public function reports()
    {
        return $this->hasMany(EmergencyReport::class, 'type', 'code');
    }
}

