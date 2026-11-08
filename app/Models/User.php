<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

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
        return $this->hasRole('owner') || $this->role === 'owner';
    }

    public function isSuperAdmin(): bool
    {
        return $this->isOwner();
    }

    public function isIncharge(): bool
    {
        return $this->hasRole('incharge') || $this->role === 'incharge';
    }

    public function isCashier(): bool
    {
        return $this->hasRole('cashier') || $this->role === 'cashier';
    }

    public function getRoleNameAttribute(): string
    {
        return $this->roles->first()?->name ?? ($this->role ?: 'staff');
    }

    public function getRoleBadgeAttribute(): string
    {
        $roleName = $this->role_name;
        return match($roleName) {
            'owner' => '<span class="badge bg-danger"><i class="bi bi-shield-lock me-1"></i>Owner</span>',
            'incharge' => '<span class="badge bg-primary"><i class="bi bi-person-badge me-1"></i>Incharge</span>',
            'cashier' => '<span class="badge bg-success"><i class="bi bi-cash me-1"></i>Cashier</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($roleName) . '</span>',
        };
    }

    public function employee()
    {
        return $this->hasOne(\App\Models\Hr\Employee::class);
    }

    public function isEmployeeActive(): bool
    {
        $employee = $this->employee;
        if (! $employee) {
            return true;
        }
        return $employee->status === 'active';
    }
}
