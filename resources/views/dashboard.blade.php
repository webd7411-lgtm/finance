@extends('layouts.app')

@section('title', 'Executive Financial Dashboard - FinanceDesk')
@section('page_title', 'Executive Financial Dashboard')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-clock me-1 text-primary"></i> Live Financial Position
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-1 gap-sm-2 w-100 justify-content-start justify-content-sm-end">
        <a href="{{ route('reports.daily-whatsapp') }}" class="btn btn-success btn-sm rounded-3 fw-semibold shadow-sm flex-fill flex-sm-grow-0 text-nowrap px-2 px-sm-3 btn-action-mobile">
            <i class="bi bi-whatsapp me-1"></i> WhatsApp Summary
        </a>
        <a href="{{ route('tanks.index') }}" class="btn btn-outline-info btn-sm rounded-3 fw-semibold shadow-sm flex-fill flex-sm-grow-0 text-nowrap px-2 px-sm-3 btn-action-mobile">
            <i class="bi bi-database-fill-gear me-1"></i> Storage Tanks
        </a>
        <a href="{{ route('pumps.index') }}" class="btn btn-outline-primary btn-sm rounded-3 fw-semibold shadow-sm flex-fill flex-sm-grow-0 text-nowrap px-2 px-sm-3 btn-action-mobile">
            <i class="bi bi-speedometer2 me-1"></i> Pump Machines
        </a>
        <a href="{{ route('products.index') }}" class="btn btn-outline-danger btn-sm rounded-3 fw-semibold shadow-sm flex-fill flex-sm-grow-0 text-nowrap px-2 px-sm-3 btn-action-mobile">
            <i class="bi bi-fuel-pump me-1"></i> Products & Fuel
        </a>
        <a href="{{ route('shift-closings.create') }}" class="btn btn-primary btn-sm rounded-3 fw-semibold shadow-sm flex-fill flex-sm-grow-0 text-nowrap px-2 px-sm-3 btn-action-mobile">
            <i class="bi bi-plus-circle me-1"></i> Shift Closing
        </a>
        <a href="{{ route('day-closings.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold flex-fill flex-sm-grow-0 text-nowrap px-2 px-sm-3 btn-action-mobile">
            <i class="bi bi-calendar2-check me-1"></i> Day Closing
        </a>
    </div>
@endsection

@push('styles')
<style>
    /* Full mobile responsive rules - strictly zero horizontal scroll */
    .dashboard-kpi-card {
        padding: 0.55rem 0.75rem !important;
        border-radius: 10px;
    }
    .kpi-icon-badge {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.85rem;
    }
    .kpi-value-text {
        font-size: clamp(1.05rem, 1.8vw, 1.25rem);
        line-height: 1.2;
        word-break: break-word;
    }
    .chart-box-wrapper {
        position: relative;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }
    @media (max-width: 767.98px) {
        .card-custom {
            padding: 0.85rem !important;
            border-radius: 10px;
        }
        .btn-action-mobile {
            font-size: 0.78rem !important;
            padding: 0.38rem 0.6rem !important;
        }
    }
    @media (max-width: 575.98px) {
        .dashboard-kpi-card {
            padding: 0.5rem 0.65rem !important;
        }
        .kpi-value-text {
            font-size: 1.05rem !important;
        }
    }
</style>
@endpush

@section('content')
<!-- Live Fuel Storage Tanks, Dip Variance & Lubricant Inventory Ribbon -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1.5">
    <div class="d-flex align-items-center gap-2">
        <span class="text-uppercase text-dark fw-bold" style="letter-spacing: 0.5px; font-size: 0.72rem;">
            <i class="bi bi-database-check text-primary me-1"></i> Live Storage Tanks, Dip Variance &amp; Lubricant Inventory
        </span>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="{{ route('reports.tank-reconciliation') }}" class="badge bg-info bg-opacity-10 text-info-emphasis border border-info-subtle text-decoration-none py-1 px-2 small">
            <i class="bi bi-clipboard-data me-1"></i> Wet-Stock Report
        </a>
        @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
            <a href="{{ route('tanks.settings') }}" class="badge bg-light text-dark border text-decoration-none py-1 px-2 small">
                <i class="bi bi-sliders me-1"></i> Tank Settings
            </a>
        @endif
        <a href="{{ route('reports.daily-whatsapp') }}" class="badge bg-success text-white text-decoration-none py-1 px-2 small">
            <i class="bi bi-whatsapp me-1"></i> Send Owner Daily Report
        </a>
        <a href="{{ route('tanks.index') }}" class="text-primary text-decoration-none small fw-semibold" style="font-size: 0.72rem;">
            View All Tanks &rarr;
        </a>
    </div>
</div>

