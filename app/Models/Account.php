<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
        'account_number',
        'opening_balance',
        'current_balance',
    ];

    public function isActive(): bool
    {
        return ($this->status ?? 'active') === 'active';
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->isActive()
            ? '<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-circle me-1"></i>Active</span>'
            : '<span class="badge bg-secondary-subtle text-secondary border border-secondary"><i class="bi bi-dash-circle me-1"></i>Inactive</span>';
    }

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function getTypeBadgeAttribute(): string
    {
        return match($this->type) {
            'cash'     => '<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-cash me-1"></i>Auto Cash</span>',
            'jazzcash' => '<span class="badge bg-danger-subtle text-danger border border-danger"><i class="bi bi-phone me-1"></i>JazzCash</span>',
            'bank'     => '<span class="badge bg-primary-subtle text-primary border border-primary"><i class="bi bi-bank me-1"></i>Bank</span>',
            default    => '<span class="badge bg-secondary">' . ucfirst($this->type) . '</span>',
        };
    }

    public function isCash(): bool
    {
        return $this->type === 'cash';
    }

    public static function getCashAccount(): self
    {
        return static::firstOrCreate(
            ['type' => 'cash'],
            [
                'name' => 'Cash in Hand',
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
            ]
        );
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
