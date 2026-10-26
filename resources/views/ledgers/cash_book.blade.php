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

        .table tfoot td,
        .table tfoot th {
            background-color: #f8fafc !important;
            font-weight: 700 !important;
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
            <label class="form-label text-muted small fw-semibold mb-1">
                <i class="bi bi-wallet2 text-primary me-1"></i>Account / Report Mode
            </label>
            <select name="account_id" class="form-select form-select-sm py-2 fw-semibold" onchange="this.form.submit()">
                <option value="whole_shop" {{ ($isWholeShop ?? false) ? 'selected' : '' }} class="fw-bold text-primary">
                    🏢 Whole Shop (All Accounts &amp; Sales Report)
                </option>
                <optgroup label="Individual Account Ledgers">
                    @foreach($allAccounts as $acc)
                        <option value="{{ $acc->id }}" {{ (!($isWholeShop ?? false) && (($selectedAccountId == $acc->id) || ($selectedAccountId === 'cash' && $acc->isCash()))) ? 'selected' : '' }}>
                            {{ $acc->isCash() ? '💵 ' : ($acc->type === 'bank' ? '🏦 ' : '📱 ') }}{{ $acc->name }} ({{ ucfirst($acc->type) }})
                        </option>
                    @endforeach
                </optgroup>
            </select>
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
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="Green Pharmacie" style="height: 52px; max-width: 175px; object-fit: contain;" onerror="this.onerror=null; this.src='/images/logo.png';">
                <div>
                    <h3 class="fw-bold m-0 text-uppercase tracking-tight" style="color: #0f172a; font-size: 15pt;">GREEN PHARMACIE</h3>
                    <div class="small fw-semibold text-secondary">CENTRAL FINANCIAL TREASURY &amp; COMMERCIAL AUDIT</div>
                    <div class="small text-muted" style="font-size: 8pt;">Your Health Companion &bull; Primary Liquid Cash Journal &bull; Software by <strong>Prowave Technologies (+92 317 3836223)</strong></div>
                </div>
            </div>
            <div class="text-end">
                <div class="badge bg-dark text-white text-uppercase px-2 py-1 mb-1" style="font-size: 8.5pt;">Official Statement</div>
                <div class="fw-bold" style="font-size: 11pt; color: #0f172a;">
                    {{ ($isWholeShop ?? false) ? 'WHOLE SHOP FINANCIAL & LIQUIDITY MATRIX' : 'CASH BOOK & LIQUIDITY STATEMENT' }}
                </div>
                <div class="small text-muted" style="font-size: 8pt;">Statement Date: {{ now()->format('d M, Y h:i A') }}</div>
            </div>
        </div>

        <div class="row mt-2 py-1 text-dark" style="font-size: 8.5pt;">
            <div class="col-6">
                <div><strong>Account Scope:</strong> {{ ($isWholeShop ?? false) ? 'Whole Shop (All Accounts & Sales)' : ($cashAccount->name . ' Register') }}</div>
                <div><strong>Statement Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} &ndash; {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</div>
                <div><strong>Base Currency:</strong> PKR (Pakistani Rupee)</div>
            </div>
            <div class="col-6 text-end">
                <div><strong>Audited By:</strong> {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</div>
                <div><strong>System Reference:</strong> {{ ($isWholeShop ?? false) ? 'WS-' : 'CB-' }}{{ date('Ymd') }}-{{ str_pad($cashAccount->id, 3, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>
    </div>

    <!-- Bank Statement Summary Table (Print Only) -->
    <table class="bank-summary-table d-none d-print-table mb-3">
        <thead>
            <tr>
                @if($isWholeShop ?? false)
                    <th class="text-start" style="width: 25%;">Total Net Sale</th>
                    <th class="text-end" style="width: 25%;">(+) Total Inflows</th>
                    <th class="text-end" style="width: 25%;">(-) Total Outflows</th>
                    <th class="text-end" style="width: 25%;">(=) Ending Total Liquidity</th>
                @else
                    <th class="text-start" style="width: 25%;">Opening Cash Balance</th>
                    <th class="text-end" style="width: 25%;">(+) Total Cash Inflows</th>
                    <th class="text-end" style="width: 25%;">(-) Total Cash Outflows</th>
                    <th class="text-end" style="width: 25%;">(=) Ending Cash-in-Hand</th>
                @endif
            </tr>
        </thead>
        <tbody>
            <tr>
                @if($isWholeShop ?? false)
                    <td class="text-start font-monospace fw-bold">Rs. {{ number_format($totalNetSale, 2) }}</td>
                    <td class="text-end font-monospace text-success">+ Rs. {{ number_format($totalInAllAccounts, 2) }}</td>
                    <td class="text-end font-monospace text-danger">- Rs. {{ number_format($totalOutAllAccounts, 2) }}</td>
                    <td class="text-end font-monospace fw-bold text-dark">Rs. {{ number_format($totalClosingAllAccounts, 2) }}</td>
                @else
                    <td class="text-start font-monospace">Rs. {{ number_format($openingBalance, 2) }}</td>
                    <td class="text-end font-monospace text-success">+ Rs. {{ number_format($totalIn, 2) }}</td>
                    <td class="text-end font-monospace text-danger">- Rs. {{ number_format($totalOut, 2) }}</td>
                    <td class="text-end font-monospace fw-bold text-dark">Rs. {{ number_format($closingBalance, 2) }}</td>
                @endif
            </tr>
        </tbody>
    </table>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 pb-md-4 mb-3 mb-md-4 flex-wrap gap-2 d-print-none">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="Green Pharmacie" style="height: 48px; max-width: 170px; object-fit: contain;" class="rounded-1" onerror="this.onerror=null; this.src='/images/logo.png';">
            <div>
                <h4 class="fw-bold text-dark m-0">Green Pharmacie</h4>
                @if($isWholeShop ?? false)
                    <h5 class="text-primary fw-bold m-0" style="font-size: clamp(1rem, 3.5vw, 1.25rem);">Whole Shop Financial &amp; Liquidity Report</h5>
                    <small class="text-muted">Consolidated Daily Sales &amp; All Account Ledgers &bull; Software by <strong>Prowave Technologies (+92 317 3836223)</strong></small>
                @else
                    <h5 class="text-secondary fw-bold m-0" style="font-size: clamp(1rem, 3.5vw, 1.25rem);">Cash Book &amp; Liquidity Statement</h5>
                    <small class="text-muted">{{ $cashAccount->name }} Register &bull; Software by <strong>Prowave Technologies (+92 317 3836223)</strong></small>
                @endif
            </div>
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
        @if($isWholeShop ?? false)
            <div class="col-6 col-md-3">
                <div class="p-3 bg-primary bg-opacity-10 rounded-3 border border-primary-subtle h-100">
                    <span class="text-primary small fw-semibold d-block mb-1 kpi-title">Total Net Sale (Period)</span>
                    <h5 class="fw-bold font-monospace text-primary m-0 kpi-amount">Rs. {{ number_format($totalNetSale, 2) }}</h5>
                    <small class="text-muted" style="font-size: 0.7rem;">Gross Sale minus Returns</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle h-100">
                    <span class="text-success small fw-semibold d-block mb-1 kpi-title">(+) Total Inflows (All Accounts)</span>
                    <h5 class="fw-bold font-monospace text-success m-0 kpi-amount">+ Rs. {{ number_format($totalInAllAccounts, 2) }}</h5>
                    <small class="text-success-emphasis" style="font-size: 0.7rem;">Cash &amp; Digital Collections</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle h-100">
                    <span class="text-danger small fw-semibold d-block mb-1 kpi-title">(-) Total Outflows (All Accounts)</span>
                    <h5 class="fw-bold font-monospace text-danger m-0 kpi-amount">- Rs. {{ number_format($totalOutAllAccounts, 2) }}</h5>
                    <small class="text-danger-emphasis" style="font-size: 0.7rem;">Supplier &amp; Expense Payouts</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-dark text-white rounded-3 shadow-sm h-100">
                    <span class="text-white-50 small fw-semibold d-block mb-1 kpi-title">(=) Ending Total Liquidity</span>
                    <h5 class="fw-bold font-monospace text-white m-0 kpi-amount">Rs. {{ number_format($totalClosingAllAccounts, 2) }}</h5>
                    <small class="text-white-50" style="font-size: 0.7rem;">Combined Balances of All Accounts</small>
                </div>
            </div>
        @else
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border h-100">
                    <span class="text-muted small d-block mb-1 kpi-title">Opening Balance</span>
                    <h5 class="fw-bold font-monospace text-dark m-0 kpi-amount">Rs. {{ number_format($openingBalance, 2) }}</h5>
                    <small class="text-muted" style="font-size: 0.7rem;">Prior to {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }}</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle h-100">
                    <span class="text-success small fw-semibold d-block mb-1 kpi-title">(+) Total Inflows</span>
                    <h5 class="fw-bold font-monospace text-success m-0 kpi-amount">+ Rs. {{ number_format($totalIn, 2) }}</h5>
                    <small class="text-success-emphasis" style="font-size: 0.7rem;">Collections &amp; Receipts</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle h-100">
                    <span class="text-danger small fw-semibold d-block mb-1 kpi-title">(-) Total Outflows</span>
                    <h5 class="fw-bold font-monospace text-danger m-0 kpi-amount">- Rs. {{ number_format($totalOut, 2) }}</h5>
                    <small class="text-danger-emphasis" style="font-size: 0.7rem;">Payments &amp; Disbursements</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-primary text-white rounded-3 shadow-sm h-100">
                    <span class="text-white-50 small fw-semibold d-block mb-1 kpi-title">(=) Ending Balance</span>
                    <h5 class="fw-bold font-monospace text-white m-0 kpi-amount">Rs. {{ number_format($closingBalance, 2) }}</h5>
                    <small class="text-white-50" style="font-size: 0.7rem;">As of {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</small>
                </div>
            </div>
        @endif
    </div>

    @if($isWholeShop ?? false)
        <!-- Whole Shop Matrix Table: Detailed Ledger with Date, Voucher #, Party, Details, Net Sale + 3 columns per Account with Horizontal Scroll -->
        <div class="table-responsive mb-4 shadow-xs border rounded-3" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table class="table table-sm table-bordered table-hover align-middle mb-0 text-nowrap" style="font-size: 0.83rem;">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="align-middle text-center bg-light fw-bold" style="width: 105px; min-width: 105px; border-bottom: 2px solid #cbd5e1;">Date</th>
                        <th rowspan="2" class="align-middle text-center bg-light fw-bold" style="width: 120px; min-width: 120px; border-bottom: 2px solid #cbd5e1;">Voucher #</th>
                        <th rowspan="2" class="align-middle bg-light fw-bold" style="min-width: 180px; border-bottom: 2px solid #cbd5e1;">Party / Payee</th>
                        <th rowspan="2" class="align-middle bg-light fw-bold" style="min-width: 220px; border-bottom: 2px solid #cbd5e1;">Particulars / Details</th>
                        <th rowspan="2" class="align-middle text-end bg-primary-subtle text-primary fw-bold" style="width: 125px; min-width: 125px; border-bottom: 2px solid #cbd5e1;">Net Sale</th>
                        @foreach($accounts as $acc)
                            <th colspan="3" class="text-center fw-bold py-2 border-start {{ $acc->isCash() ? 'bg-success-subtle text-success' : ($acc->type === 'bank' ? 'bg-info-subtle text-info-emphasis' : 'bg-warning-subtle text-dark') }}" style="border-bottom: 1px solid #cbd5e1;">
                                <span class="d-inline-flex align-items-center gap-1">
                                    <i class="bi {{ $acc->isCash() ? 'bi-cash-stack' : ($acc->type === 'bank' ? 'bi-bank' : 'bi-phone') }}"></i>
                                    {{ $acc->name }}
                                </span>
                                <span class="badge bg-white text-secondary border ms-1" style="font-size: 0.68rem;">{{ ucfirst($acc->type) }}</span>
                            </th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($accounts as $acc)
                            <th class="text-end small border-start fw-semibold py-1.5" style="min-width: 95px; font-size: 0.75rem;">In (+)</th>
                            <th class="text-end small fw-semibold py-1.5" style="min-width: 95px; font-size: 0.75rem;">Out (-)</th>
                            <th class="text-end small bg-light fw-bold py-1.5" style="min-width: 115px; font-size: 0.75rem;">Running</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row (Prior to From Date) -->
                    <tr class="table-secondary bg-opacity-25 fw-semibold">
                        <td class="text-center font-monospace">{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                        <td class="text-center font-monospace text-muted">-</td>
                        <td class="text-muted fw-bold">Opening Balance</td>
                        <td class="text-muted">Balance brought forward prior to {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }}</td>
                        <td class="text-end text-muted font-monospace">-</td>
                        @foreach($accounts as $acc)
                            <td class="text-end font-monospace text-muted border-start">-</td>
                            <td class="text-end font-monospace text-muted">-</td>
                            <td class="text-end font-monospace fw-bold text-dark bg-light bg-opacity-50">
                                {{ number_format($initialBalances[$acc->id] ?? 0, 2) }}
                            </td>
                        @endforeach
                    </tr>

                    <!-- Transaction Detail Rows -->
                    @forelse($rows as $row)
                        <tr>
                            <td class="text-center font-monospace text-dark fw-semibold">
                                {{ $row['date'] }}
                            </td>
                            <td class="font-monospace text-center">
                                @if($row['is_shift'])
                                    <a href="{{ route('shift-closings.show', $row['shift_id']) }}" class="text-decoration-none fw-bold" target="_blank">
                                        #{{ $row['voucher_no'] }}
                                    </a>
                                @else
                                    <a href="{{ route('transactions.show', $row['tx_id']) }}" class="text-decoration-none fw-bold" target="_blank">
                                        #{{ $row['voucher_no'] }}
                                    </a>
                                @endif
                                @if($row['bill_no'] && $row['bill_no'] !== '-')
                                    <span class="d-block text-muted small" style="font-size: 0.7rem;">Bill: {{ $row['bill_no'] }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $row['party'] }}</span>
                            </td>
                            <td>
                                <div>{{ $row['description'] ?? '-' }}</div>
                                @if($row['category'] && $row['category'] !== '-')
                                    <span class="badge bg-light text-muted border px-1" style="font-size: 0.68rem;">{{ $row['category'] }}</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace fw-bold {{ $row['net_sale'] > 0 ? 'text-primary' : 'text-muted' }}">
                                {{ $row['net_sale'] > 0 ? number_format($row['net_sale'], 2) : '-' }}
                            </td>
                            @foreach($accounts as $acc)
                                @php
                                    $accData = $row['accounts'][$acc->id] ?? ['in' => 0, 'out' => 0, 'running' => 0];
                                @endphp
                                <td class="text-end font-monospace border-start {{ $accData['in'] > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                                    {{ $accData['in'] > 0 ? '+ ' . number_format($accData['in'], 2) : '-' }}
                                </td>
                                <td class="text-end font-monospace {{ $accData['out'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                    {{ $accData['out'] > 0 ? '- ' . number_format($accData['out'], 2) : '-' }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark bg-light bg-opacity-50">
                                    {{ number_format($accData['running'], 2) }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 5 + count($accounts) * 3 }}" class="text-center py-4 text-muted">
                                No transactions recorded for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light border-top border-2 border-secondary">
                    <tr class="fw-bold align-middle text-dark">
                        <td colspan="4" class="text-end text-uppercase pe-3 text-dark">Total Period Movement:</td>
                        <td class="text-end font-monospace text-primary fs-6">
                            {{ number_format($totalNetSale, 2) }}
                        </td>
                        @foreach($accounts as $acc)
                            <td class="text-end font-monospace text-success border-start">
                                + {{ number_format($accountTotals[$acc->id]['in'] ?? 0, 2) }}
                            </td>
                            <td class="text-end font-monospace text-danger">
                                - {{ number_format($accountTotals[$acc->id]['out'] ?? 0, 2) }}
                            </td>
                            <td class="text-end font-monospace fw-bold text-dark bg-secondary bg-opacity-10">
                                {{ number_format($runningBalances[$acc->id] ?? 0, 2) }}
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="text-muted small d-flex align-items-center gap-1 mb-3 no-print">
            <i class="bi bi-arrows-expand-vertical text-secondary" style="transform: rotate(90deg);"></i>
            <span>Whole Shop matrix shows full transaction details (Date, Voucher #, Party, Details, Net Sale) alongside 3 columns (In, Out, Running) per account. Scroll horizontally or swipe on touchscreens to view all accounts.</span>
        </div>
    @else
        <!-- Desktop View: Single Account Register Table -->
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
                        <th class="text-end" style="width: 150px;">Running Balance (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row -->
                    <tr class="table-secondary bg-opacity-25 fw-semibold">
                        <td>{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                        <td class="font-monospace text-muted">-</td>
                        <td class="text-muted">{{ $cashAccount->name }}</td>
                        <td class="text-muted">Opening Balance</td>
                        <td class="text-muted">Balance brought forward prior to {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }}</td>
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
                                @if(!empty($entry->shift_id))
                                    <a href="{{ route('shift-closings.show', $entry->shift_id) }}" class="text-decoration-none" target="_blank">
                                        #{{ $entry->voucher_no }}
                                    </a>
                                @elseif(str_starts_with((string)$entry->id, 'shift-'))
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
                                No transactions recorded for {{ $cashAccount->name }} within the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end">Total Movement &amp; Closing Position:</td>
                        <td class="text-end font-monospace text-success">+ Rs. {{ number_format($totalIn, 2) }}</td>
                        <td class="text-end font-monospace text-danger">- Rs. {{ number_format($totalOut, 2) }}</td>
                        <td class="text-end font-monospace text-primary fs-6">Rs. {{ number_format($closingBalance, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll for single account) -->
        <div class="d-block d-md-none mb-3 d-print-none">
            <!-- Opening Balance Card -->
            <div class="p-2.5 rounded-3 border bg-light mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold text-secondary small">Opening Balance</div>
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
                                @if(!empty($entry->shift_id))
                                    <a href="{{ route('shift-closings.show', $entry->shift_id) }}" class="text-decoration-none font-monospace small" target="_blank">
                                        #{{ $entry->voucher_no }}
                                    </a>
                                @elseif(str_starts_with((string)$entry->id, 'shift-'))
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
                                <span class="text-muted d-block" style="font-size: 0.65rem;">Running Balance</span>
                                <span class="font-monospace fw-bold text-dark">Rs. {{ number_format($entry->running_balance, 2) }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4 small">
                        No transactions recorded within the selected period.
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
                    <span class="fw-bold text-dark">Ending Balance:</span>
                    <span class="font-monospace fw-bold fs-6 text-primary">Rs. {{ number_format($closingBalance, 2) }}</span>
                </div>
            </div>
        </div>
    @endif

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

    <!-- Statement Footer Notice -->
    <div class="text-center pt-3 mt-2 border-top text-muted small" style="font-size: 8pt;">
        <span>Official Cash Book Statement &bull; Software Developed by <strong>Prowave Technologies</strong> &bull; Support Helpline: <a href="tel:+923173836223" class="text-decoration-none text-dark fw-bold">+92 317 3836223</a></span>
    </div>

</div>
@endsection
