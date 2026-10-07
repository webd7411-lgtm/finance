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

        // ----------------------------------------------------
        // 1. ALL-ACCOUNTS OPENING BALANCES
        // ----------------------------------------------------
        if ($previousClosing) {
            $cashOpening = (float) $previousClosing->closing_cash;
        } else {
            $cashOpening = (float) \App\Models\Account::where('type', 'cash')->sum('opening_balance');
        }

        // Bank and JazzCash Opening: Base opening balance + all transactions in prior to selected date - all transactions out prior to selected date
        $bankOpening = (float) \App\Models\Account::where('type', 'bank')->sum('opening_balance')
            + (float) Transaction::whereHas('account', fn($q) => $q->where('type', 'bank'))->whereDate('date', '<', $selectedDate)->where('type', 'payment_in')->sum('amount')
            - (float) Transaction::whereHas('account', fn($q) => $q->where('type', 'bank'))->whereDate('date', '<', $selectedDate)->where('type', 'payment_out')->sum('amount');

        $jazzcashOpening = (float) \App\Models\Account::where('type', 'jazzcash')->sum('opening_balance')
            + (float) Transaction::whereHas('account', fn($q) => $q->where('type', 'jazzcash'))->whereDate('date', '<', $selectedDate)->where('type', 'payment_in')->sum('amount')
            - (float) Transaction::whereHas('account', fn($q) => $q->where('type', 'jazzcash'))->whereDate('date', '<', $selectedDate)->where('type', 'payment_out')->sum('amount');

        $totalOpeningAllAccounts = $cashOpening + $bankOpening + $jazzcashOpening;
        $autoOpeningCash = $cashOpening; // For backwards-compatibility in form post

        // ----------------------------------------------------
        // 2. ALL-ACCOUNTS INFLOWS (+)
        // ----------------------------------------------------
        // Shift Counted Cash (Morning + Evening)
        $morningCountedCash = $morningShift ? (float)$morningShift->total_counted_cash : 0.00;
        $eveningCountedCash = $eveningShift ? (float)$eveningShift->total_counted_cash : 0.00;
        $shiftCashIn = $morningCountedCash + $eveningCountedCash;

        // Direct Cash In
        $directCashIn = (float) Transaction::whereDate('date', $selectedDate)
                                           ->whereNull('shift_closing_id')
                                           ->where('type', 'payment_in')
                                           ->where(function($q) {
                                               $q->whereNull('account_id')
                                                 ->orWhereHas('account', fn($acc) => $acc->where('type', 'cash'));
                                           })
                                           ->sum('amount');
        $cashIn = $shiftCashIn + $directCashIn;
        $totalCashIn = $cashIn;

        // Shift Digital Collections
        $morningBankIn = $morningShift ? (float)$morningShift->bank_amount : 0.00;
        $eveningBankIn = $eveningShift ? (float)$eveningShift->bank_amount : 0.00;
        $directBankIn = (float) Transaction::whereDate('date', $selectedDate)
                                           ->whereNull('shift_closing_id')
                                           ->where('type', 'payment_in')
                                           ->whereHas('account', fn($q) => $q->where('type', 'bank'))
                                           ->sum('amount');
        $bankIn = $morningBankIn + $eveningBankIn + $directBankIn;

        $morningJazzIn = $morningShift ? (float)$morningShift->jazzcash_amount : 0.00;
        $eveningJazzIn = $eveningShift ? (float)$eveningShift->jazzcash_amount : 0.00;
        $directJazzIn = (float) Transaction::whereDate('date', $selectedDate)
                                           ->whereNull('shift_closing_id')
                                           ->where('type', 'payment_in')
                                           ->whereHas('account', fn($q) => $q->where('type', 'jazzcash'))
                                           ->sum('amount');
        $jazzcashIn = $morningJazzIn + $eveningJazzIn + $directJazzIn;

        $morningDigitalIn = $morningBankIn + $morningJazzIn;
        $eveningDigitalIn = $eveningBankIn + $eveningJazzIn;
        $totalDigitalIn = $bankIn + $jazzcashIn;
        $totalInAllAccounts = $cashIn + $bankIn + $jazzcashIn;
        $totalCombinedInflow = $totalInAllAccounts;

        // ----------------------------------------------------
        // 3. ALL-ACCOUNTS OUTFLOWS (-)
        // ----------------------------------------------------
        $shiftExpenses = ($morningShift ? (float)$morningShift->expenses_amount : 0) +
                         ($eveningShift ? (float)$eveningShift->expenses_amount : 0);

        $shiftPartyPayments = (float) ShiftClosingPartyPayment::whereHas('shiftClosing', fn ($query) => $query->whereDate('date', $selectedDate))
            ->sum('amount');

        // Direct cash payment vouchers from register (outside shift closings)
        $directCashOut = (float) Transaction::whereDate('date', $selectedDate)
                                            ->whereNull('shift_closing_id')
                                            ->where('type', 'payment_out')
                                            ->where(function($q) {
                                                $q->whereNull('account_id')
                                                  ->orWhereHas('account', fn($acc) => $acc->where('type', 'cash'));
                                            })
                                            ->sum('amount');

        // Physical cash disbursements from cash register (shift expenses & shift party payments
        // are already deducted from the cashiers' physical counted drawer cash at shift end)
        $cashOut = $directCashOut;
        $directPaymentsOut = $directCashOut;
        $totalPaymentsOut = $directCashOut;

        $bankOut = (float) Transaction::whereDate('date', $selectedDate)
                                      ->whereNull('shift_closing_id')
                                      ->where('type', 'payment_out')
                                      ->whereHas('account', fn($q) => $q->where('type', 'bank'))
                                      ->sum('amount');

        $jazzcashOut = (float) Transaction::whereDate('date', $selectedDate)
                                          ->whereNull('shift_closing_id')
                                          ->where('type', 'payment_out')
                                          ->whereHas('account', fn($q) => $q->where('type', 'jazzcash'))
                                          ->sum('amount');

        $totalOutAllAccounts = $cashOut + $bankOut + $jazzcashOut;

        // ----------------------------------------------------
        // 4. ALL-ACCOUNTS CLOSING BALANCES (=)
        // ----------------------------------------------------
        $cashClosing = $cashOpening + $cashIn - $cashOut;
        $bankClosing = $bankOpening + $bankIn - $bankOut;
        $jazzcashClosing = $jazzcashOpening + $jazzcashIn - $jazzcashOut;
        $totalClosingAllAccounts = $cashClosing + $bankClosing + $jazzcashClosing;
        $calculatedClosingCash = $cashClosing; // For DayClosing table

        // Cumulative Differences from shifts
        $totalDifference = ($morningShift ? (float)$morningShift->difference : 0) +
                           ($eveningShift ? (float)$eveningShift->difference : 0);

        // ----------------------------------------------------
        // BANK & DIGITAL ACCOUNTS POSITION (Today's Activity)
        // ----------------------------------------------------
        $allAccounts = \App\Models\Account::orderBy('type')->orderBy('name')->get();

        $accountSummaries = $allAccounts->map(function ($account) use ($selectedDate, $cashOpening, $cashClosing) {
            $inflow = (float) Transaction::whereDate('date', $selectedDate)
                                         ->where('account_id', $account->id)
                                         ->where('type', 'payment_in')
                                         ->sum('amount');

            $outflow = (float) Transaction::whereDate('date', $selectedDate)
                                          ->where('account_id', $account->id)
                                          ->where('type', 'payment_out')
                                          ->sum('amount');

            if ($account->type === 'cash') {
                $openingAtDate = $cashOpening;
                $balanceAtDate = $cashClosing;
            } else {
                $priorIn = (float) Transaction::where('account_id', $account->id)->whereDate('date', '<', $selectedDate)->where('type', 'payment_in')->sum('amount');
                $priorOut = (float) Transaction::where('account_id', $account->id)->whereDate('date', '<', $selectedDate)->where('type', 'payment_out')->sum('amount');
                $openingAtDate = (float) $account->opening_balance + $priorIn - $priorOut;
                $balanceAtDate = $openingAtDate + $inflow - $outflow;
            }

            return (object) [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'account_number' => $account->account_number,
                'opening_balance' => $openingAtDate,
                'current_balance' => $balanceAtDate,
                'inflow' => $inflow,
                'outflow' => $outflow,
                'net' => $inflow - $outflow,
            ];
        });

        // ----------------------------------------------------
        // 5. DAILY TRANSACTIONS REGISTER (All Payments In & Out)
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
                'party_name' => $sp->party->name ?? 'Direct Payee',
                'party_type' => $sp->party->type ?? 'Party',
                'party_phone' => $sp->party->phone ?? null,
                'type' => 'payment_out',
                'shift_name' => $shiftLabel,
                'channel' => 'Cash Drawer',
                'channel_type' => 'cash',
                'details' => $sp->details ?? 'Shift Cash Payout',
                'inflow' => 0.00,
                'outflow' => (float) $sp->amount,
                'amount' => (float) $sp->amount,
                'created_at' => $sp->created_at,
            ]);
        }

        // 2. Shift Sale Returns (Refund Outflow from Cash Drawer)
        $shiftsWithReturns = ShiftClosing::whereDate('date', $selectedDate)
            ->where('returns_amount', '>', 0)
            ->get();

        foreach ($shiftsWithReturns as $sr) {
            $shiftLabel = $sr->shift_type === 'morning' ? 'Morning Shift' : 'Evening Shift';
            $retCount = $sr->total_return_invoices > 0 ? " ({$sr->total_return_invoices} bills)" : "";
            $retDetails = ($sr->return_invoice_start && $sr->return_invoice_end)
                ? "Invoices #{$sr->return_invoice_start} - #{$sr->return_invoice_end}{$retCount}"
                : ($sr->return_invoice_number ? "Invoice #{$sr->return_invoice_number}" : "Sales Return Refund");

            $partyTransactions->push((object)[
                'id' => 'return-shift-' . $sr->id,
                'party_name' => 'Sales Return / Customer Refund',
                'party_type' => 'Sale Return',
                'party_phone' => null,
                'type' => 'payment_out',
                'shift_name' => $shiftLabel,
                'channel' => 'Cash Drawer',
                'channel_type' => 'cash',
                'details' => $retDetails,
                'inflow' => 0.00,
                'outflow' => (float) $sr->returns_amount,
                'amount' => (float) $sr->returns_amount,
                'created_at' => $sr->created_at,
            ]);
        }

        // 3. All Direct Transactions (Payments In & Out across Cash, Bank, JazzCash)
        $directPartyTxns = Transaction::whereDate('date', $selectedDate)
            ->whereNull('shift_closing_id')
            ->whereIn('type', ['payment_in', 'payment_out'])
            ->with(['party', 'account', 'category'])
            ->get();

        foreach ($directPartyTxns as $tx) {
            $isIn = $tx->type === 'payment_in';
            $partyTransactions->push((object)[
                'id' => 'tx-' . $tx->id,
                'party_name' => $tx->party->name ?? ($tx->category->name ?? 'General Transaction'),
                'party_type' => $tx->party->type ?? 'Direct Voucher',
                'party_phone' => $tx->party->phone ?? null,
                'type' => $tx->type,
                'shift_name' => null,
                'channel' => $tx->account->name ?? 'Cash Drawer',
                'channel_type' => $tx->account->type ?? 'cash',
                'details' => $tx->description ?? ($tx->bill_no ? 'Voucher #' . $tx->bill_no : ($tx->category->name ?? 'Direct Transaction')),
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
            'history',
            'cashOpening',
            'bankOpening',
            'jazzcashOpening',
            'totalOpeningAllAccounts',
            'cashIn',
            'bankIn',
            'jazzcashIn',
            'totalInAllAccounts',
            'cashOut',
            'bankOut',
            'jazzcashOut',
            'totalOutAllAccounts',
            'cashClosing',
            'bankClosing',
            'jazzcashClosing',
            'totalClosingAllAccounts'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'unique:day_closings,date'],
            'opening_cash' => ['nullable', 'numeric'],
            'total_cash_in' => ['nullable', 'numeric'],
            'total_payments_out' => ['nullable', 'numeric'],
            'closing_cash' => ['nullable', 'numeric'],
            'total_difference' => ['nullable', 'numeric'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $selectedDate = $validated['date'];

        // Automated Opening Cash: Previous Day's Closing Cash or initial Cash Account opening balance
        $previousClosing = DayClosing::where('date', '<', $selectedDate)
                                     ->where('status', 'closed')
                                     ->orderBy('date', 'desc')
                                     ->first();

        if ($previousClosing) {
            $openingCash = (float) $previousClosing->closing_cash;
        } else {
            $openingCash = (float) \App\Models\Account::where('type', 'cash')->sum('opening_balance');
        }

        // Bank and JazzCash Opening Balances
        $bankOpening = (float) \App\Models\Account::where('type', 'bank')->sum('opening_balance')
            + (float) Transaction::whereHas('account', fn($q) => $q->where('type', 'bank'))->whereDate('date', '<', $selectedDate)->where('type', 'payment_in')->sum('amount')
            - (float) Transaction::whereHas('account', fn($q) => $q->where('type', 'bank'))->whereDate('date', '<', $selectedDate)->where('type', 'payment_out')->sum('amount');

        $jazzcashOpening = (float) \App\Models\Account::where('type', 'jazzcash')->sum('opening_balance')
            + (float) Transaction::whereHas('account', fn($q) => $q->where('type', 'jazzcash'))->whereDate('date', '<', $selectedDate)->where('type', 'payment_in')->sum('amount')
            - (float) Transaction::whereHas('account', fn($q) => $q->where('type', 'jazzcash'))->whereDate('date', '<', $selectedDate)->where('type', 'payment_out')->sum('amount');

        $totalOpeningAllAccounts = $openingCash + $bankOpening + $jazzcashOpening;

        // Shift Counted Cash (Morning + Evening)
        $morningShift = ShiftClosing::whereDate('date', $selectedDate)->where('shift_type', 'morning')->first();
        $eveningShift = ShiftClosing::whereDate('date', $selectedDate)->where('shift_type', 'evening')->first();
        $morningCountedCash = $morningShift ? (float)$morningShift->total_counted_cash : 0.00;
        $eveningCountedCash = $eveningShift ? (float)$eveningShift->total_counted_cash : 0.00;
        $shiftCashIn = $morningCountedCash + $eveningCountedCash;

        $directCashIn = (float) Transaction::whereDate('date', $selectedDate)
                                           ->whereNull('shift_closing_id')
                                           ->where('type', 'payment_in')
                                           ->where(function($q) {
                                               $q->whereNull('account_id')
                                                 ->orWhereHas('account', fn($acc) => $acc->where('type', 'cash'));
                                           })
                                           ->sum('amount');
        $totalCashIn = $shiftCashIn + $directCashIn;

        // Bank & JazzCash Inflows
        $morningBankIn = $morningShift ? (float)$morningShift->bank_amount : 0.00;
        $eveningBankIn = $eveningShift ? (float)$eveningShift->bank_amount : 0.00;
        $directBankIn = (float) Transaction::whereDate('date', $selectedDate)
                                           ->whereNull('shift_closing_id')
                                           ->where('type', 'payment_in')
                                           ->whereHas('account', fn($q) => $q->where('type', 'bank'))
                                           ->sum('amount');
        $bankIn = $morningBankIn + $eveningBankIn + $directBankIn;

        $morningJazzIn = $morningShift ? (float)$morningShift->jazzcash_amount : 0.00;
        $eveningJazzIn = $eveningShift ? (float)$eveningShift->jazzcash_amount : 0.00;
        $directJazzIn = (float) Transaction::whereDate('date', $selectedDate)
                                           ->whereNull('shift_closing_id')
                                           ->where('type', 'payment_in')
                                           ->whereHas('account', fn($q) => $q->where('type', 'jazzcash'))
                                           ->sum('amount');
        $jazzcashIn = $morningJazzIn + $eveningJazzIn + $directJazzIn;
        $totalInAllAccounts = $totalCashIn + $bankIn + $jazzcashIn;

        // Outflows
        $directCashOut = (float) Transaction::whereDate('date', $selectedDate)
                                            ->whereNull('shift_closing_id')
                                            ->where('type', 'payment_out')
                                            ->where(function($q) {
                                                $q->whereNull('account_id')
                                                  ->orWhereHas('account', fn($acc) => $acc->where('type', 'cash'));
                                            })
                                            ->sum('amount');
        $totalPaymentsOut = $directCashOut;

        $bankOut = (float) Transaction::whereDate('date', $selectedDate)
                                      ->whereNull('shift_closing_id')
                                      ->where('type', 'payment_out')
                                      ->whereHas('account', fn($q) => $q->where('type', 'bank'))
                                      ->sum('amount');

        $jazzcashOut = (float) Transaction::whereDate('date', $selectedDate)
                                          ->whereNull('shift_closing_id')
                                          ->where('type', 'payment_out')
                                          ->whereHas('account', fn($q) => $q->where('type', 'jazzcash'))
                                          ->sum('amount');
        $totalOutAllAccounts = $totalPaymentsOut + $bankOut + $jazzcashOut;

        $closingCash = $openingCash + $totalCashIn - $totalPaymentsOut;
        $bankClosing = $bankOpening + $bankIn - $bankOut;
        $jazzcashClosing = $jazzcashOpening + $jazzcashIn - $jazzcashOut;
        $totalClosingAllAccounts = $closingCash + $bankClosing + $jazzcashClosing;

        $totalDiff = ($morningShift ? (float)$morningShift->difference : 0) +
                     ($eveningShift ? (float)$eveningShift->difference : 0);

        $validated['opening_cash'] = $openingCash;
        $validated['bank_opening'] = $bankOpening;
        $validated['jazzcash_opening'] = $jazzcashOpening;
        $validated['total_opening_all_accounts'] = $totalOpeningAllAccounts;

        $validated['total_cash_in'] = $totalCashIn;
        $validated['bank_in'] = $bankIn;
        $validated['jazzcash_in'] = $jazzcashIn;
        $validated['total_in_all_accounts'] = $totalInAllAccounts;

        $validated['total_payments_out'] = $totalPaymentsOut;
        $validated['bank_out'] = $bankOut;
        $validated['jazzcash_out'] = $jazzcashOut;
        $validated['total_out_all_accounts'] = $totalOutAllAccounts;

        $validated['closing_cash'] = $closingCash;
        $validated['bank_closing'] = $bankClosing;
        $validated['jazzcash_closing'] = $jazzcashClosing;
        $validated['total_closing_all_accounts'] = $totalClosingAllAccounts;

        $validated['total_difference'] = $totalDiff;
        $validated['closed_by'] = auth()->id();
        $validated['status'] = 'closed';

        $dayClosing = DayClosing::create($validated);

        $cashAccount = \App\Models\Account::where('type', 'cash')->first();
        if ($cashAccount) {
            $cashAccount->current_balance = $dayClosing->closing_cash;
            $cashAccount->save();
        }

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
        $accountSummaries = $allAccounts->map(function ($account) use ($date, $dayClosing) {
            $inflow = (float) Transaction::whereDate('date', $date)
                                         ->where('account_id', $account->id)
                                         ->where('type', 'payment_in')
                                         ->sum('amount');

            $outflow = (float) Transaction::whereDate('date', $date)
                                          ->where('account_id', $account->id)
                                          ->where('type', 'payment_out')
                                          ->sum('amount');

            if ($account->type === 'cash') {
                $openingAtDate = (float) $dayClosing->opening_cash;
                $balanceAtDate = (float) $dayClosing->closing_cash;
            } else {
                $priorIn = (float) Transaction::where('account_id', $account->id)->whereDate('date', '<', $date)->where('type', 'payment_in')->sum('amount');
                $priorOut = (float) Transaction::where('account_id', $account->id)->whereDate('date', '<', $date)->where('type', 'payment_out')->sum('amount');
                $openingAtDate = (float) $account->opening_balance + $priorIn - $priorOut;
                $balanceAtDate = $openingAtDate + $inflow - $outflow;
            }

            return (object) [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'account_number' => $account->account_number,
                'opening_balance' => $openingAtDate,
                'current_balance' => $balanceAtDate,
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
                'channel_type' => 'cash',
                'details' => $sp->details ?? 'Shift Cash Payout',
                'inflow' => 0.00,
                'outflow' => (float) $sp->amount,
                'amount' => (float) $sp->amount,
                'created_at' => $sp->created_at,
            ]);
        }

        // Shift Sale Returns (Refund Outflow from Cash Drawer)
        $shiftsWithReturns = ShiftClosing::whereDate('date', $date)
            ->where('returns_amount', '>', 0)
            ->get();

        foreach ($shiftsWithReturns as $sr) {
            $shiftLabel = $sr->shift_type === 'morning' ? 'Morning Shift' : 'Evening Shift';
            $retCount = $sr->total_return_invoices > 0 ? " ({$sr->total_return_invoices} bills)" : "";
            $retDetails = ($sr->return_invoice_start && $sr->return_invoice_end)
                ? "Invoices #{$sr->return_invoice_start} - #{$sr->return_invoice_end}{$retCount}"
                : ($sr->return_invoice_number ? "Invoice #{$sr->return_invoice_number}" : "Sales Return Refund");

            $partyTransactions->push((object)[
                'id' => 'return-shift-' . $sr->id,
                'party_name' => 'Sales Return / Customer Refund',
                'party_type' => 'Sale Return',
                'party_phone' => null,
                'type' => 'payment_out',
                'shift_name' => $shiftLabel,
                'channel' => 'Cash Drawer',
                'channel_type' => 'cash',
                'details' => $retDetails,
                'inflow' => 0.00,
                'outflow' => (float) $sr->returns_amount,
                'amount' => (float) $sr->returns_amount,
                'created_at' => $sr->created_at,
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

        $cashOpening = (float) $dayClosing->opening_cash;
        $bankOpening = (float) ($dayClosing->bank_opening > 0 ? $dayClosing->bank_opening : $accountSummaries->where('type', 'bank')->sum('opening_balance'));
        $jazzcashOpening = (float) ($dayClosing->jazzcash_opening > 0 ? $dayClosing->jazzcash_opening : $accountSummaries->where('type', 'jazzcash')->sum('opening_balance'));
        $totalOpeningAllAccounts = (float) ($dayClosing->total_opening_all_accounts > 0 ? $dayClosing->total_opening_all_accounts : ($cashOpening + $bankOpening + $jazzcashOpening));

        $cashIn = (float) $dayClosing->total_cash_in;
        $bankIn = (float) ($dayClosing->bank_in > 0 ? $dayClosing->bank_in : $accountSummaries->where('type', 'bank')->sum('inflow'));
        $jazzcashIn = (float) ($dayClosing->jazzcash_in > 0 ? $dayClosing->jazzcash_in : $accountSummaries->where('type', 'jazzcash')->sum('inflow'));
        $totalInAllAccounts = (float) ($dayClosing->total_in_all_accounts > 0 ? $dayClosing->total_in_all_accounts : ($cashIn + $bankIn + $jazzcashIn));

        $cashOut = (float) $dayClosing->total_payments_out;
        $bankOut = (float) ($dayClosing->bank_out > 0 ? $dayClosing->bank_out : $accountSummaries->where('type', 'bank')->sum('outflow'));
        $jazzcashOut = (float) ($dayClosing->jazzcash_out > 0 ? $dayClosing->jazzcash_out : $accountSummaries->where('type', 'jazzcash')->sum('outflow'));
        $totalOutAllAccounts = (float) ($dayClosing->total_out_all_accounts > 0 ? $dayClosing->total_out_all_accounts : ($cashOut + $bankOut + $jazzcashOut));

        $cashClosing = (float) $dayClosing->closing_cash;
        $bankClosing = (float) ($dayClosing->bank_closing > 0 ? $dayClosing->bank_closing : $accountSummaries->where('type', 'bank')->sum('current_balance'));
        $jazzcashClosing = (float) ($dayClosing->jazzcash_closing > 0 ? $dayClosing->jazzcash_closing : $accountSummaries->where('type', 'jazzcash')->sum('current_balance'));
        $totalClosingAllAccounts = (float) ($dayClosing->total_closing_all_accounts > 0 ? $dayClosing->total_closing_all_accounts : ($cashClosing + $bankClosing + $jazzcashClosing));

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
            'netPartyMovement',
            'cashOpening',
            'bankOpening',
            'jazzcashOpening',
            'totalOpeningAllAccounts',
            'cashIn',
            'bankIn',
            'jazzcashIn',
            'totalInAllAccounts',
            'cashOut',
            'bankOut',
            'jazzcashOut',
            'totalOutAllAccounts',
            'cashClosing',
            'bankClosing',
            'jazzcashClosing',
            'totalClosingAllAccounts'
        ));
    }

    public function reopen(DayClosing $dayClosing)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Only Owner can reopen finalized day closings.');
        }

        $dayClosing->status = 'open';
        $dayClosing->save();

        $cashAccount = \App\Models\Account::where('type', 'cash')->first();
        if ($cashAccount) {
            $prevClosing = DayClosing::where('id', '!=', $dayClosing->id)->where('status', 'closed')->orderBy('date', 'desc')->first();
            $cashAccount->current_balance = $prevClosing ? $prevClosing->closing_cash : $cashAccount->opening_balance;
            $cashAccount->save();
        }

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
