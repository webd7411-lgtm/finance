<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftClosingCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_closing_id',
        'party_id',
        'product_id',
        'vehicle_no',
        'slip_no',
        'liters',
        'rate',
        'amount',
        'notes',
    ];

    protected $casts = [
        'liters' => 'decimal:2',
        'rate'   => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function shiftClosing()
    {
        return $this->belongsTo(ShiftClosing::class);
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
