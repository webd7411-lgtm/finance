<?php

namespace App\Http\Controllers;

use App\Models\DayClosing;
use App\Models\ShiftClosing;
use App\Models\Transaction;
use App\Models\ExpenseCategory;
use App\Models\Account;
use App\Models\Party;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Daily Closing Consolidated Summary Report
     */
    public function dailyClosingSummary(Request $request)
    {
        $fromDate = $request->input('from_date', Carbon::today()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', Carbon::today()->toDateString());

        $closings = DayClosing::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->with('closer')
            ->orderBy('date', 'desc')
            ->get();

        // ----------------------------------------------------
        // PAYMENTS IN (Received Breakdown)
        // ----------------------------------------------------
        $paymentsIn = Transaction::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->where('type', 'payment_in')
            ->with(['party', 'account', 'category', 'creator'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalItemizedPaymentsIn = (float) $paymentsIn->sum('amount');

        // ----------------------------------------------------
        // PAYMENTS OUT (Disbursed Cash/Bank & Shift Payouts Breakdown)
        // ----------------------------------------------------
        // 1. Direct Voucher Payments Out
        $directVoucherPayments = Transaction::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->where('type', 'payment_out')
            ->with(['party', 'account', 'category', 'creator'])
            ->get()
            ->map(function ($tx) {
                return (object) [
                    'id' => 'tx-' . $tx->id,
                    'date' => $tx->date instanceof Carbon ? $tx->date : Carbon::parse($tx->date),
                    'bill_no' => $tx->bill_no ?: ('#TX-' . str_pad($tx->id, 4, '0', STR_PAD_LEFT)),
                    'party' => $tx->party,
                    'party_name' => $tx->party->name ?? ($tx->category->name ?? 'Direct Operational Expense'),
                    'party_type' => $tx->party->type ?? 'Direct Voucher',
                    'party_phone' => $tx->party->phone ?? null,
                    'account' => $tx->account,
                    'account_name' => $tx->account->name ?? 'Cash Drawer',
                    'category' => $tx->category,
                    'category_name' => $tx->category->name ?? 'Direct Voucher Payment',
                    'description' => $tx->description ?: 'Payment disbursed for supplies, expenses, or advance',
                    'amount' => (float) $tx->amount,
                    'type' => $tx->type,
                    'shift_name' => null,
                    'source' => 'voucher',
                    'created_at' => $tx->created_at,
                ];
            });

        // 2. Shift Supplier & Party Payments (Cash Payouts from Drawer)
        $shiftPartyPayments = \App\Models\ShiftClosingPartyPayment::whereHas('shiftClosing', function($q) use ($fromDate, $toDate) {
                $q->whereDate('date', '>=', $fromDate)->whereDate('date', '<=', $toDate);
            })
            ->with(['party', 'shiftClosing.cashier'])
            ->get()
            ->map(function ($sp) {
                $shiftLabel = $sp->shiftClosing && $sp->shiftClosing->shift_type === 'morning' ? 'Morning Shift' : 'Evening Shift';
                $shiftDate = $sp->shiftClosing ? ($sp->shiftClosing->date instanceof Carbon ? $sp->shiftClosing->date : Carbon::parse($sp->shiftClosing->date)) : Carbon::parse($sp->created_at);

                return (object) [
                    'id' => 'shift-p-' . $sp->id,
                    'date' => $shiftDate,
                    'bill_no' => $shiftLabel . ' #' . ($sp->shiftClosing->id ?? $sp->id),
                    'party' => $sp->party,
                    'party_name' => $sp->party->name ?? 'Direct Payee',
                    'party_type' => $sp->party->type ?? 'Supplier / Party',
                    'party_phone' => $sp->party->phone ?? null,
                    'account' => (object) ['name' => 'Cash Drawer (' . $shiftLabel . ')'],
                    'account_name' => 'Cash Drawer (' . $shiftLabel . ')',
                    'category' => (object) ['name' => 'Shift Supplier Payout'],
                    'category_name' => 'Shift Supplier Payout',
                    'description' => $sp->details ?: 'Shift cash payout to supplier / party',
                    'amount' => (float) $sp->amount,
                    'type' => 'payment_out',
                    'shift_name' => $shiftLabel,
                    'source' => 'shift_party',
                    'created_at' => $sp->created_at,
                ];
            });

        // 3. Shift Customer Sales Return Refunds (Refund Outflows from Drawer)
        $shiftReturnRefunds = ShiftClosing::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->where('returns_amount', '>', 0)
            ->with(['cashier'])
            ->get()
            ->map(function ($sr) {
                $shiftLabel = $sr->shift_type === 'morning' ? 'Morning Shift' : 'Evening Shift';
                $shiftDate = $sr->date instanceof Carbon ? $sr->date : Carbon::parse($sr->date);
                $retCount = $sr->total_return_invoices > 0 ? " ({$sr->total_return_invoices} bills)" : "";
                $retDetails = ($sr->return_invoice_start && $sr->return_invoice_end)
                    ? "Invoices #{$sr->return_invoice_start} - #{$sr->return_invoice_end}{$retCount}"
                    : ($sr->return_invoice_number ? "Invoice {$sr->return_invoice_number}" : "Shift Customer Return Refund");

                return (object) [
                    'id' => 'return-shift-' . $sr->id,
                    'date' => $shiftDate,
                    'bill_no' => $sr->return_invoice_number ? ('Ret ' . $sr->return_invoice_number) : ($shiftLabel . ' Return'),
                    'party' => null,
                    'party_name' => 'Sales Return / Customer Refund',
                    'party_type' => 'Sale Return',
                    'party_phone' => null,
                    'account' => (object) ['name' => 'Cash Drawer (' . $shiftLabel . ')'],
                    'account_name' => 'Cash Drawer (' . $shiftLabel . ')',
                    'category' => (object) ['name' => 'Sale Return'],
                    'category_name' => 'Sale Return',
                    'description' => $retDetails,
                    'amount' => (float) $sr->returns_amount,
                    'type' => 'payment_out',
                    'shift_name' => $shiftLabel,
                    'source' => 'shift_return',
                    'created_at' => $sr->created_at,
                ];
            });

        // 4. Shift Operating Expenses (Counter Expenses from Drawer)
        $shiftExpensesList = ShiftClosing::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->where('expenses_amount', '>', 0)
            ->with(['cashier'])
            ->get()
            ->map(function ($se) {
                $shiftLabel = $se->shift_type === 'morning' ? 'Morning Shift' : 'Evening Shift';
                $shiftDate = $se->date instanceof Carbon ? $se->date : Carbon::parse($se->date);
                $expDetails = $se->expenses_details ?: ('Shift operational expenses (' . $shiftLabel . ')');

                return (object) [
                    'id' => 'expense-shift-' . $se->id,
                    'date' => $shiftDate,
                    'bill_no' => $shiftLabel . ' Expense',
                    'party' => null,
                    'party_name' => 'Shift Operating Expense',
                    'party_type' => 'Expense',
                    'party_phone' => null,
                    'account' => (object) ['name' => 'Cash Drawer (' . $shiftLabel . ')'],
                    'account_name' => 'Cash Drawer (' . $shiftLabel . ')',
                    'category' => (object) ['name' => 'Shift Expense'],
                    'category_name' => 'Shift Expense',
                    'description' => $expDetails,
                    'amount' => (float) $se->expenses_amount,
                    'type' => 'payment_out',
                    'shift_name' => $shiftLabel,
                    'source' => 'shift_expense',
                    'created_at' => $se->created_at,
                ];
            });

        // Combined Payments Out List (Sorted Chronologically Descending)
        $paymentsOut = $directVoucherPayments
            ->concat($shiftPartyPayments)
            ->concat($shiftReturnRefunds)
            ->concat($shiftExpensesList)
            ->sortByDesc(function ($item) {
                $d = $item->date ? $item->date->format('Y-m-d') : '1970-01-01';
                $t = $item->created_at ? $item->created_at->format('H:i:s') : '00:00:00';
                return $d . '_' . $t . '_' . $item->id;
            })
            ->values();

        $totalItemizedPaymentsOut = (float) $paymentsOut->sum('amount');

        // ----------------------------------------------------
        // 1. ALL-ACCOUNTS OPENING BALANCES
        // ----------------------------------------------------
        $prevDayClosing = DayClosing::where('date', '<', $fromDate)
            ->where('status', 'closed')
            ->orderBy('date', 'desc')
            ->first();

        if ($prevDayClosing) {
            $cashOpening = (float) $prevDayClosing->closing_cash;
        } elseif ($closings->isNotEmpty() && (float) $closings->last()->opening_cash > 0) {
            $cashOpening = (float) $closings->last()->opening_cash;
        } else {
            $cashOpening = (float) Account::where('type', 'cash')->sum('opening_balance');
        }

        $bankOpening = (float) Account::where('type', 'bank')->sum('opening_balance')
            + (float) Transaction::whereHas('account', fn($q) => $q->where('type', 'bank'))->whereDate('date', '<', $fromDate)->where('type', 'payment_in')->sum('amount')
            - (float) Transaction::whereHas('account', fn($q) => $q->where('type', 'bank'))->whereDate('date', '<', $fromDate)->where('type', 'payment_out')->sum('amount');

        $jazzcashOpening = (float) Account::where('type', 'jazzcash')->sum('opening_balance')
            + (float) Transaction::whereHas('account', fn($q) => $q->where('type', 'jazzcash'))->whereDate('date', '<', $fromDate)->where('type', 'payment_in')->sum('amount')
            - (float) Transaction::whereHas('account', fn($q) => $q->where('type', 'jazzcash'))->whereDate('date', '<', $fromDate)->where('type', 'payment_out')->sum('amount');

        $totalOpeningAllAccounts = $cashOpening + $bankOpening + $jazzcashOpening;
        $periodOpeningCash = $cashOpening;

        // ----------------------------------------------------
        // 2. ALL-ACCOUNTS INFLOWS (+)
        // ----------------------------------------------------
        $shiftCashIn = (float) ShiftClosing::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->sum('total_counted_cash');

        $directCashIn = (float) Transaction::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->where(function($q) {
                $q->whereNull('account_id')
                  ->orWhereHas('account', fn($acc) => $acc->where('type', 'cash'));
            })
            ->sum('amount');
        $cashIn = $shiftCashIn + $directCashIn;

        $shiftBankIn = (float) ShiftClosing::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->sum('bank_amount');
        $directBankIn = (float) Transaction::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->whereHas('account', fn($q) => $q->where('type', 'bank'))
            ->sum('amount');
        $bankIn = $shiftBankIn + $directBankIn;

        $shiftJazzIn = (float) ShiftClosing::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->sum('jazzcash_amount');
        $directJazzIn = (float) Transaction::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->whereHas('account', fn($q) => $q->where('type', 'jazzcash'))
            ->sum('amount');
        $jazzcashIn = $shiftJazzIn + $directJazzIn;

        $totalInAllAccounts = $cashIn + $bankIn + $jazzcashIn;
        $grandTotalCashIn = $totalInAllAccounts;

        // ----------------------------------------------------
        // 3. ALL-ACCOUNTS OUTFLOWS (-)
        // ----------------------------------------------------
        $shiftExpenses = (float) ShiftClosing::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->sum('expenses_amount');

        $shiftPartyPayments = (float) \App\Models\ShiftClosingPartyPayment::whereHas('shiftClosing', function($q) use ($fromDate, $toDate) {
            $q->whereDate('date', '>=', $fromDate)->whereDate('date', '<=', $toDate);
        })->sum('amount');

        $directCashOut = (float) Transaction::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_out')
            ->where(function($q) {
                $q->whereNull('account_id')
                  ->orWhereHas('account', fn($acc) => $acc->where('type', 'cash'));
            })
            ->sum('amount');
        // Shift expenses & shift party payments are already deducted from physical counted cash at shift end
        $cashOut = $directCashOut;

        $bankOut = (float) Transaction::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_out')
            ->whereHas('account', fn($q) => $q->where('type', 'bank'))
            ->sum('amount');

        $jazzcashOut = (float) Transaction::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_out')
            ->whereHas('account', fn($q) => $q->where('type', 'jazzcash'))
            ->sum('amount');

        $totalOutAllAccounts = $cashOut + $bankOut + $jazzcashOut;
        $grandTotalPaymentsOut = $totalOutAllAccounts;

        // ----------------------------------------------------
        // 4. SHIFT VARIANCE & CLOSING BALANCES (=)
        // ----------------------------------------------------
        $grandTotalDifference = (float) ShiftClosing::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->sum('difference');

        $cashClosing = $cashOpening + $cashIn - $cashOut;
        $bankClosing = $bankOpening + $bankIn - $bankOut;
        $jazzcashClosing = $jazzcashOpening + $jazzcashIn - $jazzcashOut;
        $totalClosingAllAccounts = $cashClosing + $bankClosing + $jazzcashClosing;
        $latestClosingCash = $cashClosing;

        return view('reports.daily_summary', compact(
            'closings',
            'fromDate',
            'toDate',
            'periodOpeningCash',
            'cashOpening',
            'bankOpening',
            'jazzcashOpening',
            'totalOpeningAllAccounts',
            'cashIn',
            'bankIn',
            'jazzcashIn',
            'totalInAllAccounts',
            'grandTotalCashIn',
            'cashOut',
            'bankOut',
            'jazzcashOut',
            'totalOutAllAccounts',
            'grandTotalPaymentsOut',
            'cashClosing',
            'bankClosing',
            'jazzcashClosing',
            'totalClosingAllAccounts',
            'grandTotalDifference',
            'latestClosingCash',
            'paymentsIn',
            'paymentsOut',
            'totalItemizedPaymentsIn',
            'totalItemizedPaymentsOut'
        ));
    }

    /**
     * Cash Variance & Difference Audit Report
     */
    public function varianceAudit(Request $request)
    {
        $fromDate = $request->input('from_date', Carbon::today()->subDays(30)->toDateString());
        $toDate = $request->input('to_date', Carbon::today()->toDateString());
        $shiftType = $request->input('shift_type'); // morning, evening, or all
        $filterMode = $request->input('filter_mode', 'discrepancy_only'); // discrepancy_only or all

        $query = ShiftClosing::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->with(['cashier', 'closer']);

        if ($filterMode === 'discrepancy_only') {
            $query->where('difference', '!=', 0);
        }

        if (!empty($shiftType) && in_array($shiftType, ['morning', 'evening'])) {
            $query->where('shift_type', $shiftType);
        }

        $records = $query->orderBy('date', 'desc')->orderBy('shift_type', 'asc')->get();

        // Calculate Variance Statistics
        $totalShortages = $records->where('difference', '<', 0)->sum('difference');
        $shortageCount = $records->where('difference', '<', 0)->count();

        $totalSurpluses = $records->where('difference', '>', 0)->sum('difference');
        $surplusCount = $records->where('difference', '>', 0)->count();

        $netVariance = $totalSurpluses + $totalShortages; // shortages are negative

        return view('reports.variance', compact(
            'records',
            'fromDate',
            'toDate',
            'shiftType',
            'filterMode',
            'totalShortages',
            'shortageCount',
            'totalSurpluses',
            'surplusCount',
            'netVariance'
        ));
    }

    /**
     * Expense Detailed Audit & Analytics Report
     */
    public function expenseReport(Request $request)
    {
        $fromDate = $request->input('from_date', Carbon::today()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', Carbon::today()->toDateString());
        $categoryId = $request->input('category_id');
        $accountId = $request->input('account_id');
        $partyId = $request->input('party_id');
        $expenseSource = $request->input('expense_source', 'all'); // 'all', 'vouchers', 'shifts'
        $search = $request->input('search');

        // Query Voucher Payments Out
        $voucherQuery = Transaction::where('type', 'payment_out')
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->with(['party', 'account', 'category', 'creator']);

        if (!empty($categoryId)) {
            if ($categoryId === 'uncategorized') {
                $voucherQuery->whereNull('category_id');
            } else {
                $voucherQuery->where('category_id', $categoryId);
            }
        }

        if (!empty($accountId)) {
            $voucherQuery->where('account_id', $accountId);
        }

        if (!empty($partyId)) {
            if ($partyId === 'no_party') {
                $voucherQuery->whereNull('party_id');
            } else {
                $voucherQuery->where('party_id', $partyId);
            }
        }

        if (!empty($search)) {
            $voucherQuery->where(function ($q) use ($search) {
                $q->where('bill_no', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('party', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('category', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $voucherExpenses = $voucherQuery->orderBy('date', 'desc')->orderBy('id', 'desc')->get();

        // Query Shift Counter Expenses (where expenses_amount > 0)
        $shiftExpensesQuery = ShiftClosing::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->where('expenses_amount', '>', 0)
            ->with(['cashier']);

        if (!empty($search)) {
            $shiftExpensesQuery->where(function ($q) use ($search) {
                $q->where('remarks', 'like', "%{$search}%")
                  ->orWhere('expenses_details', 'like', "%{$search}%")
                  ->orWhereHas('cashier', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $shiftExpenses = $shiftExpensesQuery->orderBy('date', 'desc')->orderBy('shift_type', 'asc')->get();

        // Filter based on expenseSource
        $showVouchers = in_array($expenseSource, ['all', 'vouchers']);
        $showShifts = in_array($expenseSource, ['all', 'shifts']);

        $totalVoucherAmount = $showVouchers ? (float) $voucherExpenses->sum('amount') : 0.00;
        $totalShiftAmount = $showShifts ? (float) $shiftExpenses->sum('expenses_amount') : 0.00;
        $grandTotalAmount = $totalVoucherAmount + $totalShiftAmount;

        $totalVoucherCount = $showVouchers ? $voucherExpenses->count() : 0;
        $totalShiftCount = $showShifts ? $shiftExpenses->count() : 0;
        $grandTotalCount = $totalVoucherCount + $totalShiftCount;

        // Days in date range for daily average
        $diffDays = max(1, Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate)) + 1);
        $dailyAverage = $grandTotalAmount / $diffDays;

        // Category-wise Breakdown (from voucher expenses)
        $allCategories = ExpenseCategory::orderBy('name')->get();
        $categoryBreakdown = [];

        foreach ($allCategories as $cat) {
            $catTxns = $voucherExpenses->where('category_id', $cat->id);
            $sum = (float) $catTxns->sum('amount');
            if ($sum > 0 || empty($categoryId)) {
                $categoryBreakdown[] = (object) [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'count' => $catTxns->count(),
                    'total' => $sum,
                    'percentage' => $grandTotalAmount > 0 ? round(($sum / $grandTotalAmount) * 100, 1) : 0,
                ];
            }
        }

        // Uncategorized vouchers
        $uncatTxns = $voucherExpenses->whereNull('category_id');
        $uncatSum = (float) $uncatTxns->sum('amount');
        if ($uncatSum > 0) {
            $categoryBreakdown[] = (object) [
                'id' => 'uncategorized',
                'name' => 'General / Uncategorized',
                'count' => $uncatTxns->count(),
                'total' => $uncatSum,
                'percentage' => $grandTotalAmount > 0 ? round(($uncatSum / $grandTotalAmount) * 100, 1) : 0,
            ];
        }

        // Shift counter expenses in category breakdown if present
        if ($showShifts && $totalShiftAmount > 0) {
            $categoryBreakdown[] = (object) [
                'id' => 'shift_counter',
                'name' => 'Shift Counter Deductions (Petty Cash)',
                'count' => $shiftExpenses->count(),
                'total' => $totalShiftAmount,
                'percentage' => $grandTotalAmount > 0 ? round(($totalShiftAmount / $grandTotalAmount) * 100, 1) : 0,
            ];
        }

        // Sort categories by highest total desc
        usort($categoryBreakdown, fn($a, $b) => $b->total <=> $a->total);

        // Account-wise Breakdown
        $allAccounts = Account::orderBy('name')->get();
        $accountBreakdown = [];
        foreach ($allAccounts as $acc) {
            $accTxns = $voucherExpenses->where('account_id', $acc->id);
            $sum = (float) $accTxns->sum('amount');
            if ($sum > 0) {
                $accountBreakdown[] = (object) [
                    'id' => $acc->id,
                    'name' => $acc->name,
                    'type' => $acc->type,
                    'type_badge' => $acc->type_badge,
                    'count' => $accTxns->count(),
                    'total' => $sum,
                    'percentage' => $totalVoucherAmount > 0 ? round(($sum / $totalVoucherAmount) * 100, 1) : 0,
                ];
            }
        }

        $allParties = Party::orderBy('name')->get();

        return view('reports.expenses', compact(
            'voucherExpenses',
            'shiftExpenses',
            'fromDate',
            'toDate',
            'categoryId',
            'accountId',
            'partyId',
            'expenseSource',
            'search',
            'showVouchers',
            'showShifts',
            'totalVoucherAmount',
            'totalShiftAmount',
            'grandTotalAmount',
            'totalVoucherCount',
            'totalShiftCount',
            'grandTotalCount',
            'dailyAverage',
            'diffDays',
            'categoryBreakdown',
            'accountBreakdown',
            'allCategories',
            'allAccounts',
            'allParties'
        ));
    }

    /**
     * Total Sale & Cash Flow Executive Summary Report
     * Includes Period Sales, Receipts (Cash/Bank/JazzCash), Disbursements to Parties,
     * Period Net Surplus, and Real-time Available Treasury Balances.
     */
    public function totalSaleSummary(Request $request)
    {
        $fromDate = $request->input('from_date', Carbon::today()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', Carbon::today()->toDateString());

        // 1. Shift Closings in period
        $shifts = ShiftClosing::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->orderBy('date', 'asc')
            ->get();

        $grossSales = (float) $shifts->sum('total_sale');
        $salesReturns = (float) $shifts->sum('returns_amount');
        $netSales = max(0, $grossSales - $salesReturns);
        $totalShiftInvoices = (int) $shifts->sum('total_invoices');
        $shiftExpensesTotal = (float) $shifts->sum('expenses_amount');

        // Shift Collection Channel breakdown
        $shiftCashIn = (float) $shifts->sum('total_counted_cash');
        $shiftBankIn = (float) $shifts->sum('bank_amount');
        $shiftJazzIn = (float) $shifts->sum('jazzcash_amount');

        // 2. Direct Payment In transactions (customer collections/recoveries not part of shifts)
        $directPaymentsIn = Transaction::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->where('type', 'payment_in')
            ->whereNull('shift_closing_id')
            ->with(['party', 'account'])
            ->orderBy('date', 'asc')
            ->get();

        $directCashIn = (float) $directPaymentsIn->filter(fn($t) => !$t->account || $t->account->type === 'cash')->sum('amount');
        $directBankIn = (float) $directPaymentsIn->filter(fn($t) => $t->account && $t->account->type === 'bank')->sum('amount');
        $directJazzIn = (float) $directPaymentsIn->filter(fn($t) => $t->account && $t->account->type === 'jazzcash')->sum('amount');
        $totalDirectIn = (float) $directPaymentsIn->sum('amount');

        // Consolidated Inflows in Period
        $periodCashIn = $shiftCashIn + $directCashIn;
        $periodBankIn = $shiftBankIn + $directBankIn;
        $periodJazzIn = $shiftJazzIn + $directJazzIn;
        $totalPeriodCollections = $periodCashIn + $periodBankIn + $periodJazzIn;

        // 3. Disbursements / Payments Out to Parties & Vendors in this period
        // A) Direct Vouchers
        $voucherPaymentsOut = Transaction::whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->where('type', 'payment_out')
            ->with(['party', 'account', 'category'])
            ->orderBy('date', 'asc')
            ->get();

        // B) Shift Drawer Party Payments
        $shiftPartyPayments = \App\Models\ShiftClosingPartyPayment::whereHas('shiftClosing', function($q) use ($fromDate, $toDate) {
                $q->whereDate('date', '>=', $fromDate)->whereDate('date', '<=', $toDate);
            })
            ->with(['party', 'shiftClosing'])
            ->get();

        // Group disbursements by party
        $partyDisbursements = [];

        // Process Direct Voucher Payments Out
        foreach ($voucherPaymentsOut as $v) {
            $partyId = $v->party_id ?: 0;
            $partyName = $v->party ? $v->party->name : ($v->category ? $v->category->name : 'Operational Expense');
            $partyType = $v->party ? $v->party->type : 'expense';
            $partyPhone = $v->party ? $v->party->phone : null;
            $currentBal = $v->party ? (float) $v->party->current_balance : null;

            $isCash = !$v->account || $v->account->type === 'cash';
            $amt = (float) $v->amount;

            $key = $partyId > 0 ? ('p_' . $partyId) : ('cat_' . ($v->category_id ?: 'other'));

            if (!isset($partyDisbursements[$key])) {
                $partyDisbursements[$key] = [
                    'party_id' => $partyId,
                    'name' => $partyName,
                    'type' => $partyType,
                    'phone' => $partyPhone,
                    'cash_paid' => 0.0,
                    'bank_paid' => 0.0,
                    'total_paid' => 0.0,
                    'tx_count' => 0,
                    'current_balance' => $currentBal,
                ];
            }

            if ($isCash) {
                $partyDisbursements[$key]['cash_paid'] += $amt;
            } else {
                $partyDisbursements[$key]['bank_paid'] += $amt;
            }
            $partyDisbursements[$key]['total_paid'] += $amt;
            $partyDisbursements[$key]['tx_count'] += 1;
        }

        // Process Shift Drawer Party Payments
        foreach ($shiftPartyPayments as $sp) {
            $partyId = $sp->party_id;
            $partyName = $sp->party ? $sp->party->name : 'Supplier Cash Payout';
            $partyType = $sp->party ? $sp->party->type : 'supplier';
            $partyPhone = $sp->party ? $sp->party->phone : null;
            $currentBal = $sp->party ? (float) $sp->party->current_balance : null;
            $amt = (float) $sp->amount;

            $key = 'p_' . $partyId;

            if (!isset($partyDisbursements[$key])) {
                $partyDisbursements[$key] = [
                    'party_id' => $partyId,
                    'name' => $partyName,
                    'type' => $partyType,
                    'phone' => $partyPhone,
                    'cash_paid' => 0.0,
                    'bank_paid' => 0.0,
                    'total_paid' => 0.0,
                    'tx_count' => 0,
                    'current_balance' => $currentBal,
                ];
            }

            $partyDisbursements[$key]['cash_paid'] += $amt;
            $partyDisbursements[$key]['total_paid'] += $amt;
            $partyDisbursements[$key]['tx_count'] += 1;
        }

        // Sort parties by total paid descending
        uasort($partyDisbursements, fn($a, $b) => $b['total_paid'] <=> $a['total_paid']);

        $totalPaidToParties = array_sum(array_column($partyDisbursements, 'total_paid'));
        $totalCashPaidToParties = array_sum(array_column($partyDisbursements, 'cash_paid'));
        $totalBankPaidToParties = array_sum(array_column($partyDisbursements, 'bank_paid'));

        // Customer Return Refunds Outflow
        $totalReturnsRefunded = $salesReturns;

        // General Shift Counter Expenses (not assigned to specific parties)
        $totalShiftOperatingExpenses = $shiftExpensesTotal;

        // Total Disbursements Out
        $totalPeriodDisbursements = $totalPaidToParties + $totalShiftOperatingExpenses;

        // 4. Period Net Cash Retention / Surplus
        $periodNetSurplus = $totalPeriodCollections - $totalPeriodDisbursements;

        // 5. Current Real-time Available Balances (All accounts)
        $accounts = Account::orderBy('type')->orderBy('name')->get();
        $liveCashBalance = (float) Account::where('type', 'cash')->sum('current_balance');
        $liveBankBalance = (float) Account::where('type', 'bank')->sum('current_balance');
        $liveJazzBalance = (float) Account::where('type', 'jazzcash')->sum('current_balance');
        $totalLiveLiquidity = $liveCashBalance + $liveBankBalance + $liveJazzBalance;

        // 6. Chronological Statement Line Items (for print/PDF and detailed ledger statement view)
        $statementRows = collect();

        // Daily shift closings
        foreach ($shifts as $s) {
            $shiftLabel = $s->shift_type === 'morning' ? 'Morning Shift' : 'Evening Shift';
            $statementRows->push((object)[
                'date' => Carbon::parse($s->date),
                'reference' => 'SHIFT #' . $s->id . ' (' . $shiftLabel . ')',
                'description' => "Shift Sales Collection & Counter Register",
                'channel' => 'Drawer Cash & Digital',
                'credit' => (float) $s->total_actual_received,
                'debit' => 0.0,
                'type' => 'sale',
            ]);
        }

        // Direct voucher payments in
        foreach ($directPaymentsIn as $dpi) {
            $statementRows->push((object)[
                'date' => Carbon::parse($dpi->date),
                'reference' => $dpi->bill_no ?: ('TX-IN-' . $dpi->id),
                'description' => 'Recovery / Receipt: ' . ($dpi->party ? $dpi->party->name : 'Direct Receipt') . ($dpi->description ? ' (' . $dpi->description . ')' : ''),
                'channel' => $dpi->account ? $dpi->account->name : 'Cash Drawer',
                'credit' => (float) $dpi->amount,
                'debit' => 0.0,
                'type' => 'payment_in',
            ]);
        }

        // Voucher payments out
        foreach ($voucherPaymentsOut as $vpo) {
            $statementRows->push((object)[
                'date' => Carbon::parse($vpo->date),
                'reference' => $vpo->bill_no ?: ('TX-OUT-' . $vpo->id),
                'description' => 'Disbursement: ' . ($vpo->party ? $vpo->party->name : ($vpo->category ? $vpo->category->name : 'Expense')) . ($vpo->description ? ' (' . $vpo->description . ')' : ''),
                'channel' => $vpo->account ? $vpo->account->name : 'Cash Drawer',
                'credit' => 0.0,
                'debit' => (float) $vpo->amount,
                'type' => 'payment_out',
            ]);
        }

        // Shift drawer party payouts
        foreach ($shiftPartyPayments as $spp) {
            $sDate = $spp->shiftClosing ? Carbon::parse($spp->shiftClosing->date) : Carbon::parse($spp->created_at);
            $statementRows->push((object)[
                'date' => $sDate,
                'reference' => 'SHIFT-PAY #' . $spp->id,
                'description' => 'Drawer Payout: ' . ($spp->party ? $spp->party->name : 'Supplier') . ($spp->details ? ' (' . $spp->details . ')' : ''),
                'channel' => 'Cash Drawer',
                'credit' => 0.0,
                'debit' => (float) $spp->amount,
                'type' => 'shift_party_payout',
            ]);
        }

        $statementRows = $statementRows->sortBy(function($row) {
            return $row->date->format('Y-m-d') . '_' . $row->reference;
        })->values();

        // Bank-wise Inflows for this period
        $bankAccounts = Account::whereIn('type', ['bank', 'jazzcash'])->orderBy('type')->orderBy('name')->get();
        $bankWiseBreakdown = [];
        foreach ($bankAccounts as $acc) {
            $inflow = (float) Transaction::where('account_id', $acc->id)
                ->whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate)
                ->where('type', 'payment_in')
                ->sum('amount');

            $bankWiseBreakdown[] = (object) [
                'name' => $acc->name,
                'type' => $acc->type,
                'type_badge' => $acc->type_badge,
                'account_number' => $acc->account_number,
                'amount_received' => $inflow,
                'current_balance' => (float) $acc->current_balance,
            ];
        }

        return view('reports.total_sale_summary', compact(
            'fromDate',
            'toDate',
            'grossSales',
            'salesReturns',
            'netSales',
            'totalShiftInvoices',
            'shiftCashIn',
            'shiftBankIn',
            'shiftJazzIn',
            'directCashIn',
            'directBankIn',
            'directJazzIn',
            'totalDirectIn',
            'periodCashIn',
            'periodBankIn',
            'periodJazzIn',
            'totalPeriodCollections',
            'bankWiseBreakdown',
            'partyDisbursements',
            'totalPaidToParties',
            'totalCashPaidToParties',
            'totalBankPaidToParties',
            'totalReturnsRefunded',
            'totalShiftOperatingExpenses',
            'totalPeriodDisbursements',
            'periodNetSurplus',
            'accounts',
            'liveCashBalance',
            'liveBankBalance',
            'liveJazzBalance',
            'totalLiveLiquidity',
            'statementRows'
        ));
    }
}
