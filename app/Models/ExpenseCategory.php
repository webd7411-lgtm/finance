<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
    ];

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopePurchase($query)
    {
        return $query->where('type', 'purchase');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    public function shiftClosingPartyPayments()
    {
        return $this->hasMany(ShiftClosingPartyPayment::class, 'expense_category_id');
    }
}
