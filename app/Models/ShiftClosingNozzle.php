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
    ];

    protected $casts = [
        'start_reading'  => 'decimal:2',
        'end_reading'    => 'decimal:2',
        'meter_liters'   => 'decimal:2',
        'testing_liters' => 'decimal:2',
        'sold_liters'    => 'decimal:2',
        'fuel_rate'      => 'decimal:2',
        'subtotal'       => 'decimal:2',
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
