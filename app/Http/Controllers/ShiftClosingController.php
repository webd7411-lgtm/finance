<?php

namespace App\Http\Controllers;

use App\Models\ShiftClosing;
use App\Models\Party;
use App\Models\Account;
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
        $query = ShiftClosing::with(['cashier', 'verifier'])->latest('date')->latest('id');

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
            'parties'
        ));
    }

    public function create()
    {
        $parties = Party::orderBy('name')->get();
        $accounts = Account::orderBy('type')->orderBy('name')->get();
        return view('shift_closings.create', compact('parties', 'accounts'));
    }

    public function store(Request $request)
    {
        $paymentRows = collect($request->input('party_payments', []))
            ->filter(fn ($payment) => collect($payment)->filter(fn ($value) => filled($value))->isNotEmpty())
            ->values()
            ->all();
        $request->merge(['party_payments' => $paymentRows]);

        $accountRows = collect($request->input('account_payments', []))
            ->filter(fn ($acc) => !empty($acc['account_id']) && !empty($acc['amount']) && (float)$acc['amount'] > 0)
            ->values()
            ->all();
        $request->merge(['account_payments' => $accountRows]);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'shift_type' => ['required', Rule::in(['morning', 'evening'])],
            'invoice_start' => ['nullable', 'required_with:invoice_end', 'integer', 'min:1'],
            'invoice_end' => ['nullable', 'required_with:invoice_start', 'integer', 'gte:invoice_start'],
            'total_sale' => ['required', 'numeric', 'min:0'],
            'returns_amount' => ['nullable', 'numeric', 'min:0'],
            'return_invoice_number' => ['nullable', 'string', 'max:100'],
            'expenses_amount' => ['nullable', 'numeric', 'min:0'],
            'expenses_details' => ['nullable', 'string', 'max:2000'],
            'party_payments' => ['nullable', 'array', 'max:20'],
            'party_payments.*.party_id' => ['required', 'integer', 'exists:parties,id'],
            'party_payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'party_payments.*.details' => ['required', 'string', 'max:1000'],
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
        ]);

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

        $closing = DB::transaction(function () use (
            $validated,
            $invoiceCount,
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
                'cashier_id' => auth()->id(),
                'total_invoices' => $invoiceCount,
                'invoice_start' => $validated['invoice_start'] ?? null,
                'invoice_end' => $validated['invoice_end'] ?? null,
                'total_sale' => $totalSale,
                'returns_amount' => $returns,
                'return_invoice_number' => $validated['return_invoice_number'] ?? null,
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

        ActivityLog::log(
            'Created',
            'Shift Closing',
            "Recorded {$closing->shift_type} shift closing sheet #{$closing->id} (Counted: Rs. " . number_format($closing->total_counted_cash, 2) . ", Diff: Rs. " . number_format($closing->difference, 2) . ")"
        );

        return redirect()->route('shift-closings.show', $closing->id)
                         ->with('success', 'Shift closing submitted and locked successfully.');
    }

    public function show(ShiftClosing $shiftClosing)
    {
        $shiftClosing->load(['cashier', 'verifier', 'partyPayments.party', 'transactions.account']);
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

            $shiftClosing->delete();
        });

        ActivityLog::log('Deleted', 'Shift Closing', "Deleted shift closing sheet #{$closingId}");

        return redirect()->route('shift-closings.index')->with('success', 'Shift closing sheet deleted.');
    }
}
