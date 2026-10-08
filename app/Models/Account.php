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
        'account_number',
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
