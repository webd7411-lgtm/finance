@extends('layouts.app')

@section('title', 'Daily Closing Summary Report - FinanceDesk')
@section('page_title', 'Daily Closing Summary Report')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-calendar2-range me-1 text-primary"></i> Consolidated Closings
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-1 gap-sm-2 w-100 justify-content-start justify-content-sm-end">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Summary
        </button>
        <a href="{{ route('reports.expenses') }}" class="btn btn-outline-danger btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-receipt-cutoff me-1"></i> Expense Reports
        </a>
        <a href="{{ route('day-closings.index') }}" class="btn btn-light btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-calendar2-check me-1"></i> Day Closing Register
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
    <form method="GET" action="{{ route('reports.daily-closing') }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-4">
            <label class="form-label text-muted small fw-semibold mb-1">From Date</label>
            <input type="date" name="from_date" class="form-control form-control-sm py-2" value="{{ $fromDate }}">
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label text-muted small fw-semibold mb-1">To Date</label>
            <input type="date" name="to_date" class="form-control form-control-sm py-2" value="{{ $toDate }}">
        </div>
        <div class="col-12 col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm rounded-3 w-100 fw-semibold py-2">
                <i class="bi bi-filter me-1"></i> Apply Filter
            </button>
        </div>
    </form>
</div>

