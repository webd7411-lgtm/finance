<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PumpMeterReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'pump_id',
        'shift_closing_id',
        'reading_date',
        'reading_type',
        'start_reading',
        'end_reading',
        'volume_dispensed',
        'fuel_rate',
        'sales_amount',
        'cashier_id',
        'recorded_by',
        'remarks',
    ];

    protected $casts = [
        'reading_date'     => 'date',
        'start_reading'    => 'decimal:2',
        'end_reading'      => 'decimal:2',
        'volume_dispensed' => 'decimal:2',
        'fuel_rate'        => 'decimal:2',
        'sales_amount'     => 'decimal:2',
    ];

    /**
     * Relationship to the pump machine
     */
    public function pump()
    {
        return $this->belongsTo(Pump::class);
    }

    /**
     * Relationship to shift closing (if generated during shift session)
     */
    public function shiftClosing()
    {
        return $this->belongsTo(ShiftClosing::class);
    }

    /**
     * Relationship to the cashier who closed the shift
     */
    public function cashier()
    {
        return $this->belongsTo(Cashier::class);
    }

    /**
     * Relationship to the user who recorded or calibrated the reading
     */
    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Reading type badge
     */
    public function getTypeBadgeAttribute(): string
    {
        return match ($this->reading_type) {
            'shift_sale' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-clock-history me-1"></i>Shift Sale</span>',
            'calibration' => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-tools me-1"></i>Calibration</span>',
            'manual_adjustment' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-arrow-repeat me-1"></i>Adjustment</span>',
            'initial_setup' => '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-flag me-1"></i>Initial Setup</span>',
            default => '<span class="badge bg-secondary-subtle text-secondary px-2 py-0.5 rounded-pill">' . ucfirst($this->reading_type) . '</span>',
        };
    }
}
