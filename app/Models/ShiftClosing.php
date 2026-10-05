<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'shift_type',
        'cashier_id',
        'total_invoices',
        'invoice_start',
        'invoice_end',
        'total_sale',
        'returns_amount',
        'return_invoice_number',
        'return_invoice_start',
        'return_invoice_end',
        'total_return_invoices',
        'expenses_amount',
        'expenses_details',
        'note_5000',
        'note_1000',
        'note_500',
        'note_100',
        'note_50',
        'note_20',
        'note_10',
        'coins',
        'total_counted_cash',
        'jazzcash_amount',
        'bank_amount',
        'total_actual_received',
        'expected_cash',
        'difference',
        'status',
        'remarks',
        'verified_by',
    ];

    protected $casts = [
        'date' => 'date',
        'total_sale' => 'decimal:2',
        'returns_amount' => 'decimal:2',
        'expenses_amount' => 'decimal:2',
        'total_counted_cash' => 'decimal:2',
        'jazzcash_amount' => 'decimal:2',
        'bank_amount' => 'decimal:2',
        'total_actual_received' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function partyPayments()
    {
        return $this->hasMany(ShiftClosingPartyPayment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getShiftBadgeAttribute(): string
    {
        return match($this->shift_type) {
            'morning' => '<span class="badge bg-warning-subtle text-warning border border-warning"><i class="bi bi-sun me-1"></i>Morning Shift</span>',
            'evening' => '<span class="badge bg-primary-subtle text-primary border border-primary"><i class="bi bi-moon-stars me-1"></i>Evening Shift</span>',
            default   => '<span class="badge bg-secondary">' . ucfirst($this->shift_type) . '</span>',
        };
    }

    public function getDifferenceBadgeAttribute(): string
    {
        if ($this->difference == 0) {
            return '<span class="badge bg-success-subtle text-success border border-success fw-bold">Balanced (Rs. 0)</span>';
        } elseif ($this->difference > 0) {
            return '<span class="badge bg-info-subtle text-info border border-info fw-bold">+Rs. ' . number_format($this->difference, 2) . ' (Surplus)</span>';
        } else {
            return '<span class="badge bg-danger-subtle text-danger border border-danger fw-bold">-Rs. ' . number_format(abs($this->difference), 2) . ' (Shortage)</span>';
        }
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'locked'    => '<span class="badge bg-danger-subtle text-danger border border-danger"><i class="bi bi-lock-fill me-1"></i>Locked</span>',
            'submitted' => '<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check2-circle me-1"></i>Submitted</span>',
            default     => '<span class="badge bg-secondary">' . ucfirst($this->status) . '</span>',
        };
    }
}
