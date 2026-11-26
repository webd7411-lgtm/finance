@extends('layouts.app')

@section('title', 'Wet-Stock & Tank Dip Reconciliation Report')
@section('page_title', 'Wet-Stock & Tank Dip Variance Report')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-droplet-half me-1 text-primary"></i> Tank Short / Excess Audit
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 no-print" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
        @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
            <a href="{{ route('tanks.settings') }}" class="btn btn-outline-info btn-sm rounded-3 no-print">
                <i class="bi bi-sliders me-1"></i> Tank Settings
            </a>
        @endif
        <a href="{{ route('tanks.index') }}" class="btn btn-outline-primary btn-sm rounded-3 no-print">
            <i class="bi bi-database-fill-gear me-1"></i> Storage Tanks
        </a>
    </div>
@endsection

@section('content')
<!-- Filters -->
<div class="card-custom p-3 mb-3 no-print">
    <form method="GET" action="{{ route('reports.tank-reconciliation') }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">From Date</label>
            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">To Date</label>
            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">Storage Tank</label>
            <select name="tank_id" class="form-select form-select-sm">
                <option value="">All Storage Tanks</option>
                @foreach($tanks as $t)
                    <option value="{{ $t->id }}" {{ (string) $tankId === (string) $t->id ? 'selected' : '' }}>
                        {{ $t->tank_number }} - {{ $t->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">Variance Status</label>
            <select name="variance_status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="balanced" {{ $statusFilter === 'balanced' ? 'selected' : '' }}>Balanced</option>
                <option value="within_tolerance" {{ $statusFilter === 'within_tolerance' ? 'selected' : '' }}>Normal (Tolerance)</option>
                <option value="warning" {{ $statusFilter === 'warning' ? 'selected' : '' }}>Warning</option>
                <option value="critical" {{ $statusFilter === 'critical' ? 'selected' : '' }}>Critical Alert</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">Reconciliation</label>
            <select name="reconcile_status" class="form-select form-select-sm">
                <option value="">All Entries</option>
                <option value="unreconciled" {{ $reconcileFilter === 'unreconciled' ? 'selected' : '' }}>Unreconciled Only</option>
                <option value="reconciled" {{ $reconcileFilter === 'reconciled' ? 'selected' : '' }}>Reconciled Only</option>
            </select>
        </div>
        <div class="col-6 col-md-1">
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">Filter</button>
        </div>
    </form>
</div>

<!-- Executive KPI Strip -->
<div class="row g-2.5 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card-custom p-3 h-100 border-start border-3 border-primary">
            <div class="text-muted text-uppercase small fw-semibold" style="font-size: 0.68rem;">Current Net Unreconciled Variance</div>
            <div class="fw-bold fs-5 font-monospace mt-1 {{ $currentNetUnreconciledLiters < -0.01 ? 'text-danger' : ($currentNetUnreconciledLiters > 0.01 ? 'text-info' : 'text-success') }}">
                {{ $currentNetUnreconciledLiters > 0 ? '+' : '' }}{{ number_format($currentNetUnreconciledLiters, 1) }} L
            </div>
            <div class="text-muted small" style="font-size: 0.72rem;">
                Cost Impact: Rs. {{ number_format($currentNetUnreconciledImpact, 2) }}
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card-custom p-3 h-100 border-start border-3 border-info">
            <div class="text-muted text-uppercase small fw-semibold" style="font-size: 0.68rem;">Period Reconciled Shrinkage / Gain</div>
            <div class="fw-bold fs-5 font-monospace mt-1 {{ $totalReconciledLiters < 0 ? 'text-danger' : 'text-success' }}">
                {{ $totalReconciledLiters > 0 ? '+' : '' }}{{ number_format($totalReconciledLiters, 1) }} L
            </div>
            <div class="text-muted small" style="font-size: 0.72rem;">
                Adjusted Value: Rs. {{ number_format($totalReconciledImpact, 2) }} ({{ $adjustments->count() }} adj.)
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card-custom p-3 h-100 border-start border-3 border-success">
            <div class="text-muted text-uppercase small fw-semibold" style="font-size: 0.68rem;">Normal / Within Tolerance Dips</div>
            <div class="fw-bold fs-5 font-monospace text-success mt-1">{{ $normalCount }} / {{ $totalDips }}</div>
            <div class="text-muted small" style="font-size: 0.72rem;">Normal tank thermal &amp; meter behavior</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card-custom p-3 h-100 border-start border-3 border-danger">
            <div class="text-muted text-uppercase small fw-semibold" style="font-size: 0.68rem;">Warning / Critical Dip Alerts</div>
            <div class="fw-bold fs-5 font-monospace {{ $alertCount > 0 ? 'text-danger' : 'text-success' }} mt-1">{{ $alertCount }}</div>
            <div class="text-muted small" style="font-size: 0.72rem;">Readings exceeding configured threshold</div>
        </div>
    </div>
</div>

<!-- Current Tank Health & Tolerance Overview -->
<div class="card-custom p-3 mb-3">
    <h6 class="fw-bold text-dark mb-2.5">
        <i class="bi bi-database-check text-primary me-1"></i> Live Tank Health &amp; Active Tolerance Rules
    </h6>
    <div class="row g-2.5">
        @foreach($tankSummaries as $ts)
            @php $t = $ts->tank; @endphp
            <div class="col-12 col-md-4">
                <div class="p-2.5 rounded-3 border bg-light bg-opacity-50 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-dark small">{{ $t->tank_number }} &bull; {{ $t->product?->name ?? $t->name }}</span>
                        {!! $t->variance_status_badge !!}
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-1" style="font-size: 0.74rem;">
                        <span>Book: <strong class="text-dark font-monospace">{{ number_format($t->current_stock, 1) }} L</strong></span>
                        <span>Last Dip: <strong class="text-dark font-monospace">{{ $t->last_measured_liters !== null ? number_format($t->last_measured_liters, 1) . ' L' : 'N/A' }}</strong></span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted" style="font-size: 0.7rem;">
                        <span>Tolerance: &plusmn;{{ number_format($ts->thresholds['warning_liters'], 1) }}L / &plusmn;{{ number_format($ts->thresholds['critical_liters'], 1) }}L</span>
                        @if($ts->trend['has_trend'])
                            <span class="badge bg-danger text-white">Trend: {{ $ts->trend['streak'] }} Negative Dips</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Daily Variance Trend Chart -->
@if(!empty($chartLabels))
<div class="card-custom p-3 mb-3 no-print">
    <h6 class="fw-bold text-dark mb-2">
        <i class="bi bi-graph-up text-primary me-1"></i> Dip Variance Trend (Liters: Cumulative vs Period Change)
    </h6>
    <div style="height: 240px; position: relative;">
        <canvas id="dipVarianceTrendChart"></canvas>
    </div>
</div>
@endif

<!-- Detailed Dip Readings Table -->
<div class="card-custom p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold text-dark m-0">
            <i class="bi bi-rulers text-success me-1"></i> Physical Dip Audit Log ({{ $fromDate }} to {{ $toDate }})
        </h6>
        <span class="badge bg-light text-secondary border">{{ $dipReadings->count() }} Entries</span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover align-middle small mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date &amp; Time</th>
                    <th>Tank</th>
                    <th>Context</th>
                    <th class="text-center">Dip ({{ $tankConfig['dip_unit'] }})</th>
                    <th class="text-end">Physical (L)</th>
                    <th class="text-end">Book (L)</th>
                    <th class="text-end">Period &Delta;</th>
                    <th class="text-end">Cumulative Var.</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Reconciled</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dipReadings as $dip)
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">{{ $dip->reading_date->format('d M, Y') }}</div>
                            @if($dip->reading_time)
                                <small class="text-muted">{{ \Carbon\Carbon::parse($dip->reading_time)->format('g:i A') }}</small>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('tanks.show', $dip->tank_id) }}" class="fw-semibold text-decoration-none">
                                {{ $dip->tank?->tank_number }} - {{ $dip->tank?->product?->name ?? $dip->tank?->name }}
                            </a>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $dip->context_label }}</span></td>
                        <td class="text-center font-monospace">{{ number_format($dip->dip_cm, 2) }}</td>
                        <td class="text-end font-monospace fw-bold">{{ number_format($dip->measured_liters, 1) }} L</td>
                        <td class="text-end font-monospace text-secondary">{{ number_format($dip->book_stock_liters, 1) }} L</td>
                        <td class="text-end font-monospace {{ ($dip->period_variance_liters ?? 0) < 0 ? 'text-danger' : 'text-info' }}">
                            @if($dip->period_variance_liters !== null)
                                {{ $dip->period_variance_liters > 0 ? '+' : '' }}{{ number_format($dip->period_variance_liters, 1) }} L
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end font-monospace fw-bold {{ $dip->variance_liters < 0 ? 'text-danger' : ($dip->variance_liters > 0 ? 'text-info' : 'text-success') }}">
                            {{ $dip->variance_liters > 0 ? '+' : '' }}{{ number_format($dip->variance_liters, 1) }} L
                        </td>
                        <td class="text-center">{!! $dip->status_badge !!}</td>
                        <td class="text-center">
                            @if($dip->is_reconciled)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    Yes ({{ $dip->adjusted_liters >= 0 ? '+' : '' }}{{ number_format($dip->adjusted_liters ?? 0, 1) }}L)
                                </span>
                            @else
                                <span class="badge bg-light text-muted border">Pending</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">No dip readings recorded in this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Approved Stock Adjustments Table -->
