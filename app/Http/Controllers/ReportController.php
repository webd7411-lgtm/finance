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

        // Calculate Grand Totals
        $grandTotalCashIn = $closings->sum('total_cash_in');
        $grandTotalPaymentsOut = $closings->sum('total_payments_out');
        $grandTotalDifference = $closings->sum('total_difference');
        $latestClosingCash = $closings->first()?->closing_cash ?? 0.00;

        return view('reports.daily_summary', compact(
            'closings',
            'fromDate',
            'toDate',
            'grandTotalCashIn',
            'grandTotalPaymentsOut',
            'grandTotalDifference',
            'latestClosingCash'
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
}
