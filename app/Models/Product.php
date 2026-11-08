<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'category',
        'unit',
        'is_fuel',
        'density_or_octane',
        'tank_or_shelf',
        'supplier_id',
        'purchase_price',
        'sale_price',
        'opening_stock',
        'current_stock',
        'min_stock_alert',
        'status',
        'description',
    ];

    protected $casts = [
        'is_fuel' => 'boolean',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'opening_stock' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'min_stock_alert' => 'decimal:2',
    ];

    /**
     * Categories list with labels (Default presets)
     */
    public const CATEGORIES = [
        'fuel'       => 'Fuel',
        'lubricant'  => 'Lubricants & Engine Oils',
        'cng_lpg'    => 'CNG & Auto LPG Gas',
        'car_care'   => 'Car Care & Radiator Coolants',
        'tuck_shop'  => 'Tuck Shop / Convenience Items',
        'service'    => 'Station Services (Wash / Greasing)',
        'other'      => 'General / Miscellaneous',
    ];

    /**
     * Units of Measurement list with labels (Default presets)
     */
    public const UNITS = [
        'liter'   => 'Liters (Ltr)',
        'can'     => 'Cans / Tins',
        'bottle'  => 'Bottles',
        'drum'    => 'Drums (208L)',
        'pack'    => 'Packs / Cartons',
        'piece'   => 'Pieces / Items',
        'kg'      => 'Kilograms (Kg)',
    ];

    /**
     * Get all active categories merged from database and static presets
     */
    public static function getAllCategories(): array
    {
        try {
            $dbList = ProductCategory::getList();
            if (!empty($dbList)) {
                return $dbList;
            }
        } catch (\Throwable $e) {
            // fallback
        }
        return self::CATEGORIES;
    }

    /**
     * Get all active units merged from database and static presets
     */
    public static function getAllUnits(): array
    {
        try {
            $dbList = ProductUnit::getList();
            if (!empty($dbList)) {
                return $dbList;
            }
        } catch (\Throwable $e) {
            // fallback
        }
        return self::UNITS;
    }

    /**
     * Relationship to supplier party
     */
    public function supplier()
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    /**
     * Relationship to pump machines dispensing this product
     */
    public function pumps()
    {
        return $this->hasMany(Pump::class);
    }

    /**
     * Relationship to stock purchases
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Relationship to shift closings
     */
    public function shiftClosings()
    {
        return $this->hasMany(ShiftClosing::class);
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for fuel products
     */
    public function scopeFuel($query)
    {
        return $query->where(function ($q) {
            $q->where('is_fuel', true)
              ->orWhere('category', 'fuel');
        });
    }

    /**
     * Scope for lubricants
     */
    public function scopeLubricants($query)
    {
        return $query->where('category', 'lubricant');
    }

    /**
     * Scope for products with low stock
     */
    public function scopeLowStock($query)
    {
        return $query->whereNotNull('min_stock_alert')
                     ->whereColumn('current_stock', '<=', 'min_stock_alert');
    }

    /**
     * Human readable category label
     */
    public function getCategoryLabelAttribute(): string
    {
        $all = self::getAllCategories();
        return $all[$this->category] ?? (self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category)));
    }

    /**
     * Human readable unit label
     */
    public function getUnitLabelAttribute(): string
    {
        $all = self::getAllUnits();
        return $all[$this->unit] ?? (self::UNITS[$this->unit] ?? ucfirst($this->unit));
    }

    /**
     * Profit margin per unit
     */
    public function getProfitMarginAttribute(): float
    {
        return (float) ($this->sale_price - $this->purchase_price);
    }

    /**
     * Profit margin percentage
     */
    public function getProfitPercentageAttribute(): float
    {
        if ($this->purchase_price > 0) {
            return round((($this->sale_price - $this->purchase_price) / $this->purchase_price) * 100, 2);
        }
        return 0.0;
    }

    /**
     * Current total stock inventory value based on purchase price
     */
    public function getStockValueAttribute(): float
    {
        return (float) ($this->current_stock * $this->purchase_price);
    }

    /**
     * Check if product is running low on stock
     */
    public function getIsLowStockAttribute(): bool
    {
        if ($this->min_stock_alert === null) {
            return false;
        }
        return (float) $this->current_stock <= (float) $this->min_stock_alert;
    }

    /**
     * Category badge HTML
     */
    public function getCategoryBadgeAttribute(): string
    {
        return match ($this->category) {
            'fuel'      => '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-fuel-pump me-1"></i>Fuel</span>',
            'lubricant' => '<span class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle px-2 py-1"><i class="bi bi-droplet-half text-warning me-1"></i>Lubricant</span>',
            'cng_lpg'   => '<span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info-subtle px-2 py-1"><i class="bi bi-wind me-1"></i>Gas / CNG</span>',
            'car_care'  => '<span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1"><i class="bi bi-shield-check me-1"></i>Car Care</span>',
            'tuck_shop' => '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-shop me-1"></i>Tuck Shop</span>',
            'service'   => '<span class="badge bg-dark bg-opacity-10 text-dark border border-dark-subtle px-2 py-1"><i class="bi bi-wrench-adjustable me-1"></i>Service</span>',
            default     => '<span class="badge bg-light text-dark border px-2 py-1">' . ucfirst($this->category) . '</span>',
        };
    }

    /**
     * Status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->status === 'active'
            ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle me-1"></i>Active</span>'
            : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-slash-circle me-1"></i>Inactive</span>';
    }
}
