<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TankStockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tank_id',
        'tank_dip_reading_id',
        'adjustment_date',
        'adjustment_type',
        'book_stock_before',
        'adjustment_liters',
        'book_stock_after',
        'unit_cost',
        'financial_impact',
        'reason_category',
        'notes',
        'is_auto',
        'created_by',
    ];

    protected $casts = [
        'adjustment_date'   => 'date',
        'book_stock_before' => 'decimal:2',
        'adjustment_liters' => 'decimal:2',
        'book_stock_after'  => 'decimal:2',
        'unit_cost'         => 'decimal:2',
        'financial_impact'  => 'decimal:2',
        'is_auto'           => 'boolean',
    ];

    public function tank()
    {
        return $this->belongsTo(Tank::class, 'tank_id');
    }

    public function dipReading()
    {
        return $this->belongsTo(TankDipReading::class, 'tank_dip_reading_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getReasonLabelAttribute(): string
    {
        return StationSetting::REASON_CATEGORIES[$this->reason_category]
            ?? ($this->reason_category ? ucfirst(str_replace('_', ' ', $this->reason_category)) : 'Dip Reconciliation');
    }
}
