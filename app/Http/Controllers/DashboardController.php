<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\DayClosing;
use App\Models\Party;
use App\Models\Product;
use App\Models\ShiftClosing;
use App\Models\ShiftClosingPartyPayment;
use App\Models\StationSetting;
use App\Models\Tank;
use App\Models\TankStockAdjustment;
use App\Models\Transaction;
use App\Services\TankReconciliationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(TankReconciliationService $reconciliationService)
    {
        $today = Carbon::today()->toDateString();

        // Today's Shifts (supports 24-hours, morning, evening, night)
        $todayShifts = ShiftClosing::whereDate('date', $today)->get();
        $morningShift = $todayShifts->firstWhere('shift_type', 'morning');
        $eveningShift = $todayShifts->firstWhere('shift_type', 'evening');

        // Today's Day Closing Record
        $todayClosing = DayClosing::whereDate('date', $today)->first();

        // Today's Collections
        $shiftCashIn = (float) $todayShifts->sum('total_counted_cash');
        $shiftBankIn = (float) $todayShifts->sum('bank_amount');
        $shiftJazzIn = (float) $todayShifts->sum('jazzcash_amount');
        $directIn = (float) Transaction::whereDate('date', $today)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_in')
            ->sum('amount');
        $todayInflow = $shiftCashIn + $shiftBankIn + $shiftJazzIn + $directIn;

        // Today's Outflows
        $shiftExpenses = (float) $todayShifts->sum('expenses_amount');
        $shiftReturns = (float) $todayShifts->sum('returns_amount');
        $shiftPartyPayments = (float) ShiftClosingPartyPayment::whereHas('shiftClosing', fn ($query) => $query->whereDate('date', $today))
            ->sum('amount');
        $directPaymentsOut = (float) Transaction::whereDate('date', $today)
            ->whereNull('shift_closing_id')
            ->where('type', 'payment_out')
            ->sum('amount');
        $todayOutflow = $shiftExpenses + $shiftReturns + $shiftPartyPayments + $directPaymentsOut;

        // Today's Shift Discrepancy / Variance
        $todayVariance = (float) $todayShifts->sum('difference');

        // Monthly Daily Sales Average
        $currentMonthShifts = ShiftClosing::whereMonth('date', Carbon::today()->month)
            ->whereYear('date', Carbon::today()->year)
            ->get();

        $monthlyShiftsCount = $currentMonthShifts->count();
        $todayDayNumber = (int) Carbon::today()->day;
        $monthlyDaysCount = max(1, $todayDayNumber - 1);

        $monthlyNetShiftSaleTotal = (float) $currentMonthShifts->sum(function ($shift) {
            return (float) $shift->total_sale - (float) $shift->returns_amount;
        });

        $monthlyAvgDaySale = $monthlyDaysCount > 0
            ? ($monthlyNetShiftSaleTotal / $monthlyDaysCount)
            : 0;
        $monthlyAvgShiftSale = $monthlyAvgDaySale;

        // Vault & Accounts Summary
        Account::syncCashAccountBalance();
        $accounts = Account::orderBy('id')->get();
        $bankFunds = (float) $accounts->where('type', 'bank')->sum('current_balance');
        $jazzFunds = (float) $accounts->where('type', 'jazzcash')->sum('current_balance');
        $cashInVault = (float) $accounts->where('type', 'cash')->sum('current_balance');

        $totalLiquidFunds = $cashInVault + $bankFunds + $jazzFunds;

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
            $chartInflows[] = (float) $scCash + (float) $scBank + (float) $scJazz + (float) $txIn;
            $chartOutflows[] = (float) $scOut + (float) $scReturns + (float) $shiftPartyOut + (float) $txOut;
        }

        $accountLabels = [];
        $accountBalances = [];
        foreach ($accounts as $acc) {
            $accountLabels[] = $acc->name;
            $accountBalances[] = $acc->type === 'cash' ? (float) $cashInVault : (float) $acc->current_balance;
        }

        $recentTransactions = Transaction::with(['party', 'account', 'creator'])->latest('id')->take(6)->get();
        $recentShifts = ShiftClosing::with('cashier')->latest('date')->latest('id')->take(4)->get();
        $recentLogs = ActivityLog::with('user')->latest('id')->take(5)->get();

        // Live Storage Tanks, Wet-Stock Variance & Lubricants
        $tankConfig = StationSetting::getTankConfig();
        $allTanks = Tank::with(['product', 'latestDipReading'])
            ->withCount('dipCharts')
            ->active()
            ->orderBy('tank_number')
            ->get();

        $tankCards = [];
        $netVarianceLiters = 0.0;
        $netVarianceValue = 0.0;
        $alertTanksCount = 0;
        $dippedTanksCount = 0;

        foreach ($allTanks as $tank) {
            $bookStock = (float) $tank->current_stock;
            $latestDip = $tank->latestDipReading;
            $hasDip = $tank->last_dip_date !== null || $latestDip !== null;

            $measured = $tank->last_measured_liters !== null
                ? (float) $tank->last_measured_liters
                : ($latestDip ? (float) $latestDip->measured_liters : null);

            $variance = $tank->last_variance_liters !== null
                ? (float) $tank->last_variance_liters
                : ($latestDip ? (float) $latestDip->variance_liters : null);

            $status = $tank->last_variance_status
                ?? ($latestDip?->variance_status);

            if ($hasDip && $variance !== null && $status === null) {
                $status = $reconciliationService->evaluateVariance($tank, $variance, $bookStock, $tankConfig)['status'];
            }

            $thresholds = $reconciliationService->calculateToleranceThresholds($tank, $bookStock, $tankConfig);
            $trend = $reconciliationService->detectConsecutiveShortageTrend($tank, $tankConfig);
            $unitCost = (float) ($tank->product?->purchase_price ?? 0);
            $financialImpact = $variance !== null ? round($variance * $unitCost, 2) : 0.0;

            if ($hasDip) {
                $dippedTanksCount++;
                $netVarianceLiters += (float) $variance;
                $netVarianceValue += $financialImpact;
            }

            if (in_array($status, ['warning', 'critical'], true) || $trend['has_trend']) {
                $alertTanksCount++;
            }

            $tankCards[] = [
                'tank'             => $tank,
                'book_stock'       => $bookStock,
                'has_dip'          => $hasDip,
                'measured_liters'  => $measured,
                'variance_liters'  => $variance,
                'variance_status'  => $status,
                'latest_dip'       => $latestDip,
                'last_dip_date'    => $tank->last_dip_date ?? $latestDip?->reading_date,
                'last_dip_time'    => $tank->last_dip_time ?? $latestDip?->reading_time,
                'thresholds'       => $thresholds,
                'trend'            => $trend,
                'financial_impact' => $financialImpact,
                'has_chart'        => $tank->dip_charts_count > 0,
            ];
        }

        $monthAdjustmentsQuery = TankStockAdjustment::whereMonth('adjustment_date', Carbon::today()->month)
            ->whereYear('adjustment_date', Carbon::today()->year);

        $wetStockSummary = [
            'net_variance_liters'     => round($netVarianceLiters, 2),
            'net_variance_value'      => round($netVarianceValue, 2),
            'month_reconciled_liters' => round((float) (clone $monthAdjustmentsQuery)->sum('adjustment_liters'), 2),
            'month_reconciled_value'  => round((float) (clone $monthAdjustmentsQuery)->sum('financial_impact'), 2),
            'alert_tanks_count'       => $alertTanksCount,
            'dipped_tanks_count'      => $dippedTanksCount,
            'total_active_tanks'      => $allTanks->count(),
        ];

        $petrolProduct = Product::where('code', 'PET-92')->first();
        $dieselProduct = Product::where('code', 'HSD-01')->first();
        $octaneProduct = Product::where('code', 'HOBC-97')->first();

        $lubricantProducts = Product::where('is_fuel', false)
            ->where(function ($q) {
                $q->where('category', 'lubricant')
                  ->orWhere('name', 'like', '%Havoline%')
                  ->orWhere('name', 'like', '%Oil%');
            })
            ->get();
        $totalLubricantUnits = $lubricantProducts->sum('current_stock');

        $todayFuelLiters = ShiftClosing::whereDate('date', $today)->sum('total_liters');
        $todayFuelSales = ShiftClosing::whereDate('date', $today)->sum('total_sale');
        $todayLubricantSales = ShiftClosing::whereDate('date', $today)->sum('total_lubricant_sales');
        $todayCreditSales = ShiftClosing::whereDate('date', $today)->sum('total_credit_sales');

        $readingContexts = StationSetting::READING_CONTEXTS;
        $reasonCategories = StationSetting::REASON_CATEGORIES;

        return view('dashboard', compact(
            'today',
            'morningShift',
            'eveningShift',
            'todayClosing',
            'todayInflow',
            'todayOutflow',
            'todayVariance',
            'monthlyAvgShiftSale',
            'monthlyAvgDaySale',
            'monthlyShiftsCount',
            'monthlyDaysCount',
            'monthlyNetShiftSaleTotal',
            'totalLiquidFunds',
            'cashInVault',
            'bankFunds',
            'jazzFunds',
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
            'recentLogs',
            'allTanks',
            'tankConfig',
            'tankCards',
            'wetStockSummary',
            'readingContexts',
            'reasonCategories',
            'petrolProduct',
            'dieselProduct',
            'octaneProduct',
            'lubricantProducts',
            'totalLubricantUnits',
            'todayFuelLiters',
            'todayFuelSales',
            'todayLubricantSales',
            'todayCreditSales'
        ));
    }
}
