<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TankDipReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'tank_id',
        'reading_date',
        'reading_time',
        'reading_context',
        'previous_dip_id',
        'dip_cm',
        'measured_liters',
        'book_stock_liters',
        'variance_liters',
        'period_variance_liters',
        'variance_status',
        'reason_category',
        'temperature_c',
        'water_dip_mm',
        'is_chart_calculated',
        'purchase_id',
        'is_reconciled',
        'reconciled_at',
        'reconciled_by',
        'adjusted_liters',
        'financial_impact_amount',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'reading_date'            => 'date',
        'dip_cm'                  => 'decimal:2',
        'measured_liters'         => 'decimal:2',
        'book_stock_liters'       => 'decimal:2',
        'variance_liters'         => 'decimal:2',
        'period_variance_liters'  => 'decimal:2',
        'temperature_c'           => 'decimal:2',
        'water_dip_mm'            => 'decimal:2',
        'is_chart_calculated'     => 'boolean',
        'is_reconciled'           => 'boolean',
        'reconciled_at'           => 'datetime',
        'adjusted_liters'         => 'decimal:2',
        'financial_impact_amount' => 'decimal:2',
    ];

    public function tank()
    {
        return $this->belongsTo(Tank::class, 'tank_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reconciler()
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function previousDip()
    {
        return $this->belongsTo(TankDipReading::class, 'previous_dip_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function stockAdjustment()
    {
        return $this->hasOne(TankStockAdjustment::class, 'tank_dip_reading_id');
    }

    public function getContextLabelAttribute(): string
    {
        return StationSetting::READING_CONTEXTS[$this->reading_context]
            ?? ucfirst(str_replace('_', ' ', (string) $this->reading_context));
    }

    public function getReasonLabelAttribute(): ?string
    {
        if (empty($this->reason_category)) {
            return null;
        }
        return StationSetting::REASON_CATEGORIES[$this->reason_category]
            ?? ucfirst(str_replace('_', ' ', $this->reason_category));
    }

    public function getStatusBadgeAttribute(): string
    {
        return Tank::formatVarianceBadge($this->variance_status, $this->variance_liters);
    }
}