@php
    $dipUnit = $tankConfig['dip_unit'] ?? 'mm';
    $tankBorderColors = ['danger', 'primary', 'warning', 'success', 'info', 'secondary'];
@endphp

<div class="row g-2 g-md-2.5 mb-2.5">
    @foreach($tankCards as $idx => $card)
        @php
            $t = $card['tank'];
            $accent = $tankBorderColors[$idx % count($tankBorderColors)];
            $tCap = max(0, (float) $t->capacity);
            $tBook = (float) $card['book_stock'];
            $tPct = $tCap > 0 ? min(100, round(($tBook / $tCap) * 100)) : 0;
            $tUllage = max(0, $tCap - $tBook);
            $unitCost = (float) ($t->product?->purchase_price ?? 0);
            $wLim = $card['thresholds']['warning_liters'] ?? 25;
            $cLim = $card['thresholds']['critical_liters'] ?? 50;
            $varStatus = $card['variance_status'];
        @endphp
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-custom dashboard-kpi-card h-100 border-start border-3 border-{{ $accent }} shadow-sm position-relative overflow-hidden d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-1 gap-1">
                        <a href="{{ route('tanks.show', $t->id) }}" class="text-muted small fw-semibold text-uppercase text-decoration-none text-truncate" style="letter-spacing: 0.4px; font-size: 0.68rem;" title="{{ $t->name }}">
                            {{ $t->product?->name ?? $t->name }}
                        </a>
                        <span class="badge bg-{{ $accent }} bg-opacity-10 text-{{ $accent === 'warning' ? 'warning-emphasis' : $accent }} border border-{{ $accent }}-subtle small py-0.5 px-1.5 flex-shrink-0" style="font-size: 0.65rem;">
                            {{ $t->tank_number ?: ('Tank #' . ($idx + 1)) }}
                        </span>
                    </div>

                    <div class="d-flex align-items-baseline justify-content-between gap-1">
                        <div class="fw-bold text-dark font-monospace fs-5">
                            {{ number_format($tBook, 1) }} <small class="fs-6 fw-normal text-muted">Ltr</small>
                        </div>
                        <div class="small text-muted text-end" style="font-size: 0.71rem;">
                            Dip: <strong class="text-dark">{{ $t->current_dip_reading ? number_format($t->current_dip_reading, 1) . ' ' . $dipUnit : 'N/A' }}</strong>
                        </div>
                    </div>

                    @if($tankConfig['show_variance_on_dashboard'])
                        <div class="d-flex align-items-center justify-content-between gap-1 mt-1" style="font-size: 0.7rem;">
                            @if($card['has_dip'])
                                <span class="text-muted">
                                    Phys: <strong class="text-dark font-monospace">{{ number_format($card['measured_liters'], 1) }} L</strong>
                                </span>
                                <span>{!! \App\Models\Tank::formatVarianceBadge($varStatus, $card['variance_liters']) !!}</span>
                            @else
                                <span class="text-muted fst-italic">No physical dip logged yet</span>
                            @endif
                        </div>

                        @if($card['trend']['has_trend'])
                            <div class="mt-1">
                                <span class="badge bg-danger text-white w-100 text-start py-1" style="font-size: 0.64rem;">
                                    <i class="bi bi-exclamation-octagon-fill me-1"></i>{{ $card['trend']['streak'] }}-Dip Shortage Trend Detected
                                </span>
                            </div>
                        @endif
                    @endif

                    <div class="progress mt-1.5" style="height: 6px;">
                        <div class="progress-bar bg-{{ $accent }}" style="width: {{ $tPct }}%"></div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center text-muted mt-1.5 pt-1 border-top border-light" style="font-size: 0.68rem;">
                    <span>Cap: {{ number_format($tCap) }}L &bull; Space: <strong class="text-primary">{{ number_format($tUllage, 0) }}L</strong></span>
                    <button type="button" class="btn btn-outline-success btn-sm py-0 px-1.5" style="font-size: 0.66rem;"
                            onclick="openDipModal({{ $t->id }}, '{{ addslashes($t->name) }}', {{ $t->current_dip_reading ?? 0 }}, {{ $tBook }}, {{ $unitCost }}, {{ $wLim }}, {{ $cLim }}, {{ $card['has_chart'] ? 'true' : 'false' }})">
                        <i class="bi bi-rulers me-0.5"></i>Dip
                    </button>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Lubricants Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-3 border-info shadow-sm position-relative overflow-hidden d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.4px; font-size: 0.68rem;">Caltex Havoline &amp; Oils</span>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle small py-0.5 px-1.5" style="font-size: 0.65rem;">
                        Shelf Store
                    </span>
                </div>
                <div class="d-flex align-items-baseline justify-content-between">
                    <div class="fw-bold text-info font-monospace fs-5">
                        {{ number_format($totalLubricantUnits, 0) }} <small class="fs-6 fw-normal text-muted">Cans / Packs</small>
                    </div>
                    <div class="small text-muted" style="font-size: 0.72rem;">
                        <strong class="text-dark">{{ $lubricantProducts->count() }}</strong> SKUs
                    </div>
                </div>
                <div class="progress mt-1.5" style="height: 6px;">
                    <div class="progress-bar bg-info" style="width: 75%"></div>
                </div>
            </div>
            <div class="d-flex justify-content-between text-muted mt-1.5 pt-1 border-top border-light" style="font-size: 0.68rem;">
                <span>Havoline, Helix, 4T Oils</span>
                <a href="{{ route('products.index') }}" class="text-info text-decoration-none fw-semibold">Manage Store &rarr;</a>
            </div>
        </div>
    </div>
