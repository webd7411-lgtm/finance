<?php

namespace App\Http\Controllers;

use App\Models\Pump;
use App\Models\PumpMeterReading;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ShiftClosing;
use App\Models\ActivityLog;
use App\Models\Tank;
use App\Models\StationLocation;
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
        $query = Pump::with(['product', 'tank', 'location'])->withCount(['shiftClosings', 'meterReadings']);

        // Filter by Status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by Dispenser Unit / Machine
        if ($request->filled('dispenser_unit') && $request->dispenser_unit !== 'all') {
            $query->where('dispenser_unit', $request->dispenser_unit);
        }

        // Filter by Fuel Product
        if ($request->filled('product_id') && $request->product_id !== 'all') {
            $query->where('product_id', $request->product_id);
        }

        // Filter by Storage Tank
        if ($request->filled('tank_id') && $request->tank_id !== 'all') {
            $query->where('tank_id', $request->tank_id);
        }

        // Search by Name, Dispenser Unit, Code, Tank, or Nozzle
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('dispenser_unit', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('tank_no', 'like', "%{$search}%")
                  ->orWhere('nozzle_number', 'like', "%{$search}%")
                  ->orWhereHas('tank', fn($tq) => $tq->where('name', 'like', "%{$search}%")->orWhere('tank_number', 'like', "%{$search}%"));
            });
        }

        $pumps = $query->orderByRaw("COALESCE(dispenser_unit, name) ASC")->orderBy('nozzle_number')->paginate(12)->withQueryString();

        // High-level statistics
        $totalPumps = Pump::count();
        $activePumps = Pump::where('status', 'active')->count();
        $maintenancePumps = Pump::where('status', 'maintenance')->count();
        $totalDispensedLiters = (float) ShiftClosing::sum('total_liters');
        $todayDispensedLiters = (float) ShiftClosing::whereDate('date', now()->toDateString())->sum('total_liters');
        $totalRevenueGenerated = (float) ShiftClosing::sum('total_sale');

        $fuelProducts = Product::active()->fuel()->orderBy('name')->get(['id', 'name', 'sale_price', 'current_stock', 'unit']);
        $tanks = Tank::active()->with('product')->orderBy('tank_number')->get();
        $dispenserUnits = Pump::whereNotNull('dispenser_unit')->where('dispenser_unit', '!=', '')->distinct()->pluck('dispenser_unit')->filter()->values();

        return view('pumps.index', compact(
            'pumps',
            'totalPumps',
            'activePumps',
            'maintenancePumps',
            'totalDispensedLiters',
            'todayDispensedLiters',
            'totalRevenueGenerated',
            'fuelProducts',
            'tanks',
            'dispenserUnits'
        ));
    }

    /**
     * Show the form for creating a new pump machine
     */
    public function create(Request $request)
    {
        $fuelProducts = Product::active()->fuel()->orderBy('name')->get();
        $tanks = Tank::with(['product', 'location'])->active()->orderBy('tank_number')->get();
        $locations = StationLocation::active()->orderBy('name')->get();
        $existingTanks = Pump::whereNotNull('tank_no')->distinct()->pluck('tank_no')->filter()->values();
        $existingDispenserUnits = Pump::whereNotNull('dispenser_unit')->where('dispenser_unit', '!=', '')->distinct()->pluck('dispenser_unit')->filter()->values();
        $prefilledDispenser = $request->query('dispenser_unit', $request->query('machine'));

        return view('pumps.create', compact('fuelProducts', 'tanks', 'locations', 'existingTanks', 'existingDispenserUnits', 'prefilledDispenser'));
    }

    /**
     * Store a newly created pump machine
     */
    public function store(Request $request)
    {
        // 1. Check if multiple nozzles submitted in nozzles array
        if ($request->has('nozzles') && is_array($request->input('nozzles')) && count($request->input('nozzles')) > 0) {
            $nozzlesInput = $request->input('nozzles');
            foreach ($nozzlesInput as $idx => $noz) {
                foreach (['initial_reading', 'last_reading'] as $field) {
                    if (isset($noz[$field]) && is_string($noz[$field])) {
                        $clean = str_replace(',', '', trim($noz[$field]));
                        $nozzlesInput[$idx][$field] = $clean === '' ? 0 : $clean;
                    }
                }
            }
            $request->merge(['nozzles' => $nozzlesInput]);

            $validated = $request->validate([
                'name'                      => ['required', 'string', 'max:255'],
                'code'                      => ['nullable', 'string', 'max:50'],
                'location_id'               => ['nullable', 'integer', 'exists:station_locations,id'],
                'meter_type'                => ['required', 'string', Rule::in(['electronic', 'mechanical'])],
                'status'                    => ['required', 'string', Rule::in(['active', 'maintenance', 'inactive'])],
                'description'               => ['nullable', 'string', 'max:1000'],
                'nozzles'                   => ['required', 'array', 'min:1'],
                'nozzles.*.product_id'      => ['required', 'integer', 'exists:products,id'],
                'nozzles.*.tank_id'         => ['nullable', 'integer', 'exists:tanks,id'],
                'nozzles.*.tank_no'         => ['nullable', 'string', 'max:255'],
                'nozzles.*.name'            => ['nullable', 'string', 'max:255'],
                'nozzles.*.nozzle_number'   => ['nullable', 'string', 'max:100'],
                'nozzles.*.code'            => ['nullable', 'string', 'max:50'],
                'nozzles.*.initial_reading' => ['required', 'numeric', 'min:0'],
                'nozzles.*.last_reading'    => ['nullable', 'numeric', 'min:0'],
            ], [
                'name.required'                      => 'Pump machine / dispenser name is required.',
                'nozzles.required'                   => 'Please configure at least one nozzle for this machine.',
                'nozzles.min'                        => 'Please configure at least one nozzle for this machine.',
                'nozzles.*.product_id.required'      => 'Please select the fuel product for each nozzle.',
                'nozzles.*.initial_reading.required' => 'Initial / Starting meter reading is required for each nozzle.',
            ]);

            $machineName = trim($validated['name']);
            $machineCode = !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null;
            $locationId = !empty($validated['location_id']) ? (int) $validated['location_id'] : null;
            $meterType = $validated['meter_type'];
            $status = $validated['status'];
            $description = $validated['description'] ?? null;

            $createdPumps = DB::transaction(function () use ($validated, $machineName, $machineCode, $locationId, $meterType, $status, $description) {
                $pumps = [];

                foreach ($validated['nozzles'] as $index => $nData) {
                    $num = $index + 1;
                    $product = Product::find($nData['product_id']);

                    // Resolve Tank
                    $tankId = !empty($nData['tank_id']) ? (int) $nData['tank_id'] : null;
                    $tankNo = $nData['tank_no'] ?? null;

                    if ($tankId) {
                        $tankObj = Tank::find($tankId);
                        if ($tankObj) {
                            $tankNo = $tankObj->name;
                            if (!$locationId && $tankObj->location_id) {
                                $locationId = $tankObj->location_id;
                            }
                        }
                    }

                    // Nozzle Number
                    $nozzleNumber = !empty($nData['nozzle_number']) ? trim($nData['nozzle_number']) : (string) $num;

                    // Nozzle Name
                    $customName = !empty($nData['name']) ? trim($nData['name']) : null;
                    if (!$customName) {
                        $prodName = $product ? $product->name : 'Fuel';
                        $customName = "{$machineName} - Nozzle {$nozzleNumber} ({$prodName})";
                    }

                    // Nozzle Code
                    $nozzleCode = !empty($nData['code']) ? strtoupper(trim($nData['code'])) : null;
                    if (!$nozzleCode && $machineCode) {
                        $nozzleCode = "{$machineCode}-N{$nozzleNumber}";
                    }

                    // Ensure unique code if provided or generated
                    if ($nozzleCode && Pump::where('code', $nozzleCode)->exists()) {
                        $nozzleCode = $nozzleCode . '-' . $num;
                        if (Pump::where('code', $nozzleCode)->exists()) {
                            $nozzleCode = $nozzleCode . '-' . rand(10, 99);
                        }
                    }

                    $initialReading = (float) $nData['initial_reading'];
                    $lastReading = isset($nData['last_reading']) && (float)$nData['last_reading'] > 0
                        ? (float) $nData['last_reading']
                        : $initialReading;

                    $pump = Pump::create([
                        'location_id'     => $locationId,
                        'dispenser_unit'  => $machineName,
                        'tank_id'         => $tankId,
                        'name'            => $customName,
                        'code'            => $nozzleCode,
                        'nozzle_number'   => $nozzleNumber,
                        'meter_type'      => $meterType,
                        'product_id'      => $nData['product_id'],
                        'tank_no'         => $tankNo,
                        'initial_reading' => $initialReading,
                        'last_reading'    => $lastReading,
                        'status'          => $status,
                        'description'     => $description ? "{$machineName} • {$customName}. {$description}" : "{$machineName} • {$customName}",
                    ]);

                    // Create baseline meter reading record
                    PumpMeterReading::create([
                        'pump_id'          => $pump->id,
                        'reading_date'     => now()->toDateString(),
                        'reading_type'     => 'initial_setup',
                        'start_reading'    => $initialReading,
                        'end_reading'      => $lastReading,
                        'volume_dispensed' => 0,
                        'fuel_rate'        => $product?->sale_price ?? 0,
                        'sales_amount'     => 0,
                        'recorded_by'      => auth()->id(),
                        'remarks'          => "Initial dispenser nozzle installation meter baseline setup ({$machineName})",
                    ]);

                    ActivityLog::log(
                        'Created',
                        'Pump Machine',
                        "Added dispenser nozzle '{$pump->name}' (" . ($pump->code ?: '#' . $pump->id) . ") under '{$machineName}' with initial meter reading of " . number_format($initialReading, 2)
                    );

                    $pumps[] = $pump;
                }

                return $pumps;
            });

            // Handle AJAX submission from Shift Closing modal
            if ($request->ajax() || $request->wantsJson()) {
                $primaryPump = $createdPumps[0];
                $primaryPump->load('product');
                return response()->json([
                    'success' => true,
                    'message' => 'Pump machine with ' . count($createdPumps) . ' nozzles added successfully.',
                    'pump'    => [
                        'id'            => $primaryPump->id,
                        'name'          => $primaryPump->name,
                        'code'          => $primaryPump->code,
                        'last_reading'  => (float) $primaryPump->last_reading,
                        'product_id'    => $primaryPump->product_id,
                        'product_name'  => $primaryPump->product?->name ?? 'Fuel',
                        'fuel_rate'     => (float) ($primaryPump->product?->sale_price ?? 0),
                        'unit'          => $primaryPump->product?->unit_label ?? 'Ltr',
                        'current_stock' => (float) ($primaryPump->product?->current_stock ?? 0),
                        'tank_no'       => $primaryPump->tank_no ?? 'Storage Tank',
                    ],
                    'pumps'   => $createdPumps,
                ]);
            }

            $firstPump = $createdPumps[0];
            $count = count($createdPumps);
            return redirect()->route('pumps.show', $firstPump->id)
                ->with('success', "Pump Machine '{$machineName}' registered with {$count} nozzle(s) successfully.");
        }

        // 2. Fallback for single-nozzle creation (backward compatibility)
        foreach (['initial_reading', 'last_reading'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $clean = str_replace(',', '', trim($request->input($field)));
                $request->merge([$field => $clean === '' ? 0 : $clean]);
            }
        }

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'dispenser_unit'  => ['nullable', 'string', 'max:255'],
            'code'            => ['nullable', 'string', 'max:50', 'unique:pumps,code'],
            'location_id'     => ['nullable', 'integer', 'exists:station_locations,id'],
            'tank_id'         => ['nullable', 'integer', 'exists:tanks,id'],
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

        // Auto sync tank details
        $tankId = !empty($validated['tank_id']) ? (int) $validated['tank_id'] : null;
        $locationId = !empty($validated['location_id']) ? (int) $validated['location_id'] : null;
        $tankNo = $validated['tank_no'] ?? null;

        if ($tankId) {
            $tankObj = Tank::find($tankId);
            if ($tankObj) {
                $tankNo = $tankObj->name;
                if (!$locationId && $tankObj->location_id) {
                    $locationId = $tankObj->location_id;
                }
            }
        }

        $initialReading = (float) $validated['initial_reading'];
        $lastReading = isset($validated['last_reading']) && (float)$validated['last_reading'] > 0
            ? (float) $validated['last_reading']
            : $initialReading;

        $pump = DB::transaction(function () use ($validated, $initialReading, $lastReading, $tankId, $locationId, $tankNo) {
            $pump = Pump::create([
                'location_id'     => $locationId,
                'tank_id'         => $tankId,
                'name'            => $validated['name'],
                'dispenser_unit'  => $validated['dispenser_unit'] ?? $validated['name'],
                'code'            => !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
                'product_id'      => $validated['product_id'],
                'tank_no'         => $tankNo,
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
        $pump->load(['product', 'tank', 'location']);

        // Sibling nozzles belonging to the same dispenser machine
        $siblingNozzles = !empty($pump->dispenser_unit)
            ? Pump::with(['product', 'tank'])
                ->where('dispenser_unit', $pump->dispenser_unit)
                ->orderBy('nozzle_number')
                ->orderBy('name')
                ->get()
            : collect([$pump]);

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
            'siblingNozzles',
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
        $tanks = Tank::with(['product', 'location'])->active()->orderBy('tank_number')->get();
        $locations = StationLocation::active()->orderBy('name')->get();
        $existingTanks = Pump::whereNotNull('tank_no')->distinct()->pluck('tank_no')->filter()->values();
        $existingDispenserUnits = Pump::whereNotNull('dispenser_unit')->where('dispenser_unit', '!=', '')->distinct()->pluck('dispenser_unit')->filter()->values();

        return view('pumps.edit', compact('pump', 'fuelProducts', 'tanks', 'locations', 'existingTanks', 'existingDispenserUnits'));
    }

    /**
     * Update the pump machine
     */
    public function update(Request $request, Pump $pump)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'dispenser_unit' => ['nullable', 'string', 'max:255'],
            'code'           => ['nullable', 'string', 'max:50', Rule::unique('pumps', 'code')->ignore($pump->id)],
            'location_id'    => ['nullable', 'integer', 'exists:station_locations,id'],
            'tank_id'        => ['nullable', 'integer', 'exists:tanks,id'],
            'product_id'     => ['required', 'integer', 'exists:products,id'],
            'tank_no'        => ['nullable', 'string', 'max:255'],
            'nozzle_number'  => ['nullable', 'string', 'max:100'],
            'meter_type'     => ['required', 'string', Rule::in(['electronic', 'mechanical'])],
            'status'         => ['required', 'string', Rule::in(['active', 'maintenance', 'inactive'])],
            'description'    => ['nullable', 'string', 'max:1000'],
        ]);

        $tankId = !empty($validated['tank_id']) ? (int) $validated['tank_id'] : null;
        $locationId = !empty($validated['location_id']) ? (int) $validated['location_id'] : null;
        $tankNo = $validated['tank_no'] ?? null;

        if ($tankId) {
            $tankObj = Tank::find($tankId);
            if ($tankObj) {
                $tankNo = $tankObj->name;
                if (!$locationId && $tankObj->location_id) {
                    $locationId = $tankObj->location_id;
                }
            }
        }

        $pump->update([
            'location_id'    => $locationId,
            'tank_id'        => $tankId,
            'name'           => $validated['name'],
            'dispenser_unit' => $validated['dispenser_unit'] ?? null,
            'code'           => !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'product_id'     => $validated['product_id'],
            'tank_no'        => $tankNo,
            'nozzle_number'  => $validated['nozzle_number'] ?? null,
            'meter_type'     => $validated['meter_type'],
            'status'         => $validated['status'],
            'description'    => $validated['description'] ?? null,
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

            // If test volume was NOT returned to the tank, decrement both tank and product stock
            if (!$returnedToTank && $volumeTested > 0) {
                if ($pump->tank_id) {
                    $tank = Tank::lockForUpdate()->find($pump->tank_id);
                    if ($tank) {
                        $tank->current_stock = max(0, $tank->current_stock - $volumeTested);
                        $tank->save();
                    }
                }

                if ($pump->product_id) {
                    $product = Product::lockForUpdate()->find($pump->product_id);
                    if ($product) {
                        $product->current_stock = max(0, $product->current_stock - $volumeTested);
                        $product->save();
                    }
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
