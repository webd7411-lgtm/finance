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
        'status',
        'description',
    ];

    protected $casts = [
        'capacity'            => 'decimal:2',
        'opening_stock'       => 'decimal:2',
        'current_stock'       => 'decimal:2',
        'dead_stock'          => 'decimal:2',
        'current_dip_reading' => 'decimal:2',
        'max_dip_reading'     => 'decimal:2',
    ];

    /**
     * Station location
     */
    public function location()
    {
        return $this->belongsTo(StationLocation::class, 'location_id');
    }

    /**
     * Product stored inside this tank
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Pumps / Dispensers connected to this storage tank
     */
    public function pumps()
    {
        return $this->hasMany(Pump::class, 'tank_id');
    }

    /**
     * Fuel purchases / bowsers discharged into this tank
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'tank_id');
    }

    /**
     * Audit log of physical dip measurements
     */
    public function dipReadings()
    {
        return $this->hasMany(TankDipReading::class, 'tank_id')->latest('reading_date')->latest('id');
    }

    /**
     * Shift closings from all pumps attached to this tank
     */
    public function shiftClosings()
    {
        return $this->hasMany(ShiftClosing::class, 'tank_id');
    }

    /**
     * Scope for active tanks
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Remaining / Available storage capacity in liters before tank overflows
     */
    public function getAvailableCapacityAttribute(): float
    {
        return max(0, (float) ($this->capacity - $this->current_stock));
    }

    /**
     * Usable fuel stock (above dead stock)
     */
    public function getUsableStockAttribute(): float
    {
        return max(0, (float) ($this->current_stock - $this->dead_stock));
    }

    /**
     * Current fill percentage (0% - 100%)
     */
    public function getFillPercentageAttribute(): float
    {
        if ($this->capacity <= 0) {
            return 0.0;
        }
        $pct = ($this->current_stock / $this->capacity) * 100;
        return round(min(100, max(0, $pct)), 1);
    }

    /**
     * Determine if fuel is below dead stock / danger zone
     */
    public function getIsLowFuelAttribute(): bool
    {
        return $this->current_stock <= ($this->dead_stock + 1000);
    }

    /**
     * Status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active'      => '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>',
            'maintenance' => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-tools me-1"></i>Maintenance</span>',
            default       => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-pause-circle me-1"></i>Inactive</span>',
        };
    }
}
