<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StationRental extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'facility_type',
        'tenant_name',
        'tenant_phone',
        'monthly_rent',
        'due_day',
        'status',
        'notes',
    ];

    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'due_day'      => 'integer',
    ];

    public function collections()
    {
        return $this->hasMany(RentalCollection::class)->latest('payment_date');
    }

    public function getFacilityTypeLabelAttribute(): string
    {
        static $typeMap = null;
        if ($typeMap === null) {
            try {
                $typeMap = RentalFacilityType::all()->pluck('display_name', 'slug')->toArray();
            } catch (\Throwable $e) {
                $typeMap = [];
            }
        }

        if (isset($typeMap[$this->facility_type])) {
            return $typeMap[$this->facility_type];
        }

        return match($this->facility_type) {
            'car_wash'  => '🚿 Car Wash Station',
            'tyre_shop' => '🛞 Tyre Shop & Puncture',
            'tuck_shop' => '🏪 Mart / Tuck Shop',
            'atm'       => '🏧 ATM Booth Space',
            'other'     => '🏢 Other Leased Space',
            default     => ucfirst(str_replace('_', ' ', $this->facility_type)),
        };
    }
}
