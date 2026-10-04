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
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 pb-md-4 mb-3 mb-md-4 flex-wrap gap-2">
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
        <div class="text-start text-md-end w-100 w-md-auto">
            <span class="badge bg-light text-dark border px-2 py-1 mb-1 font-monospace d-inline-block">
                Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }} ({{ $diffDays }} Days)
            </span>
            <div class="small text-muted" style="font-size: 0.72rem;">Generated: {{ now()->format('d M, Y - h:i A') }}</div>
            <div class="small text-muted" style="font-size: 0.72rem;">Audited by: <strong>{{ auth()->user()->name }}</strong> ({{ ucfirst(auth()->user()->role) }})</div>
        </div>
    </div>

    <!-- Executive Metrics Cards -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
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
        <div class="mb-4">
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
        <div class="mb-4">
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
            <div class="d-none d-md-block table-responsive border rounded-3 mb-4">
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
            <div class="d-block d-md-none mb-4">
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
            <div class="d-none d-md-block table-responsive border rounded-3 mb-4">
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
            <div class="d-block d-md-none mb-4">
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
        <div class="text-start text-md-end w-100 w-md-auto">
            <h4 class="fw-bold font-monospace text-danger m-0" style="font-size: clamp(1.2rem, 4vw, 1.6rem);">
                Rs. {{ number_format($grandTotalAmount, 2) }}
            </h4>
            <small class="text-muted">{{ $grandTotalCount }} total expense items</small>
        </div>
    </div>

    <!-- Printable Audit Signatures (visible on print) -->
    <div class="row pt-5 mt-4 text-center d-none d-print-flex">
        <div class="col-4">
            <div class="border-top pt-2 small fw-semibold text-secondary">
                Prepared By (Accountant)
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2 small fw-semibold text-secondary">
                Verified By (Branch Incharge)
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2 small fw-semibold text-secondary">
                Approved By (Proprietor / Owner)
            </div>
        </div>
    </div>
</div>
@endsection
