@extends('layouts.app')

@section('title', 'Day Closing')
@section('page_title', 'End-of-Day Financial Closing')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-calendar2-check me-1 text-primary"></i> {{ Carbon\Carbon::parse($selectedDate)->format('d M Y') }}
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-1 gap-sm-2 w-100 justify-content-start justify-content-sm-end">
        @if(!$existingDayClosing)
            <button type="button" class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm text-nowrap w-100 w-sm-auto" data-bs-toggle="modal" data-bs-target="#finalizeDayModal">
                <i class="bi bi-check2-circle me-1"></i> Finalize Day Closing
            </button>
        @else
            <span class="badge bg-success-subtle text-success border border-success p-2 small fw-semibold text-truncate flex-grow-1 flex-sm-grow-0">
                <i class="bi bi-check-all me-1"></i> Day Finalized by {{ $existingDayClosing->closer->name ?? 'User' }}
            </span>
            <a href="{{ route('day-closings.show', $existingDayClosing->id) }}" class="btn btn-outline-primary btn-sm rounded-3 text-nowrap flex-grow-1 flex-sm-grow-0">
                <i class="bi bi-printer me-1"></i> View Statement
            </a>
        @endif
    </div>
@endsection

@push('styles')
<style>
    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .kpi-formula-value {
        font-size: clamp(1.15rem, 3.5vw, 1.45rem);
        line-height: 1.25;
        word-break: break-word;
    }
</style>
@endpush

@section('content')
<!-- Date Selector Bar -->
<div class="card-custom p-3 mb-3 bg-white border">
    <form method="GET" action="{{ route('day-closings.index') }}" class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap w-100 w-md-auto">
            <span class="small fw-bold text-secondary text-uppercase" style="font-size: 0.75rem;">Select Closing Date:</span>
            <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}" style="max-width: 170px;">
            <button type="submit" class="btn btn-outline-primary btn-sm text-nowrap">
                <i class="bi bi-arrow-repeat me-1"></i> Load Summary
            </button>
        </div>
        <div class="small text-muted" style="font-size: 0.75rem;">
            <i class="bi bi-shield-check text-success me-1"></i> 
            Opening Cash is automatically carried forward from previous finalized day closing.
        </div>
    </form>
</div>

