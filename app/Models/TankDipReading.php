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
        'dip_cm',
        'measured_liters',
        'book_stock_liters',
        'variance_liters',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'reading_date'      => 'date',
        'dip_cm'            => 'decimal:2',
        'measured_liters'   => 'decimal:2',
        'book_stock_liters' => 'decimal:2',
        'variance_liters'   => 'decimal:2',
    ];

    public function tank()
    {
        return $this->belongsTo(Tank::class, 'tank_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
