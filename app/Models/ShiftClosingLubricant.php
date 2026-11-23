<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftClosingLubricant extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_closing_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function shiftClosing()
    {
        return $this->belongsTo(ShiftClosing::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
