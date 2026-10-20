<?php

namespace App\Http\Controllers;

use App\Models\ShiftClosing;
use App\Models\User;
use App\Models\Cashier;
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
            $query = ShiftClosing::onlyTrashed()->with(['cashier', 'verifier', 'deletedBy'])->latest('deleted_at');
        } else {
            $query = ShiftClosing::with(['cashier', 'verifier'])->latest('date')->latest('id');
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('shift_type')) {
            $query->where('shift_type', $request->shift_type);
        }

        $closings = $query->paginate(15)->withQueryString();

        // Summary calculations
        $totalSalesToday = ShiftClosing::whereDate('date', now()->toDateString())->sum('total_sale');
        $totalReceivedToday = ShiftClosing::whereDate('date', now()->toDateString())->sum('total_actual_received');
        $totalDifferenceToday = ShiftClosing::whereDate('date', now()->toDateString())->sum('difference');
        $parties = Party::orderBy('name')->get();

        return view('shift_closings.index', compact(
            'closings',
            'totalSalesToday',
            'totalReceivedToday',
            'totalDifferenceToday',
            'parties',
            'isTrashView',
            'activeCount',
            'trashCount'
        ));
    }

    public function create()
    {
        $parties = Party::orderBy('name')->get();
        $accounts = Account::where('type', '!=', 'cash')->where('status', 'active')->orderBy('type')->orderBy('name')->get();
        $cashiers = Cashier::where('status', 'active')->orderBy('name')->get();
        if ($cashiers->isEmpty()) {
            $cashiers = Cashier::orderBy('name')->get();
        }
        $expenseCategories = ExpenseCategory::expense()->orderBy('name')->get();

        $lastShift = ShiftClosing::whereNotNull('invoice_end')
            ->where('invoice_end', '>', 0)
            ->orderByDesc('id')
            ->first();

        $suggestedInvoiceStart = $lastShift ? ((int) $lastShift->invoice_end + 1) : null;

        $lastReturnShift = ShiftClosing::where(function ($q) {
                $q->whereNotNull('return_invoice_end')->where('return_invoice_end', '>', 0);
            })
            ->orWhere(function ($q) {
                $q->whereNotNull('return_invoice_start')->where('return_invoice_start', '>', 0);
            })
            ->orderByDesc('id')
            ->first();

        $suggestedReturnInvoiceStart = null;
        if ($lastReturnShift) {
            $lastEnd = $lastReturnShift->return_invoice_end ?: $lastReturnShift->return_invoice_start;
            if ($lastEnd > 0) {
                $suggestedReturnInvoiceStart = (int) $lastEnd + 1;
            }
        }

        $existingClosings = ShiftClosing::select('date', 'shift_type')
            ->whereIn('date', [now()->subDay()->toDateString(), now()->toDateString()])
            ->get()
            ->map(fn($item) => $item->date->format('Y-m-d') . '_' . $item->shift_type)
            ->values()
            ->toArray();

        return view('shift_closings.create', compact('parties', 'accounts', 'suggestedInvoiceStart', 'suggestedReturnInvoiceStart', 'cashiers', 'expenseCategories', 'existingClosings'));
    }

    public function store(Request $request)
    {
        $stripCommas = function ($val) {
            if (is_null($val) || $val === '') return $val;
            return is_string($val) ? str_replace(',', '', $val) : $val;
        };

        $request->merge([
            'total_sale' => $stripCommas($request->input('total_sale')),
            'returns_amount' => $stripCommas($request->input('returns_amount')),
            'expenses_amount' => $stripCommas($request->input('expenses_amount')),
            'coins' => $stripCommas($request->input('coins')),
        ]);

        // 1. Clean and filter party payments: only keep rows with valid party_id and amount > 0
        $paymentRows = collect($request->input('party_payments', []))
            ->map(function ($payment) use ($stripCommas) {
                if (isset($payment['amount'])) {
                    $payment['amount'] = $stripCommas($payment['amount']);
                }
                return $payment;
            })
            ->filter(function ($payment) {
                return !empty($payment['party_id']) && !empty($payment['amount']) && (float)$payment['amount'] > 0;
            })
            ->map(function ($payment) {
                return [
                    'party_id' => (int) $payment['party_id'],
                    'expense_category_id' => !empty($payment['expense_category_id']) ? (int) $payment['expense_category_id'] : null,
                    'amount' => (float) $payment['amount'],
                    'details' => filled($payment['details'] ?? null) ? $payment['details'] : 'Shift cash payout',
                ];
            })
            ->values()
            ->all();
        $request->merge(['party_payments' => $paymentRows]);

        // 2. Clean and filter digital account payments
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

        // 3. Invoice range handling: If invoice_end was left empty, clear invoice_start so it doesn't fail required_with
        if (!$request->filled('invoice_end')) {
            $request->merge(['invoice_start' => null, 'invoice_end' => null]);
        } else {
            // Lock invoice_start to previous shift's end + 1 if previous shift exists
            $lastShift = ShiftClosing::whereNotNull('invoice_end')
                ->where('invoice_end', '>', 0)
                ->orderByDesc('id')
                ->first();
            if ($lastShift) {
                $request->merge(['invoice_start' => (int) $lastShift->invoice_end + 1]);
            }
        }

        // Return invoice start enforcement
        if ($request->filled('return_invoice_end') || $request->filled('return_invoice_start')) {
            $lastReturnShift = ShiftClosing::where(function ($q) {
                    $q->whereNotNull('return_invoice_end')->where('return_invoice_end', '>', 0);
                })
                ->orWhere(function ($q) {
                    $q->whereNotNull('return_invoice_start')->where('return_invoice_start', '>', 0);
                })
                ->orderByDesc('id')
                ->first();

            if ($lastReturnShift) {
                $lastRetEnd = $lastReturnShift->return_invoice_end ?: $lastReturnShift->return_invoice_start;
                if ($lastRetEnd > 0) {
                    $request->merge(['return_invoice_start' => (int) $lastRetEnd + 1]);
                }
            }
        }

        $yesterday = now()->subDay()->toDateString();
        $today = now()->toDateString();

        $validated = $request->validate([
            'date' => ['required', 'date', "after_or_equal:{$yesterday}", "before_or_equal:{$today}"],
            'shift_type' => [
                'required',
                Rule::in(['morning', 'evening']),
                Rule::unique('shift_closings', 'shift_type')->where(function ($query) use ($request) {
                    return $query->whereDate('date', $request->date)->whereNull('deleted_at');
                }),
            ],
            'cashier_id' => ['required', 'integer', 'exists:cashiers,id'],
            'invoice_start' => ['nullable', 'integer', 'min:1'],
            'invoice_end' => ['nullable', 'integer', 'gte:invoice_start'],
            'total_sale' => ['required', 'numeric', 'min:0'],
            'returns_amount' => ['nullable', 'numeric', 'min:0'],
            'return_invoice_start' => ['nullable', 'integer', 'min:1'],
            'return_invoice_end' => ['nullable', 'integer', 'min:1'],
            'return_invoice_number' => ['nullable', 'string', 'max:255'],
            'expenses_amount' => ['nullable', 'numeric', 'min:0'],
            'expenses_details' => ['nullable', 'string', 'max:2000'],
            'party_payments' => ['nullable', 'array', 'max:20'],
            'party_payments.*.party_id' => ['required', 'integer', 'exists:parties,id'],
            'party_payments.*.expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'party_payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'party_payments.*.details' => ['nullable', 'string', 'max:1000'],
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
            'shift_type.unique' => 'The ' . ucfirst($request->shift_type) . ' shift for this date is already closed. A shift cannot be closed more than once.',
            'cashier_id.required' => 'Please select a shift cashier from Master Setup.',
            'cashier_id.exists' => 'The selected cashier is invalid. Please configure cashiers in Master Setup.',
            'invoice_end.gte' => 'Invoice End number must be greater than or equal to Invoice Start number.',
        ]);

        $cashierId = (int) ($validated['cashier_id'] ?? auth()->id());

        // Default zero values
        $n5000 = $validated['note_5000'] ?? 0;
        $n1000 = $validated['note_1000'] ?? 0;
        $n500  = $validated['note_500'] ?? 0;
        $n100  = $validated['note_100'] ?? 0;
        $n50   = $validated['note_50'] ?? 0;
        $n20   = $validated['note_20'] ?? 0;
        $n10   = $validated['note_10'] ?? 0;
        $coins = $validated['coins'] ?? 0;
        $invoiceCount = isset($validated['invoice_start'], $validated['invoice_end'])
            ? $validated['invoice_end'] - $validated['invoice_start'] + 1
            : 0;

        // Return Invoice Calculation
        $returns = (float) ($validated['returns_amount'] ?? 0);
        $retStart = $request->filled('return_invoice_start') ? (int) $request->input('return_invoice_start') : null;
        $retEnd = $request->filled('return_invoice_end') ? (int) $request->input('return_invoice_end') : null;

        if ($returns <= 0 && $retEnd === null) {
            $retStart = null;
        } elseif ($retStart !== null && $retEnd === null) {
            $retEnd = $retStart;
        } elseif ($retStart === null && $retEnd !== null) {
            $retStart = $retEnd;
        }

        $totalReturnInvoices = 0;
        $returnInvoiceNumber = null;
        if ($retStart !== null && $retEnd !== null) {
            if ($retEnd >= $retStart) {
                $totalReturnInvoices = $retEnd - $retStart + 1;
                $returnInvoiceNumber = $retStart === $retEnd ? "#{$retStart}" : "#{$retStart} - #{$retEnd}";
            } else {
                $totalReturnInvoices = 1;
                $returnInvoiceNumber = "#{$retStart}";
            }
        } elseif ($returnInvoiceNumber === null && !empty($validated['return_invoice_number'])) {
            $returnInvoiceNumber = $validated['return_invoice_number'];
        }

        // Note Calculation
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

        // Expected Cash: Total Sale - Returns - Expenses - Party Payments
        $totalSale = $validated['total_sale'] ?? 0;
        $returns = $validated['returns_amount'] ?? 0;
        $expenses = $validated['expenses_amount'] ?? 0;
        $partyPayments = $validated['party_payments'] ?? [];
        $totalPartyPayments = collect($partyPayments)->sum('amount');
        $expectedCash = $totalSale - $returns - $expenses - $totalPartyPayments;

        // Difference: Actual - Expected
        $difference = $actualReceived - $expectedCash;

        if ($difference < 0) {
            throw ValidationException::withMessages([
                'difference' => 'Shift cannot be closed with a negative variance / cash shortage (Shortage: Rs. ' . number_format(abs($difference), 2) . '). Shift closing can only be saved when balanced or surplus.',
            ]);
        }

        $closing = DB::transaction(function () use (
            $validated,
            $cashierId,
            $invoiceCount,
            $retStart,
            $retEnd,
            $totalReturnInvoices,
            $returnInvoiceNumber,
            $totalSale,
            $returns,
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
            $partyPayments,
            $accountPayments,
            $totalAccountCollections
        ) {
            $closing = ShiftClosing::create([
                'date' => $validated['date'],
                'shift_type' => $validated['shift_type'],
                'cashier_id' => $cashierId,
                'total_invoices' => $invoiceCount,
                'invoice_start' => $validated['invoice_start'] ?? null,
                'invoice_end' => $validated['invoice_end'] ?? null,
                'total_sale' => $totalSale,
                'returns_amount' => $returns,
                'return_invoice_number' => $returnInvoiceNumber,
                'return_invoice_start' => $retStart,
                'return_invoice_end' => $retEnd,
                'total_return_invoices' => $totalReturnInvoices,
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

            foreach ($partyPayments as $payment) {
                $closing->partyPayments()->create($payment);

                $party = Party::whereKey($payment['party_id'])->lockForUpdate()->firstOrFail();
                if ($party->type === 'staff') {
                    $party->current_balance += $payment['amount'];
                } else {
                    $party->current_balance -= $payment['amount'];
                }
                $party->save();
            }

            // Record dynamic account payments and update account balances
            $jazzcashSum = 0;
            $bankSum = 0;
            foreach ($accountPayments as $accPayment) {
                $account = Account::whereKey($accPayment['account_id'])->lockForUpdate()->firstOrFail();
                $amt = (float) $accPayment['amount'];

                // 1. Maintain account balance
                $account->current_balance += $amt;
                $account->save();

                // 2. Create Transaction record (payment_in) linking to shift closing
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
            "Recorded {$closing->shift_type} shift closing sheet #{$closing->id} (Counted: Rs. " . number_format($closing->total_counted_cash, 2) . ", Diff: Rs. " . number_format($closing->difference, 2) . ")"
        );

        return redirect()->route('shift-closings.show', $closing->id)
                         ->with('success', 'Shift closing submitted and locked successfully.');
    }

    public function show($id)
    {
        $shiftClosing = ShiftClosing::withTrashed()
            ->with(['cashier', 'verifier', 'deletedBy', 'partyPayments.party', 'partyPayments.expenseCategory', 'transactions.account'])
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
