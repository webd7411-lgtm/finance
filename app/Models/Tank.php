<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tank extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'location_id',
        'product_id',
        'tank_number',
        'name',
        'tank_type',
        'capacity',
        'opening_stock',
        'current_stock',
        'dead_stock',
        'current_dip_reading',
        'max_dip_reading',
        'use_default_tolerance',
        'tolerance_mode',
        'warning_tolerance_liters',
        'critical_tolerance_liters',
        'warning_tolerance_percent',
        'critical_tolerance_percent',
        'last_dip_date',
        'last_dip_time',
        'last_measured_liters',
        'last_variance_liters',
        'last_variance_status',
        'status',
        'description',
    ];

    protected $casts = [
        'capacity'                   => 'decimal:2',
        'opening_stock'              => 'decimal:2',
        'current_stock'              => 'decimal:2',
        'dead_stock'                 => 'decimal:2',
        'current_dip_reading'        => 'decimal:2',
        'max_dip_reading'            => 'decimal:2',
        'use_default_tolerance'      => 'boolean',
        'warning_tolerance_liters'   => 'decimal:2',
        'critical_tolerance_liters'  => 'decimal:2',
        'warning_tolerance_percent'  => 'decimal:2',
        'critical_tolerance_percent' => 'decimal:2',
        'last_dip_date'              => 'date',
        'last_measured_liters'       => 'decimal:2',
        'last_variance_liters'       => 'decimal:2',
    ];

    public function location()
    {
        return $this->belongsTo(StationLocation::class, 'location_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function pumps()
    {
        return $this->hasMany(Pump::class, 'tank_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'tank_id');
    }

    public function dipReadings()
    {
        return $this->hasMany(TankDipReading::class, 'tank_id')->latest('reading_date')->latest('id');
    }

    public function latestDipReading()
    {
        return $this->hasOne(TankDipReading::class, 'tank_id')->latest('reading_date')->latest('id');
    }

    public function dipCharts()
    {
        return $this->hasMany(TankDipChart::class, 'tank_id')->orderBy('dip_mm');
    }

    public function stockAdjustments()
    {
        return $this->hasMany(TankStockAdjustment::class, 'tank_id')->latest('adjustment_date')->latest('id');
    }

    public function shiftClosings()
    {
        return $this->hasMany(ShiftClosing::class, 'tank_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getAvailableCapacityAttribute(): float
    {
        return max(0, (float) ($this->capacity - $this->current_stock));
    }

    public function getUsableStockAttribute(): float
    {
        return max(0, (float) ($this->current_stock - $this->dead_stock));
    }

    public function getFillPercentageAttribute(): float
    {
        if ($this->capacity <= 0) {
            return 0.0;
        }
        $pct = ($this->current_stock / $this->capacity) * 100;
        return round(min(100, max(0, $pct)), 1);
    }

    public function getIsLowFuelAttribute(): bool
    {
        return $this->current_stock <= ($this->dead_stock + 1000);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active'      => '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>',
            'maintenance' => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-tools me-1"></i>Maintenance</span>',
            default       => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-pause-circle me-1"></i>Inactive</span>',
        };
    }

    /**
     * Resolve effective tolerance settings for this tank (either station default or per-tank override).
     */
    public function getEffectiveToleranceSettings(?array $stationConfig = null): array
    {
        $config = $stationConfig ?? StationSetting::getTankConfig();

        if ($this->use_default_tolerance || empty($this->tolerance_mode)) {
            return [
                'tolerance_mode'   => $config['tolerance_mode'],
                'warning_liters'   => $config['warning_liters'],
                'critical_liters'  => $config['critical_liters'],
                'warning_percent'  => $config['warning_percent'],
                'critical_percent' => $config['critical_percent'],
                'source'           => 'station_default',
            ];
        }

        return [
            'tolerance_mode'   => $this->tolerance_mode ?: $config['tolerance_mode'],
            'warning_liters'   => $this->warning_tolerance_liters !== null ? (float) $this->warning_tolerance_liters : $config['warning_liters'],
            'critical_liters'  => $this->critical_tolerance_liters !== null ? (float) $this->critical_tolerance_liters : $config['critical_liters'],
            'warning_percent'  => $this->warning_tolerance_percent !== null ? (float) $this->warning_tolerance_percent : $config['warning_percent'],
            'critical_percent' => $this->critical_tolerance_percent !== null ? (float) $this->critical_tolerance_percent : $config['critical_percent'],
            'source'           => 'tank_custom',
        ];
    }

    public function getVarianceStatusBadgeAttribute(): string
    {
        return self::formatVarianceBadge($this->last_variance_status, $this->last_variance_liters);
    }

    public static function formatVarianceBadge(?string $status, mixed $variance = null): string
    {
        if ($status === null && $variance === null) {
            return '<span class="badge bg-light text-muted border px-2 py-0.5 rounded-pill">No Dip Yet</span>';
        }

        $varFloat = (float) ($variance ?? 0);
        $sign = $varFloat > 0 ? '+' : '';
        $dirText = $varFloat > 0.009 ? 'Excess' : ($varFloat < -0.009 ? 'Short' : 'Match');
        $amtText = abs($varFloat) < 0.01 ? '0.0 L' : ($sign . number_format($varFloat, 1) . ' L ' . $dirText);

        return match ($status) {
            'balanced' => '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Balanced (0 L)</span>',
            'within_tolerance' => '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-activity me-1"></i>' . $amtText . ' (Normal)</span>',
            'warning' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-exclamation-triangle-fill me-1"></i>' . $amtText . ' (Warning)</span>',
            'critical' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-shield-exclamation me-1"></i>' . $amtText . ' (Alert)</span>',
            default => '<span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5 rounded-pill">' . $amtText . '</span>',
        };
    }
}
