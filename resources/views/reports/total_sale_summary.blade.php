@extends('layouts.app')

@section('title', 'Total Sale Summary - FinanceDesk')
@section('page_title', 'Total Sale Summary')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-file-earmark-text text-primary me-1"></i> Official Statement
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-2 w-100 justify-content-start justify-content-sm-end no-print">
        <button type="button" class="btn btn-dark btn-sm rounded-2 d-flex align-items-center gap-1.5 shadow-sm" onclick="window.print()">
            <i class="bi bi-printer-fill"></i> Print Statement (PDF)
        </button>
        <a href="{{ route('reports.daily-closing') }}" class="btn btn-outline-secondary btn-sm rounded-2 d-flex align-items-center gap-1.5">
            <i class="bi bi-calendar2-range text-warning"></i> Daily Closings
        </a>
    </div>
@endsection

@push('styles')
<style>
    /* Clean Statement Sheet Styling */
    .statement-sheet {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        max-width: 980px;
        margin: 0 auto;
        padding: 24px 30px;
        color: #0f172a;
        font-size: 9pt;
        line-height: 1.35;
    }

    .stmt-box {
        border: 1px solid #334155 !important;
        margin-bottom: 14px;
    }

    .stmt-header-cell {
        background-color: #f1f5f9;
        font-weight: 700;
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #1e293b;
        padding: 4px 8px;
        border-bottom: 1px solid #334155;
    }

    .stmt-table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin-bottom: 0 !important;
        border: 1px solid #334155 !important;
    }

    .stmt-table th, .stmt-table td {
        border: 1px solid #94a3b8 !important;
        padding: 5px 8px !important;
        font-size: 8.5pt !important;
        color: #0f172a !important;
    }

    .stmt-table thead th {
        background-color: #f1f5f9 !important;
        font-weight: 700 !important;
        color: #0f172a !important;
        text-transform: uppercase !important;
        font-size: 7.8pt !important;
        letter-spacing: 0.3px !important;
        border-bottom: 1px solid #334155 !important;
    }

    .section-title {
        font-size: 8.5pt;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #0f172a;
        margin-bottom: 4px;
        padding-bottom: 2px;
        border-bottom: 1.5px solid #0f172a;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .preset-pill {
        font-size: 0.78rem;
        padding: 0.22rem 0.6rem;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.15s ease;
    }
    .preset-pill.active {
        background-color: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
    }

    /* Print & PDF (Formal Bank Statement Style) */
    @media print {
        @page {
            size: A4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }
        html, body {
            background: #ffffff !important;
            color: #000000 !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            font-size: 8.2pt !important;
            line-height: 1.25 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .vip-navbar, .sub-header, .no-print, .btn, .alert, footer, nav, header {
            display: none !important;
        }
        main, .container-fluid, .row, .col-12 {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }
        .statement-sheet {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            max-width: 100% !important;
            margin: 0 !important;
        }
        .stmt-box, .stmt-table, .stmt-table th, .stmt-table td {
            border-color: #000000 !important;
        }
        .stmt-table thead th, .stmt-header-cell {
            background-color: #f1f5f9 !important;
            color: #000000 !important;
        }
        .statement-signatures {
            page-break-inside: avoid;
            margin-top: 20px !important;
            padding-top: 10px !important;
            border-top: 1px solid #000000 !important;
        }
        .statement-sig-line {
            border-top: 1px dashed #000000;
            width: 80%;
            margin: 0 auto;
            padding-top: 3px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-2 px-lg-4 pb-5">

    <!-- Screen Filter Bar (Hidden on Print) -->
    <div class="card p-3 mb-3 no-print border rounded-2 bg-white shadow-sm" style="max-width: 980px; margin-left: auto; margin-right: auto;">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-2">
            <div>
                <strong class="text-dark small">Statement Reporting Period:</strong>
                <span class="text-muted small ms-1">{{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} &ndash; {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</span>
            </div>

            @php
                $today = \Carbon\Carbon::today()->toDateString();
                $yesterday = \Carbon\Carbon::yesterday()->toDateString();
                $startOfWeek = \Carbon\Carbon::today()->startOfWeek()->toDateString();
                $startOfMonth = \Carbon\Carbon::today()->startOfMonth()->toDateString();
                $lastMonthStart = \Carbon\Carbon::today()->subMonth()->startOfMonth()->toDateString();
                $lastMonthEnd = \Carbon\Carbon::today()->subMonth()->endOfMonth()->toDateString();
            @endphp
            <div class="d-flex flex-wrap gap-1">
                <a href="{{ route('reports.total-sale-summary', ['from_date' => $today, 'to_date' => $today]) }}" 
                   class="btn btn-outline-secondary btn-sm preset-pill {{ $fromDate === $today && $toDate === $today ? 'active' : '' }}">Today</a>
                <a href="{{ route('reports.total-sale-summary', ['from_date' => $yesterday, 'to_date' => $yesterday]) }}" 
                   class="btn btn-outline-secondary btn-sm preset-pill {{ $fromDate === $yesterday && $toDate === $yesterday ? 'active' : '' }}">Yesterday</a>
                <a href="{{ route('reports.total-sale-summary', ['from_date' => $startOfWeek, 'to_date' => $today]) }}" 
                   class="btn btn-outline-secondary btn-sm preset-pill {{ $fromDate === $startOfWeek && $toDate === $today ? 'active' : '' }}">This Week</a>
                <a href="{{ route('reports.total-sale-summary', ['from_date' => $startOfMonth, 'to_date' => $today]) }}" 
                   class="btn btn-outline-secondary btn-sm preset-pill {{ $fromDate === $startOfMonth && $toDate === $today ? 'active' : '' }}">This Month</a>
                <a href="{{ route('reports.total-sale-summary', ['from_date' => $lastMonthStart, 'to_date' => $lastMonthEnd]) }}" 
                   class="btn btn-outline-secondary btn-sm preset-pill {{ $fromDate === $lastMonthStart && $toDate === $lastMonthEnd ? 'active' : '' }}">Last Month</a>
            </div>
        </div>

        <form method="GET" action="{{ route('reports.total-sale-summary') }}" class="row g-2 align-items-end pt-2 border-top">
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small fw-semibold text-secondary mb-1">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}" required>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small fw-semibold text-secondary mb-1">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}" required>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-filter me-1"></i> Apply Filter
                </button>
                <button type="button" class="btn btn-dark btn-sm px-3" onclick="window.print()" title="Print Statement">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </form>
    </div>

    <!-- =========================================================
         FORMAL BANK STATEMENT SHEET CONTAINER
         ========================================================= -->
    <div class="statement-sheet">

        <!-- 1. STATEMENT HEADER BLOCK -->
        <div class="d-flex justify-content-between align-items-start pb-2 mb-2 border-bottom border-2 border-dark">
            <div>
                <h4 class="fw-bold m-0 text-uppercase tracking-tight" style="color: #0f172a; font-size: 15pt;">FINANCEDESK ENTERPRISE</h4>
                <div class="fw-semibold text-secondary" style="font-size: 8.5pt;">CENTRAL COMMERCIAL TREASURY &bull; OFFICIAL STATEMENT OF ACCOUNT</div>
            </div>
            <div class="text-end">
                <div class="fw-bold text-uppercase" style="font-size: 11pt; color: #0f172a;">SALES & CASH FLOW STATEMENT</div>
                <div class="small text-muted" style="font-size: 7.8pt;">Date Issued: {{ now()->format('d M, Y h:i A') }}</div>
            </div>
        </div>

        <!-- 2. STATEMENT METADATA STRIP -->
        <div class="stmt-box mb-3">
            <div class="row g-0 text-dark" style="font-size: 8.2pt;">
                <div class="col-6 col-md-3 p-1.5 border-end border-dark">
                    <span class="text-muted d-block" style="font-size: 7.2pt; text-transform: uppercase;">Account Entity</span>
                    <strong>Commercial Treasury</strong>
                </div>
                <div class="col-6 col-md-4 p-1.5 border-end border-dark">
                    <span class="text-muted d-block" style="font-size: 7.2pt; text-transform: uppercase;">Statement Period</span>
                    <strong>{{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} &ndash; {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</strong>
                </div>
                <div class="col-6 col-md-2 p-1.5 border-end border-dark">
                    <span class="text-muted d-block" style="font-size: 7.2pt; text-transform: uppercase;">Base Currency</span>
                    <strong>PKR (Rs.)</strong>
                </div>
                <div class="col-6 col-md-3 p-1.5">
                    <span class="text-muted d-block" style="font-size: 7.2pt; text-transform: uppercase;">Prepared By</span>
                    <strong>{{ auth()->user()->name }}</strong>
                </div>
            </div>
        </div>

        <!-- 3. STATEMENT SUMMARY (DETAILS GRID AT THE TOP) -->
        <div class="stmt-box mb-3">
            <div class="stmt-header-cell d-flex justify-content-between align-items-center">
                <span>Statement Financial Summary (Period Overview)</span>
                <span>Values in PKR</span>
            </div>
            <div class="row g-0" style="font-size: 8.4pt;">
                <!-- Left Column: Inflows & Sales -->
                <div class="col-12 col-md-6 border-end border-dark p-2">
                    <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle">
                        <span>Total Counter Sales (Net):</span>
                        <strong class="font-monospace">Rs. {{ number_format($netSales, 2) }}</strong>
                    </div>
                    @if($salesReturns > 0)
                        <div class="d-flex justify-content-between py-0.5 text-muted" style="font-size: 7.8pt;">
                            <span class="ps-2">&bull; Gross Sales: Rs. {{ number_format($grossSales, 2) }}</span>
                            <span class="font-monospace">Returns: -Rs. {{ number_format($salesReturns, 2) }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle">
                        <span>Customer Recoveries (Payment In):</span>
                        <strong class="font-monospace">Rs. {{ number_format($totalDirectIn, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle text-muted" style="font-size: 8pt;">
                        <span class="ps-2">&bull; Cash Drawer Received:</span>
                        <span class="font-monospace text-dark">Rs. {{ number_format($periodCashIn, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle text-muted" style="font-size: 8pt;">
                        <span class="ps-2">&bull; Bank Accounts Received:</span>
                        <span class="font-monospace text-dark">Rs. {{ number_format($periodBankIn, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle text-muted" style="font-size: 8pt;">
                        <span class="ps-2">&bull; JazzCash / Wallets Received:</span>
                        <span class="font-monospace text-dark">Rs. {{ number_format($periodJazzIn, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between pt-1.5 fw-bold bg-light px-1 mt-1">
                        <span class="text-uppercase">Total Inflows (Credit):</span>
                        <span class="font-monospace">+ Rs. {{ number_format($totalPeriodCollections, 2) }}</span>
                    </div>
                </div>

                <!-- Right Column: Outflows & Net Balance -->
                <div class="col-12 col-md-6 p-2 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle">
                            <span>Disbursed to Parties & Suppliers:</span>
                            <strong class="font-monospace">- Rs. {{ number_format($totalPaidToParties, 2) }}</strong>
                        </div>
                        @if($totalShiftOperatingExpenses > 0)
                            <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle">
                                <span>Shift Operating Expenses:</span>
                                <strong class="font-monospace">- Rs. {{ number_format($totalShiftOperatingExpenses, 2) }}</strong>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between py-1.5 fw-bold border-bottom border-dark">
                            <span class="text-uppercase">Total Payments Disbursed (Debit):</span>
                            <span class="font-monospace text-danger">- Rs. {{ number_format($totalPeriodDisbursements, 2) }}</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <div class="p-2 border border-dark rounded-1 bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-uppercase d-block" style="font-size: 8.5pt;">Net Period Cash Surplus:</strong>
                                    <span class="text-muted" style="font-size: 7.4pt;">Period Collections less Disbursements</span>
                                </div>
                                <div class="font-monospace fw-bold" style="font-size: 11pt;">
                                    {{ $periodNetSurplus >= 0 ? '+' : '' }} Rs. {{ number_format($periodNetSurplus, 2) }}
                                </div>
                            </div>
                        </div>

                        <div class="p-2 border border-light-subtle rounded-1 bg-white mt-1.5" style="font-size: 7.8pt;">
                            <div class="d-flex justify-content-between py-0.5 text-muted">
                                <span>(+) Period Opening Balance:</span>
                                <span class="font-monospace">Rs. {{ number_format($periodOpeningLiquid, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-0.5 text-muted">
                                <span>(+) Net Period Cash Surplus:</span>
                                <span class="font-monospace">{{ $periodNetSurplus >= 0 ? '+' : '' }} Rs. {{ number_format($periodNetSurplus, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between pt-1 border-top border-secondary fw-bold text-dark">
                                <span class="text-uppercase">Total Available Liquid Funds:</span>
                                <span class="font-monospace">Rs. {{ number_format($periodClosingLiquid, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. TABLE: BANK & WALLET COLLECTIONS -->
        <div class="mb-3">
            <div class="section-title">
                <span>Collections by Bank & Digital Wallet Accounts</span>
                <span class="fw-normal text-muted" style="font-size: 7.5pt;">Inflows Received in Selected Period</span>
            </div>
            <table class="table stmt-table">
                <thead>
                    <tr>
                        <th style="width: 42%;">Account Name</th>
                        <th style="width: 18%;">Account Type</th>
                        <th style="width: 20%;">Account Number</th>
                        <th class="text-end" style="width: 20%;">Period Inflow Received</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bankWiseBreakdown as $b)
                        <tr>
                            <td><strong>{{ $b->name }}</strong></td>
                            <td class="text-uppercase" style="font-size: 7.8pt;">{{ $b->type }}</td>
                            <td class="font-monospace text-muted" style="font-size: 8pt;">{{ $b->account_number ?: '-' }}</td>
                            <td class="text-end font-monospace fw-bold">
                                {{ $b->amount_received > 0 ? ('Rs. ' . number_format($b->amount_received, 2)) : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-2">No bank or wallet accounts registered.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold bg-light">
                        <td colspan="3" class="text-uppercase text-end">Total Bank & Digital Collections:</td>
                        <td class="text-end font-monospace">Rs. {{ number_format(collect($bankWiseBreakdown)->sum('amount_received'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 5. TABLE: PARTY & SUPPLIER DISBURSEMENTS -->
        <div class="mb-3">
            <div class="section-title">
                <span>Disbursements to Parties & Expense Heads</span>
                <span class="fw-normal text-muted" style="font-size: 7.5pt;">Total Outflows Disbursed in Selected Period</span>
            </div>
            <table class="table stmt-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">Sr #</th>
                        <th style="width: 40%;">Party / Beneficiary Name</th>
                        <th style="width: 15%;">Category</th>
                        <th class="text-end" style="width: 13%;">Cash Paid</th>
                        <th class="text-end" style="width: 13%;">Bank Paid</th>
                        <th class="text-end" style="width: 14%;">Total Disbursed</th>
                    </tr>
                </thead>
                <tbody>
                    @php $idx = 1; @endphp
                    @forelse($partyDisbursements as $key => $p)
                        <tr>
                            <td class="text-muted" style="font-size: 7.8pt;">{{ $idx++ }}</td>
                            <td>
                                <strong>{{ $p['name'] }}</strong>
                                @if($p['phone'])
                                    <span class="text-muted" style="font-size: 7.5pt;">({{ $p['phone'] }})</span>
                                @endif
                            </td>
                            <td class="text-uppercase" style="font-size: 7.8pt;">{{ $p['type'] }}</td>
                            <td class="text-end font-monospace">
                                {{ $p['cash_paid'] > 0 ? ('Rs. ' . number_format($p['cash_paid'], 2)) : '-' }}
                            </td>
                            <td class="text-end font-monospace">
                                {{ $p['bank_paid'] > 0 ? ('Rs. ' . number_format($p['bank_paid'], 2)) : '-' }}
                            </td>
                            <td class="text-end font-monospace fw-bold text-danger">
                                - Rs. {{ number_format($p['total_paid'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-2">No party disbursements recorded for this period.</td>
                        </tr>
                    @endforelse

                    @if($totalShiftOperatingExpenses > 0)
                        <tr>
                            <td class="text-muted" style="font-size: 7.8pt;">{{ $idx++ }}</td>
                            <td><strong>Shift Counter Operating Expenses</strong></td>
                            <td class="text-uppercase" style="font-size: 7.8pt;">Expense</td>
                            <td class="text-end font-monospace">Rs. {{ number_format($totalShiftOperatingExpenses, 2) }}</td>
                            <td class="text-end font-monospace">-</td>
                            <td class="text-end font-monospace fw-bold text-danger">- Rs. {{ number_format($totalShiftOperatingExpenses, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr class="fw-bold bg-light">
                        <td colspan="5" class="text-uppercase text-end">Total Payments Disbursed (Debit):</td>
                        <td class="text-end font-monospace text-danger">- Rs. {{ number_format($totalPeriodDisbursements, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 6. TABLE: CURRENT AVAILABLE ACCOUNT BALANCES (CLOSING POSITION) -->
        <div class="mb-3">
            <div class="section-title">
                <span>Closing Treasury Position (Current Available Balances)</span>
                <span class="fw-normal text-muted" style="font-size: 7.5pt;">Real-time Liquid Funds</span>
            </div>
            <table class="table stmt-table">
                <thead>
                    <tr>
                        <th style="width: 45%;">Account / Safe Description</th>
                        <th style="width: 18%;">Account Type</th>
                        <th style="width: 17%;">Account Number</th>
                        <th class="text-end" style="width: 20%;">Current Available Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $acc)
                        <tr>
                            <td><strong>{{ $acc->name }}</strong></td>
                            <td class="text-uppercase" style="font-size: 7.8pt;">{{ $acc->type }}</td>
                            <td class="font-monospace text-muted" style="font-size: 8pt;">{{ $acc->account_number ?: 'Cash Vault / Safe' }}</td>
                            <td class="text-end font-monospace fw-bold">
                                Rs. {{ number_format($acc->current_balance, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold bg-dark text-white">
                        <td colspan="3" class="text-uppercase text-end">Grand Total Available Liquid Funds:</td>
                        <td class="text-end font-monospace">Rs. {{ number_format($totalLiveLiquidity, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 7. AUTHENTICATION SIGNATURES BLOCK -->
        <div class="statement-signatures pt-3 mt-3">
            <div class="row text-center w-100 m-0">
                <div class="col-4">
                    <div class="statement-sig-line">
                        <div class="fw-bold" style="font-size: 8pt;">Prepared By</div>
                        <div class="text-muted" style="font-size: 7.4pt;">{{ auth()->user()->name }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="statement-sig-line">
                        <div class="fw-bold" style="font-size: 8pt;">Supervisor Verified</div>
                        <div class="text-muted" style="font-size: 7.4pt;">Finance Incharge</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="statement-sig-line">
                        <div class="fw-bold" style="font-size: 8pt;">Authorized Approval</div>
                        <div class="text-muted" style="font-size: 7.4pt;">Director / Owner</div>
                    </div>
                </div>
            </div>
        </div>



    </div>

</div>
@endsection
