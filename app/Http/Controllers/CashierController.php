<?php

namespace App\Http\Controllers;

use App\Models\Cashier;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CashierController extends Controller
{
    public function index(Request $request)
    {
        $query = Cashier::query()->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $cashiers = $query->paginate(15)->withQueryString();
        $totalCashiers = Cashier::count();
        $activeCashiers = Cashier::where('status', 'active')->count();
        $inactiveCashiers = Cashier::where('status', 'inactive')->count();

        return view('cashiers.index', compact(
            'cashiers',
            'totalCashiers',
            'activeCashiers',
            'inactiveCashiers'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:50', 'unique:cashiers,code'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if (empty($validated['code'])) {
            $nextId = (Cashier::max('id') ?? 0) + 1;
            $validated['code'] = 'CSH-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
        }

        $cashier = Cashier::create($validated);

        ActivityLog::log('Created', 'Cashier', "Added cashier {$cashier->name} ({$cashier->code})");

        return redirect()->route('cashiers.index')
            ->with('success', "Cashier '{$cashier->name}' added successfully.");
    }

    public function update(Request $request, Cashier $cashier)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('cashiers', 'code')->ignore($cashier->id)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $cashier->update($validated);

        ActivityLog::log('Updated', 'Cashier', "Updated cashier {$cashier->name}");

        return redirect()->route('cashiers.index')
            ->with('success', "Cashier '{$cashier->name}' updated successfully.");
    }

    public function toggleStatus(Cashier $cashier)
    {
        $cashier->status = $cashier->status === 'active' ? 'inactive' : 'active';
        $cashier->save();

        $state = ucfirst($cashier->status);
        ActivityLog::log('Updated', 'Cashier', "Toggled status of cashier {$cashier->name} to {$state}");

        return redirect()->route('cashiers.index')
            ->with('success', "Cashier status marked as {$state}.");
    }

    public function destroy(Cashier $cashier)
    {
        if ($cashier->shiftClosings()->exists()) {
            return redirect()->route('cashiers.index')
                ->with('error', "Cannot delete cashier '{$cashier->name}' because they have associated shift closing records. You can deactivate them instead.");
        }

        $cashier->delete();

        ActivityLog::log('Deleted', 'Cashier', "Deleted cashier {$cashier->name}");

        return redirect()->route('cashiers.index')
            ->with('success', "Cashier '{$cashier->name}' deleted successfully.");
    }
}