</div>

@if($tankConfig['show_variance_on_dashboard'] && $wetStockSummary['total_active_tanks'] > 0)
<!-- Wet-Stock Tank Behaviour & Reconciliation Summary Strip -->
<div class="row g-2 g-md-2.5 mb-3">
    <div class="col-12 col-md-4">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-3 {{ $wetStockSummary['net_variance_liters'] < -0.01 ? 'border-danger' : ($wetStockSummary['net_variance_liters'] > 0.01 ? 'border-info' : 'border-success') }} shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">Net Unreconciled Tank Variance</span>
                <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">
                    {{ $wetStockSummary['dipped_tanks_count'] }}/{{ $wetStockSummary['total_active_tanks'] }} Dipped
                </span>
            </div>
            <div class="d-flex align-items-baseline justify-content-between">
                <span class="fw-bold font-monospace kpi-value-text {{ $wetStockSummary['net_variance_liters'] < -0.01 ? 'text-danger' : ($wetStockSummary['net_variance_liters'] > 0.01 ? 'text-info' : 'text-success') }}">
                    {{ $wetStockSummary['net_variance_liters'] > 0 ? '+' : '' }}{{ number_format($wetStockSummary['net_variance_liters'], 1) }} Ltr
                </span>
                <span class="small font-monospace text-muted">
                    Cost: {{ $wetStockSummary['net_variance_value'] > 0 ? '+' : '' }}Rs. {{ number_format($wetStockSummary['net_variance_value'], 0) }}
                </span>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-3 border-secondary shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">Month Reconciled Shrinkage / Gain</span>
                <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">{{ now()->format('M Y') }}</span>
            </div>
            <div class="d-flex align-items-baseline justify-content-between">
                <span class="fw-bold font-monospace kpi-value-text {{ $wetStockSummary['month_reconciled_liters'] < 0 ? 'text-danger' : 'text-success' }}">
                    {{ $wetStockSummary['month_reconciled_liters'] > 0 ? '+' : '' }}{{ number_format($wetStockSummary['month_reconciled_liters'], 1) }} Ltr
                </span>
                <span class="small font-monospace text-muted">
                    Impact: {{ $wetStockSummary['month_reconciled_value'] > 0 ? '+' : '' }}Rs. {{ number_format($wetStockSummary['month_reconciled_value'], 0) }}
                </span>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-3 {{ $wetStockSummary['alert_tanks_count'] > 0 ? 'border-danger' : 'border-success' }} shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">Wet-Stock Tolerance Health</span>
                <a href="{{ route('reports.tank-reconciliation') }}" class="text-primary text-decoration-none small fw-semibold" style="font-size: 0.68rem;">Audit &rarr;</a>
            </div>
            <div class="d-flex align-items-center justify-content-between">
                @if($wetStockSummary['alert_tanks_count'] > 0)
                    <span class="fw-bold text-danger small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $wetStockSummary['alert_tanks_count'] }} Tank(s) Exceed Tolerance / Shortage Trend
                    </span>
                @else
                    <span class="fw-bold text-success small">
                        <i class="bi bi-check-circle-fill me-1"></i>All Tanks Within Normal Behaviour
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

<!-- Row 1: Liquid Funds & Closing Balances -->
<div class="d-flex align-items-center justify-content-between mb-1.5">
    <div class="d-flex align-items-center gap-2">
        <span class="text-uppercase text-muted fw-bold" style="letter-spacing: 0.5px; font-size: 0.7rem;">
            <i class="bi bi-wallet2 text-primary me-1"></i>Liquid Funds & Closing Balances
        </span>
    </div>
