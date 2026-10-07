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

        // Today's Collections (Shift Counted Cash + Shift Digital Collections + Direct Payment In)
        $shiftCashIn = ($morningShift ? (float)$morningShift->total_counted_cash : 0) +
                       ($eveningShift ? (float)$eveningShift->total_counted_cash : 0);
        $shiftBankIn = ($morningShift ? (float)$morningShift->bank_amount : 0) +
                       ($eveningShift ? (float)$eveningShift->bank_amount : 0);
        $shiftJazzIn = ($morningShift ? (float)$morningShift->jazzcash_amount : 0) +
                       ($eveningShift ? (float)$eveningShift->jazzcash_amount : 0);
        $directIn = (float) Transaction::whereDate('date', $today)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->sum('amount');
        $todayInflow = $shiftCashIn + $shiftBankIn + $shiftJazzIn + $directIn;

        // Today's Outflows (Shift Expenses + Shift Returns + Shift Party Payments + Direct Payment Out)
        $shiftExpenses = ($morningShift ? (float)$morningShift->expenses_amount : 0) +
                         ($eveningShift ? (float)$eveningShift->expenses_amount : 0);
        $shiftReturns = ($morningShift ? (float)$morningShift->returns_amount : 0) +
                        ($eveningShift ? (float)$eveningShift->returns_amount : 0);
        $shiftPartyPayments = (float) ShiftClosingPartyPayment::whereHas('shiftClosing', fn ($query) => $query->whereDate('date', $today))
            ->sum('amount');
        $directPaymentsOut = (float) Transaction::whereDate('date', $today)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_out')
            ->sum('amount');
        $todayOutflow = $shiftExpenses + $shiftReturns + $shiftPartyPayments + $directPaymentsOut;

        // Today's Shift Discrepancy / Variance
        $todayVariance = ($morningShift ? (float)$morningShift->difference : 0) +
                         ($eveningShift ? (float)$eveningShift->difference : 0);

        // Vault & Accounts Summary
        $accounts = Account::orderBy('id')->get();
        $bankFunds = (float) $accounts->whereIn('type', ['bank', 'jazzcash'])->sum('current_balance');

        if ($todayClosing && $todayClosing->status === 'closed') {
            $cashInVault = (float) $todayClosing->closing_cash;
        } else {
            $prevClosing = DayClosing::where('date', '<', $today)->where('status', 'closed')->orderBy('date', 'desc')->first();
            $cashOpening = $prevClosing ? (float)$prevClosing->closing_cash : (float)Account::where('type', 'cash')->sum('opening_balance');
            $directCashIn = (float) Transaction::whereDate('date', $today)
                ->whereNull('shift_closing_id')
                ->where('type', 'payment_in')
                ->where(function($q) {
                    $q->whereNull('account_id')->orWhereHas('account', fn($acc) => $acc->where('type', 'cash'));
                })
                ->sum('amount');
            $directCashOut = (float) Transaction::whereDate('date', $today)
                ->whereNull('shift_closing_id')
                ->where('type', 'payment_out')
                ->where(function($q) {
                    $q->whereNull('account_id')->orWhereHas('account', fn($acc) => $acc->where('type', 'cash'));
                })
                ->sum('amount');
            $cashInVault = $cashOpening + $shiftCashIn + $directCashIn - $directCashOut;
        }

        $totalLiquidFunds = $cashInVault + $bankFunds;

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

            $scCash = ShiftClosing::whereDate('date', $d)->sum('total_counted_cash');
            $scBank = ShiftClosing::whereDate('date', $d)->sum('bank_amount');
            $scJazz = ShiftClosing::whereDate('date', $d)->sum('jazzcash_amount');
            $txIn = Transaction::whereDate('date', $d)->whereNull('shift_closing_id')->where('type', 'payment_in')->sum('amount');

            $scOut = ShiftClosing::whereDate('date', $d)->sum('expenses_amount');
            $scReturns = ShiftClosing::whereDate('date', $d)->sum('returns_amount');
            $shiftPartyOut = ShiftClosingPartyPayment::whereHas('shiftClosing', fn ($query) => $query->whereDate('date', $d))->sum('amount');
            $txOut = Transaction::whereDate('date', $d)->whereNull('shift_closing_id')->where('type', 'payment_out')->sum('amount');

            $chartLabels[] = $dLabel;
            $chartInflows[] = (float)$scCash + (float)$scBank + (float)$scJazz + (float)$txIn;
            $chartOutflows[] = (float)$scOut + (float)$scReturns + (float)$shiftPartyOut + (float)$txOut;
        }

        // Account Balances for Doughnut Chart (reflecting live cash in vault)
        $accountLabels = [];
        $accountBalances = [];
        foreach ($accounts as $acc) {
            $accountLabels[] = $acc->name;
            if ($acc->type === 'cash') {
                $accountBalances[] = (float) $cashInVault;
            } else {
                $accountBalances[] = (float) $acc->current_balance;
            }
        }

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
