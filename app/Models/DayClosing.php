<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'opening_cash',
        'bank_opening',
        'jazzcash_opening',
        'total_opening_all_accounts',
        'total_cash_in',
        'bank_in',
        'jazzcash_in',
        'total_in_all_accounts',
        'total_payments_out',
        'bank_out',
        'jazzcash_out',
        'total_out_all_accounts',
        'closing_cash',
        'bank_closing',
        'jazzcash_closing',
        'total_closing_all_accounts',
        'total_difference',
        'status',
        'remarks',
        'closed_by',
    ];

    protected $casts = [
        'date' => 'date',
        'opening_cash' => 'decimal:2',
        'bank_opening' => 'decimal:2',
        'jazzcash_opening' => 'decimal:2',
        'total_opening_all_accounts' => 'decimal:2',
        'total_cash_in' => 'decimal:2',
        'bank_in' => 'decimal:2',
        'jazzcash_in' => 'decimal:2',
        'total_in_all_accounts' => 'decimal:2',
        'total_payments_out' => 'decimal:2',
        'bank_out' => 'decimal:2',
        'jazzcash_out' => 'decimal:2',
        'total_out_all_accounts' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'bank_closing' => 'decimal:2',
        'jazzcash_closing' => 'decimal:2',
        'total_closing_all_accounts' => 'decimal:2',
        'total_difference' => 'decimal:2',
    ];

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'closed' => '<span class="badge bg-success-subtle text-success border border-success fw-bold"><i class="bi bi-check2-circle me-1"></i>Day Finalized</span>',
            'open'   => '<span class="badge bg-warning-subtle text-warning border border-warning fw-bold"><i class="bi bi-clock-history me-1"></i>Active / In-Progress</span>',
            default  => '<span class="badge bg-secondary">' . ucfirst($this->status) . '</span>',
        };
    }
}
