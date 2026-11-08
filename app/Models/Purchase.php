<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_date',
        'invoice_no',
        'supplier_id',
        'product_id',
        'pump_id',
        'tank_no',
        'vehicle_no',
        'quantity',
        'unit_price',
        'total_amount',
        'paid_amount',
        'payment_account_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function pump()
    {
        return $this->belongsTo(Pump::class, 'pump_id');
    }

    public function paymentAccount()
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getUnpaidBalanceAttribute(): float
    {
        return (float) max(0, $this->total_amount - $this->paid_amount);
    }

    public function getPaymentStatusBadgeAttribute(): string
    {
        if ($this->paid_amount >= $this->total_amount && $this->total_amount > 0) {
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check2-all me-1"></i>Fully Paid</span>';
        } elseif ($this->paid_amount > 0) {
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-clock-history me-1"></i>Partial (Rs. ' . number_format($this->unpaid_balance, 2) . ' Due)</span>';
        } else {
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-exclamation-circle me-1"></i>Unpaid (Credit Khata)</span>';
        }
    }
}
