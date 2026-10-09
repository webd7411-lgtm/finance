@extends('layouts.app')

@section('title', 'Daily Account & Sales Register - FinanceDesk')
@section('page_title', 'Daily Account & Sales Register')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-table text-primary me-1"></i> Multi-Account Spreadsheet View
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-2 w-100 justify-content-start justify-content-sm-end no-print">
        <a href="{{ route('reports.daily-register.export', request()->query()) }}" class="btn btn-outline-success btn-sm rounded-2 d-flex align-items-center gap-1.5 shadow-sm">
            <i class="bi bi-file-earmark-excel"></i> Export to Excel / CSV
        </a>
        <button type="button" class="btn btn-dark btn-sm rounded-2 d-flex align-items-center gap-1.5 shadow-sm" onclick="window.print()">
            <i class="bi bi-printer-fill"></i> Print Register (PDF)
        </button>
        <a href="{{ route('reports.daily-closing') }}" class="btn btn-outline-secondary btn-sm rounded-2 d-flex align-items-center gap-1.5">
            <i class="bi bi-calendar2-range text-warning"></i> Closing Summary
        </a>
    </div>
@endsection

@push('styles')
<style>
    /* Spreadsheet Table Styling */
    .register-card {
        border: 1px solid #cbd5e1;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        background: #ffffff;
        border-radius: 8px;
    }

    .table-register {
        font-size: 0.82rem;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-register th {
        font-weight: 600;
        text-align: center;
        vertical-align: middle;
        padding: 7px 6px;
        white-space: nowrap;
        border-bottom: 1px solid #cbd5e1;
        border-right: 1px solid #cbd5e1;
    }

    .table-register td {
        padding: 6px 7px;
        vertical-align: middle;
        white-space: nowrap;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        font-variant-numeric: tabular-nums;
    }

    .table-register tr:hover td {
        background-color: #f1f5f9 !important;
    }

    /* Column Group Color Themes */
    .th-grp-date { background-color: #1e293b; color: #ffffff; }
    .th-grp-sale { background-color: #0f766e; color: #ffffff; }
    .th-grp-cash { background-color: #1d4ed8; color: #ffffff; }
    .th-grp-jazz { background-color: #7e22ce; color: #ffffff; }
    .th-grp-bank { background-color: #0369a1; color: #ffffff; }
    .th-grp-closing { background-color: #0f172a; color: #ffffff; }

    /* Sub-headers */
    .th-sub-date { background-color: #334155; color: #f8fafc; font-size: 0.76rem; }
    .th-sub-sale { background-color: #115e59; color: #f0fdfa; font-size: 0.76rem; }
    .th-sub-cash { background-color: #1e40af; color: #eff6ff; font-size: 0.76rem; }
    .th-sub-jazz { background-color: #6b21a8; color: #faf5ff; font-size: 0.76rem; }
    .th-sub-bank { background-color: #075985; color: #f0f9ff; font-size: 0.76rem; }
    .th-sub-closing { background-color: #1e293b; color: #f8fafc; font-size: 0.76rem; }

    /* Cell Highlights */
    .cell-opening { background-color: #f8fafc; font-weight: 500; }
    .cell-sale { background-color: #f0fdfa; }
    .cell-cash { background-color: #eff6ff; }
    .cell-jazz { background-color: #faf5ff; }
    .cell-bank { background-color: #f0f9ff; }
    .cell-closing { background-color: #f8fafc; font-weight: 700; color: #0f172a; }

    .val-positive { color: #047857; font-weight: 600; }
    .val-negative { color: #b91c1c; font-weight: 600; }
    .val-muted { color: #94a3b8; }

    /* Footer Totals Row */
    .tr-totals td {
        background-color: #0f172a !important;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 0.85rem;
        border-top: 2px solid #020617;
        border-bottom: 2px solid #020617;
    }

    /* Column width classes on screen */
    .col-th-date    { min-width: 82px; }
    .col-th-open    { min-width: 92px; }
    .col-th-sale    { min-width: 82px; }
    .col-th-ret     { min-width: 70px; }
    .col-th-netsale { min-width: 82px; }
    .col-th-cashin  { min-width: 92px; }
    .col-th-cashout { min-width: 92px; }
    .col-th-cashbal { min-width: 92px; }
    .col-th-jazzin  { min-width: 92px; }
    .col-th-jazzout { min-width: 92px; }
    .col-th-jazzbal { min-width: 92px; }
    .col-th-bankin  { min-width: 92px; }
    .col-th-bankout { min-width: 92px; }
    .col-th-bankbal { min-width: 92px; }
    .col-th-closing { min-width: 96px; }

    .print-hdr-short { display: none; }
    .print-hdr-full  { display: inline; }

    /* Print Formatting */
    @media print {
        @page {
            size: landscape;
            margin: 4mm 4mm 4mm 4mm;
        }
        *, *::before, *::after {
            box-sizing: border-box !important;
        }
        html, body {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            color: #000000 !important;
            font-size: 6.5pt !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .vip-navbar, .no-print, .btn, form, footer {
            display: none !important;
        }
        .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .register-card {
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        .table-responsive {
            overflow: visible !important;
            overflow-x: visible !important;
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        .table-register {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed !important;
            font-size: 6.1pt !important;
            border-collapse: collapse !important;
        }
        .table-register th, .table-register td {
            padding: 2.5px 1px !important;
            border: 0.5pt solid #475569 !important;
            min-width: 0 !important;
            max-width: none !important;
            white-space: normal !important;
            word-wrap: break-word !important;
            word-break: normal !important;
            overflow: hidden !important;
            line-height: 1.15 !important;
            color: #000000 !important;
        }
        .table-register th {
            font-size: 5.8pt !important;
            text-align: center !important;
            font-weight: 700 !important;
            padding: 2.5px 1px !important;
        }
        .th-grp-date, .th-grp-sale, .th-grp-cash, .th-grp-jazz, .th-grp-bank, .th-grp-closing {
            background-color: #cbd5e1 !important;
            color: #000000 !important;
            font-weight: 800 !important;
            font-size: 6.2pt !important;
        }
        .th-sub-date, .th-sub-sale, .th-sub-cash, .th-sub-jazz, .th-sub-bank, .th-sub-closing {
            background-color: #e2e8f0 !important;
            color: #000000 !important;
            font-weight: 700 !important;
        }
        .cell-opening, .cell-sale, .cell-cash, .cell-jazz, .cell-bank, .cell-closing {
            background-color: #ffffff !important;
        }
        .tr-totals td {
            background-color: #e2e8f0 !important;
            color: #000000 !important;
            font-weight: 800 !important;
            font-size: 6.3pt !important;
            border-top: 1pt solid #000000 !important;
            border-bottom: 1.5pt solid #000000 !important;
        }
        .print-only-header {
            display: block !important;
            margin-bottom: 6px;
        }

        /* 15 Proportional Print Widths - Total Exactly 100% */
        .col-th-date     { width: 5.5% !important; }
        .col-th-open     { width: 7.0% !important; }
        .col-th-sale     { width: 6.5% !important; }
        .col-th-ret      { width: 5.5% !important; }
        .col-th-netsale  { width: 6.5% !important; }
        .col-th-cashin   { width: 7.0% !important; }
        .col-th-cashout  { width: 6.5% !important; }
        .col-th-cashbal  { width: 6.5% !important; }
        .col-th-jazzin   { width: 7.0% !important; }
        .col-th-jazzout  { width: 6.5% !important; }
        .col-th-jazzbal  { width: 6.5% !important; }
        .col-th-bankin   { width: 7.0% !important; }
        .col-th-bankout  { width: 6.5% !important; }
        .col-th-bankbal  { width: 6.5% !important; }
        .col-th-closing  { width: 8.5% !important; }

        .print-hdr-short { display: inline !important; }
        .print-hdr-full  { display: none !important; }
    }
    .print-only-header {
        display: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">

    <!-- Print Only Header -->
    <div class="print-only-header text-center border-bottom pb-2 mb-3">
        <h4 class="fw-bold mb-1">FINANCEDESK - DAILY ACCOUNT & SALES REGISTER</h4>
        <div class="small text-muted">
            Report Statement Period: <strong>{{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</strong>
            | Generated on: {{ now()->format('d M Y, h:i A') }}
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 no-print">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('reports.daily-register') }}" id="filterForm" class="row g-2 align-items-end">
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">From Date</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-event"></i></span>
                        <input type="date" name="from_date" id="from_date" class="form-control form-control-sm border-start-0" value="{{ $fromDate }}" required>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">To Date</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-check"></i></span>
                        <input type="date" name="to_date" id="to_date" class="form-control form-control-sm border-start-0" value="{{ $toDate }}" required>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4 d-flex align-items-center gap-1 flex-wrap">
                    <span class="small text-muted me-1 d-none d-lg-inline">Presets:</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 small" onclick="setPreset('today')">Today</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 small" onclick="setPreset('this_week')">This Week</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 small" onclick="setPreset('this_month')">This Month</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 small" onclick="setPreset('last_month')">Last Month</button>
                </div>

                <div class="col-6 col-md-4 col-lg-2 d-flex align-items-center">
                    <div class="form-check form-switch small m-0">
                        <input class="form-check-input" type="checkbox" role="switch" name="include_inactive" id="include_inactive" value="1" {{ $includeInactive ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="form-check-label text-secondary" for="include_inactive">Show all days</label>
                    </div>
                </div>

                <div class="col-6 col-md-8 col-lg-2 text-end d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                    <a href="{{ route('reports.daily-register') }}" class="btn btn-light btn-sm border" title="Reset to current month">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Highlights -->
    <div class="row g-2 mb-3 no-print">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 p-2.5 h-100 border-start border-4 border-secondary">
                <div class="text-uppercase text-secondary fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.4px;">Period Opening Balance</div>
                <div class="fs-6 fw-bold text-dark mt-1">Rs. {{ number_format($totals['initial_opening_cash'], 2) }}</div>
                <div class="small text-muted" style="font-size: 0.72rem;">All accounts base opening</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 p-2.5 h-100 border-start border-4 border-success">
                <div class="text-uppercase text-secondary fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.4px;">Net Sales</div>
                <div class="fs-6 fw-bold text-success mt-1">Rs. {{ number_format($totals['net_sale'], 2) }}</div>
                <div class="small text-muted" style="font-size: 0.72rem;">Gross: {{ number_format($totals['total_sale'], 0) }} | Ret: {{ number_format($totals['returns_amount'], 0) }}</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 p-2.5 h-100 border-start border-4 border-primary">
                <div class="text-uppercase text-secondary fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.4px;">Cash Drawer Net</div>
                <div class="fs-6 fw-bold text-primary mt-1">Rs. {{ number_format($totals['cash_balance'], 2) }}</div>
                <div class="small text-muted" style="font-size: 0.72rem;">In: {{ number_format($totals['cash_received'], 0) }} | Out: {{ number_format($totals['cash_payments'], 0) }}</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 p-2.5 h-100 border-start border-4" style="border-color: #9333ea !important;">
                <div class="text-uppercase text-secondary fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.4px;">JazzCash Net</div>
                <div class="fs-6 fw-bold text-purple mt-1" style="color: #7e22ce;">Rs. {{ number_format($totals['jazzcash_balance'], 2) }}</div>
                <div class="small text-muted" style="font-size: 0.72rem;">In: {{ number_format($totals['jazzcash_received'], 0) }} | Out: {{ number_format($totals['jazzcash_payments'], 0) }}</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 p-2.5 h-100 border-start border-4 border-info">
                <div class="text-uppercase text-secondary fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.4px;">Bank Net</div>
                <div class="fs-6 fw-bold text-info mt-1">Rs. {{ number_format($totals['bank_balance'], 2) }}</div>
                <div class="small text-muted" style="font-size: 0.72rem;">In: {{ number_format($totals['bank_received'], 0) }} | Out: {{ number_format($totals['bank_payments'], 0) }}</div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 p-2.5 h-100 border-start border-4 border-dark bg-light">
                <div class="text-uppercase text-secondary fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.4px;">Total Closing Balance</div>
                <div class="fs-6 fw-bold text-dark mt-1">Rs. {{ number_format($totals['final_closing_cash'], 2) }}</div>
                <div class="small text-muted" style="font-size: 0.72rem;">Cash in Drawer: Rs. {{ number_format($totals['total_cash_in_hand'], 0) }}</div>
            </div>
        </div>
    </div>

    <!-- Main Spreadsheet Register Table -->
    <div class="register-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-register align-middle">
                <thead>
                    <!-- Tier 1 Group Header -->
                    <tr>
                        <th colspan="2" class="th-grp-date">DATE & OPENING BALANCE</th>
                        <th colspan="3" class="th-grp-sale">SALES OVERVIEW</th>
                        <th colspan="3" class="th-grp-cash">CASH DRAWER</th>
                        <th colspan="3" class="th-grp-jazz">JAZZCASH</th>
                        <th colspan="3" class="th-grp-bank">BANK ACCOUNT</th>
                        <th class="th-grp-closing">CLOSING BALANCE</th>
                    </tr>
                    <tr>
                        <th class="th-sub-date col-th-date">Date</th>
                        <th class="th-sub-date col-th-open"><span class="print-hdr-full">Opening Balance</span><span class="print-hdr-short">Open Bal.</span></th>
                        
                        <th class="th-sub-sale col-th-sale"><span class="print-hdr-full">Total Sale</span><span class="print-hdr-short">Total Sale</span></th>
                        <th class="th-sub-sale col-th-ret">Return</th>
                        <th class="th-sub-sale col-th-netsale"><span class="print-hdr-full">Net Sale</span><span class="print-hdr-short">Net Sale</span></th>

                        <th class="th-sub-cash col-th-cashin"><span class="print-hdr-full">Cash Received</span><span class="print-hdr-short">Cash Rec.</span></th>
                        <th class="th-sub-cash col-th-cashout"><span class="print-hdr-full">Cash Payments</span><span class="print-hdr-short">Cash Pay.</span></th>
                        <th class="th-sub-cash col-th-cashbal"><span class="print-hdr-full">Cash Balance</span><span class="print-hdr-short">Cash Bal.</span></th>

                        <th class="th-sub-jazz col-th-jazzin"><span class="print-hdr-full">Jazz Cash Received</span><span class="print-hdr-short">Jazz Rec.</span></th>
                        <th class="th-sub-jazz col-th-jazzout"><span class="print-hdr-full">Jazz Cash Payments</span><span class="print-hdr-short">Jazz Pay.</span></th>
                        <th class="th-sub-jazz col-th-jazzbal"><span class="print-hdr-full">Jazz Cash Balance</span><span class="print-hdr-short">Jazz Bal.</span></th>

                        <th class="th-sub-bank col-th-bankin"><span class="print-hdr-full">Bank Received</span><span class="print-hdr-short">Bank Rec.</span></th>
                        <th class="th-sub-bank col-th-bankout"><span class="print-hdr-full">Bank Payments</span><span class="print-hdr-short">Bank Pay.</span></th>
                        <th class="th-sub-bank col-th-bankbal"><span class="print-hdr-full">Bank Balance</span><span class="print-hdr-short">Bank Bal.</span></th>

                        <th class="th-sub-closing col-th-closing"><span class="print-hdr-full">Closing Balance</span><span class="print-hdr-short">Close Bal.</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $isInactive = !$row['has_activity'];
                        @endphp
                        <tr class="{{ $isInactive ? 'opacity-75' : '' }}">
                            <!-- Date -->
                            <td class="text-center fw-semibold text-secondary" style="background-color: #f8fafc;">
                                {{ $row['date'] }}
                                <small class="text-muted d-block" style="font-size: 0.68rem;">{{ $row['day_name'] }}</small>
                            </td>

                            <!-- Opening Balance -->
                            <td class="text-end cell-opening">
                                <div>{{ number_format($row['opening_cash'], 2) }}</div>
                                <span class="d-print-none text-muted fw-normal" style="font-size: 0.64rem; display: block;">Cash: {{ number_format($row['cash_opening'], 0) }}</span>
                            </td>

                            <!-- Total Sale -->
                            <td class="text-end cell-sale {{ $row['total_sale'] > 0 ? 'fw-semibold text-dark' : 'val-muted' }}">
                                {{ $row['total_sale'] > 0 ? number_format($row['total_sale'], 2) : '0.00' }}
                            </td>

                            <!-- Return -->
                            <td class="text-end cell-sale {{ $row['return'] > 0 ? 'text-danger fw-semibold' : 'val-muted' }}">
                                {{ $row['return'] > 0 ? number_format($row['return'], 2) : '0.00' }}
                            </td>

                            <!-- Net Sale -->
                            <td class="text-end cell-sale {{ $row['net_sale'] > 0 ? 'fw-bold text-success' : 'val-muted' }}">
                                {{ $row['net_sale'] > 0 ? number_format($row['net_sale'], 2) : '0.00' }}
                            </td>

                            <!-- Cash Received -->
                            <td class="text-end cell-cash {{ $row['cash_received'] > 0 ? 'fw-semibold text-dark' : 'val-muted' }}">
                                {{ $row['cash_received'] > 0 ? number_format($row['cash_received'], 2) : '0.00' }}
                            </td>

                            <!-- Cash Payments -->
                            <td class="text-end cell-cash {{ $row['cash_payments'] > 0 ? 'text-danger fw-semibold' : 'val-muted' }}">
                                {{ $row['cash_payments'] > 0 ? number_format($row['cash_payments'], 2) : '0.00' }}
                            </td>

                            <!-- Cash Balance (Received - Paid) -->
                            <td class="text-end cell-cash">
                                @if($row['cash_balance'] > 0)
                                    <span class="val-positive">+{{ number_format($row['cash_balance'], 2) }}</span>
                                @elseif($row['cash_balance'] < 0)
                                    <span class="val-negative">{{ number_format($row['cash_balance'], 2) }}</span>
                                @else
                                    <span class="val-muted">0.00</span>
                                @endif
                            </td>

                            <!-- Jazz Cash Received -->
                            <td class="text-end cell-jazz {{ $row['jazzcash_received'] > 0 ? 'fw-semibold text-dark' : 'val-muted' }}">
                                {{ $row['jazzcash_received'] > 0 ? number_format($row['jazzcash_received'], 2) : '0.00' }}
                            </td>

                            <!-- Jazz Cash Payments -->
                            <td class="text-end cell-jazz {{ $row['jazzcash_payments'] > 0 ? 'text-danger fw-semibold' : 'val-muted' }}">
                                {{ $row['jazzcash_payments'] > 0 ? number_format($row['jazzcash_payments'], 2) : '0.00' }}
                            </td>

                            <!-- Jazz Cash Balance -->
                            <td class="text-end cell-jazz">
                                @if($row['jazzcash_balance'] > 0)
                                    <span class="val-positive">+{{ number_format($row['jazzcash_balance'], 2) }}</span>
                                @elseif($row['jazzcash_balance'] < 0)
                                    <span class="val-negative">{{ number_format($row['jazzcash_balance'], 2) }}</span>
                                @else
                                    <span class="val-muted">0.00</span>
                                @endif
                            </td>

                            <!-- Bank Received -->
                            <td class="text-end cell-bank {{ $row['bank_received'] > 0 ? 'fw-semibold text-dark' : 'val-muted' }}">
                                {{ $row['bank_received'] > 0 ? number_format($row['bank_received'], 2) : '0.00' }}
                            </td>

                            <!-- Bank Payments -->
                            <td class="text-end cell-bank {{ $row['bank_payments'] > 0 ? 'text-danger fw-semibold' : 'val-muted' }}">
                                {{ $row['bank_payments'] > 0 ? number_format($row['bank_payments'], 2) : '0.00' }}
                            </td>

                            <!-- Bank Balance -->
                            <td class="text-end cell-bank">
                                @if($row['bank_balance'] > 0)
                                    <span class="val-positive">+{{ number_format($row['bank_balance'], 2) }}</span>
                                @elseif($row['bank_balance'] < 0)
                                    <span class="val-negative">{{ number_format($row['bank_balance'], 2) }}</span>
                                @else
                                    <span class="val-muted">0.00</span>
                                @endif
                            </td>

                            <!-- Closing Balance -->
                            <td class="text-end cell-closing">
                                <div class="fw-bold">{{ number_format($row['closing_cash'], 2) }}</div>
                                <span class="d-print-none text-muted fw-normal" style="font-size: 0.64rem; display: block;">Cash: {{ number_format($row['cash_closing'], 0) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                No records found for the selected date range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <!-- Total Row Matching the Excel Sheet -->
                    <tr class="tr-totals">
                        <td class="text-center">TOTAL</td>
                        <td class="text-end">{{ number_format($totals['initial_opening_cash'], 2) }}</td>
                        <td class="text-end">{{ number_format($totals['total_sale'], 2) }}</td>
                        <td class="text-end">{{ number_format($totals['returns_amount'], 2) }}</td>
                        <td class="text-end text-success">{{ number_format($totals['net_sale'], 2) }}</td>
                        <td class="text-end">{{ number_format($totals['cash_received'], 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($totals['cash_payments'], 2) }}</td>
                        <td class="text-end">{{ number_format($totals['cash_balance'], 2) }}</td>
                        <td class="text-end">{{ number_format($totals['jazzcash_received'], 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($totals['jazzcash_payments'], 2) }}</td>
                        <td class="text-end">{{ number_format($totals['jazzcash_balance'], 2) }}</td>
                        <td class="text-end">{{ number_format($totals['bank_received'], 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($totals['bank_payments'], 2) }}</td>
                        <td class="text-end">{{ number_format($totals['bank_balance'], 2) }}</td>
                        <td class="text-end text-warning">
                            <div class="fw-bold">{{ number_format($totals['final_closing_cash'], 2) }}</div>
                            <span class="d-print-none text-light text-opacity-75 fw-normal" style="font-size: 0.64rem; display: block;">Cash: {{ number_format($totals['total_cash_in_hand'], 0) }}</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Footnote Note on Formulas -->
    <div class="mt-2 text-muted small d-flex justify-content-between align-items-center no-print">
        <span><i class="bi bi-info-circle me-1"></i> <strong>Formulas:</strong> Net Sale = Total Sale - Return | Cash Bal = Cash Rec - Cash Pay | Closing Balance = Opening Balance + Cash Bal + JazzCash Bal + Bank Bal</span>
        <span>Showing <strong>{{ count($rows) }}</strong> day(s)</span>
    </div>

</div>

@push('scripts')
<script>
    function setPreset(preset) {
        const today = new Date();
        let fromDate, toDate;

        function formatDate(d) {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        if (preset === 'today') {
            fromDate = formatDate(today);
            toDate = formatDate(today);
        } else if (preset === 'this_week') {
            const first = today.getDate() - today.getDay(); // Sunday or Monday
            const start = new Date(today.setDate(first));
            const end = new Date();
            fromDate = formatDate(start);
            toDate = formatDate(end);
        } else if (preset === 'this_month') {
            const start = new Date(today.getFullYear(), today.getMonth(), 1);
            fromDate = formatDate(start);
            toDate = formatDate(today);
        } else if (preset === 'last_month') {
            const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const end = new Date(today.getFullYear(), today.getMonth(), 0);
            fromDate = formatDate(start);
            toDate = formatDate(end);
        }

        document.getElementById('from_date').value = fromDate;
        document.getElementById('to_date').value = toDate;
        document.getElementById('filterForm').submit();
    }
</script>
@endpush
@endsection