</div>
<div class="row g-2 g-md-2.5 mb-2.5 mb-md-3">
    <!-- Card 1: Total Liquid Assets (Overall Closing Balance) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-3 border-primary shadow-sm position-relative overflow-hidden">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.68rem;">Total Liquid Assets</span>
                <div class="bg-primary text-white kpi-icon-badge shadow-sm">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
            <div class="fw-bold text-dark mb-1 font-monospace kpi-value-text">
                Rs. {{ number_format($totalLiquidFunds, 2) }}
            </div>
            <div class="d-flex align-items-center justify-content-between text-muted flex-wrap gap-1" style="font-size: 0.7rem;">
                <span>Total: <strong class="text-dark">Cash + Banks + JazzCash</strong></span>
                <a href="{{ route('accounts.index') }}" class="text-primary text-decoration-none fw-semibold">View All &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Card 2: Cash in Vault (Physical Cash Closing Balance) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-3 border-success shadow-sm position-relative overflow-hidden">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-success small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.68rem;">Cash In Vault</span>
                <div class="bg-success text-white kpi-icon-badge shadow-sm">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <div class="fw-bold text-dark mb-1 font-monospace kpi-value-text">
                Rs. {{ number_format($cashInVault, 2) }}
            </div>
            <div class="d-flex align-items-center justify-content-between text-muted flex-wrap gap-1" style="font-size: 0.7rem;">
                <span>
                    <i class="bi bi-circle-fill text-success me-1" style="font-size: 0.45rem;"></i>Physical Cash
                </span>
                <a href="{{ route('ledgers.cash-book') }}" class="text-success text-decoration-none fw-semibold">Cash Book &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Card 3: Bank Accounts (Commercial Bank Balances) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-3 shadow-sm position-relative overflow-hidden" style="border-color: #0284c7 !important;">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small fw-semibold text-uppercase" style="color: #0284c7; letter-spacing: 0.5px; font-size: 0.68rem;">Bank Accounts</span>
                <div class="text-white kpi-icon-badge shadow-sm" style="background: #0284c7;">
                    <i class="bi bi-bank2"></i>
                </div>
            </div>
            <div class="fw-bold text-dark mb-1 font-monospace kpi-value-text">
                Rs. {{ number_format($bankFunds, 2) }}
            </div>
            <div class="d-flex align-items-center justify-content-between text-muted flex-wrap gap-1" style="font-size: 0.7rem;">
                <span>
                    <i class="bi bi-building me-1" style="color: #0284c7;"></i><strong class="text-dark">{{ $accounts->where('type', 'bank')->count() }}</strong> {{ Str::plural('Bank', $accounts->where('type', 'bank')->count()) }}
                </span>
                <a href="{{ route('ledgers.bank-book') }}" class="text-decoration-none fw-semibold" style="color: #0284c7;">Bank Book &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Card 4: JazzCash / Digital Wallet Balance -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-3 border-danger shadow-sm position-relative overflow-hidden">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-danger small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.68rem;">JazzCash / Digital</span>
                <div class="bg-danger text-white kpi-icon-badge shadow-sm">
                    <i class="bi bi-phone"></i>
                </div>
            </div>
            <div class="fw-bold text-dark mb-1 font-monospace kpi-value-text">
                Rs. {{ number_format($jazzFunds, 2) }}
            </div>
            <div class="d-flex align-items-center justify-content-between text-muted flex-wrap gap-1" style="font-size: 0.7rem;">
                <span>
                    <i class="bi bi-phone-vibrate me-1 text-danger"></i><strong class="text-dark">{{ $accounts->where('type', 'jazzcash')->count() }}</strong> {{ Str::plural('Channel', $accounts->where('type', 'jazzcash')->count()) }}
                </span>
                @if($jazzAcc = $accounts->where('type', 'jazzcash')->first())
                    <a href="{{ route('ledgers.bank-book', ['account_id' => $jazzAcc->id]) }}" class="text-danger text-decoration-none fw-semibold">Ledger &rarr;</a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Today's Shift & Cash Operations -->
<div class="d-flex align-items-center justify-content-between mb-1.5">
    <div class="d-flex align-items-center gap-2">
        <span class="text-uppercase text-muted fw-bold" style="letter-spacing: 0.5px; font-size: 0.7rem;">
            <i class="bi bi-activity text-success me-1"></i>Today's Operations & Shift Activity
        </span>
    </div>