<!-- ==================== AUTOMATED PHYSICAL CASH EQUATION (STEP-BY-STEP MATH) ==================== -->
<div class="card-custom p-3 p-md-4 mb-3 bg-white border shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2">
        <div>
            <h6 class="fw-bold text-dark m-0">
                <i class="bi bi-calculator text-primary me-1"></i> Physical Drawer Cash Flow Equation
            </h6>
            <small class="text-muted" style="font-size: 0.76rem;">Reconciliation of physical register cash: Opening Balance (+) Cash Inflows (-) Cash Outflows = Closing Cash</small>
        </div>
        <span class="badge bg-light text-secondary border font-monospace">
            {{ Carbon\Carbon::parse($selectedDate)->format('l, d F Y') }}
        </span>
    </div>

    <div class="row g-3">
        <!-- 1. Opening Cash -->
        <div class="col-12 col-md-3">
            <div class="p-3 rounded-3 bg-light border h-100">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Step 1 &bull; Base</span>
                    <span class="badge bg-secondary text-white small px-2 py-0.5">Starting Balance</span>
                </div>
                <div class="text-secondary small fw-semibold">Opening Cash Balance</div>
                <div class="fs-4 fw-bold font-monospace text-dark mt-1">Rs. {{ number_format($autoOpeningCash, 2) }}</div>
                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Brought forward from yesterday</small>
            </div>
        </div>

        <!-- 2. Cash Inflows (+) -->
        <div class="col-12 col-md-3">
            <div class="p-3 rounded-3 bg-success-subtle border border-success h-100">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-success small fw-bold text-uppercase" style="font-size: 0.7rem;">Step 2 &bull; Inflow</span>
                    <span class="badge bg-success text-white small px-2 py-0.5"><i class="bi bi-plus-lg me-1"></i>PLUS</span>
                </div>
                <div class="text-success fw-bold small">Total Cash Received</div>
                <div class="fs-4 fw-bold font-monospace text-success mt-1">+Rs. {{ number_format($totalCashIn, 2) }}</div>
                <div class="mt-2 pt-1 border-top border-success-subtle small text-dark" style="font-size: 0.72rem;">
                    <div class="d-flex justify-content-between">
                        <span>Morning Shift Cash:</span>
                        <span class="font-monospace fw-semibold">+Rs. {{ number_format($morningCountedCash, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Evening Shift Cash:</span>
                        <span class="font-monospace fw-semibold">+Rs. {{ number_format($eveningCountedCash, 2) }}</span>
                    </div>
                    @if($directCashIn > 0)
                        <div class="d-flex justify-content-between">
                            <span>Direct Cash Receipts:</span>
                            <span class="font-monospace fw-semibold">+Rs. {{ number_format($directCashIn, 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. Direct Cash Outflows (-) -->
        <div class="col-12 col-md-3">
            <div class="p-3 rounded-3 bg-danger-subtle border border-danger h-100">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-danger small fw-bold text-uppercase" style="font-size: 0.7rem;">Step 3 &bull; Outflow</span>
                    <span class="badge bg-danger text-white small px-2 py-0.5"><i class="bi bi-dash-lg me-1"></i>MINUS</span>
                </div>
                <div class="text-danger fw-bold small">Direct Voucher Payments</div>
                <div class="fs-4 fw-bold font-monospace text-danger mt-1">-Rs. {{ number_format($directPaymentsOut, 2) }}</div>
                <div class="mt-2 pt-1 border-top border-danger-subtle small text-dark" style="font-size: 0.72rem;">
                    <div class="d-flex justify-content-between">
                        <span>Direct Vouchers Paid:</span>
                        <span class="font-monospace fw-semibold">-Rs. {{ number_format($directPaymentsOut, 2) }}</span>
                    </div>
                    <small class="text-muted d-block mt-1">Shift expenses settled directly from shift cash</small>
                </div>
            </div>
        </div>

        <!-- 4. Final Calculated Closing Cash (=) -->
        <div class="col-12 col-md-3">
            <div class="p-3 rounded-3 bg-primary text-white border border-primary h-100 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-white-50 small fw-bold text-uppercase" style="font-size: 0.7rem;">Step 4 &bull; Result</span>
                    <span class="badge bg-white text-primary small px-2 py-0.5 fw-bold">= EQUALS</span>
                </div>
                <div class="text-white fw-bold small">Final Physical Closing Cash</div>
                <div class="fs-4 fw-bold font-monospace text-white mt-1">Rs. {{ number_format($calculatedClosingCash, 2) }}</div>
                <div class="mt-2 pt-1 border-top border-white-50 small text-white-50" style="font-size: 0.72rem;">
                    <span>Remaining cash in drawer &bull; Automatically becomes tomorrow's Opening Cash</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== NEW SECTION: DAILY BANK & DIGITAL ACCOUNTS POSITION ==================== -->
<div class="card-custom p-3 p-md-4 mb-3 bg-white border shadow-sm">
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-info text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">
                <i class="bi bi-bank"></i>
            </span>
            <div>
                <h6 class="fw-bold text-dark m-0">Daily Bank & Digital Collections Breakdown</h6>
                <small class="text-muted" style="font-size: 0.76rem;">Activity recorded across bank accounts, mobile wallets (JazzCash), and digital payment channels</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-primary-subtle text-primary border border-primary px-3 py-1.5 small">
                <i class="bi bi-phone me-1"></i> Today's Digital Collections: <strong class="font-monospace ms-1">Rs. {{ number_format($totalDigitalIn, 2) }}</strong>
            </span>
            <span class="badge bg-success-subtle text-success border border-success px-3 py-1.5 small">
                <i class="bi bi-cash-stack me-1"></i> Total Combined Collections (Cash + Digital): <strong class="font-monospace ms-1">Rs. {{ number_format($totalCombinedInflow, 2) }}</strong>
            </span>
        </div>
    </div>

    <!-- Account Wise Breakdown Table -->
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 25%;">Account / Wallet Name</th>
                    <th style="width: 15%;">Account Type</th>
                    <th style="width: 15%;" class="text-end">Today's Inflow (+)</th>
                    <th style="width: 15%;" class="text-end">Today's Outflow (-)</th>
                    <th style="width: 15%;" class="text-end">Net Daily Movement</th>
                    <th style="width: 15%;" class="text-end">Current Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accountSummaries as $acc)
                    <tr>
                        <td class="fw-bold text-dark">
                            <i class="bi bi-{{ $acc->type === 'bank' ? 'building' : ($acc->type === 'jazzcash' ? 'phone' : 'wallet2') }} text-secondary me-1"></i>
                            {{ $acc->name }}
                            @if($acc->account_number)
                                <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;">Acc: {{ $acc->account_number }}</small>
                            @endif
                        </td>
                        <td>
                            @if($acc->type === 'bank')
                                <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-0.5 small">Bank Account</span>
                            @elseif($acc->type === 'jazzcash')
                                <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-0.5 small">JazzCash</span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success px-2 py-0.5 small">Cash Register</span>
                            @endif
                        </td>
                        <td class="text-end font-monospace fw-semibold {{ $acc->inflow > 0 ? 'text-success' : 'text-muted' }}">
                            {{ $acc->inflow > 0 ? '+Rs. ' . number_format($acc->inflow, 2) : '-' }}
                        </td>
                        <td class="text-end font-monospace fw-semibold {{ $acc->outflow > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ $acc->outflow > 0 ? '-Rs. ' . number_format($acc->outflow, 2) : '-' }}
                        </td>
                        <td class="text-end font-monospace fw-bold {{ $acc->net > 0 ? 'text-success' : ($acc->net < 0 ? 'text-danger' : 'text-muted') }}">
                            @if($acc->net > 0)
                                +Rs. {{ number_format($acc->net, 2) }}
                            @elseif($acc->net < 0)
                                -Rs. {{ number_format(abs($acc->net), 2) }}
                            @else
                                Rs. 0.00
                            @endif
                        </td>
                        <td class="text-end font-monospace fw-bold fs-6 text-dark">
                            Rs. {{ number_format($acc->current_balance, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-3 text-muted small">No accounts configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light border-top-2">
                <tr class="fw-bold">
                    <td colspan="2" class="text-dark">Total Accounts Turnover:</td>
                    <td class="text-end font-monospace text-success">+Rs. {{ number_format($accountSummaries->sum('inflow'), 2) }}</td>
                    <td class="text-end font-monospace text-danger">-Rs. {{ number_format($accountSummaries->sum('outflow'), 2) }}</td>
                    <td class="text-end font-monospace text-primary">
                        Rs. {{ number_format($accountSummaries->sum('inflow') - $accountSummaries->sum('outflow'), 2) }}
                    </td>
                    <td class="text-end font-monospace text-dark fs-6">
                        Rs. {{ number_format($accountSummaries->sum('current_balance'), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- ==================== NEW SECTION: DAILY PARTY & KHATA TRANSACTIONS ==================== -->
<div class="card-custom p-3 p-md-4 mb-3 bg-white border shadow-sm">
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">
                <i class="bi bi-people-fill"></i>
            </span>
            <div>
                <h6 class="fw-bold text-dark m-0">Daily Party & Khata Transactions (Inflows & Outflows)</h6>
                <small class="text-muted" style="font-size: 0.76rem;">All payments made to or received from suppliers, customers, and staff during shifts and direct vouchers</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-success-subtle text-success border border-success px-3 py-1.5 small">
                <i class="bi bi-arrow-down-left me-1"></i> Total Received In: <strong class="font-monospace ms-1">+Rs. {{ number_format($totalPartyInflow, 2) }}</strong>
            </span>
            <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-1.5 small">
                <i class="bi bi-arrow-up-right me-1"></i> Total Paid Out: <strong class="font-monospace ms-1">-Rs. {{ number_format($totalPartyOutflow, 2) }}</strong>
            </span>
            <span class="badge {{ $netPartyMovement >= 0 ? 'bg-primary-subtle text-primary border border-primary' : 'bg-warning-subtle text-warning-emphasis border border-warning' }} px-3 py-1.5 small">
                <i class="bi bi-cash-stack me-1"></i> Net Party Flow: <strong class="font-monospace ms-1">{{ $netPartyMovement >= 0 ? '+' : '-' }}Rs. {{ number_format(abs($netPartyMovement), 2) }}</strong>
            </span>
        </div>
    </div>

    <!-- Party Transactions Table -->
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 22%;">Party / Payee</th>
                    <th style="width: 14%;">Type</th>
                    <th style="width: 16%;">Shift / Timing</th>
                    <th style="width: 14%;">Channel / Mode</th>
                    <th style="width: 20%;">Detail / Reference</th>
                    <th style="width: 14%;" class="text-end">Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partyTransactions as $pt)
                    <tr>
                        <td class="fw-bold text-dark">
                            <i class="bi bi-person-circle text-secondary me-1"></i>
                            {{ $pt->party_name }}
                            <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.68rem;">{{ ucfirst($pt->party_type) }}</span>
                            @if($pt->party_phone)
                                <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;">{{ $pt->party_phone }}</small>
                            @endif
                        </td>
                        <td>
                            @if($pt->type === 'payment_in')
                                <span class="badge bg-success-subtle text-success border border-success px-2 py-0.5 small">
                                    <i class="bi bi-arrow-down-left me-1"></i>Payment In
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-0.5 small">
                                    <i class="bi bi-arrow-up-right me-1"></i>Payment Out
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($pt->shift_name)
                                @if(str_contains(strtolower($pt->shift_name), 'morning'))
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-0.5 small">
                                        <i class="bi bi-sun me-1"></i>Morning Shift
                                    </span>
                                @else
                                    <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-0.5 small">
                                        <i class="bi bi-moon-stars me-1"></i>Evening Shift
                                    </span>
                                @endif
                            @else
                                <span class="badge bg-light text-secondary border px-2 py-0.5 small">
                                    <i class="bi bi-dash me-1"></i>Direct (Non-Shift)
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="text-dark small fw-semibold">
                                <i class="bi bi-wallet2 text-muted me-1"></i>{{ $pt->channel }}
                            </span>
                        </td>
                        <td class="small text-muted">
                            {{ $pt->details }}
                        </td>
                        <td class="text-end font-monospace fw-bold fs-6 {{ $pt->type === 'payment_in' ? 'text-success' : 'text-danger' }}">
                            {{ $pt->type === 'payment_in' ? '+' : '-' }}Rs. {{ number_format($pt->amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted small">
                            <i class="bi bi-person-x fs-3 d-block text-secondary opacity-50 mb-1"></i>
                            No party or supplier payments recorded for this date.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($partyTransactions->isNotEmpty())
                <tfoot class="table-light border-top-2">
                    <tr class="fw-bold">
                        <td colspan="4" class="text-dark">Total Party Transactions ({{ $partyTransactions->count() }}):</td>
                        <td class="text-end small text-muted">In: +Rs. {{ number_format($totalPartyInflow, 2) }} &bull; Out: -Rs. {{ number_format($totalPartyOutflow, 2) }}</td>
                        <td class="text-end font-monospace fs-6 {{ $netPartyMovement >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $netPartyMovement >= 0 ? '+' : '-' }}Rs. {{ number_format(abs($netPartyMovement), 2) }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

<!-- ==================== SHIFT MERGING CARDS (MORNING & EVENING) ==================== -->
<div class="row g-2 g-md-3 mb-3">
    <!-- Morning Shift -->
    <div class="col-12 col-md-6">
        <div class="card-custom p-3 h-100 shadow-xs bg-white border">
            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1">
                        <i class="bi bi-sun me-1"></i> Morning Shift
                    </span>
                    <span class="fw-bold text-dark small">Performance Summary</span>
                </div>
                @if($morningShift)
                    <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.7rem;">
                        <i class="bi bi-check-circle me-1"></i> Recorded
                    </span>
                @else
                    <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">Not Submitted Yet</span>
                @endif
            </div>

            @if($morningShift)
                <div class="row g-2 small text-secondary">
                    <div class="col-6">Cashier Incharge: <strong class="text-dark">{{ $morningShift->cashier->name ?? 'N/A' }}</strong></div>
                    <div class="col-6 text-end">Invoices: <strong class="text-dark">{{ $morningShift->total_invoices }} Bills</strong>
                        @if($morningShift->invoice_start && $morningShift->invoice_end)
                            <small class="d-block text-muted font-monospace">#{{ $morningShift->invoice_start }} to #{{ $morningShift->invoice_end }}</small>
                        @endif
                    </div>
                    <div class="col-6">Gross Sales Billing: <strong class="text-dark font-monospace">Rs. {{ number_format($morningShift->total_sale, 2) }}</strong></div>
                    <div class="col-6 text-end">Physical Counted Cash: <strong class="text-success fw-bold font-monospace">Rs. {{ number_format($morningShift->total_counted_cash, 2) }}</strong></div>
                    <div class="col-6">Bank / Digital Transfer: <strong class="text-primary fw-semibold font-monospace">Rs. {{ number_format($morningShift->jazzcash_amount + $morningShift->bank_amount, 2) }}</strong></div>
                    <div class="col-6 text-end">Shift Operating Expenses: <strong class="text-danger fw-semibold font-monospace">Rs. {{ number_format($morningShift->expenses_amount, 2) }}</strong></div>
                    <div class="col-6">Party / Supplier Payouts: <strong class="text-danger fw-semibold font-monospace">Rs. {{ number_format($morningShift->partyPayments->sum('amount'), 2) }}</strong></div>
                    <div class="col-6 text-end">Shift Audit Status: {!! $morningShift->difference_badge !!}</div>
                    @if($morningShift->return_invoice_number || $morningShift->returns_amount > 0)
                        <div class="col-6">Sales Return Bill: <strong class="text-dark">{{ $morningShift->return_invoice_number ? '#' . $morningShift->return_invoice_number : '-' }}</strong></div>
                        <div class="col-6 text-end">Returned Amount: <strong class="text-danger fw-semibold font-monospace">Rs. {{ number_format($morningShift->returns_amount, 2) }}</strong></div>
                    @endif
                    @if($morningShift->expenses_details)
                        <div class="col-12 text-muted">Expense Note: {{ $morningShift->expenses_details }}</div>
                    @endif
                </div>
            @else
                <div class="text-center py-4 text-muted small">
                    Morning shift closing sheet has not been submitted for this date.
                </div>
            @endif
        </div>
    </div>

    <!-- Evening Shift -->
    <div class="col-12 col-md-6">
        <div class="card-custom p-3 h-100 shadow-xs bg-white border">
            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                        <i class="bi bi-moon-stars me-1"></i> Evening Shift
                    </span>
                    <span class="fw-bold text-dark small">Performance Summary</span>
                </div>
                @if($eveningShift)
                    <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.7rem;">
                        <i class="bi bi-check-circle me-1"></i> Recorded
                    </span>
                @else
                    <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">Not Submitted Yet</span>
                @endif
            </div>

            @if($eveningShift)
                <div class="row g-2 small text-secondary">
                    <div class="col-6">Cashier Incharge: <strong class="text-dark">{{ $eveningShift->cashier->name ?? 'N/A' }}</strong></div>
                    <div class="col-6 text-end">Invoices: <strong class="text-dark">{{ $eveningShift->total_invoices }} Bills</strong>
                        @if($eveningShift->invoice_start && $eveningShift->invoice_end)
                            <small class="d-block text-muted font-monospace">#{{ $eveningShift->invoice_start }} to #{{ $eveningShift->invoice_end }}</small>
                        @endif
                    </div>
                    <div class="col-6">Gross Sales Billing: <strong class="text-dark font-monospace">Rs. {{ number_format($eveningShift->total_sale, 2) }}</strong></div>
                    <div class="col-6 text-end">Physical Counted Cash: <strong class="text-success fw-bold font-monospace">Rs. {{ number_format($eveningShift->total_counted_cash, 2) }}</strong></div>
                    <div class="col-6">Bank / Digital Transfer: <strong class="text-primary fw-semibold font-monospace">Rs. {{ number_format($eveningShift->jazzcash_amount + $eveningShift->bank_amount, 2) }}</strong></div>
                    <div class="col-6 text-end">Shift Operating Expenses: <strong class="text-danger fw-semibold font-monospace">Rs. {{ number_format($eveningShift->expenses_amount, 2) }}</strong></div>
                    <div class="col-6">Party / Supplier Payouts: <strong class="text-danger fw-semibold font-monospace">Rs. {{ number_format($eveningShift->partyPayments->sum('amount'), 2) }}</strong></div>
                    <div class="col-6 text-end">Shift Audit Status: {!! $eveningShift->difference_badge !!}</div>
                    @if($eveningShift->return_invoice_number || $eveningShift->returns_amount > 0)
                        <div class="col-6">Sales Return Bill: <strong class="text-dark">{{ $eveningShift->return_invoice_number ? '#' . $eveningShift->return_invoice_number : '-' }}</strong></div>
                        <div class="col-6 text-end">Returned Amount: <strong class="text-danger fw-semibold font-monospace">Rs. {{ number_format($eveningShift->returns_amount, 2) }}</strong></div>
                    @endif
                    @if($eveningShift->expenses_details)
                        <div class="col-12 text-muted">Expense Note: {{ $eveningShift->expenses_details }}</div>
                    @endif
                </div>
            @else
                <div class="text-center py-4 text-muted small">
                    Evening shift closing sheet has not been submitted for this date.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- ==================== DAY CLOSINGS HISTORY TABLE ==================== -->
<div class="card-custom overflow-hidden bg-white border shadow-sm">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold text-dark m-0">Finalized Day Closings Register</h6>
            <small class="text-muted" style="font-size: 0.78rem;">Permanent financial log of opening cash, collections, payouts, and verified closing cash</small>
        </div>
    </div>

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Date</th>
                    <th class="text-end">Opening Cash</th>
                    <th class="text-end">Total Cash In</th>
                    <th class="text-end">Total Payments</th>
                    <th class="text-end">Final Closing Cash</th>
                    <th class="text-center">Total Variance</th>
                    <th class="text-center">Status</th>
                    <th>Finalized By</th>
                    <th class="text-end" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($history as $index => $day)
                    <tr>
                        <td class="text-muted small">{{ $history->firstItem() + $index }}</td>
                        <td class="fw-bold text-dark">{{ $day->date->format('d M Y') }}</td>
                        <td class="text-end small font-monospace">Rs. {{ number_format($day->opening_cash, 2) }}</td>
                        <td class="text-end small font-monospace text-success">+Rs. {{ number_format($day->total_cash_in, 2) }}</td>
                        <td class="text-end small font-monospace text-danger">-Rs. {{ number_format($day->total_payments_out, 2) }}</td>
                        <td class="text-end fw-bold fs-6 font-monospace text-primary">Rs. {{ number_format($day->closing_cash, 2) }}</td>
                        <td class="text-center">
                            @if($day->total_difference == 0)
                                <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.72rem;">Balanced</span>
                            @elseif($day->total_difference > 0)
                                <span class="badge bg-info-subtle text-info border border-info" style="font-size: 0.72rem;">+Rs. {{ number_format($day->total_difference, 2) }} (Surplus)</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger" style="font-size: 0.72rem;">-Rs. {{ number_format(abs($day->total_difference), 2) }} (Shortage)</span>
                            @endif
                        </td>
                        <td class="text-center">{!! $day->status_badge !!}</td>
                        <td class="small text-secondary"><i class="bi bi-person me-1"></i>{{ $day->closer->name ?? 'User #' . $day->closed_by }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('day-closings.show', $day->id) }}" class="btn btn-outline-primary btn-sm py-1 px-2" title="Print Statement">
                                    <i class="bi bi-printer"></i>
                                </a>
                                @if(auth()->user()->isOwner())
                                    @if($day->status === 'closed')
                                        <form action="{{ route('day-closings.reopen', $day->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Reopen this day closing for revisions?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-warning btn-sm py-1 px-2" title="Reopen Day">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('day-closings.destroy', $day->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this day closing record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted small">
                            <i class="bi bi-calendar-x fs-3 d-block text-secondary opacity-50 mb-2"></i>
                            No finalized day closings found. Click <strong>"Finalize Day Closing"</strong> above to close today's register balance.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile View: Clean Responsive Cards -->
    <div class="d-block d-md-none p-2 p-sm-3">
        <div class="d-flex flex-column gap-2">
            @forelse($history as $day)
                <div class="p-3 rounded-3 border bg-white shadow-xs">
                    <div class="d-flex align-items-center justify-content-between gap-1 mb-2">
                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                            <i class="bi bi-calendar-check text-primary me-1"></i>{{ $day->date->format('d M Y') }}
                        </div>
                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            {!! $day->status_badge !!}
                            @if($day->total_difference == 0)
                                <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.68rem;">Balanced</span>
                            @elseif($day->total_difference > 0)
                                <span class="badge bg-info-subtle text-info border border-info" style="font-size: 0.68rem;">+Rs. {{ number_format($day->total_difference, 0) }}</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger" style="font-size: 0.68rem;">-Rs. {{ number_format(abs($day->total_difference), 0) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-2 rounded bg-light border small font-monospace mb-2">
                        <div class="d-flex justify-content-between text-secondary">
                            <span>Opening Cash:</span>
                            <span>Rs. {{ number_format($day->opening_cash, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-success">
                            <span>(+) Cash In:</span>
                            <span>+Rs. {{ number_format($day->total_cash_in, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-danger">
                            <span>(-) Payments:</span>
                            <span>-Rs. {{ number_format($day->total_payments_out, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-primary fw-bold border-top pt-1 mt-1">
                            <span>(=) Closing Cash:</span>
                            <span>Rs. {{ number_format($day->closing_cash, 2) }}</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-1">
                        <small class="text-secondary"><i class="bi bi-person me-1"></i>{{ $day->closer->name ?? 'User' }}</small>
                        <div class="d-flex gap-1">
                            <a href="{{ route('day-closings.show', $day->id) }}" class="btn btn-outline-primary btn-sm py-1 px-2" style="font-size: 0.76rem;">
                                <i class="bi bi-printer me-1"></i> Statement
                            </a>
                            @if(auth()->user()->isOwner())
                                @if($day->status === 'closed')
                                    <form action="{{ route('day-closings.reopen', $day->id) }}" method="POST" class="m-0" onsubmit="return confirm('Reopen this day closing for revisions?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning btn-sm py-1 px-2" style="font-size: 0.76rem;">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('day-closings.destroy', $day->id) }}" method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to delete this day closing?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" style="font-size: 0.76rem;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-calendar-x fs-3 d-block text-secondary opacity-50 mb-2"></i>
                    No finalized day closings found. Click <strong>"Finalize Day Closing"</strong> above to close today's balance.
                </div>
            @endforelse
        </div>
    </div>

    @if($history->hasPages())
        <div class="p-3 border-top bg-light overflow-x-auto">
            {{ $history->links() }}
        </div>
    @endif
</div>

<!-- ==================== FINALIZE DAY CLOSING MODAL ==================== -->
<div class="modal fade" id="finalizeDayModal" tabindex="-1" aria-labelledby="finDayLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg text-start">
            <div class="modal-header border-bottom py-3" style="background-color: #0f172a; color: #ffffff;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar2-check-fill fs-5 text-primary"></i>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="finDayLabel">Finalize Day Closing: {{ Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</h6>
                        <small class="text-white-50" style="font-size: 0.75rem;">Consolidate shift records & lock physical opening cash for tomorrow</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('day-closings.store') }}" method="POST">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate }}">

                <div class="modal-body p-3 p-sm-4 text-start">
                    <!-- Step-by-Step Math Box -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between py-1 small">
                            <span class="text-secondary fw-semibold">Step 1 &bull; Opening Cash Balance:</span>
                            <span class="fw-bold text-dark font-monospace">Rs. {{ number_format($autoOpeningCash, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small text-success">
                            <span class="fw-semibold">(+) Step 2 &bull; Total Physical Cash Inflow:</span>
                            <span class="fw-bold font-monospace">+Rs. {{ number_format($totalCashIn, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small text-danger">
                            <span class="fw-semibold">(-) Step 3 &bull; Direct Voucher Payments Out:</span>
                            <span class="fw-bold font-monospace">-Rs. {{ number_format($directPaymentsOut, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-top mt-2 align-items-center">
                            <span class="fw-bold text-dark">(=) Step 4 &bull; Final Verified Closing Cash:</span>
                            <span class="fw-bold fs-5 text-primary font-monospace">Rs. {{ number_format($calculatedClosingCash, 2) }}</span>
                        </div>
                    </div>

                    <!-- Digital Collections Notice -->
                    <div class="p-2 rounded bg-info-subtle border border-info small text-dark mb-3">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        Total Digital & Bank Collections for today: <strong class="font-monospace text-primary">Rs. {{ number_format($totalDigitalIn, 2) }}</strong>
                    </div>

                    <input type="hidden" name="opening_cash" value="{{ $autoOpeningCash }}">
                    <input type="hidden" name="total_cash_in" value="{{ $totalCashIn }}">
                    <input type="hidden" name="total_payments_out" value="{{ $totalPaymentsOut }}">
                    <input type="hidden" name="closing_cash" value="{{ $calculatedClosingCash }}">
                    <input type="hidden" name="total_difference" value="{{ $totalDifference }}">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Consolidated Shift Audit Status:</label>
                        <div class="p-2 rounded bg-light border fw-semibold small font-monospace {{ $totalDifference < 0 ? 'text-danger' : ($totalDifference > 0 ? 'text-primary' : 'text-success') }}">
                            {{ $totalDifference >= 0 ? '+' : '' }}Rs. {{ number_format($totalDifference, 2) }} 
                            <span class="fw-normal text-muted">({{ $totalDifference == 0 ? 'Balanced' : ($totalDifference > 0 ? 'Cash Surplus' : 'Cash Shortage') }})</span>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Closing Remarks / Audit Notes</label>
                        <textarea name="remarks" class="form-control form-control-sm py-2" rows="3" placeholder="Optional audit handover notes or remarks..."></textarea>
                    </div>

                    <div class="small text-muted mt-2" style="font-size: 0.74rem;">
                        <i class="bi bi-shield-lock-fill text-warning me-1"></i> Once finalized, this closing cash will automatically become tomorrow's opening balance.
                    </div>
                </div>

                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Confirm & Finalize Day
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
