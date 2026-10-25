<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date',
        'type',
        'shift_closing_id',
        'party_id',
        'account_id',
        'category_id',
        'amount',
        'bill_no',
        'description',
        'created_by',
        'status',
        'deleted_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getTypeBadgeAttribute(): string
    {
        if ($this->type === 'payment_out' && empty($this->party_id) && !empty($this->category_id)) {
            return '<span class="badge bg-danger text-white border border-danger fw-bold"><i class="bi bi-receipt-cutoff me-1"></i>Expense</span>';
        }

        return match($this->type) {
            'payment_in'    => '<span class="badge bg-success-subtle text-success border border-success fw-bold"><i class="bi bi-arrow-down-left me-1"></i>Payment In</span>',
            'payment_out'   => '<span class="badge bg-danger-subtle text-danger border border-danger fw-bold"><i class="bi bi-arrow-up-right me-1"></i>Payment Out</span>',
            'purchase_bill' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning fw-bold"><i class="bi bi-receipt me-1"></i>Purchase Bill</span>',
            default         => '<span class="badge bg-secondary">' . ucfirst($this->type) . '</span>',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'locked'    => '<span class="badge bg-danger-subtle text-danger border border-danger"><i class="bi bi-lock-fill me-1"></i>Locked</span>',
            'submitted' => '<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check2-circle me-1"></i>Submitted</span>',
            default     => '<span class="badge bg-secondary">' . ucfirst($this->status ?? 'locked') . '</span>',
        };
    }
}
