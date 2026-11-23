<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelRateRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'affected_tank_id',
        'effective_date',
        'effective_time',
        'old_sale_rate',
        'new_sale_rate',
        'rate_difference',
        'old_purchase_rate',
        'new_purchase_rate',
        'stock_at_revision',
        'stock_gain_loss_amount',
        'notification_ref',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'effective_date'         => 'date',
        'old_sale_rate'          => 'decimal:2',
        'new_sale_rate'          => 'decimal:2',
        'rate_difference'        => 'decimal:2',
        'old_purchase_rate'      => 'decimal:2',
        'new_purchase_rate'      => 'decimal:2',
        'stock_at_revision'      => 'decimal:2',
        'stock_gain_loss_amount' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function affectedTank()
    {
        return $this->belongsTo(Tank::class, 'affected_tank_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isGain(): bool
    {
        return (float) $this->rate_difference > 0;
    }

    public function isLoss(): bool
    {
        return (float) $this->rate_difference < 0;
    }

    public function getFormattedDifferenceAttribute(): string
    {
        $diff = (float) $this->rate_difference;
        $sign = $diff > 0 ? '+' : '';
        return $sign . 'Rs. ' . number_format($diff, 2);
    }
}
