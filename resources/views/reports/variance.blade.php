@extends('layouts.app')

@section('title', 'Cash Variance & Discrepancy Audit Report - FinanceDesk')
@section('page_title', 'Cash Variance & Discrepancy Audit')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-shield-exclamation me-1 text-danger"></i> Discrepancy Audit
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-1 gap-sm-2 w-100 justify-content-start justify-content-sm-end">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Audit Report
        </button>
        <a href="{{ route('reports.expenses') }}" class="btn btn-outline-danger btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-receipt-cutoff me-1"></i> Expense Reports
        </a>
        <a href="{{ route('shift-closings.index') }}" class="btn btn-light btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-clock-history me-1"></i> Shift Registers
        </a>
    </div>
@endsection

@push('styles')
<style>
    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .kpi-title {
        font-size: 0.7rem;
        letter-spacing: 0.4px;
    }
    .kpi-amount {
        font-size: clamp(1.05rem, 3.5vw, 1.35rem);
        line-height: 1.25;
        word-break: break-word;
    }
    @media (max-width: 575.98px) {
        .card-custom {
            border-radius: 10px;
        }
    }
    @media print {
        .vip-navbar, .no-print, header, nav, .btn, .sub-header {
            display: none !important;
        }
        body {
            background-color: #fff !important;
            color: #000 !important;
            padding: 0 !important;
        }
        .card-custom {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }
    }
</style>
@endpush

@section('content')
<!-- Filter Bar (No-Print) -->
<div class="card-custom p-3 mb-3 mb-md-4 no-print">
    <form method="GET" action="{{ route('reports.variance') }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">From Date</label>
            <input type="date" name="from_date" class="form-control form-control-sm py-2" value="{{ $fromDate }}">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">To Date</label>
            <input type="date" name="to_date" class="form-control form-control-sm py-2" value="{{ $toDate }}">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">Shift</label>
            <select name="shift_type" class="form-select form-select-sm py-2">
                <option value="">All Shifts</option>
                <option value="morning" {{ $shiftType === 'morning' ? 'selected' : '' }}>Morning Shift</option>
                <option value="evening" {{ $shiftType === 'evening' ? 'selected' : '' }}>Evening Shift</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">Filter Scope</label>
            <select name="filter_mode" class="form-select form-select-sm py-2">
                <option value="discrepancy_only" {{ $filterMode === 'discrepancy_only' ? 'selected' : '' }}>Discrepancies Only</option>
                <option value="all" {{ $filterMode === 'all' ? 'selected' : '' }}>All Records</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm rounded-3 w-100 fw-semibold py-2">
                <i class="bi bi-filter me-1"></i> Apply Filter
            </button>
        </div>
    </form>
</div>

