<?php

namespace App\Http\Controllers;

use App\Models\DayClosing;
use App\Models\ShiftClosing;
use App\Models\ShiftClosingPartyPayment;
use App\Models\Transaction;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DayClosingController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $selectedDate = $request->input('date', $today);

        // Fetch shift closings for the selected date
        $morningShift = ShiftClosing::whereDate('date', $selectedDate)
                                    ->where('shift_type', 'morning')
                                    ->with(['cashier', 'partyPayments.party'])
                                    ->first();

        $eveningShift = ShiftClosing::whereDate('date', $selectedDate)
                                    ->where('shift_type', 'evening')
                                    ->with(['cashier', 'partyPayments.party'])
                                    ->first();

        // Previous Day's Closing Cash is Today's Opening Cash (Automated)
        $previousClosing = DayClosing::where('date', '<', $selectedDate)
                                     ->where('status', 'closed')
                                     ->orderBy('date', 'desc')
                                     ->first();

        $autoOpeningCash = $previousClosing ? (float)$previousClosing->closing_cash : 0.00;

        // Shift Counted Cash (Morning + Evening)
        $morningCountedCash = $morningShift ? (float)$morningShift->total_counted_cash : 0.00;
        $eveningCountedCash = $eveningShift ? (float)$eveningShift->total_counted_cash : 0.00;
        $shiftCashIn = $morningCountedCash + $eveningCountedCash;

        // Direct Payment In (Non-shift direct vouchers)
        $directCashIn = (float) Transaction::whereDate('date', $selectedDate)
                                           ->whereNull('shift_closing_id')
                                           ->where('type', 'payment_in')
                                           ->sum('amount');

        $totalCashIn = $shiftCashIn + $directCashIn;

        // Shift Expenses + Party Payments + Direct Payments Out
        $shiftExpenses = ($morningShift ? (float)$morningShift->expenses_amount : 0) +
                         ($eveningShift ? (float)$eveningShift->expenses_amount : 0);

        $shiftPartyPayments = (float) ShiftClosingPartyPayment::whereHas('shiftClosing', fn ($query) => $query->whereDate('date', $selectedDate))
            ->sum('amount');

        $directPaymentsOut = (float) Transaction::whereDate('date', $selectedDate)
                                                ->where('type', 'payment_out')
                                                ->sum('amount');

        $totalPaymentsOut = $shiftExpenses + $shiftPartyPayments + $directPaymentsOut;

        // Cumulative Differences from shifts
        $totalDifference = ($morningShift ? (float)$morningShift->difference : 0) +
                           ($eveningShift ? (float)$eveningShift->difference : 0);

        // Core Cash Equation:
        // Opening Cash (+) + Total Physical Cash In (+) - Direct Voucher Payments Out (-) = Calculated Closing Cash (=)
        $calculatedClosingCash = $autoOpeningCash + $totalCashIn - $directPaymentsOut;

        // ----------------------------------------------------
        // BANK & DIGITAL ACCOUNTS POSITION (Today's Activity)
        // ----------------------------------------------------
        $allAccounts = \App\Models\Account::orderBy('type')->orderBy('name')->get();

        $accountSummaries = $allAccounts->map(function ($account) use ($selectedDate) {
            $inflow = (float) Transaction::whereDate('date', $selectedDate)
                                         ->where('account_id', $account->id)
                                         ->where('type', 'payment_in')
                                         ->sum('amount');

            $outflow = (float) Transaction::whereDate('date', $selectedDate)
                                          ->where('account_id', $account->id)
                                          ->where('type', 'payment_out')
                                          ->sum('amount');

            return (object) [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'account_number' => $account->account_number,
                'current_balance' => (float) $account->current_balance,
                'inflow' => $inflow,
                'outflow' => $outflow,
                'net' => $inflow - $outflow,
            ];
        });

        // Shift Digital Collections (Morning + Evening)
        $morningDigitalIn = $morningShift ? (float)($morningShift->jazzcash_amount + $morningShift->bank_amount) : 0.00;
        $eveningDigitalIn = $eveningShift ? (float)($eveningShift->jazzcash_amount + $eveningShift->bank_amount) : 0.00;
        $shiftDigitalIn = $morningDigitalIn + $eveningDigitalIn;

        $digitalTransactionsIn = (float) Transaction::whereDate('date', $selectedDate)
                                                    ->where('type', 'payment_in')
                                                    ->whereHas('account', fn($q) => $q->whereIn('type', ['bank', 'jazzcash']))
                                                    ->sum('amount');

        $totalDigitalIn = max($shiftDigitalIn, $digitalTransactionsIn);
        $totalCombinedInflow = $totalCashIn + $totalDigitalIn;

        // ----------------------------------------------------
        // PARTY & KHATA TRANSACTIONS (Shift Payments + Direct Vouchers)
        // ----------------------------------------------------
        $partyTransactions = collect();

        // 1. Shift Party Payments (Morning & Evening)
        $shiftPayments = ShiftClosingPartyPayment::whereHas('shiftClosing', fn ($query) => $query->whereDate('date', $selectedDate))
            ->with(['party', 'shiftClosing'])
            ->get();

        foreach ($shiftPayments as $sp) {
            $shiftLabel = $sp->shiftClosing && $sp->shiftClosing->shift_type === 'morning' ? 'Morning Shift' : 'Evening Shift';
            $partyTransactions->push((object)[
                'id' => 'shift-' . $sp->id,
                'party_name' => $sp->party->name ?? 'Unknown Party',
                'party_type' => $sp->party->type ?? 'Party',
                'party_phone' => $sp->party->phone ?? null,
                'type' => 'payment_out',
                'shift_name' => $shiftLabel,
                'channel' => 'Cash Drawer',
                'details' => $sp->details ?? 'Shift Cash Payout',
                'inflow' => 0.00,
                'outflow' => (float) $sp->amount,
                'amount' => (float) $sp->amount,
                'created_at' => $sp->created_at,
            ]);
        }

        // 2. Direct Party Transactions (Non-Shift Vouchers)
        $directPartyTxns = Transaction::whereDate('date', $selectedDate)
            ->whereNotNull('party_id')
            ->whereNull('shift_closing_id')
            ->with(['party', 'account'])
            ->get();

        foreach ($directPartyTxns as $tx) {
            $isIn = $tx->type === 'payment_in';
            $partyTransactions->push((object)[
                'id' => 'tx-' . $tx->id,
                'party_name' => $tx->party->name ?? 'Unknown Party',
                'party_type' => $tx->party->type ?? 'Party',
                'party_phone' => $tx->party->phone ?? null,
                'type' => $tx->type,
                'shift_name' => null, // Non-shift transaction -> displayed without shift name!
                'channel' => $tx->account->name ?? 'Direct Voucher',
                'details' => $tx->description ?? $tx->bill_no ?? 'Direct Transaction',
                'inflow' => $isIn ? (float) $tx->amount : 0.00,
                'outflow' => !$isIn ? (float) $tx->amount : 0.00,
                'amount' => (float) $tx->amount,
                'created_at' => $tx->created_at,
            ]);
        }

        $partyTransactions = $partyTransactions->sortBy('created_at')->values();
        $totalPartyInflow = (float) $partyTransactions->sum('inflow');
        $totalPartyOutflow = (float) $partyTransactions->sum('outflow');
        $netPartyMovement = $totalPartyInflow - $totalPartyOutflow;

        // Check if day closing is already finalized
        $existingDayClosing = DayClosing::whereDate('date', $selectedDate)->first();

        // History of Day Closings
        $history = DayClosing::with('closer')->orderBy('date', 'desc')->paginate(15);

        return view('day_closings.index', compact(
            'selectedDate',
            'morningShift',
            'eveningShift',
            'morningCountedCash',
            'eveningCountedCash',
            'morningDigitalIn',
            'eveningDigitalIn',
            'autoOpeningCash',
            'shiftCashIn',
            'directCashIn',
            'totalCashIn',
            'shiftExpenses',
            'shiftPartyPayments',
            'directPaymentsOut',
            'totalPaymentsOut',
            'totalDifference',
            'calculatedClosingCash',
            'accountSummaries',
            'totalDigitalIn',
            'totalCombinedInflow',
            'partyTransactions',
            'totalPartyInflow',
            'totalPartyOutflow',
            'netPartyMovement',
            'existingDayClosing',
            'history'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'unique:day_closings,date'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'total_cash_in' => ['required', 'numeric', 'min:0'],
            'total_payments_out' => ['required', 'numeric', 'min:0'],
            'closing_cash' => ['required', 'numeric'],
            'total_difference' => ['nullable', 'numeric'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['closed_by'] = auth()->id();
        $validated['status'] = 'closed';

        $dayClosing = DayClosing::create($validated);

        ActivityLog::log(
            'Finalized',
            'Day Closing',
            "Finalized Day Closing for date " . $dayClosing->date->format('d M, Y') . " (Closing Cash: Rs. " . number_format($dayClosing->closing_cash, 2) . ", Collections: Rs. " . number_format($dayClosing->total_cash_in, 2) . ", Payments: Rs. " . number_format($dayClosing->total_payments_out, 2) . ")"
        );

        return redirect()->route('day-closings.show', $dayClosing->id)
                         ->with('success', 'Day closing finalized successfully. Tomorrow\'s opening cash has been set.');
    }

    public function show(DayClosing $dayClosing)
    {
        $dayClosing->load('closer');
        $date = $dayClosing->date->toDateString();

        $morningShift = ShiftClosing::whereDate('date', $date)->where('shift_type', 'morning')
            ->with(['cashier', 'partyPayments.party'])->first();
        $eveningShift = ShiftClosing::whereDate('date', $date)->where('shift_type', 'evening')
            ->with(['cashier', 'partyPayments.party'])->first();
        $transactions = Transaction::whereDate('date', $date)->with(['account', 'party'])->get();

        $allAccounts = \App\Models\Account::orderBy('type')->orderBy('name')->get();
        $accountSummaries = $allAccounts->map(function ($account) use ($date) {
            $inflow = (float) Transaction::whereDate('date', $date)
                                         ->where('account_id', $account->id)
                                         ->where('type', 'payment_in')
                                         ->sum('amount');

            $outflow = (float) Transaction::whereDate('date', $date)
                                          ->where('account_id', $account->id)
                                          ->where('type', 'payment_out')
                                          ->sum('amount');

            return (object) [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'account_number' => $account->account_number,
                'current_balance' => (float) $account->current_balance,
                'inflow' => $inflow,
                'outflow' => $outflow,
                'net' => $inflow - $outflow,
            ];
        });

        $shiftDigitalIn = ($morningShift ? (float)($morningShift->jazzcash_amount + $morningShift->bank_amount) : 0) +
                          ($eveningShift ? (float)($eveningShift->jazzcash_amount + $eveningShift->bank_amount) : 0);

        $digitalTransactionsIn = (float) Transaction::whereDate('date', $date)
                                                    ->where('type', 'payment_in')
                                                    ->whereHas('account', fn($q) => $q->whereIn('type', ['bank', 'jazzcash']))
                                                    ->sum('amount');

        $totalDigitalIn = max($shiftDigitalIn, $digitalTransactionsIn);
        $totalCombinedInflow = (float)$dayClosing->total_cash_in + $totalDigitalIn;

        // Party Transactions for this specific day
        $partyTransactions = collect();

        $shiftPayments = ShiftClosingPartyPayment::whereHas('shiftClosing', fn ($query) => $query->whereDate('date', $date))
            ->with(['party', 'shiftClosing'])
            ->get();

        foreach ($shiftPayments as $sp) {
            $shiftLabel = $sp->shiftClosing && $sp->shiftClosing->shift_type === 'morning' ? 'Morning Shift' : 'Evening Shift';
            $partyTransactions->push((object)[
                'id' => 'shift-' . $sp->id,
                'party_name' => $sp->party->name ?? 'Unknown Party',
                'party_type' => $sp->party->type ?? 'Party',
                'party_phone' => $sp->party->phone ?? null,
                'type' => 'payment_out',
                'shift_name' => $shiftLabel,
                'channel' => 'Cash Drawer',
                'details' => $sp->details ?? 'Shift Cash Payout',
                'inflow' => 0.00,
                'outflow' => (float) $sp->amount,
                'amount' => (float) $sp->amount,
                'created_at' => $sp->created_at,
            ]);
        }

        $directPartyTxns = Transaction::whereDate('date', $date)
            ->whereNotNull('party_id')
            ->whereNull('shift_closing_id')
            ->with(['party', 'account'])
            ->get();

        foreach ($directPartyTxns as $tx) {
            $isIn = $tx->type === 'payment_in';
            $partyTransactions->push((object)[
                'id' => 'tx-' . $tx->id,
                'party_name' => $tx->party->name ?? 'Unknown Party',
                'party_type' => $tx->party->type ?? 'Party',
                'party_phone' => $tx->party->phone ?? null,
                'type' => $tx->type,
                'shift_name' => null, // Non-shift transaction -> displayed without shift name!
                'channel' => $tx->account->name ?? 'Direct Voucher',
                'details' => $tx->description ?? $tx->bill_no ?? 'Direct Transaction',
                'inflow' => $isIn ? (float) $tx->amount : 0.00,
                'outflow' => !$isIn ? (float) $tx->amount : 0.00,
                'amount' => (float) $tx->amount,
                'created_at' => $tx->created_at,
            ]);
        }

        $partyTransactions = $partyTransactions->sortBy('created_at')->values();
        $totalPartyInflow = (float) $partyTransactions->sum('inflow');
        $totalPartyOutflow = (float) $partyTransactions->sum('outflow');
        $netPartyMovement = $totalPartyInflow - $totalPartyOutflow;

        return view('day_closings.show', compact(
            'dayClosing',
            'morningShift',
            'eveningShift',
            'transactions',
            'accountSummaries',
            'totalDigitalIn',
            'totalCombinedInflow',
            'partyTransactions',
            'totalPartyInflow',
            'totalPartyOutflow',
            'netPartyMovement'
        ));
    }

    public function reopen(DayClosing $dayClosing)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Only Owner can reopen finalized day closings.');
        }

        $dayClosing->status = 'open';
        $dayClosing->save();

        ActivityLog::log(
            'Reopened',
            'Day Closing',
            "Reopened finalized Day Closing for date " . $dayClosing->date->format('d M, Y')
        );

        return back()->with('success', 'Day closing reopened for review.');
    }

    public function destroy(DayClosing $dayClosing)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Only Owner can delete day closing records.');
        }

        $dateStr = $dayClosing->date->format('d M, Y');
        $dayClosing->delete();

        ActivityLog::log(
            'Deleted',
            'Day Closing',
            "Deleted Day Closing record for date {$dateStr}"
        );

        return redirect()->route('day-closings.index')->with('success', 'Day closing record deleted.');
    }
}
