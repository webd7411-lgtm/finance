<?php

namespace App\Http\Controllers;

use App\Models\Pump;
use App\Models\PumpMeterReading;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ShiftClosing;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PumpController extends Controller
{
    /**
     * Display a listing of petrol pump machines & dispensers
     */
    public function index(Request $request)
    {
        $query = Pump::with(['product'])->withCount(['shiftClosings', 'meterReadings']);

        // Filter by Status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by Fuel Product
        if ($request->filled('product_id') && $request->product_id !== 'all') {
            $query->where('product_id', $request->product_id);
        }

        // Search by Name, Code, Tank, or Nozzle
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('tank_no', 'like', "%{$search}%")
                  ->orWhere('nozzle_number', 'like', "%{$search}%");
            });
        }

        $pumps = $query->orderBy('name')->paginate(12)->withQueryString();

        // High-level statistics
        $totalPumps = Pump::count();
        $activePumps = Pump::where('status', 'active')->count();
        $maintenancePumps = Pump::where('status', 'maintenance')->count();
        $totalDispensedLiters = (float) ShiftClosing::sum('total_liters');
        $todayDispensedLiters = (float) ShiftClosing::whereDate('date', now()->toDateString())->sum('total_liters');
        $totalRevenueGenerated = (float) ShiftClosing::sum('total_sale');

        $fuelProducts = Product::active()->fuel()->orderBy('name')->get(['id', 'name', 'sale_price', 'current_stock', 'unit']);

        return view('pumps.index', compact(
            'pumps',
            'totalPumps',
            'activePumps',
            'maintenancePumps',
            'totalDispensedLiters',
            'todayDispensedLiters',
            'totalRevenueGenerated',
            'fuelProducts'
        ));
    }

    /**
     * Show the form for creating a new pump machine
     */
    public function create()
    {
        $fuelProducts = Product::active()->fuel()->orderBy('name')->get();
        // Common underground tank suggestions
        $existingTanks = Pump::whereNotNull('tank_no')->distinct()->pluck('tank_no')->filter()->values();

        return view('pumps.create', compact('fuelProducts', 'existingTanks'));
    }

    /**
     * Store a newly created pump machine
     */
    public function store(Request $request)
    {
        // Sanitize numeric fields
        foreach (['initial_reading', 'last_reading'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $clean = str_replace(',', '', trim($request->input($field)));
                $request->merge([$field => $clean === '' ? 0 : $clean]);
            }
        }

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'code'            => ['nullable', 'string', 'max:50', 'unique:pumps,code'],
            'product_id'      => ['required', 'integer', 'exists:products,id'],
            'tank_no'         => ['nullable', 'string', 'max:255'],
            'nozzle_number'   => ['nullable', 'string', 'max:100'],
            'meter_type'      => ['required', 'string', Rule::in(['electronic', 'mechanical'])],
            'initial_reading' => ['required', 'numeric', 'min:0'],
            'last_reading'    => ['nullable', 'numeric', 'min:0'],
            'status'          => ['required', 'string', Rule::in(['active', 'maintenance', 'inactive'])],
            'description'     => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Pump machine / dispenser name is required.',
            'product_id.required' => 'Please select the fuel product dispensed by this machine.',
            'initial_reading.required' => 'Initial / Starting meter reading is required.',
        ]);

        $initialReading = (float) $validated['initial_reading'];
        $lastReading = isset($validated['last_reading']) && (float)$validated['last_reading'] > 0
            ? (float) $validated['last_reading']
            : $initialReading;

        $pump = DB::transaction(function () use ($validated, $initialReading, $lastReading) {
            $pump = Pump::create([
                'name'            => $validated['name'],
                'code'            => !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
                'product_id'      => $validated['product_id'],
                'tank_no'         => $validated['tank_no'] ?? null,
                'nozzle_number'   => $validated['nozzle_number'] ?? null,
                'meter_type'      => $validated['meter_type'],
                'initial_reading' => $initialReading,
                'last_reading'    => $lastReading,
                'status'          => $validated['status'],
                'description'     => $validated['description'] ?? null,
            ]);

            // Create initial baseline meter reading record
            PumpMeterReading::create([
                'pump_id'          => $pump->id,
                'reading_date'     => now()->toDateString(),
                'reading_type'     => 'initial_setup',
                'start_reading'    => $initialReading,
                'end_reading'      => $lastReading,
                'volume_dispensed' => 0,
                'fuel_rate'        => $pump->product?->sale_price ?? 0,
                'sales_amount'     => 0,
                'recorded_by'      => auth()->id(),
                'remarks'          => 'Initial dispenser installation meter baseline setup',
            ]);

            ActivityLog::log(
                'Created',
                'Pump Machine',
                "Added new pump dispenser '{$pump->name}' (" . ($pump->code ?: '#' . $pump->id) . ") with initial meter reading of " . number_format($initialReading, 2)
            );

            return $pump;
        });

        // Handle AJAX submission from Shift Closing modal
        if ($request->ajax() || $request->wantsJson()) {
            $pump->load('product');
            return response()->json([
                'success' => true,
                'message' => 'Pump machine added successfully.',
                'pump'    => [
                    'id'            => $pump->id,
                    'name'          => $pump->name,
                    'code'          => $pump->code,
                    'last_reading'  => (float) $pump->last_reading,
                    'product_id'    => $pump->product_id,
                    'product_name'  => $pump->product?->name ?? 'Fuel',
                    'fuel_rate'     => (float) ($pump->product?->sale_price ?? 0),
                    'unit'          => $pump->product?->unit_label ?? 'Ltr',
                    'current_stock' => (float) ($pump->product?->current_stock ?? 0),
                    'tank_no'       => $pump->tank_no ?? 'Storage Tank',
                ],
            ]);
        }

        return redirect()->route('pumps.show', $pump->id)->with('success', "Pump Machine '{$pump->name}' created successfully.");
    }

    /**
     * Display a specific pump machine, its meter readings history, and stock synchronization
     */
    public function show(Pump $pump, Request $request)
    {
        $pump->load(['product']);

        // Stock & Purchase Synchronization Data
        $stockSync = $pump->getStockSyncData();

        // Purchases linked to this fuel / pump tank
        $recentPurchases = Purchase::with(['supplier', 'paymentAccount'])
            ->where('product_id', $pump->product_id)
            ->latest('purchase_date')
            ->limit(5)
            ->get();

        // Meter Readings Query with Filters
        $readingsQuery = $pump->meterReadings()->with(['shiftClosing', 'cashier', 'recorder']);

        if ($request->filled('reading_type') && $request->reading_type !== 'all') {
            $readingsQuery->where('reading_type', $request->reading_type);
        }

        if ($request->filled('from_date')) {
            $readingsQuery->whereDate('reading_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $readingsQuery->whereDate('reading_date', '<=', $request->to_date);
        }

        $readings = $readingsQuery->paginate(20)->withQueryString();

        // Machine performance metrics
        $totalDispensed = (float) $pump->shiftClosings()->sum('total_liters');
        $totalRevenue = (float) $pump->shiftClosings()->sum('total_sale');
        $shiftsCount = $pump->shiftClosings()->count();
        $lastShift = $pump->shiftClosings()->latest('date')->first();

        return view('pumps.show', compact(
            'pump',
            'stockSync',
            'recentPurchases',
            'readings',
            'totalDispensed',
            'totalRevenue',
            'shiftsCount',
            'lastShift'
        ));
    }

    /**
     * Show the form for editing the pump machine
     */
    public function edit(Pump $pump)
    {
        $fuelProducts = Product::active()->fuel()->orderBy('name')->get();
        $existingTanks = Pump::whereNotNull('tank_no')->distinct()->pluck('tank_no')->filter()->values();

        return view('pumps.edit', compact('pump', 'fuelProducts', 'existingTanks'));
    }

    /**
     * Update the pump machine
     */
    public function update(Request $request, Pump $pump)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'code'          => ['nullable', 'string', 'max:50', Rule::unique('pumps', 'code')->ignore($pump->id)],
            'product_id'    => ['required', 'integer', 'exists:products,id'],
            'tank_no'       => ['nullable', 'string', 'max:255'],
            'nozzle_number' => ['nullable', 'string', 'max:100'],
            'meter_type'    => ['required', 'string', Rule::in(['electronic', 'mechanical'])],
            'status'        => ['required', 'string', Rule::in(['active', 'maintenance', 'inactive'])],
            'description'   => ['nullable', 'string', 'max:1000'],
        ]);

        $pump->update([
            'name'          => $validated['name'],
            'code'          => !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'product_id'    => $validated['product_id'],
            'tank_no'       => $validated['tank_no'] ?? null,
            'nozzle_number' => $validated['nozzle_number'] ?? null,
            'meter_type'    => $validated['meter_type'],
            'status'        => $validated['status'],
            'description'   => $validated['description'] ?? null,
        ]);

        ActivityLog::log(
            'Updated',
            'Pump Machine',
            "Updated specifications for pump dispenser '{$pump->name}'"
        );

        return redirect()->route('pumps.show', $pump->id)->with('success', "Pump Machine '{$pump->name}' updated successfully.");
    }

    /**
     * Toggle status between active, maintenance, and inactive
     */
    public function toggleStatus(Pump $pump)
    {
        $oldStatus = $pump->status;
        $newStatus = $oldStatus === 'active' ? 'maintenance' : ($oldStatus === 'maintenance' ? 'inactive' : 'active');

        $pump->status = $newStatus;
        $pump->save();

        ActivityLog::log(
            'Updated',
            'Pump Machine',
            "Changed status of '{$pump->name}' from {$oldStatus} to {$newStatus}"
        );

        return back()->with('success', "Machine status updated to " . ucfirst($newStatus) . ".");
    }

    /**
     * Record a manual meter calibration or adjustment
     */
    public function adjustReading(Request $request, Pump $pump)
    {
        // Sanitize numeric fields
        foreach (['new_reading', 'volume_tested'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $clean = str_replace(',', '', trim($request->input($field)));
                $request->merge([$field => $clean === '' ? 0 : $clean]);
            }
        }

        $validated = $request->validate([
            'new_reading'      => ['required', 'numeric', 'min:0'],
            'adjustment_type'  => ['required', 'string', Rule::in(['calibration', 'manual_adjustment'])],
            'volume_tested'    => ['nullable', 'numeric', 'min:0'],
            'returned_to_tank' => ['nullable', 'boolean'],
            'remarks'          => ['required', 'string', 'max:1000'],
        ], [
            'new_reading.required'     => 'New meter reading is required.',
            'adjustment_type.required' => 'Please select the adjustment/calibration reason.',
            'remarks.required'         => 'Please provide detailed remarks or technician calibration notes.',
        ]);

        $previousReading = (float) $pump->last_reading;
        $newReading = (float) $validated['new_reading'];
        $volumeTested = (float) ($validated['volume_tested'] ?? 0);
        $returnedToTank = (bool) ($request->has('returned_to_tank') && $request->input('returned_to_tank') == '1');

        DB::transaction(function () use ($pump, $previousReading, $newReading, $volumeTested, $returnedToTank, $validated) {
            // Update pump last reading
            $pump->last_reading = $newReading;
            $pump->save();

            // Record meter calibration log
            PumpMeterReading::create([
                'pump_id'          => $pump->id,
                'reading_date'     => now()->toDateString(),
                'reading_type'     => $validated['adjustment_type'],
                'start_reading'    => $previousReading,
                'end_reading'      => $newReading,
                'volume_dispensed' => $volumeTested,
                'fuel_rate'        => $pump->product?->sale_price ?? 0,
                'sales_amount'     => round($volumeTested * ($pump->product?->sale_price ?? 0), 2),
                'recorded_by'      => auth()->id(),
                'remarks'          => $validated['remarks'] . ($returnedToTank ? ' (Test volume returned to storage tank)' : ' (Test volume consumed/discharged)'),
            ]);

            // If test volume was NOT returned to the tank, decrement product stock
            if (!$returnedToTank && $volumeTested > 0 && $pump->product_id) {
                $product = Product::find($pump->product_id);
                if ($product) {
                    $product->current_stock = max(0, $product->current_stock - $volumeTested);
                    $product->save();
                }
            }

            ActivityLog::log(
                'Calibrated',
                'Pump Machine',
                "Calibrated/Adjusted meter reading for '{$pump->name}' from {$previousReading} to {$newReading} (Type: {$validated['adjustment_type']})"
            );
        });

        return back()->with('success', "Meter reading successfully adjusted from {$previousReading} to {$newReading}.");
    }

    /**
     * Delete the pump machine if no shifts are linked
     */
    public function destroy(Pump $pump)
    {
        $shiftsCount = $pump->shiftClosings()->count();
        if ($shiftsCount > 0) {
            return back()->with('error', "Cannot delete '{$pump->name}' because it has {$shiftsCount} historical shift closing sheets attached. You can set its status to Inactive instead to maintain financial and inventory records.");
        }

        $pumpName = $pump->name;
        $pump->delete();

        ActivityLog::log(
            'Deleted',
            'Pump Machine',
            "Deleted pump machine '{$pumpName}'"
        );

        return redirect()->route('pumps.index')->with('success', "Pump Machine '{$pumpName}' has been removed.");
    }

    /**
     * JSON API endpoint for dynamic pump details in Shift Closing form
     */
    public function getDetails(Pump $pump)
    {
        $pump->load('product');

        return response()->json([
            'id'            => $pump->id,
            'name'          => $pump->name,
            'code'          => $pump->code,
            'last_reading'  => (float) $pump->last_reading,
            'product_id'    => $pump->product_id,
            'product_name'  => $pump->product?->name ?? 'Fuel',
            'fuel_rate'     => (float) ($pump->product?->sale_price ?? 0),
            'unit'          => $pump->product?->unit_label ?? 'Ltr',
            'current_stock' => (float) ($pump->product?->current_stock ?? 0),
            'tank_no'       => $pump->tank_no ?? 'Storage Tank',
            'meter_type'    => $pump->meter_type,
            'status'        => $pump->status,
        ]);
    }
}
