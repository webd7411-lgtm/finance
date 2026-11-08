@extends('layouts.app')

@section('title', $pump->name . ' - Machine Details & Readings')
@section('page_title', $pump->name)

@section('page_badge')
    <div class="d-flex align-items-center gap-1.5 flex-wrap">
        <span class="badge bg-white text-dark border font-monospace px-2.5 py-1 rounded-pill fw-bold" style="font-size: 0.75rem;">
            <i class="bi bi-tag me-1 text-primary"></i> {{ $pump->code ?: 'PUMP-' . str_pad($pump->id, 2, '0', STR_PAD_LEFT) }}
        </span>
        @if($pump->status === 'active')
            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem; font-weight: 600;">
                <span class="status-pulse-dot bg-success pulse me-1"></span>Active
            </span>
        @elseif($pump->status === 'maintenance')
            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem; font-weight: 600;">
                <i class="bi bi-tools me-1"></i>Maintenance
            </span>
        @else
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem; font-weight: 600;">
                <i class="bi bi-pause-circle me-1"></i>Inactive
            </span>
        @endif
        <span class="badge bg-light text-secondary border px-2.5 py-1 rounded-pill text-capitalize" style="font-size: 0.75rem;">
            <i class="bi bi-cpu me-1 text-info"></i>{{ $pump->meter_type }} Totalizer
        </span>
    </div>
@endsection

@section('page_actions')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('pumps.index') }}" class="btn btn-outline-secondary btn-sm px-2.5 py-1.5 rounded-3 fw-semibold shadow-xs" style="font-size: 0.81rem;">
            <i class="bi bi-arrow-left me-1"></i> All Machines
        </a>
        <button type="button" class="btn btn-outline-warning btn-sm px-3 py-1.5 rounded-3 fw-semibold text-dark shadow-xs" data-bs-toggle="modal" data-bs-target="#adjustModal" style="font-size: 0.81rem;">
            <i class="bi bi-tools text-warning me-1"></i> Calibrate / Adjust Meter
        </button>
        <a href="{{ route('purchases.create', ['pump_id' => $pump->id, 'product_id' => $pump->product_id]) }}" class="btn btn-outline-success btn-sm px-3 py-1.5 rounded-3 fw-semibold shadow-xs" style="font-size: 0.81rem;">
            <i class="bi bi-truck text-success me-1"></i> Purchase Fuel for Tank
        </a>
        <a href="{{ route('shift-closings.create', ['pump_id' => $pump->id]) }}" class="btn btn-primary btn-sm px-3 py-1.5 rounded-3 fw-semibold text-white shadow-xs" style="font-size: 0.81rem; background-color: #2563eb; border-color: #2563eb;">
            <i class="bi bi-clock-history me-1"></i> Record Shift Closing
        </a>
        <a href="{{ route('pumps.edit', $pump->id) }}" class="btn btn-outline-dark btn-sm px-2.5 py-1.5 rounded-3 shadow-xs" title="Edit Machine">
            <i class="bi bi-pencil"></i>
        </a>
    </div>
@endsection

@push('styles')
<!-- Google Font: Plus Jakarta Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

