<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contact_number',
        'address',
        'birthdate',
        'gender',
        'profile_picture',
    ];

    protected $casts = [
        'birthdate' => 'date',
    ];

    /**
     * Get the user that owns this user detail.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

