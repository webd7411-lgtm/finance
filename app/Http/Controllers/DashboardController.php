<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Party;
use App\Models\ShiftClosing;
use App\Models\ShiftClosingPartyPayment;
use App\Models\Transaction;
use App\Models\DayClosing;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        // Today's Shifts
        $morningShift = ShiftClosing::whereDate('date', $today)->where('shift_type', 'morning')->first();
        $eveningShift = ShiftClosing::whereDate('date', $today)->where('shift_type', 'evening')->first();

        // Today's Day Closing Record
        $todayClosing = DayClosing::whereDate('date', $today)->first();

        // Today's Collections (Shift Counted Cash + Payment In)
        $shiftCashIn = ($morningShift ? (float)$morningShift->total_counted_cash : 0) +
                       ($eveningShift ? (float)$eveningShift->total_counted_cash : 0);
        $directCashIn = Transaction::whereDate('date', $today)->where('type', 'payment_in')->sum('amount');
        $todayInflow = $shiftCashIn + (float)$directCashIn;

        // Today's Outflows (Shift Expenses + Payment Out)
        $shiftExpenses = ($morningShift ? (float)$morningShift->expenses_amount : 0) +
                         ($eveningShift ? (float)$eveningShift->expenses_amount : 0);
        $shiftPartyPayments = ShiftClosingPartyPayment::whereHas('shiftClosing', fn ($query) => $query->whereDate('date', $today))
            ->sum('amount');
        $directPaymentsOut = Transaction::whereDate('date', $today)->where('type', 'payment_out')->sum('amount');
        $todayOutflow = $shiftExpenses + (float)$shiftPartyPayments + (float)$directPaymentsOut;

        // Today's Shift Discrepancy / Variance
        $todayVariance = ($morningShift ? (float)$morningShift->difference : 0) +
                         ($eveningShift ? (float)$eveningShift->difference : 0);

        // Vault & Accounts Summary
        $accounts = Account::orderBy('id')->get();
        $totalLiquidFunds = $accounts->sum('current_balance');
        $cashInVault = $accounts->where('type', 'cash')->sum('current_balance');
        $bankFunds = $accounts->whereIn('type', ['bank', 'jazzcash'])->sum('current_balance');

        // Party Receivables vs Payables
        $parties = Party::all();
        $totalReceivables = $parties->whereIn('type', ['customer', 'staff'])->where('current_balance', '>', 0)->sum('current_balance');
        $totalPayables = $parties->whereIn('type', ['supplier', 'trader'])->where('current_balance', '>', 0)->sum('current_balance');

        // Last 7 Days Financial Trend (Chart.js)
        $chartLabels = [];
        $chartInflows = [];
        $chartOutflows = [];

        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i)->toDateString();
            $dLabel = Carbon::today()->subDays($i)->format('d M');

            $scIn = ShiftClosing::whereDate('date', $d)->sum('total_counted_cash');
            $txIn = Transaction::whereDate('date', $d)->where('type', 'payment_in')->sum('amount');

            $scOut = ShiftClosing::whereDate('date', $d)->sum('expenses_amount');
            $shiftPartyOut = ShiftClosingPartyPayment::whereHas('shiftClosing', fn ($query) => $query->whereDate('date', $d))
                ->sum('amount');
            $txOut = Transaction::whereDate('date', $d)->where('type', 'payment_out')->sum('amount');

            $chartLabels[] = $dLabel;
            $chartInflows[] = (float)$scIn + (float)$txIn;
            $chartOutflows[] = (float)$scOut + (float)$shiftPartyOut + (float)$txOut;
        }

        // Account Balances for Doughnut Chart
        $accountLabels = $accounts->pluck('name')->toArray();
        $accountBalances = $accounts->pluck('current_balance')->map(fn($v) => (float)$v)->toArray();

        // Recent Transactions (Last 6)
        $recentTransactions = Transaction::with(['party', 'account', 'creator'])->latest('id')->take(6)->get();

        // Recent Shifts (Last 4)
        $recentShifts = ShiftClosing::with('cashier')->latest('date')->latest('id')->take(4)->get();

        // Recent Activity Logs (Last 5)
        $recentLogs = ActivityLog::with('user')->latest('id')->take(5)->get();

        return view('dashboard', compact(
            'today',
            'morningShift',
            'eveningShift',
            'todayClosing',
            'todayInflow',
            'todayOutflow',
            'todayVariance',
            'totalLiquidFunds',
            'cashInVault',
            'bankFunds',
            'totalReceivables',
            'totalPayables',
            'accounts',
            'chartLabels',
            'chartInflows',
            'chartOutflows',
            'accountLabels',
            'accountBalances',
            'recentTransactions',
            'recentShifts',
            'recentLogs'
        ));
    }
}
