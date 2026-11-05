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
        $view = $request->get('view');
        $isTrashView = $view === 'trash';
        $isReturnsView = $view === 'returns';
        $activeCount = Transaction::where('type', '!=', 'purchase_return')->count();
        $returnsCount = Transaction::where('type', 'purchase_return')->count();
        $trashCount = Transaction::onlyTrashed()->count();

        if ($isTrashView) {
            $query = Transaction::onlyTrashed()->with(['party', 'account', 'category', 'creator', 'deletedBy', 'originalTransaction'])->latest('deleted_at');
        } elseif ($isReturnsView) {
            $query = Transaction::where('type', 'purchase_return')->with(['party', 'account', 'category', 'creator', 'originalTransaction'])->latest('date')->latest('id');
        } else {
            $query = Transaction::where('type', '!=', 'purchase_return')
                ->with(['party', 'account', 'category', 'creator', 'originalTransaction'])
                ->withSum('returns', 'amount')
                ->latest('date')->latest('id');
        }

        if (!$isReturnsView && $request->filled('type')) {
            if ($request->type === 'expense') {
                $query->where('type', 'payment_out')->whereNull('party_id');
            } else {
                $query->where('type', $request->type);
            }
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('party_id')) {
            $query->where('party_id', $request->party_id);
        }

        if ($request->filled('expense_category_id') && $request->filled('purchase_category_id')) {
            $query->whereIn('category_id', [$request->expense_category_id, $request->purchase_category_id]);
        } elseif ($request->filled('expense_category_id')) {
            $query->where('category_id', $request->expense_category_id);
        } elseif ($request->filled('purchase_category_id')) {
            $query->where('category_id', $request->purchase_category_id);
        } elseif ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->paginate(15)->withQueryString();

        // Metrics for summary
        $totalIn = Transaction::where('type', 'payment_in')->sum('amount');
        $totalOut = Transaction::where('type', 'payment_out')->sum('amount');
        $netCashFlow = $totalIn - $totalOut;

        $parties = Party::orderBy('name')->get();
        $accounts = Account::orderBy('name')->get();
        $categories = ExpenseCategory::orderBy('name')->get();
        $expenseCategories = ExpenseCategory::expense()->orderBy('name')->get();
        $purchaseCategories = ExpenseCategory::purchase()->orderBy('name')->get();

        return view('transactions.index', compact(
            'transactions',
            'totalIn',
            'totalOut',
            'netCashFlow',
            'parties',
            'accounts',
            'categories',
            'expenseCategories',
            'purchaseCategories',
            'isTrashView',
            'isReturnsView',
            'activeCount',
            'returnsCount',
            'trashCount'
        ));
    }

    public function store(Request $request)
    {
        if ($request->has('amount')) {
            $val = $request->input('amount');
            $request->merge(['amount' => is_string($val) ? str_replace(',', '', $val) : $val]);
        }

        $yesterday = now()->subDay()->toDateString();
        $today = now()->toDateString();

        $validated = $request->validate([
            'date' => ['required', 'date', "after_or_equal:{$yesterday}", "before_or_equal:{$today}"],
            'type' => ['required', Rule::in(['payment_in', 'payment_out', 'purchase_bill', 'purchase_return'])],
            'account_id' => [Rule::requiredIf(fn() => !in_array($request->type, ['purchase_bill', 'purchase_return'])), 'nullable', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'party_id' => [
                Rule::requiredIf(fn() => in_array($request->type, ['purchase_bill', 'purchase_return']) || ($request->type === 'payment_out' && empty($request->category_id))),
                'nullable',
                'exists:parties,id'
            ],
            'category_id' => [
                Rule::requiredIf(fn() => $request->type === 'payment_out' && empty($request->party_id)),
                'nullable',
                'exists:expense_categories,id'
            ],
            'bill_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'original_transaction_id' => ['nullable', 'exists:transactions,id'],
        ], [
            'date.after_or_equal' => 'Date cannot be earlier than yesterday.',
            'date.before_or_equal' => 'Date cannot be in the future.',
            'party_id.required' => 'Please select either a party or an expense category for this payment out.',
            'category_id.required' => 'Please select either an expense category or a party for this payment out.',
        ]);

        $validated['created_by'] = auth()->id();

        // Prevent Payment Out exceeding account balance
        if ($validated['type'] === 'payment_out' && !empty($validated['account_id'])) {
            $targetAccount = Account::findOrFail($validated['account_id']);
            if ($targetAccount->isCash()) {
                Account::syncCashAccountBalance();
                $targetAccount->refresh();
            }

            $currentBal = (float) $targetAccount->current_balance;
            $reqAmount = (float) $validated['amount'];

            if ($reqAmount > $currentBal) {
                return back()->withInput()->with('error', "Insufficient funds in '{$targetAccount->name}'. Available balance is Rs. " . number_format($currentBal, 2) . ", but you entered Rs. " . number_format($reqAmount, 2) . ". Payment Out cannot exceed available account balance.");
            }
        }

        // Enforce Purchase Return restrictions
        if ($validated['type'] === 'purchase_return') {
            if (!empty($request->original_transaction_id)) {
                $origTrx = Transaction::findOrFail($request->original_transaction_id);

                // Enforce vendor cannot be different
                if ($origTrx->party_id && (int) $validated['party_id'] !== (int) $origTrx->party_id) {
                    return back()->withInput()->with('error', "The return vendor must match the original purchase bill vendor ({$origTrx->party?->name}).");
                }

                // Enforce return amount cannot exceed remaining returnable amount of the original bill
                $alreadyReturned = (float) Transaction::where('original_transaction_id', $origTrx->id)->sum('amount');
                $remainingAllowed = max(0, (float) $origTrx->amount - $alreadyReturned);

                if ((float) $validated['amount'] > $remainingAllowed) {
                    return back()->withInput()->with('error', "Return amount (Rs. " . number_format($validated['amount'], 2) . ") exceeds the remaining returnable bill amount of Rs. " . number_format($remainingAllowed, 2) . " (Original Bill Total: Rs. " . number_format($origTrx->amount, 2) . ").");
                }

                $validated['original_transaction_id'] = $origTrx->id;
            }
        }

        try {
            DB::transaction(function () use ($validated) {
                $transaction = Transaction::create($validated);
                $amount = $validated['amount'];

                // Adjust Account Balance only for cash/bank payments
                if (!in_array($validated['type'], ['purchase_bill', 'purchase_return']) && !empty($validated['account_id'])) {
                    $account = Account::whereKey($validated['account_id'])->lockForUpdate()->firstOrFail();
                    if ($validated['type'] === 'payment_in') {
                        $account->current_balance += $amount;
                    } else {
                        if ((float) $amount > (float) $account->current_balance) {
                            throw new \Exception("Insufficient funds in '{$account->name}'. Available balance is Rs. " . number_format($account->current_balance, 2) . ".");
                        }
                        $account->current_balance -= $amount;
                    }
                    $account->save();
                }

            // Adjust Party Balance if party attached
            if (!empty($validated['party_id'])) {
                $party = Party::findOrFail($validated['party_id']);
                if ($validated['type'] === 'payment_in') {
                    // Receipt reduces customer receivable
                    $party->current_balance -= $amount;
                } elseif ($validated['type'] === 'purchase_bill') {
                    // Credit purchase increases supplier payable (we owe more)
                    $party->current_balance += $amount;
                } elseif ($validated['type'] === 'purchase_return') {
                    // Purchase return reduces supplier payable (we owe less, minus from ledger)
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

            $isExpense = empty($validated['party_id']) && !empty($validated['category_id']);
            $mod = match($validated['type']) {
                'payment_in' => 'Payment In',
                'purchase_bill' => 'Purchase Bill',
                'purchase_return' => 'Purchase Return',
                default => $isExpense ? 'Expense' : 'Payment Out',
            };
            $partyName = !empty($validated['party_id']) 
                ? (Party::find($validated['party_id'])?->name ?? 'Party') 
                : ($isExpense ? (ExpenseCategory::find($validated['category_id'])?->name ?? 'Operational Expense') : 'Direct Counter');
            $channelInfo = !empty($validated['account_id']) ? Account::find($validated['account_id'])?->name : ($validated['type'] === 'purchase_return' ? 'Credit Return (Party Ledger Deducted)' : 'Credit (No Cash)');
            $logMeta = $isExpense ? "Category: {$partyName}" : "Party: {$partyName}";
            if (!empty($validated['party_id']) && !empty($validated['category_id'])) {
                $catName = ExpenseCategory::find($validated['category_id'])?->name;
                if ($catName) $logMeta .= ", Category: {$catName}";
            }
            ActivityLog::log(
                'Created',
                $mod,
                "Voucher #{$transaction->id}: {$mod} of Rs. " . number_format($amount, 2) . " ({$channelInfo}, {$logMeta})"
            );
        });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        \App\Models\Account::syncCashAccountBalance();
        \App\Models\DayClosing::autoSyncDay($validated['date']);

        $isExpense = empty($validated['party_id']) && !empty($validated['category_id']);
        $msg = match($validated['type']) {
            'payment_in' => 'Payment In recorded and account balance credited successfully.',
            'purchase_bill' => 'Purchase Bill recorded and supplier ledger credited successfully.',
            'purchase_return' => 'Purchase Return recorded and supplier ledger deducted successfully.',
            default => $isExpense 
                ? 'Expense recorded and account balance debited successfully.' 
                : 'Payment Out recorded and account balance debited successfully.',
        };

        $redirectParams = $validated['type'] === 'purchase_return' ? ['view' => 'returns'] : [];
        return redirect()->route('transactions.index', $redirectParams)->with('success', $msg);
    }

    public function transfer(Request $request)
    {
        if ($request->has('amount')) {
            $val = $request->input('amount');
            $request->merge(['amount' => is_string($val) ? str_replace(',', '', $val) : $val]);
        }

        $yesterday = now()->subDay()->toDateString();
        $today = now()->toDateString();

        $validated = $request->validate([
            'date' => ['required', 'date', "after_or_equal:{$yesterday}", "before_or_equal:{$today}"],
            'from_account_id' => ['required', 'exists:accounts,id'],
            'to_account_id' => ['required', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'date.after_or_equal' => 'Transfer date cannot be earlier than yesterday.',
            'date.before_or_equal' => 'Transfer date cannot be in the future.',
            'to_account_id.different' => 'Transfer From and Transfer To accounts must be different.',
        ]);

        $fromAccount = Account::findOrFail($validated['from_account_id']);
        $toAccount = Account::findOrFail($validated['to_account_id']);
        $amount = (float) $validated['amount'];

        if ($fromAccount->isCash()) {
            Account::syncCashAccountBalance();
            $fromAccount->refresh();
        }

        $fromBal = (float) $fromAccount->current_balance;
        if ($amount > $fromBal) {
            return back()->withInput()->with('error', "Insufficient funds in source account '{$fromAccount->name}'. Available balance is Rs. " . number_format($fromBal, 2) . ", but transfer amount is Rs. " . number_format($amount, 2) . ". Transfer cannot exceed available account balance.");
        }

        $notes = $validated['description'] ?? '';
        $ref = $validated['reference'] ?? 'Transfer';

        try {
            DB::transaction(function () use ($validated, $fromAccount, $toAccount, $amount, $notes, $ref) {
                $lockedFrom = Account::whereKey($fromAccount->id)->lockForUpdate()->firstOrFail();
                if ($amount > (float) $lockedFrom->current_balance) {
                    throw new \Exception("Insufficient funds in source account '{$lockedFrom->name}'. Available balance is Rs. " . number_format($lockedFrom->current_balance, 2) . ".");
                }
                $lockedTo = Account::whereKey($toAccount->id)->lockForUpdate()->firstOrFail();

                // 1. Payment Out from Source Account
                Transaction::create([
                    'date' => $validated['date'],
                    'type' => 'payment_out',
                    'account_id' => $lockedFrom->id,
                    'party_id' => null,
                    'category_id' => null,
                    'amount' => $amount,
                    'bill_no' => $ref,
                    'description' => 'Transfer to ' . $lockedTo->name . ($notes ? " ({$notes})" : ''),
                    'created_by' => auth()->id(),
                ]);
                $lockedFrom->current_balance -= $amount;
                $lockedFrom->save();

                // 2. Payment In to Destination Account
                Transaction::create([
                    'date' => $validated['date'],
                    'type' => 'payment_in',
                    'account_id' => $lockedTo->id,
                    'party_id' => null,
                    'category_id' => null,
                    'amount' => $amount,
                    'bill_no' => $ref,
                    'description' => 'Transfer from ' . $lockedFrom->name . ($notes ? " ({$notes})" : ''),
                    'created_by' => auth()->id(),
                ]);
                $lockedTo->current_balance += $amount;
                $lockedTo->save();

                // 3. Activity Log
                ActivityLog::log(
                    'Transfer',
                    'Funds Transfer',
                    "Transferred Rs. " . number_format($amount, 2) . " from {$lockedFrom->name} to {$lockedTo->name}" . ($notes ? " ({$notes})" : '')
                );
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        \App\Models\Account::syncCashAccountBalance();
        \App\Models\DayClosing::autoSyncDay($validated['date']);

        return back()->with('success', "Rs. " . number_format($amount, 2) . " transferred successfully from {$fromAccount->name} to {$toAccount->name}.");
    }

    public function show($id)
    {
        $transaction = Transaction::withTrashed()->with(['party', 'account', 'category', 'creator', 'deletedBy'])->findOrFail($id);
        return view('transactions.show', compact('transaction'));
    }

    public function unlock(Transaction $transaction)
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isIncharge()) {
            abort(403, 'Unauthorized action.');
        }

        $transaction->status = 'submitted';
        $transaction->save();

        ActivityLog::log('Unlocked', 'Transaction', "Unlocked transaction voucher #{$transaction->id}");

        return back()->with('success', 'Transaction unlocked.');
    }

    public function lock(Transaction $transaction)
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isIncharge()) {
            abort(403, 'Unauthorized action.');
        }

        $transaction->status = 'locked';
        $transaction->save();

        ActivityLog::log('Locked', 'Transaction', "Locked transaction voucher #{$transaction->id}");

        return back()->with('success', 'Transaction locked.');
    }

    public function destroy(Transaction $transaction)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Only Owner can delete financial transactions.');
        }

        if ($transaction->status === 'locked') {
            return back()->with('error', 'This transaction is locked to prevent unauthorized deletion. Please unlock it before deleting.');
        }

        $txDate = $transaction->date;

        DB::transaction(function () use ($transaction) {
            // Revert Account Balance if it was a cash/bank transaction
            if (!in_array($transaction->type, ['purchase_bill', 'purchase_return']) && $transaction->account) {
                $account = $transaction->account;
                if ($transaction->type === 'payment_in') {
                    $account->current_balance -= $transaction->amount;
                } else {
                    $account->current_balance += $transaction->amount;
                }
                $account->save();
            }

            // Revert Party Balance
            if ($transaction->party) {
                $party = $transaction->party;
                if ($transaction->type === 'payment_in') {
                    $party->current_balance += $transaction->amount;
                } elseif ($transaction->type === 'purchase_bill') {
                    $party->current_balance -= $transaction->amount;
                } elseif ($transaction->type === 'purchase_return') {
                    // Reverting a return adds the amount back to supplier payable
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
            $txType = match($transaction->type) {
                'payment_in' => 'Payment In',
                'purchase_bill' => 'Purchase Bill',
                'purchase_return' => 'Purchase Return',
                default => 'Payment Out',
            };

            $transaction->deleted_by = auth()->id();
            $transaction->save();
            $transaction->delete();

            ActivityLog::log(
                'Deleted',
                $txType,
                "Deleted {$txType} voucher #{$txId} of Rs. " . number_format($txAmount, 2) . " and reversed balances"
            );
        });

        \App\Models\Account::syncCashAccountBalance();
        \App\Models\DayClosing::autoSyncDay($txDate);

        return redirect()->route('transactions.index')->with('success', "Transaction voucher #{$transaction->id} deleted and safely archived in Deleted Vouchers audit.");
    }
}
