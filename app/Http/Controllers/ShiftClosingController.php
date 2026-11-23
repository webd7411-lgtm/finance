<?php

namespace App\Http\Controllers;

use App\Models\ShiftClosing;
use App\Models\ShiftClosingNozzle;
use App\Models\ShiftClosingExpense;
use App\Models\ShiftClosingLubricant;
use App\Models\ShiftClosingCredit;
use App\Models\User;
use App\Models\Cashier;
use App\Models\Pump;
use App\Models\PumpMeterReading;
use App\Models\Product;
use App\Models\Tank;
use App\Models\Party;
use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Models\Transaction;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ShiftClosingController extends Controller
{
    public function index(Request $request)
    {
        $isTrashView = $request->get('view') === 'trash';
        $activeCount = ShiftClosing::count();
        $trashCount = ShiftClosing::onlyTrashed()->count();

        if ($isTrashView) {
            $query = ShiftClosing::onlyTrashed()->with(['cashier', 'verifier', 'deletedBy', 'pump', 'product', 'nozzles'])->latest('deleted_at');
        } else {
            $query = ShiftClosing::with(['cashier', 'verifier', 'pump', 'product', 'nozzles'])->latest('date')->latest('id');
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('shift_type')) {
            $query->where('shift_type', $request->shift_type);
        }

        if ($request->filled('pump_id')) {
            $query->where('pump_id', $request->pump_id);
        }

        $closings = $query->paginate(15)->withQueryString();

        // Summary calculations
        $totalSalesToday = ShiftClosing::whereDate('date', now()->toDateString())->sum('total_sale');
        $totalLitersToday = ShiftClosing::whereDate('date', now()->toDateString())->sum('total_liters');
        $totalReceivedToday = ShiftClosing::whereDate('date', now()->toDateString())->sum('total_actual_received');
        $totalDifferenceToday = ShiftClosing::whereDate('date', now()->toDateString())->sum('difference');
        $pumps = Pump::orderBy('name')->get();

        return view('shift_closings.index', compact(
            'closings',
            'totalSalesToday',
            'totalLitersToday',
            'totalReceivedToday',
            'totalDifferenceToday',
            'pumps',
            'isTrashView',
            'activeCount',
            'trashCount'
        ));
    }

    public function create(Request $request)
    {
        $accounts = Account::where('type', '!=', 'cash')->where('status', 'active')->orderBy('type')->orderBy('name')->get();
        $cashiers = Cashier::where('status', 'active')->orderBy('name')->get();
        if ($cashiers->isEmpty()) {
            $cashierUsers = User::where('role', 'cashier')->where('status', 'active')->get();
            foreach ($cashierUsers as $cu) {
                Cashier::updateOrCreate(
                    ['id' => $cu->id],
                    [
                        'name' => $cu->name,
                        'code' => 'CSH-' . str_pad($cu->id, 3, '0', STR_PAD_LEFT),
                        'status' => 'active',
                    ]
                );
            }
            $cashiers = Cashier::where('status', 'active')->orderBy('name')->get();
        }

        // Active Expense Categories (Video requirement)
        $expenseCategories = ExpenseCategory::expense()->orderBy('name')->get();

        // Active Petrol Pump Machines & Nozzles (Audio 1: Dispensers 1, 2, 3)
        $pumps = Pump::with(['product', 'tank'])->where('status', 'active')
            ->orderByRaw("COALESCE(dispenser_unit, 'Dispenser Unit 1') ASC")
            ->orderBy('name')
            ->get();

        $fuelProducts = Product::active()->fuel()->orderBy('name')->get();

        // Packaged Engine Oils / Lubricants (Audio 6: Caltex Havoline, etc.)
        $lubricantProducts = Product::active()
            ->where(function ($q) {
                $q->where('is_fuel', false)
                  ->orWhere('category', 'lubricant');
            })
            ->orderBy('name')
            ->get();

        // Credit Parties (Customers) (Audio 8: Credit / Udhar Sales)
        $creditParties = Party::where(function ($q) {
            $q->where('type', 'customer')
              ->orWhere('is_credit', true);
        })->orderBy('name')->get();

        // Government Fuel Subsidy Account (Audio 3)
        $subsidyAccount = Account::where('name', 'like', '%Subsidy%')->first()
            ?? Account::where('type', 'bank')->first();

        $selectedPumpId = $request->input('pump_id');

        return view('shift_closings.create', compact(
            'cashiers',
            'pumps',
            'fuelProducts',
            'lubricantProducts',
            'creditParties',
            'subsidyAccount',
            'accounts',
            'expenseCategories',
            'selectedPumpId'
        ));
    }

    public function store(Request $request)
    {
        $stripCommas = function ($val) {
            if (is_null($val) || $val === '') return $val;
            return is_string($val) ? str_replace(',', '', $val) : $val;
        };

        // Pre-clean inputs
        foreach (['start_reading', 'end_reading', 'total_liters', 'testing_liters', 'fuel_rate', 'total_sale', 'expenses_amount', 'coins', 'govt_subsidy_amount'] as $field) {
            if ($request->has($field)) {
                $request->merge([$field => $stripCommas($request->input($field))]);
            }
        }

        // Pre-clean Nozzles Array
        $nozzleRows = collect($request->input('nozzles', []))
            ->map(function ($row) use ($stripCommas) {
                return [
                    'pump_id'        => (int) ($row['pump_id'] ?? 0),
                    'tank_id'        => !empty($row['tank_id']) ? (int) $row['tank_id'] : null,
                    'product_id'     => (int) ($row['product_id'] ?? 0),
                    'nozzle_name'    => $row['nozzle_name'] ?? null,
                    'start_reading'  => (float) $stripCommas($row['start_reading'] ?? 0),
                    'end_reading'    => (float) $stripCommas($row['end_reading'] ?? 0),
                    'testing_liters' => (float) $stripCommas($row['testing_liters'] ?? 0),
                    'fuel_rate'      => (float) $stripCommas($row['fuel_rate'] ?? 0),
                    'subtotal'       => (float) $stripCommas($row['subtotal'] ?? 0),
                ];
            })
            ->filter(fn ($r) => $r['pump_id'] > 0 && ($r['end_reading'] > 0 || $r['start_reading'] > 0))
            ->values()
            ->all();

        // Pre-clean Counter Expenses Array
        $expenseRows = collect($request->input('counter_expenses', []))
            ->map(function ($row) use ($stripCommas) {
                return [
                    'expense_category_id' => !empty($row['expense_category_id']) ? (int) $row['expense_category_id'] : null,
                    'category_name'       => $row['category_name'] ?? null,
                    'details'             => $row['details'] ?? null,
                    'amount'              => (float) $stripCommas($row['amount'] ?? 0),
                ];
            })
            ->filter(fn ($r) => $r['amount'] > 0)
            ->values()
            ->all();

        // Pre-clean Lubricant Sales Array
        $lubricantRows = collect($request->input('lubricant_sales', []))
            ->map(function ($row) use ($stripCommas) {
                return [
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'quantity'   => (float) $stripCommas($row['quantity'] ?? 1),
                    'unit_price' => (float) $stripCommas($row['unit_price'] ?? 0),
                    'subtotal'   => (float) $stripCommas($row['subtotal'] ?? 0),
                ];
            })
            ->filter(fn ($r) => $r['product_id'] > 0 && $r['quantity'] > 0)
            ->values()
            ->all();

        // Pre-clean Credit / Udhar Sales Array
        $creditRows = collect($request->input('credit_sales', []))
            ->map(function ($row) use ($stripCommas) {
                return [
                    'party_id'   => (int) ($row['party_id'] ?? 0),
                    'product_id' => !empty($row['product_id']) ? (int) $row['product_id'] : null,
                    'vehicle_no' => $row['vehicle_no'] ?? null,
                    'slip_no'    => $row['slip_no'] ?? null,
                    'liters'     => (float) $stripCommas($row['liters'] ?? 0),
                    'rate'       => (float) $stripCommas($row['rate'] ?? 0),
                    'amount'     => (float) $stripCommas($row['amount'] ?? 0),
                    'notes'      => $row['notes'] ?? null,
                ];
            })
            ->filter(fn ($r) => $r['party_id'] > 0 && $r['amount'] > 0)
            ->values()
            ->all();

        // Pre-clean Digital Account Collections
        $accountRows = collect($request->input('account_payments', []))
            ->map(function ($acc) use ($stripCommas) {
                if (isset($acc['amount'])) {
                    $acc['amount'] = $stripCommas($acc['amount']);
                }
                return $acc;
            })
            ->filter(fn ($acc) => !empty($acc['account_id']) && !empty($acc['amount']) && (float)$acc['amount'] > 0)
            ->map(function ($acc) {
                return [
                    'account_id' => (int) $acc['account_id'],
                    'amount' => (float) $acc['amount'],
                    'description' => $acc['description'] ?? null,
                ];
            })
            ->values()
            ->all();

        $yesterday = now()->subDays(2)->toDateString();
        $today = now()->toDateString();

        $validated = $request->validate([
            'date' => ['required', 'date', "after_or_equal:{$yesterday}", "before_or_equal:{$today}"],
            'shift_type' => ['required', Rule::in(['morning', 'evening'])],
            'cashier_id' => ['required', 'integer', 'exists:cashiers,id'],
            'pump_id' => ['nullable', 'integer', 'exists:pumps,id'],
            'pump_machine' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            
            // Legacy / Single-Pump fields
            'start_reading' => ['nullable', 'numeric', 'min:0'],
            'end_reading' => ['nullable', 'numeric'],
            'total_liters' => ['nullable', 'numeric', 'min:0'],
            'testing_liters' => ['nullable', 'numeric', 'min:0'],
            'fuel_rate' => ['nullable', 'numeric', 'min:0'],
            'total_sale' => ['nullable', 'numeric', 'min:0'],
            'expenses_amount' => ['nullable', 'numeric', 'min:0'],
            'expenses_details' => ['nullable', 'string', 'max:2000'],
            
            // Government Subsidy (Audio 3)
            'govt_subsidy_amount' => ['nullable', 'numeric', 'min:0'],
            'govt_subsidy_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'govt_subsidy_remarks' => ['nullable', 'string', 'max:500'],

            'note_5000' => ['nullable', 'integer', 'min:0'],
            'note_1000' => ['nullable', 'integer', 'min:0'],
            'note_500' => ['nullable', 'integer', 'min:0'],
            'note_100' => ['nullable', 'integer', 'min:0'],
            'note_50' => ['nullable', 'integer', 'min:0'],
            'note_20' => ['nullable', 'integer', 'min:0'],
            'note_10' => ['nullable', 'integer', 'min:0'],
            'coins' => ['nullable', 'numeric', 'min:0'],
            'jazzcash_amount' => ['nullable', 'numeric', 'min:0'],
            'bank_amount' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $cashierId = (int) ($validated['cashier_id'] ?? auth()->id());

        // Default note counts
        $n5000 = $validated['note_5000'] ?? 0;
        $n1000 = $validated['note_1000'] ?? 0;
        $n500  = $validated['note_500'] ?? 0;
        $n100  = $validated['note_100'] ?? 0;
        $n50   = $validated['note_50'] ?? 0;
        $n20   = $validated['note_20'] ?? 0;
        $n10   = $validated['note_10'] ?? 0;
        $coins = (float) ($validated['coins'] ?? 0);

        // Calculate Fuel Sales across Nozzles
        $calculatedFuelLiters = 0;
        $calculatedTestingLiters = 0;
        $calculatedFuelSale = 0;

        if (!empty($nozzleRows)) {
            foreach ($nozzleRows as $nRow) {
                $start = (float) $nRow['start_reading'];
                $end = (float) $nRow['end_reading'];
                $test = (float) $nRow['testing_liters'];
                $meterDiff = max(0, $end - $start);
                $soldLtr = max(0, $meterDiff - $test);
                $rate = (float) $nRow['fuel_rate'];
                $sub = round($soldLtr * $rate, 2);

                $calculatedFuelLiters += $soldLtr;
                $calculatedTestingLiters += $test;
                $calculatedFuelSale += $sub;
            }
        } else {
            // Single-pump fallback
            $startReading = (float) ($validated['start_reading'] ?? 0);
            $endReading = (float) ($validated['end_reading'] ?? 0);
            $testingLtr = (float) ($validated['testing_liters'] ?? 0);
            $meterDiff = max(0, $endReading - $startReading);
            $soldLtr = max(0, $meterDiff - $testingLtr);
            $fuelRate = (float) ($validated['fuel_rate'] ?? 0);

            $calculatedFuelLiters = $soldLtr;
            $calculatedTestingLiters = $testingLtr;
            $calculatedFuelSale = round($soldLtr * $fuelRate, 2);
        }

        // Calculate Lubricant Sales (Audio 6)
        $calculatedLubricantSale = 0;
        foreach ($lubricantRows as $lubRow) {
            $calculatedLubricantSale += round((float)$lubRow['quantity'] * (float)$lubRow['unit_price'], 2);
        }

        // Calculate Credit / Udhar Sales (Audio 8)
        $calculatedCreditSale = 0;
        foreach ($creditRows as $cRow) {
            $calculatedCreditSale += (float) $cRow['amount'];
        }

        // Calculate Counter Expenses (Video walkthrough)
        $calculatedExpenses = 0;
        if (!empty($expenseRows)) {
            $calculatedExpenses = (float) collect($expenseRows)->sum('amount');
        } else {
            $calculatedExpenses = (float) ($validated['expenses_amount'] ?? 0);
        }

        // Physical Cash Counted
        $countedCash = ($n5000 * 5000) + ($n1000 * 1000) + ($n500 * 500) +
                       ($n100 * 100) + ($n50 * 50) + ($n20 * 20) + ($n10 * 10) + $coins;

        // Digital / Bank Collections
        $totalAccountCollections = (float) collect($accountRows)->sum('amount');
        $jazzcash = (float) ($validated['jazzcash_amount'] ?? 0);
        $bank = (float) ($validated['bank_amount'] ?? 0);
        $totalDigital = $totalAccountCollections > 0 ? $totalAccountCollections : ($jazzcash + $bank);

        // Government Subsidy (Audio 3)
        $govtSubsidy = (float) ($validated['govt_subsidy_amount'] ?? 0);

        // Grand Reconciliation Formula:
        // Gross Sales = Fuel + Lubricants
        // Expected Cash = Gross Sales - Credit Sales - Expenses
        // Actual Received = Counted Cash + Digital/Bank + Govt Subsidy
        $grossSales = $calculatedFuelSale + $calculatedLubricantSale;
        $expectedCash = $grossSales - $calculatedCreditSale - $calculatedExpenses;
        $actualReceived = $countedCash + $totalDigital + $govtSubsidy;
        $difference = round($actualReceived - $expectedCash, 2);

        if ($difference < -0.01) {
            throw ValidationException::withMessages([
                'difference' => 'Shift cash is short by Rs. ' . number_format(abs($difference), 2) . ' (Expected: Rs. ' . number_format($expectedCash, 2) . ', Actual: Rs. ' . number_format($actualReceived, 2) . '). Shift closing can only be saved when balanced or surplus.',
            ]);
        }

        $closing = DB::transaction(function () use (
            $validated,
            $cashierId,
            $nozzleRows,
            $expenseRows,
            $lubricantRows,
            $creditRows,
            $accountRows,
            $calculatedFuelLiters,
            $calculatedTestingLiters,
            $calculatedFuelSale,
            $calculatedLubricantSale,
            $calculatedCreditSale,
            $calculatedExpenses,
            $n5000, $n1000, $n500, $n100, $n50, $n20, $n10, $coins,
            $countedCash,
            $totalDigital,
            $totalAccountCollections,
            $jazzcash,
            $bank,
            $govtSubsidy,
            $actualReceived,
            $expectedCash,
            $difference
        ) {
            // Primary single pump link for backwards compatibility
            $primaryPumpId = !empty($nozzleRows) ? $nozzleRows[0]['pump_id'] : ($validated['pump_id'] ?? null);
            $primaryPump = $primaryPumpId ? Pump::find($primaryPumpId) : null;
            $primaryTankId = $primaryPump?->tank_id;
            $primaryProductId = $primaryPump?->product_id ?? ($validated['product_id'] ?? null);

            $closing = ShiftClosing::create([
                'date'                    => $validated['date'],
                'shift_type'              => $validated['shift_type'],
                'cashier_id'              => $cashierId,
                'closed_by'               => auth()->id() ?? $cashierId,
                'tank_id'                 => $primaryTankId,
                'pump_id'                 => $primaryPumpId,
                'pump_machine'            => $primaryPump?->name ?? 'Multi-Dispenser Shift',
                'product_id'              => $primaryProductId,
                'start_reading'           => !empty($nozzleRows) ? $nozzleRows[0]['start_reading'] : ($validated['start_reading'] ?? 0),
                'end_reading'             => !empty($nozzleRows) ? $nozzleRows[0]['end_reading'] : ($validated['end_reading'] ?? 0),
                'total_liters'            => $calculatedFuelLiters,
                'testing_liters'          => $calculatedTestingLiters,
                'fuel_rate'               => !empty($nozzleRows) ? $nozzleRows[0]['fuel_rate'] : ($validated['fuel_rate'] ?? 0),
                'total_sale'              => $calculatedFuelSale,
                'total_credit_sales'      => $calculatedCreditSale,
                'total_lubricant_sales'   => $calculatedLubricantSale,
                'returns_amount'          => 0,
                'expenses_amount'         => $calculatedExpenses,
                'expenses_details'        => $validated['expenses_details'] ?? null,
                'note_5000'               => $n5000,
                'note_1000'               => $n1000,
                'note_500'                => $n500,
                'note_100'                => $n100,
                'note_50'                 => $n50,
                'note_20'                 => $n20,
                'note_10'                 => $n10,
                'coins'                   => $coins,
                'total_counted_cash'      => $countedCash,
                'jazzcash_amount'         => $jazzcash,
                'bank_amount'             => $bank,
                'govt_subsidy_amount'     => $govtSubsidy,
                'govt_subsidy_account_id' => $validated['govt_subsidy_account_id'] ?? null,
                'govt_subsidy_remarks'    => $validated['govt_subsidy_remarks'] ?? null,
                'total_actual_received'   => $actualReceived,
                'expected_cash'           => $expectedCash,
                'difference'              => $difference,
                'status'                  => 'locked',
                'remarks'                 => $validated['remarks'] ?? null,
            ]);

            // 1. Process Nozzles (Multi-Dispenser reading, tank stock, pump totalizer)
            if (!empty($nozzleRows)) {
                foreach ($nozzleRows as $nRow) {
                    $pump = Pump::find($nRow['pump_id']);
                    $tankId = $nRow['tank_id'] ?: $pump?->tank_id;
                    $prodId = $nRow['product_id'] ?: $pump?->product_id;

                    $start = (float) $nRow['start_reading'];
                    $end = (float) $nRow['end_reading'];
                    $test = (float) $nRow['testing_liters'];
                    $meterDiff = max(0, $end - $start);
                    $soldLtr = max(0, $meterDiff - $test);
                    $rate = (float) $nRow['fuel_rate'];
                    $sub = round($soldLtr * $rate, 2);

                    ShiftClosingNozzle::create([
                        'shift_closing_id' => $closing->id,
                        'pump_id'          => $pump->id,
                        'tank_id'          => $tankId,
                        'product_id'       => $prodId,
                        'nozzle_name'      => $pump->name,
                        'start_reading'    => $start,
                        'end_reading'      => $end,
                        'meter_liters'     => $meterDiff,
                        'testing_liters'   => $test,
                        'sold_liters'      => $soldLtr,
                        'fuel_rate'        => $rate,
                        'subtotal'         => $sub,
                    ]);

                    // Update pump last reading
                    if ($pump) {
                        $pump->last_reading = $end;
                        $pump->save();

                        PumpMeterReading::create([
                            'pump_id'          => $pump->id,
                            'shift_closing_id' => $closing->id,
                            'reading_date'     => $validated['date'],
                            'reading_type'     => 'shift_sale',
                            'start_reading'    => $start,
                            'end_reading'      => $end,
                            'volume_dispensed' => $soldLtr,
                            'fuel_rate'        => $rate,
                            'sales_amount'     => $sub,
                            'cashier_id'       => $cashierId,
                            'recorded_by'      => auth()->id(),
                            'remarks'          => "Shift Closing #{$closing->id} (Dispensed: {$meterDiff}L, Testing: {$test}L, Sold: {$soldLtr}L)",
                        ]);
                    }

                    // Decrement Tank Stock ONLY for SOLD LITERS (Audio 2: Testing fuel went BACK into tank!)
                    if ($tankId && $soldLtr > 0) {
                        $tank = Tank::lockForUpdate()->find($tankId);
                        if ($tank) {
                            $tank->current_stock = max(0, $tank->current_stock - $soldLtr);
                            $tank->save();
                        }
                    }

                    // Decrement Product Stock for SOLD LITERS
                    if ($prodId && $soldLtr > 0) {
                        $product = Product::lockForUpdate()->find($prodId);
                        if ($product) {
                            $product->current_stock = max(0, $product->current_stock - $soldLtr);
                            $product->save();
                        }
                    }
                }
            } else {
                // Fallback for single pump
                if ($primaryPump) {
                    $primaryPump->last_reading = (float)$validated['end_reading'];
                    $primaryPump->save();

                    PumpMeterReading::create([
                        'pump_id'          => $primaryPump->id,
                        'shift_closing_id' => $closing->id,
                        'reading_date'     => $validated['date'],
                        'reading_type'     => 'shift_sale',
                        'start_reading'    => (float)$validated['start_reading'],
                        'end_reading'      => (float)$validated['end_reading'],
                        'volume_dispensed' => $calculatedFuelLiters,
                        'fuel_rate'        => (float)$validated['fuel_rate'],
                        'sales_amount'     => $calculatedFuelSale,
                        'cashier_id'       => $cashierId,
                        'recorded_by'      => auth()->id(),
                        'remarks'          => "Shift Closing #{$closing->id}",
                    ]);

                    if ($primaryTankId && $calculatedFuelLiters > 0) {
                        $tank = Tank::lockForUpdate()->find($primaryTankId);
                        if ($tank) {
                            $tank->current_stock = max(0, $tank->current_stock - $calculatedFuelLiters);
                            $tank->save();
                        }
                    }

                    if ($primaryProductId && $calculatedFuelLiters > 0) {
                        $product = Product::lockForUpdate()->find($primaryProductId);
                        if ($product) {
                            $product->current_stock = max(0, $product->current_stock - $calculatedFuelLiters);
                            $product->save();
                        }
                    }
                }
            }

            // 2. Process Itemized Counter Expenses (Video Walkthrough requirement)
            if (!empty($expenseRows)) {
                foreach ($expenseRows as $expRow) {
                    ShiftClosingExpense::create([
                        'shift_closing_id'    => $closing->id,
                        'expense_category_id' => $expRow['expense_category_id'],
                        'category_name'       => $expRow['category_name'],
                        'details'             => $expRow['details'],
                        'amount'              => $expRow['amount'],
                    ]);
                }
            }

            // 3. Process Lubricants / Engine Oil Counter Sales (Audio 6)
            if (!empty($lubricantRows)) {
                foreach ($lubricantRows as $lubRow) {
                    $prod = Product::lockForUpdate()->find($lubRow['product_id']);
                    $qty = (float) $lubRow['quantity'];
                    $price = (float) $lubRow['unit_price'];
                    $sub = round($qty * $price, 2);

                    ShiftClosingLubricant::create([
                        'shift_closing_id' => $closing->id,
                        'product_id'       => $lubRow['product_id'],
                        'quantity'         => $qty,
                        'unit_price'       => $price,
                        'subtotal'         => $sub,
                    ]);

                    if ($prod) {
                        $prod->current_stock = max(0, $prod->current_stock - $qty);
                        $prod->save();
                    }
                }
            }

            // 4. Process Credit / Udhar Sales to Customer Parties (Audio 8)
            if (!empty($creditRows)) {
                foreach ($creditRows as $cRow) {
                    $party = Party::lockForUpdate()->find($cRow['party_id']);
                    $amt = (float) $cRow['amount'];

                    ShiftClosingCredit::create([
                        'shift_closing_id' => $closing->id,
                        'party_id'         => $cRow['party_id'],
                        'product_id'       => $cRow['product_id'],
                        'vehicle_no'       => $cRow['vehicle_no'],
                        'slip_no'          => $cRow['slip_no'],
                        'liters'           => $cRow['liters'],
                        'rate'             => $cRow['rate'],
                        'amount'           => $amt,
                        'notes'            => $cRow['notes'],
                    ]);

                    if ($party) {
                        // Increase Party Debit / Receivable Khata
                        $party->current_balance += $amt;
                        $party->save();

                        // Record Transaction for Customer Ledger
                        Transaction::create([
                            'date'             => $validated['date'],
                            'type'             => 'payment_out',
                            'shift_closing_id' => $closing->id,
                            'party_id'         => $party->id,
                            'amount'           => $amt,
                            'bill_no'          => $cRow['slip_no'] ?: ('CREDIT-SHIFT-' . $closing->id),
                            'description'      => "Fuel Credit Sale (Shift #{$closing->id}) - " . ($cRow['vehicle_no'] ? "Veh: {$cRow['vehicle_no']}, " : "") . "{$cRow['liters']} Ltr",
                            'created_by'       => auth()->id(),
                        ]);
                    }
                }
            }

            // 5. Process Digital Collections & Transactions
            $jazzcashSum = 0;
            $bankSum = 0;
            foreach ($accountRows as $accPayment) {
                $account = Account::whereKey($accPayment['account_id'])->lockForUpdate()->firstOrFail();
                $amt = (float) $accPayment['amount'];

                $account->current_balance += $amt;
                $account->save();

                Transaction::create([
                    'date'             => $validated['date'],
                    'type'             => 'payment_in',
                    'shift_closing_id' => $closing->id,
                    'account_id'       => $account->id,
                    'amount'           => $amt,
                    'bill_no'          => 'Shift #' . $closing->id,
                    'description'      => $accPayment['description'] ?? ("Shift closing collection ({$closing->shift_type})"),
                    'created_by'       => auth()->id(),
                ]);

                if ($account->type === 'jazzcash') {
                    $jazzcashSum += $amt;
                } else {
                    $bankSum += $amt;
                }
            }

            if ($totalAccountCollections > 0) {
                $closing->jazzcash_amount = $jazzcashSum;
                $closing->bank_amount = $bankSum;
                $closing->save();
            }

            // 6. Process Government Subsidy Payment (Audio 3)
            if ($govtSubsidy > 0 && !empty($validated['govt_subsidy_account_id'])) {
                $subsidyAcc = Account::whereKey($validated['govt_subsidy_account_id'])->lockForUpdate()->first();
                if ($subsidyAcc) {
                    $subsidyAcc->current_balance += $govtSubsidy;
                    $subsidyAcc->save();

                    Transaction::create([
                        'date'             => $validated['date'],
                        'type'             => 'payment_in',
                        'shift_closing_id' => $closing->id,
                        'account_id'       => $subsidyAcc->id,
                        'amount'           => $govtSubsidy,
                        'bill_no'          => 'GOVT-SUB-' . $closing->id,
                        'description'      => "Government Fuel Subsidy Vouchers (Shift #{$closing->id}) - " . ($validated['govt_subsidy_remarks'] ?? 'Mobile App Subsidy'),
                        'created_by'       => auth()->id(),
                    ]);
                }
            }

            return $closing;
        });

        // Directly sync cash account & day closing immediately
        Account::syncCashAccountBalance();
        \App\Models\DayClosing::autoSyncDay($closing->date);

        ActivityLog::log(
            'Created',
            'Shift Closing',
            "Recorded {$closing->shift_type} shift closing sheet #{$closing->id} (Dispensed: " . number_format($closing->total_liters, 2) . " Ltr, Test: " . number_format($closing->testing_liters, 2) . " Ltr, Sales: Rs. " . number_format($closing->total_sale, 2) . ", Oil: Rs. " . number_format($closing->total_lubricant_sales, 2) . ", Credit: Rs. " . number_format($closing->total_credit_sales, 2) . ", Counted: Rs. " . number_format($closing->total_counted_cash, 2) . ")"
        );

        return redirect()->route('shift-closings.show', $closing->id)
                         ->with('success', 'Shift closing submitted and locked successfully.');
    }

    public function show($id)
    {
        $shiftClosing = ShiftClosing::withTrashed()
            ->with([
                'cashier',
                'closedBy',
                'verifier',
                'deletedBy',
                'pump',
                'product',
                'nozzles.pump',
                'nozzles.product',
                'nozzles.tank',
                'expenses',
                'lubricants.product',
                'credits.party',
                'govtSubsidyAccount',
                'transactions.account'
            ])
            ->findOrFail($id);

        return view('shift_closings.show', compact('shiftClosing'));
    }

    public function unlock(ShiftClosing $shiftClosing)
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isIncharge()) {
            abort(403, 'Only Owner or Incharge can unlock a submitted shift closing.');
        }

        $shiftClosing->status = 'submitted';
        $shiftClosing->verified_by = auth()->id();
        $shiftClosing->save();

        ActivityLog::log('Unlocked', 'Shift Closing', "Unlocked shift closing #{$shiftClosing->id} for verification");

        return back()->with('success', 'Shift closing has been unlocked for verification.');
    }

    public function lock(ShiftClosing $shiftClosing)
    {
        $shiftClosing->status = 'locked';
        $shiftClosing->save();

        ActivityLog::log('Locked', 'Shift Closing', "Locked/Verified shift closing #{$shiftClosing->id}");

        return back()->with('success', 'Shift closing locked.');
    }

    public function destroy(ShiftClosing $shiftClosing)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Only Owner can delete closing sheets.');
        }

        $closingId = $shiftClosing->id;

        DB::transaction(function () use ($shiftClosing) {
            $shiftClosing->load([
                'partyPayments',
                'transactions.account',
                'nozzles.pump',
                'lubricants',
                'credits'
            ]);

            // Reverse party credit balances
            foreach ($shiftClosing->credits as $credit) {
                $party = Party::whereKey($credit->party_id)->lockForUpdate()->first();
                if ($party) {
                    $party->current_balance = max(0, $party->current_balance - $credit->amount);
                    $party->save();
                }
            }

            // Reverse digital & subsidy transaction accounts
            foreach ($shiftClosing->transactions as $tx) {
                if ($tx->account_id) {
                    $account = Account::whereKey($tx->account_id)->lockForUpdate()->first();
                    if ($account) {
                        $account->current_balance = max(0, $account->current_balance - $tx->amount);
                        $account->save();
                    }
                }
                $tx->delete();
            }

            // Restore Product stock for lubricants sold
            foreach ($shiftClosing->lubricants as $lub) {
                $p = Product::lockForUpdate()->find($lub->product_id);
                if ($p) {
                    $p->current_stock += (float) $lub->quantity;
                    $p->save();
                }
            }

            // Restore Tank & Product stock and reverse nozzle readings
            if ($shiftClosing->nozzles->isNotEmpty()) {
                foreach ($shiftClosing->nozzles as $nz) {
                    if ($nz->tank_id && $nz->sold_liters > 0) {
                        $tank = Tank::lockForUpdate()->find($nz->tank_id);
                        if ($tank) {
                            $tank->current_stock += (float) $nz->sold_liters;
                            $tank->save();
                        }
                    }
                    if ($nz->product_id && $nz->sold_liters > 0) {
                        $prod = Product::lockForUpdate()->find($nz->product_id);
                        if ($prod) {
                            $prod->current_stock += (float) $nz->sold_liters;
                            $prod->save();
                        }
                    }
                    if ($nz->pump_id) {
                        $pump = Pump::lockForUpdate()->find($nz->pump_id);
                        if ($pump && (float)$pump->last_reading == (float)$nz->end_reading) {
                            $pump->last_reading = (float)$nz->start_reading;
                            $pump->save();
                        }
                    }
                }
                PumpMeterReading::where('shift_closing_id', $shiftClosing->id)->delete();
            } else {
                // Fallback for single pump
                if ($shiftClosing->product_id && (float)$shiftClosing->total_liters > 0) {
                    $product = Product::lockForUpdate()->find($shiftClosing->product_id);
                    if ($product) {
                        $product->current_stock += (float) $shiftClosing->total_liters;
                        $product->save();
                    }
                }

                if ($shiftClosing->tank_id && (float)$shiftClosing->total_liters > 0) {
                    $tank = Tank::lockForUpdate()->find($shiftClosing->tank_id);
                    if ($tank) {
                        $tank->current_stock += (float) $shiftClosing->total_liters;
                        $tank->save();
                    }
                }

                if ($shiftClosing->pump_id) {
                    $pump = Pump::lockForUpdate()->find($shiftClosing->pump_id);
                    if ($pump && (float)$pump->last_reading == (float)$shiftClosing->end_reading) {
                        $pump->last_reading = (float)$shiftClosing->start_reading;
                        $pump->save();
                    }
                    PumpMeterReading::where('shift_closing_id', $shiftClosing->id)->delete();
                }
            }

            $shiftClosing->deleted_by = auth()->id();
            $shiftClosing->save();
            $shiftClosing->delete();
        });

        Account::syncCashAccountBalance();
        \App\Models\DayClosing::autoSyncDay($shiftClosing->date);

        ActivityLog::log(
            'Deleted',
            'Shift Closing',
            "Deleted and archived {$shiftClosing->shift_type} shift closing sheet #{$closingId} by " . auth()->user()->name
        );

        return redirect()->route('shift-closings.index')->with('success', "Shift closing sheet #{$closingId} deleted and safely archived in Deleted Sheets audit.");
    }
}