<!-- Printable Consolidated Report Sheet -->
<div class="card-custom p-3 p-md-5 bg-white shadow-sm mb-4" id="dailyClosingSummarySheet">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 pb-md-4 mb-3 mb-md-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="bg-primary text-white rounded-2 p-1 px-2 fw-bold">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h4 class="fw-bold text-dark m-0">FinanceDesk</h4>
            </div>
            <h5 class="text-secondary fw-bold m-0" style="font-size: clamp(1rem, 3.5vw, 1.25rem);">Consolidated Daily Closings Audit Report</h5>
            <small class="text-muted">Proware Technologies &bull; Daily Financial Performance & Liquidity Summary</small>
        </div>
        <div class="text-start text-md-end w-100 w-md-auto">
            <span class="badge bg-light text-dark border px-2 py-1 mb-1 font-monospace d-inline-block">
                Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}
            </span>
            <div class="small text-muted" style="font-size: 0.72rem;">Audited: {{ now()->format('d M, Y - h:i A') }}</div>
            <div class="small text-muted" style="font-size: 0.72rem;">Reviewer: <strong>{{ auth()->user()->name }}</strong></div>
        </div>
    </div>

    <!-- Executive Metrics -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <div class="col-6 col-md-3">
            <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle h-100">
                <span class="text-success small fw-semibold d-block mb-1 kpi-title">Total Period Collections (In)</span>
                <h5 class="fw-bold font-monospace text-success m-0 kpi-amount">+ Rs. {{ number_format($grandTotalCashIn, 2) }}</h5>
                <small class="text-success-emphasis" style="font-size: 0.7rem;">Shift Sales & Recoveries</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle h-100">
                <span class="text-danger small fw-semibold d-block mb-1 kpi-title">Total Period Payments (Out)</span>
                <h5 class="fw-bold font-monospace text-danger m-0 kpi-amount">- Rs. {{ number_format($grandTotalPaymentsOut, 2) }}</h5>
                <small class="text-danger-emphasis" style="font-size: 0.7rem;">Suppliers, Advances & Expenses</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-light rounded-3 border h-100">
                <span class="text-muted small d-block mb-1 kpi-title">Cumulative Shift Variance</span>
                <h5 class="fw-bold font-monospace {{ $grandTotalDifference < 0 ? 'text-danger' : ($grandTotalDifference > 0 ? 'text-primary' : 'text-success') }} m-0 kpi-amount">
                    Rs. {{ number_format($grandTotalDifference, 2) }}
                </h5>
                <small class="text-muted" style="font-size: 0.7rem;">Net Cash Discrepancies</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-primary text-white rounded-3 shadow-sm h-100">
                <span class="text-white-50 small fw-semibold d-block mb-1 kpi-title">Latest Day Closing Cash</span>
                <h5 class="fw-bold font-monospace text-white m-0 kpi-amount">Rs. {{ number_format($latestClosingCash, 2) }}</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Vault / Physical Balance</small>
            </div>
        </div>
    </div>

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block table-responsive mb-4">
        <table class="table table-sm table-bordered table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 110px;">Date</th>
                    <th style="width: 95px;">Ref #</th>
                    <th class="text-end">Opening Cash</th>
                    <th class="text-end">Total Cash In</th>
                    <th class="text-end">Payments Out</th>
                    <th class="text-end">Closing Cash</th>
                    <th class="text-end">Variance</th>
                    <th class="text-center" style="width: 95px;">Status</th>
                    <th>Finalized By</th>
                    <th class="text-center no-print" style="width: 80px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($closings as $c)
                    <tr>
                        <td class="fw-bold text-dark">{{ $c->date->format('d-m-Y') }}</td>
                        <td class="font-monospace fw-bold text-muted">#DC-{{ str_pad($c->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td class="text-end font-monospace">Rs. {{ number_format($c->opening_cash, 2) }}</td>
                        <td class="text-end font-monospace text-success fw-semibold">+ Rs. {{ number_format($c->total_cash_in, 2) }}</td>
                        <td class="text-end font-monospace text-danger fw-semibold">- Rs. {{ number_format($c->total_payments_out, 2) }}</td>
                        <td class="text-end font-monospace fw-bold text-dark">Rs. {{ number_format($c->closing_cash, 2) }}</td>
                        <td class="text-end font-monospace fw-bold {{ $c->total_difference < 0 ? 'text-danger' : ($c->total_difference > 0 ? 'text-primary' : 'text-success') }}">
                            Rs. {{ number_format($c->total_difference, 2) }}
                        </td>
                        <td class="text-center">{!! $c->status_badge !!}</td>
                        <td class="small text-muted">{{ $c->closer->name ?? 'User #' . $c->closed_by }}</td>
                        <td class="text-center no-print">
                            <a href="{{ route('day-closings.show', $c->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2 rounded-2" title="View Full Day Closing Sheet">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-calendar-x fs-3 d-block mb-1"></i>
                            No finalized day closings found within the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll) -->
    <div class="d-block d-md-none mb-3">
        <div class="d-flex flex-column gap-2">
            @forelse($closings as $c)
                <div class="p-3 rounded-3 border bg-white shadow-xs">
                    <div class="d-flex align-items-center justify-content-between gap-1 mb-2">
                        <div class="d-flex align-items-center gap-1.5 min-w-0">
                            <span class="fw-bold text-dark">{{ $c->date->format('d M Y') }}</span>
                            <span class="font-monospace text-muted small">#DC-{{ str_pad($c->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="flex-shrink-0">
                            {!! $c->status_badge !!}
                        </div>
                    </div>

                    <!-- Financial Movement Box -->
                    <div class="p-2.5 rounded-2 bg-light border mb-2">
                        <div class="row g-2" style="font-size: 0.78rem;">
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Opening Cash</span>
                                <span class="font-monospace text-secondary">Rs. {{ number_format($c->opening_cash, 2) }}</span>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Total Cash In</span>
                                <strong class="text-success font-monospace">+Rs. {{ number_format($c->total_cash_in, 2) }}</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Payments Out</span>
                                <span class="text-danger font-monospace fw-semibold">-Rs. {{ number_format($c->total_payments_out, 2) }}</span>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Closing Cash</span>
                                <strong class="text-dark font-monospace fs-6">Rs. {{ number_format($c->closing_cash, 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Variance & Actions Row -->
                    <div class="d-flex justify-content-between align-items-center pt-1" style="font-size: 0.76rem;">
                        <div>
                            <span class="text-muted">Variance:</span>
                            <span class="font-monospace fw-bold {{ $c->total_difference < 0 ? 'text-danger' : ($c->total_difference > 0 ? 'text-primary' : 'text-success') }}">
                                {{ $c->total_difference >= 0 ? '+' : '' }}Rs. {{ number_format($c->total_difference, 0) }}
                            </span>
                        </div>
                        <a href="{{ route('day-closings.show', $c->id) }}" class="btn btn-outline-primary btn-sm py-1 px-2.5 rounded-2 fw-semibold" style="font-size: 0.76rem;">
                            <i class="bi bi-eye me-1"></i> View Sheet
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4 small">
                    No finalized day closings found within the selected period.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
