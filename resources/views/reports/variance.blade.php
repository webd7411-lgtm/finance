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
    /* =========================================================
       BANK STATEMENT PRINT & PDF STYLES
       ========================================================= */
    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        html, body {
            background: #ffffff !important;
            color: #0f172a !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            font-size: 8.8pt !important;
            line-height: 1.3 !important;
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Hide Web Navigation and UI elements */
        .vip-navbar, .sub-header, .no-print, .btn, .alert, footer, nav, header, .modal, .modal-backdrop, .btn-close, .dropdown-menu {
            display: none !important;
        }

        /* Container Resets */
        main, .container-fluid, .row, .col-12, .col-xl-12 {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        #varianceAuditSheet, .card-custom {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            background: transparent !important;
        }

        /* Bank Statement Header */
        .bank-stmt-header {
            display: block !important;
            border-bottom: 2px solid #0f172a !important;
            padding-bottom: 8px !important;
            margin-bottom: 12px !important;
        }

        /* Force Ledger Table Display */
        .statement-desktop-table {
            display: block !important;
            width: 100% !important;
            overflow: visible !important;
        }
        .table-responsive {
            overflow: visible !important;
            display: block !important;
        }

        /* Statement Grid & Tables */
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        thead {
            display: table-header-group;
        }
        tfoot {
            display: table-footer-group;
        }

        .table th, .table td,
        .bank-summary-table th, .bank-summary-table td,
        .table-custom th, .table-custom td {
            border: 1px solid #475569 !important;
            padding: 4px 6px !important;
            font-size: 8.2pt !important;
            color: #0f172a !important;
        }

        .table thead th,
        .bank-summary-table thead th,
        .table-custom thead th {
            background-color: #f1f5f9 !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            color: #0f172a !important;
        }

        .badge {
            border: 1px solid #94a3b8 !important;
            background: transparent !important;
            color: #0f172a !important;
            font-size: 7.5pt !important;
            padding: 1px 3px !important;
        }

        /* Signatures block */
        .statement-signatures {
            page-break-inside: avoid;
            margin-top: 25px !important;
            padding-top: 15px !important;
            border-top: 1px solid #0f172a !important;
        }

        .statement-sig-line {
            border-top: 1px dashed #475569;
            width: 80%;
            margin: 0 auto;
            padding-top: 4px;
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
    <!-- OFFICIAL BANK STATEMENT PRINT HEADER (Visible on Print) -->
    <div class="bank-stmt-header d-none d-print-block">
        <div class="d-flex justify-content-between align-items-start pb-2 border-bottom border-dark">
            <div>
                <h3 class="fw-bold m-0 text-uppercase tracking-tight" style="color: #0f172a; font-size: 16pt;">FINANCEDESK ENTERPRISE</h3>
                <div class="small fw-semibold text-secondary">CENTRAL AUDIT & CASH RECONCILIATION DIVISION</div>
                <div class="small text-muted" style="font-size: 8pt;">Proware Technologies &bull; Cash Discrepancy & Shift Audit Trail &bull; Verified Books</div>
            </div>
            <div class="text-end">
                <div class="badge bg-dark text-white text-uppercase px-2 py-1 mb-1" style="font-size: 8.5pt;">Official Statement</div>
                <div class="fw-bold" style="font-size: 11pt; color: #0f172a;">CASH VARIANCE & DISCREPANCY AUDIT</div>
                <div class="small text-muted" style="font-size: 8pt;">Statement Date: {{ now()->format('d M, Y h:i A') }}</div>
            </div>
        </div>

        <div class="row pt-2 pb-1" style="font-size: 8.5pt;">
            <div class="col-7">
                <table class="w-100 border-0" style="border: none !important;">
                    <tr style="border: none !important;"><td style="border: none !important; padding: 1px 0; width: 120px;" class="text-muted fw-semibold">Audit Scope:</td><td style="border: none !important; padding: 1px 0;" class="fw-bold text-dark">{{ $filterMode === 'discrepancy_only' ? 'Discrepant Shifts Only' : 'All Shifts Audited' }} {{ $shiftType ? '('.ucfirst($shiftType).' Shift)' : '' }}</td></tr>
                    <tr style="border: none !important;"><td style="border: none !important; padding: 1px 0;" class="text-muted fw-semibold">Audit Period:</td><td style="border: none !important; padding: 1px 0;" class="fw-bold font-monospace">{{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} &mdash; {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</td></tr>
                    <tr style="border: none !important;"><td style="border: none !important; padding: 1px 0;" class="text-muted fw-semibold">Investigator:</td><td style="border: none !important; padding: 1px 0;" class="fw-semibold">{{ auth()->user()->name }}</td></tr>
                </table>
            </div>
            <div class="col-5 text-end">
                <table class="w-100 border-0 ms-auto" style="border: none !important; max-width: 280px;">
                    <tr style="border: none !important;"><td style="border: none !important; padding: 1px 0;" class="text-muted fw-semibold text-end pe-2">Shortage Incidents:</td><td style="border: none !important; padding: 1px 0;" class="fw-bold text-danger text-end font-monospace">{{ $shortageCount }}</td></tr>
                    <tr style="border: none !important;"><td style="border: none !important; padding: 1px 0;" class="text-muted fw-semibold text-end pe-2">Surplus Incidents:</td><td style="border: none !important; padding: 1px 0;" class="fw-bold text-primary text-end font-monospace">{{ $surplusCount }}</td></tr>
                    <tr style="border: none !important;"><td style="border: none !important; padding: 1px 0;" class="text-muted fw-semibold text-end pe-2">Audit Status:</td><td style="border: none !important; padding: 1px 0;" class="fw-bold text-dark text-end">VERIFIED & BALANCED</td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- PRINT SUMMARY TABLE (Compact Bank Format) -->
    <div class="d-none d-print-block mb-3">
        <table class="bank-summary-table table table-sm w-100 mb-0">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width: 25%;">Total Shortages</th>
                    <th class="text-center" style="width: 25%;">Total Surpluses</th>
                    <th class="text-center" style="width: 25%;">Net Cash Variance</th>
                    <th class="text-center" style="width: 25%;">Total Audited Shifts</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center font-monospace fw-bold text-danger">Rs. {{ number_format(abs($totalShortages), 2) }}</td>
                    <td class="text-center font-monospace fw-bold text-primary">+ Rs. {{ number_format($totalSurpluses, 2) }}</td>
                    <td class="text-center font-monospace fw-bold {{ $netVariance < 0 ? 'text-danger' : ($netVariance > 0 ? 'text-primary' : 'text-success') }}">
                        Rs. {{ number_format($netVariance, 2) }}
                    </td>
                    <td class="text-center font-monospace fw-bold">{{ $records->count() }} Shifts</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Header Section (Screen Only) -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 pb-md-4 mb-3 mb-md-4 flex-wrap gap-2 d-print-none">
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
        <div class="text-start text-md-end">
            <span class="badge bg-light text-dark border px-2 py-1 mb-1 font-monospace d-inline-block">
                Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}
            </span>
            <div class="small text-muted" style="font-size: 0.72rem;">Audited: {{ now()->format('d M, Y - h:i A') }}</div>
            <div class="small text-muted" style="font-size: 0.72rem;">Investigator: <strong>{{ auth()->user()->name }}</strong></div>
        </div>
    </div>

    <!-- Executive Metrics (Screen Only) -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4 d-print-none">
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
    <div class="d-none d-md-block statement-desktop-table table-responsive mb-4">
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
    <div class="d-block d-md-none d-print-none mb-3">
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

    <!-- OFFICIAL STATEMENT SIGNATURES (Bank Statement Format) -->
    <div class="statement-signatures d-none d-print-block mt-4">
        <div class="row text-center">
            <div class="col-4">
                <div class="statement-sig-line"></div>
                <div class="fw-bold small text-uppercase mt-1" style="font-size: 8pt;">Investigator / Auditor</div>
                <div class="text-muted" style="font-size: 7.5pt;">FinanceDesk Compliance</div>
            </div>
            <div class="col-4">
                <div class="statement-sig-line"></div>
                <div class="fw-bold small text-uppercase mt-1" style="font-size: 8pt;">Head Cashier / Supervisor</div>
                <div class="text-muted" style="font-size: 7.5pt;">Verified & Reconciled</div>
            </div>
            <div class="col-4">
                <div class="statement-sig-line"></div>
                <div class="fw-bold small text-uppercase mt-1" style="font-size: 8pt;">Authorized Signatory</div>
                <div class="text-muted" style="font-size: 7.5pt;">Director / Financial Controller</div>
            </div>
        </div>
    </div>
</div>
@endsection
