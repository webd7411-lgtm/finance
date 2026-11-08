@extends('layouts.app')

@section('title', 'Bank Book & Wallets Register - FinanceDesk')
@section('page_title', 'Bank & Wallet Register')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-bank me-1 text-primary"></i> Digital Ledger
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
    /* Tighten page top spacing for maximum viewport efficiency */
    .sub-header {
        padding: 0.35rem 0 !important;
        margin-bottom: 0.5rem !important;
    }
    main.container-fluid {
        margin-bottom: 0.5rem !important;
    }
    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .kpi-title {
        font-size: 0.63rem;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        font-weight: 700;
    }
    .kpi-amount {
        font-size: 1.05rem;
        line-height: 1.2;
        word-break: break-word;
    }

    /* High-Density Sticky Table Container */
    .bank-book-scroll-container {
        max-height: calc(100vh - 315px);
        min-height: 110px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
    }
    .bank-book-scroll-container::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .bank-book-scroll-container::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 3px;
    }
    .bank-book-scroll-container::-webkit-scrollbar-thumb:hover {
        background-color: #94a3b8;
    }
    .bank-book-scroll-container::-webkit-scrollbar-track {
        background-color: #f8fafc;
    }
    .bank-book-scroll-container table thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 10 !important;
        background-color: #f1f5f9 !important;
        box-shadow: inset 0 -1px 0 #cbd5e1;
        padding: 5px 8px !important;
        font-size: 0.77rem !important;
    }
    .bank-book-scroll-container table td {
        padding: 4px 8px !important;
        font-size: 0.81rem !important;
    }
    .bank-book-scroll-container table tfoot td {
        position: sticky !important;
        bottom: 0 !important;
        z-index: 9 !important;
        background-color: #f8fafc !important;
        box-shadow: inset 0 1px 0 #cbd5e1;
        padding: 5px 8px !important;
        font-weight: 700 !important;
        font-size: 0.82rem !important;
    }

    @media (max-width: 575.98px) {
        .card-custom {
            border-radius: 8px;
        }
        .bank-book-scroll-container {
            max-height: calc(100vh - 280px);
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
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
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

        #bankBookSheet, .card-custom {
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
        .bank-book-scroll-container {
            max-height: none !important;
            overflow: visible !important;
            border: none !important;
            box-shadow: none !important;
        }
        .bank-book-scroll-container table thead th,
        .bank-book-scroll-container table tfoot td {
            position: static !important;
            box-shadow: none !important;
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
            display: block !important;
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
<div class="card-custom p-2 px-3 mb-2 no-print bg-white border shadow-xs">
    <form method="GET" action="{{ route('ledgers.bank-book') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label text-muted fw-semibold mb-0.5" style="font-size: 0.72rem;">Select Bank / Wallet Account</label>
            <select name="account_id" class="form-select form-select-sm py-1" style="height: 32px; font-size: 0.81rem;" onchange="this.form.submit()">
                <option value="">All Bank & Wallet Accounts</option>
                @foreach($bankAccounts as $acc)
                    <option value="{{ $acc->id }}" {{ $selectedAccountId == $acc->id ? 'selected' : '' }}>
                        [{{ ucfirst($acc->type) }}] {{ $acc->name }} (Current: Rs. {{ number_format($acc->current_balance, 2) }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label text-muted fw-semibold mb-0.5" style="font-size: 0.72rem;">From Date</label>
            <input type="date" name="from_date" class="form-control form-control-sm py-1" style="height: 32px; font-size: 0.81rem;" value="{{ $fromDate }}">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label text-muted fw-semibold mb-0.5" style="font-size: 0.72rem;">To Date</label>
            <input type="date" name="to_date" class="form-control form-control-sm py-1" style="height: 32px; font-size: 0.81rem;" value="{{ $toDate }}">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm rounded-2 w-100 fw-semibold py-1 d-flex align-items-center justify-content-center" style="height: 32px; font-size: 0.81rem;">
                <i class="bi bi-filter me-1"></i> Apply Filter
            </button>
        </div>
    </form>
</div>

<!-- Printable Bank Book Card -->
<div class="card-custom p-2.5 p-md-3 bg-white shadow-xs mb-2 border rounded-3" id="bankBookSheet">
    <!-- OFFICIAL BANK STATEMENT PRINT HEADER (Visible on Print) -->
    <div class="bank-stmt-header d-none d-print-block">
        <div class="d-flex justify-content-between align-items-start pb-2 border-bottom border-dark">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="Caltex Rao Filling Station" style="height: 52px; max-width: 175px; object-fit: contain;" onerror="this.onerror=null; this.src='/images/logo.png';">
                <div>
                    <h3 class="fw-bold m-0 text-uppercase tracking-tight" style="color: #0f172a; font-size: 15pt;">CALTEX - RAO FILLING STATION</h3>
                    <div class="small fw-semibold text-secondary">CENTRAL FINANCIAL TREASURY &amp; COMMERCIAL AUDIT</div>
                    <div class="small text-muted" style="font-size: 8pt;">Caltex Petroleum &bull; Institutional Liquidity Ledger &bull; Software by <strong>Prowave Technologies (+92 317 3836223)</strong></div>
                </div>
            </div>
            <div class="text-end">
                <div class="badge bg-dark text-white text-uppercase px-2 py-1 mb-1" style="font-size: 8.5pt;">Official Statement</div>
                <div class="fw-bold" style="font-size: 11pt; color: #0f172a;">BANK &amp; DIGITAL WALLET STATEMENT</div>
                <div class="small text-muted" style="font-size: 8pt;">Statement Date: {{ now()->format('d M, Y h:i A') }}</div>
            </div>
        </div>

        @php
            $activeAccount = $selectedAccountId ? $bankAccounts->firstWhere('id', $selectedAccountId) : null;
        @endphp
        <div class="row mt-2 py-1 text-dark" style="font-size: 8.5pt;">
            <div class="col-7">
                <div><strong>Account Scope:</strong> {{ $activeAccount ? $activeAccount->name . ' (' . ucfirst($activeAccount->type) . ')' : 'Consolidated (All Bank & Digital Wallet Accounts)' }}</div>
                <div><strong>Statement Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} &ndash; {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</div>
                <div><strong>Base Currency:</strong> PKR (Pakistani Rupee)</div>
            </div>
            <div class="col-5 text-end">
                <div><strong>Audited By:</strong> {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</div>
                <div><strong>System Reference:</strong> BK-{{ date('Ymd') }}-{{ $selectedAccountId ? str_pad($selectedAccountId, 3, '0', STR_PAD_LEFT) : 'ALL' }}</div>
            </div>
        </div>
    </div>

    <!-- Bank Statement Summary Table (Print Only) -->
    <table class="bank-summary-table d-none d-print-table mb-3">
        <thead>
            <tr>
                <th class="text-start" style="width: 25%;">Opening Bank Balance</th>
                <th class="text-end" style="width: 25%;">(+) Total Deposits</th>
                <th class="text-end" style="width: 25%;">(-) Total Withdrawals</th>
                <th class="text-end" style="width: 25%;">(=) Ending Bank Balance</th>
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

    <!-- Header Section (Screen View - Compact) -->
    <div class="d-flex justify-content-between align-items-center border-bottom pb-1.5 mb-2 flex-wrap gap-2 d-print-none">
        <div class="d-flex align-items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="Caltex Rao Filling Station" style="height: 32px; max-width: 120px; object-fit: contain;" class="rounded-1" onerror="this.onerror=null; this.src='/images/logo.png';">
            <div>
                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                    <span class="fw-bold text-dark" style="font-size: 1rem;">Caltex Rao Filling Station</span>
                    <span class="text-secondary fw-semibold" style="font-size: 0.85rem;">&bull; Bank &amp; Digital Wallet Register</span>
                </div>
                <div class="text-muted" style="font-size: 0.68rem; line-height: 1.2;">Caltex Petroleum &bull; Institutional Liquidity Ledger &bull; Software by <strong>Prowave Technologies (+92 317 3836223)</strong></div>
            </div>
        </div>
        <div class="text-start text-md-end">
            <span class="badge bg-light text-dark border px-2 py-0.5 mb-0.5 font-monospace d-inline-block" style="font-size: 0.72rem;">
                Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}
            </span>
            <div class="text-muted" style="font-size: 0.67rem;">Audited: {{ now()->format('d M, Y - h:i A') }} &bull; Operator: <strong>{{ auth()->user()->name }}</strong></div>
        </div>
    </div>

    <!-- Executive Metric Cards (High-Density) -->
    <div class="row g-2 mb-2 d-print-none">
        <div class="col-6 col-md-3">
            <div class="p-2 px-2.5 bg-light rounded-2 border h-100">
                <span class="text-muted text-uppercase fw-bold d-block kpi-title">Opening Bank Balance</span>
                <div class="fw-bold font-monospace text-dark kpi-amount my-0.5">Rs. {{ number_format($openingBalance, 2) }}</div>
                <div class="text-muted text-truncate" style="font-size: 0.65rem;">Prior to {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-2 px-2.5 bg-success-subtle rounded-2 border border-success-subtle h-100">
                <span class="text-success text-uppercase fw-bold d-block kpi-title">(+) Total Deposits</span>
                <div class="fw-bold font-monospace text-success kpi-amount my-0.5">+ Rs. {{ number_format($totalIn, 2) }}</div>
                <div class="text-success-emphasis text-truncate" style="font-size: 0.65rem;">Bank &amp; Wallet Collections</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-2 px-2.5 bg-danger-subtle rounded-2 border border-danger-subtle h-100">
                <span class="text-danger text-uppercase fw-bold d-block kpi-title">(-) Total Withdrawals</span>
                <div class="fw-bold font-monospace text-danger kpi-amount my-0.5">- Rs. {{ number_format($totalOut, 2) }}</div>
                <div class="text-danger-emphasis text-truncate" style="font-size: 0.65rem;">Disbursements &amp; Payments</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-2 px-2.5 bg-primary text-white rounded-2 shadow-xs h-100">
                <span class="text-white-50 text-uppercase fw-bold d-block kpi-title">(=) Ending Bank Balance</span>
                <div class="fw-bold font-monospace text-white kpi-amount my-0.5">Rs. {{ number_format($closingBalance, 2) }}</div>
                <div class="text-white-50 text-truncate" style="font-size: 0.65rem;">As of {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</div>
            </div>
        </div>
    </div>

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block statement-desktop-table table-responsive bank-book-scroll-container mb-2">
        <table class="table table-sm table-bordered table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 105px;">Date</th>
                    <th style="width: 110px;">Voucher #</th>
                    <th>Bank / Wallet Account</th>
                    <th>Party / Payee</th>
                    <th>Particulars / Description</th>
                    <th class="text-end" style="width: 130px;">Deposit (+)</th>
                    <th class="text-end" style="width: 130px;">Withdrawal (-)</th>
                    <th class="text-end" style="width: 150px;">Running Balance (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance Row -->
                <tr class="table-secondary bg-opacity-25 fw-semibold">
                    <td>{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                    <td class="font-monospace text-muted">-</td>
                    <td class="text-muted">Bank Accounts Pool</td>
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
                            <a href="{{ route('transactions.show', $entry->id) }}" class="text-decoration-none" target="_blank">
                                #{{ $entry->voucher_no }}
                            </a>
                            @if($entry->bill_no)
                                <span class="d-block text-muted small" style="font-size: 0.72rem;">Bill: {{ $entry->bill_no }}</span>
                            @endif
                        </td>
                        <td>
                            @if($entry->account_type === 'jazzcash')
                                <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                                    <i class="bi bi-phone me-1"></i>{{ $entry->account }}
                                </span>
                            @else
                                <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                    <i class="bi bi-bank me-1"></i>{{ $entry->account }}
                                </span>
                            @endif
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
                            No bank or digital wallet transactions recorded within the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="5" class="text-end">Total Deposits & Ending Bank Position:</td>
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
                    <div class="fw-semibold text-secondary small">Opening Bank Balance</div>
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
                            <a href="{{ route('transactions.show', $entry->id) }}" class="text-decoration-none font-monospace small" target="_blank">
                                #{{ $entry->voucher_no }}
                            </a>
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
                    No bank or digital wallet transactions recorded within the selected period.
                </div>
            @endforelse
        </div>

        <!-- Mobile Totals Card -->
        <div class="p-3 rounded-3 border bg-primary bg-opacity-10 mt-2">
            <div class="d-flex justify-content-between align-items-center small py-1">
                <span class="text-success fw-semibold">(+) Total Deposits:</span>
                <span class="font-monospace fw-bold text-success">+Rs. {{ number_format($totalIn, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center small py-1">
                <span class="text-danger fw-semibold">(-) Total Withdrawals:</span>
                <span class="font-monospace fw-bold text-danger">-Rs. {{ number_format($totalOut, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-1">
                <span class="fw-bold text-dark">Ending Bank Balance:</span>
                <span class="font-monospace fw-bold fs-6 text-primary">Rs. {{ number_format($closingBalance, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Official Bank Statement Signatures (Visible on Print) -->
    <div class="statement-signatures pt-4 mt-3 d-none d-print-block">
        <div class="row text-center w-100 m-0">
            <div class="col-4">
                <div class="statement-sig-line">
                    <div class="small fw-bold text-dark">Prepared By (Finance Desk)</div>
                    <div class="small text-muted" style="font-size: 8pt;">{{ auth()->user()->name }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="statement-sig-line">
                    <div class="small fw-bold text-dark">Reconciliation Officer</div>
                    <div class="small text-muted" style="font-size: 8pt;">Branch Accounts Supervisor</div>
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

    <!-- Statement Footer Notice (Visible on Print) -->
    <div class="text-center pt-2 mt-2 border-top text-muted small d-none d-print-block" style="font-size: 8pt;">
        <span>Official Bank &amp; Wallet Statement &bull; Software Developed by <strong>Prowave Technologies</strong> &bull; Support Helpline: <a href="tel:+923173836223" class="text-decoration-none text-dark fw-bold">+92 317 3836223</a></span>
    </div>

</div>
@endsection
