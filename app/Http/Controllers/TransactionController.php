<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Party;
use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['party', 'account', 'category', 'creator'])->latest('date')->latest('id');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('party_id')) {
            $query->where('party_id', $request->party_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $transactions = $query->paginate(15)->withQueryString();

        // Metrics for summary
        $totalIn = Transaction::where('type', 'payment_in')->sum('amount');
        $totalOut = Transaction::where('type', 'payment_out')->sum('amount');
        $netCashFlow = $totalIn - $totalOut;

        $parties = Party::orderBy('name')->get();
        $accounts = Account::orderBy('name')->get();
        $categories = ExpenseCategory::orderBy('name')->get();

        return view('transactions.index', compact(
            'transactions',
            'totalIn',
            'totalOut',
            'netCashFlow',
            'parties',
            'accounts',
            'categories'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', Rule::in(['payment_in', 'payment_out'])],
            'account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'party_id' => ['nullable', 'exists:parties,id'],
            'category_id' => ['nullable', 'exists:expense_categories,id'],
            'bill_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['created_by'] = auth()->id();

        DB::transaction(function () use ($validated) {
            $transaction = Transaction::create($validated);

            $account = Account::findOrFail($validated['account_id']);
            $amount = $validated['amount'];

            // Adjust Account Balance
            if ($validated['type'] === 'payment_in') {
                $account->current_balance += $amount;
            } else {
                $account->current_balance -= $amount;
            }
            $account->save();

            // Adjust Party Balance if party attached
            if (!empty($validated['party_id'])) {
                $party = Party::findOrFail($validated['party_id']);
                if ($validated['type'] === 'payment_in') {
                    // Wasooli reduces party receivables
                    $party->current_balance -= $amount;
                } else {
                    // Payment Out: for suppliers reduces payable; for staff advance adds to their balance
                    if ($party->type === 'staff') {
                        $party->current_balance += $amount; // staff owes more
                    } else {
                        $party->current_balance -= $amount; // supplier payable reduced
                    }
                }
                $party->save();
            }

            $mod = ($validated['type'] === 'payment_in') ? 'Payment In' : 'Payment Out';
            $partyName = !empty($validated['party_id']) ? Party::find($validated['party_id'])?->name : 'Direct Counter';
            ActivityLog::log(
                'Created',
                $mod,
                "Voucher #{$transaction->id}: {$mod} of Rs. " . number_format($amount, 2) . " ({$account->name}, Party: {$partyName})"
            );
        });

        $msg = ($validated['type'] === 'payment_in') 
            ? 'Payment In recorded and account balance credited successfully.'
            : 'Payment Out recorded and account balance debited successfully.';

        return redirect()->route('transactions.index')->with('success', $msg);
    }

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'from_account_id' => ['required', 'exists:accounts,id'],
            'to_account_id' => ['required', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'to_account_id.different' => 'Transfer From aur Transfer To accounts mukhtalif hone chahiye.',
        ]);

        $fromAccount = Account::findOrFail($validated['from_account_id']);
        $toAccount = Account::findOrFail($validated['to_account_id']);
        $amount = (float) $validated['amount'];
        $notes = $validated['description'] ?? '';
        $ref = $validated['reference'] ?? 'Transfer';

        DB::transaction(function () use ($validated, $fromAccount, $toAccount, $amount, $notes, $ref) {
            // 1. Payment Out from Source Account
            Transaction::create([
                'date' => $validated['date'],
                'type' => 'payment_out',
                'account_id' => $fromAccount->id,
                'party_id' => null,
                'category_id' => null,
                'amount' => $amount,
                'bill_no' => $ref,
                'description' => 'Transfer to ' . $toAccount->name . ($notes ? " ({$notes})" : ''),
                'created_by' => auth()->id(),
            ]);
            $fromAccount->current_balance -= $amount;
            $fromAccount->save();

            // 2. Payment In to Destination Account
            Transaction::create([
                'date' => $validated['date'],
                'type' => 'payment_in',
                'account_id' => $toAccount->id,
                'party_id' => null,
                'category_id' => null,
                'amount' => $amount,
                'bill_no' => $ref,
                'description' => 'Transfer from ' . $fromAccount->name . ($notes ? " ({$notes})" : ''),
                'created_by' => auth()->id(),
            ]);
            $toAccount->current_balance += $amount;
            $toAccount->save();

            // 3. Activity Log
            ActivityLog::log(
                'Transfer',
                'Funds Transfer',
                "Transferred Rs. " . number_format($amount, 2) . " from {$fromAccount->name} to {$toAccount->name}" . ($notes ? " ({$notes})" : '')
            );
        });

        return back()->with('success', "Rs. " . number_format($amount, 2) . " kamyabi se {$fromAccount->name} se {$toAccount->name} me transfer ho gaye hain.");
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['party', 'account', 'category', 'creator']);
        return view('transactions.show', compact('transaction'));
    }

    public function destroy(Transaction $transaction)
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isIncharge()) {
            abort(403, 'Only Owner or Incharge can delete financial transactions.');
        }

        DB::transaction(function () use ($transaction) {
            // Revert Account Balance
            $account = $transaction->account;
            if ($transaction->type === 'payment_in') {
                $account->current_balance -= $transaction->amount;
            } else {
                $account->current_balance += $transaction->amount;
            }
            $account->save();

            // Revert Party Balance
            if ($transaction->party) {
                $party = $transaction->party;
                if ($transaction->type === 'payment_in') {
                    $party->current_balance += $transaction->amount;
                } else {
                    if ($party->type === 'staff') {
                        $party->current_balance -= $transaction->amount;
                    } else {
                        $party->current_balance += $transaction->amount;
                    }
                }
                $party->save();
            }

            $txId = $transaction->id;
            $txAmount = $transaction->amount;
            $txType = ($transaction->type === 'payment_in') ? 'Payment In' : 'Payment Out';

            $transaction->delete();

            ActivityLog::log(
                'Deleted',
                $txType,
                "Deleted {$txType} voucher #{$txId} of Rs. " . number_format($txAmount, 2) . " and reversed account balances"
            );
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction deleted and balances reverted successfully.');
    }
}
