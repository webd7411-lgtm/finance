<?php

namespace App\Http\Controllers;

use App\Models\StationRental;
use App\Models\RentalCollection;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RentalController extends Controller
{
    public function index()
    {
        $rentals = StationRental::with(['collections.account', 'collections.receivedBy'])->latest()->get();
        $accounts = Account::active()->orderBy('type')->orderBy('name')->get();

        $currentMonth = Carbon::now()->format('Y-m');
        $currentMonthName = Carbon::now()->format('F Y');

        $totalMonthlyExpected = (float) $rentals->where('status', 'active')->sum('monthly_rent');
        
        $currentMonthCollections = RentalCollection::where('rent_month', $currentMonth)->sum('amount');
        $allTimeCollections = RentalCollection::sum('amount');

        $recentCollections = RentalCollection::with(['rental', 'account', 'receivedBy'])
            ->latest('payment_date')
            ->latest('id')
            ->take(20)
            ->get();

        return view('rentals.index', compact(
            'rentals',
            'accounts',
            'currentMonth',
            'currentMonthName',
            'totalMonthlyExpected',
            'currentMonthCollections',
            'allTimeCollections',
            'recentCollections'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'facility_type' => ['required', 'string', 'in:car_wash,tyre_shop,tuck_shop,atm,other'],
            'tenant_name'   => ['required', 'string', 'max:255'],
            'tenant_phone'  => ['nullable', 'string', 'max:50'],
            'monthly_rent'  => ['required', 'numeric', 'min:0'],
            'due_day'       => ['nullable', 'integer', 'between:1,31'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ]);

        $rental = StationRental::create([
            'name'          => $validated['name'],
            'facility_type' => $validated['facility_type'],
            'tenant_name'   => $validated['tenant_name'],
            'tenant_phone'  => $validated['tenant_phone'] ?? null,
            'monthly_rent'  => $validated['monthly_rent'],
            'due_day'       => $validated['due_day'] ?? 1,
            'status'        => 'active',
            'notes'         => $validated['notes'] ?? null,
        ]);

        ActivityLog::log(
            'Created',
            'Station Rental',
            "Added station rental facility '{$rental->name}' (Tenant: {$rental->tenant_name}, Rent: Rs. " . number_format($rental->monthly_rent, 2) . "/month)"
        );

        return redirect()->route('rentals.index')->with('success', "Rental facility '{$rental->name}' registered successfully.");
    }

    public function update(Request $request, StationRental $rental)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'facility_type' => ['required', 'string', 'in:car_wash,tyre_shop,tuck_shop,atm,other'],
            'tenant_name'   => ['required', 'string', 'max:255'],
            'tenant_phone'  => ['nullable', 'string', 'max:50'],
            'monthly_rent'  => ['required', 'numeric', 'min:0'],
            'due_day'       => ['nullable', 'integer', 'between:1,31'],
            'status'        => ['required', 'string', 'in:active,inactive'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ]);

        $rental->update($validated);

        ActivityLog::log(
            'Updated',
            'Station Rental',
            "Updated rental facility '{$rental->name}' details"
        );

        return redirect()->route('rentals.index')->with('success', "Rental facility '{$rental->name}' updated successfully.");
    }

    public function collectRent(Request $request, StationRental $rental)
    {
        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'rent_month'   => ['required', 'string', 'max:20'], // e.g. 2026-11
            'amount'       => ['required', 'numeric', 'gt:0'],
            'account_id'   => ['required', 'integer', 'exists:accounts,id'],
            'receipt_no'   => ['nullable', 'string', 'max:100'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $collection = DB::transaction(function () use ($rental, $validated) {
            $amount = (float) $validated['amount'];
            $account = Account::whereKey($validated['account_id'])->lockForUpdate()->firstOrFail();

            $collection = RentalCollection::create([
                'station_rental_id' => $rental->id,
                'payment_date'      => $validated['payment_date'],
                'rent_month'        => $validated['rent_month'],
                'amount'            => $amount,
                'account_id'        => $account->id,
                'receipt_no'        => $validated['receipt_no'] ?? null,
                'notes'             => $validated['notes'] ?? null,
                'received_by'       => auth()->id(),
            ]);

            // Deposit rent amount into chosen Cash/Bank account
            $account->current_balance += $amount;
            $account->save();

            // Create Financial Transaction for Ledger
            Transaction::create([
                'date'        => $validated['payment_date'],
                'type'        => 'payment_in',
                'account_id'  => $account->id,
                'amount'      => $amount,
                'bill_no'     => $validated['receipt_no'] ?? ('RENT-' . $collection->id),
                'description' => "Rental Income: {$rental->name} ({$rental->tenant_name}) for {$validated['rent_month']}",
                'created_by'  => auth()->id(),
            ]);

            return $collection;
        });

        Account::syncCashAccountBalance();

        ActivityLog::log(
            'Created',
            'Rental Income',
            "Collected Rs. " . number_format($collection->amount, 2) . " rent for '{$rental->name}' ({$collection->rent_month}) into {$collection->account?->name}"
        );

        return redirect()->route('rentals.index')->with('success', "Rent of Rs. " . number_format($collection->amount, 2) . " successfully collected and deposited into {$collection->account?->name}.");
    }

    public function destroyCollection(RentalCollection $collection)
    {
        DB::transaction(function () use ($collection) {
            if ($collection->account_id && $collection->amount > 0) {
                $account = Account::find($collection->account_id);
                if ($account) {
                    $account->current_balance = max(0, $account->current_balance - $collection->amount);
                    $account->save();
                }
            }

            // Remove associated ledger transaction
            Transaction::where('bill_no', $collection->receipt_no ?: ('RENT-' . $collection->id))->delete();

            $collection->delete();
        });

        Account::syncCashAccountBalance();

        return redirect()->route('rentals.index')->with('success', 'Rental collection entry deleted and reversed.');
    }

    public function destroy(StationRental $rental)
    {
        $rental->delete();

        ActivityLog::log(
            'Deleted',
            'Station Rental',
            "Archived station rental facility '{$rental->name}'"
        );

        return redirect()->route('rentals.index')->with('success', "Rental facility '{$rental->name}' archived.");
    }
}
