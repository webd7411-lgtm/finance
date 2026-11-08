<?php

namespace App\Http\Controllers;

use App\Models\Tank;
use App\Models\TankDipReading;
use App\Models\StationLocation;
use App\Models\Product;
use App\Models\Pump;
use App\Models\Purchase;
use App\Models\ShiftClosing;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class TankController extends Controller
{
    /**
     * Display a listing of storage tanks
     */
    public function index(Request $request)
    {
        $query = Tank::with(['location', 'product', 'pumps' => fn($q) => $q->active()]);

        if ($request->filled('location_id') && $request->location_id !== 'all') {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('product_id') && $request->product_id !== 'all') {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('tank_number', 'like', "%{$search}%")
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        $tanks = $query->orderBy('tank_number')->paginate(12)->withQueryString();

        // Key KPI metrics
        $totalTanks = Tank::count();
        $totalCapacity = (float) Tank::where('status', 'active')->sum('capacity');
        $totalCurrentStock = (float) Tank::where('status', 'active')->sum('current_stock');
        $totalAvailableSpace = max(0, $totalCapacity - $totalCurrentStock);
        $lowStockTanks = Tank::where('status', 'active')->whereColumn('current_stock', '<=', 'dead_stock')->count();

        $locations = StationLocation::active()->orderBy('name')->get(['id', 'name', 'code']);
        $fuelProducts = Product::active()->fuel()->orderBy('name')->get(['id', 'name', 'unit', 'current_stock']);

        return view('tanks.index', compact(
            'tanks',
            'totalTanks',
            'totalCapacity',
            'totalCurrentStock',
            'totalAvailableSpace',
            'lowStockTanks',
            'locations',
            'fuelProducts'
        ));
    }

    /**
     * Show the form for creating a new storage tank
     */
    public function create()
    {
        $locations = StationLocation::active()->orderBy('name')->get();
        $fuelProducts = Product::active()->fuel()->orderBy('name')->get();

        return view('tanks.create', compact('locations', 'fuelProducts'));
    }

    /**
     * Store a newly created storage tank
     */
    public function store(Request $request)
    {
        // Sanitize numeric inputs
        foreach (['capacity', 'opening_stock', 'dead_stock', 'current_dip_reading', 'max_dip_reading'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $clean = str_replace(',', '', trim($request->input($field)));
                $request->merge([$field => $clean === '' ? 0 : $clean]);
            }
        }

        $validated = $request->validate([
            'location_id'         => ['required', 'integer', 'exists:station_locations,id'],
            'product_id'          => ['required', 'integer', 'exists:products,id'],
            'tank_number'         => ['required', 'string', 'max:50'],
            'name'                => ['required', 'string', 'max:255'],
            'tank_type'           => ['required', 'string', Rule::in(['underground', 'aboveground'])],
            'capacity'            => ['required', 'numeric', 'min:1'],
            'opening_stock'       => ['required', 'numeric', 'min:0'],
            'dead_stock'          => ['nullable', 'numeric', 'min:0'],
            'current_dip_reading' => ['nullable', 'numeric', 'min:0'],
            'max_dip_reading'     => ['nullable', 'numeric', 'min:0'],
            'status'              => ['required', 'string', Rule::in(['active', 'maintenance', 'inactive'])],
            'description'         => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['current_stock'] = $validated['opening_stock'];

        DB::beginTransaction();
        try {
            $tank = Tank::create($validated);

            // If initial dip reading was provided, record first log
            if (!empty($validated['current_dip_reading'])) {
                TankDipReading::create([
                    'tank_id'           => $tank->id,
                    'reading_date'      => now()->toDateString(),
                    'dip_cm'            => $validated['current_dip_reading'],
                    'measured_liters'   => $tank->current_stock,
                    'book_stock_liters' => $tank->current_stock,
                    'variance_liters'   => 0.00,
                    'recorded_by'       => auth()->id(),
                    'notes'             => 'Initial calibration dip reading on tank creation',
                ]);
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action'  => 'create',
                'module'  => 'tanks',
                'details' => "Created Storage Tank: {$tank->name} ({$tank->tank_number}) with capacity of {$tank->capacity} Ltr",
            ]);

            DB::commit();

            return redirect()->route('tanks.index')->with('success', "Storage Tank '{$tank->name}' created successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error creating storage tank: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified storage tank with full fuel ledger, machine-wise dispensing history, and reconciliation
     */
    public function show(Tank $tank)
    {
        $tank->load(['location', 'product', 'pumps.meterReadings', 'dipReadings.recorder']);

        $pumpIds = $tank->pumps->pluck('id')->toArray();

        // 1. All Fuel Purchases / Bowser Deliveries into this tank
        $purchases = Purchase::with(['supplier', 'paymentAccount', 'creator'])
            ->where(function ($q) use ($tank, $pumpIds) {
                $q->where('tank_id', $tank->id);
                if (!empty($pumpIds)) {
                    $q->orWhereIn('pump_id', $pumpIds);
                }
            })
            ->orderBy('purchase_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 2. All Dispenser Shift Closings / Fuel Outflow from this tank
        $shiftClosings = ShiftClosing::with(['cashier', 'closedBy', 'pump'])
            ->where(function ($q) use ($tank, $pumpIds) {
                $q->where('tank_id', $tank->id);
                if (!empty($pumpIds)) {
                    $q->orWhereIn('pump_id', $pumpIds);
                }
            })
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 3. Cumulative Metrics
        $openingStock = (float) $tank->opening_stock;
        $totalPurchasedLiters = (float) $purchases->sum('quantity');
        $totalPurchasesAmount = (float) $purchases->sum('total_amount');
        $totalDispensedLiters = (float) $shiftClosings->sum('total_liters');
        $totalSalesAmount = (float) $shiftClosings->sum('total_sale');
        $calculatedBookStock = max(0, $openingStock + $totalPurchasedLiters - $totalDispensedLiters);

        // 4. Machine-wise Performance Breakdown (Kis machine ne kitna sale kiya)
        $machineStats = [];
        foreach ($tank->pumps as $pump) {
            $pLiters = (float) $shiftClosings->where('pump_id', $pump->id)->sum('total_liters');
            $pSales = (float) $shiftClosings->where('pump_id', $pump->id)->sum('total_sale');
            $pShiftsCount = $shiftClosings->where('pump_id', $pump->id)->count();
            $pLastShift = $shiftClosings->where('pump_id', $pump->id)->sortByDesc('date')->first();

            $machineStats[] = [
                'pump'             => $pump,
                'liters_dispensed' => $pLiters,
                'sales_amount'     => $pSales,
                'shifts_count'     => $pShiftsCount,
                'last_shift'       => $pLastShift,
            ];
        }

        // 5. Build Comprehensive Chronological Stock Movement Ledger (Inflow vs Outflow)
        $timeline = [];

        // Initial setup entry
        $timeline[] = [
            'sort_key'      => $tank->created_at ? $tank->created_at->format('Y-m-d H:i:s') . '_0' : '2026-01-01 00:00:00_0',
            'date'          => $tank->created_at ? $tank->created_at->format('Y-m-d') : date('Y-m-d'),
            'type'          => 'opening',
            'type_badge'    => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-0.5"><i class="bi bi-flag-fill me-1"></i>Initial Setup</span>',
            'title'         => 'Initial Tank Baseline / Opening Stock',
            'reference'     => 'Tank Calibration Baseline',
            'machine'       => '-',
            'in'            => $openingStock,
            'out'           => 0,
            'rate'          => $tank->product?->purchase_price ?? 0,
            'amount'        => 0,
            'user'          => 'System Admin',
            'notes'         => 'Opening inventory calibrated at tank configuration',
            'raw_obj'       => null,
        ];

        // Purchases (Fuel In)
        foreach ($purchases as $pur) {
            $sortTime = $pur->created_at ? $pur->created_at->format('Y-m-d H:i:s') : ($pur->purchase_date->format('Y-m-d') . ' 12:00:00');
            $timeline[] = [
                'sort_key'      => $sortTime . '_' . $pur->id,
                'date'          => $pur->purchase_date->format('Y-m-d'),
                'type'          => 'purchase',
                'type_badge'    => '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5"><i class="bi bi-box-arrow-in-down me-1"></i>Fuel Intake</span>',
                'title'         => 'Fuel Bowser Intake • ' . ($pur->supplier?->name ?? 'Supplier'),
                'reference'     => 'Bill #' . ($pur->invoice_no ?: 'PO #' . $pur->id) . ($pur->vehicle_no ? ' (Bowser: ' . $pur->vehicle_no . ')' : ''),
                'machine'       => $pur->pump?->name ?? 'Direct Tank Discharge',
                'in'            => (float) $pur->quantity,
                'out'           => 0,
                'rate'          => (float) $pur->unit_price,
                'amount'        => (float) $pur->total_amount,
                'user'          => $pur->creator?->name ?? 'Admin',
                'notes'         => $pur->notes,
                'raw_obj'       => $pur,
            ];
        }

        // Shift Closings (Fuel Out)
        foreach ($shiftClosings as $sc) {
            $sortTime = $sc->created_at ? $sc->created_at->format('Y-m-d H:i:s') : ($sc->date->format('Y-m-d') . ($sc->shift_type === 'morning' ? ' 14:00:00' : ' 22:00:00'));
            $meterRange = number_format($sc->start_reading, 1) . ' → ' . number_format($sc->end_reading, 1);
            $timeline[] = [
                'sort_key'      => $sortTime . '_' . $sc->id,
                'date'          => $sc->date->format('Y-m-d'),
                'type'          => 'shift_sale',
                'type_badge'    => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5"><i class="bi bi-fuel-pump me-1"></i>Dispenser Sale</span>',
                'title'         => ($sc->pump_machine ?: 'Pump Machine') . ' • ' . ucfirst($sc->shift_type) . ' Shift',
                'reference'     => 'Shift #' . $sc->id . ' (Meter: ' . $meterRange . ')',
                'machine'       => $sc->pump_machine ?: ($sc->pump?->name ?? 'Pump'),
                'in'            => 0,
                'out'           => (float) $sc->total_liters,
                'rate'          => (float) $sc->fuel_rate,
                'amount'        => (float) $sc->total_sale,
                'user'          => $sc->cashier?->name ?? ($sc->closedBy?->name ?? 'Cashier'),
                'notes'         => $sc->remarks,
                'raw_obj'       => $sc,
            ];
        }

        // Sort chronologically ascending to compute running balances accurately
        usort($timeline, fn($a, $b) => strcmp($a['sort_key'], $b['sort_key']));

        $runningStock = 0.0;
        foreach ($timeline as $index => &$entry) {
            if ($entry['type'] === 'opening') {
                $entry['stock_before'] = 0.0;
                $runningStock = $entry['in'];
                $entry['stock_after'] = $runningStock;
            } elseif ($entry['type'] === 'purchase') {
                $entry['stock_before'] = $runningStock;
                $runningStock += $entry['in'];
                $entry['stock_after'] = $runningStock;
            } elseif ($entry['type'] === 'shift_sale') {
                $entry['stock_before'] = $runningStock;
                $runningStock = max(0.0, $runningStock - $entry['out']);
                $entry['stock_after'] = $runningStock;
            }
        }
        unset($entry);

        // Reverse for display so most recent transactions appear first
        $displayLedger = array_reverse($timeline);

        // Attach before/after stock directly on the purchases collection for table display
        $purchaseStockMap = [];
        foreach ($timeline as $tItem) {
            if ($tItem['type'] === 'purchase' && !empty($tItem['raw_obj'])) {
                $purchaseStockMap[$tItem['raw_obj']->id] = [
                    'stock_before' => $tItem['stock_before'],
                    'stock_after'  => $tItem['stock_after'],
                ];
            }
        }

        // Attach before/after stock directly on shift closings for table display
        $closingStockMap = [];
        foreach ($timeline as $tItem) {
            if ($tItem['type'] === 'shift_sale' && !empty($tItem['raw_obj'])) {
                $closingStockMap[$tItem['raw_obj']->id] = [
                    'stock_before' => $tItem['stock_before'],
                    'stock_after'  => $tItem['stock_after'],
                ];
            }
        }

        return view('tanks.show', compact(
            'tank',
            'purchases',
            'shiftClosings',
            'openingStock',
            'totalPurchasedLiters',
            'totalPurchasesAmount',
            'totalDispensedLiters',
            'totalSalesAmount',
            'calculatedBookStock',
            'machineStats',
            'displayLedger',
            'purchaseStockMap',
            'closingStockMap'
        ));
    }

    /**
     * Show the form for editing the specified storage tank
     */
    public function edit(Tank $tank)
    {
        $locations = StationLocation::active()->orderBy('name')->get();
        $fuelProducts = Product::active()->fuel()->orderBy('name')->get();

        return view('tanks.edit', compact('tank', 'locations', 'fuelProducts'));
    }

    /**
     * Update the specified storage tank
     */
    public function update(Request $request, Tank $tank)
    {
        foreach (['capacity', 'dead_stock', 'current_dip_reading', 'max_dip_reading'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $clean = str_replace(',', '', trim($request->input($field)));
                $request->merge([$field => $clean === '' ? 0 : $clean]);
            }
        }

        $validated = $request->validate([
            'location_id'         => ['required', 'integer', 'exists:station_locations,id'],
            'product_id'          => ['required', 'integer', 'exists:products,id'],
            'tank_number'         => ['required', 'string', 'max:50'],
            'name'                => ['required', 'string', 'max:255'],
            'tank_type'           => ['required', 'string', Rule::in(['underground', 'aboveground'])],
            'capacity'            => ['required', 'numeric', 'min:1'],
            'dead_stock'          => ['nullable', 'numeric', 'min:0'],
            'current_dip_reading' => ['nullable', 'numeric', 'min:0'],
            'max_dip_reading'     => ['nullable', 'numeric', 'min:0'],
            'status'              => ['required', 'string', Rule::in(['active', 'maintenance', 'inactive'])],
            'description'         => ['nullable', 'string', 'max:1000'],
        ]);

        $tank->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'update',
            'module'  => 'tanks',
            'details' => "Updated Storage Tank: {$tank->name} ({$tank->tank_number})",
        ]);

        return redirect()->route('tanks.index')->with('success', "Storage Tank '{$tank->name}' updated successfully!");
    }

    /**
     * Remove the specified storage tank
     */
    public function destroy(Tank $tank)
    {
        if ($tank->pumps()->where('status', 'active')->exists()) {
            return back()->with('error', "Cannot delete tank '{$tank->name}' because active pump machines are currently connected to it. Reassign or deactivate the pumps first.");
        }

        $tankName = $tank->name;
        $tank->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'delete',
            'module'  => 'tanks',
            'details' => "Deleted Storage Tank: {$tankName}",
        ]);

        return redirect()->route('tanks.index')->with('success', "Storage Tank '{$tankName}' has been removed successfully.");
    }

    /**
     * Record a daily physical Dip Stick measurement & calculate variance against book stock
     */
    public function recordDip(Request $request, Tank $tank)
    {
        $validated = $request->validate([
            'dip_cm'          => ['required', 'numeric', 'min:0'],
            'measured_liters' => ['required', 'numeric', 'min:0'],
            'reading_date'    => ['required', 'date'],
            'notes'           => ['nullable', 'string', 'max:500'],
        ]);

        $bookStock = (float) $tank->current_stock;
        $measured = (float) $validated['measured_liters'];
        $variance = $measured - $bookStock; // +ve = gain/surplus, -ve = evaporation/loss

        TankDipReading::create([
            'tank_id'           => $tank->id,
            'reading_date'      => $validated['reading_date'],
            'dip_cm'            => $validated['dip_cm'],
            'measured_liters'   => $measured,
            'book_stock_liters' => $bookStock,
            'variance_liters'   => $variance,
            'recorded_by'       => auth()->id(),
            'notes'             => $validated['notes'] ?? 'Manual daily physical dip check',
        ]);

        $tank->update([
            'current_dip_reading' => $validated['dip_cm'],
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action'  => 'dip_reading',
            'module'  => 'tanks',
            'details' => "Recorded Dip for {$tank->name}: {$validated['dip_cm']} cm ({$measured} Ltr). Book Variance: " . number_format($variance, 2) . " Ltr",
        ]);

        return back()->with('success', "Dip reading recorded for {$tank->name}! Physical: {$measured} Ltr, Variance: " . number_format($variance, 2) . " Ltr");
    }
}
