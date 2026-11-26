<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Pump;
use App\Models\Purchase;
use App\Models\ShiftClosing;
use App\Models\StationLocation;
use App\Models\StationSetting;
use App\Models\Tank;
use App\Models\TankDipChart;
use App\Models\TankDipReading;
use App\Models\TankStockAdjustment;
use App\Services\TankReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TankController extends Controller
{
    public function __construct(protected TankReconciliationService $reconciliationService)
    {
    }

    /**
     * Display a listing of storage tanks with live dip variance & alerts
     */
    public function index(Request $request)
    {
        $query = Tank::with(['location', 'product', 'pumps' => fn($q) => $q->active()])
            ->withCount('dipCharts');

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
        $tankConfig = StationSetting::getTankConfig();

        $tankMeta = [];
        foreach ($tanks as $t) {
            $thresholds = $this->reconciliationService->calculateToleranceThresholds($t, (float) $t->current_stock, $tankConfig);
            $trend = $this->reconciliationService->detectConsecutiveShortageTrend($t, $tankConfig);
            $tankMeta[$t->id] = [
                'thresholds' => $thresholds,
                'trend'      => $trend,
                'has_chart'  => $t->dip_charts_count > 0,
            ];
        }

        // Key KPI metrics
        $activeTanks = Tank::with('product')->where('status', 'active')->get();
        $totalTanks = Tank::count();
        $totalCapacity = (float) $activeTanks->sum('capacity');
        $totalCurrentStock = (float) $activeTanks->sum('current_stock');
        $totalAvailableSpace = max(0, $totalCapacity - $totalCurrentStock);
        $lowStockTanks = Tank::where('status', 'active')->whereColumn('current_stock', '<=', 'dead_stock')->count();

        $netVarianceLiters = (float) $activeTanks->whereNotNull('last_variance_liters')->sum('last_variance_liters');
        $alertTanksCount = $activeTanks->filter(function ($t) use ($tankConfig) {
            if (in_array($t->last_variance_status, ['warning', 'critical'], true)) {
                return true;
            }
            return $this->reconciliationService->detectConsecutiveShortageTrend($t, $tankConfig)['has_trend'];
        })->count();

        $locations = StationLocation::active()->orderBy('name')->get(['id', 'name', 'code']);
        $fuelProducts = Product::active()->fuel()->orderBy('name')->get(['id', 'name', 'unit', 'current_stock']);
        $readingContexts = StationSetting::READING_CONTEXTS;
        $reasonCategories = StationSetting::REASON_CATEGORIES;

        return view('tanks.index', compact(
            'tanks',
            'tankConfig',
            'tankMeta',
            'totalTanks',
            'totalCapacity',
            'totalCurrentStock',
            'totalAvailableSpace',
            'lowStockTanks',
            'netVarianceLiters',
            'alertTanksCount',
            'locations',
            'fuelProducts',
            'readingContexts',
            'reasonCategories'
        ));
    }

    /**
     * Show the form for creating a new storage tank
     */
    public function create()
    {
        $locations = StationLocation::active()->orderBy('name')->get();
        $fuelProducts = Product::active()->fuel()->orderBy('name')->get();
        $tankConfig = StationSetting::getTankConfig();
        $toleranceModes = StationSetting::TOLERANCE_MODES;

        return view('tanks.create', compact('locations', 'fuelProducts', 'tankConfig', 'toleranceModes'));
    }

    /**
     * Store a newly created storage tank
     */
    public function store(Request $request)
    {
        foreach ([
            'capacity', 'opening_stock', 'dead_stock', 'current_dip_reading', 'max_dip_reading',
            'warning_tolerance_liters', 'critical_tolerance_liters', 'warning_tolerance_percent', 'critical_tolerance_percent',
        ] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $clean = str_replace(',', '', trim($request->input($field)));
                $request->merge([$field => $clean === '' ? null : $clean]);
            }
        }

        $validated = $request->validate([
            'location_id'                => ['required', 'integer', 'exists:station_locations,id'],
            'product_id'                 => ['required', 'integer', 'exists:products,id'],
            'tank_number'                => ['required', 'string', 'max:50'],
            'name'                       => ['required', 'string', 'max:255'],
            'tank_type'                  => ['required', 'string', Rule::in(['underground', 'aboveground'])],
            'capacity'                   => ['required', 'numeric', 'min:1'],
            'opening_stock'              => ['required', 'numeric', 'min:0'],
            'dead_stock'                 => ['nullable', 'numeric', 'min:0'],
            'current_dip_reading'        => ['nullable', 'numeric', 'min:0'],
            'max_dip_reading'            => ['nullable', 'numeric', 'min:0'],
            'use_default_tolerance'      => ['nullable', 'boolean'],
            'tolerance_mode'             => ['nullable', 'string', Rule::in(array_keys(StationSetting::TOLERANCE_MODES))],
            'warning_tolerance_liters'   => ['nullable', 'numeric', 'min:0'],
            'critical_tolerance_liters'  => ['nullable', 'numeric', 'min:0'],
            'warning_tolerance_percent'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'critical_tolerance_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status'                     => ['required', 'string', Rule::in(['active', 'maintenance', 'inactive'])],
            'description'                => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['dead_stock'] = $validated['dead_stock'] ?? 0;
        $validated['current_stock'] = $validated['opening_stock'];
        $validated['use_default_tolerance'] = $request->has('use_default_tolerance')
            ? $request->boolean('use_default_tolerance')
            : true;

        if ($validated['use_default_tolerance']) {
            $validated['tolerance_mode'] = null;
            $validated['warning_tolerance_liters'] = null;
            $validated['critical_tolerance_liters'] = null;
            $validated['warning_tolerance_percent'] = null;
            $validated['critical_tolerance_percent'] = null;
        }

        if (!empty($validated['current_dip_reading'])) {
            $validated['last_dip_date'] = now()->toDateString();
            $validated['last_dip_time'] = now()->format('H:i:s');
            $validated['last_measured_liters'] = $validated['current_stock'];
            $validated['last_variance_liters'] = 0.00;
            $validated['last_variance_status'] = 'balanced';
        }

        DB::beginTransaction();
        try {
            $tank = Tank::create($validated);

            if (!empty($validated['current_dip_reading'])) {
                TankDipReading::create([
                    'tank_id'                => $tank->id,
                    'reading_date'           => now()->toDateString(),
                    'reading_time'           => now()->format('H:i:s'),
                    'reading_context'        => 'initial',
                    'dip_cm'                 => $validated['current_dip_reading'],
                    'measured_liters'        => $tank->current_stock,
                    'book_stock_liters'      => $tank->current_stock,
                    'variance_liters'        => 0.00,
                    'period_variance_liters' => 0.00,
                    'variance_status'        => 'balanced',
                    'is_reconciled'          => true,
                    'reconciled_at'          => now(),
                    'reconciled_by'          => auth()->id(),
                    'recorded_by'            => auth()->id(),
                    'notes'                  => 'Initial calibration dip reading on tank creation',
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
     * Display the specified storage tank with full fuel ledger, machine-wise dispensing history, dip chart, and reconciliation
     */
    public function show(Tank $tank)
    {
        $tank->load([
            'location',
            'product',
            'pumps.meterReadings',
            'dipReadings.recorder',
            'dipReadings.reconciler',
            'dipCharts',
            'stockAdjustments.creator',
        ]);

        $tankConfig = StationSetting::getTankConfig();
        $thresholds = $this->reconciliationService->calculateToleranceThresholds($tank, (float) $tank->current_stock, $tankConfig);
        $trendInfo = $this->reconciliationService->detectConsecutiveShortageTrend($tank, $tankConfig);

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

        // 3. Approved Stock Reconciliation Adjustments
        $adjustments = $tank->stockAdjustments->sortBy([
            ['adjustment_date', 'asc'],
            ['id', 'asc'],
        ])->values();

        // 4. Cumulative Metrics
        $openingStock = (float) $tank->opening_stock;
        $totalPurchasedLiters = (float) $purchases->sum('quantity');
        $totalPurchasesAmount = (float) $purchases->sum('total_amount');
        $totalDispensedLiters = (float) $shiftClosings->sum('total_liters');
        $totalSalesAmount = (float) $shiftClosings->sum('total_sale');
        $totalAdjustmentLiters = (float) $adjustments->sum('adjustment_liters');
        $calculatedBookStock = max(0, $openingStock + $totalPurchasedLiters - $totalDispensedLiters + $totalAdjustmentLiters);

        // 5. Machine-wise Performance Breakdown
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

        // 6. Comprehensive Chronological Stock Movement Ledger (Inflow vs Outflow vs Dip Reconciliation Adjustments)
        $timeline = [];

        $timeline[] = [
            'sort_key'   => $tank->created_at ? $tank->created_at->format('Y-m-d H:i:s') . '_0' : '2026-01-01 00:00:00_0',
            'date'       => $tank->created_at ? $tank->created_at->format('Y-m-d') : date('Y-m-d'),
            'type'       => 'opening',
            'type_badge' => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-0.5"><i class="bi bi-flag-fill me-1"></i>Initial Setup</span>',
            'title'      => 'Initial Tank Baseline / Opening Stock',
            'reference'  => 'Tank Calibration Baseline',
            'machine'    => '-',
            'in'         => $openingStock,
            'out'        => 0,
            'rate'       => $tank->product?->purchase_price ?? 0,
            'amount'     => 0,
            'user'       => 'System Admin',
            'notes'      => 'Opening inventory calibrated at tank configuration',
            'raw_obj'    => null,
        ];

        foreach ($purchases as $pur) {
            $sortTime = $pur->created_at ? $pur->created_at->format('Y-m-d H:i:s') : ($pur->purchase_date->format('Y-m-d') . ' 12:00:00');
            $timeline[] = [
                'sort_key'   => $sortTime . '_1_' . $pur->id,
                'date'       => $pur->purchase_date->format('Y-m-d'),
                'type'       => 'purchase',
                'type_badge' => '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5"><i class="bi bi-box-arrow-in-down me-1"></i>Fuel Intake</span>',
                'title'      => 'Fuel Bowser Intake • ' . ($pur->supplier?->name ?? 'Supplier'),
                'reference'  => 'Bill #' . ($pur->invoice_no ?: 'PO #' . $pur->id) . ($pur->vehicle_no ? ' (Bowser: ' . $pur->vehicle_no . ')' : ''),
                'machine'    => $pur->pump?->name ?? 'Direct Tank Discharge',
                'in'         => (float) $pur->quantity,
                'out'        => 0,
                'rate'       => (float) $pur->unit_price,
                'amount'     => (float) $pur->total_amount,
                'user'       => $pur->creator?->name ?? 'Admin',
                'notes'      => $pur->notes,
                'raw_obj'    => $pur,
            ];
        }

        foreach ($shiftClosings as $sc) {
            $sortTime = $sc->created_at ? $sc->created_at->format('Y-m-d H:i:s') : ($sc->date->format('Y-m-d') . ($sc->shift_type === 'morning' ? ' 14:00:00' : ' 22:00:00'));
            $meterRange = number_format($sc->start_reading, 1) . ' → ' . number_format($sc->end_reading, 1);
            $timeline[] = [
                'sort_key'   => $sortTime . '_2_' . $sc->id,
                'date'       => $sc->date->format('Y-m-d'),
                'type'       => 'shift_sale',
                'type_badge' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5"><i class="bi bi-fuel-pump me-1"></i>Dispenser Sale</span>',
                'title'      => ($sc->pump_machine ?: 'Pump Machine') . ' • ' . ucfirst($sc->shift_type) . ' Shift',
                'reference'  => 'Shift #' . $sc->id . ' (Meter: ' . $meterRange . ')',
                'machine'    => $sc->pump_machine ?: ($sc->pump?->name ?? 'Pump'),
                'in'         => 0,
                'out'        => (float) $sc->total_liters,
                'rate'       => (float) $sc->fuel_rate,
                'amount'     => (float) $sc->total_sale,
                'user'       => $sc->cashier?->name ?? ($sc->closedBy?->name ?? 'Cashier'),
                'notes'      => $sc->remarks,
                'raw_obj'    => $sc,
            ];
        }

        foreach ($adjustments as $adj) {
            $sortTime = $adj->created_at ? $adj->created_at->format('Y-m-d H:i:s') : ($adj->adjustment_date->format('Y-m-d') . ' 23:59:00');
            $isGain = (float) $adj->adjustment_liters >= 0;
            $badge = $isGain
                ? '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2 py-0.5"><i class="bi bi-sliders me-1"></i>Dip Gain Adj</span>'
                : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5"><i class="bi bi-sliders me-1"></i>Dip Loss Adj</span>';

            $timeline[] = [
                'sort_key'   => $sortTime . '_3_' . $adj->id,
                'date'       => $adj->adjustment_date->format('Y-m-d'),
                'type'       => 'adjustment',
                'type_badge' => $badge,
                'title'      => ($adj->is_auto ? 'Auto ' : '') . 'Dip Reconciliation • ' . $adj->reason_label,
                'reference'  => 'Adj #' . $adj->id . ($adj->tank_dip_reading_id ? ' (Dip #' . $adj->tank_dip_reading_id . ')' : ''),
                'machine'    => 'Physical Dip Sync',
                'in'         => $isGain ? (float) $adj->adjustment_liters : 0,
                'out'        => !$isGain ? abs((float) $adj->adjustment_liters) : 0,
                'rate'       => (float) $adj->unit_cost,
                'amount'     => (float) $adj->financial_impact,
                'user'       => $adj->creator?->name ?? 'System',
                'notes'      => $adj->notes,
                'raw_obj'    => $adj,
            ];
        }

        usort($timeline, fn($a, $b) => strcmp($a['sort_key'], $b['sort_key']));

        $runningStock = 0.0;
        foreach ($timeline as &$entry) {
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
            } elseif ($entry['type'] === 'adjustment') {
                $entry['stock_before'] = $runningStock;
                $runningStock = max(0.0, $runningStock + $entry['in'] - $entry['out']);
                $entry['stock_after'] = $runningStock;
            }
        }
        unset($entry);

        $displayLedger = array_reverse($timeline);

        $purchaseStockMap = [];
        $closingStockMap = [];
        foreach ($timeline as $tItem) {
            if ($tItem['type'] === 'purchase' && !empty($tItem['raw_obj'])) {
                $purchaseStockMap[$tItem['raw_obj']->id] = [
                    'stock_before' => $tItem['stock_before'],
                    'stock_after'  => $tItem['stock_after'],
                ];
            } elseif ($tItem['type'] === 'shift_sale' && !empty($tItem['raw_obj'])) {
                $closingStockMap[$tItem['raw_obj']->id] = [
                    'stock_before' => $tItem['stock_before'],
                    'stock_after'  => $tItem['stock_after'],
                ];
            }
        }

        $unreconciledCount = $tank->dipReadings->where('is_reconciled', false)->count();
        $monthAdjustments = $tank->stockAdjustments
            ->filter(fn($a) => $a->adjustment_date && $a->adjustment_date->month === now()->month && $a->adjustment_date->year === now()->year);
        $monthAdjustedLiters = (float) $monthAdjustments->sum('adjustment_liters');
        $monthAdjustedImpact = (float) $monthAdjustments->sum('financial_impact');

        $readingContexts = StationSetting::READING_CONTEXTS;
        $reasonCategories = StationSetting::REASON_CATEGORIES;

        return view('tanks.show', compact(
            'tank',
            'tankConfig',
            'thresholds',
            'trendInfo',
            'purchases',
            'shiftClosings',
            'adjustments',
            'openingStock',
            'totalPurchasedLiters',
            'totalPurchasesAmount',
            'totalDispensedLiters',
            'totalSalesAmount',
            'totalAdjustmentLiters',
            'calculatedBookStock',
            'machineStats',
            'displayLedger',
            'purchaseStockMap',
            'closingStockMap',
            'unreconciledCount',
            'monthAdjustedLiters',
            'monthAdjustedImpact',
            'readingContexts',
            'reasonCategories'
        ));
    }

    /**
     * Show the form for editing the specified storage tank
     */
    public function edit(Tank $tank)
    {
        $locations = StationLocation::active()->orderBy('name')->get();
        $fuelProducts = Product::active()->fuel()->orderBy('name')->get();
        $tankConfig = StationSetting::getTankConfig();
        $toleranceModes = StationSetting::TOLERANCE_MODES;

        return view('tanks.edit', compact('tank', 'locations', 'fuelProducts', 'tankConfig', 'toleranceModes'));
    }

    /**
     * Update the specified storage tank
     */
    public function update(Request $request, Tank $tank)
    {
        foreach ([
            'capacity', 'dead_stock', 'current_dip_reading', 'max_dip_reading',
            'warning_tolerance_liters', 'critical_tolerance_liters', 'warning_tolerance_percent', 'critical_tolerance_percent',
        ] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $clean = str_replace(',', '', trim($request->input($field)));
                $request->merge([$field => $clean === '' ? null : $clean]);
            }
        }

        $validated = $request->validate([
            'location_id'                => ['required', 'integer', 'exists:station_locations,id'],
            'product_id'                 => ['required', 'integer', 'exists:products,id'],
            'tank_number'                => ['required', 'string', 'max:50'],
            'name'                       => ['required', 'string', 'max:255'],
            'tank_type'                  => ['required', 'string', Rule::in(['underground', 'aboveground'])],
            'capacity'                   => ['required', 'numeric', 'min:1'],
            'dead_stock'                 => ['nullable', 'numeric', 'min:0'],
            'current_dip_reading'        => ['nullable', 'numeric', 'min:0'],
            'max_dip_reading'            => ['nullable', 'numeric', 'min:0'],
            'use_default_tolerance'      => ['nullable', 'boolean'],
            'tolerance_mode'             => ['nullable', 'string', Rule::in(array_keys(StationSetting::TOLERANCE_MODES))],
            'warning_tolerance_liters'   => ['nullable', 'numeric', 'min:0'],
            'critical_tolerance_liters'  => ['nullable', 'numeric', 'min:0'],
            'warning_tolerance_percent'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'critical_tolerance_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status'                     => ['required', 'string', Rule::in(['active', 'maintenance', 'inactive'])],
            'description'                => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['dead_stock'] = $validated['dead_stock'] ?? 0;
        $validated['use_default_tolerance'] = $request->has('use_default_tolerance')
            ? $request->boolean('use_default_tolerance')
            : false;

        if ($validated['use_default_tolerance']) {
            $validated['tolerance_mode'] = null;
            $validated['warning_tolerance_liters'] = null;
            $validated['critical_tolerance_liters'] = null;
            $validated['warning_tolerance_percent'] = null;
            $validated['critical_tolerance_percent'] = null;
        }

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
     * Record a physical Dip Stick measurement, evaluate tolerance, and optionally auto-reconcile
     */
    public function recordDip(Request $request, Tank $tank)
    {
        foreach (['dip_cm', 'measured_liters', 'temperature_c', 'water_dip_mm'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $clean = str_replace(',', '', trim($request->input($field)));
                $request->merge([$field => $clean === '' ? null : $clean]);
            }
        }

        $hasChart = $tank->dipCharts()->exists();

        $validated = $request->validate([
            'dip_cm'              => ['required', 'numeric', 'min:0'],
            'measured_liters'     => [$hasChart ? 'nullable' : 'required', 'numeric', 'min:0'],
            'reading_date'        => ['required', 'date'],
            'reading_time'        => ['nullable', 'string', 'max:20'],
            'reading_context'     => ['nullable', 'string', Rule::in(array_keys(StationSetting::READING_CONTEXTS))],
            'reason_category'     => ['nullable', 'string', Rule::in(array_keys(StationSetting::REASON_CATEGORIES))],
            'temperature_c'       => ['nullable', 'numeric', 'min:-30', 'max:80'],
            'water_dip_mm'        => ['nullable', 'numeric', 'min:0'],
            'is_chart_calculated' => ['nullable', 'boolean'],
            'notes'               => ['nullable', 'string', 'max:500'],
        ]);

        $dip = $this->reconciliationService->recordDip($tank, $validated, auth()->id());

        $sign = (float) $dip->variance_liters > 0 ? '+' : '';
        $statusMsg = ucfirst(str_replace('_', ' ', (string) $dip->variance_status));
        $reconciledNote = $dip->is_reconciled ? ' Book stock was auto-reconciled within tolerance.' : '';

        return back()->with(
            'success',
            "Dip recorded for {$tank->name}: Physical " . number_format($dip->measured_liters, 1)
            . " L | Variance: {$sign}" . number_format($dip->variance_liters, 1) . " L ({$statusMsg}).{$reconciledNote}"
        );
    }

    /**
     * Owner / Incharge approval to reconcile tank book stock to a recorded physical dip reading
     */
    public function reconcileDip(Request $request, Tank $tank, TankDipReading $dipReading)
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isIncharge()) {
            abort(403, 'Only Owner or Incharge can reconcile tank book stock.');
        }

        if ((int) $dipReading->tank_id !== (int) $tank->id) {
            abort(404);
        }

        $validated = $request->validate([
            'reason_category' => ['nullable', 'string', Rule::in(array_keys(StationSetting::REASON_CATEGORIES))],
            'notes'           => ['nullable', 'string', 'max:500'],
        ]);

        $adj = $this->reconciliationService->reconcileDip(
            $dipReading,
            auth()->id(),
            $validated['reason_category'] ?? null,
            $validated['notes'] ?? null,
            false
        );

        $sign = (float) $adj->adjustment_liters >= 0 ? '+' : '';
        return back()->with(
            'success',
            "Tank '{$tank->name}' reconciled to " . number_format($adj->book_stock_after, 1)
            . " L ({$sign}" . number_format($adj->adjustment_liters, 1) . " L adjustment logged)."
        );
    }

    /**
     * JSON lookup endpoint for dip calibration chart interpolation & live tank variance thresholds
     */
    public function lookupDipChart(Request $request, Tank $tank)
    {
        $dipMm = (float) $request->input('dip_mm', 0);
        $lookup = $this->reconciliationService->interpolateLitersFromChart($tank, $dipMm);
        $tankConfig = StationSetting::getTankConfig();
        $thresholds = $this->reconciliationService->calculateToleranceThresholds($tank, (float) $tank->current_stock, $tankConfig);

        return response()->json([
            'tank_id'        => $tank->id,
            'dip_mm'         => $dipMm,
            'has_chart'      => $lookup['has_chart'],
            'liters'         => $lookup['liters'],
            'matched_exact'  => $lookup['matched_exact'],
            'out_of_range'   => $lookup['out_of_range'],
            'min_mm'         => $lookup['min_mm'],
            'max_mm'         => $lookup['max_mm'],
            'book_stock'     => (float) $tank->current_stock,
            'unit_cost'      => (float) ($tank->product?->purchase_price ?? 0),
            'warning_limit'  => $thresholds['warning_liters'],
            'critical_limit' => $thresholds['critical_liters'],
            'dip_unit'       => $tankConfig['dip_unit'],
        ]);
    }

    /**
     * Save or import per-tank dip calibration chart (mm -> Liters)
     */
    public function saveDipChart(Request $request, Tank $tank)
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isIncharge()) {
            abort(403, 'Only Owner or Incharge can manage tank calibration charts.');
        }

        $parsedPoints = [];

        // Mode 1: Bulk CSV / text lines ("dip_mm, liters" or whitespace/tab separated)
        if ($request->filled('bulk_csv')) {
            $lines = preg_split('/\r\n|\r|\n/', trim((string) $request->input('bulk_csv')));
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = preg_split('/[\s,;|\t]+/', $line);
                if (count($parts) >= 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                    $mm = round((float) $parts[0], 2);
                    $ltr = round((float) $parts[1], 2);
                    if ($mm >= 0 && $ltr >= 0) {
                        $parsedPoints[(string) $mm] = ['dip_mm' => $mm, 'liters' => $ltr];
                    }
                }
            }
        }

        // Mode 2: Structured table rows
        if ($request->has('rows') && is_array($request->input('rows'))) {
            foreach ($request->input('rows') as $row) {
                if (isset($row['dip_mm'], $row['liters']) && is_numeric($row['dip_mm']) && is_numeric($row['liters'])) {
                    $mm = round((float) $row['dip_mm'], 2);
                    $ltr = round((float) $row['liters'], 2);
                    if ($mm >= 0 && $ltr >= 0) {
                        $parsedPoints[(string) $mm] = ['dip_mm' => $mm, 'liters' => $ltr];
                    }
                }
            }
        }

        if (empty($parsedPoints)) {
            return back()->with('error', 'No valid calibration points (Dip Height & Liters) were provided.');
        }

        $replaceExisting = $request->boolean('replace_existing', true);

        DB::transaction(function () use ($tank, $parsedPoints, $replaceExisting) {
            if ($replaceExisting) {
                TankDipChart::where('tank_id', $tank->id)->delete();
            }

            foreach ($parsedPoints as $pt) {
                TankDipChart::updateOrCreate(
                    ['tank_id' => $tank->id, 'dip_mm' => $pt['dip_mm']],
                    ['liters' => $pt['liters']]
                );
            }

            $maxDip = TankDipChart::where('tank_id', $tank->id)->max('dip_mm');
            if ($maxDip !== null && ((float) $tank->max_dip_reading <= 0 || $replaceExisting)) {
                $tank->update(['max_dip_reading' => $maxDip]);
            }

            ActivityLog::log(
                action: 'updated',
                module: 'tanks',
                details: "Updated Dip Calibration Chart for {$tank->name} with " . count($parsedPoints) . " calibration point(s)."
            );
        });

        return back()->with('success', 'Calibration chart updated with ' . count($parsedPoints) . ' point(s) for ' . $tank->name . '.');
    }

    /**
     * Delete a single calibration chart point
     */
    public function deleteDipChartPoint(Tank $tank, TankDipChart $chartPoint)
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isIncharge()) {
            abort(403, 'Only Owner or Incharge can modify calibration charts.');
        }

        if ((int) $chartPoint->tank_id !== (int) $tank->id) {
            abort(404);
        }

        $chartPoint->delete();

        return back()->with('success', 'Calibration point removed.');
    }

    /**
     * Show client-configurable station-wide Tank & Dip Settings
     */
    public function settings()
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isIncharge()) {
            abort(403, 'Only Owner or Incharge can configure Tank & Dip settings.');
        }

        $tankConfig = StationSetting::getTankConfig();
        $toleranceModes = StationSetting::TOLERANCE_MODES;
        $reconciliationModes = StationSetting::RECONCILIATION_MODES;
        $bowserDipModes = StationSetting::BOWSER_DIP_MODES;

        return view('tanks.settings', compact(
            'tankConfig',
            'toleranceModes',
            'reconciliationModes',
            'bowserDipModes'
        ));
    }

    /**
     * Persist client-configurable station-wide Tank & Dip Settings
     */
    public function updateSettings(Request $request)
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isIncharge()) {
            abort(403, 'Only Owner or Incharge can update Tank & Dip settings.');
        }

        $validated = $request->validate([
            'dip_unit'                   => ['required', Rule::in(['mm', 'cm'])],
            'tolerance_mode'             => ['required', Rule::in(array_keys(StationSetting::TOLERANCE_MODES))],
            'warning_liters'             => ['required', 'numeric', 'min:0'],
            'critical_liters'            => ['required', 'numeric', 'min:0', 'gte:warning_liters'],
            'warning_percent'            => ['required', 'numeric', 'min:0', 'max:100'],
            'critical_percent'           => ['required', 'numeric', 'min:0', 'max:100', 'gte:warning_percent'],
            'consecutive_shortage_alert' => ['required', 'integer', 'min:0', 'max:30'],
            'reconciliation_mode'        => ['required', Rule::in(array_keys(StationSetting::RECONCILIATION_MODES))],
            'bowser_dip_mode'            => ['required', Rule::in(array_keys(StationSetting::BOWSER_DIP_MODES))],
            'show_variance_on_dashboard' => ['nullable', 'boolean'],
        ]);

        $map = [
            'tank_dip_unit'                   => $validated['dip_unit'],
            'tank_tolerance_mode'             => $validated['tolerance_mode'],
            'tank_warning_liters'             => $validated['warning_liters'],
            'tank_critical_liters'            => $validated['critical_liters'],
            'tank_warning_percent'            => $validated['warning_percent'],
            'tank_critical_percent'           => $validated['critical_percent'],
            'tank_consecutive_shortage_alert' => $validated['consecutive_shortage_alert'],
            'tank_reconciliation_mode'        => $validated['reconciliation_mode'],
            'tank_bowser_dip_mode'            => $validated['bowser_dip_mode'],
            'tank_show_variance_on_dashboard' => $request->boolean('show_variance_on_dashboard') ? '1' : '0',
        ];

        foreach ($map as $key => $val) {
            StationSetting::setValue($key, $val, auth()->id());
        }

        ActivityLog::log(
            action: 'updated',
            module: 'tanks',
            details: "Updated Tank & Dip Reconciliation Settings (Mode: {$validated['tolerance_mode']}, Warn: ±{$validated['warning_liters']}L/{$validated['warning_percent']}%, Policy: {$validated['reconciliation_mode']}, Bowser Dip: {$validated['bowser_dip_mode']})"
        );

        return redirect()->route('tanks.settings')->with('success', 'Tank & Dip Reconciliation settings updated successfully!');
    }
}
