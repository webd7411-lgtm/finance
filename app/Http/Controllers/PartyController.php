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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $parties = $query->latest()->paginate(15)->withQueryString();

        // Summary metrics
        $totalParties = Party::count();
        $totalSuppliers = Party::where('type', 'supplier')->count();
        $totalTraders = Party::where('type', 'trader')->count();
        $totalStaff = Party::where('type', 'staff')->count();
        $totalCustomers = Party::where('type', 'customer')->count();

        return view('parties.index', compact(
            'parties',
            'totalParties',
            'totalSuppliers',
            'totalTraders',
            'totalStaff',
            'totalCustomers'
        ));
    }

    public function create()
    {
        return view('parties.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['supplier', 'trader', 'staff', 'customer'])],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'opening_balance' => ['nullable', 'numeric'],
        ]);

        $opening = $validated['opening_balance'] ?? 0;
        $validated['opening_balance'] = $opening;
        $validated['current_balance'] = $opening;

        Party::create($validated);

        return redirect()->route('parties.index')->with('success', 'Party created successfully with initial opening balance.');
    }

    public function edit(Party $party)
    {
        return view('parties.edit', compact('party'));
    }

    public function update(Request $request, Party $party)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['supplier', 'trader', 'staff', 'customer'])],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'opening_balance' => ['nullable', 'numeric'],
        ]);

        // If current balance was same as opening balance, adjust it accordingly
        if ($party->current_balance == $party->opening_balance) {
            $party->current_balance = $validated['opening_balance'] ?? 0;
        }

        $party->update($validated);

        return redirect()->route('parties.index')->with('success', 'Party details updated successfully.');
    }

    public function destroy(Party $party)
    {
        $party->delete();
        return redirect()->route('parties.index')->with('success', 'Party deleted successfully.');
    }
}
