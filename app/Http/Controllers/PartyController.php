<?php

namespace App\Http\Controllers;

use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartyController extends Controller
{
    public function index(Request $request)
    {
        $query = Party::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $mode = $request->input('mode', $request->input('payment_mode'));
        if ($mode === 'credit') {
            $query->where('is_credit', true);
        } elseif ($mode === 'cash') {
            $query->where('is_cash', true);
        } elseif ($mode === 'both') {
            $query->where('is_credit', true)->where('is_cash', true);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'name_asc');
        if ($sort === 'latest') {
            $query->latest();
        } elseif ($sort === 'name_desc') {
            $query->orderBy('name', 'desc')->orderBy('id', 'asc');
        } else {
            // Default: Alphabetical order (A–Z)
            $query->orderBy('name', 'asc')->orderBy('id', 'asc');
        }

        $parties = $query->paginate(15)->withQueryString();

        // Summary metrics
        $totalParties = Party::count();
        $totalSuppliers = Party::where('type', 'supplier')->count();
        $totalTraders = Party::where('type', 'trader')->count();
        $totalStaff = Party::where('type', 'staff')->count();
        $totalCustomers = Party::where('type', 'customer')->count();
        $totalCredit = Party::where('is_credit', true)->count();
        $totalCash = Party::where('is_cash', true)->count();

        return view('parties.index', compact(
            'parties',
            'totalParties',
            'totalSuppliers',
            'totalTraders',
            'totalStaff',
            'totalCustomers',
            'totalCredit',
            'totalCash'
        ));
    }

    public function checkDuplicate(Request $request)
    {
        $name = trim($request->input('name', ''));
        $excludeId = $request->input('exclude_id');

        if (mb_strlen($name) < 1) {
            return response()->json([
                'exact_match' => false,
                'exact_party' => null,
                'suggestions' => [],
            ]);
        }

        $query = Party::query();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Get matching parties (contains query string)
        $matches = (clone $query)->where('name', 'like', "%{$name}%")
            ->orderByRaw("CASE WHEN LOWER(name) = ? THEN 1 WHEN name LIKE ? THEN 2 ELSE 3 END", [
                strtolower($name),
                "{$name}%"
            ])
            ->take(6)
            ->get(['id', 'name', 'type', 'is_credit', 'is_cash', 'phone', 'current_balance']);

        // Check if there is an exact match (case-insensitive)
        $exactMatch = $matches->first(function ($p) use ($name) {
            return strcasecmp(trim($p->name), $name) === 0;
        });

        if (!$exactMatch) {
            $exactMatch = (clone $query)->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])->first(['id', 'name', 'type', 'is_credit', 'is_cash', 'phone', 'current_balance']);
        }

        return response()->json([
            'exact_match' => (bool) $exactMatch,
            'exact_party' => $exactMatch ? [
                'id' => $exactMatch->id,
                'name' => $exactMatch->name,
                'type' => ucfirst($exactMatch->type),
                'phone' => $exactMatch->phone ?: 'N/A',
                'balance' => number_format($exactMatch->current_balance, 2),
                'ledger_url' => route('ledgers.party', $exactMatch->id),
            ] : null,
            'suggestions' => $matches->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'type' => ucfirst($p->type),
                    'phone' => $p->phone ?: 'N/A',
                    'balance' => number_format($p->current_balance, 2),
                    'ledger_url' => route('ledgers.party', $p->id),
                ];
            })->values(),
        ]);
    }

    public function create()
    {
        return view('parties.create');
    }

    public function store(Request $request)
    {
        if ($request->has('opening_balance')) {
            $val = $request->input('opening_balance');
            $request->merge(['opening_balance' => is_string($val) ? str_replace(',', '', $val) : $val]);
        }

        if ($request->has('name')) {
            $request->merge(['name' => trim($request->input('name'))]);
        }

        if ($request->has('dealing_mode_submitted') && !$request->boolean('is_credit') && !$request->boolean('is_cash')) {
            return back()->withErrors(['dealing_mode' => 'Please select at least one dealing mode (Credit or Cash).'])->withInput();
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('parties', 'name'),
            ],
            'type' => ['required', Rule::in(['supplier', 'trader', 'staff', 'customer'])],
            'is_credit' => ['nullable', 'boolean'],
            'is_cash' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'opening_balance' => ['nullable', 'numeric'],
        ], [
            'name.unique' => 'A party with this name is already registered. Duplicate party registration is not allowed.',
        ]);

        if ($request->has('dealing_mode_submitted') || $request->has('is_credit') || $request->has('is_cash')) {
            $validated['is_credit'] = $request->boolean('is_credit');
            $validated['is_cash'] = $request->boolean('is_cash');
        } else {
            $validated['is_credit'] = true;
            $validated['is_cash'] = false;
        }

        $opening = $validated['opening_balance'] ?? 0;
        $validated['opening_balance'] = $opening;
        $validated['current_balance'] = $opening;

        $party = Party::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Party registered successfully.',
                'party' => $party
            ], 201);
        }

        return redirect()->route('parties.index')->with('success', 'Party created successfully with initial opening balance.');
    }

    public function edit(Party $party)
    {
        return view('parties.edit', compact('party'));
    }

    public function update(Request $request, Party $party)
    {
        if ($request->has('name')) {
            $request->merge(['name' => trim($request->input('name'))]);
        }

        if ($request->has('dealing_mode_submitted') && !$request->boolean('is_credit') && !$request->boolean('is_cash')) {
            return back()->withErrors(['dealing_mode' => 'Please select at least one dealing mode (Credit or Cash).'])->withInput();
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('parties', 'name')->ignore($party->id),
            ],
            'type' => ['required', Rule::in(['supplier', 'trader', 'staff', 'customer'])],
            'is_credit' => ['nullable', 'boolean'],
            'is_cash' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ], [
            'name.unique' => 'A party with this name is already registered.',
        ]);

        if ($request->has('dealing_mode_submitted') || $request->has('is_credit') || $request->has('is_cash')) {
            $validated['is_credit'] = $request->boolean('is_credit');
            $validated['is_cash'] = $request->boolean('is_cash');
        }

        $party->update($validated);

        return redirect()->route('parties.index')->with('success', 'Party details updated successfully.');
    }

    public function destroy(Party $party)
    {
        if (abs((float) $party->current_balance) > 0.001) {
            $formattedBal = number_format(abs($party->current_balance), 2);
            $nature = $party->current_balance > 0 ? "positive balance of Rs. {$formattedBal}" : "negative balance of Rs. -{$formattedBal}";
            return redirect()->route('parties.index')->with('error', "Party '{$party->name}' cannot be deleted because they have an active ledger balance ({$nature}). The ledger balance must be cleared to Rs. 0.00 before deletion.");
        }

        $partyName = $party->name;
        $partyType = ucfirst($party->type);
        $party->delete();

        \App\Models\ActivityLog::log('Deleted', 'Party', "Deleted party '{$partyName}' ({$partyType}) with zero ledger balance");

        return redirect()->route('parties.index')->with('success', "Party '{$partyName}' deleted successfully.");
    }
}