<!-- Printable Variance Sheet -->
<div class="card-custom p-3 p-md-5 bg-white shadow-sm mb-4" id="varianceAuditSheet">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 pb-md-4 mb-3 mb-md-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="bg-danger text-white rounded-2 p-1 px-2 fw-bold">
                    <i class="bi bi-shield-shaded"></i>
                </div>
                <h4 class="fw-bold text-dark m-0">FinanceDesk</h4>
            </div>
            <h5 class="text-secondary fw-bold m-0" style="font-size: clamp(1rem, 3.5vw, 1.25rem);">Cash Variance & Difference Audit Register</h5>
            <small class="text-muted">Proware Technologies &bull; Cash Discrepancy & Shift Audit Trail</small>
        </div>
        <div class="text-start text-md-end w-100 w-md-auto">
            <span class="badge bg-light text-dark border px-2 py-1 mb-1 font-monospace d-inline-block">
                Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}
            </span>
            <div class="small text-muted" style="font-size: 0.72rem;">Audited: {{ now()->format('d M, Y - h:i A') }}</div>
            <div class="small text-muted" style="font-size: 0.72rem;">Investigator: <strong>{{ auth()->user()->name }}</strong></div>
        </div>
    </div>

    <!-- Executive Metrics -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <div class="col-6 col-md-3">
            <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle h-100">
                <span class="text-danger small fw-semibold d-block mb-1 kpi-title">Total Shortages (Short Cash)</span>
                <h5 class="fw-bold font-monospace text-danger m-0 kpi-amount">Rs. {{ number_format(abs($totalShortages), 2) }}</h5>
                <small class="text-danger-emphasis" style="font-size: 0.7rem;">{{ $shortageCount }} incidents recorded</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-primary-subtle rounded-3 border border-primary-subtle h-100">
                <span class="text-primary small fw-semibold d-block mb-1 kpi-title">Total Surpluses (Excess Cash)</span>
                <h5 class="fw-bold font-monospace text-primary m-0 kpi-amount">+ Rs. {{ number_format($totalSurpluses, 2) }}</h5>
                <small class="text-primary-emphasis" style="font-size: 0.7rem;">{{ $surplusCount }} incidents recorded</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-light rounded-3 border h-100">
                <span class="text-muted small d-block mb-1 kpi-title">Net Cash Variance</span>
                <h5 class="fw-bold font-monospace {{ $netVariance < 0 ? 'text-danger' : ($netVariance > 0 ? 'text-primary' : 'text-success') }} m-0 kpi-amount">
                    Rs. {{ number_format($netVariance, 2) }}
                </h5>
                <small class="text-muted" style="font-size: 0.7rem;">Reconciliation Impact</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-dark text-white rounded-3 shadow-sm h-100">
                <span class="text-white-50 small fw-semibold d-block mb-1 kpi-title">Audited Shifts Analyzed</span>
                <h5 class="fw-bold font-monospace text-white m-0 kpi-amount">{{ $records->count() }} Shifts</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Scope: {{ $filterMode === 'discrepancy_only' ? 'Differences Only' : 'All Shifts' }}</small>
            </div>
        </div>
    </div>

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block table-responsive mb-4">
        <table class="table table-sm table-bordered table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 105px;">Date</th>
                    <th style="width: 110px;">Shift</th>
                    <th>Cashier</th>
                    <th class="text-end">Expected Cash</th>
                    <th class="text-end">Counted Cash</th>
                    <th class="text-end">Difference (Rs.)</th>
                    <th class="text-center" style="width: 120px;">Variance Result</th>
                    <th>Remarks / Notes</th>
                    <th class="text-center no-print" style="width: 80px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        <td class="fw-bold text-dark">{{ $rec->date->format('d-m-Y') }}</td>
                        <td>{!! $rec->shift_badge !!}</td>
                        <td class="fw-semibold">{{ $rec->cashier->name ?? 'User #' . $rec->cashier_id }}</td>
                        <td class="text-end font-monospace text-secondary">Rs. {{ number_format($rec->expected_cash, 2) }}</td>
                        <td class="text-end font-monospace text-dark fw-bold">Rs. {{ number_format($rec->total_counted_cash, 2) }}</td>
                        <td class="text-end font-monospace fw-bold {{ $rec->difference < 0 ? 'text-danger' : ($rec->difference > 0 ? 'text-primary' : 'text-success') }}">
                            Rs. {{ number_format($rec->difference, 2) }}
                        </td>
                        <td class="text-center">
                            @if($rec->difference < 0)
                                <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                                    <i class="bi bi-arrow-down-circle me-1"></i>Shortage
                                </span>
                            @elseif($rec->difference > 0)
                                <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                    <i class="bi bi-arrow-up-circle me-1"></i>Surplus
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                    <i class="bi bi-check-circle me-1"></i>Balanced
                                </span>
                            @endif
                        </td>
                        <td class="small text-muted text-truncate" style="max-width: 200px;">
                            {{ $rec->difference_note ?? '-' }}
                        </td>
                        <td class="text-center no-print">
                            <a href="{{ route('shift-closings.show', $rec->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2 rounded-2" title="Inspect Shift Sheet">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-shield-check fs-3 d-block text-success mb-1"></i>
                            No cash discrepancies found for the selected filter criteria!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll) -->
    <div class="d-block d-md-none mb-3">
        <div class="d-flex flex-column gap-2">
            @forelse($records as $rec)
                <div class="p-3 rounded-3 border bg-white shadow-xs">
                    <div class="d-flex align-items-center justify-content-between gap-1 mb-2">
                        <div class="d-flex align-items-center gap-1.5 min-w-0">
                            <span class="fw-bold text-dark">{{ $rec->date->format('d M Y') }}</span>
                            {!! $rec->shift_badge !!}
                        </div>
                        <div class="flex-shrink-0">
                            @if($rec->difference < 0)
                                <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                                    <i class="bi bi-arrow-down-circle me-1"></i>Shortage
                                </span>
                            @elseif($rec->difference > 0)
                                <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                    <i class="bi bi-arrow-up-circle me-1"></i>Surplus
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                    <i class="bi bi-check-circle me-1"></i>Balanced
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="small text-muted mb-2">
                        Cashier: <strong class="text-dark">{{ $rec->cashier->name ?? 'User #' . $rec->cashier_id }}</strong>
                    </div>

                    <!-- Comparison Box -->
                    <div class="p-2.5 rounded-2 bg-light border mb-2">
                        <div class="row g-2" style="font-size: 0.78rem;">
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Expected Cash</span>
                                <span class="font-monospace text-secondary">Rs. {{ number_format($rec->expected_cash, 2) }}</span>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Counted Cash</span>
                                <strong class="text-dark font-monospace">Rs. {{ number_format($rec->total_counted_cash, 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Difference & Actions Row -->
                    <div class="d-flex justify-content-between align-items-center pt-1" style="font-size: 0.76rem;">
                        <div>
                            <span class="text-muted">Discrepancy:</span>
                            <span class="font-monospace fw-bold {{ $rec->difference < 0 ? 'text-danger' : ($rec->difference > 0 ? 'text-primary' : 'text-success') }}">
                                {{ $rec->difference >= 0 ? '+' : '' }}Rs. {{ number_format($rec->difference, 0) }}
                            </span>
                        </div>
                        <a href="{{ route('shift-closings.show', $rec->id) }}" class="btn btn-outline-primary btn-sm py-1 px-2.5 rounded-2 fw-semibold" style="font-size: 0.76rem;">
                            <i class="bi bi-eye me-1"></i> View Sheet
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-shield-check fs-3 d-block text-success mb-1"></i>
                    No cash discrepancies found for the selected filter criteria!
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
