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
        'location_id',
        'product_id',
        'tank_id',
        'pump_id',
        'tank_no',
        'vehicle_no',
        'quantity',
        'unit_price',
        'total_amount',
        'paid_amount',
        'payment_account_id',
        'notes',
        'pre_delivery_dip_mm',
        'pre_delivery_liters',
        'post_delivery_dip_mm',
        'post_delivery_liters',
        'sales_during_unloading',
        'actual_received_liters',
        'delivery_variance_liters',
        'created_by',
    ];

    protected $casts = [
        'purchase_date'            => 'date',
        'quantity'                 => 'decimal:2',
        'unit_price'               => 'decimal:2',
        'total_amount'             => 'decimal:2',
        'paid_amount'              => 'decimal:2',
        'pre_delivery_dip_mm'      => 'decimal:2',
        'pre_delivery_liters'      => 'decimal:2',
        'post_delivery_dip_mm'     => 'decimal:2',
        'post_delivery_liters'     => 'decimal:2',
        'sales_during_unloading'   => 'decimal:2',
        'actual_received_liters'   => 'decimal:2',
        'delivery_variance_liters' => 'decimal:2',
    ];

    public function location()
    {
        return $this->belongsTo(StationLocation::class, 'location_id');
    }

    public function tank()
    {
        return $this->belongsTo(Tank::class, 'tank_id');
    }

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

    public function dipReadings()
    {
        return $this->hasMany(TankDipReading::class, 'purchase_id');
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

    public function hasDipVerification(): bool
    {
        return $this->pre_delivery_liters !== null && $this->post_delivery_liters !== null;
    }
}
