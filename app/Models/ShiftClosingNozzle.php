<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftClosingNozzle extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_closing_id',
        'pump_id',
        'tank_id',
        'product_id',
        'nozzle_name',
        'start_reading',
        'end_reading',
        'meter_liters',
        'testing_liters',
        'sold_liters',
        'fuel_rate',
        'subtotal',
        'has_rate_change',
        'midnight_reading',
        'old_fuel_rate',
        'new_fuel_rate',
        'old_rate_liters',
        'new_rate_liters',
        'old_rate_subtotal',
        'new_rate_subtotal',
    ];

    protected $casts = [
        'start_reading'      => 'decimal:2',
        'end_reading'        => 'decimal:2',
        'meter_liters'       => 'decimal:2',
        'testing_liters'     => 'decimal:2',
        'sold_liters'        => 'decimal:2',
        'fuel_rate'          => 'decimal:2',
        'subtotal'           => 'decimal:2',
        'has_rate_change'    => 'boolean',
        'midnight_reading'   => 'decimal:2',
        'old_fuel_rate'      => 'decimal:2',
        'new_fuel_rate'      => 'decimal:2',
        'old_rate_liters'    => 'decimal:2',
        'new_rate_liters'    => 'decimal:2',
        'old_rate_subtotal'  => 'decimal:2',
        'new_rate_subtotal'  => 'decimal:2',
    ];

    public function shiftClosing()
    {
        return $this->belongsTo(ShiftClosing::class);
    }

    public function pump()
    {
        return $this->belongsTo(Pump::class);
    }

    public function tank()
    {
        return $this->belongsTo(Tank::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
