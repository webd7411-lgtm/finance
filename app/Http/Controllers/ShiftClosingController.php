<?php

namespace App\Http\Controllers;

use App\Models\ShiftClosing;
use App\Models\User;
use App\Models\Cashier;
use App\Models\Pump;
use App\Models\PumpMeterReading;
use App\Models\Product;
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
            $query = ShiftClosing::onlyTrashed()->with(['cashier', 'verifier', 'deletedBy', 'pump', 'product'])->latest('deleted_at');
        } else {
            $query = ShiftClosing::with(['cashier', 'verifier', 'pump', 'product'])->latest('date')->latest('id');
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
        $expenseCategories = ExpenseCategory::expense()->orderBy('name')->get();

        // Petrol Pump Machines / Dispensers
        $pumps = Pump::with('product')->where('status', 'active')->orderBy('name')->get();
        if ($pumps->isEmpty()) {
            $pumps = Pump::with('product')->orderBy('name')->get();
        }
        $fuelProducts = Product::active()->fuel()->orderBy('name')->get();

        $selectedPumpId = $request->input('pump_id');

        return view('shift_closings.create', compact(
            'cashiers',
            'pumps',
            'fuelProducts',
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

        $request->merge([
            'start_reading' => $stripCommas($request->input('start_reading')),
            'end_reading' => $stripCommas($request->input('end_reading')),
            'total_liters' => $stripCommas($request->input('total_liters')),
            'fuel_rate' => $stripCommas($request->input('fuel_rate')),
            'total_sale' => $stripCommas($request->input('total_sale')),
            'expenses_amount' => $stripCommas($request->input('expenses_amount')),
            'coins' => $stripCommas($request->input('coins')),
        ]);

        // Clean and filter digital account payments
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
        $request->merge(['account_payments' => $accountRows]);

        $yesterday = now()->subDay()->toDateString();
        $today = now()->toDateString();

        $validated = $request->validate([
            'date' => ['required', 'date', "after_or_equal:{$yesterday}", "before_or_equal:{$today}"],
            // Single-shift restriction REMOVED: Multiple shifts allowed per day
            'shift_type' => ['required', Rule::in(['morning', 'evening'])],
            'cashier_id' => ['required', 'integer', 'exists:cashiers,id'],
            'pump_id' => ['nullable', 'integer', 'exists:pumps,id'],
            'pump_machine' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            
            // Meter Reading System
            'start_reading' => ['required', 'numeric', 'min:0'],
            'end_reading' => ['required', 'numeric', 'gte:start_reading'],
            'total_liters' => ['required', 'numeric', 'min:0'],
            'fuel_rate' => ['required', 'numeric', 'min:0'],
            'total_sale' => ['required', 'numeric', 'min:0'],
            
            'expenses_amount' => ['nullable', 'numeric', 'min:0'],
            'expenses_details' => ['nullable', 'string', 'max:2000'],
            
            'account_payments' => ['nullable', 'array', 'max:20'],
            'account_payments.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'account_payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'account_payments.*.description' => ['nullable', 'string', 'max:500'],
            
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
        ], [
            'date.after_or_equal' => 'Shift closing can only be recorded for today or yesterday.',
            'date.before_or_equal' => 'Shift closing cannot be recorded for future dates.',
            'cashier_id.required' => 'Please select a shift cashier from Master Setup.',
            'start_reading.required' => 'Start meter reading is required.',
            'end_reading.required' => 'End meter reading is required.',
            'end_reading.gte' => 'End meter reading must be greater than or equal to Start reading.',
            'fuel_rate.required' => 'Fuel rate per liter is required.',
        ]);

        $cashierId = (int) ($validated['cashier_id'] ?? auth()->id());

        // Default zero values for cash note denominations
        $n5000 = $validated['note_5000'] ?? 0;
        $n1000 = $validated['note_1000'] ?? 0;
        $n500  = $validated['note_500'] ?? 0;
        $n100  = $validated['note_100'] ?? 0;
        $n50   = $validated['note_50'] ?? 0;
        $n20   = $validated['note_20'] ?? 0;
        $n10   = $validated['note_10'] ?? 0;
        $coins = $validated['coins'] ?? 0;

        // Auto-calculate readings & volume
        $startReading = (float) $validated['start_reading'];
        $endReading = (float) $validated['end_reading'];
        $totalLiters = (float) max(0, $endReading - $startReading);
        $fuelRate = (float) $validated['fuel_rate'];
        $totalSale = (float) $validated['total_sale'];

        if ($totalSale <= 0 && $totalLiters > 0 && $fuelRate > 0) {
            $totalSale = round($totalLiters * $fuelRate, 2);
        }

        // Physical Cash Counted
        $countedCash = ($n5000 * 5000) + ($n1000 * 1000) + ($n500 * 500) +
                       ($n100 * 100) + ($n50 * 50) + ($n20 * 20) + ($n10 * 10) + $coins;

        $accountPayments = $validated['account_payments'] ?? [];
        $totalAccountCollections = (float) collect($accountPayments)->sum('amount');
        $jazzcash = (float) ($validated['jazzcash_amount'] ?? 0);
        $bank = (float) ($validated['bank_amount'] ?? 0);

        // Digital Collections: from account rows, or fallback legacy fields
        $totalDigital = $totalAccountCollections > 0
            ? $totalAccountCollections
            : ($jazzcash + $bank);

        // Actual Received: Counted Cash + Digital Collections
        $actualReceived = $countedCash + $totalDigital;

        // Clean Petrol Pump Calculations: Expected Cash = Total Sale - Shift Expenses
        $expenses = (float) ($validated['expenses_amount'] ?? 0);
        $expectedCash = $totalSale - $expenses;

        // Difference: Actual - Expected
        $difference = $actualReceived - $expectedCash;

        if ($difference < 0) {
            throw ValidationException::withMessages([
                'difference' => 'Shift cannot be closed with a negative variance / cash shortage (Shortage: Rs. ' . number_format(abs($difference), 2) . '). Shift closing can only be saved when balanced or surplus.',
            ]);
        }

        // Pump Machine Label
        $pumpId = !empty($validated['pump_id']) ? (int) $validated['pump_id'] : null;
        $pumpMachineName = $validated['pump_machine'] ?? null;
        if ($pumpId && empty($pumpMachineName)) {
            $pumpObj = Pump::find($pumpId);
            $pumpMachineName = $pumpObj?->name;
        }

        $closing = DB::transaction(function () use (
            $validated,
            $cashierId,
            $pumpId,
            $pumpMachineName,
            $startReading,
            $endReading,
            $totalLiters,
            $fuelRate,
            $totalSale,
            $expenses,
            $n5000,
            $n1000,
            $n500,
            $n100,
            $n50,
            $n20,
            $n10,
            $coins,
            $countedCash,
            $jazzcash,
            $bank,
            $actualReceived,
            $expectedCash,
            $difference,
            $accountPayments,
            $totalAccountCollections
        ) {
            $closing = ShiftClosing::create([
                'date' => $validated['date'],
                'shift_type' => $validated['shift_type'],
                'cashier_id' => $cashierId,
                'closed_by' => auth()->id() ?? $cashierId,
                'pump_id' => $pumpId,
                'pump_machine' => $pumpMachineName,
                'product_id' => $validated['product_id'] ?? null,
                'start_reading' => $startReading,
                'end_reading' => $endReading,
                'total_liters' => $totalLiters,
                'fuel_rate' => $fuelRate,
                'total_sale' => $totalSale,
                'returns_amount' => 0,
                'expenses_amount' => $expenses,
                'expenses_details' => $validated['expenses_details'] ?? null,
                'note_5000' => $n5000,
                'note_1000' => $n1000,
                'note_500' => $n500,
                'note_100' => $n100,
                'note_50' => $n50,
                'note_20' => $n20,
                'note_10' => $n10,
                'coins' => $coins,
                'total_counted_cash' => $countedCash,
                'jazzcash_amount' => $jazzcash,
                'bank_amount' => $bank,
                'total_actual_received' => $actualReceived,
                'expected_cash' => $expectedCash,
                'difference' => $difference,
                'status' => 'locked',
                'remarks' => $validated['remarks'] ?? null,
            ]);

            // 1. Update Pump's live reading to end_reading for the next shift and record meter reading log
            if ($pumpId) {
                $pump = Pump::find($pumpId);
                if ($pump) {
                    $pump->last_reading = $endReading;
                    $pump->save();

                    // Permanent Meter Reading Audit History Log
                    PumpMeterReading::create([
                        'pump_id'          => $pump->id,
                        'shift_closing_id' => $closing->id,
                        'reading_date'     => $validated['date'],
                        'reading_type'     => 'shift_sale',
                        'start_reading'    => $startReading,
                        'end_reading'      => $endReading,
                        'volume_dispensed' => $totalLiters,
                        'fuel_rate'        => $fuelRate,
                        'sales_amount'     => $totalSale,
                        'cashier_id'       => $cashierId,
                        'recorded_by'      => auth()->id(),
                        'remarks'          => "Shift Closing #{$closing->id} (" . ucfirst($validated['shift_type']) . " Shift)",
                    ]);
                }
            }

            // 2. Decrement Product Live Stock (fuel volume sold through nozzles)
            if (!empty($validated['product_id']) && $totalLiters > 0) {
                $product = Product::find($validated['product_id']);
                if ($product) {
                    $product->current_stock = max(0, $product->current_stock - $totalLiters);
                    $product->save();
                }
            }

            // 3. Process dynamic account collections
            $jazzcashSum = 0;
            $bankSum = 0;
            foreach ($accountPayments as $accPayment) {
                $account = Account::whereKey($accPayment['account_id'])->lockForUpdate()->firstOrFail();
                $amt = (float) $accPayment['amount'];

                $account->current_balance += $amt;
                $account->save();

                Transaction::create([
                    'date' => $validated['date'],
                    'type' => 'payment_in',
                    'shift_closing_id' => $closing->id,
                    'account_id' => $account->id,
                    'amount' => $amt,
                    'bill_no' => 'Shift #' . $closing->id,
                    'description' => $accPayment['description'] ?? ("Shift closing collection ({$closing->shift_type})"),
                    'created_by' => auth()->id(),
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

            return $closing;
        });

        // Directly sync cash account & day closing immediately
        \App\Models\Account::syncCashAccountBalance();
        \App\Models\DayClosing::autoSyncDay($closing->date);

        ActivityLog::log(
            'Created',
            'Shift Closing',
            "Recorded {$closing->shift_type} shift closing sheet #{$closing->id} (Pump: " . ($closing->pump_machine ?? 'General') . ", Liters: " . number_format($closing->total_liters, 2) . ", Sale: Rs. " . number_format($closing->total_sale, 2) . ", Counted: Rs. " . number_format($closing->total_counted_cash, 2) . ")"
        );

        return redirect()->route('shift-closings.show', $closing->id)
                         ->with('success', 'Shift closing submitted and locked successfully.');
    }

    public function show($id)
    {
        $shiftClosing = ShiftClosing::withTrashed()
            ->with(['cashier', 'closedBy', 'verifier', 'deletedBy', 'pump', 'product', 'transactions.account'])
            ->findOrFail($id);

        return view('shift_closings.show', compact('shiftClosing'));
    }

    public function unlock(ShiftClosing $shiftClosing)
    {
        // Only Owner and Incharge can unlock
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
            $shiftClosing->load(['partyPayments', 'transactions.account']);
            foreach ($shiftClosing->partyPayments as $payment) {
                $party = Party::whereKey($payment->party_id)->lockForUpdate()->first();
                if (!$party) {
                    continue;
                }

                if ($party->type === 'staff') {
                    $party->current_balance -= $payment->amount;
                } else {
                    $party->current_balance += $payment->amount;
                }
                $party->save();
            }

            foreach ($shiftClosing->transactions as $tx) {
                $account = Account::whereKey($tx->account_id)->lockForUpdate()->first();
                if ($account) {
                    $account->current_balance -= $tx->amount;
                    $account->save();
                }
                $tx->delete();
            }

            // Restore Product stock if fuel was dispensed
            if ($shiftClosing->product_id && (float)$shiftClosing->total_liters > 0) {
                $product = Product::lockForUpdate()->find($shiftClosing->product_id);
                if ($product) {
                    $product->current_stock += (float) $shiftClosing->total_liters;
                    $product->save();
                }
            }

            // Reverse Pump reading and remove meter reading entry
            if ($shiftClosing->pump_id) {
                $pump = Pump::lockForUpdate()->find($shiftClosing->pump_id);
                if ($pump && (float)$pump->last_reading == (float)$shiftClosing->end_reading) {
                    $pump->last_reading = (float)$shiftClosing->start_reading;
                    $pump->save();
                }
                PumpMeterReading::where('shift_closing_id', $shiftClosing->id)->delete();
            }

            $shiftClosing->deleted_by = auth()->id();
            $shiftClosing->save();
            $shiftClosing->delete();
        });

        \App\Models\Account::syncCashAccountBalance();
        \App\Models\DayClosing::autoSyncDay($shiftClosing->date);

        ActivityLog::log(
            'Deleted',
            'Shift Closing',
            "Deleted and archived {$shiftClosing->shift_type} shift closing sheet #{$closingId} (Date: " . $shiftClosing->date->format('d M Y') . ", Sales: Rs. " . number_format($shiftClosing->total_sale, 2) . ", Counted: Rs. " . number_format($shiftClosing->total_counted_cash, 2) . ", Diff: Rs. " . number_format($shiftClosing->difference, 2) . ") by " . auth()->user()->name
        );

        return redirect()->route('shift-closings.index')->with('success', "Shift closing sheet #{$closingId} deleted and safely archived in Deleted Sheets audit.");
    }
}
