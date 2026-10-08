<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::all();
        $totalBalance = $accounts->sum('current_balance');
        $cashBalance = $accounts->where('type', 'cash')->sum('current_balance');
        $jazzCashBalance = $accounts->where('type', 'jazzcash')->sum('current_balance');
        $bankBalance = $accounts->where('type', 'bank')->sum('current_balance');

        return view('accounts.index', compact(
            'accounts',
            'totalBalance',
            'cashBalance',
            'jazzCashBalance',
            'bankBalance'
        ));
    }

    public function create()
    {
        return view('accounts.create');
    }

    public function store(Request $request)
    {
        if ($request->has('opening_balance')) {
            $val = $request->input('opening_balance');
            $request->merge(['opening_balance' => is_string($val) ? str_replace(',', '', $val) : $val]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['jazzcash', 'bank'])],
            'account_number' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['nullable', 'numeric'],
        ], [
            'type.in' => 'Cash account is automatically managed by the system. Only Bank and Mobile Wallet accounts can be added.',
        ]);

        $opening = $validated['opening_balance'] ?? 0;
        $validated['opening_balance'] = $opening;
        $validated['current_balance'] = $opening;

        Account::create($validated);

        return redirect()->route('accounts.index')->with('success', 'Payment account created successfully.');
    }

    public function edit(Account $account)
    {
        return view('accounts.edit', compact('account'));
    }

    public function update(Request $request, Account $account)
    {
        if ($request->has('opening_balance')) {
            $val = $request->input('opening_balance');
            $request->merge(['opening_balance' => is_string($val) ? str_replace(',', '', $val) : $val]);
        }

        if ($account->type === 'cash') {
            $request->merge(['type' => 'cash']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', $account->type === 'cash' ? Rule::in(['cash']) : Rule::in(['jazzcash', 'bank'])],
            'account_number' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['nullable', 'numeric'],
        ], [
            'type.in' => 'Invalid account channel selected.',
        ]);

        if ($account->current_balance == $account->opening_balance) {
            $account->current_balance = $validated['opening_balance'] ?? 0;
        }

        $account->update($validated);

        return redirect()->route('accounts.index')->with('success', 'Account details updated successfully.');
    }

    public function destroy(Account $account)
    {
        if ($account->type === 'cash') {
            return redirect()->route('accounts.index')->with('error', 'The system Cash in Hand account is automatic and cannot be deleted.');
        }

        $account->delete();
        return redirect()->route('accounts.index')->with('success', 'Account deleted successfully.');
    }
}
