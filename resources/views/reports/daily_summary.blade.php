@extends('layouts.app')

@section('title', 'Daily Closing Summary Report - FinanceDesk')
@section('page_title', 'Daily Closing Summary Report')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-calendar2-range me-1 text-primary"></i> Consolidated Closings & Transactions
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-1 gap-sm-2 w-100 justify-content-start justify-content-sm-end">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Statement
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
    .section-header-badge {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .bank-summary-table {
        border-collapse: collapse !important;
        width: 100% !important;
        border: 2px solid #0f172a !important;
    }
    .bank-summary-table th, .bank-summary-table td {
        border: 1px solid #cbd5e1 !important;
    }
    @media (max-width: 575.98px) {
        .card-custom {
            border-radius: 10px;
        }
    }
    @media print {
        /* 1. Page Geometry & Base Resets */
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        html, body {
            background: #ffffff !important;
            color: #0f172a !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            font-size: 9pt !important;
            line-height: 1.3 !important;
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        /* Hide all web chrome */
        .vip-navbar, .sub-header, .no-print, .btn, .alert, footer, nav, header, .modal, .modal-backdrop, .btn-close {
            display: none !important;
        }

        /* Container resets - remove web card borders, shadows & margins */
        main, .container-fluid, .row, .col-12, .col-xl-10 {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }
        #dailyClosingSummarySheet, .card-custom {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            background: transparent !important;
        }

        /* Hide icons on print */
        .statement-icon {
            display: none !important;
        }

        /* Always show desktop statement tables and hide mobile card view */
        .statement-desktop-table {
            display: block !important;
        }
        .statement-mobile-cards {
            display: none !important;
        }

        /* Official Statement Print Header */
        .statement-print-header {
            display: block !important;
            border-bottom: 2px solid #0f172a !important;
            margin-bottom: 12px !important;
            padding-bottom: 6px !important;
        }

        /* Bank Statement Summary Table */
        .bank-summary-table {
            width: 100% !important;
            border: 2px solid #0f172a !important;
            margin-bottom: 12px !important;
            page-break-inside: avoid !important;
        }
        .bank-summary-table th, .bank-summary-table td {
            border: 1px solid #0f172a !important;
        }
        .kpi-title {
            font-size: 7pt !important;
        }

        /* Section Headings */
        .statement-section-title {
            font-size: 9pt !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.4px !important;
            border-bottom: 1.5px solid #0f172a !important;
            padding-bottom: 3px !important;
            margin-bottom: 6px !important;
            color: #0f172a !important;
        }

        /* Table styling - Crisp Accounting Statement Borders */
        .table {
            width: 100% !important;
            border-collapse: collapse !important;
            border: 1px solid #334155 !important;
            margin-bottom: 10px !important;
            page-break-inside: auto !important;
        }
        .table tr {
            page-break-inside: avoid !important;
        }
        .table th, .table td {
            border: 1px solid #cbd5e1 !important;
            padding: 3px 5px !important;
            font-size: 8pt !important;
            line-height: 1.25 !important;
            color: #0f172a !important;
            background-color: transparent !important;
        }
        .table thead th, .table th.table-light, tr.table-light th, tr.table-light td {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            font-weight: 700 !important;
            border-bottom: 1.5px solid #0f172a !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .table tfoot tr, tr.total-row, .table tfoot th {
            border-top: 1.5px solid #0f172a !important;
            border-bottom: 2px solid #0f172a !important;
            font-weight: 800 !important;
            background-color: #f8fafc !important;
        }

        /* Badges */
        .badge {
            border: 1px solid #475569 !important;
            background: transparent !important;
            color: #0f172a !important;
            font-weight: 600 !important;
            font-size: 7pt !important;
            padding: 1px 3px !important;
            border-radius: 3px !important;
        }

        /* Signatures Section */
        .statement-signatures {
            margin-top: 25px !important;
            padding-top: 12px !important;
            border-top: 1px solid #94a3b8 !important;
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            page-break-inside: avoid !important;
        }
        .statement-signatures .col-4 {
            flex: 1 1 33.33% !important;
            width: 33.33% !important;
        }
        .statement-sig-line {
            border-top: 1.2px solid #0f172a !important;
            margin: 0 auto !important;
            padding-top: 4px !important;
            width: 75% !important;
        }
        .statement-sig-line small, .statement-sig-line .small {
            font-size: 8pt !important;
        }

        /* Print Footer */
        .statement-print-footer {
            display: block !important;
            page-break-inside: avoid !important;
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

<!-- Consolidated Report Sheet -->
<div class="card-custom p-3 p-md-4 bg-white shadow-sm mb-4" id="dailyClosingSummarySheet">

    <!-- Statement Header (Print Only: Official Audit Statement Header) -->
    <div class="d-none d-print-block statement-print-header mb-3 pb-2 border-bottom border-dark border-2">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4 class="fw-bold text-uppercase m-0" style="letter-spacing: 0.5px; font-size: 15pt; color: #000;">PROWARE TECHNOLOGIES</h4>
                <div class="fw-bold text-dark" style="font-size: 11pt; margin-top: 2px;">CONSOLIDATED DAILY CLOSINGS & CASH FLOW AUDIT STATEMENT</div>
                <div class="text-secondary small" style="font-size: 8.5pt;">Financial Management System &bull; Periodic Audit & Register</div>
            </div>
            <div class="text-end" style="font-size: 9pt; line-height: 1.45; color: #000;">
                <div><strong>Audit Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} &rarr; {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</div>
                <div><strong>Report Date:</strong> {{ now()->format('d M, Y - h:i A') }}</div>
                <div><strong>Audited By:</strong> {{ auth()->user()->name }}</div>
            </div>
        </div>
    </div>

    <!-- Header Section (Screen Only: Modern Web UI with Branding & Badges) -->
    <div class="border-bottom pb-3 mb-3 d-print-none">
        <div class="row align-items-center g-2">
            <div class="col-12 col-md-7">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="bg-primary text-white rounded-2 p-1 px-2 fw-bold d-inline-flex align-items-center justify-content-center">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h4 class="fw-bold text-dark m-0">FinanceDesk</h4>
                </div>
                <h5 class="text-secondary fw-bold m-0" style="font-size: clamp(1rem, 2.5vw, 1.25rem);">Consolidated Daily Closings & Cash Flow Audit</h5>
                <small class="text-muted d-block">Proware Technologies &bull; Daily Financial Performance, Collections & Disbursal Breakdown</small>
            </div>
            <div class="col-12 col-md-5 text-start text-md-end">
                <div class="d-inline-flex flex-column align-items-start align-items-md-end bg-light p-2 rounded-3 border">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-1 font-monospace" style="font-size: 0.78rem;">
                        <i class="bi bi-calendar-range me-1"></i> Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} &rarr; {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}
                    </span>
                    <div class="small text-muted" style="font-size: 0.74rem;">Audited: <span class="fw-semibold text-dark">{{ now()->format('d M, Y - h:i A') }}</span></div>
                    <div class="small text-muted" style="font-size: 0.74rem;">Reviewer: <strong class="text-dark">{{ auth()->user()->name }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Official Bank Statement Period Cash Flow Summary Strip -->
    <div class="mb-4">
        <table class="table table-bordered align-middle mb-0 bank-summary-table" style="border: 2px solid #0f172a; width: 100%;">
            <thead style="background-color: #f1f5f9; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0f172a;">
                <tr>
                    <th class="text-center py-2 text-secondary" style="width: 20%;">1. Opening Balance</th>
                    <th class="text-center py-2 text-success" style="width: 20%;">2. (+) Collections (Credits)</th>
                    <th class="text-center py-2 text-danger" style="width: 20%;">3. (-) Disbursed (Debits)</th>
                    <th class="text-center py-2 text-primary" style="width: 20%;">4. Shift Variance</th>
                    <th class="text-center py-2 text-dark bg-secondary bg-opacity-10" style="width: 20%;">5. (=) Net Closing Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center py-2.5">
                        <span class="d-block text-muted" style="font-size: 7.5pt;">Starting Base</span>
                        <span class="fw-bold font-monospace text-dark fs-6">Rs. {{ number_format($totalOpeningAllAccounts, 2) }}</span>
                    </td>
                    <td class="text-center py-2.5" style="background-color: #f0fdf4;">
                        <span class="d-block text-success fw-semibold" style="font-size: 7.5pt;">Sales & Receipts</span>
                        <span class="fw-bold font-monospace text-success fs-6">+ Rs. {{ number_format($totalInAllAccounts, 2) }}</span>
                    </td>
                    <td class="text-center py-2.5" style="background-color: #fef2f2;">
                        <span class="d-block text-danger fw-semibold" style="font-size: 7.5pt;">Expenses & Suppliers</span>
                        <span class="fw-bold font-monospace text-danger fs-6">- Rs. {{ number_format($totalOutAllAccounts, 2) }}</span>
                    </td>
                    <td class="text-center py-2.5" style="background-color: #f0f9ff;">
                        <span class="d-block text-muted" style="font-size: 7.5pt;">{{ $grandTotalDifference == 0 ? 'Balanced' : ($grandTotalDifference > 0 ? 'Surplus' : 'Shortage') }}</span>
                        <span class="fw-bold font-monospace {{ $grandTotalDifference < 0 ? 'text-danger' : ($grandTotalDifference > 0 ? 'text-primary' : 'text-success') }} fs-6">
                            {{ $grandTotalDifference > 0 ? '+' : '' }}Rs. {{ number_format($grandTotalDifference, 2) }}
                        </span>
                    </td>
                    <td class="text-center py-2.5" style="background-color: #f1f5f9;">
                        <span class="d-block text-secondary fw-semibold" style="font-size: 7.5pt;">Projected Closing</span>
                        <span class="fw-bold font-monospace text-dark fs-6" style="color: #0f172a !important;">Rs. {{ number_format($totalClosingAllAccounts, 2) }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ========================================== -->
    <!-- SECTION 1: CONSOLIDATED DAY CLOSINGS TABLE -->
    <!-- ========================================== -->
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold text-dark m-0 statement-section-title">
                <span class="statement-icon me-1"><i class="bi bi-calendar-check text-primary"></i></span> Daily Closings Register
            </h6>
            <span class="badge bg-light text-secondary border small">{{ $closings->count() }} Days Recorded</span>
        </div>

        <!-- Desktop Statement Table View -->
        <div class="d-none d-md-block statement-desktop-table table-responsive mb-2">
            <table class="table table-sm table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 105px;">Date</th>
                        <th style="width: 95px;">Ref #</th>
                        <th class="text-end">Opening Cash</th>
                        <th class="text-end">(+) Total Cash In</th>
                        <th class="text-end">(-) Payments Out</th>
                        <th class="text-end">(=) Closing Cash</th>
                        <th class="text-end">Variance</th>
                        <th class="text-center" style="width: 95px;">Status</th>
                        <th>Finalized By</th>
                        <th class="text-center no-print" style="width: 75px;">Action</th>
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
                                {{ $c->total_difference > 0 ? '+' : '' }}Rs. {{ number_format($c->total_difference, 2) }}
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
                            <td colspan="10" class="text-center text-muted py-3">
                                No finalized day closings found within the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($closings->isNotEmpty())
                    <tfoot class="table-light">
                        <tr class="fw-bold total-row">
                            <td colspan="3" class="text-end text-uppercase">Period Totals:</td>
                            <td class="text-end font-monospace text-success">+ Rs. {{ number_format($closings->sum('total_cash_in'), 2) }}</td>
                            <td class="text-end font-monospace text-danger">- Rs. {{ number_format($closings->sum('total_payments_out'), 2) }}</td>
                            <td class="text-end font-monospace text-dark">Rs. {{ number_format($closings->first()->closing_cash ?? 0, 2) }}</td>
                            <td class="text-end font-monospace {{ $closings->sum('total_difference') < 0 ? 'text-danger' : 'text-success' }}">
                                {{ $closings->sum('total_difference') > 0 ? '+' : '' }}Rs. {{ number_format($closings->sum('total_difference'), 2) }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <!-- Mobile View: Clean Responsive Cards (Screen Only) -->
        <div class="d-block d-md-none statement-mobile-cards mb-3 no-print">
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
                    <div class="text-center text-muted py-3 small">
                        No finalized day closings found within the selected period.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- SECTION 2: PAYMENTS IN (RECEIVED DETAILS)  -->
    <!-- ========================================== -->
    <div class="mt-4 pt-3 border-top">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h6 class="fw-bold text-success m-0 statement-section-title">
                    <span class="statement-icon me-1"><i class="bi bi-arrow-down-left-circle-fill"></i></span> Payments In (Received Breakdown)
                </h6>
                <small class="text-muted">Party recoveries, customer receipts & funds received into accounts</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                    {{ $paymentsIn->count() }} Entries
                </span>
                <span class="badge bg-success text-white px-2 py-1 font-monospace">
                    Total: + Rs. {{ number_format($totalItemizedPaymentsIn, 2) }}
                </span>
            </div>
        </div>

        <!-- Desktop Statement Payments In Table -->
        <div class="d-none d-md-block statement-desktop-table table-responsive mb-3">
            <table class="table table-sm table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 95px;">Date</th>
                        <th style="width: 90px;">Voucher #</th>
                        <th>Received From (Party)</th>
                        <th>Deposit Account</th>
                        <th>Category / Purpose</th>
                        <th>Remarks / Detail</th>
                        <th class="text-end" style="width: 130px;">Amount (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentsIn as $pin)
                        <tr>
                            <td class="fw-semibold text-dark">{{ $pin->date ? $pin->date->format('d-m-Y') : '-' }}</td>
                            <td class="font-monospace text-muted small">{{ $pin->bill_no ?? '#TX-' . str_pad($pin->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td>
                                @if($pin->party)
                                    <span class="fw-bold text-dark">{{ $pin->party->name }}</span>
                                @else
                                    <span class="text-muted fst-italic">Direct Cash / Counter</span>
                                @endif
                            </td>
                            <td>
                                @if($pin->account)
                                    <span class="badge bg-light text-dark border">{{ $pin->account->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($pin->category)
                                    <span class="badge bg-light text-secondary border">{{ $pin->category->name }}</span>
                                @else
                                    <span class="text-muted small">Party Receipt</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $pin->description ?: 'Payment received towards account / ledger recovery' }}
                            </td>
                            <td class="text-end font-monospace fw-bold text-success">
                                + Rs. {{ number_format($pin->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">
                                No payment in transactions recorded for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($paymentsIn->isNotEmpty())
                    <tfoot class="table-light">
                        <tr class="fw-bold total-row">
                            <th colspan="6" class="text-end text-uppercase">Total Payments Received (In):</th>
                            <th class="text-end font-monospace text-success">+ Rs. {{ number_format($totalItemizedPaymentsIn, 2) }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <!-- Mobile View: Payments In Cards (Screen Only) -->
        <div class="d-block d-md-none statement-mobile-cards mb-3 no-print">
            <div class="d-flex flex-column gap-2">
                @forelse($paymentsIn as $pin)
                    <div class="p-3 rounded-3 border border-success-subtle bg-white shadow-xs">
                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                            <span class="fw-bold text-dark" style="font-size: 0.82rem;">{{ $pin->date ? $pin->date->format('d M Y') : '-' }}</span>
                            <span class="font-monospace text-success fw-bold">+Rs. {{ number_format($pin->amount, 2) }}</span>
                        </div>
                        <div class="mb-1">
                            <span class="text-muted small">From:</span>
                            <strong class="text-dark">{{ $pin->party->name ?? 'Direct Counter Receipt' }}</strong>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mb-1.5" style="font-size: 0.72rem;">
                            @if($pin->account)
                                <span class="badge bg-light text-dark border">{{ $pin->account->name }}</span>
                            @endif
                            @if($pin->bill_no)
                                <span class="badge bg-light text-muted border">Voucher: {{ $pin->bill_no }}</span>
                            @endif
                            @if($pin->category)
                                <span class="badge bg-light text-secondary border">{{ $pin->category->name }}</span>
                            @endif
                        </div>
                        @if($pin->description)
                            <div class="p-1.5 bg-light rounded-2 text-muted" style="font-size: 0.75rem;">
                                {{ $pin->description }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-3 small">
                        No payment in transactions recorded for this period.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- SECTION 3: PAYMENTS OUT (DISBURSED DETAILS) -->
    <!-- ========================================== -->
    <div class="mt-4 pt-3 border-top">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h6 class="fw-bold text-danger m-0 statement-section-title">
                    <span class="statement-icon me-1"><i class="bi bi-arrow-up-right-circle-fill"></i></span> Payments Out (Disbursed & Expenses Breakdown)
                </h6>
                <small class="text-muted">Supplier payments, vendor advances, operational expenses & cash out</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                    {{ $paymentsOut->count() }} Entries
                </span>
                <span class="badge bg-danger text-white px-2 py-1 font-monospace">
                    Total: - Rs. {{ number_format($totalItemizedPaymentsOut, 2) }}
                </span>
            </div>
        </div>

        <!-- Desktop Statement Payments Out Table -->
        <div class="d-none d-md-block statement-desktop-table table-responsive mb-3">
            <table class="table table-sm table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 95px;">Date</th>
                        <th style="width: 90px;">Voucher #</th>
                        <th>Paid To (Party / Beneficiary)</th>
                        <th>Paid From (Account)</th>
                        <th>Category / Reason</th>
                        <th>Remarks / Detail</th>
                        <th class="text-end" style="width: 130px;">Amount (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentsOut as $pout)
                        <tr>
                            <td class="fw-semibold text-dark">{{ $pout->date ? $pout->date->format('d-m-Y') : '-' }}</td>
                            <td class="font-monospace text-muted small">
                                @if(isset($pout->shift_name) && $pout->shift_name)
                                    @if(str_contains(strtolower($pout->shift_name), 'morning'))
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-1.5 py-0.5 small">
                                            <i class="bi bi-sun me-1"></i>{{ $pout->bill_no }}
                                        </span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary border border-primary px-1.5 py-0.5 small">
                                            <i class="bi bi-moon-stars me-1"></i>{{ $pout->bill_no }}
                                        </span>
                                    @endif
                                @else
                                    <span class="badge bg-light text-secondary border px-1.5 py-0.5 small">{{ $pout->bill_no }}</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($pout->party) && $pout->party)
                                    <span class="fw-bold text-dark">{{ $pout->party->name }}</span>
                                    @if(isset($pout->party->type) && $pout->party->type)
                                        <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.68rem;">{{ ucfirst($pout->party->type) }}</span>
                                    @endif
                                @elseif(isset($pout->party_name) && $pout->party_name)
                                    <span class="fw-bold text-dark">{{ $pout->party_name }}</span>
                                    @if(isset($pout->party_type) && $pout->party_type)
                                        <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.68rem;">{{ ucfirst($pout->party_type) }}</span>
                                    @endif
                                @else
                                    <span class="text-muted fst-italic">Direct Operational Expense</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($pout->account) && $pout->account)
                                    <span class="badge bg-light text-dark border">{{ $pout->account->name }}</span>
                                @elseif(isset($pout->account_name))
                                    <span class="badge bg-light text-dark border">{{ $pout->account_name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($pout->category) && $pout->category)
                                    <span class="badge bg-light text-danger border border-danger-subtle">{{ $pout->category->name }}</span>
                                @elseif(isset($pout->category_name))
                                    <span class="badge bg-light text-danger border border-danger-subtle">{{ $pout->category_name }}</span>
                                @elseif($pout->type === 'purchase_bill')
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning">Purchase Bill</span>
                                @else
                                    <span class="badge bg-light text-secondary border">Supplier Payment</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $pout->description ?: 'Payment disbursed for supplies, expenses, or advance' }}
                            </td>
                            <td class="text-end font-monospace fw-bold text-danger">
                                - Rs. {{ number_format($pout->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">
                                No payment out transactions recorded for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($paymentsOut->isNotEmpty())
                    <tfoot class="table-light">
                        <tr class="fw-bold total-row">
                            <th colspan="6" class="text-end text-uppercase">Total Payments Disbursed (Out):</th>
                            <th class="text-end font-monospace text-danger">- Rs. {{ number_format($totalItemizedPaymentsOut, 2) }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <!-- Mobile View: Payments Out Cards (Screen Only) -->
        <div class="d-block d-md-none statement-mobile-cards mb-3 no-print">
            <div class="d-flex flex-column gap-2">
                @forelse($paymentsOut as $pout)
                    <div class="p-3 rounded-3 border border-danger-subtle bg-white shadow-xs">
                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                            <span class="fw-bold text-dark" style="font-size: 0.82rem;">{{ $pout->date ? $pout->date->format('d M Y') : '-' }}</span>
                            <span class="font-monospace text-danger fw-bold">-Rs. {{ number_format($pout->amount, 2) }}</span>
                        </div>
                        <div class="mb-1">
                            <span class="text-muted small">Paid To:</span>
                            <strong class="text-dark">{{ $pout->party->name ?? ($pout->party_name ?? 'Direct Expense / Vendor') }}</strong>
                            @if(isset($pout->party_type) && $pout->party_type)
                                <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.68rem;">{{ ucfirst($pout->party_type) }}</span>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-1 mb-1.5" style="font-size: 0.72rem;">
                            @if(isset($pout->account) && $pout->account)
                                <span class="badge bg-light text-dark border">{{ $pout->account->name }}</span>
                            @elseif(isset($pout->account_name))
                                <span class="badge bg-light text-dark border">{{ $pout->account_name }}</span>
                            @endif
                            @if($pout->bill_no)
                                <span class="badge bg-light text-muted border">{{ $pout->bill_no }}</span>
                            @endif
                            @if(isset($pout->category) && $pout->category)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ $pout->category->name }}</span>
                            @elseif(isset($pout->category_name))
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ $pout->category_name }}</span>
                            @endif
                        </div>
                        @if($pout->description)
                            <div class="p-1.5 bg-light rounded-2 text-muted" style="font-size: 0.75rem;">
                                {{ $pout->description }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-3 small">
                        No payment out transactions recorded for this period.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Official Authentication & Signatures Section -->
    <div class="pt-5 mt-4 border-top statement-signatures">
        <div class="row text-center w-100 m-0">
            <div class="col-4">
                <div class="statement-sig-line pt-2">
                    <div class="small fw-bold text-dark mt-1">Prepared By (Accountant)</div>
                    <div class="small text-muted">{{ auth()->user()->name }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="statement-sig-line pt-2">
                    <div class="small fw-bold text-dark mt-1">Supervisor Verified</div>
                    <div class="small text-muted">Branch / Finance Incharge</div>
                </div>
            </div>
            <div class="col-4">
                <div class="statement-sig-line pt-2">
                    <div class="small fw-bold text-dark mt-1">Authorized Approval</div>
                    <div class="small text-muted">Managing Owner / Director</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statement Print Footer -->
    <div class="statement-print-footer d-none d-print-block mt-4 pt-2 border-top text-muted" style="font-size: 8pt;">
        <div class="d-flex justify-content-between align-items-center">
            <span>Consolidated Daily Closing Statement &bull; FinanceDesk ERP &bull; Official Accounting Record</span>
            <span>Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} &ndash; {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }} &bull; Printed: {{ now()->format('d M, Y h:i A') }}</span>
        </div>
    </div>

</div>
@endsection
