<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Helper methods for roles
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isIncharge(): bool
    {
        return $this->role === 'incharge';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function getRoleBadgeAttribute(): string
    {
        return match($this->role) {
            'owner' => '<span class="badge bg-danger"><i class="bi bi-shield-lock me-1"></i>Owner</span>',
            'incharge' => '<span class="badge bg-primary"><i class="bi bi-person-badge me-1"></i>Incharge</span>',
            'cashier' => '<span class="badge bg-success"><i class="bi bi-cash me-1"></i>Cashier</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($this->role) . '</span>',
        };
    }
}