<div class="card-custom p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold text-dark m-0">
            <i class="bi bi-sliders text-info me-1"></i> Reconciled Stock Adjustments Ledger (P&amp;L Inventory Impact)
        </h6>
        <span class="badge bg-light text-secondary border">{{ $adjustments->count() }} Adjustments</span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover align-middle small mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Tank</th>
                    <th>Reason</th>
                    <th class="text-end">Book Before</th>
                    <th class="text-end">Adjustment (L)</th>
                    <th class="text-end">Book After</th>
                    <th class="text-end">Cost Impact (Rs.)</th>
                    <th>Approved By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($adjustments as $adj)
                    <tr>
                        <td>{{ $adj->adjustment_date->format('d M, Y') }}</td>
                        <td class="fw-semibold">{{ $adj->tank?->tank_number }} &bull; {{ $adj->tank?->name }}</td>
                        <td>
                            {{ $adj->reason_label }}
                            @if($adj->is_auto)
                                <span class="badge bg-info-subtle text-info border ms-1">Auto</span>
                            @endif
                        </td>
                        <td class="text-end font-monospace text-secondary">{{ number_format($adj->book_stock_before, 1) }} L</td>
                        <td class="text-end font-monospace fw-bold {{ $adj->adjustment_liters < 0 ? 'text-danger' : 'text-success' }}">
                            {{ $adj->adjustment_liters > 0 ? '+' : '' }}{{ number_format($adj->adjustment_liters, 1) }} L
                        </td>
                        <td class="text-end font-monospace fw-bold">{{ number_format($adj->book_stock_after, 1) }} L</td>
                        <td class="text-end font-monospace fw-bold {{ $adj->financial_impact < 0 ? 'text-danger' : 'text-success' }}">
                            {{ $adj->financial_impact > 0 ? '+' : '' }}Rs. {{ number_format($adj->financial_impact, 2) }}
                        </td>
                        <td>{{ $adj->creator?->name ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No stock reconciliation adjustments recorded in this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
@if(!empty($chartLabels))
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('dipVarianceTrendChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [
                {
                    label: 'Cumulative Variance (L)',
                    data: {!! json_encode($chartVariances) !!},
                    backgroundColor: {!! json_encode(array_map(fn($v) => $v < 0 ? 'rgba(239, 68, 68, 0.75)' : 'rgba(14, 165, 233, 0.75)', $chartVariances)) !!},
                    borderRadius: 4,
                    maxBarThickness: 26,
                },
                {
                    label: 'Period Change (L)',
                    data: {!! json_encode($chartPeriodChanges) !!},
                    type: 'line',
                    borderColor: '#6366f1',
                    backgroundColor: '#6366f1',
                    tension: 0.25,
                    fill: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endif
@endpush
