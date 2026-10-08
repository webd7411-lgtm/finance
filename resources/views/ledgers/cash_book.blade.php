@extends('layouts.app')

@section('title', 'Cash Book Register - FinanceDesk')
@section('page_title', 'Cash Book Register')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-cash-stack me-1 text-success"></i> Cash Journal
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-1 gap-sm-2 w-100 justify-content-start justify-content-sm-end">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Register
        </button>
        <a href="{{ route('accounts.index') }}" class="btn btn-light btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-wallet2 me-1"></i> Accounts Directory
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

        #cashBookSheet, .card-custom {
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
        .bank-summary-table th, .bank-summary-table td {
            border: 1px solid #475569 !important;
            padding: 4px 6px !important;
            font-size: 8.2pt !important;
            color: #0f172a !important;
        }

        .table thead th,
        .bank-summary-table thead th {
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
<!-- Filter & Selection Bar (No-Print) -->
<div class="card-custom p-3 mb-3 mb-md-4 no-print">
    <form method="GET" action="{{ route('ledgers.cash-book') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label text-muted small fw-semibold mb-1">System Cash Account</label>
            <div class="d-flex align-items-center justify-content-between p-2 px-3 bg-light rounded-3 border" style="height: 38px;">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <i class="bi bi-cash-stack text-success fs-5"></i>
                    <div class="min-w-0">
                        <span class="fw-bold text-dark d-block text-truncate small">{{ $cashAccount->name }}</span>
                    </div>
                </div>
                <span class="badge bg-success-subtle text-success border border-success small">Auto Cash</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">From Date</label>
            <input type="date" name="from_date" class="form-control form-control-sm py-2" value="{{ $fromDate }}">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">To Date</label>
            <input type="date" name="to_date" class="form-control form-control-sm py-2" value="{{ $toDate }}">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm rounded-3 w-100 fw-semibold py-2">
                <i class="bi bi-filter me-1"></i> Apply Filter
            </button>
        </div>
    </form>
</div>

<!-- Printable Cash Book Card -->
<div class="card-custom p-3 p-md-5 bg-white shadow-sm mb-4" id="cashBookSheet">
    <!-- OFFICIAL BANK STATEMENT PRINT HEADER (Visible on Print) -->
    <div class="bank-stmt-header d-none d-print-block">
        <div class="d-flex justify-content-between align-items-start pb-2 border-bottom border-dark">
            <div>
                <h3 class="fw-bold m-0 text-uppercase tracking-tight" style="color: #0f172a; font-size: 16pt;">FINANCEDESK ENTERPRISE</h3>
                <div class="small fw-semibold text-secondary">CENTRAL FINANCIAL TREASURY & COMMERCIAL AUDIT</div>
                <div class="small text-muted" style="font-size: 8pt;">Proware Technologies &bull; Primary Liquid Cash Journal &bull; Verified Books</div>
            </div>
            <div class="text-end">
                <div class="badge bg-dark text-white text-uppercase px-2 py-1 mb-1" style="font-size: 8.5pt;">Official Statement</div>
                <div class="fw-bold" style="font-size: 11pt; color: #0f172a;">CASH BOOK &amp; LIQUIDITY STATEMENT</div>
                <div class="small text-muted" style="font-size: 8pt;">Statement Date: {{ now()->format('d M, Y h:i A') }}</div>
            </div>
        </div>

        <div class="row mt-2 py-1 text-dark" style="font-size: 8.5pt;">
            <div class="col-6">
                <div><strong>Cash Account:</strong> {{ $cashAccount->name }} (System Cash Journal)</div>
                <div><strong>Statement Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} &ndash; {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</div>
                <div><strong>Base Currency:</strong> PKR (Pakistani Rupee)</div>
            </div>
            <div class="col-6 text-end">
                <div><strong>Audited By:</strong> {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</div>
                <div><strong>System Reference:</strong> CB-{{ date('Ymd') }}-{{ str_pad($cashAccount->id, 3, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>
    </div>

    <!-- Bank Statement Summary Table (Print Only) -->
    <table class="bank-summary-table d-none d-print-table mb-3">
        <thead>
            <tr>
                <th class="text-start" style="width: 25%;">Opening Cash Balance</th>
                <th class="text-end" style="width: 25%;">(+) Total Cash Inflows</th>
                <th class="text-end" style="width: 25%;">(-) Total Cash Outflows</th>
                <th class="text-end" style="width: 25%;">(=) Ending Cash-in-Hand</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-start font-monospace">Rs. {{ number_format($openingBalance, 2) }}</td>
                <td class="text-end font-monospace text-success">+ Rs. {{ number_format($totalIn, 2) }}</td>
                <td class="text-end font-monospace text-danger">- Rs. {{ number_format($totalOut, 2) }}</td>
                <td class="text-end font-monospace fw-bold text-dark">Rs. {{ number_format($closingBalance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 pb-md-4 mb-3 mb-md-4 flex-wrap gap-2 d-print-none">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="bg-success text-white rounded-2 p-1 px-2 fw-bold">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <h4 class="fw-bold text-dark m-0">FinanceDesk</h4>
            </div>
            <h5 class="text-secondary fw-bold m-0" style="font-size: clamp(1rem, 3.5vw, 1.25rem);">Cash Book & Liquidity Statement</h5>
            <small class="text-muted">{{ config('app.name', 'FinanceDesk') }} &bull; {{ $cashAccount->name }} Register</small>
        </div>
        <div class="text-start text-md-end">
            <span class="badge bg-light text-dark border px-2 py-1 mb-1 font-monospace d-inline-block">
                Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}
            </span>
            <div class="small text-muted" style="font-size: 0.72rem;">Audited: {{ now()->format('d M, Y - h:i A') }}</div>
            <div class="small text-muted" style="font-size: 0.72rem;">Operator: <strong>{{ auth()->user()->name }}</strong></div>
        </div>
    </div>

    <!-- Executive Metric Cards -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4 d-print-none">
        <div class="col-6 col-md-3">
            <div class="p-3 bg-light rounded-3 border h-100">
                <span class="text-muted small d-block mb-1 kpi-title">Opening Cash Balance</span>
                <h5 class="fw-bold font-monospace text-dark m-0 kpi-amount">Rs. {{ number_format($openingBalance, 2) }}</h5>
                <small class="text-muted" style="font-size: 0.7rem;">Prior to {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }}</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle h-100">
                <span class="text-success small fw-semibold d-block mb-1 kpi-title">(+) Total Cash Inflows</span>
                <h5 class="fw-bold font-monospace text-success m-0 kpi-amount">+ Rs. {{ number_format($totalIn, 2) }}</h5>
                <small class="text-success-emphasis" style="font-size: 0.7rem;">Customer Collections & Sales</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle h-100">
                <span class="text-danger small fw-semibold d-block mb-1 kpi-title">(-) Total Cash Outflows</span>
                <h5 class="fw-bold font-monospace text-danger m-0 kpi-amount">- Rs. {{ number_format($totalOut, 2) }}</h5>
                <small class="text-danger-emphasis" style="font-size: 0.7rem;">Supplier & Expense Payments</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-primary text-white rounded-3 shadow-sm h-100">
                <span class="text-white-50 small fw-semibold d-block mb-1 kpi-title">(=) Ending Cash-in-Hand</span>
                <h5 class="fw-bold font-monospace text-white m-0 kpi-amount">Rs. {{ number_format($closingBalance, 2) }}</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">As of {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</small>
            </div>
        </div>
    </div>

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block statement-desktop-table table-responsive mb-4">
        <table class="table table-sm table-bordered table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 105px;">Date</th>
                    <th style="width: 110px;">Voucher #</th>
                    <th>Account</th>
                    <th>Party / Payee</th>
                    <th>Particulars / Details</th>
                    <th class="text-end" style="width: 130px;">Cash In (+)</th>
                    <th class="text-end" style="width: 130px;">Cash Out (-)</th>
                    <th class="text-end" style="width: 150px;">Running Cash (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance Row -->
                <tr class="table-secondary bg-opacity-25 fw-semibold">
                    <td>{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                    <td class="font-monospace text-muted">-</td>
                    <td class="text-muted">{{ $cashAccount->name }}</td>
                    <td class="text-muted">Opening Balance</td>
                    <td class="text-muted">Cash brought forward prior to {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }}</td>
                    <td class="text-end font-monospace text-muted">-</td>
                    <td class="text-end font-monospace text-muted">-</td>
                    <td class="text-end font-monospace fw-bold text-dark">
                        Rs. {{ number_format($openingBalance, 2) }}
                    </td>
                </tr>

                @forelse($transactions as $entry)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($entry->date)->format('d-m-Y') }}</td>
                        <td class="font-monospace fw-bold">
                            @if(str_starts_with((string)$entry->id, 'shift-'))
                                <a href="{{ route('shift-closings.show', str_replace('shift-', '', $entry->id)) }}" class="text-decoration-none" target="_blank">
                                    #{{ $entry->voucher_no }}
                                </a>
                            @else
                                <a href="{{ route('transactions.show', $entry->id) }}" class="text-decoration-none" target="_blank">
                                    #{{ $entry->voucher_no }}
                                </a>
                            @endif
                            @if($entry->bill_no)
                                <span class="d-block text-muted small" style="font-size: 0.72rem;">Bill: {{ $entry->bill_no }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1">
                                {{ $entry->account }}
                            </span>
                        </td>
                        <td class="fw-semibold text-dark">{{ $entry->party }}</td>
                        <td>
                            <div>{{ $entry->description ?? '-' }}</div>
                            @if($entry->category !== '-')
                                <span class="badge bg-light text-muted border px-1" style="font-size: 0.7rem;">{{ $entry->category }}</span>
                            @endif
                        </td>
                        <td class="text-end font-monospace {{ $entry->in > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                            {{ $entry->in > 0 ? '+ ' . number_format($entry->in, 2) : '-' }}
                        </td>
                        <td class="text-end font-monospace {{ $entry->out > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ $entry->out > 0 ? '- ' . number_format($entry->out, 2) : '-' }}
                        </td>
                        <td class="text-end font-monospace fw-bold text-dark">
                            Rs. {{ number_format($entry->running_balance, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                            No cash transactions recorded within the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="5" class="text-end">Total Cash Movement & Closing Position:</td>
                    <td class="text-end font-monospace text-success">+ Rs. {{ number_format($totalIn, 2) }}</td>
                    <td class="text-end font-monospace text-danger">- Rs. {{ number_format($totalOut, 2) }}</td>
                    <td class="text-end font-monospace text-primary fs-6">Rs. {{ number_format($closingBalance, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll) -->
    <div class="d-block d-md-none mb-3 d-print-none">
        <!-- Opening Balance Card -->
        <div class="p-2.5 rounded-3 border bg-light mb-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold text-secondary small">Opening Cash</div>
                    <small class="text-muted" style="font-size: 0.7rem;">Prior to {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }}</small>
                </div>
                <div class="font-monospace fw-bold text-dark">
                    Rs. {{ number_format($openingBalance, 2) }}
                </div>
            </div>
        </div>

        <!-- Transactions Cards -->
        <div class="d-flex flex-column gap-2">
            @forelse($transactions as $entry)
                <div class="p-3 rounded-3 border bg-white shadow-xs">
                    <div class="d-flex align-items-center justify-content-between gap-1 mb-1.5">
                        <div class="d-flex align-items-center gap-1.5 min-w-0">
                            <span class="fw-bold text-dark small">{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</span>
                            @if(str_starts_with((string)$entry->id, 'shift-'))
                                <a href="{{ route('shift-closings.show', str_replace('shift-', '', $entry->id)) }}" class="text-decoration-none font-monospace small" target="_blank">
                                    #{{ $entry->voucher_no }}
                                </a>
                            @else
                                <a href="{{ route('transactions.show', $entry->id) }}" class="text-decoration-none font-monospace small" target="_blank">
                                    #{{ $entry->voucher_no }}
                                </a>
                            @endif
                        </div>
                        <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.68rem;">
                            {{ $entry->account }}
                        </span>
                    </div>

                    <div class="fw-semibold text-dark small text-truncate mb-1">
                        {{ $entry->party }}
                    </div>

                    @if($entry->description || $entry->category !== '-')
                        <div class="text-muted small text-truncate mb-2" style="font-size: 0.74rem;">
                            {{ $entry->description ?? '' }}
                            @if($entry->category !== '-')
                                <span class="badge bg-light text-muted border px-1" style="font-size: 0.68rem;">{{ $entry->category }}</span>
                            @endif
                        </div>
                    @endif

                    <!-- Amount Strip -->
                    <div class="p-2 rounded-2 bg-light border d-flex justify-content-between align-items-center" style="font-size: 0.78rem;">
                        <div>
                            @if($entry->in > 0)
                                <span class="text-success fw-bold font-monospace">+Rs. {{ number_format($entry->in, 2) }}</span>
                            @elseif($entry->out > 0)
                                <span class="text-danger fw-bold font-monospace">-Rs. {{ number_format($entry->out, 2) }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                        <div class="text-end">
                            <span class="text-muted d-block" style="font-size: 0.65rem;">Running Cash</span>
                            <span class="font-monospace fw-bold text-dark">Rs. {{ number_format($entry->running_balance, 2) }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4 small">
                    No cash transactions recorded within the selected period.
                </div>
            @endforelse
        </div>

        <!-- Mobile Totals Card -->
        <div class="p-3 rounded-3 border bg-success bg-opacity-10 mt-2">
            <div class="d-flex justify-content-between align-items-center small py-1">
                <span class="text-success fw-semibold">(+) Total Inflows:</span>
                <span class="font-monospace fw-bold text-success">+Rs. {{ number_format($totalIn, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center small py-1">
                <span class="text-danger fw-semibold">(-) Total Outflows:</span>
                <span class="font-monospace fw-bold text-danger">-Rs. {{ number_format($totalOut, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-1">
                <span class="fw-bold text-dark">Ending Cash-in-Hand:</span>
                <span class="font-monospace fw-bold fs-6 text-primary">Rs. {{ number_format($closingBalance, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Official Bank Statement Signatures (Visible on Print) -->
    <div class="statement-signatures pt-4 mt-4">
        <div class="row text-center w-100 m-0">
            <div class="col-4">
                <div class="statement-sig-line">
                    <div class="small fw-bold text-dark">Prepared By (Cashier)</div>
                    <div class="small text-muted" style="font-size: 8pt;">{{ auth()->user()->name }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="statement-sig-line">
                    <div class="small fw-bold text-dark">Supervisor / Audit Verified</div>
                    <div class="small text-muted" style="font-size: 8pt;">Branch Accounts Incharge</div>
                </div>
            </div>
            <div class="col-4">
                <div class="statement-sig-line">
                    <div class="small fw-bold text-dark">Managing Approval</div>
                    <div class="small text-muted" style="font-size: 8pt;">Director / Owner</div>
                </div>
            </div>
        </div>
    </div>


</div>
@endsection
