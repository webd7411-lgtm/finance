<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftClosingExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_closing_id',
        'expense_category_id',
        'category_name',
        'details',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function shiftClosing()
    {
        return $this->belongsTo(ShiftClosing::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
