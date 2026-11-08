<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StationLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'city',
        'address',
        'phone',
        'manager_name',
        'is_default',
        'status',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Storage tanks located at this station
     */
    public function tanks()
    {
        return $this->hasMany(Tank::class, 'location_id');
    }

    /**
     * Pumps / dispensers located at this station
     */
    public function pumps()
    {
        return $this->hasMany(Pump::class, 'location_id');
    }

    /**
     * Fuel purchases / stock intakes delivered to this location
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'location_id');
    }

    /**
     * Active scope
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>',
            default  => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5 rounded-pill"><i class="bi bi-pause-circle me-1"></i>Inactive</span>',
        };
    }

    /**
     * Total storage capacity of all active tanks at this site (in Liters)
     */
    public function getTotalCapacityAttribute(): float
    {
        return (float) $this->tanks()->where('status', 'active')->sum('capacity');
    }

    /**
     * Total live fuel stock stored at this site (in Liters)
     */
    public function getTotalFuelStockAttribute(): float
    {
        return (float) $this->tanks()->where('status', 'active')->sum('current_stock');
    }
}