</div>
<div class="row g-2 g-md-2.5 mb-2.5 mb-md-3">
    <!-- Card 1: Today's Total Inflow -->
    <div class="col-12 col-md-4">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-3 border-success shadow-sm position-relative overflow-hidden">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-success small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.68rem;">Today's Collections (In)</span>
                <div class="bg-success text-white kpi-icon-badge shadow-sm">
                    <i class="bi bi-arrow-down-left-circle"></i>
                </div>
            </div>
            <div class="fw-bold text-success mb-1 font-monospace kpi-value-text">
                + Rs. {{ number_format($todayInflow, 2) }}
            </div>
            <div class="text-muted small text-truncate" style="font-size: 0.7rem;">
                <i class="bi bi-check-circle-fill text-success me-1"></i>Shift cash sales + customer receipts
            </div>
        </div>
    </div>

    <!-- Card 2: Today's Total Outflow -->
    <div class="col-12 col-md-4">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-3 border-danger shadow-sm position-relative overflow-hidden">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-danger small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.68rem;">Today's Disbursements (Out)</span>
                <div class="bg-danger text-white kpi-icon-badge shadow-sm">
                    <i class="bi bi-arrow-up-right-circle"></i>
                </div>
            </div>
            <div class="fw-bold text-danger mb-1 font-monospace kpi-value-text">
                - Rs. {{ number_format($todayOutflow, 2) }}
            </div>
            <div class="text-muted small text-truncate" style="font-size: 0.7rem;">
                <i class="bi bi-dash-circle-fill text-danger me-1"></i>Shift expenses, returns + supplier & staff payments
            </div>
        </div>
    </div>

    <!-- Card 3: Monthly Average Day Sale (Sales minus Returns divided by operating days) -->
    <div class="col-12 col-md-4">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-3 shadow-sm position-relative overflow-hidden" style="border-color: #6366f1 !important;">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small fw-semibold text-uppercase" style="color: #6366f1; letter-spacing: 0.5px; font-size: 0.68rem;">Month Avg Day Sale</span>
                <div class="text-white kpi-icon-badge shadow-sm" style="background: #6366f1;">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
            <div class="fw-bold mb-1 font-monospace kpi-value-text text-dark">
                Rs. {{ number_format($monthlyAvgDaySale, 2) }}
            </div>
            <div class="d-flex align-items-center justify-content-between text-muted flex-wrap gap-1" style="font-size: 0.7rem;">
                <span class="text-truncate">
                    <i class="bi bi-calendar-event me-1 text-primary"></i>Sale minus returns ({{ $monthlyDaysCount }} {{ Str::plural('Day', $monthlyDaysCount) }})
                </span>
                <a href="{{ route('day-closings.index') }}" class="text-decoration-none fw-semibold" style="color: #6366f1;">Day Closings &rarr;</a>
            </div>
        </div>
    </div>
</div>

