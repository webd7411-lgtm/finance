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

        // Determine scope: 'single', 'all_credit', 'all_cash', 'all', or 'multiple'
        $partyIdParam = $request->input('party_id');
        $partyIdsParam = array_filter(array_map('intval', (array) $request->input('party_ids', [])));
        $scopeParam = $request->input('scope', $request->input('mode'));

        if ($partyIdParam === 'all_credit' || $scopeParam === 'all_credit' || $scopeParam === 'credit') {
            $ledgerScope = 'all_credit';
            $party = null;
            $selectedParties = $allParties->where('is_credit', true)->values();
            $statementTitle = 'All Credit Parties (Combined Statement)';
        } elseif ($partyIdParam === 'all_cash' || $scopeParam === 'all_cash' || $scopeParam === 'cash') {
            $ledgerScope = 'all_cash';
            $party = null;
            $selectedParties = $allParties->where('is_cash', true)->values();
            $statementTitle = 'All Cash Parties (Combined Statement)';
        } elseif ($partyIdParam === 'all' || $scopeParam === 'all') {
            $ledgerScope = 'all';
            $party = null;
            $selectedParties = $allParties->values();
            $statementTitle = 'All Parties (Consolidated Statement)';
        } elseif (!empty($partyIdsParam)) {
            $selectedParties = $allParties->whereIn('id', $partyIdsParam)->values();
            if ($selectedParties->count() === 1) {
                $ledgerScope = 'single';
                $party = $selectedParties->first();
                $statementTitle = $party->name;
            } else {
                $ledgerScope = 'multiple';
                $party = null;
                $statementTitle = 'Selected Parties Statement (' . $selectedParties->count() . ' Parties)';
            }
        } else {
            $ledgerScope = 'single';
            if (!$party && $request->filled('party_id') && is_numeric($partyIdParam)) {
                $party = Party::find($partyIdParam);
            }
            $selectedParties = $party ? collect([$party]) : collect();
            $statementTitle = $party ? $party->name : 'Select Party';
        }

        $fromDate = $request->input('from_date', Carbon::today()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', Carbon::today()->toDateString());

        $transactions = collect();
        $partyGroups = [];
        $allEntries = [];
        $openingBalance = 0.00;
        $totalDebit = 0.00;
        $totalCredit = 0.00;
        $closingBalance = 0.00;

        if ($selectedParties->isNotEmpty()) {
            foreach ($selectedParties as $p) {
                // 1. Calculate opening balance for this party prior to $fromDate
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

                // 2. Fetch period transactions & shift payments for this party
                $periodTransactions = Transaction::where('party_id', $p->id)
                    ->whereDate('date', '>=', $fromDate)
                    ->whereDate('date', '<=', $toDate)
                    ->with(['party', 'account', 'category', 'creator'])
                    ->get();

                $periodShiftPayments = ShiftClosingPartyPayment::where('party_id', $p->id)
                    ->whereHas('shiftClosing', fn ($query) => $query
                        ->whereDate('date', '>=', $fromDate)
                        ->whereDate('date', '<=', $toDate))
                    ->with(['party', 'shiftClosing.cashier'])
                    ->get();

                // 3. Chronological sorting for this party
                $pItems = collect();
                foreach ($periodTransactions as $tx) {
                    $itemDate = $tx->date instanceof Carbon ? $tx->date->toDateString() : substr((string)$tx->date, 0, 10);
                    $pItems->push([
                        'date' => $itemDate,
                        'created_at' => $tx->created_at,
                        'kind' => 'transaction',
                        'record' => $tx,
                    ]);
                }
                foreach ($periodShiftPayments as $payment) {
                    $shiftDate = $payment->shiftClosing && $payment->shiftClosing->date instanceof Carbon
                        ? $payment->shiftClosing->date->toDateString()
                        : ($payment->shiftClosing ? substr((string)$payment->shiftClosing->date, 0, 10) : substr((string)$payment->created_at, 0, 10));
                    $pItems->push([
                        'date' => $shiftDate,
                        'created_at' => $payment->created_at,
                        'kind' => 'shift_payment',
                        'record' => $payment,
                    ]);
                }

                $pItems = $pItems->sort(function ($a, $b) {
                    $timeA = $a['created_at'] instanceof Carbon ? $a['created_at']->format('H:i:s.u') : '00:00:00';
                    $timeB = $b['created_at'] instanceof Carbon ? $b['created_at']->format('H:i:s.u') : '00:00:00';
                    return strcmp($a['date'] . ' ' . $timeA, $b['date'] . ' ' . $timeB);
                })->values();

                // 4. Running balance for this party
                $pRunning = $pOpening;
                $pTotalDebit = 0.00;
                $pTotalCredit = 0.00;
                $pEntries = [];

                foreach ($pItems as $item) {
                    $isShiftPayment = $item['kind'] === 'shift_payment';
                    $record = $item['record'];
                    $amount = (float) $record->amount;
                    $type = $isShiftPayment ? 'payment_out' : $record->type;
                    $debit = 0.00;
                    $credit = 0.00;

                    if ($p->type === 'customer' || $p->type === 'staff') {
                        if ($type === 'payment_out') {
                            $debit = $amount;
                            $pRunning += $debit;
                            $pTotalDebit += $debit;
                        } else {
                            $credit = $amount;
                            $pRunning -= $credit;
                            $pTotalCredit += $credit;
                        }
                    } else {
                        if ($type === 'payment_out') {
                            $debit = $amount;
                            $pRunning -= $debit;
                            $pTotalDebit += $debit;
                        } else {
                            $credit = $amount;
                            $pRunning += $credit;
                            $pTotalCredit += $credit;
                        }
                    }

                    $entry = (object) [
                        'id' => $isShiftPayment ? null : $record->id,
                        'date' => $item['date'],
                        'party_id' => $p->id,
                        'party_name' => $p->name,
                        'party_type' => ucfirst($p->type),
                        'party_dealing' => $p->dealing_badges,
                        'bill_no' => $isShiftPayment ? 'Shift #' . ($record->shift_closing_id ?? $record->shiftClosing->id ?? '') : $record->bill_no,
                        'type' => $type,
                        'description' => $isShiftPayment ? $record->details : $record->description,
                        'account_name' => $isShiftPayment ? 'Shift Closing Cash' : (($record->type ?? '') === 'purchase_bill' ? 'Credit Purchase (No Cash)' : ($record->account->name ?? 'Direct Counter')),
                        'debit' => $debit,
                        'credit' => $credit,
                        'running_balance' => $pRunning,
                        'creator' => $isShiftPayment
                            ? ($record->shiftClosing->cashier->name ?? 'System')
                            : ($record->creator->name ?? 'System'),
                        'transaction_id' => $isShiftPayment ? null : $record->id,
                    ];

                    $pEntries[] = $entry;
                    $allEntries[] = $entry;
                }

                $pClosing = $pRunning;

                $partyGroups[] = (object) [
                    'party' => $p,
                    'opening_balance' => $pOpening,
                    'transactions' => collect($pEntries),
                    'total_debit' => $pTotalDebit,
                    'total_credit' => $pTotalCredit,
                    'closing_balance' => $pClosing,
                ];

                $openingBalance += $pOpening;
                $totalDebit += $pTotalDebit;
                $totalCredit += $pTotalCredit;
                $closingBalance += $pClosing;
            }

            $transactions = collect($allEntries);
        }

        return view('ledgers.party', compact(
            'party',
            'allParties',
            'selectedParties',
            'partyGroups',
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
        // MODE A: WHOLE SHOP REPORT (Detailed Ledger: Date, Voucher #, Party, Details, Net Sale, 3 Columns per Account)
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

            $events = [];

            // 1. Shift Closings (Counter Cash & Sales, Digital Collections, & Drawer Disbursements)
            $shifts = ShiftClosing::whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate)
                ->with(['cashier', 'partyPayments.party', 'partyPayments.expenseCategory', 'transactions.account'])
                ->get();

            foreach ($shifts as $s) {
                $billInfo = $s->invoice_start && $s->invoice_end ? (number_format($s->invoice_start) . ' - ' . number_format($s->invoice_end)) : ($s->total_invoices ? (number_format($s->total_invoices) . ' bills') : '-');
                $netSale = max(0, (float) $s->total_sale - (float) $s->returns_amount);
                $countedCash = (float) $s->total_counted_cash;
                $dateString = $s->date instanceof Carbon ? $s->date->toDateString() : substr((string) $s->date, 0, 10);
                $timeString = $s->created_at ? $s->created_at->format('H:i:s.u') : '00:00:00';

                $shiftPartyPayments = $s->partyPayments;
                $partyPaymentsTotal = (float) $shiftPartyPayments->sum('amount');
                $shiftExpenses = (float) $s->expenses_amount;

                // Gross cash generated from counter before shift drawer cash disbursements
                $shiftCashInflow = $countedCash + $partyPaymentsTotal + $shiftExpenses;

                // Collect inflows across all accounts for this shift (Cash drawer + Digital collections)
                $shiftMovements = [
                    $defaultCashAccount->id => ['in' => $shiftCashInflow, 'out' => 0.00],
                ];

                // Digital/Bank collections recorded during shift closing
                foreach ($s->transactions as $stx) {
                    if ($stx->type === 'payment_in' && $stx->account_id) {
                        $accId = $stx->account_id;
                        if (!isset($shiftMovements[$accId])) {
                            $shiftMovements[$accId] = ['in' => 0.00, 'out' => 0.00];
                        }
                        $shiftMovements[$accId]['in'] += (float) $stx->amount;
                    }
                }

                // Fallback for legacy shifts that stored digital amounts directly on model without transaction records
                if ($s->transactions->isEmpty()) {
                    if ((float) $s->jazzcash_amount > 0) {
                        $jazzAcc = $accounts->firstWhere('type', 'jazzcash');
                        if ($jazzAcc) {
                            if (!isset($shiftMovements[$jazzAcc->id])) {
                                $shiftMovements[$jazzAcc->id] = ['in' => 0.00, 'out' => 0.00];
                            }
                            $shiftMovements[$jazzAcc->id]['in'] += (float) $s->jazzcash_amount;
                        }
                    }
                    if ((float) $s->bank_amount > 0) {
                        $bankAcc = $accounts->firstWhere('type', 'bank');
                        if ($bankAcc) {
                            if (!isset($shiftMovements[$bankAcc->id])) {
                                $shiftMovements[$bankAcc->id] = ['in' => 0.00, 'out' => 0.00];
                            }
                            $shiftMovements[$bankAcc->id]['in'] += (float) $s->bank_amount;
                        }
                    }
                }

                $totalShiftInflow = array_sum(array_column($shiftMovements, 'in'));

                if ($totalShiftInflow > 0 || $netSale > 0) {
                    $events[] = [
                        'date_sort' => $dateString . ' ' . $timeString . '_0',
                        'date' => $s->date instanceof Carbon ? $s->date->format('d-m-Y') : Carbon::parse($s->date)->format('d-m-Y'),
                        'raw_date' => $dateString,
                        'id' => 'shift-' . $s->id,
                        'is_shift' => true,
                        'shift_id' => $s->id,
                        'tx_id' => null,
                        'voucher_no' => 'SH-' . str_pad($s->id, 4, '0', STR_PAD_LEFT),
                        'bill_no' => $billInfo,
                        'party' => 'Counter Sales (' . ucfirst($s->shift_type) . ')',
                        'category' => 'Shift Sale Cash',
                        'description' => ucfirst($s->shift_type) . ' shift cash collected by ' . ($s->cashier->name ?? 'Cashier'),
                        'net_sale' => $netSale,
                        'account_movements' => $shiftMovements,
                    ];
                }

                // Shift Drawer Party Payments (Payment Out from Drawer)
                foreach ($shiftPartyPayments as $payment) {
                    $payCreatedAt = $payment->created_at ? $payment->created_at->format('H:i:s.u') : $timeString;
                    $partyName = $payment->party ? $payment->party->name : 'Supplier Cash Payout';
                    $catName = $payment->expenseCategory ? $payment->expenseCategory->name : 'Shift Payout';
                    $events[] = [
                        'date_sort' => $dateString . ' ' . $payCreatedAt . '_1_' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
                        'date' => $s->date instanceof Carbon ? $s->date->format('d-m-Y') : Carbon::parse($s->date)->format('d-m-Y'),
                        'raw_date' => $dateString,
                        'id' => 'shift-payment-' . $payment->id,
                        'is_shift' => true,
                        'shift_id' => $s->id,
                        'tx_id' => null,
                        'voucher_no' => 'SP-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT),
                        'bill_no' => 'Shift #' . $s->id,
                        'party' => $partyName,
                        'category' => $catName,
                        'description' => ($payment->details ?: 'Shift cash payout') . ' (' . ucfirst($s->shift_type) . ' Shift #SH-' . str_pad($s->id, 4, '0', STR_PAD_LEFT) . ')',
                        'net_sale' => 0.00,
                        'account_movements' => [
                            $defaultCashAccount->id => ['in' => 0.00, 'out' => (float) $payment->amount],
                        ],
                    ];
                }

                // Shift Direct Expenses (if any)
                if ($shiftExpenses > 0) {
                    $events[] = [
                        'date_sort' => $dateString . ' ' . $timeString . '_2',
                        'date' => $s->date instanceof Carbon ? $s->date->format('d-m-Y') : Carbon::parse($s->date)->format('d-m-Y'),
                        'raw_date' => $dateString,
                        'id' => 'shift-exp-' . $s->id,
                        'is_shift' => true,
                        'shift_id' => $s->id,
                        'tx_id' => null,
                        'voucher_no' => 'SE-' . str_pad($s->id, 4, '0', STR_PAD_LEFT),
                        'bill_no' => 'Shift #' . $s->id,
                        'party' => 'Shift Expense',
                        'category' => 'Operational Expense',
                        'description' => ($s->expenses_details ?: 'Shift operational expense') . ' (' . ucfirst($s->shift_type) . ' Shift #SH-' . str_pad($s->id, 4, '0', STR_PAD_LEFT) . ')',
                        'net_sale' => 0.00,
                        'account_movements' => [
                            $defaultCashAccount->id => ['in' => 0.00, 'out' => $shiftExpenses],
                        ],
                    ];
                }
            }

            // 2. Transactions (Vouchers for Cash & Bank Accounts outside shifts)
            $directTxns = Transaction::whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate)
                ->whereNull('shift_closing_id')
                ->whereIn('type', ['payment_in', 'payment_out'])
                ->with(['party', 'account', 'category', 'creator'])
                ->get();

            foreach ($directTxns as $tx) {
                $targetAccId = $tx->account_id ?: $defaultCashAccount->id;
                $in = ($tx->type === 'payment_in') ? (float) $tx->amount : 0.00;
                $out = ($tx->type === 'payment_out') ? (float) $tx->amount : 0.00;
                $dateString = $tx->date instanceof Carbon ? $tx->date->toDateString() : substr((string) $tx->date, 0, 10);
                $timeString = $tx->created_at ? $tx->created_at->format('H:i:s.u') : '00:00:00';

                $events[] = [
                    'date_sort' => $dateString . ' ' . $timeString,
                    'date' => $tx->date instanceof Carbon ? $tx->date->format('d-m-Y') : Carbon::parse($tx->date)->format('d-m-Y'),
                    'raw_date' => $dateString,
                    'id' => (string) $tx->id,
                    'is_shift' => false,
                    'shift_id' => $tx->shift_closing_id,
                    'tx_id' => $tx->id,
                    'voucher_no' => str_pad($tx->id, 5, '0', STR_PAD_LEFT),
                    'bill_no' => $tx->bill_no ?: '-',
                    'party' => $tx->party->name ?? 'Direct Counter / General',
                    'category' => $tx->category->name ?? '-',
                    'description' => $tx->description ?: ($tx->type === 'payment_in' ? 'Receipt' : 'Payment'),
                    'net_sale' => 0.00,
                    'account_movements' => [
                        $targetAccId => ['in' => $in, 'out' => $out],
                    ],
                ];
            }

            // Sort all events chronologically
            $sortedEvents = collect($events)->sortBy('date_sort')->values();

            $accountTotals = [];
            foreach ($accounts as $acc) {
                $accountTotals[$acc->id] = [
                    'in' => 0.00,
                    'out' => 0.00,
                ];
            }

            $rows = [];
            $totalNetSale = 0.00;

            foreach ($sortedEvents as $ev) {
                $movements = $ev['account_movements'] ?? [];
                $totalNetSale += $ev['net_sale'];

                $rowAccounts = [];
                foreach ($accounts as $acc) {
                    $mIn = (float) ($movements[$acc->id]['in'] ?? 0.00);
                    $mOut = (float) ($movements[$acc->id]['out'] ?? 0.00);

                    if (isset($runningBalances[$acc->id])) {
                        $runningBalances[$acc->id] += ($mIn - $mOut);
                    }
                    if (isset($accountTotals[$acc->id])) {
                        $accountTotals[$acc->id]['in'] += $mIn;
                        $accountTotals[$acc->id]['out'] += $mOut;
                    }

                    $rowAccounts[$acc->id] = [
                        'in' => $mIn,
                        'out' => $mOut,
                        'running' => $runningBalances[$acc->id] ?? 0.00,
                    ];
                }

                $rows[] = array_merge($ev, [
                    'accounts' => $rowAccounts,
                ]);
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

            // 2. Shift Counter Collections (Morning & Evening Shifts) & Drawer Disbursements
            $shifts = ShiftClosing::whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate)
                ->with(['cashier', 'partyPayments.party', 'partyPayments.expenseCategory'])
                ->get();

            foreach ($shifts as $s) {
                $partyPaymentsSum = (float) $s->partyPayments->sum('amount');
                $shiftExpenses = (float) $s->expenses_amount;
                $cashInFromShift = (float) $s->total_counted_cash + $partyPaymentsSum + $shiftExpenses;

                if ($cashInFromShift > 0) {
                    $billInfo = $s->invoice_start && $s->invoice_end ? (number_format($s->invoice_start) . ' - ' . number_format($s->invoice_end)) : ($s->total_invoices ? (number_format($s->total_invoices) . ' bills') : '-');
                    $items[] = [
                        'date' => $s->date instanceof Carbon ? $s->date->toDateString() : substr((string) $s->date, 0, 10),
                        'id' => 'shift-' . $s->id,
                        'shift_id' => $s->id,
                        'voucher_no' => 'SH-' . str_pad($s->id, 4, '0', STR_PAD_LEFT),
                        'bill_no' => $billInfo,
                        'type' => 'shift_closing',
                        'account' => $cashAccount->name,
                        'party' => 'Counter Sales (' . ucfirst($s->shift_type) . ')',
                        'category' => 'Shift Sale Cash',
                        'description' => ucfirst($s->shift_type) . ' shift cash collected by ' . ($s->cashier->name ?? 'Cashier'),
                        'in' => $cashInFromShift,
                        'out' => 0.00,
                        'created_at' => $s->created_at ? $s->created_at->copy()->subSeconds(2) : Carbon::parse($s->date),
                    ];
                }

                foreach ($s->partyPayments as $payment) {
                    $partyName = $payment->party ? $payment->party->name : 'Supplier Cash Payout';
                    $catName = $payment->expenseCategory ? $payment->expenseCategory->name : 'Shift Payout';
                    $items[] = [
                        'date' => $s->date instanceof Carbon ? $s->date->toDateString() : substr((string) $s->date, 0, 10),
                        'id' => 'shift-payment-' . $payment->id,
                        'shift_id' => $s->id,
                        'voucher_no' => 'SP-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT),
                        'bill_no' => 'Shift #' . $s->id,
                        'type' => 'shift_party_payment',
                        'account' => $cashAccount->name,
                        'party' => $partyName,
                        'category' => $catName,
                        'description' => ($payment->details ?: 'Shift cash payout') . ' (' . ucfirst($s->shift_type) . ' Shift #SH-' . str_pad($s->id, 4, '0', STR_PAD_LEFT) . ')',
                        'in' => 0.00,
                        'out' => (float) $payment->amount,
                        'created_at' => $payment->created_at ?: $s->created_at,
                    ];
                }

                if ($shiftExpenses > 0) {
                    $items[] = [
                        'date' => $s->date instanceof Carbon ? $s->date->toDateString() : substr((string) $s->date, 0, 10),
                        'id' => 'shift-exp-' . $s->id,
                        'shift_id' => $s->id,
                        'voucher_no' => 'SE-' . str_pad($s->id, 4, '0', STR_PAD_LEFT),
                        'bill_no' => 'Shift #' . $s->id,
                        'type' => 'shift_expense',
                        'account' => $cashAccount->name,
                        'party' => 'Shift Expense',
                        'category' => 'Operational Expense',
                        'description' => ($s->expenses_details ?: 'Shift operational expense') . ' (' . ucfirst($s->shift_type) . ' Shift #SH-' . str_pad($s->id, 4, '0', STR_PAD_LEFT) . ')',
                        'in' => 0.00,
                        'out' => $shiftExpenses,
                        'created_at' => $s->created_at ? $s->created_at->copy()->addSecond() : Carbon::parse($s->date),
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
