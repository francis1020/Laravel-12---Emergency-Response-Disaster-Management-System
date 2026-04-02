<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is a regular user (type = 0)
     */
    public function isUser(): bool
    {
        return $this->type === 0;
    }

    /**
     * Check if user is a responder (type = 1)
     */
    public function isResponder(): bool
    {
        return $this->type === 1;
    }

    /**
     * Check if user is an admin (type = 2)
     */
    public function isAdmin(): bool
    {
        return $this->type === 2 || $this->type === 3;
    }

    /**
     * Check if user is a super admin (type = 3)
     */
    public function isSuperAdmin(): bool
    {
        return $this->type === 3;
    }

    /**
     * Get the responder detail for this user (if responder)
     */
    public function responderDetail()
    {
        return $this->hasOne(ResponderDetail::class);
    }

    /**
     * Get the user detail for this user (if regular user)
     */
    public function userDetail()
    {
        return $this->hasOne(UserDetail::class);
    }
}