<!-- Financial Charts Row -->
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <!-- Chart 1: 7-Day Inflow vs Outflow Performance -->
    <div class="col-12 col-xl-8">
        <div class="card-custom p-3 p-md-4 h-100 shadow-sm overflow-hidden">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-graph-up-arrow text-primary me-2"></i>7-Day Cash Movement Trend
                    </h6>
                    <small class="text-muted" style="font-size: 0.75rem;">Daily collections (In) versus disbursements (Out)</small>
                </div>
                <div class="d-flex align-items-center gap-2 gap-sm-3 small fw-semibold">
                    <span class="d-flex align-items-center gap-1 text-success" style="font-size: 0.78rem;">
                        <span style="display:inline-block; width:9px; height:9px; border-radius:50%; background:#10b981;"></span> In
                    </span>
                    <span class="d-flex align-items-center gap-1 text-danger" style="font-size: 0.78rem;">
                        <span style="display:inline-block; width:9px; height:9px; border-radius:50%; background:#ef4444;"></span> Out
                    </span>
                </div>
            </div>
            <div class="chart-box-wrapper" style="height: 250px;">
                <canvas id="cashFlowChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: Account Liquidity Breakdown -->
    <div class="col-12 col-xl-4">
        <div class="card-custom p-3 p-md-4 h-100 shadow-sm overflow-hidden">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-pie-chart text-primary me-2"></i>Fund Allocation
                    </h6>
                    <small class="text-muted" style="font-size: 0.75rem;">Share across physical vault and banks</small>
                </div>
                <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-2" style="font-size: 0.72rem;">
                    Accounts
                </a>
            </div>
            <div class="chart-box-wrapper" style="height: 190px;">
                <canvas id="liquidityDonutChart"></canvas>
            </div>
            <div class="row g-2 mt-2 pt-2 border-top text-center" style="font-size: 0.75rem;">
                <div class="col-6 border-end">
                    <span class="text-muted d-block" style="font-size: 0.7rem;">Party Receivables</span>
                    <strong class="text-primary fw-bold font-monospace">Rs. {{ number_format($totalReceivables, 0) }}</strong>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block" style="font-size: 0.7rem;">Party Payables</span>
                    <strong class="text-danger fw-bold font-monospace">Rs. {{ number_format($totalPayables, 0) }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Middle Section: Today's Shift Status & Liquid Accounts -->
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <!-- Left: Today's Morning & Evening Shift Cards -->
    <div class="col-12 col-lg-6">
        <div class="card-custom p-3 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark m-0">
                    <i class="bi bi-clock-history text-primary me-2"></i>Today's Shift Registers ({{ date('d M, Y') }})
                </h6>
                <a href="{{ route('shift-closings.index') }}" class="btn btn-light btn-sm rounded-2 py-0 px-2" style="font-size: 0.74rem;">
                    All Shifts <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="row g-2 g-sm-3">
                <!-- Morning Shift -->
                <div class="col-12 col-sm-6">
                    <div class="p-3 rounded-3 border {{ $morningShift ? 'bg-white' : 'bg-light' }} h-100 shadow-xs">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1">Morning Shift</span>
                            @if($morningShift)
                                {!! $morningShift->status_badge !!}
                            @else
                                <span class="badge bg-light text-muted border">Pending</span>
                            @endif
                        </div>
                        @if($morningShift)
                            <div class="small text-muted mb-1 text-truncate">Cashier: <strong>{{ $morningShift->cashier->name ?? 'User #' . $morningShift->cashier_id }}</strong></div>
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span class="text-muted">Gross Sale:</span>
                                <span class="fw-bold text-dark font-monospace">Rs. {{ number_format($morningShift->total_sale, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span class="text-muted">Physical Cash:</span>
                                <span class="fw-bold text-success font-monospace">Rs. {{ number_format($morningShift->total_counted_cash, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small pt-1">
                                <span class="text-muted">Variance:</span>
                                <span class="fw-bold font-monospace {{ $morningShift->difference < 0 ? 'text-danger' : 'text-success' }}">
                                    Rs. {{ number_format($morningShift->difference, 0) }}
                                </span>
                            </div>
                            <div class="mt-2 text-end">
                                <a href="{{ route('shift-closings.show', $morningShift->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.72rem;">
                                    View Sheet
                                </a>
                            </div>
                        @else
                            <div class="text-center py-3 text-muted">
                                <i class="bi bi-hourglass-split fs-4 d-block mb-1 text-warning"></i>
                                <span class="small">Morning register not submitted yet.</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Evening Shift -->
                <div class="col-12 col-sm-6">
                    <div class="p-3 rounded-3 border {{ $eveningShift ? 'bg-white' : 'bg-light' }} h-100 shadow-xs">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-indigo-subtle border px-2 py-1" style="background:#ede9fe; color:#4338ca;">Evening Shift</span>
                            @if($eveningShift)
                                {!! $eveningShift->status_badge !!}
                            @else
                                <span class="badge bg-light text-muted border">Pending</span>
                            @endif
                        </div>
                        @if($eveningShift)
                            <div class="small text-muted mb-1 text-truncate">Cashier: <strong>{{ $eveningShift->cashier->name ?? 'User #' . $eveningShift->cashier_id }}</strong></div>
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span class="text-muted">Gross Sale:</span>
                                <span class="fw-bold text-dark font-monospace">Rs. {{ number_format($eveningShift->total_sale, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span class="text-muted">Physical Cash:</span>
                                <span class="fw-bold text-success font-monospace">Rs. {{ number_format($eveningShift->total_counted_cash, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small pt-1">
                                <span class="text-muted">Variance:</span>
                                <span class="fw-bold font-monospace {{ $eveningShift->difference < 0 ? 'text-danger' : 'text-success' }}">
                                    Rs. {{ number_format($eveningShift->difference, 0) }}
                                </span>
                            </div>
                            <div class="mt-2 text-end">
                                <a href="{{ route('shift-closings.show', $eveningShift->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.72rem;">
                                    View Sheet
                                </a>
                            </div>
                        @else
                            <div class="text-center py-3 text-muted">
                                <i class="bi bi-hourglass-split fs-4 d-block mb-1 text-indigo" style="color: #6366f1;"></i>
                                <span class="small">Evening register not submitted yet.</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Liquid Balances & Accounts Grid -->
    <div class="col-12 col-lg-6">
        <div class="card-custom p-3 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark m-0">
                    <i class="bi bi-wallet2 text-success me-2"></i>Accounts & Payment Channels
                </h6>
                <a href="{{ route('accounts.index') }}" class="btn btn-light btn-sm rounded-2 py-0 px-2" style="font-size: 0.74rem;">
                    Manage <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Desktop View: Standard Table -->
            <div class="d-none d-md-block table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th class="text-end">Balance (Rs.)</th>
                            <th class="text-center" style="width: 70px;">Ledger</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($accounts as $acc)
                            <tr>
                                <td class="fw-bold text-dark">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($acc->type === 'cash')
                                            <i class="bi bi-cash-stack text-success"></i>
                                        @elseif($acc->type === 'jazzcash')
                                            <i class="bi bi-phone text-danger"></i>
                                        @else
                                            <i class="bi bi-bank text-primary"></i>
                                        @endif
                                        <span>{{ $acc->name }}</span>
                                    </div>
                                    @if($acc->account_number)
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">{{ $acc->account_number }}</small>
                                    @endif
                                </td>
                                <td>{!! $acc->type_badge !!}</td>
                                <td class="text-end font-monospace fw-bold {{ $acc->current_balance < 0 ? 'text-danger' : 'text-dark' }}">
                                    Rs. {{ number_format($acc->current_balance, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($acc->type === 'cash')
                                        <a href="{{ route('ledgers.cash-book', ['account_id' => $acc->id]) }}" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-2" title="View Cash Book">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('ledgers.bank-book', ['account_id' => $acc->id]) }}" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-2" title="View Bank Book">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile View: Clean Account Cards (Strictly 0 Horizontal Scroll) -->
            <div class="d-block d-md-none">
                <div class="d-flex flex-column gap-2">
                    @foreach($accounts as $acc)
                        <div class="p-2.5 rounded-3 border bg-light bg-opacity-50">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1 overflow-hidden">
                                    @if($acc->type === 'cash')
                                        <div class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-cash-stack"></i>
                                        </div>
                                    @elseif($acc->type === 'jazzcash')
                                        <div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-phone"></i>
                                        </div>
                                    @else
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-bank"></i>
                                        </div>
                                    @endif
                                    <div class="overflow-hidden min-w-0">
                                        <div class="fw-bold text-dark small text-truncate">{{ $acc->name }}</div>
                                        <div class="d-flex align-items-center gap-1 mt-0.5">
                                            {!! $acc->type_badge !!}
                                            @if($acc->account_number)
                                                <small class="text-muted font-monospace" style="font-size: 0.68rem;">{{ $acc->account_number }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="font-monospace fw-bold small {{ $acc->current_balance < 0 ? 'text-danger' : 'text-dark' }}">
                                        Rs. {{ number_format($acc->current_balance, 2) }}
                                    </div>
                                    <div class="mt-1">
                                        @if($acc->type === 'cash')
                                            <a href="{{ route('ledgers.cash-book', ['account_id' => $acc->id]) }}" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-2" style="font-size: 0.7rem;">
                                                <i class="bi bi-journal-text me-1"></i>Ledger
                                            </a>
                                        @else
                                            <a href="{{ route('ledgers.bank-book', ['account_id' => $acc->id]) }}" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-2" style="font-size: 0.7rem;">
                                                <i class="bi bi-journal-text me-1"></i>Ledger
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Section: Recent Vouchers & Activity Audit Feed -->
<div class="row g-2 g-md-3">
    <!-- Left: Recent Vouchers (Payments In & Out) -->
    <div class="col-12 col-xl-7">
        <div class="card-custom p-3 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark m-0">
                    <i class="bi bi-arrow-left-right text-primary me-2"></i>Recent Financial Vouchers
                </h6>
                <a href="{{ route('transactions.index') }}" class="btn btn-light btn-sm rounded-2 py-0 px-2" style="font-size: 0.74rem;">
                    All Vouchers <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Desktop View: Standard Table -->
            <div class="d-none d-md-block table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Ref #</th>
                            <th style="width: 110px;">Type</th>
                            <th>Party / Payee</th>
                            <th>Account</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center" style="width: 50px;">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $tx)
                            <tr>
                                <td class="font-monospace fw-bold text-muted">#{{ str_pad($tx->id, 5, '0', STR_PAD_LEFT) }}</td>
                                <td>{!! $tx->type_badge !!}</td>
                                <td class="fw-semibold text-dark">
                                    {{ $tx->party->name ?? 'Direct Counter' }}
                                    @if($tx->description)
                                        <small class="text-muted d-block text-truncate" style="max-width: 160px; font-size: 0.72rem;">{{ $tx->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 small">
                                        {{ $tx->account->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-end font-monospace fw-bold {{ $tx->type === 'payment_in' ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->type === 'payment_in' ? '+' : '-' }} Rs. {{ number_format($tx->amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('transactions.show', $tx->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2 rounded-2">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-receipt text-muted fs-4 d-block mb-1"></i>
                                    No financial vouchers recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile View: Clean Voucher Cards (Strictly 0 Horizontal Scroll) -->
            <div class="d-block d-md-none">
                <div class="d-flex flex-column gap-2">
                    @forelse($recentTransactions as $tx)
                        <div class="p-2.5 rounded-3 border bg-white shadow-xs">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="font-monospace fw-bold text-muted small">#{{ str_pad($tx->id, 5, '0', STR_PAD_LEFT) }}</span>
                                {!! $tx->type_badge !!}
                            </div>
                            <div class="d-flex justify-content-between align-items-end gap-2 mt-1">
                                <div class="overflow-hidden min-w-0 flex-grow-1">
                                    <div class="fw-bold text-dark small text-truncate">
                                        {{ $tx->party->name ?? 'Direct Counter' }}
                                    </div>
                                    <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                        <span class="badge bg-light text-dark border px-1.5 py-0.5" style="font-size: 0.68rem;">
                                            {{ $tx->account->name ?? 'N/A' }}
                                        </span>
                                        @if($tx->description)
                                            <small class="text-muted text-truncate d-inline-block" style="max-width: 140px; font-size: 0.7rem;" title="{{ $tx->description }}">{{ $tx->description }}</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="font-monospace fw-bold small {{ $tx->type === 'payment_in' ? 'text-success' : 'text-danger' }}">
                                        {{ $tx->type === 'payment_in' ? '+' : '-' }} Rs. {{ number_format($tx->amount, 2) }}
                                    </div>
                                    <div class="mt-1">
                                        <a href="{{ route('transactions.show', $tx->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2 rounded-2" style="font-size: 0.7rem;">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-receipt text-muted fs-4 d-block mb-1"></i>
                            No financial vouchers recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Live Audit Trail Activity Stream -->
    <div class="col-12 col-xl-5">
        <div class="card-custom p-3 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark m-0">
                    <i class="bi bi-shield-check text-info me-2"></i>Live Audit Trail
                </h6>
                @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-light btn-sm rounded-2 py-0 px-2" style="font-size: 0.74rem;">
                        Full Logs <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                @endif
            </div>

            <div class="d-flex flex-column gap-2">
                @forelse($recentLogs as $log)
                    <div class="p-2 rounded-2 border bg-light d-flex align-items-start gap-2 overflow-hidden">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold mt-1 flex-shrink-0" style="width: 26px; height: 26px; font-size: 0.7rem;">
                            {{ strtoupper(substr($log->user->name ?? 'S', 0, 1)) }}
                        </div>
                        <div class="flex-grow-1 overflow-hidden min-w-0">
                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                <span class="fw-semibold text-dark small text-truncate">{{ $log->user->name ?? 'System' }}</span>
                                <span class="text-muted flex-shrink-0" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-1 mb-1 flex-wrap">
                                {!! $log->action_badge !!}
                                <span class="badge bg-secondary-subtle text-secondary border px-1" style="font-size: 0.68rem;">{{ $log->module }}</span>
                            </div>
                            <div class="text-secondary small text-truncate" style="font-size: 0.75rem;" title="{{ $log->details }}">
                                {{ $log->details }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-shield-slash text-muted fs-4 d-block mb-1"></i>
                        No audit activities recorded yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@include('tanks.partials.dip_modal')
@endsection

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isMobile = window.innerWidth < 768;

    // 1. Cash Movement Trend Bar Chart
    const ctxCashFlow = document.getElementById('cashFlowChart');
    if (ctxCashFlow) {
        new Chart(ctxCashFlow, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Collections (In)',
                        data: {!! json_encode($chartInflows) !!},
                        backgroundColor: '#10b981',
                        borderRadius: 5,
                        maxBarThickness: isMobile ? 14 : 26,
                    },
                    {
                        label: 'Disbursements (Out)',
                        data: {!! json_encode($chartOutflows) !!},
                        backgroundColor: '#ef4444',
                        borderRadius: 5,
                        maxBarThickness: isMobile ? 14 : 26,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': Rs. ' + Number(context.parsed.y).toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            callback: function(value) {
                                if (window.innerWidth < 576) {
                                    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return (value / 1000).toFixed(0) + 'k';
                                    return value;
                                }
                                return 'Rs. ' + Number(value).toLocaleString();
                            },
                            font: {
                                size: isMobile ? 9 : 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: isMobile ? 9 : 11
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Liquid Fund Allocation Doughnut Chart
    const ctxLiquidity = document.getElementById('liquidityDonutChart');
    if (ctxLiquidity) {
        new Chart(ctxLiquidity, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($accountLabels) !!},
                datasets: [{
                    data: {!! json_encode($accountBalances) !!},
                    backgroundColor: [
                        '#10b981', // Green for cash
                        '#2563eb', // Blue for bank
                        '#e11d48', // Red for jazzcash
                        '#8b5cf6', // Purple for others
                        '#f59e0b'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 8,
                            font: {
                                size: isMobile ? 10 : 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': Rs. ' + Number(context.parsed).toLocaleString();
                            }
                        }
                    }
                },
                cutout: '68%'
            }
        });
    }
});
</script>
@endpush
