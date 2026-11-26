<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TankDipChart extends Model
{
    use HasFactory;

    protected $fillable = [
        'tank_id',
        'dip_mm',
        'liters',
    ];

    protected $casts = [
        'dip_mm' => 'decimal:2',
        'liters' => 'decimal:2',
    ];

    public function tank()
    {
        return $this->belongsTo(Tank::class, 'tank_id');
    }
}
