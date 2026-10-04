<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\ShiftClosingPartyPayment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LedgerController extends Controller
{
    /**
     * Party Statement of Account / Khata Register
     */
    public function partyLedger(Request $request, ?Party $party = null)
    {
        // Resolve Party if ID provided via query string or route
        if (!$party && $request->filled('party_id')) {
            $party = Party::find($request->party_id);
        }

        $allParties = Party::orderBy('type')->orderBy('name')->get();

        // If no party selected, pick the first one by default if available
        if (!$party && $allParties->isNotEmpty()) {
            $party = $allParties->first();
        }

        $fromDate = $request->input('from_date', Carbon::today()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', Carbon::today()->toDateString());

        $transactions = collect();
        $openingBalance = 0.00;
        $totalDebit = 0.00;
        $totalCredit = 0.00;
        $closingBalance = 0.00;

        if ($party) {
            // Calculate historical transactions before $fromDate
            $priorTransactions = Transaction::where('party_id', $party->id)
                ->whereDate('date', '<', $fromDate)
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            $priorShiftPayments = ShiftClosingPartyPayment::where('party_id', $party->id)
                ->whereHas('shiftClosing', fn ($query) => $query->whereDate('date', '<', $fromDate))
                ->get();

            // Compute starting balance before from_date
            $openingBalance = (float) $party->opening_balance;
            foreach ($priorTransactions as $tx) {
                if ($party->type === 'customer' || $party->type === 'staff') {
                    // Payment In reduces receivable; Payment Out increases receivable
                    if ($tx->type === 'payment_in') {
                        $openingBalance -= (float) $tx->amount;
                    } else {
                        $openingBalance += (float) $tx->amount;
                    }
                } else {
                    // Supplier / Trader: Payment Out reduces payable; Payment In increases payable
                    if ($tx->type === 'payment_out') {
                        $openingBalance -= (float) $tx->amount;
                    } else {
                        $openingBalance += (float) $tx->amount;
                    }
                }
            }
            foreach ($priorShiftPayments as $payment) {
                $openingBalance += ($party->type === 'customer' || $party->type === 'staff')
                    ? (float) $payment->amount
                    : -(float) $payment->amount;
            }

            // Fetch transactions in selected date range
            $periodTransactions = Transaction::where('party_id', $party->id)
                ->whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate)
                ->with(['account', 'category', 'creator'])
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            $periodShiftPayments = ShiftClosingPartyPayment::where('party_id', $party->id)
                ->whereHas('shiftClosing', fn ($query) => $query
                    ->whereDate('date', '>=', $fromDate)
                    ->whereDate('date', '<=', $toDate))
                ->with(['shiftClosing.cashier'])
                ->get();

            // Build chronological ledger entries with running balance
            $currentRunning = $openingBalance;
            $ledgerEntries = [];

            $ledgerItems = collect();
            foreach ($periodTransactions as $tx) {
                $ledgerItems->push([
                    'date' => $tx->date->toDateString(),
                    'created_at' => $tx->created_at,
                    'kind' => 'transaction',
                    'record' => $tx,
                ]);
            }
            foreach ($periodShiftPayments as $payment) {
                $ledgerItems->push([
                    'date' => $payment->shiftClosing->date->toDateString(),
                    'created_at' => $payment->created_at,
                    'kind' => 'shift_payment',
                    'record' => $payment,
                ]);
            }
            $ledgerItems = $ledgerItems->sortBy(fn ($item) => $item['date'] . ' ' . $item['created_at']->format('H:i:s.u'))->values();

            foreach ($ledgerItems as $item) {
                $isShiftPayment = $item['kind'] === 'shift_payment';
                $record = $item['record'];
                $amount = (float) $record->amount;
                $type = $isShiftPayment ? 'payment_out' : $record->type;
                $debit = 0.00;
                $credit = 0.00;

                if ($party->type === 'customer' || $party->type === 'staff') {
                    if ($type === 'payment_out') {
                        $debit = $amount;
                        $currentRunning += $debit;
                        $totalDebit += $debit;
                    } else {
                        $credit = $amount;
                        $currentRunning -= $credit;
                        $totalCredit += $credit;
                    }
                } else {
                    // Supplier / Trader
                    if ($type === 'payment_out') {
                        $debit = $amount;
                        $currentRunning -= $debit;
                        $totalDebit += $debit;
                    } else {
                        $credit = $amount;
                        $currentRunning += $credit;
                        $totalCredit += $credit;
                    }
                }

                $ledgerEntries[] = (object) [
                    'id' => $isShiftPayment ? null : $record->id,
                    'date' => $item['date'],
                    'bill_no' => $isShiftPayment ? 'Shift #' . $record->shift_closing_id : $record->bill_no,
                    'type' => $type,
                    'description' => $isShiftPayment ? $record->details : $record->description,
                    'account_name' => $isShiftPayment ? 'Shift Closing Cash' : ($record->account->name ?? 'Cash/Bank'),
                    'debit' => $debit,
                    'credit' => $credit,
                    'running_balance' => $currentRunning,
                    'creator' => $isShiftPayment
                        ? ($record->shiftClosing->cashier->name ?? 'System')
                        : ($record->creator->name ?? 'System'),
                    'transaction_id' => $isShiftPayment ? null : $record->id,
                ];
            }

            $transactions = collect($ledgerEntries);
            $closingBalance = $currentRunning;
        }

        return view('ledgers.party', compact(
            'party',
            'allParties',
            'fromDate',
            'toDate',
            'openingBalance',
            'transactions',
            'totalDebit',
            'totalCredit',
            'closingBalance'
        ));
    }

    /**
     * Cash Book Register
     */
    public function cashBook(Request $request)
    {
        $cashAccounts = Account::where('type', 'cash')->get();
        $selectedAccountId = $request->input('account_id');

        $accountQuery = Account::where('type', 'cash');
        if ($selectedAccountId) {
            $accountQuery->where('id', $selectedAccountId);
        }
        $targetAccounts = $accountQuery->get();
        $targetAccountIds = $targetAccounts->pluck('id')->toArray();

        $fromDate = $request->input('from_date', Carbon::today()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', Carbon::today()->toDateString());

        // Opening cash balance before from_date
        $baseOpening = $targetAccounts->sum('opening_balance');
        $priorIn = Transaction::whereIn('account_id', $targetAccountIds)
            ->whereDate('date', '<', $fromDate)
            ->where('type', 'payment_in')
            ->sum('amount');
        $priorOut = Transaction::whereIn('account_id', $targetAccountIds)
            ->whereDate('date', '<', $fromDate)
            ->where('type', 'payment_out')
            ->sum('amount');

        $openingBalance = (float) $baseOpening + (float) $priorIn - (float) $priorOut;

        // Transactions in date range
        $periodTransactions = Transaction::whereIn('account_id', $targetAccountIds)
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->with(['party', 'account', 'category', 'creator'])
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $running = $openingBalance;
        $totalIn = 0.00;
        $totalOut = 0.00;
        $entries = [];

        foreach ($periodTransactions as $tx) {
            $in = ($tx->type === 'payment_in') ? (float) $tx->amount : 0.00;
            $out = ($tx->type === 'payment_out') ? (float) $tx->amount : 0.00;

            $running = $running + $in - $out;
            $totalIn += $in;
            $totalOut += $out;

            $entries[] = (object) [
                'id' => $tx->id,
                'date' => $tx->date,
                'voucher_no' => str_pad($tx->id, 5, '0', STR_PAD_LEFT),
                'bill_no' => $tx->bill_no,
                'type' => $tx->type,
                'account' => $tx->account->name ?? 'Cash Account',
                'party' => $tx->party->name ?? 'Direct Counter / General',
                'category' => $tx->category->name ?? '-',
                'description' => $tx->description,
                'in' => $in,
                'out' => $out,
                'running_balance' => $running,
                'creator' => $tx->creator->name ?? 'System',
            ];
        }

        $transactions = collect($entries);
        $closingBalance = $running;

        return view('ledgers.cash_book', compact(
            'cashAccounts',
            'selectedAccountId',
            'fromDate',
            'toDate',
            'openingBalance',
            'transactions',
            'totalIn',
            'totalOut',
            'closingBalance'
        ));
    }

    /**
     * Bank Book & Digital Wallets Register
     */
    public function bankBook(Request $request)
    {
        $bankAccounts = Account::whereIn('type', ['bank', 'jazzcash'])->get();
        $selectedAccountId = $request->input('account_id');

        $accountQuery = Account::whereIn('type', ['bank', 'jazzcash']);
        if ($selectedAccountId) {
            $accountQuery->where('id', $selectedAccountId);
        }
        $targetAccounts = $accountQuery->get();
        $targetAccountIds = $targetAccounts->pluck('id')->toArray();

        $fromDate = $request->input('from_date', Carbon::today()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', Carbon::today()->toDateString());

        // Opening bank balance before from_date
        $baseOpening = $targetAccounts->sum('opening_balance');
        $priorIn = Transaction::whereIn('account_id', $targetAccountIds)
            ->whereDate('date', '<', $fromDate)
            ->where('type', 'payment_in')
            ->sum('amount');
        $priorOut = Transaction::whereIn('account_id', $targetAccountIds)
            ->whereDate('date', '<', $fromDate)
            ->where('type', 'payment_out')
            ->sum('amount');

        $openingBalance = (float) $baseOpening + (float) $priorIn - (float) $priorOut;

        // Transactions in date range
        $periodTransactions = Transaction::whereIn('account_id', $targetAccountIds)
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->with(['party', 'account', 'category', 'creator'])
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $running = $openingBalance;
        $totalIn = 0.00;
        $totalOut = 0.00;
        $entries = [];

        foreach ($periodTransactions as $tx) {
            $in = ($tx->type === 'payment_in') ? (float) $tx->amount : 0.00;
            $out = ($tx->type === 'payment_out') ? (float) $tx->amount : 0.00;

            $running = $running + $in - $out;
            $totalIn += $in;
            $totalOut += $out;

            $entries[] = (object) [
                'id' => $tx->id,
                'date' => $tx->date,
                'voucher_no' => str_pad($tx->id, 5, '0', STR_PAD_LEFT),
                'bill_no' => $tx->bill_no,
                'type' => $tx->type,
                'account' => $tx->account->name ?? 'Bank / Wallet',
                'account_type' => $tx->account->type ?? 'bank',
                'party' => $tx->party->name ?? 'Direct Counter / General',
                'category' => $tx->category->name ?? '-',
                'description' => $tx->description,
                'in' => $in,
                'out' => $out,
                'running_balance' => $running,
                'creator' => $tx->creator->name ?? 'System',
            ];
        }

        $transactions = collect($entries);
        $closingBalance = $running;

        return view('ledgers.bank_book', compact(
            'bankAccounts',
            'selectedAccountId',
            'fromDate',
            'toDate',
            'openingBalance',
            'transactions',
            'totalIn',
            'totalOut',
            'closingBalance'
        ));
    }
}
