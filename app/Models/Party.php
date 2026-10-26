<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'is_credit',
        'is_cash',
        'phone',
        'address',
        'opening_balance',
        'current_balance',
    ];

    protected $casts = [
        'is_credit' => 'boolean',
        'is_cash' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function getTypeBadgeAttribute(): string
    {
        return match($this->type) {
            'supplier' => '<span class="badge bg-danger-subtle text-danger border border-danger">Supplier</span>',
            'trader'   => '<span class="badge bg-primary-subtle text-primary border border-primary">Trader</span>',
            'staff'    => '<span class="badge bg-warning-subtle text-warning border border-warning">Staff</span>',
            'customer' => '<span class="badge bg-success-subtle text-success border border-success">Customer</span>',
            default    => '<span class="badge bg-secondary">' . ucfirst($this->type) . '</span>',
        };
    }

    public function getDealingBadgesAttribute(): string
    {
        $badges = [];
        if ($this->is_credit) {
            $badges[] = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0.5" style="font-size: 0.68rem;"><i class="bi bi-credit-card me-1"></i>Credit</span>';
        }
        if ($this->is_cash) {
            $badges[] = '<span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.68rem;"><i class="bi bi-cash-stack me-1"></i>Cash</span>';
        }
        if (empty($badges)) {
            return '<span class="badge bg-light text-muted border px-1.5 py-0.5" style="font-size: 0.68rem;">None</span>';
        }
        return implode(' ', $badges);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Recomputes current balance dynamically from opening balance, transactions, and shift payments
     */
    public function recalculateCurrentBalance(): float
    {
        $balance = (float) $this->opening_balance;

        $transactions = $this->transactions()
            ->whereNull('deleted_at')
            ->get();

        $shiftPayments = ShiftClosingPartyPayment::where('party_id', $this->id)
            ->whereHas('shiftClosing', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->get();

        if ($this->type === 'customer' || $this->type === 'staff') {
            foreach ($transactions as $tx) {
                if ($tx->type === 'payment_in') {
                    $balance -= (float) $tx->amount;
                } else {
                    $balance += (float) $tx->amount;
                }
            }
            foreach ($shiftPayments as $sp) {
                $balance += (float) $sp->amount;
            }
        } else {
            // supplier or trader
            foreach ($transactions as $tx) {
                if ($tx->type === 'payment_out') {
                    $balance -= (float) $tx->amount;
                } else {
                    $balance += (float) $tx->amount;
                }
            }
            foreach ($shiftPayments as $sp) {
                $balance -= (float) $sp->amount;
            }
        }

        $this->current_balance = $balance;
        $this->saveQuietly();

        return $balance;
    }

    /**
     * Synchronize current balances for all parties
     */
    public static function syncAllBalances(): void
    {
        foreach (static::all() as $party) {
            $party->recalculateCurrentBalance();
        }
    }
}
