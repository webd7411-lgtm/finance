<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
        'account_number',
        'opening_balance',
        'current_balance',
    ];

    public function isActive(): bool
    {
        return ($this->status ?? 'active') === 'active';
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->isActive()
            ? '<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-circle me-1"></i>Active</span>'
            : '<span class="badge bg-secondary-subtle text-secondary border border-secondary"><i class="bi bi-dash-circle me-1"></i>Inactive</span>';
    }

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function getTypeBadgeAttribute(): string
    {
        return match($this->type) {
            'cash'     => '<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-cash me-1"></i>Auto Cash</span>',
            'jazzcash' => '<span class="badge bg-danger-subtle text-danger border border-danger"><i class="bi bi-phone me-1"></i>JazzCash</span>',
            'bank'     => '<span class="badge bg-primary-subtle text-primary border border-primary"><i class="bi bi-bank me-1"></i>Bank</span>',
            default    => '<span class="badge bg-secondary">' . ucfirst($this->type) . '</span>',
        };
    }

    public function isCash(): bool
    {
        return $this->type === 'cash';
    }

    public static function getCashAccount(): self
    {
        return static::firstOrCreate(
            ['type' => 'cash'],
            [
                'name' => 'Cash in Hand',
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
            ]
        );
    }

    public static function syncCashAccountBalance(): float
    {
        $cashAccount = static::getCashAccount();
        $baseOpening = (float) $cashAccount->opening_balance;
        $shiftCash = (float) \App\Models\ShiftClosing::sum('total_counted_cash');
        $directIn = (float) \App\Models\Transaction::whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->where(function ($q) use ($cashAccount) {
                $q->where('account_id', $cashAccount->id)->orWhereNull('account_id');
            })
            ->sum('amount');
        $directOut = (float) \App\Models\Transaction::whereNull('shift_closing_id')
            ->where('type', 'payment_out')
            ->where(function ($q) use ($cashAccount) {
                $q->where('account_id', $cashAccount->id)->orWhereNull('account_id');
            })
            ->sum('amount');

        $calculated = $baseOpening + $shiftCash + $directIn - $directOut;
        $cashAccount->current_balance = $calculated;
        $cashAccount->save();

        return $calculated;
    }

    /**
     * Unified calculation of opening balances across all accounts prior to a given date.
     * Guaranteed to match Day Closing, Daily Sale Register, and Financial Summary.
     *
     * @param string|\Carbon\Carbon $date
     * @return array
     */
    public static function getBalancesPriorToDate($date): array
    {
        $dateStr = $date instanceof \Carbon\Carbon ? $date->toDateString() : substr((string)$date, 0, 10);

        // 1. Cash Opening
        $cashAccount = static::where('type', 'cash')->first() ?? static::getCashAccount();
        $baseCashOpening = (float) $cashAccount->opening_balance;
        $priorShiftCash = (float) \App\Models\ShiftClosing::whereDate('date', '<', $dateStr)->sum('total_counted_cash');
        $priorCashIn = (float) \App\Models\Transaction::whereDate('date', '<', $dateStr)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->where(function ($q) use ($cashAccount) {
                $q->where('account_id', $cashAccount->id)->orWhereNull('account_id');
            })
            ->sum('amount');
        $priorCashOut = (float) \App\Models\Transaction::whereDate('date', '<', $dateStr)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_out')
            ->where(function ($q) use ($cashAccount) {
                $q->where('account_id', $cashAccount->id)->orWhereNull('account_id');
            })
            ->sum('amount');
        $cashOpening = $baseCashOpening + $priorShiftCash + $priorCashIn - $priorCashOut;

        // 2. Bank Opening
        $baseBankOpening = (float) static::where('type', 'bank')->sum('opening_balance');
        $priorShiftBank = (float) \App\Models\ShiftClosing::whereDate('date', '<', $dateStr)->sum('bank_amount');
        $priorBankIn = (float) \App\Models\Transaction::whereDate('date', '<', $dateStr)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->whereHas('account', fn($q) => $q->where('type', 'bank'))
            ->sum('amount');
        $priorBankOut = (float) \App\Models\Transaction::whereDate('date', '<', $dateStr)
            ->where('type', 'payment_out')
            ->whereHas('account', fn($q) => $q->where('type', 'bank'))
            ->sum('amount');
        $bankOpening = $baseBankOpening + $priorShiftBank + $priorBankIn - $priorBankOut;

        // 3. JazzCash Opening
        $baseJazzOpening = (float) static::where('type', 'jazzcash')->sum('opening_balance');
        $priorShiftJazz = (float) \App\Models\ShiftClosing::whereDate('date', '<', $dateStr)->sum('jazzcash_amount');
        $priorJazzIn = (float) \App\Models\Transaction::whereDate('date', '<', $dateStr)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->whereHas('account', fn($q) => $q->where('type', 'jazzcash'))
            ->sum('amount');
        $priorJazzOut = (float) \App\Models\Transaction::whereDate('date', '<', $dateStr)
            ->where('type', 'payment_out')
            ->whereHas('account', fn($q) => $q->where('type', 'jazzcash'))
            ->sum('amount');
        $jazzcashOpening = $baseJazzOpening + $priorShiftJazz + $priorJazzIn - $priorJazzOut;

        $totalOpening = $cashOpening + $bankOpening + $jazzcashOpening;

        // Account-wise breakdown
        $accounts = static::all();
        $firstBank = $accounts->where('type', 'bank')->first();
        $firstJazz = $accounts->where('type', 'jazzcash')->first();

        // Any legacy shift digital amount without transaction:
        $shiftIdsWithBankTx = \App\Models\Transaction::whereNotNull('shift_closing_id')
            ->whereHas('account', fn($q) => $q->where('type', 'bank'))
            ->whereDate('date', '<', $dateStr)
            ->pluck('shift_closing_id');
        $legacyShiftBank = (float) \App\Models\ShiftClosing::whereDate('date', '<', $dateStr)
            ->whereNotIn('id', $shiftIdsWithBankTx)
            ->sum('bank_amount');

        $shiftIdsWithJazzTx = \App\Models\Transaction::whereNotNull('shift_closing_id')
            ->whereHas('account', fn($q) => $q->where('type', 'jazzcash'))
            ->whereDate('date', '<', $dateStr)
            ->pluck('shift_closing_id');
        $legacyShiftJazz = (float) \App\Models\ShiftClosing::whereDate('date', '<', $dateStr)
            ->whereNotIn('id', $shiftIdsWithJazzTx)
            ->sum('jazzcash_amount');

        $accountBalances = [];
        foreach ($accounts as $acc) {
            $base = (float) $acc->opening_balance;
            if ($acc->type === 'cash') {
                $accountBalances[$acc->id] = $cashOpening;
            } elseif ($acc->type === 'bank') {
                $txIn = (float) \App\Models\Transaction::where('account_id', $acc->id)
                    ->whereDate('date', '<', $dateStr)
                    ->where('type', 'payment_in')
                    ->sum('amount');
                $txOut = (float) \App\Models\Transaction::where('account_id', $acc->id)
                    ->whereDate('date', '<', $dateStr)
                    ->where('type', 'payment_out')
                    ->sum('amount');
                $extra = ($firstBank && $acc->id === $firstBank->id) ? $legacyShiftBank : 0.0;
                $accountBalances[$acc->id] = $base + $txIn + $extra - $txOut;
            } elseif ($acc->type === 'jazzcash') {
                $txIn = (float) \App\Models\Transaction::where('account_id', $acc->id)
                    ->whereDate('date', '<', $dateStr)
                    ->where('type', 'payment_in')
                    ->sum('amount');
                $txOut = (float) \App\Models\Transaction::where('account_id', $acc->id)
                    ->whereDate('date', '<', $dateStr)
                    ->where('type', 'payment_out')
                    ->sum('amount');
                $extra = ($firstJazz && $acc->id === $firstJazz->id) ? $legacyShiftJazz : 0.0;
                $accountBalances[$acc->id] = $base + $txIn + $extra - $txOut;
            } else {
                $txIn = (float) \App\Models\Transaction::where('account_id', $acc->id)
                    ->whereDate('date', '<', $dateStr)
                    ->where('type', 'payment_in')
                    ->sum('amount');
                $txOut = (float) \App\Models\Transaction::where('account_id', $acc->id)
                    ->whereDate('date', '<', $dateStr)
                    ->where('type', 'payment_out')
                    ->sum('amount');
                $accountBalances[$acc->id] = $base + $txIn - $txOut;
            }
        }

        return [
            'cash' => $cashOpening,
            'bank' => $bankOpening,
            'jazzcash' => $jazzcashOpening,
            'total' => $totalOpening,
            'account_balances' => $accountBalances,
        ];
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