<style>
    body, html, button, input, optgroup, select, textarea, table, th, td, h1, h2, h3, h4, h5, h6, p, span, a, label {
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif !important;
    }
    .bi, [class^="bi-"], [class*=" bi-"] {
        font-family: "bootstrap-icons" !important;
    }

    .shadow-xs {
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .card-custom {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }

    /* 5-Cards Unified Grid in ONE Single Row */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.55rem;
    }
    @media (max-width: 1199px) { .metrics-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 767px) { .metrics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 480px) { .metrics-grid { grid-template-columns: 1fr; } }

    .metric-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.55rem 0.8rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 72px;
    }
    .metric-card:hover {
        transform: translateY(-1.5px);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        border-color: #cbd5e1;
    }
    .metric-card-valuation {
        background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
        border: 1px solid #1d4ed8;
        color: #ffffff;
    }
    .metric-card-valuation:hover {
        border-color: #1e40af;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
    }

    .metric-label {
        font-size: 0.73rem;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
    }
    .metric-icon-box {
        width: 30px;
        height: 30px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .metric-value {
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1.15;
        margin: 2px 0 1px 0;
        letter-spacing: -0.02em;
    }
    .metric-sub {
        font-size: 0.68rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #64748b;
        font-weight: 500;
    }

    /* Stylized Digital Totalizer LCD Bezel */
    .meter-bezel-hero {
        background: linear-gradient(180deg, #0b1120 0%, #030712 100%);
        border: 2px solid #1e293b;
        border-radius: 12px;
        padding: 0.85rem 1.25rem;
        box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.7), 0 4px 12px rgba(0, 0, 0, 0.08);
        position: relative;
        overflow: hidden;
    }
    .meter-bezel-hero::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1.5px;
        background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.5), transparent);
    }
    .meter-digits-hero {
        font-family: 'Courier New', Courier, monospace !important;
        font-size: 2.2rem;
        font-weight: 900;
        color: #38bdf8;
        letter-spacing: 3px;
        line-height: 1.1;
        text-shadow: 0 0 14px rgba(56, 189, 248, 0.4);
    }

    /* Pulse Status Dot */
    .status-pulse-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .status-pulse-dot.pulse {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
        animation: pulseAnimation 2s infinite;
    }
    @keyframes pulseAnimation {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    .table-custom thead th {
        background: #f8fafc;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.5rem 0.75rem;
        white-space: nowrap;
        vertical-align: middle;
    }
    .table-custom tbody td {
        padding: 0.55rem 0.75rem;
        vertical-align: middle;
        font-size: 0.79rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <!-- Top Key Metrics Ribbon (5 Unified Cards) -->
    <div class="metrics-grid mb-2.5">
        <!-- Metric 1: Live Reading -->
        <div class="metric-card">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label">Live Meter Reading</span>
                <div class="metric-icon-box bg-info bg-opacity-10 text-info">
                    <i class="bi bi-speedometer2"></i>
                </div>
            </div>
            <div class="metric-value text-info-emphasis">{{ number_format($pump->last_reading, 2) }}</div>
            <div class="metric-sub">Baseline: {{ number_format($pump->initial_reading, 2) }}</div>
        </div>

        <!-- Metric 2: Volume Dispensed -->
        <div class="metric-card">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label">Dispensed Volume</span>
                <div class="metric-icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-fuel-pump"></i>
                </div>
            </div>
            <div class="metric-value text-primary">{{ number_format($totalDispensed, 2) }} <span class="fs-6 fw-normal text-muted">Ltr</span></div>
            <div class="metric-sub">Across {{ $shiftsCount }} shift sessions</div>
        </div>

        <!-- Metric 3: Total Sales Generated -->
        <div class="metric-card">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label">Shift Sales Generated</span>
                <div class="metric-icon-box bg-success bg-opacity-10 text-success">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
            <div class="metric-value text-success">Rs. {{ number_format($totalRevenue, 2) }}</div>
            <div class="metric-sub">Revenue via meter sales</div>
        </div>

        <!-- Metric 4: Tank Available Stock -->
        <div class="metric-card">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label">Available Tank Stock</span>
                <div class="metric-icon-box bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-database"></i>
                </div>
            </div>
            <div class="metric-value text-warning-emphasis">
                {{ number_format($stockSync['current_stock'], 2) }} <span class="fs-6 fw-normal text-muted">{{ $stockSync['unit'] }}</span>
            </div>
            <div class="metric-sub">In {{ $stockSync['tank_or_shelf'] }}</div>
        </div>

        <!-- Metric 5: Fuel Sale Price (Accent Card) -->
        <div class="metric-card metric-card-valuation">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label text-white text-opacity-75">Fuel Sale Rate</span>
                <div class="metric-icon-box bg-white bg-opacity-20 text-white">
                    <i class="bi bi-tags"></i>
                </div>
            </div>
            <div class="metric-value text-white">
                <span style="font-size: 0.82em; opacity: 0.88; font-weight: 600;">Rs.</span> {{ number_format($stockSync['fuel_rate'], 2) }}
            </div>
            <div class="metric-sub text-white text-opacity-75">Per {{ $stockSync['unit'] }} official rate</div>
        </div>
    </div>

    <!-- Machine Hardware Specs & Live Bezel Showcase Row -->
    <div class="row g-2.5 mb-2.5">
        <!-- Left: Live Digital Bezel & Quick Controls -->
        <div class="col-12 col-lg-5">
            <div class="card-custom h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-light text-dark font-monospace border px-2 py-1 fw-bold small">
                        {{ $pump->code ?: 'PUMP-' . str_pad($pump->id, 2, '0', STR_PAD_LEFT) }}
                    </span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-0.5" style="font-size: 0.72rem;">
                        <i class="bi bi-fuel-pump me-1"></i>{{ $stockSync['product_name'] }}
                    </span>
                </div>

                <!-- Digital LCD Totalizer Bezel -->
                <div class="meter-bezel-hero mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-uppercase fw-semibold" style="font-size: 0.65rem; color: #64748b; letter-spacing: 1px;">
                            <i class="bi bi-speedometer2 text-info me-1"></i>DISPENSER TOTALIZER COUNTER
                        </span>
                        <span class="badge bg-dark text-info border border-info border-opacity-25" style="font-size: 0.6rem;">LIVE STATUS</span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="meter-digits-hero">
                            {{ number_format($pump->last_reading, 2) }}
                        </div>
                        <span class="text-info text-opacity-75 fw-bold fs-5 font-monospace">Ltr</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top border-secondary border-opacity-25" style="font-size: 0.72rem; color: #94a3b8;">
                        <span>Baseline: <strong>{{ number_format($pump->initial_reading, 2) }}</strong></span>
                        <span class="text-info text-opacity-90">Total Pumped: <strong>{{ number_format($totalDispensed, 2) }} Ltr</strong></span>
                    </div>
                </div>

                <!-- Specs List -->
                <div class="bg-light rounded-3 p-2.5 small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-secondary"><i class="bi bi-geo-alt me-1 text-danger"></i>Station Site:</span>
                        <strong class="text-dark">{{ $pump->location?->name ?? 'Main Site' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-secondary"><i class="bi bi-database me-1 text-primary"></i>Connected Tank:</span>
                        @if($pump->tank)
                            <a href="{{ route('tanks.show', $pump->tank->id) }}" class="text-primary fw-bold text-decoration-none">
                                <i class="bi bi-box-arrow-up-right me-0.5" style="font-size: 0.65rem;"></i> {{ $pump->tank->name }}
                            </a>
                        @else
                            <strong class="text-dark">{{ $stockSync['tank_or_shelf'] }}</strong>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-secondary"><i class="bi bi-pipette me-1 text-info"></i>Nozzle Identifier:</span>
                        <strong class="text-dark">{{ $pump->nozzle_number ?: 'Standard Single Nozzle' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-secondary"><i class="bi bi-cpu me-1 text-secondary"></i>Meter Mechanism:</span>
                        <strong class="text-dark text-capitalize">{{ $pump->meter_type }} Digital Totalizer</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-secondary"><i class="bi bi-clock-history me-1 text-warning"></i>Last Shift Closed:</span>
                        <strong class="text-dark">{{ $lastShift ? $lastShift->date->format('d M Y') . ' (' . ucfirst($lastShift->shift_type) . ')' : 'No shifts yet' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Live Stock & Purchase Inflow Synchronization Panel -->
        <div class="col-12 col-lg-7">
            <div class="card-custom h-100 p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-color: #bbf7d0 !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success text-white px-2.5 py-1 rounded-pill" style="font-size: 0.72rem;">
                            <i class="bi bi-arrow-repeat me-1"></i> Tank &amp; Purchase Sync
                        </span>
                        <h6 class="fw-bold mb-0 text-dark">
                            {{ $stockSync['product_name'] }} &bull; {{ $stockSync['tank_or_shelf'] }}
                        </h6>
                    </div>
                    <a href="{{ route('purchases.create', ['pump_id' => $pump->id, 'product_id' => $pump->product_id]) }}" class="btn btn-success btn-sm py-1 px-2.5 rounded-2 fw-semibold shadow-xs" style="font-size: 0.77rem;">
                        <i class="bi bi-plus-circle me-1"></i> Record Tank Intake
                    </a>
                </div>

                <p class="text-secondary small mb-3" style="font-size: 0.78rem;">
                    Full volumetric reconciliation between refinery/OMC bowsers unloaded into the underground tank and retail fuel dispensed through this pump nozzle.
                </p>

                <!-- Stock Flow Metrics Grid -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-sm-3">
                        <div class="bg-white p-2 rounded border">
                            <span class="text-secondary small d-block" style="font-size: 0.68rem;">Opening Stock</span>
                            <strong class="text-dark">{{ number_format($stockSync['opening_stock'], 2) }}</strong>
                            <small class="text-muted" style="font-size: 0.65rem;">Ltr</small>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="bg-white p-2 rounded border border-success-subtle">
                            <span class="text-success small d-block" style="font-size: 0.68rem;"><i class="bi bi-box-arrow-in-down me-1"></i>Purchased (In)</span>
                            <strong class="text-success">+{{ number_format($stockSync['total_purchased'], 2) }}</strong>
                            <small class="text-muted" style="font-size: 0.65rem;">Ltr</small>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="bg-white p-2 rounded border border-primary-subtle">
                            <span class="text-primary small d-block" style="font-size: 0.68rem;"><i class="bi bi-box-arrow-up me-1"></i>Dispensed (Out)</span>
                            <strong class="text-primary">-{{ number_format($stockSync['total_dispensed_all_pumps'], 2) }}</strong>
                            <small class="text-muted" style="font-size: 0.65rem;">Ltr</small>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="bg-white p-2 rounded border border-warning-subtle">
                            <span class="text-dark small d-block" style="font-size: 0.68rem;">Current Tank Stock</span>
                            <strong class="{{ $stockSync['current_stock'] <= 3000 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($stockSync['current_stock'], 2) }}
                            </strong>
                            <small class="text-muted" style="font-size: 0.65rem;">Ltr</small>
                        </div>
                    </div>
                </div>

                <!-- Recent Tank Deliveries Table -->
                <div class="border rounded-2 overflow-hidden bg-white">
                    <div class="bg-light py-1 px-2.5 border-bottom d-flex align-items-center justify-content-between">
                        <span class="fw-semibold small text-dark" style="font-size: 0.73rem;">
                            <i class="bi bi-truck text-warning me-1"></i> Recent Bowser Unloadings for this Fuel
                        </span>
                        <a href="{{ route('purchases.index', ['product_id' => $pump->product_id]) }}" class="text-primary text-decoration-none small" style="font-size: 0.7rem;">View All Deliveries &rarr;</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.74rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-2.5">Date</th>
                                    <th>Supplier</th>
                                    <th>Vehicle #</th>
                                    <th class="text-end">Delivered Qty</th>
                                    <th class="text-end">Cost Rate</th>
                                    <th class="pe-2.5 text-end">Bill #</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPurchases as $purchase)
                                    <tr>
                                        <td class="ps-2.5 fw-semibold">{{ $purchase->purchase_date->format('d M Y') }}</td>
                                        <td>{{ $purchase->supplier?->name ?? 'N/A' }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $purchase->vehicle_no ?: 'Bowser' }}</span></td>
                                        <td class="text-end fw-bold text-success">+{{ number_format($purchase->quantity, 2) }} Ltr</td>
                                        <td class="text-end">Rs. {{ number_format($purchase->unit_price, 2) }}</td>
                                        <td class="pe-2.5 text-end">
                                            <a href="{{ route('purchases.show', $purchase->id) }}" class="text-primary text-decoration-none font-monospace">
                                                {{ $purchase->invoice_no ?: ('PO #' . $purchase->id) }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-2 text-muted">No bowsers recorded yet for this fuel product.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Section: Meter Readings Log & Audit History -->
    <div class="card-custom">
        <div class="px-3 py-2.5 border-bottom bg-white rounded-top-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-journal-text text-primary me-2"></i>Chronological Meter Reading Log &amp; Calibration Register
                </h6>
                <small class="text-secondary" style="font-size: 0.75rem;">Tamper-proof register of shift sales, nozzle calibrations, and totalizer adjustments.</small>
            </div>

            <!-- Reading Filters -->
            <form action="{{ route('pumps.show', $pump->id) }}" method="GET" class="d-flex flex-wrap gap-1.5 align-items-center">
                <select name="reading_type" class="form-select form-select-sm" style="width: auto; font-size: 0.77rem;" onchange="this.form.submit()">
                    <option value="all" {{ request('reading_type') === 'all' ? 'selected' : '' }}>All Log Types</option>
                    <option value="shift_sale" {{ request('reading_type') === 'shift_sale' ? 'selected' : '' }}>Shift Sales</option>
                    <option value="calibration" {{ request('reading_type') === 'calibration' ? 'selected' : '' }}>Calibrations</option>
                    <option value="manual_adjustment" {{ request('reading_type') === 'manual_adjustment' ? 'selected' : '' }}>Adjustments</option>
                    <option value="initial_setup" {{ request('reading_type') === 'initial_setup' ? 'selected' : '' }}>Initial Baseline</option>
                </select>

                <input type="date" name="from_date" class="form-control form-control-sm" style="width: 125px; font-size: 0.77rem;" value="{{ request('from_date') }}" title="From Date">
                <input type="date" name="to_date" class="form-control form-control-sm" style="width: 125px; font-size: 0.77rem;" value="{{ request('to_date') }}" title="To Date">
                <button type="submit" class="btn btn-dark btn-sm px-2.5" style="font-size: 0.77rem;">Filter</button>
                @if(request()->hasAny(['reading_type', 'from_date', 'to_date']))
                    <a href="{{ route('pumps.show', $pump->id) }}" class="btn btn-outline-secondary btn-sm px-2" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width: 100px;">Date</th>
                        <th style="min-width: 120px;">Type</th>
                        <th style="min-width: 120px;" class="text-end">Start / Opening</th>
                        <th style="min-width: 130px;" class="text-end">End / Closing</th>
                        <th style="min-width: 130px;" class="text-end">Volume Dispensed</th>
                        <th style="min-width: 110px;" class="text-end">Fuel Rate</th>
                        <th style="min-width: 130px;" class="text-end">Sales Amount</th>
                        <th style="min-width: 140px;">Cashier / Operator</th>
                        <th style="min-width: 120px;">Recorded By</th>
                        <th style="min-width: 180px;">Remarks / Audit Notes</th>
                        <th class="pe-3 text-end" style="min-width: 110px;">Shift Link</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($readings as $reading)
                        <tr>
                            <td class="ps-3 fw-semibold text-dark">
                                {{ $reading->reading_date ? $reading->reading_date->format('d M Y') : '-' }}
                            </td>
                            <td>
                                {!! $reading->type_badge !!}
                            </td>
                            <td class="text-end font-monospace text-secondary">
                                {{ number_format($reading->start_reading, 2) }}
                            </td>
                            <td class="text-end font-monospace fw-bold text-primary">
                                {{ number_format($reading->end_reading, 2) }}
                            </td>
                            <td class="text-end">
                                @if((float)$reading->volume_dispensed > 0)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-0.5 fw-bold font-monospace">
                                        {{ number_format($reading->volume_dispensed, 2) }} Ltr
                                    </span>
                                @else
                                    <span class="text-muted font-monospace">0.00 Ltr</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if((float)$reading->fuel_rate > 0)
                                    Rs. {{ number_format($reading->fuel_rate, 2) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-dark">
                                @if((float)$reading->sales_amount > 0)
                                    Rs. {{ number_format($reading->sales_amount, 2) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($reading->shiftClosing)
                                    <span class="badge bg-light text-dark border">
                                        {{ ucfirst($reading->shiftClosing->shift_type) }} Shift
                                    </span>
                                    <div class="text-muted small" style="font-size: 0.68rem;">
                                        {{ $reading->cashier?->name ?? 'Cashier' }}
                                    </div>
                                @else
                                    <span class="text-secondary small">{{ $reading->cashier?->name ?? 'Technician / Admin' }}</span>
                                @endif
                            </td>
                            <td class="small text-secondary">
                                {{ $reading->recorder?->name ?? 'System' }}
                            </td>
                            <td class="small text-muted" style="max-width: 220px;">
                                {{ $reading->remarks ?: '-' }}
                            </td>
                            <td class="pe-3 text-end">
                                @if($reading->shift_closing_id)
                                    <a href="{{ route('shift-closings.show', $reading->shift_closing_id) }}" class="btn btn-outline-primary btn-sm py-0.5 px-2 rounded-2" style="font-size: 0.72rem;">
                                        <i class="bi bi-receipt me-1"></i>Shift #{{ $reading->shift_closing_id }}
                                    </a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
                                <i class="bi bi-journal-x fs-3 d-block mb-2"></i>
                                No meter readings found matching the selected filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($readings->hasPages())
            <div class="card-footer bg-white py-2 px-3 border-top d-flex justify-content-center">
                {{ $readings->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal: Meter Reading Calibration / Adjustment -->
<div class="modal fade" id="adjustModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('pumps.adjust-reading', $pump->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-dark text-white py-2 px-3">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="bi bi-tools text-warning me-1.5"></i> Calibrate / Adjust Meter: {{ $pump->name }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Current Live Reading:</strong> {{ number_format($pump->last_reading, 2) }} Liters.
                        Use this to record technician calibration, meter rollover, or repairs with full audit trail.
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-dark mb-1">New Meter Reading <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="new_reading" class="form-control form-control-sm fw-bold" value="{{ old('new_reading', $pump->last_reading) }}" required>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-dark mb-1">Reason / Adjustment Type <span class="text-danger">*</span></label>
                        <select name="adjustment_type" class="form-select form-select-sm" required>
                            <option value="calibration">Routine Inspection / Volumetric Calibration Test</option>
                            <option value="manual_adjustment">Meter Reset / Counter Rollover / Mechanical Repair</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark mb-1">Test Volume (Ltr)</label>
                            <input type="number" step="0.01" min="0" name="volume_tested" class="form-control form-control-sm" placeholder="e.g. 10.00" value="0">
                        </div>
                        <div class="col-6 d-flex align-items-end pb-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="returned_to_tank" value="1" id="returnCheck" checked>
                                <label class="form-check-label small" for="returnCheck">
                                    Poured back into Tank
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">Technician / Calibration Notes <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="e.g. Tested nozzle with 10L calibration measure bucket; seal verified." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Save Calibration Log</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
