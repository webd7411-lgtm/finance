<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'opening_cash',
        'bank_opening',
        'jazzcash_opening',
        'total_opening_all_accounts',
        'total_cash_in',
        'bank_in',
        'jazzcash_in',
        'total_in_all_accounts',
        'total_payments_out',
        'bank_out',
        'jazzcash_out',
        'total_out_all_accounts',
        'closing_cash',
        'bank_closing',
        'jazzcash_closing',
        'total_closing_all_accounts',
        'total_difference',
        'status',
        'remarks',
        'closed_by',
    ];

    protected $casts = [
        'date' => 'date',
        'opening_cash' => 'decimal:2',
        'bank_opening' => 'decimal:2',
        'jazzcash_opening' => 'decimal:2',
        'total_opening_all_accounts' => 'decimal:2',
        'total_cash_in' => 'decimal:2',
        'bank_in' => 'decimal:2',
        'jazzcash_in' => 'decimal:2',
        'total_in_all_accounts' => 'decimal:2',
        'total_payments_out' => 'decimal:2',
        'bank_out' => 'decimal:2',
        'jazzcash_out' => 'decimal:2',
        'total_out_all_accounts' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'bank_closing' => 'decimal:2',
        'jazzcash_closing' => 'decimal:2',
        'total_closing_all_accounts' => 'decimal:2',
        'total_difference' => 'decimal:2',
    ];

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public static function autoSyncDay($date): self
    {
        $dateStr = $date instanceof \Carbon\Carbon ? $date->toDateString() : substr((string)$date, 0, 10);

        $cashAccount = \App\Models\Account::where('type', 'cash')->first() ?? \App\Models\Account::getCashAccount();
        $baseCashOpening = (float) $cashAccount->opening_balance;
        $priorShiftCash = (float) \App\Models\ShiftClosing::whereDate('date', '<', $dateStr)->sum('total_counted_cash');
        $priorCashIn = (float) \App\Models\Transaction::whereDate('date', '<', $dateStr)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->where(function($q) use ($cashAccount) {
                $q->whereNull('account_id')->orWhere('account_id', $cashAccount->id);
            })
            ->sum('amount');
        $priorCashOut = (float) \App\Models\Transaction::whereDate('date', '<', $dateStr)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_out')
            ->where(function($q) use ($cashAccount) {
                $q->whereNull('account_id')->orWhere('account_id', $cashAccount->id);
            })
            ->sum('amount');

        $cashOpening = $baseCashOpening + $priorShiftCash + $priorCashIn - $priorCashOut;

        $bankOpening = (float) \App\Models\Account::where('type', 'bank')->sum('opening_balance')
            + (float) \App\Models\Transaction::whereHas('account', fn($q) => $q->where('type', 'bank'))->whereDate('date', '<', $dateStr)->where('type', 'payment_in')->sum('amount')
            - (float) \App\Models\Transaction::whereHas('account', fn($q) => $q->where('type', 'bank'))->whereDate('date', '<', $dateStr)->where('type', 'payment_out')->sum('amount');

        $jazzcashOpening = (float) \App\Models\Account::where('type', 'jazzcash')->sum('opening_balance')
            + (float) \App\Models\Transaction::whereHas('account', fn($q) => $q->where('type', 'jazzcash'))->whereDate('date', '<', $dateStr)->where('type', 'payment_in')->sum('amount')
            - (float) \App\Models\Transaction::whereHas('account', fn($q) => $q->where('type', 'jazzcash'))->whereDate('date', '<', $dateStr)->where('type', 'payment_out')->sum('amount');

        $totalOpeningAllAccounts = $cashOpening + $bankOpening + $jazzcashOpening;

        // Shifts for the date
        $shifts = \App\Models\ShiftClosing::whereDate('date', $dateStr)->get();
        $morningShift = $shifts->where('shift_type', 'morning')->first();
        $eveningShift = $shifts->where('shift_type', 'evening')->first();

        $shiftCashIn = (float) $shifts->sum('total_counted_cash');
        $morningBankIn = $morningShift ? (float)$morningShift->bank_amount : 0.00;
        $eveningBankIn = $eveningShift ? (float)$eveningShift->bank_amount : 0.00;
        $morningJazzIn = $morningShift ? (float)$morningShift->jazzcash_amount : 0.00;
        $eveningJazzIn = $eveningShift ? (float)$eveningShift->jazzcash_amount : 0.00;

        // Direct transactions for the date
        $directCashIn = (float) \App\Models\Transaction::whereDate('date', $dateStr)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->where(function($q) use ($cashAccount) {
                $q->whereNull('account_id')->orWhere('account_id', $cashAccount->id);
            })
            ->sum('amount');

        $directBankIn = (float) \App\Models\Transaction::whereDate('date', $dateStr)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->whereHas('account', fn($q) => $q->where('type', 'bank'))
            ->sum('amount');

        $directJazzIn = (float) \App\Models\Transaction::whereDate('date', $dateStr)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->whereHas('account', fn($q) => $q->where('type', 'jazzcash'))
            ->sum('amount');

        $cashIn = $shiftCashIn + $directCashIn;
        $bankIn = $morningBankIn + $eveningBankIn + $directBankIn;
        $jazzcashIn = $morningJazzIn + $eveningJazzIn + $directJazzIn;
        $totalInAllAccounts = $cashIn + $bankIn + $jazzcashIn;

        // Direct payments out for the date
        $directCashOut = (float) \App\Models\Transaction::whereDate('date', $dateStr)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_out')
            ->where(function($q) use ($cashAccount) {
                $q->whereNull('account_id')->orWhere('account_id', $cashAccount->id);
            })
            ->sum('amount');

        $directBankOut = (float) \App\Models\Transaction::whereDate('date', $dateStr)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_out')
            ->whereHas('account', fn($q) => $q->where('type', 'bank'))
            ->sum('amount');

        $directJazzOut = (float) \App\Models\Transaction::whereDate('date', $dateStr)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_out')
            ->whereHas('account', fn($q) => $q->where('type', 'jazzcash'))
            ->sum('amount');

        $cashOut = $directCashOut;
        $bankOut = $directBankOut;
        $jazzcashOut = $directJazzOut;
        $totalOutAllAccounts = $cashOut + $bankOut + $jazzcashOut;

        $cashClosing = $cashOpening + $cashIn - $cashOut;
        $bankClosing = $bankOpening + $bankIn - $bankOut;
        $jazzcashClosing = $jazzcashOpening + $jazzcashIn - $jazzcashOut;
        $totalClosingAllAccounts = $cashClosing + $bankClosing + $jazzcashClosing;

        $totalDifference = (float) $shifts->sum('difference');

        return static::updateOrCreate(
            ['date' => $dateStr],
            [
                'opening_cash' => $cashOpening,
                'bank_opening' => $bankOpening,
                'jazzcash_opening' => $jazzcashOpening,
                'total_opening_all_accounts' => $totalOpeningAllAccounts,
                'total_cash_in' => $cashIn,
                'bank_in' => $bankIn,
                'jazzcash_in' => $jazzcashIn,
                'total_in_all_accounts' => $totalInAllAccounts,
                'total_payments_out' => $cashOut,
                'bank_out' => $bankOut,
                'jazzcash_out' => $jazzcashOut,
                'total_out_all_accounts' => $totalOutAllAccounts,
                'closing_cash' => $cashClosing,
                'bank_closing' => $bankClosing,
                'jazzcash_closing' => $jazzcashClosing,
                'total_closing_all_accounts' => $totalClosingAllAccounts,
                'total_difference' => $totalDifference,
                'status' => 'closed',
                'closed_by' => auth()->id() ?? 1,
            ]
        );
    }

    public function getStatusBadgeAttribute(): string
    {
        return '<span class="badge bg-success-subtle text-success border border-success fw-bold"><i class="bi bi-check2-circle me-1"></i>Recorded</span>';
    }
}
