<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

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

    public function getTypeBadgeAttribute(): string
    {
        return match($this->type) {
            'payment_in'  => '<span class="badge bg-success-subtle text-success border border-success fw-bold"><i class="bi bi-arrow-down-left me-1"></i>Payment In</span>',
            'payment_out' => '<span class="badge bg-danger-subtle text-danger border border-danger fw-bold"><i class="bi bi-arrow-up-right me-1"></i>Payment Out</span>',
            default       => '<span class="badge bg-secondary">' . ucfirst($this->type) . '</span>',
        };
    }
}
