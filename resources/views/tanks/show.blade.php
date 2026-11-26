@extends('layouts.app')

@section('title', 'Storage Tank - ' . $tank->name)

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Header Breadcrumb & Actions -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tanks.index') }}" class="text-decoration-none">Storage Tanks</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $tank->tank_number }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark font-monospace px-2.5 py-1 text-white fs-6">{{ $tank->tank_number }}</span>
                <h4 class="fw-bold mb-0 text-dark">{{ $tank->name }}</h4>
                {!! $tank->status_badge !!}
            </div>
            <div class="text-muted small mt-1">
                <span><i class="bi bi-fuel-pump text-danger me-1"></i><strong>{{ $tank->product?->name }}</strong></span>
                <span class="mx-1">&bull;</span>
                <span><i class="bi bi-geo-alt text-secondary me-1"></i>{{ $tank->location?->name ?? 'Main Site' }}</span>
                <span class="mx-1">&bull;</span>
                <span class="text-capitalize"><i class="bi bi-layer-backward me-1"></i>{{ $tank->tank_type }} Tank</span>
            </div>
        </div>

        @php
            $dipUnit = $tankConfig['dip_unit'] ?? 'mm';
            $wLim = $thresholds['warning_liters'] ?? 25;
            $cLim = $thresholds['critical_liters'] ?? 50;
            $unitCost = (float) ($tank->product?->purchase_price ?? 0);
            $hasChart = $tank->dipCharts->isNotEmpty();
        @endphp
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-outline-success btn-sm px-3 shadow-sm" onclick="openDipModal({{ $tank->id }}, '{{ addslashes($tank->name) }}', {{ $tank->current_dip_reading ?? 0 }}, {{ $tank->current_stock }}, {{ $unitCost }}, {{ $wLim }}, {{ $cLim }}, {{ $hasChart ? 'true' : 'false' }})">
                <i class="bi bi-rulers me-1"></i> Record Dip Reading
            </button>
            <a href="{{ route('purchases.create', ['tank_id' => $tank->id, 'product_id' => $tank->product_id]) }}" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
                <i class="bi bi-truck me-1"></i> Intake Fuel Delivery
            </a>
            <a href="{{ route('reports.tank-reconciliation', ['tank_id' => $tank->id]) }}" class="btn btn-outline-info btn-sm px-3 shadow-sm">
                <i class="bi bi-clipboard-data me-1"></i> Audit Report
            </a>
            <a href="{{ route('tanks.edit', $tank->id) }}" class="btn btn-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-pencil-square me-1"></i> Edit Specs
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show small py-2 px-3 mb-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(!empty($trendInfo['has_trend']))
        <div class="alert alert-danger border-0 shadow-sm small py-2.5 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <i class="bi bi-exclamation-octagon-fill me-1"></i>
                <strong>Consecutive Shortage Trend Alert:</strong>
                This tank has recorded <strong>{{ $trendInfo['streak'] }} consecutive negative dip readings</strong> without recovery or reconciliation. Inspect dispenser meter calibration, suction/return lines, or tank seals.
            </div>
            <span class="badge bg-danger text-white">Action Recommended</span>
        </div>
    @endif

    <!-- High-Level Fuel Reconciliation & Movement Banner -->
    <div class="row g-2.5 mb-3">
        <!-- 1. Opening Baseline -->
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-2.5 h-100">
                <div class="text-muted text-uppercase small fw-semibold" style="font-size: 0.68rem;">Opening Stock</div>
                <div class="fw-bold fs-6 text-dark font-monospace mt-1">
                    {{ number_format($openingStock, 1) }} <span class="small text-muted">L</span>
                </div>
                <div class="text-muted small" style="font-size: 0.68rem;">Initial Baseline Setup</div>
            </div>
        </div>

        <!-- 2. Total Fuel Received (+) -->
        <div class="col-6 col-md-3 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-2.5 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-success text-uppercase small fw-semibold" style="font-size: 0.68rem;">Total Fuel In (+)</span>
                    <span class="badge bg-success-subtle text-success rounded-pill" style="font-size: 0.65rem;">{{ $purchases->count() }} Deliver{{ $purchases->count() == 1 ? 'y' : 'ies' }}</span>
                </div>
                <div class="fw-bold fs-6 text-success font-monospace mt-1">
                    +{{ number_format($totalPurchasedLiters, 1) }} <span class="small text-muted">L</span>
                </div>
                <div class="text-muted small" style="font-size: 0.68rem;">Purchases: Rs. {{ number_format($totalPurchasesAmount) }}</div>
            </div>
        </div>

        <!-- 3. Total Fuel Dispensed (-) -->
        <div class="col-6 col-md-3 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-2.5 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-primary text-uppercase small fw-semibold" style="font-size: 0.68rem;">Total Fuel Out (-)</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill" style="font-size: 0.65rem;">{{ $shiftClosings->count() }} Shift{{ $shiftClosings->count() == 1 ? '' : 's' }}</span>
                </div>
                <div class="fw-bold fs-6 text-primary font-monospace mt-1">
                    -{{ number_format($totalDispensedLiters, 1) }} <span class="small text-muted">L</span>
                </div>
                <div class="text-muted small" style="font-size: 0.68rem;">Sales: Rs. {{ number_format($totalSalesAmount) }}</div>
            </div>
        </div>

        <!-- 4. Current Live Fuel Stock -->
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-2.5 h-100 border-start border-3 border-success">
                <div class="text-muted text-uppercase small fw-semibold" style="font-size: 0.68rem;">Live Tank Stock</div>
                <div class="fw-bold fs-6 text-dark font-monospace mt-1">
                    {{ number_format($tank->current_stock, 1) }} <span class="small text-muted">L</span>
                </div>
                <div class="text-success small fw-semibold" style="font-size: 0.68rem;">{{ $tank->fill_percentage }}% Filled</div>
            </div>
        </div>

        <!-- 5. Available Space (Ullage) -->
        <div class="col-12 col-md-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-2.5 h-100 border-start border-3 border-info">
                <div class="text-muted text-uppercase small fw-semibold" style="font-size: 0.68rem;">Available Space</div>
                <div class="fw-bold fs-6 text-info font-monospace mt-1">
                    {{ number_format($tank->available_capacity, 1) }} <span class="small text-muted">L</span>
                </div>
                <div class="text-muted small" style="font-size: 0.68rem;">Capacity: {{ number_format($tank->capacity) }} L</div>
            </div>
        </div>
    </div>

    <!-- Middle Row: Tank Gauge & Connected Pump Machines -->
    <div class="row g-3 mb-4">
        <!-- Left: Tank Volume Gauge & Specs Table -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3.5 h-100">
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-fuel-pump-fill text-primary"></i>
                    <span>LIVE TANK VOLUME &amp; CAPACITY</span>
                </h6>

                @php
                    $pct = $tank->fill_percentage;
                    $fillColor = $pct < 20 ? 'danger' : ($pct < 45 ? 'warning' : 'success');
                @endphp

                <!-- Progress & Percent -->
                <div class="p-3 bg-light rounded-3 border mb-3 text-center">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-secondary small fw-semibold">CURRENT TANK LEVEL</span>
                        <span class="badge bg-{{ $fillColor }} font-monospace px-2.5 py-1">{{ $pct }}%</span>
                    </div>

                    <div class="progress rounded-pill mb-2" style="height: 18px; background-color: #e2e8f0;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-{{ $fillColor }}"
                             role="progressbar" style="width: {{ $pct }}%;"
                             aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-baseline font-monospace small">
                        <span class="fw-bold text-dark fs-6">{{ number_format($tank->current_stock, 1) }} Liters</span>
                        <span class="text-muted">Total Cap: {{ number_format($tank->capacity) }} Liters</span>
                    </div>
                </div>

                <!-- Stats Breakdown Table -->
                <table class="table table-sm table-bordered small align-middle mb-0">
                    <tbody>
                        <tr>
                            <td class="text-secondary w-50">Storage Capacity:</td>
                            <td class="fw-bold font-monospace text-dark text-end">{{ number_format($tank->capacity, 1) }} Ltr</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Available Space (Ullage):</td>
                            <td class="fw-bold font-monospace text-info text-end">{{ number_format($tank->available_capacity, 1) }} Ltr</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Usable Fuel (Above Buffer):</td>
                            <td class="fw-bold font-monospace text-success text-end">{{ number_format($tank->usable_stock, 1) }} Ltr</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Dead Stock Buffer:</td>
                            <td class="font-monospace text-secondary text-end">{{ number_format($tank->dead_stock, 1) }} Ltr</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Current Dip Reading:</td>
                            <td class="fw-bold font-monospace text-dark text-end">
                                {{ $tank->current_dip_reading ? number_format($tank->current_dip_reading, 1) . ' ' . $dipUnit : 'Not recorded' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Last Physical Measured:</td>
                            <td class="fw-bold font-monospace text-dark text-end">
                                @if($tank->last_measured_liters !== null)
                                    {{ number_format($tank->last_measured_liters, 1) }} Ltr
                                    @if($tank->last_dip_date)
                                        <small class="text-muted fw-normal">({{ $tank->last_dip_date->format('d M') }})</small>
                                    @endif
                                @else
                                    Not recorded
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Latest Dip Variance:</td>
                            <td class="text-end">{!! $tank->variance_status_badge !!}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Active Tolerance Rule:</td>
                            <td class="font-monospace text-secondary text-end">
                                &plusmn;{{ number_format($wLim, 1) }} L Warn / &plusmn;{{ number_format($cLim, 1) }} L Crit
                                <span class="badge bg-light text-secondary border ms-1">{{ $thresholds['source'] === 'tank_custom' ? 'Custom' : 'Station Default' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary">This Month Reconciled Adj:</td>
                            <td class="font-monospace fw-bold text-end {{ $monthAdjustedLiters < 0 ? 'text-danger' : 'text-success' }}">
                                {{ $monthAdjustedLiters > 0 ? '+' : '' }}{{ number_format($monthAdjustedLiters, 1) }} L
                                <small class="text-muted fw-normal">(Rs. {{ number_format($monthAdjustedImpact, 0) }})</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Max Calibration Dip:</td>
                            <td class="font-monospace text-muted text-end">
                                {{ $tank->max_dip_reading ? number_format($tank->max_dip_reading, 1) . ' ' . $dipUnit : 'N/A' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Connected Pump Dispensers & Live Metrics -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3.5 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-speedometer2 text-primary"></i>
                        <span>CONNECTED PUMP MACHINES / DISPENSERS</span>
                    </h6>
                    <a href="{{ route('pumps.create') }}" class="btn btn-outline-primary btn-sm py-1 px-2.5" style="font-size: 0.75rem;">
                        <i class="bi bi-plus-circle me-1"></i> Add / Link Dispenser
                    </a>
                </div>

                @if($tank->pumps->isEmpty())
                    <div class="text-center py-4 bg-light rounded-3 border">
                        <i class="bi bi-speedometer text-muted fs-3 mb-2 d-block"></i>
                        <p class="text-muted small mb-0">No pump dispensers are currently linked to this storage tank.</p>
                        <a href="{{ route('pumps.create') }}" class="btn btn-link btn-sm text-decoration-none">Assign a pump machine</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle small mb-0 border">
                            <thead class="table-light">
                                <tr>
                                    <th>Dispenser Machine</th>
                                    <th>Code / Nozzle</th>
                                    <th class="text-end">Last Meter Reading</th>
                                    <th class="text-end">Total Sold (Ltr)</th>
                                    <th class="text-end">Revenue Generated</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($machineStats as $stat)
                                    @php $p = $stat['pump']; @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('pumps.show', $p->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">
                                                {{ $p->name }}
                                            </a>
                                        </td>
                                        <td class="font-monospace text-secondary">
                                            {{ $p->code ?: '#' . $p->id }}
                                            @if($p->nozzle_number)
                                                <span class="text-muted">({{ $p->nozzle_number }})</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            {{ number_format($p->last_reading, 2) }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-primary">
                                            {{ number_format($stat['liters_dispensed'], 2) }} L
                                        </td>
                                        <td class="text-end font-monospace text-secondary">
                                            Rs. {{ number_format($stat['sales_amount'], 2) }}
                                        </td>
                                        <td class="text-center">{!! $p->status_badge !!}</td>
                                        <td class="text-end">
                                            <a href="{{ route('pumps.show', $p->id) }}" class="btn btn-light btn-sm py-0.5 px-2 border" title="View Dispenser">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- MAIN TABBED DATA LOGS -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-3 mb-4">
        <ul class="nav nav-tabs nav-tabs-custom mb-3" id="tankTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold text-dark d-flex align-items-center gap-1.5" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger-pane" type="button" role="tab" aria-controls="ledger-pane" aria-selected="true">
                    <i class="bi bi-journal-text text-primary"></i>
                    <span>Fuel Stock Ledger (Inflow vs Outflow)</span>
                    <span class="badge bg-primary rounded-pill ms-1" style="font-size: 0.68rem;">{{ count($displayLedger) }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-dark d-flex align-items-center gap-1.5" id="machines-tab" data-bs-toggle="tab" data-bs-target="#machines-pane" type="button" role="tab" aria-controls="machines-pane" aria-selected="false">
                    <i class="bi bi-speedometer text-info"></i>
                    <span>Machine-wise Fuel Dispensed Sales Log</span>
                    <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.68rem;">{{ $shiftClosings->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-dark d-flex align-items-center gap-1.5" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases-pane" type="button" role="tab" aria-controls="purchases-pane" aria-selected="false">
                    <i class="bi bi-truck text-warning"></i>
                    <span>Fuel Deliveries &amp; Bowser Intake</span>
                    <span class="badge bg-success rounded-pill ms-1" style="font-size: 0.68rem;">{{ $purchases->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-dark d-flex align-items-center gap-1.5" id="dip-tab" data-bs-toggle="tab" data-bs-target="#dip-pane" type="button" role="tab" aria-controls="dip-pane" aria-selected="false">
                    <i class="bi bi-rulers text-success"></i>
                    <span>Physical Dip Stick Audit Log</span>
                    <span class="badge bg-light text-secondary border rounded-pill ms-1" style="font-size: 0.68rem;">{{ $tank->dipReadings->count() }}</span>
                    @if($unreconciledCount > 0)
                        <span class="badge bg-warning text-dark rounded-pill" style="font-size: 0.65rem;">{{ $unreconciledCount }} Pending</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-dark d-flex align-items-center gap-1.5" id="chart-tab" data-bs-toggle="tab" data-bs-target="#chart-pane" type="button" role="tab" aria-controls="chart-pane" aria-selected="false">
                    <i class="bi bi-table text-primary"></i>
                    <span>Dip Calibration Chart</span>
                    <span class="badge bg-light text-secondary border rounded-pill ms-1" style="font-size: 0.68rem;">{{ $tank->dipCharts->count() }} pts</span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="tankTabContent">

            <!-- TAB 1: Complete Chronological Stock Movement Ledger -->
            <div class="tab-pane fade show active" id="ledger-pane" role="tabpanel" aria-labelledby="ledger-tab" tabindex="0">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Running inventory timeline showing exact <strong>Stock Before</strong>, volume added/dispensed, and <strong>Stock After</strong> for every bowser intake and shift sale.
                    </p>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small mb-0 border">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Activity / Event</th>
                                <th>Source Reference</th>
                                <th>Machine / Vehicle</th>
                                <th class="text-end bg-light">Stock Before (Ltr)</th>
                                <th class="text-end text-success">Fuel IN (+)</th>
                                <th class="text-end text-danger">Fuel OUT (-)</th>
                                <th class="text-end bg-light fw-bold">Stock After (Ltr)</th>
                                <th>Logged By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($displayLedger as $row)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('d M, Y') }}</td>
                                    <td>{!! $row['type_badge'] !!}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $row['title'] }}</div>
                                        <div class="text-muted small" style="font-size: 0.7rem;">{{ $row['reference'] }}</div>
                                    </td>
                                    <td class="font-monospace text-secondary">{{ $row['machine'] }}</td>
                                    <td class="text-end font-monospace text-secondary bg-light">
                                        {{ number_format($row['stock_before'], 2) }} L
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-success">
                                        @if($row['in'] > 0)
                                            +{{ number_format($row['in'], 2) }} L
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-danger">
                                        @if($row['out'] > 0)
                                            -{{ number_format($row['out'], 2) }} L
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-dark bg-light">
                                        {{ number_format($row['stock_after'], 2) }} L
                                    </td>
                                    <td>{{ $row['user'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No stock movements recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: Machine-wise Dispensing Sales Log (Kis Machine ne kitna petrol sale kiya) -->
            <div class="tab-pane fade" id="machines-pane" role="tabpanel" aria-labelledby="machines-tab" tabindex="0">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Record of every shift closing from dispensers connected to this tank, showing starting &amp; ending meter readings and exact liters dispensed.
                    </p>
                    <a href="{{ route('shift-closings.create') }}" class="btn btn-outline-primary btn-sm py-1 px-2.5">
                        <i class="bi bi-plus-circle me-1"></i> Record Shift Closing
                    </a>
                </div>

                @if($shiftClosings->isEmpty())
                    <div class="text-center py-5 bg-light rounded-3 border">
                        <i class="bi bi-clock-history text-muted fs-2 mb-2 d-block"></i>
                        <h6 class="fw-bold text-dark mb-1">No Dispenser Sales Recorded Yet</h6>
                        <p class="text-muted small mb-2">Shift closings will appear here whenever fuel is sold and recorded through connected pump nozzles.</p>
                        <a href="{{ route('shift-closings.create') }}" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-plus-circle me-1"></i> Record First Shift Closing
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle small mb-0 border">
                            <thead class="table-light">
                                <tr>
                                    <th>Date &amp; Shift</th>
                                    <th>Dispenser Machine</th>
                                    <th class="text-end">Meter Start</th>
                                    <th class="text-end">Meter End</th>
                                    <th class="text-end text-primary">Liters Dispensed</th>
                                    <th class="text-end">Fuel Rate</th>
                                    <th class="text-end text-success">Total Sale Amount</th>
                                    <th class="text-end bg-light">Tank Stock Impact</th>
                                    <th>Cashier / Shift Incharge</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shiftClosings->sortByDesc('date') as $closing)
                                    @php $cStock = $closingStockMap[$closing->id] ?? null; @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $closing->date->format('d M, Y') }}</div>
                                            {!! $closing->shift_badge !!}
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark">{{ $closing->pump_machine ?: 'Pump Machine' }}</span>
                                            @if($closing->pump?->nozzle_number)
                                                <span class="text-muted font-monospace small">({{ $closing->pump->nozzle_number }})</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace text-secondary">
                                            {{ number_format($closing->start_reading, 2) }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            {{ number_format($closing->end_reading, 2) }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-primary fs-6">
                                            {{ number_format($closing->total_liters, 2) }} L
                                        </td>
                                        <td class="text-end font-monospace text-secondary">
                                            Rs. {{ number_format($closing->fuel_rate, 2) }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            Rs. {{ number_format($closing->total_sale, 2) }}
                                        </td>
                                        <td class="text-end font-monospace small bg-light">
                                            @if($cStock)
                                                <span class="text-secondary">{{ number_format($cStock['stock_before'], 1) }}L</span>
                                                <i class="bi bi-arrow-right text-muted mx-0.5" style="font-size: 0.65rem;"></i>
                                                <strong class="text-dark">{{ number_format($cStock['stock_after'], 1) }}L</strong>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $closing->cashier?->name ?? 'Cashier' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('shift-closings.show', $closing->id) }}" class="btn btn-light btn-sm py-0.5 px-2 border" title="View Shift Sheet">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- TAB 3: Fuel Deliveries & Bowser Purchases Intake -->
            <div class="tab-pane fade" id="purchases-pane" role="tabpanel" aria-labelledby="purchases-tab" tabindex="0">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Refinery and OMC bowser deliveries unloaded into this storage tank with before/after stock tracking.
                    </p>
                    <a href="{{ route('purchases.create', ['tank_id' => $tank->id, 'product_id' => $tank->product_id]) }}" class="btn btn-outline-success btn-sm py-1 px-2.5">
                        <i class="bi bi-truck me-1"></i> Intake Fuel Delivery
                    </a>
                </div>

                @if($purchases->isEmpty())
                    <div class="text-center py-5 bg-light rounded-3 border">
                        <i class="bi bi-truck text-muted fs-2 mb-2 d-block"></i>
                        <h6 class="fw-bold text-dark mb-1">No Fuel Deliveries Recorded Yet</h6>
                        <p class="text-muted small mb-2">Incoming bowser intakes unloaded into this tank will appear here.</p>
                        <a href="{{ route('purchases.create', ['tank_id' => $tank->id, 'product_id' => $tank->product_id]) }}" class="btn btn-success btn-sm px-3">
                            <i class="bi bi-truck me-1"></i> Record First Fuel Delivery
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle small mb-0 border">
                            <thead class="table-light">
                                <tr>
                                    <th>Delivery Date</th>
                                    <th>Invoice / Bill No.</th>
                                    <th>Supplier / OMC</th>
                                    <th>Bowser Vehicle</th>
                                    <th class="text-end bg-light">Tank Stock Before</th>
                                    <th class="text-end text-success">Delivered Volume (+)</th>
                                    <th class="text-end bg-light fw-bold">Tank Stock After</th>
                                    <th class="text-end">Purchase Rate</th>
                                    <th class="text-end">Total Bill Amount</th>
                                    <th>Logged By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchases->sortByDesc('purchase_date') as $pur)
                                    @php $pStock = $purchaseStockMap[$pur->id] ?? null; @endphp
                                    <tr>
                                        <td>{{ $pur->purchase_date->format('d M, Y') }}</td>
                                        <td class="font-monospace text-dark fw-semibold">
                                            <a href="{{ route('purchases.show', $pur->id) }}" class="text-decoration-none text-dark hover-primary">
                                                {{ $pur->invoice_no ?: ('PO #' . $pur->id) }}
                                            </a>
                                        </td>
                                        <td>{{ $pur->supplier?->name }}</td>
                                        <td class="font-monospace text-secondary">{{ $pur->vehicle_no ?: '-' }}</td>
                                        <td class="text-end font-monospace text-secondary bg-light">
                                            {{ $pStock ? number_format($pStock['stock_before'], 2) . ' L' : '-' }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-success fs-6">
                                            +{{ number_format($pur->quantity, 2) }} L
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark bg-light">
                                            {{ $pStock ? number_format($pStock['stock_after'], 2) . ' L' : '-' }}
                                        </td>
                                        <td class="text-end font-monospace text-secondary">Rs. {{ number_format($pur->unit_price, 2) }}</td>
                                        <td class="text-end font-monospace fw-bold text-dark">Rs. {{ number_format($pur->total_amount, 2) }}</td>
                                        <td>{{ $pur->creator?->name ?? 'Admin' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- TAB 4: Physical Dip Stick Audit & Book Variance -->
            <div class="tab-pane fade" id="dip-pane" role="tabpanel" aria-labelledby="dip-tab" tabindex="0">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Daily physical dip measurements vs book stock. Minor variances within tolerance (&plusmn;{{ number_format($wLim, 1) }} L) are normal; Owner/Incharge can reconcile book stock when needed.
                    </p>
                    <button type="button" class="btn btn-success btn-sm py-1 px-3" onclick="openDipModal({{ $tank->id }}, '{{ addslashes($tank->name) }}', {{ $tank->current_dip_reading ?? 0 }}, {{ $tank->current_stock }}, {{ $unitCost }}, {{ $wLim }}, {{ $cLim }}, {{ $hasChart ? 'true' : 'false' }})">
                        <i class="bi bi-plus-circle me-1"></i> Record New Dip Reading
                    </button>
                </div>

                @if($tank->dipReadings->isEmpty())
                    <div class="text-center py-5 bg-light rounded-3 border">
                        <i class="bi bi-rulers text-muted fs-2 mb-2 d-block"></i>
                        <h6 class="fw-bold text-dark mb-1">No Physical Dip Stick Readings Recorded</h6>
                        <p class="text-muted small mb-2">Measure physical fuel dip height ({{ $dipUnit }}) using dip stick to monitor thermal fluctuations, evaporation, or leakage.</p>
                        <button type="button" class="btn btn-success btn-sm px-3" onclick="openDipModal({{ $tank->id }}, '{{ addslashes($tank->name) }}', {{ $tank->current_dip_reading ?? 0 }}, {{ $tank->current_stock }}, {{ $unitCost }}, {{ $wLim }}, {{ $cLim }}, {{ $hasChart ? 'true' : 'false' }})">
                            <i class="bi bi-rulers me-1"></i> Take Initial Dip Reading
                        </button>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle small mb-0 border">
                            <thead class="table-light">
                                <tr>
                                    <th>Date &amp; Context</th>
                                    <th class="text-center">Dip ({{ $dipUnit }})</th>
                                    <th class="text-end">Physical (L)</th>
                                    <th class="text-end">Book (L)</th>
                                    <th class="text-end">Period &Delta;</th>
                                    <th class="text-end">Book Variance</th>
                                    <th class="text-center">Tolerance Status</th>
                                    <th>Reason / Notes</th>
                                    <th class="text-center">Reconciliation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tank->dipReadings as $dip)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $dip->reading_date->format('d M, Y') }}</div>
                                            <div class="text-muted" style="font-size: 0.7rem;">
                                                @if($dip->reading_time)
                                                    {{ \Carbon\Carbon::parse($dip->reading_time)->format('g:i A') }} &bull;
                                                @endif
                                                {{ $dip->context_label }}
                                            </div>
                                        </td>
                                        <td class="text-center font-monospace fw-bold">
                                            {{ number_format($dip->dip_cm, 2) }} {{ $dipUnit }}
                                            @if($dip->is_chart_calculated)
                                                <div><span class="badge bg-success-subtle text-success" style="font-size: 0.62rem;">Chart</span></div>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">{{ number_format($dip->measured_liters, 2) }} L</td>
                                        <td class="text-end font-monospace text-secondary">{{ number_format($dip->book_stock_liters, 2) }} L</td>
                                        <td class="text-end font-monospace {{ ($dip->period_variance_liters ?? 0) < 0 ? 'text-danger' : 'text-info' }}">
                                            @if($dip->period_variance_liters !== null)
                                                {{ $dip->period_variance_liters > 0 ? '+' : '' }}{{ number_format($dip->period_variance_liters, 1) }} L
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-bold">
                                            @if($dip->variance_liters == 0)
                                                <span class="text-success">0.00 L</span>
                                            @elseif($dip->variance_liters > 0)
                                                <span class="text-info">+{{ number_format($dip->variance_liters, 2) }} L</span>
                                            @else
                                                <span class="text-danger">{{ number_format($dip->variance_liters, 2) }} L</span>
                                            @endif
                                            @if($dip->financial_impact_amount !== null && abs($dip->variance_liters) > 0.01)
                                                <div class="text-muted fw-normal" style="font-size: 0.68rem;">
                                                    Rs. {{ number_format($dip->financial_impact_amount, 0) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">{!! $dip->status_badge !!}</td>
                                        <td class="text-muted small">
                                            @if($dip->reason_label)
                                                <div class="fw-semibold text-dark">{{ $dip->reason_label }}</div>
                                            @endif
                                            @if($dip->temperature_c !== null || $dip->water_dip_mm !== null)
                                                <div style="font-size: 0.68rem;">
                                                    @if($dip->temperature_c !== null) Temp: {{ $dip->temperature_c }}°C @endif
                                                    @if($dip->water_dip_mm !== null) | Water: {{ $dip->water_dip_mm }} {{ $dipUnit }} @endif
                                                </div>
                                            @endif
                                            <div>{{ $dip->notes ?: '-' }}</div>
                                            <small class="text-secondary">By: {{ $dip->recorder?->name ?? 'System' }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($dip->is_reconciled)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="bi bi-check2-all me-1"></i>Reconciled
                                                </span>
                                                @if($dip->adjusted_liters !== null && abs($dip->adjusted_liters) > 0.01)
                                                    <div class="text-muted font-monospace" style="font-size: 0.68rem;">
                                                        {{ $dip->adjusted_liters > 0 ? '+' : '' }}{{ number_format($dip->adjusted_liters, 1) }} L
                                                    </div>
                                                @endif
                                            @elseif(auth()->user()->isOwner() || auth()->user()->isIncharge())
                                                <button type="button" class="btn btn-outline-primary btn-sm py-0.5 px-2"
                                                        onclick="openReconcileModal({{ $tank->id }}, {{ $dip->id }}, {{ $tank->current_stock }}, {{ $dip->measured_liters }}, '{{ addslashes($dip->reason_category ?? '') }}')">
                                                    <i class="bi bi-sliders me-1"></i>Reconcile
                                                </button>
                                            @else
                                                <span class="badge bg-light text-muted border">Pending Audit</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- TAB 5: Tank Dip Calibration Chart (mm -> Liters) -->
            <div class="tab-pane fade" id="chart-pane" role="tabpanel" aria-labelledby="chart-tab" tabindex="0">
                <div class="row g-3">
                    <div class="col-12 col-lg-7">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-dark m-0">Calibration Strapping Points ({{ $dipUnit }} &rarr; Liters)</h6>
                            <span class="badge bg-light text-secondary border">{{ $tank->dipCharts->count() }} Points</span>
                        </div>

                        @if($tank->dipCharts->isEmpty())
                            <div class="text-center py-4 bg-light rounded-3 border">
                                <i class="bi bi-table text-muted fs-2 mb-2 d-block"></i>
                                <h6 class="fw-bold text-dark mb-1">No Dip Calibration Chart Configured</h6>
                                <p class="text-muted small mb-0">Add calibration points on the right (paste CSV/lines like <code>100, 1200</code>) so entering dip height automatically computes Liters using linear interpolation.</p>
                            </div>
                        @else
                            <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                                <table class="table table-sm table-bordered table-hover align-middle small mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Dip Height ({{ $dipUnit }})</th>
                                            <th class="text-end">Volume (Liters)</th>
                                            @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                                                <th class="text-center" style="width: 70px;">Remove</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tank->dipCharts as $pt)
                                            <tr>
                                                <td class="font-monospace fw-bold">{{ number_format($pt->dip_mm, 2) }} {{ $dipUnit }}</td>
                                                <td class="text-end font-monospace">{{ number_format($pt->liters, 2) }} L</td>
                                                @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                                                    <td class="text-center">
                                                        <form action="{{ route('tanks.dip-chart.delete', [$tank->id, $pt->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove calibration point?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1.5"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="col-12 col-lg-5">
                        @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                            <div class="p-3 rounded-3 border bg-light">
                                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-upload text-primary me-1"></i> Import / Update Dip Chart</h6>
                                <p class="text-muted small mb-2">Enter one calibration point per line as <code>dip_{{ $dipUnit }}, liters</code> (comma, tab, or space separated).</p>
                                <form action="{{ route('tanks.dip-chart.save', $tank->id) }}" method="POST">
                                    @csrf
                                    <div class="mb-2">
                                        <textarea name="bulk_csv" class="form-control form-control-sm font-monospace" rows="7" placeholder="0, 0&#10;500, 4200&#10;1000, 10800&#10;1500, 17900&#10;2000, 23600&#10;2200, 25000" required></textarea>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="replace_existing" value="1" id="replaceChartCheck" checked>
                                        <label class="form-check-label small" for="replaceChartCheck">
                                            Replace existing chart points (uncheck to merge/append)
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                                        <i class="bi bi-save me-1"></i> Save Calibration Chart
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

@if(auth()->user()->isOwner() || auth()->user()->isIncharge())
<!-- Modal: Reconcile Book Stock to Physical Dip -->
<div class="modal fade" id="reconcileDipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white py-2.5 px-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-sliders me-1"></i> Reconcile Book Stock to Physical Dip</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="reconcileDipForm" method="POST" action="">
                @csrf
                <div class="modal-body p-3.5">
                    <div class="p-2.5 rounded-3 bg-light border mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-secondary">Current Book Stock:</span>
                            <strong id="recBookStock" class="font-monospace">0 L</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-secondary">Target Physical Dip:</span>
                            <strong id="recPhysicalStock" class="font-monospace">0 L</strong>
                        </div>
                        <div class="d-flex justify-content-between pt-1 border-top">
                            <span class="fw-bold">Stock Adjustment:</span>
                            <strong id="recAdjustment" class="font-monospace">0 L</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Reconciliation Reason</label>
                        <select name="reason_category" id="recReasonCategory" class="form-select form-select-sm" required>
                            @foreach($reasonCategories as $rKey => $rLabel)
                                <option value="{{ $rKey }}">{{ $rLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-secondary">Approval Remarks <span class="text-muted">(Optional)</span></label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. Month-end evaporation / thermal variance reconciliation approved..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Approve &amp; Reconcile Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openReconcileModal(tankId, dipId, bookStock, physicalStock, defaultReason) {
    const form = document.getElementById('reconcileDipForm');
    form.action = `{{ url('tanks') }}/${tankId}/dip-readings/${dipId}/reconcile`;

    const diff = Number(physicalStock) - Number(bookStock);
    const sign = diff > 0 ? '+' : '';
    document.getElementById('recBookStock').textContent = Number(bookStock).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' L';
    document.getElementById('recPhysicalStock').textContent = Number(physicalStock).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' L';

    const adjEl = document.getElementById('recAdjustment');
    adjEl.textContent = `${sign}${diff.toFixed(1)} L (${diff >= 0 ? 'Gain' : 'Shrinkage Loss'})`;
    adjEl.className = 'font-monospace ' + (diff < 0 ? 'text-danger' : 'text-success');

    const sel = document.getElementById('recReasonCategory');
    if (defaultReason && sel) {
        sel.value = defaultReason;
    } else if (sel) {
        sel.value = diff < 0 ? 'evaporation_loss' : 'temperature_variation';
    }

    const modal = new bootstrap.Modal(document.getElementById('reconcileDipModal'));
    modal.show();
}
</script>
@endpush
@endif

@include('tanks.partials.dip_modal')
@endsection
