<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'phone',
        'address',
        'opening_balance',
        'current_balance',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function getTypeBadgeAttribute(): string
    {
        return match($this->type) {
            'supplier' => '<span class="badge bg-danger-subtle text-danger border border-danger">Supplier</span>',
            'trader'   => '<span class="badge bg-primary-subtle text-primary border border-primary">Trader</span>',
            'staff'    => '<span class="badge bg-warning-subtle text-warning border border-warning">Staff</span>',
            'customer' => '<span class="badge bg-success-subtle text-success border border-success">Customer</span>',
            default    => '<span class="badge bg-secondary">' . ucfirst($this->type) . '</span>',
        };
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
