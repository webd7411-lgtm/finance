@extends('layouts.app')

@section('title', 'Pump Machines & Dispensers')
@section('page_title', 'Petrol Pump Machines & Meter Readings')

@section('page_badge')
    <div class="d-flex align-items-center gap-1.5 flex-wrap">
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem; font-weight: 600;">
            <i class="bi bi-speedometer2 me-1"></i> Total Machines: {{ $totalPumps }}
        </span>
        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem; font-weight: 600;">
            <i class="bi bi-check-circle-fill me-1"></i> Active: {{ $activePumps }}
        </span>
        @if($maintenancePumps > 0)
            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem; font-weight: 600;">
                <i class="bi bi-tools me-1"></i> Under Maintenance: {{ $maintenancePumps }}
            </span>
        @endif
    </div>
@endsection

@section('page_actions')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('shift-closings.create') }}" class="btn btn-outline-primary btn-sm px-3 py-1.5 rounded-3 shadow-xs fw-semibold text-nowrap d-inline-flex align-items-center gap-1.5" style="font-size: 0.81rem;">
            <i class="bi bi-clock-history"></i> Record Shift Closing
        </a>
        <a href="{{ route('pumps.create') }}" class="btn btn-primary btn-sm px-3 py-1.5 rounded-3 shadow-xs fw-semibold text-nowrap d-inline-flex align-items-center gap-1.5" style="font-size: 0.81rem; background-color: #2563eb; border-color: #2563eb;">
            <i class="bi bi-plus-circle-fill"></i> Add Pump Machine
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

    /* Filter Pills */
    .filter-pills-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .filter-pill {
        font-size: 0.76rem;
        font-weight: 600;
        padding: 0.32rem 0.75rem;
        border-radius: 50px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #475569;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        transition: all 0.15s ease;
        line-height: 1.2;
    }
    .filter-pill:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .filter-pill.active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.28);
    }

    /* Modern Dispenser Hardware Card */
    .dispenser-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .dispenser-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.12);
        transform: translateY(-3px);
    }
    .dispenser-card-header {
        padding: 0.85rem 1rem 0.65rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        background: #fafbfc;
    }
    .dispenser-card-body {
        padding: 0.85rem 1rem;
        flex-grow: 1;
    }
    .dispenser-card-footer {
        padding: 0.65rem 1rem;
        background: #fafbfc;
        border-top: 1px solid #f1f5f9;
    }

    /* Stylized Digital Totalizer LCD Bezel */
    .meter-bezel {
        background: linear-gradient(180deg, #0b1120 0%, #030712 100%);
        border: 1.5px solid #1e293b;
        border-radius: 10px;
        padding: 0.6rem 0.85rem;
        box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.6), 0 2px 4px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }
    .meter-bezel::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.4), transparent);
    }
    .meter-title-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2px;
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }
    .meter-digits {
        font-family: 'Courier New', Courier, monospace !important;
        font-size: 1.5rem;
        font-weight: 900;
        color: #38bdf8;
        letter-spacing: 2px;
        line-height: 1.15;
        text-shadow: 0 0 10px rgba(56, 189, 248, 0.35);
        margin: 3px 0;
        display: flex;
        align-items: baseline;
        justify-content: space-between;
    }
    .meter-sub-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.66rem;
        color: #94a3b8;
        border-top: 1px dashed rgba(255, 255, 255, 0.1);
        padding-top: 4px;
        margin-top: 3px;
    }

    /* Specs List within Card */
    .spec-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.3rem 0;
        font-size: 0.77rem;
        border-bottom: 1px dashed #f1f5f9;
    }
    .spec-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .spec-label {
        color: #64748b;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .spec-val {
        color: #0f172a;
        font-weight: 600;
        text-align: right;
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

    /* Highlighted Machine Identity Header Banner */
    .machine-highlight-header {
        background: linear-gradient(135deg, #f0f7ff 0%, #e0f2fe 100%);
        border: 1.5px solid #93c5fd;
        border-radius: 10px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(37, 99, 235, 0.08);
    }
    .dispenser-card:hover .machine-highlight-header {
        background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
        border-color: #2563eb;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.16);
    }
    .machine-num-badge {
        background: #1d4ed8;
        color: #ffffff;
        font-weight: 800;
        font-size: 0.78rem;
        letter-spacing: 0.5px;
        padding: 0.25rem 0.55rem;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 4px rgba(29, 78, 216, 0.25);
    }

    /* Action Trigger Menu */
    .btn-action-trigger {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .btn-action-trigger:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #cbd5e1;
    }

    /* Table View for ERP Dense Data */
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

    <!-- Top Key Metrics: 5 Unified Cards in ONE Single Row -->
    <div class="metrics-grid mb-2.5">
        <!-- Metric 1: Total Machines -->
        <div class="metric-card">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label">Total Dispensers</span>
                <div class="metric-icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-speedometer2"></i>
                </div>
            </div>
            <div class="metric-value text-dark">{{ number_format($totalPumps) }}</div>
            <div class="metric-sub">Installed pump units</div>
        </div>

        <!-- Metric 2: Active Dispensers -->
        <div class="metric-card">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label">Active &amp; Ready</span>
                <div class="metric-icon-box bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
            <div class="metric-value text-success">{{ number_format($activePumps) }}</div>
            <div class="metric-sub">{{ $maintenancePumps > 0 ? $maintenancePumps . ' in maintenance' : 'All ready for shifts' }}</div>
        </div>

        <!-- Metric 3: Today's Volume -->
        <div class="metric-card">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label">Today's Dispensed</span>
                <div class="metric-icon-box bg-info bg-opacity-10 text-info">
                    <i class="bi bi-fuel-pump"></i>
                </div>
            </div>
            <div class="metric-value text-info-emphasis">
                {{ number_format($todayDispensedLiters, 2) }} <span class="fs-6 fw-normal text-muted">Ltr</span>
            </div>
            <div class="metric-sub">Recorded in today's shifts</div>
        </div>

        <!-- Metric 4: Total Lifetime Volume -->
        <div class="metric-card">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label">Lifetime Volume</span>
                <div class="metric-icon-box bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-droplet-half"></i>
                </div>
            </div>
            <div class="metric-value text-dark">
                {{ number_format($totalDispensedLiters, 2) }} <span class="fs-6 fw-normal text-muted">Ltr</span>
            </div>
            <div class="metric-sub">Cumulative totalizer fuel</div>
        </div>

        <!-- Metric 5: Total Sales Revenue (Accent Card) -->
        <div class="metric-card metric-card-valuation">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label text-white text-opacity-75">Gross Sales Revenue</span>
                <div class="metric-icon-box bg-white bg-opacity-20 text-white">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <div class="metric-value text-white text-nowrap overflow-hidden text-truncate" title="Rs. {{ number_format($totalRevenueGenerated, 2) }}">
                <span style="font-size: 0.82em; opacity: 0.88; font-weight: 600;">Rs.</span> {{ number_format($totalRevenueGenerated, 2) }}
            </div>
            <div class="metric-sub text-white text-opacity-75">Generated via meter sales</div>
        </div>
    </div>

    <!-- Main Card Container -->
    <div class="card-custom">
        <!-- Filter Header Bar -->
        <div class="px-3 py-2.5 border-bottom bg-white rounded-top-3">
            <!-- Row 1: Status Filter Pills & View Switcher -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <!-- Status Pills -->
                <div class="filter-pills-wrap">
                    <a href="{{ route('pumps.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}"
                       class="filter-pill {{ !request('status') || request('status') === 'all' ? 'active' : '' }}">
                        <i class="bi bi-grid-fill"></i> All Machines ({{ $totalPumps }})
                    </a>
                    <a href="{{ route('pumps.index', array_merge(request()->except('status', 'page'), ['status' => 'active'])) }}"
                       class="filter-pill {{ request('status') === 'active' ? 'active' : '' }}">
                        <span class="status-pulse-dot bg-success"></span> Active ({{ $activePumps }})
                    </a>
                    <a href="{{ route('pumps.index', array_merge(request()->except('status', 'page'), ['status' => 'maintenance'])) }}"
                       class="filter-pill {{ request('status') === 'maintenance' ? 'active' : '' }}">
                        <i class="bi bi-tools text-warning"></i> Maintenance ({{ $maintenancePumps }})
                    </a>
                    <a href="{{ route('pumps.index', array_merge(request()->except('status', 'page'), ['status' => 'inactive'])) }}"
                       class="filter-pill {{ request('status') === 'inactive' ? 'active' : '' }}">
                        <i class="bi bi-pause-circle text-secondary"></i> Inactive ({{ max(0, $totalPumps - $activePumps - $maintenancePumps) }})
                    </a>
                </div>

                <!-- View Switcher (Grid vs Table) -->
                <div class="btn-group btn-group-sm p-0.5 bg-light rounded-2 border" role="group">
                    <button type="button" class="btn btn-sm btn-white border-0 shadow-xs fw-semibold py-1 px-2.5 active" id="viewBtnCards" onclick="switchView('cards')">
                        <i class="bi bi-grid me-1"></i> Dispenser Cards
                    </button>
                    <button type="button" class="btn btn-sm text-secondary border-0 py-1 px-2.5" id="viewBtnTable" onclick="switchView('table')">
                        <i class="bi bi-table me-1"></i> Table Register
                    </button>
                </div>
            </div>

            <!-- Row 2: Search, Fuel Product Dropdown & Quick Filter Submit -->
            <form action="{{ route('pumps.index') }}" method="GET" class="row g-2 align-items-center">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search machine, tank, or nozzle..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <select name="product_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all">-- All Fuel Products --</option>
                        @foreach($fuelProducts as $fp)
                            <option value="{{ $fp->id }}" {{ request('product_id') == $fp->id ? 'selected' : '' }}>
                                {{ $fp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <select name="tank_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all">-- All Storage Tanks --</option>
                        @foreach($tanks as $t)
                            <option value="{{ $t->id }}" {{ request('tank_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex gap-1 justify-content-end">
                    <button type="submit" class="btn btn-dark btn-sm px-3 fw-semibold">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    @if(request()->hasAny(['search', 'product_id', 'tank_id', 'status']))
                        <a href="{{ route('pumps.index') }}" class="btn btn-outline-secondary btn-sm px-2.5" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- 1. Grid Cards View (Default) -->
        <div id="cardsViewContainer" class="p-3">
            <!-- Directory Summary / Count Banner -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 px-1 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white font-monospace px-2.5 py-1 fw-bold rounded-pill" style="font-size: 0.74rem;">
                        <i class="bi bi-fuel-pump-fill me-1"></i>{{ $pumps->total() }} Total Machine{{ $pumps->total() == 1 ? '' : 's' }}
                    </span>
                    <span class="text-secondary small fw-medium">
                        Showing {{ $pumps->firstItem() ?? 0 }} - {{ $pumps->lastItem() ?? 0 }} of {{ $pumps->total() }} installed dispensers
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2 small">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-0.5 rounded-pill">
                        <i class="bi bi-check-circle-fill me-1"></i>{{ $activePumps }} Active
                    </span>
                    @if($maintenancePumps > 0)
                        <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle px-2 py-0.5 rounded-pill">
                            <i class="bi bi-tools me-1"></i>{{ $maintenancePumps }} Under Maintenance
                        </span>
                    @endif
                </div>
            </div>

            <div class="row g-3">
                @forelse($pumps as $pump)
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="dispenser-card h-100">
                            <!-- Header -->
                            <div class="dispenser-card-header d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-1.5">
                                    <span class="badge bg-white text-dark font-monospace border px-2 py-1 fw-bold" style="font-size: 0.73rem;">
                                        {{ $pump->code ?: 'PUMP-' . str_pad($pump->id, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="badge bg-light text-secondary border px-1.5 py-0.5 text-capitalize" style="font-size: 0.67rem;">
                                        {{ $pump->meter_type }} Totalizer
                                    </span>
                                </div>

                                <div class="d-flex align-items-center gap-1">
                                    @if($pump->status === 'active')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-0.5 rounded-pill" style="font-size: 0.68rem; font-weight: 600;">
                                            <span class="status-pulse-dot bg-success pulse me-1"></span>Active
                                        </span>
                                    @elseif($pump->status === 'maintenance')
                                        <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle px-2 py-0.5 rounded-pill" style="font-size: 0.68rem; font-weight: 600;">
                                            <i class="bi bi-tools me-1"></i>Maintenance
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-2 py-0.5 rounded-pill" style="font-size: 0.68rem; font-weight: 600;">
                                            <i class="bi bi-pause-circle me-1"></i>Inactive
                                        </span>
                                    @endif

                                    <!-- Dropdown Menu -->
                                    <div class="dropdown">
                                        <button class="btn-action-trigger" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 0.79rem; min-width: 190px;">
                                            <li><a class="dropdown-item py-1.5" href="{{ route('pumps.show', $pump->id) }}"><i class="bi bi-journal-text text-primary me-2"></i>View History & Readings</a></li>
                                            <li><a class="dropdown-item py-1.5" href="{{ route('pumps.edit', $pump->id) }}"><i class="bi bi-pencil text-warning me-2"></i>Edit Machine Specs</a></li>
                                            <li>
                                                <button type="button" class="dropdown-item py-1.5" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $pump->id }}">
                                                    <i class="bi bi-tools text-info me-2"></i>Calibrate / Adjust Meter
                                                </button>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-1.5" href="{{ route('purchases.create', ['pump_id' => $pump->id, 'product_id' => $pump->product_id]) }}">
                                                    <i class="bi bi-truck text-success me-2"></i>Record Fuel Purchase
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <form action="{{ route('pumps.toggle-status', $pump->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item py-1.5 text-secondary">
                                                        <i class="bi bi-arrow-repeat me-2"></i>Toggle Status ({{ $pump->status === 'active' ? 'Maintenance' : 'Active' }})
                                                    </button>
                                                </form>
                                            </li>
                                            @if($pump->shift_closings_count === 0)
                                                <li>
                                                    <form action="{{ route('pumps.destroy', $pump->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this pump machine?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item py-1.5 text-danger">
                                                            <i class="bi bi-trash me-2"></i>Delete Machine
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="dispenser-card-body">
                                <!-- Highlighted Machine Identity Header Banner -->
                                <div class="machine-highlight-header p-2.5 rounded-3 mb-2.5">
                                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                                        <span class="machine-num-badge">
                                            <i class="bi bi-fuel-pump-fill"></i> Machine #{{ str_pad(($pumps->currentPage() - 1) * $pumps->perPage() + $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                        </span>
                                        <span class="badge bg-white text-primary border border-primary-subtle font-monospace px-2 py-0.5 fw-bold" style="font-size: 0.68rem;" title="Dispenser sequence in station">
                                            {{ ($pumps->currentPage() - 1) * $pumps->perPage() + $loop->iteration }} of {{ $pumps->total() }} Machines
                                        </span>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-0 text-truncate" title="{{ $pump->name }}" style="font-size: 0.95rem; letter-spacing: -0.01em;">
                                        <a href="{{ route('pumps.show', $pump->id) }}" class="text-dark text-decoration-none hover-primary">
                                            {{ $pump->name }}
                                        </a>
                                    </h6>
                                </div>

                                <!-- Clean Fuel Tag & Live Rate (Spaced cleanly without wrapping!) -->
                                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1 fw-semibold text-truncate" style="font-size: 0.72rem; max-width: 160px;" title="{{ $pump->product?->name ?? 'Fuel' }}">
                                        <i class="bi bi-fuel-pump me-1"></i>{{ $pump->product?->name ?? 'Unassigned' }}
                                    </span>
                                    <div class="text-end">
                                        <span class="fw-bold text-dark" style="font-size: 0.88rem;">Rs. {{ number_format($pump->product?->sale_price ?? 0, 2) }}</span>
                                        <span class="text-muted fw-normal" style="font-size: 0.68rem;">/{{ $pump->product?->unit_label ?? 'Ltr' }}</span>
                                    </div>
                                </div>

                                <!-- Stylized Digital LCD Totalizer -->
                                <div class="meter-bezel mb-3">
                                    <div class="meter-title-strip">
                                        <span><i class="bi bi-speedometer2 text-info me-1"></i>DIGITAL TOTALIZER</span>
                                        <span class="badge bg-dark text-info border border-info border-opacity-25 py-0 px-1" style="font-size: 0.58rem;">LIVE</span>
                                    </div>
                                    <div class="meter-digits">
                                        <span>{{ number_format($pump->last_reading, 2) }}</span>
                                        <span style="font-size: 0.75rem; color: #7dd3fc; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 600;">Ltr</span>
                                    </div>
                                    <div class="meter-sub-strip">
                                        <span>Baseline: {{ number_format($pump->initial_reading, 2) }}</span>
                                        <span class="text-info text-opacity-90">Dispensed: <strong>{{ number_format($pump->total_dispensed_liters, 2) }} Ltr</strong></span>
                                    </div>
                                </div>

                                <!-- Hardware & Tank Specifications Grid -->
                                <div class="bg-light bg-opacity-75 rounded-3 p-2.5">
                                    <div class="spec-item">
                                        <span class="spec-label"><i class="bi bi-database text-primary"></i> Storage Tank:</span>
                                        @if($pump->tank)
                                            <a href="{{ route('tanks.show', $pump->tank->id) }}" class="spec-val text-decoration-none text-primary fw-bold text-truncate" style="max-width: 140px;" title="{{ $pump->tank->name }}">
                                                <i class="bi bi-box-arrow-up-right me-0.5" style="font-size: 0.65rem;"></i> {{ $pump->tank->name }}
                                            </a>
                                        @else
                                            <span class="spec-val text-truncate" style="max-width: 140px;" title="{{ $pump->tank_no ?: ($pump->product?->tank_or_shelf ?: 'Underground Tank') }}">
                                                {{ $pump->tank_no ?: ($pump->product?->tank_or_shelf ?: 'Underground Tank') }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="spec-item">
                                        <span class="spec-label"><i class="bi bi-pipette text-info"></i> Nozzle Config:</span>
                                        <span class="spec-val">{{ $pump->nozzle_number ?: 'Standard Nozzle' }}</span>
                                    </div>
                                    <div class="spec-item">
                                        <span class="spec-label"><i class="bi bi-box-seam text-success"></i> Available Stock:</span>
                                        <span class="spec-val {{ ($pump->product?->current_stock ?? 0) <= ($pump->product?->min_stock_alert ?? 0) ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($pump->product?->current_stock ?? 0, 2) }} {{ $pump->product?->unit_label ?? 'Ltr' }}
                                        </span>
                                    </div>
                                    <div class="spec-item">
                                        <span class="spec-label"><i class="bi bi-clock-history text-secondary"></i> Shift Sessions:</span>
                                        <span class="spec-val">{{ $pump->shift_closings_count }} closed</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Action Bar -->
                            <div class="dispenser-card-footer d-flex align-items-center gap-1.5">
                                <a href="{{ route('pumps.show', $pump->id) }}" class="btn btn-primary btn-sm py-1.5 px-3 rounded-2 fw-semibold w-100 d-inline-flex align-items-center justify-content-center gap-1.5" style="font-size: 0.77rem; background-color: #2563eb; border-color: #2563eb;">
                                    <i class="bi bi-journal-text"></i> Readings &amp; Log
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-1.5 px-2.5 rounded-2" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $pump->id }}" title="Calibrate / Adjust Meter Reading">
                                    <i class="bi bi-tools text-warning"></i>
                                </button>
                                <a href="{{ route('purchases.create', ['pump_id' => $pump->id, 'product_id' => $pump->product_id]) }}" class="btn btn-outline-success btn-sm py-1.5 px-2.5 rounded-2" title="Record Tank Fuel Intake">
                                    <i class="bi bi-truck"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Modal: Meter Reading Calibration / Adjustment -->
                    <div class="modal fade" id="adjustModal{{ $pump->id }}" tabindex="-1" aria-hidden="true">
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
                                                    <input class="form-check-input" type="checkbox" name="returned_to_tank" value="1" id="returnCheck{{ $pump->id }}" checked>
                                                    <label class="form-check-label small" for="returnCheck{{ $pump->id }}">
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
                                        <button type="submit" class="btn btn-primary btn-sm px-3">Save Calibration Log</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-speedometer2 text-muted" style="font-size: 3rem;"></i>
                            <h5 class="fw-bold mt-3">No Pump Machines Found</h5>
                            <p class="text-secondary small mb-3">Get started by setting up your fuel dispensers and nozzles.</p>
                            <a href="{{ route('pumps.create') }}" class="btn btn-primary btn-sm px-3 py-2">
                                <i class="bi bi-plus-circle me-1"></i> Add First Pump Machine
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- 2. Dense ERP Table View (Toggleable via button above) -->
        <div id="tableViewContainer" class="d-none">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width: 80px;">Unit #</th>
                            <th style="width: 100px;">Code</th>
                            <th style="min-width: 200px;">Machine / Dispenser Name</th>
                            <th style="min-width: 160px;">Fuel Product &amp; Rate</th>
                            <th style="min-width: 160px;">Storage Tank &amp; Stock</th>
                            <th style="min-width: 120px;">Nozzle Config</th>
                            <th style="min-width: 120px;" class="text-end">Opening Baseline</th>
                            <th style="min-width: 130px;" class="text-end">Current Reading</th>
                            <th style="min-width: 130px;" class="text-end">Total Dispensed</th>
                            <th style="min-width: 110px;" class="text-center">Status</th>
                            <th class="pe-3 text-end" style="min-width: 130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pumps as $pump)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-primary text-white font-monospace px-2 py-1 fw-bold" style="font-size: 0.73rem;">
                                        #{{ str_pad(($pumps->currentPage() - 1) * $pumps->perPage() + $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                </td>
                                <td class="font-monospace fw-bold text-dark">
                                    {{ $pump->code ?: 'PUMP-' . str_pad($pump->id, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <a href="{{ route('pumps.show', $pump->id) }}" class="text-dark text-decoration-none hover-primary">
                                            {{ $pump->name }}
                                        </a>
                                    </div>
                                    <small class="text-primary fw-semibold" style="font-size: 0.69rem;">
                                        Machine {{ ($pumps->currentPage() - 1) * $pumps->perPage() + $loop->iteration }} of {{ $pumps->total() }} Total
                                    </small>
                                    <small class="text-muted" style="font-size: 0.69rem;"> • {{ $pump->meter_type }} Totalizer</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-0.5">
                                        <i class="bi bi-fuel-pump me-1"></i>{{ $pump->product?->name ?? 'N/A' }}
                                    </span>
                                    <div class="small fw-semibold text-dark mt-0.5">
                                        Rs. {{ number_format($pump->product?->sale_price ?? 0, 2) }}/{{ $pump->product?->unit_label ?? 'Ltr' }}
                                    </div>
                                </td>
                                <td>
                                    @if($pump->tank)
                                        <a href="{{ route('tanks.show', $pump->tank->id) }}" class="text-primary fw-bold text-decoration-none small d-block">
                                            <i class="bi bi-box-arrow-up-right me-0.5" style="font-size: 0.65rem;"></i> {{ $pump->tank->name }}
                                        </a>
                                    @else
                                        <div class="text-dark fw-semibold small">{{ $pump->tank_no ?: ($pump->product?->tank_or_shelf ?: 'Underground Tank') }}</div>
                                    @endif
                                    <div class="small {{ ($pump->product?->current_stock ?? 0) <= ($pump->product?->min_stock_alert ?? 0) ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($pump->product?->current_stock ?? 0, 2) }} {{ $pump->product?->unit_label ?? 'Ltr' }} avail
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $pump->nozzle_number ?: 'Standard' }}</span>
                                </td>
                                <td class="text-end font-monospace text-secondary">
                                    {{ number_format($pump->initial_reading, 2) }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-primary">
                                    {{ number_format($pump->last_reading, 2) }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark">
                                    {{ number_format($pump->total_dispensed_liters, 2) }} Ltr
                                </td>
                                <td class="text-center">
                                    {!! $pump->status_badge !!}
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('pumps.show', $pump->id) }}" class="btn btn-outline-primary btn-sm py-1 px-2" title="View Readings">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $pump->id }}" title="Calibrate">
                                            <i class="bi bi-tools text-warning"></i>
                                        </button>
                                        <a href="{{ route('pumps.edit', $pump->id) }}" class="btn btn-outline-dark btn-sm py-1 px-2" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">No pump machines found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($pumps->hasPages())
            <div class="card-footer bg-white py-2 px-3 border-top d-flex justify-content-center">
                {{ $pumps->links() }}
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    function switchView(view) {
        const cardsCont = document.getElementById('cardsViewContainer');
        const tableCont = document.getElementById('tableViewContainer');
        const btnCards = document.getElementById('viewBtnCards');
        const btnTable = document.getElementById('viewBtnTable');

        if (view === 'table') {
            cardsCont.classList.add('d-none');
            tableCont.classList.remove('d-none');
            btnTable.classList.add('btn-white', 'shadow-xs', 'fw-semibold', 'active');
            btnTable.classList.remove('text-secondary');
            btnCards.classList.remove('btn-white', 'shadow-xs', 'fw-semibold', 'active');
            btnCards.classList.add('text-secondary');
            localStorage.setItem('pump_view_mode', 'table');
        } else {
            tableCont.classList.add('d-none');
            cardsCont.classList.remove('d-none');
            btnCards.classList.add('btn-white', 'shadow-xs', 'fw-semibold', 'active');
            btnCards.classList.remove('text-secondary');
            btnTable.classList.remove('btn-white', 'shadow-xs', 'fw-semibold', 'active');
            btnTable.classList.add('text-secondary');
            localStorage.setItem('pump_view_mode', 'cards');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const savedView = localStorage.getItem('pump_view_mode');
        if (savedView === 'table') {
            switchView('table');
        }
    });
</script>
@endpush
@endsection
