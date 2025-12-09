<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\DatabaseNotification;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function getEmailAttribute()
    {
        return $this->username;
    }

    public function getIsAdminAttribute()
    {
        return $this->role === 'admin';
    }

    /**
     * Override notifications relationship
     * HAPUS ->latest() dari sini, biar controller yang handle sorting
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable');
        // ↑ TANPA ->latest()
    }
}
