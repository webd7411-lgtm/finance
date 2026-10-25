<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\ShiftClosing;
use App\Models\DayClosing;
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
        $allParties = Party::orderBy('type')->orderBy('name')->get();
        $creditPartiesCount = $allParties->where('is_credit', true)->count();
        $cashPartiesCount = $allParties->where('is_cash', true)->count();

        // Determine scope: 'single', 'all_credit', 'all_cash', or 'all'
        $partyIdParam = $request->input('party_id');
        $scopeParam = $request->input('scope', $request->input('mode'));

        if ($partyIdParam === 'all_credit' || $scopeParam === 'all_credit' || $scopeParam === 'credit') {
            $ledgerScope = 'all_credit';
            $party = null;
        } elseif ($partyIdParam === 'all_cash' || $scopeParam === 'all_cash' || $scopeParam === 'cash') {
            $ledgerScope = 'all_cash';
            $party = null;
        } elseif ($partyIdParam === 'all' || $scopeParam === 'all') {
            $ledgerScope = 'all';
            $party = null;
        } else {
            $ledgerScope = 'single';
            if (!$party && $request->filled('party_id') && is_numeric($partyIdParam)) {
                $party = Party::find($partyIdParam);
            }
            if (!$party && $allParties->isNotEmpty()) {
                $party = $allParties->first();
            }
        }

        $fromDate = $request->input('from_date', Carbon::today()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', Carbon::today()->toDateString());

        // Determine target parties
        if ($ledgerScope === 'all_credit') {
            $selectedParties = $allParties->where('is_credit', true)->values();
            $statementTitle = 'All Credit Parties (Combined Statement)';
        } elseif ($ledgerScope === 'all_cash') {
            $selectedParties = $allParties->where('is_cash', true)->values();
            $statementTitle = 'All Cash Parties (Combined Statement)';
        } elseif ($ledgerScope === 'all') {
            $selectedParties = $allParties;
            $statementTitle = 'All Parties (Consolidated Statement)';
        } else {
            $selectedParties = $party ? collect([$party]) : collect();
            $statementTitle = $party ? $party->name : 'Select Party';
        }

        $transactions = collect();
        $openingBalance = 0.00;
        $totalDebit = 0.00;
        $totalCredit = 0.00;
        $closingBalance = 0.00;

        if ($selectedParties->isNotEmpty()) {
            $partyIds = $selectedParties->pluck('id')->toArray();
            $partyMap = $selectedParties->keyBy('id');

            // 1. Calculate opening balance for all selected parties prior to $fromDate
            foreach ($selectedParties as $p) {
                $pOpening = (float) $p->opening_balance;

                $priorTransactions = Transaction::where('party_id', $p->id)
                    ->whereDate('date', '<', $fromDate)
                    ->get();
                $priorShiftPayments = ShiftClosingPartyPayment::where('party_id', $p->id)
                    ->whereHas('shiftClosing', fn ($query) => $query->whereDate('date', '<', $fromDate))
                    ->get();

                foreach ($priorTransactions as $tx) {
                    if ($p->type === 'customer' || $p->type === 'staff') {
                        if ($tx->type === 'payment_in') {
                            $pOpening -= (float) $tx->amount;
                        } else {
                            $pOpening += (float) $tx->amount;
                        }
                    } else {
                        if ($tx->type === 'payment_out') {
                            $pOpening -= (float) $tx->amount;
                        } else {
                            $pOpening += (float) $tx->amount;
                        }
                    }
                }

                foreach ($priorShiftPayments as $payment) {
                    $pOpening += ($p->type === 'customer' || $p->type === 'staff')
                        ? (float) $payment->amount
                        : -(float) $payment->amount;
                }

                $openingBalance += $pOpening;
            }

            // 2. Fetch period transactions & shift payments
            $periodTransactions = Transaction::whereIn('party_id', $partyIds)
                ->whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate)
                ->with(['party', 'account', 'category', 'creator'])
                ->get();

            $periodShiftPayments = ShiftClosingPartyPayment::whereIn('party_id', $partyIds)
                ->whereHas('shiftClosing', fn ($query) => $query
                    ->whereDate('date', '>=', $fromDate)
                    ->whereDate('date', '<=', $toDate))
                ->with(['party', 'shiftClosing.cashier'])
                ->get();

            // 3. Chronological sorting
            $ledgerItems = collect();
            foreach ($periodTransactions as $tx) {
                $itemDate = $tx->date instanceof Carbon ? $tx->date->toDateString() : substr((string)$tx->date, 0, 10);
                $ledgerItems->push([
                    'date' => $itemDate,
                    'created_at' => $tx->created_at,
                    'kind' => 'transaction',
                    'record' => $tx,
                    'party_id' => $tx->party_id,
                ]);
            }
            foreach ($periodShiftPayments as $payment) {
                $shiftDate = $payment->shiftClosing && $payment->shiftClosing->date instanceof Carbon
                    ? $payment->shiftClosing->date->toDateString()
                    : ($payment->shiftClosing ? substr((string)$payment->shiftClosing->date, 0, 10) : substr((string)$payment->created_at, 0, 10));
                $ledgerItems->push([
                    'date' => $shiftDate,
                    'created_at' => $payment->created_at,
                    'kind' => 'shift_payment',
                    'record' => $payment,
                    'party_id' => $payment->party_id,
                ]);
            }

            $ledgerItems = $ledgerItems->sortBy(function ($item) {
                $createdAtStr = $item['created_at'] instanceof Carbon ? $item['created_at']->format('H:i:s.u') : '00:00:00';
                return $item['date'] . ' ' . $createdAtStr;
            })->values();

            // 4. Running balance computation
            $currentRunning = $openingBalance;
            $ledgerEntries = [];

            foreach ($ledgerItems as $item) {
                $isShiftPayment = $item['kind'] === 'shift_payment';
                $record = $item['record'];
                $itemParty = $partyMap->get($item['party_id']) ?? $record->party;
                $amount = (float) $record->amount;
                $type = $isShiftPayment ? 'payment_out' : $record->type;
                $debit = 0.00;
                $credit = 0.00;

                $partyType = $itemParty ? $itemParty->type : 'supplier';

                if ($partyType === 'customer' || $partyType === 'staff') {
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
                    'party_id' => $item['party_id'],
                    'party_name' => $itemParty ? $itemParty->name : 'N/A',
                    'party_type' => $itemParty ? ucfirst($itemParty->type) : '',
                    'party_dealing' => $itemParty ? $itemParty->dealing_badges : '',
                    'bill_no' => $isShiftPayment ? 'Shift #' . ($record->shift_closing_id ?? $record->shiftClosing->id ?? '') : $record->bill_no,
                    'type' => $type,
                    'description' => $isShiftPayment ? $record->details : $record->description,
                    'account_name' => $isShiftPayment ? 'Shift Closing Cash' : (($record->type ?? '') === 'purchase_bill' ? 'Credit Purchase (No Cash)' : ($record->account->name ?? 'Direct Counter')),
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
            'selectedParties',
            'ledgerScope',
            'statementTitle',
            'creditPartiesCount',
            'cashPartiesCount',
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
     * Cash Book Register & Whole Shop Financial Matrix
     */
    public function cashBook(Request $request)
    {
        $allAccounts = Account::orderByRaw("CASE WHEN type = 'cash' THEN 1 WHEN type = 'bank' THEN 2 ELSE 3 END")
            ->orderBy('id', 'asc')
            ->get();

        $defaultCashAccount = $allAccounts->firstWhere('type', 'cash') ?? Account::getCashAccount();

        $selectedAccountId = $request->input('account_id', 'cash');
        $isWholeShop = ($selectedAccountId === 'whole_shop');

        $fromDate = $request->input('from_date', Carbon::today()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', Carbon::today()->toDateString());

        // ----------------------------------------------------
        // MODE A: WHOLE SHOP REPORT (Date, Net Sale, 3 Columns In/Out/Running per Account)
        // ----------------------------------------------------
        if ($isWholeShop) {
            $accounts = $allAccounts;
            $initialBalances = [];
            $runningBalances = [];

            foreach ($accounts as $acc) {
                $baseOpening = (float) $acc->opening_balance;
                if ($acc->type === 'cash') {
                    $priorShiftCash = (float) ShiftClosing::whereDate('date', '<', $fromDate)->sum('total_counted_cash');
                    $priorDirectIn = (float) Transaction::whereDate('date', '<', $fromDate)
                        ->whereNull('shift_closing_id')
                        ->where('type', 'payment_in')
                        ->where(function ($q) use ($acc) {
                            $q->where('account_id', $acc->id)->orWhereNull('account_id');
                        })
                        ->sum('amount');
                    $priorDirectOut = (float) Transaction::whereDate('date', '<', $fromDate)
                        ->whereNull('shift_closing_id')
                        ->where('type', 'payment_out')
                        ->where(function ($q) use ($acc) {
                            $q->where('account_id', $acc->id)->orWhereNull('account_id');
                        })
                        ->sum('amount');
                    $initialBalances[$acc->id] = $baseOpening + $priorShiftCash + $priorDirectIn - $priorDirectOut;
                } else {
                    $priorIn = (float) Transaction::where('account_id', $acc->id)
                        ->whereDate('date', '<', $fromDate)
                        ->where('type', 'payment_in')
                        ->sum('amount');
                    $priorOut = (float) Transaction::where('account_id', $acc->id)
                        ->whereDate('date', '<', $fromDate)
                        ->where('type', 'payment_out')
                        ->sum('amount');
                    $initialBalances[$acc->id] = $baseOpening + $priorIn - $priorOut;
                }
                $runningBalances[$acc->id] = $initialBalances[$acc->id];
            }

            // Pre-fetch shifts & transactions grouped by date string
            $shiftsByDate = ShiftClosing::whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate)
                ->get()
                ->groupBy(fn ($s) => $s->date instanceof Carbon ? $s->date->toDateString() : substr((string) $s->date, 0, 10));

            $txnsByDate = Transaction::whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate)
                ->get()
                ->groupBy(fn ($t) => $t->date instanceof Carbon ? $t->date->toDateString() : substr((string) $t->date, 0, 10));

            $rows = [];
            $cur = Carbon::parse($fromDate);
            $end = Carbon::parse($toDate);

            $accountTotals = [];
            foreach ($accounts as $acc) {
                $accountTotals[$acc->id] = [
                    'in' => 0.00,
                    'out' => 0.00,
                ];
            }
            $totalNetSale = 0.00;

            while ($cur->lte($end)) {
                $dateStr = $cur->toDateString();
                $dayShifts = $shiftsByDate->get($dateStr, collect());
                $dayTxns = $txnsByDate->get($dateStr, collect());

                $totalSale = (float) $dayShifts->sum('total_sale');
                $returns = (float) $dayShifts->sum('returns_amount');
                $netSale = max(0, $totalSale - $returns);
                $totalNetSale += $netSale;

                $dayAccounts = [];
                $dayHasActivity = ($netSale > 0);

                foreach ($accounts as $acc) {
                    if ($acc->type === 'cash') {
                        $in = (float) $dayShifts->sum('total_counted_cash') + (float) $dayTxns
                            ->whereNull('shift_closing_id')
                            ->where('type', 'payment_in')
                            ->filter(fn ($t) => $t->account_id == $acc->id || is_null($t->account_id))
                            ->sum('amount');

                        $out = (float) $dayTxns
                            ->whereNull('shift_closing_id')
                            ->where('type', 'payment_out')
                            ->filter(fn ($t) => $t->account_id == $acc->id || is_null($t->account_id))
                            ->sum('amount');
                    } else {
                        $in = (float) $dayTxns
                            ->where('account_id', $acc->id)
                            ->where('type', 'payment_in')
                            ->sum('amount');

                        $out = (float) $dayTxns
                            ->where('account_id', $acc->id)
                            ->where('type', 'payment_out')
                            ->sum('amount');
                    }

                    $runningBalances[$acc->id] += ($in - $out);
                    $accountTotals[$acc->id]['in'] += $in;
                    $accountTotals[$acc->id]['out'] += $out;

                    if ($in > 0 || $out > 0) {
                        $dayHasActivity = true;
                    }

                    $dayAccounts[$acc->id] = [
                        'in' => $in,
                        'out' => $out,
                        'running' => $runningBalances[$acc->id],
                    ];
                }

                $rows[] = [
                    'date' => $cur->format('d-m-Y'),
                    'day_name' => $cur->format('D'),
                    'raw_date' => $dateStr,
                    'net_sale' => $netSale,
                    'accounts' => $dayAccounts,
                    'has_activity' => $dayHasActivity,
                ];

                $cur->addDay();
            }

            $totalInAllAccounts = array_sum(array_column($accountTotals, 'in'));
            $totalOutAllAccounts = array_sum(array_column($accountTotals, 'out'));
            $totalOpeningAllAccounts = array_sum($initialBalances);
            $totalClosingAllAccounts = array_sum($runningBalances);
            $cashAccount = $defaultCashAccount;

            return view('ledgers.cash_book', compact(
                'isWholeShop',
                'allAccounts',
                'selectedAccountId',
                'accounts',
                'initialBalances',
                'rows',
                'accountTotals',
                'runningBalances',
                'totalNetSale',
                'totalInAllAccounts',
                'totalOutAllAccounts',
                'totalOpeningAllAccounts',
                'totalClosingAllAccounts',
                'cashAccount',
                'fromDate',
                'toDate'
            ));
        }

        // ----------------------------------------------------
        // MODE B: SINGLE ACCOUNT REGISTER (Default Cash in Hand or Selected Account)
        // ----------------------------------------------------
        if ($selectedAccountId === 'cash' || empty($selectedAccountId)) {
            $cashAccount = $defaultCashAccount;
        } else {
            $cashAccount = $allAccounts->firstWhere('id', (int) $selectedAccountId) ?? $defaultCashAccount;
        }

        $items = [];

        if ($cashAccount->type === 'cash') {
            // 1. Calculate opening cash prior to $fromDate (Dynamic & Continuous)
            $baseOpening = (float) $cashAccount->opening_balance;
            $priorShiftCash = (float) ShiftClosing::whereDate('date', '<', $fromDate)->sum('total_counted_cash');
            $priorDirectIn = (float) Transaction::whereDate('date', '<', $fromDate)
                ->whereNull('shift_closing_id')
                ->where('type', 'payment_in')
                ->where(function ($q) use ($cashAccount) {
                    $q->where('account_id', $cashAccount->id)->orWhereNull('account_id');
                })
                ->sum('amount');
            $priorDirectOut = (float) Transaction::whereDate('date', '<', $fromDate)
                ->whereNull('shift_closing_id')
                ->where('type', 'payment_out')
                ->where(function ($q) use ($cashAccount) {
                    $q->where('account_id', $cashAccount->id)->orWhereNull('account_id');
                })
                ->sum('amount');

            $openingBalance = $baseOpening + $priorShiftCash + $priorDirectIn - $priorDirectOut;

            // 2. Shift Counter Collections (Morning & Evening Shifts)
            $shifts = ShiftClosing::whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate)
                ->with('cashier')
                ->get();

            foreach ($shifts as $s) {
                if ((float) $s->total_counted_cash > 0) {
                    $billInfo = $s->invoice_start && $s->invoice_end ? "#{$s->invoice_start} - #{$s->invoice_end}" : ($s->total_invoices ? "{$s->total_invoices} bills" : '-');
                    $items[] = [
                        'date' => $s->date instanceof Carbon ? $s->date->toDateString() : substr((string) $s->date, 0, 10),
                        'id' => 'shift-' . $s->id,
                        'voucher_no' => 'SH-' . str_pad($s->id, 4, '0', STR_PAD_LEFT),
                        'bill_no' => $billInfo,
                        'type' => 'shift_closing',
                        'account' => $cashAccount->name,
                        'party' => 'Counter Sales (' . ucfirst($s->shift_type) . ')',
                        'category' => 'Shift Sale Cash',
                        'description' => ucfirst($s->shift_type) . ' shift cash collected by ' . ($s->cashier->name ?? 'Cashier'),
                        'in' => (float) $s->total_counted_cash,
                        'out' => 0.00,
                        'created_at' => $s->created_at,
                    ];
                }
            }

            // 3. Direct Cash Transactions (payment_in & payment_out from cash register)
            $directTxns = Transaction::whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate)
                ->whereNull('shift_closing_id')
                ->whereIn('type', ['payment_in', 'payment_out'])
                ->where(function ($q) use ($cashAccount) {
                    $q->where('account_id', $cashAccount->id)->orWhereNull('account_id');
                })
                ->with(['party', 'account', 'category', 'creator'])
                ->get();

            foreach ($directTxns as $tx) {
                $in = ($tx->type === 'payment_in') ? (float) $tx->amount : 0.00;
                $out = ($tx->type === 'payment_out') ? (float) $tx->amount : 0.00;

                $items[] = [
                    'date' => $tx->date instanceof Carbon ? $tx->date->toDateString() : substr((string) $tx->date, 0, 10),
                    'id' => (string) $tx->id,
                    'voucher_no' => str_pad($tx->id, 5, '0', STR_PAD_LEFT),
                    'bill_no' => $tx->bill_no ?? '-',
                    'type' => $tx->type,
                    'account' => $cashAccount->name,
                    'party' => $tx->party->name ?? 'Direct Counter / General',
                    'category' => $tx->category->name ?? '-',
                    'description' => $tx->description ?: ($tx->type === 'payment_in' ? 'Cash Receipt' : 'Cash Payment'),
                    'in' => $in,
                    'out' => $out,
                    'created_at' => $tx->created_at,
                ];
            }
        } else {
            // Non-cash account (Bank, JazzCash, etc.)
            $baseOpening = (float) $cashAccount->opening_balance;
            $priorIn = (float) Transaction::where('account_id', $cashAccount->id)
                ->whereDate('date', '<', $fromDate)
                ->where('type', 'payment_in')
                ->sum('amount');
            $priorOut = (float) Transaction::where('account_id', $cashAccount->id)
                ->whereDate('date', '<', $fromDate)
                ->where('type', 'payment_out')
                ->sum('amount');
            $openingBalance = $baseOpening + $priorIn - $priorOut;

            $txns = Transaction::where('account_id', $cashAccount->id)
                ->whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate)
                ->with(['party', 'account', 'category', 'creator'])
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($txns as $tx) {
                $in = ($tx->type === 'payment_in') ? (float) $tx->amount : 0.00;
                $out = ($tx->type === 'payment_out') ? (float) $tx->amount : 0.00;

                $items[] = [
                    'date' => $tx->date instanceof Carbon ? $tx->date->toDateString() : substr((string) $tx->date, 0, 10),
                    'id' => (string) $tx->id,
                    'voucher_no' => str_pad($tx->id, 5, '0', STR_PAD_LEFT),
                    'bill_no' => $tx->bill_no ?? '-',
                    'type' => $tx->type,
                    'account' => $cashAccount->name,
                    'party' => $tx->party->name ?? 'Direct Payee / General',
                    'category' => $tx->category->name ?? '-',
                    'description' => $tx->description ?: ($tx->type === 'payment_in' ? 'Account Deposit' : 'Account Withdrawal'),
                    'in' => $in,
                    'out' => $out,
                    'created_at' => $tx->created_at,
                ];
            }
        }

        $sortedItems = collect($items)->sortBy(function ($item) {
            return $item['date'] . ' ' . ($item['created_at'] ?? '');
        })->values();

        $running = $openingBalance;
        $totalIn = 0.00;
        $totalOut = 0.00;
        $entries = [];

        foreach ($sortedItems as $item) {
            $running = $running + $item['in'] - $item['out'];
            $totalIn += $item['in'];
            $totalOut += $item['out'];

            $entries[] = (object) array_merge($item, [
                'running_balance' => $running,
                'creator' => $item['party'] ?? 'System',
            ]);
        }

        $transactions = collect($entries);
        $closingBalance = $running;

        return view('ledgers.cash_book', compact(
            'isWholeShop',
            'allAccounts',
            'selectedAccountId',
            'cashAccount',
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
