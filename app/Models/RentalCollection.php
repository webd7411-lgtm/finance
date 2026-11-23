<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_rental_id',
        'payment_date',
        'rent_month',
        'amount',
        'account_id',
        'receipt_no',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public function rental()
    {
        return $this->belongsTo(StationRental::class, 'station_rental_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
