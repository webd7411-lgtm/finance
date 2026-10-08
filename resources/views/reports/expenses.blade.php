@extends('layouts.app')

@section('title', 'Expense Reports & Detailed Audit - FinanceDesk')
@section('page_title', 'Expense Reports & Detailed Audit')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-receipt-cutoff me-1 text-danger"></i> Expense Ledger & Analysis
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-1 gap-sm-2 w-100 justify-content-start justify-content-sm-end">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
        <a href="{{ route('transactions.index') }}" class="btn btn-danger btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-arrow-up-right me-1"></i> Record Payment Out
        </a>
        <a href="{{ route('expense-categories.index') }}" class="btn btn-light btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-tags me-1"></i> Expense Categories
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

        #expenseReportSheet, .card-custom {
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
    <form method="GET" action="{{ route('reports.expenses') }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">From Date</label>
            <input type="date" name="from_date" class="form-control form-control-sm py-2" value="{{ $fromDate }}">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">To Date</label>
            <input type="date" name="to_date" class="form-control form-control-sm py-2" value="{{ $toDate }}">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">Expense Category</label>
            <select name="category_id" class="form-select form-select-sm py-2">
                <option value="">All Categories</option>
                <option value="uncategorized" {{ $categoryId === 'uncategorized' ? 'selected' : '' }}>General / Uncategorized</option>
                @foreach($allCategories as $cat)
                    <option value="{{ $cat->id }}" {{ (string)$categoryId === (string)$cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">Payment Account</label>
            <select name="account_id" class="form-select form-select-sm py-2">
                <option value="">All Accounts</option>
                @foreach($allAccounts as $acc)
                    <option value="{{ $acc->id }}" {{ (string)$accountId === (string)$acc->id ? 'selected' : '' }}>
                        {{ $acc->name }} ({{ strtoupper($acc->type) }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">Payee / Party</label>
            <select name="party_id" class="form-select form-select-sm py-2">
                <option value="">All Payees</option>
                <option value="no_party" {{ $partyId === 'no_party' ? 'selected' : '' }}>-- Direct Counter / Shop Expense --</option>
                @foreach($allParties as $p)
                    <option value="{{ $p->id }}" {{ (string)$partyId === (string)$p->id ? 'selected' : '' }}>
                        {{ $p->name }} ({{ ucfirst($p->type) }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">Expense Source</label>
            <select name="expense_source" class="form-select form-select-sm py-2">
                <option value="all" {{ $expenseSource === 'all' ? 'selected' : '' }}>All Sources (Vouchers + Shifts)</option>
                <option value="vouchers" {{ $expenseSource === 'vouchers' ? 'selected' : '' }}>Vouchers Only</option>
                <option value="shifts" {{ $expenseSource === 'shifts' ? 'selected' : '' }}>Shift Deductions Only</option>
            </select>
        </div>
        <div class="col-12 col-md-9">
            <label class="form-label text-muted small fw-semibold mb-1">Search Keywords</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control form-control-sm py-2" placeholder="Search by voucher #, bill #, description, or payee..." value="{{ $search }}">
            </div>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm rounded-3 w-100 fw-semibold py-2">
                <i class="bi bi-filter me-1"></i> Apply Filter
            </button>
            @if(request()->hasAny(['from_date', 'to_date', 'category_id', 'account_id', 'party_id', 'expense_source', 'search']))
                <a href="{{ route('reports.expenses') }}" class="btn btn-light btn-sm rounded-3 px-3 text-secondary text-decoration-none d-flex align-items-center justify-content-center" title="Reset all filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Printable Consolidated Expense Sheet -->
<div class="card-custom p-3 p-md-5 bg-white shadow-sm mb-4" id="expenseReportSheet">
    <!-- OFFICIAL BANK STATEMENT PRINT HEADER (Visible on Print) -->
    <div class="bank-stmt-header d-none d-print-block">
        <div class="d-flex justify-content-between align-items-start pb-2 border-bottom border-dark">
            <div>
                <h3 class="fw-bold m-0 text-uppercase tracking-tight" style="color: #0f172a; font-size: 16pt;">FINANCEDESK ENTERPRISE</h3>
                <div class="small fw-semibold text-secondary">CENTRAL FINANCIAL TREASURY & COMMERCIAL AUDIT</div>
                <div class="small text-muted" style="font-size: 8pt;">Proware Technologies &bull; Periodic Expense Audit &bull; Verified Books</div>
            </div>
            <div class="text-end">
                <div class="badge bg-dark text-white text-uppercase px-2 py-1 mb-1" style="font-size: 8.5pt;">Official Statement</div>
                <div class="fw-bold" style="font-size: 11pt; color: #0f172a;">CONSOLIDATED EXPENSE AUDIT STATEMENT</div>
                <div class="small text-muted" style="font-size: 8pt;">Statement Date: {{ now()->format('d M, Y h:i A') }}</div>
            </div>
        </div>

        <div class="row mt-2 py-1 text-dark" style="font-size: 8.5pt;">
            <div class="col-7">
                <div><strong>Statement Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} &ndash; {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }} ({{ $diffDays }} Days)</div>
                <div><strong>Scope:</strong> {{ ucfirst($expenseSource) }} &bull; <strong>Category:</strong> {{ $categoryId ? (is_numeric($categoryId) ? ($allCategories->firstWhere('id', $categoryId)->name ?? 'Selected') : ucfirst($categoryId)) : 'All Categories' }}</div>
                <div><strong>Base Currency:</strong> PKR (Pakistani Rupee)</div>
            </div>
            <div class="col-5 text-end">
                <div><strong>Audited By:</strong> {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</div>
                <div><strong>System Reference:</strong> EXP-{{ date('Ymd') }}-{{ str_pad(auth()->id(), 3, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>
    </div>

    <!-- Bank Statement Summary Table (Print Only) -->
    <table class="bank-summary-table d-none d-print-table mb-3">
        <thead>
            <tr>
                <th class="text-start" style="width: 25%;">Total Period Expenses</th>
                <th class="text-end" style="width: 25%;">Voucher Payments Out</th>
                <th class="text-end" style="width: 25%;">Shift Counter Expenses</th>
                <th class="text-end" style="width: 25%;">Daily Average Spending</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-start font-monospace text-danger fw-bold">Rs. {{ number_format($grandTotalAmount, 2) }}</td>
                <td class="text-end font-monospace">Rs. {{ number_format($totalVoucherAmount, 2) }}</td>
                <td class="text-end font-monospace">Rs. {{ number_format($totalShiftAmount, 2) }}</td>
                <td class="text-end font-monospace">Rs. {{ number_format($dailyAverage, 2) }}/day</td>
            </tr>
        </tbody>
    </table>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 pb-md-4 mb-3 mb-md-4 flex-wrap gap-2 d-print-none">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="bg-danger text-white rounded-2 p-1 px-2 fw-bold">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
                <h4 class="fw-bold text-dark m-0">FinanceDesk</h4>
            </div>
            <h5 class="text-secondary fw-bold m-0" style="font-size: clamp(1rem, 3.5vw, 1.25rem);">Consolidated Expense & Disbursement Audit Report</h5>
            <small class="text-muted">Proware Technologies &bull; Detailed Analysis of Daily Expenses, Vouchers & Counter Deductions</small>
        </div>
        <div class="text-start text-md-end">
            <span class="badge bg-light text-dark border px-2 py-1 mb-1 font-monospace d-inline-block">
                Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }} ({{ $diffDays }} Days)
            </span>
            <div class="small text-muted" style="font-size: 0.72rem;">Generated: {{ now()->format('d M, Y - h:i A') }}</div>
            <div class="small text-muted" style="font-size: 0.72rem;">Audited by: <strong>{{ auth()->user()->name }}</strong> ({{ ucfirst(auth()->user()->role) }})</div>
        </div>
    </div>

    <!-- Executive Metrics Cards -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4 d-print-none">
        <div class="col-6 col-md-3">
            <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle h-100">
                <span class="text-danger small fw-semibold d-block mb-1 kpi-title">Total Period Expenses</span>
                <h5 class="fw-bold font-monospace text-danger m-0 kpi-amount">Rs. {{ number_format($grandTotalAmount, 2) }}</h5>
                <small class="text-danger-emphasis" style="font-size: 0.7rem;">{{ $grandTotalCount }} Total Records</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-light rounded-3 border h-100">
                <span class="text-muted small d-block mb-1 kpi-title">Voucher Payments Out</span>
                <h5 class="fw-bold font-monospace text-dark m-0 kpi-amount">Rs. {{ number_format($totalVoucherAmount, 2) }}</h5>
                <small class="text-muted" style="font-size: 0.7rem;">{{ $totalVoucherCount }} Vouchers Logged</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-light rounded-3 border h-100">
                <span class="text-muted small d-block mb-1 kpi-title">Shift Counter Expenses</span>
                <h5 class="fw-bold font-monospace text-warning-emphasis m-0 kpi-amount">Rs. {{ number_format($totalShiftAmount, 2) }}</h5>
                <small class="text-muted" style="font-size: 0.7rem;">{{ $totalShiftCount }} Shifts with Deductions</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-primary text-white rounded-3 shadow-sm h-100">
                <span class="text-white-50 small fw-semibold d-block mb-1 kpi-title">Daily Average Spending</span>
                <h5 class="fw-bold font-monospace text-white m-0 kpi-amount">Rs. {{ number_format($dailyAverage, 2) }}</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Avg per day ({{ $diffDays }} days)</small>
            </div>
        </div>
    </div>

    <!-- Category-wise Expense Breakdown Cards -->
    @if(count($categoryBreakdown) > 0)
        <div class="mb-4 d-print-none">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                <h6 class="fw-bold text-dark m-0" style="font-size: 0.92rem;">
                    <i class="bi bi-pie-chart-fill text-danger me-1"></i> Expense Breakdown by Category
                </h6>
                <small class="text-muted" style="font-size: 0.72rem;">{{ count($categoryBreakdown) }} Categories</small>
            </div>

            <div class="row g-2">
                @foreach($categoryBreakdown as $catItem)
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="border rounded-3 p-2.5 bg-light bg-opacity-50 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark small text-truncate" title="{{ $catItem->name }}">
                                    <i class="bi bi-tag text-primary me-1"></i>{{ $catItem->name }}
                                </span>
                                <span class="badge bg-secondary-subtle text-secondary border font-monospace" style="font-size: 0.68rem;">
                                    {{ $catItem->percentage }}%
                                </span>
                            </div>
                            <div class="fw-bold font-monospace text-danger mb-1" style="font-size: 0.95rem;">
                                Rs. {{ number_format($catItem->total, 2) }}
                            </div>
                            <div class="d-flex justify-content-between text-muted" style="font-size: 0.7rem;">
                                <span>{{ $catItem->count }} txns</span>
                                <span>Avg: Rs. {{ number_format($catItem->count > 0 ? $catItem->total / $catItem->count : 0, 0) }}</span>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $catItem->percentage }}%;" aria-valuenow="{{ $catItem->percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Payment Account / Channel Distribution (Cash vs Banks) -->
    @if(count($accountBreakdown) > 0)
        <div class="mb-4 d-print-none">
            <h6 class="fw-bold text-dark mb-2" style="font-size: 0.92rem;">
                <i class="bi bi-wallet2 text-primary me-1"></i> Spending by Payment Channel (Accounts)
            </h6>
            <div class="row g-2">
                @foreach($accountBreakdown as $accItem)
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="border rounded-3 p-2 px-3 bg-white d-flex align-items-center justify-content-between">
                            <div>
                                <div class="small fw-bold text-dark">{{ $accItem->name }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">{!! $accItem->type_badge !!} &bull; {{ $accItem->count }} txns</div>
                            </div>
                            <div class="text-end">
                                <div class="font-monospace fw-bold text-dark small">Rs. {{ number_format($accItem->total, 2) }}</div>
                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.65rem;">{{ $accItem->percentage }}%</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Section 1: Detailed Voucher Expenses Register -->
    @if($showVouchers)
        <div class="mt-4 pt-2">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-journal-text text-danger me-1"></i> Detailed Expense Vouchers Register
                    </h6>
                    <small class="text-muted" style="font-size: 0.78rem;">Detailed itemized list of all payment out vouchers recorded in the system</small>
                </div>
                <span class="badge bg-danger-subtle text-danger border border-danger fw-bold">
                    {{ $voucherExpenses->count() }} Vouchers &bull; Rs. {{ number_format($totalVoucherAmount, 2) }}
                </span>
            </div>

            <!-- Desktop View: Table -->
            <div class="d-none d-md-block statement-desktop-table table-responsive border rounded-3 mb-4">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th style="width: 95px;">Date</th>
                            <th style="width: 85px;">Voucher #</th>
                            <th>Category</th>
                            <th>Payee / Beneficiary</th>
                            <th>Payment Account</th>
                            <th>Bill # / Ref</th>
                            <th>Description / Particulars</th>
                            <th>Recorded By</th>
                            <th class="text-end">Amount (Rs.)</th>
                            <th class="text-center no-print" style="width: 60px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($voucherExpenses as $index => $trx)
                            <tr>
                                <td class="text-muted small">{{ $index + 1 }}</td>
                                <td class="small fw-semibold text-dark">{{ $trx->date->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">#{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td>
                                    @if($trx->category)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning fw-semibold">
                                            <i class="bi bi-tag-fill me-1"></i>{{ $trx->category->name }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border">Uncategorized</span>
                                    @endif
                                </td>
                                <td>
                                    @if($trx->party)
                                        <div class="fw-semibold text-dark small">{{ $trx->party->name }}</div>
                                        <small class="text-muted">{!! $trx->party->type_badge !!}</small>
                                    @else
                                        <span class="badge bg-light text-muted border">Direct / Counter Expense</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">{{ $trx->account->name }}</div>
                                    <small class="text-muted">{!! $trx->account->type_badge !!}</small>
                                </td>
                                <td class="small font-monospace text-secondary">
                                    {{ $trx->bill_no ?? '-' }}
                                </td>
                                <td class="small text-dark" style="max-width: 250px;">
                                    @if(!empty($trx->description))
                                        <div class="text-break">{{ $trx->description }}</div>
                                    @else
                                        <span class="text-muted fst-italic">No description recorded</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $trx->creator->name ?? 'System' }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-danger">
                                    Rs. {{ number_format($trx->amount, 2) }}
                                </td>
                                <td class="text-center no-print">
                                    <a href="{{ route('transactions.show', $trx->id) }}" class="btn btn-outline-secondary btn-sm py-0 px-2" title="View & Print Voucher">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted small">
                                    <i class="bi bi-inbox fs-4 d-block opacity-50 mb-1"></i>
                                    No payment out / expense vouchers found matching the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($voucherExpenses->isNotEmpty())
                        <tfoot class="table-light border-top">
                            <tr>
                                <th colspan="9" class="text-end fw-bold text-dark">Subtotal Voucher Expenses:</th>
                                <th class="text-end font-monospace fw-bold text-danger fs-6">
                                    Rs. {{ number_format($totalVoucherAmount, 2) }}
                                </th>
                                <th class="no-print"></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <!-- Mobile View: Clean Voucher Cards (Zero Horizontal Scroll) -->
            <div class="d-block d-md-none mb-4 d-print-none">
                <div class="d-flex flex-column gap-2">
                    @forelse($voucherExpenses as $trx)
                        <div class="p-3 rounded-3 border bg-white shadow-xs">
                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1.5">
                                <div class="d-flex align-items-center gap-1.5 min-w-0">
                                    <span class="fw-bold text-dark small">{{ $trx->date->format('d M Y') }}</span>
                                    <span class="font-monospace text-muted small">#{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                @if($trx->category)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning" style="font-size: 0.68rem;">
                                        {{ $trx->category->name }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">Uncategorized</span>
                                @endif
                            </div>

                            <div class="fw-semibold text-dark small mb-1">
                                {{ $trx->party->name ?? 'Direct Counter Expense' }}
                            </div>

                            <div class="d-flex align-items-center gap-1 mb-2 flex-wrap" style="font-size: 0.72rem;">
                                <span class="badge bg-light text-secondary border px-1.5 py-0.5">
                                    {{ $trx->account->name }}
                                </span>
                                @if($trx->bill_no)
                                    <span class="font-monospace text-muted">Bill: {{ $trx->bill_no }}</span>
                                @endif
                            </div>

                            @if($trx->description)
                                <div class="text-muted small text-truncate p-1.5 rounded-2 bg-light border mb-2" style="font-size: 0.72rem;">
                                    {{ $trx->description }}
                                </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-center pt-1" style="font-size: 0.78rem;">
                                <div class="font-monospace fw-bold text-danger fs-6">
                                    Rs. {{ number_format($trx->amount, 2) }}
                                </div>
                                <a href="{{ route('transactions.show', $trx->id) }}" class="btn btn-outline-secondary btn-sm py-1 px-2.5 rounded-2" style="font-size: 0.74rem;">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">
                            No payment out / expense vouchers found matching the selected filters.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- Section 2: Shift Counter Petty Expenses (Cashier Shift Deductions) -->
    @if($showShifts)
        <div class="mt-4 pt-2">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-clock-history text-warning me-1"></i> Shift Counter Petty Expenses
                    </h6>
                    <small class="text-muted" style="font-size: 0.78rem;">Expenses deducted by cashiers on the spot during morning and evening shift closings</small>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning fw-bold">
                    {{ $shiftExpenses->count() }} Shifts &bull; Rs. {{ number_format($totalShiftAmount, 2) }}
                </span>
            </div>

            <!-- Desktop View: Table -->
            <div class="d-none d-md-block statement-desktop-table table-responsive border rounded-3 mb-4">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th style="width: 105px;">Date</th>
                            <th style="width: 130px;">Shift</th>
                            <th>Cashier In-Charge</th>
                            <th class="text-end">Shift Total Sale</th>
                            <th class="text-end">Expense Deducted (Rs.)</th>
                            <th>Expense Details & Particulars</th>
                            <th class="text-center no-print" style="width: 70px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shiftExpenses as $sIndex => $shift)
                            <tr>
                                <td class="text-muted small">{{ $sIndex + 1 }}</td>
                                <td class="small fw-semibold text-dark">{{ $shift->date->format('d M Y') }}</td>
                                <td>{!! $shift->shift_badge !!}</td>
                                <td class="small fw-semibold text-dark">
                                    <i class="bi bi-person me-1 text-secondary"></i>{{ $shift->cashier->name ?? 'Cashier' }}
                                </td>
                                <td class="text-end font-monospace small text-muted">
                                    Rs. {{ number_format($shift->total_sale, 2) }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-danger">
                                    Rs. {{ number_format($shift->expenses_amount, 2) }}
                                </td>
                                <td class="small text-dark" style="max-width: 320px;">
                                    @if(!empty($shift->expenses_details))
                                        <div class="fw-semibold text-dark">{{ $shift->expenses_details }}</div>
                                    @endif
                                    @if(!empty($shift->remarks))
                                        <div class="text-muted" style="font-size: 0.74rem;"><i class="bi bi-chat-left-text me-1"></i>Remarks: {{ $shift->remarks }}</div>
                                    @endif
                                    @if(empty($shift->expenses_details) && empty($shift->remarks))
                                        <span class="text-muted fst-italic">Shift counter deduction</span>
                                    @endif
                                </td>
                                <td class="text-center no-print">
                                    <a href="{{ route('shift-closings.show', $shift->id) }}" class="btn btn-outline-secondary btn-sm py-0 px-2" title="View Shift Closing Sheet">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted small">
                                    <i class="bi bi-check2-circle fs-4 d-block text-success opacity-50 mb-1"></i>
                                    No counter shift expenses were recorded in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($shiftExpenses->isNotEmpty())
                        <tfoot class="table-light border-top">
                            <tr>
                                <th colspan="5" class="text-end fw-bold text-dark">Subtotal Shift Counter Expenses:</th>
                                <th class="text-end font-monospace fw-bold text-danger fs-6">
                                    Rs. {{ number_format($totalShiftAmount, 2) }}
                                </th>
                                <th colspan="2" class="no-print"></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <!-- Mobile View: Clean Shift Cards (Zero Horizontal Scroll) -->
            <div class="d-block d-md-none mb-4 d-print-none">
                <div class="d-flex flex-column gap-2">
                    @forelse($shiftExpenses as $shift)
                        <div class="p-3 rounded-3 border bg-white shadow-xs">
                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1.5">
                                <div class="d-flex align-items-center gap-1.5 min-w-0">
                                    <span class="fw-bold text-dark small">{{ $shift->date->format('d M Y') }}</span>
                                    {!! $shift->shift_badge !!}
                                </div>
                                <span class="font-monospace fw-bold text-danger">
                                    -Rs. {{ number_format($shift->expenses_amount, 2) }}
                                </span>
                            </div>

                            <div class="small text-muted mb-1">
                                Cashier: <strong class="text-dark">{{ $shift->cashier->name ?? 'Cashier' }}</strong>
                            </div>

                            @if($shift->expenses_details || $shift->remarks)
                                <div class="p-1.5 rounded-2 bg-light border text-muted small mb-2" style="font-size: 0.72rem;">
                                    {{ $shift->expenses_details ?? $shift->remarks }}
                                </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-center pt-1" style="font-size: 0.76rem;">
                                <span class="text-muted">Sale: Rs. {{ number_format($shift->total_sale, 0) }}</span>
                                <a href="{{ route('shift-closings.show', $shift->id) }}" class="btn btn-outline-secondary btn-sm py-1 px-2.5 rounded-2" style="font-size: 0.74rem;">
                                    <i class="bi bi-file-earmark-text me-1"></i> Sheet
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">
                            No counter shift expenses were recorded in this period.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- Grand Total Summary Box -->
    <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center flex-wrap gap-2 my-4">
        <div>
            <h6 class="fw-bold text-dark m-0">Grand Total Net Period Expenses</h6>
            <small class="text-muted">Total of all voucher disbursements and counter shift expenses combined</small>
        </div>
        <div class="text-start text-md-end">
            <h4 class="fw-bold font-monospace text-danger m-0" style="font-size: clamp(1.2rem, 4vw, 1.6rem);">
                Rs. {{ number_format($grandTotalAmount, 2) }}
            </h4>
            <small class="text-muted">{{ $grandTotalCount }} total expense items</small>
        </div>
    </div>

    <!-- Official Bank Statement Signatures (Visible on Print) -->
    <div class="statement-signatures pt-4 mt-4">
        <div class="row text-center w-100 m-0">
            <div class="col-4">
                <div class="statement-sig-line">
                    <div class="small fw-bold text-dark">Prepared By (Accountant)</div>
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
