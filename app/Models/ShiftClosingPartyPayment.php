<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftClosingPartyPayment extends Model
{
    protected $fillable = [
        'shift_closing_id',
        'party_id',
        'amount',
        'details',
    ];

    protected $casts = [
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
}