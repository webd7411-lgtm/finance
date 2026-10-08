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
    .day-equation-cards {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 12px !important;
        width: 100% !important;
    }
    .day-equation-col {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
    }
    @media (max-width: 991px) {
        .day-equation-cards {
            display: flex !important;
            flex-wrap: nowrap !important;
            overflow-x: auto !important;
            padding-bottom: 6px !important;
        }
        .day-equation-col {
            min-width: 210px !important;
            flex: 0 0 210px !important;
        }
    }
</style>
@endpush

@section('content')
<!-- Date Selector Bar -->
<div class="card-custom p-3 mb-3 bg-white border">
    <form method="GET" action="{{ route('day-closings.index') }}" class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
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

    <div class="day-equation-cards mb-3">
        <!-- 1. Total Opening Balance (All Accounts) -->
        <div class="day-equation-col">
            <div class="p-3 rounded-3 bg-light border h-100">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Step 1 &bull; Base</span>
                    <span class="badge bg-secondary text-white small px-2 py-0.5">All Accounts</span>
                </div>
                <div class="text-secondary small fw-semibold">Opening Balance</div>
                <div class="fs-4 fw-bold font-monospace text-dark mt-1">Rs. {{ number_format($totalOpeningAllAccounts, 2) }}</div>
                
                <div class="mt-2 pt-1 border-top small text-dark" style="font-size: 0.72rem;">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-wallet2 me-1"></i>Cash Drawer:</span>
                        <span class="font-monospace fw-semibold">Rs. {{ number_format($cashOpening, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-building me-1"></i>Bank Accounts:</span>
                        <span class="font-monospace fw-semibold">Rs. {{ number_format($bankOpening, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-phone me-1"></i>JazzCash / Wallets:</span>
                        <span class="font-monospace fw-semibold">Rs. {{ number_format($jazzcashOpening, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Inflows (+) -->
        <div class="day-equation-col">
            <div class="p-3 rounded-3 bg-success-subtle border border-success h-100">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-success small fw-bold text-uppercase" style="font-size: 0.7rem;">Step 2 &bull; Inflow</span>
                    <span class="badge bg-success text-white small px-2 py-0.5"><i class="bi bi-plus-lg me-1"></i>PLUS</span>
                </div>
                <div class="text-success fw-bold small">(+) Payments Received</div>
                <div class="fs-4 fw-bold font-monospace text-success mt-1">+Rs. {{ number_format($totalInAllAccounts, 2) }}</div>
                
                <div class="mt-2 pt-1 border-top border-success-subtle small text-dark" style="font-size: 0.72rem;">
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-wallet2 me-1"></i>Cash Received:</span>
                        <span class="font-monospace fw-semibold">+Rs. {{ number_format($cashIn, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-building me-1"></i>Bank Deposits:</span>
                        <span class="font-monospace fw-semibold">+Rs. {{ number_format($bankIn, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-phone me-1"></i>JazzCash In:</span>
                        <span class="font-monospace fw-semibold">+Rs. {{ number_format($jazzcashIn, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Total Outflows (-) -->
        <div class="day-equation-col">
            <div class="p-3 rounded-3 bg-danger-subtle border border-danger h-100">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-danger small fw-bold text-uppercase" style="font-size: 0.7rem;">Step 3 &bull; Outflow</span>
                    <span class="badge bg-danger text-white small px-2 py-0.5"><i class="bi bi-dash-lg me-1"></i>MINUS</span>
                </div>
                <div class="text-danger fw-bold small">(-) Payments Disbursed</div>
                <div class="fs-4 fw-bold font-monospace text-danger mt-1">-Rs. {{ number_format($totalOutAllAccounts, 2) }}</div>
                
                <div class="mt-2 pt-1 border-top border-danger-subtle small text-dark" style="font-size: 0.72rem;">
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-wallet2 me-1"></i>Cash Paid Out:</span>
                        <span class="font-monospace fw-semibold">-Rs. {{ number_format($cashOut, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-building me-1"></i>Bank Out:</span>
                        <span class="font-monospace fw-semibold">-Rs. {{ number_format($bankOut, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-phone me-1"></i>JazzCash Out:</span>
                        <span class="font-monospace fw-semibold">-Rs. {{ number_format($jazzcashOut, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Final Total Closing Balance (=) -->
        <div class="day-equation-col">
            <div class="p-3 rounded-3 bg-primary text-white border border-primary h-100 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-white-50 small fw-bold text-uppercase" style="font-size: 0.7rem;">Step 4 &bull; Result</span>
                    <span class="badge bg-white text-primary small px-2 py-0.5 fw-bold">= EQUALS</span>
                </div>
                <div class="text-white fw-bold small">Closing Balance</div>
                <div class="fs-4 fw-bold font-monospace text-white mt-1">Rs. {{ number_format($totalClosingAllAccounts, 2) }}</div>
                
                <div class="mt-2 pt-1 border-top border-white-50 small" style="font-size: 0.72rem;">
                    <div class="d-flex justify-content-between text-white">
                        <span><i class="bi bi-wallet2 me-1 text-white-50"></i>Cash in Drawer:</span>
                        <span class="font-monospace fw-semibold">Rs. {{ number_format($cashClosing, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-white">
                        <span><i class="bi bi-building me-1 text-white-50"></i>Bank Balance:</span>
                        <span class="font-monospace fw-semibold">Rs. {{ number_format($bankClosing, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-white">
                        <span><i class="bi bi-phone me-1 text-white-50"></i>JazzCash:</span>
                        <span class="font-monospace fw-semibold">Rs. {{ number_format($jazzcashClosing, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== SECTION: DAILY BANK & DIGITAL ACCOUNTS POSITION ==================== -->
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
                    <th style="width: 22%;">Account / Wallet Name</th>
                    <th style="width: 13%;">Account Type</th>
                    <th style="width: 13%;" class="text-end">Opening Balance</th>
                    <th style="width: 13%;" class="text-end">Today's Inflow (+)</th>
                    <th style="width: 13%;" class="text-end">Today's Outflow (-)</th>
                    <th style="width: 13%;" class="text-end">Net Daily Movement</th>
                    <th style="width: 13%;" class="text-end">Current Balance</th>
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
                        <td class="text-end font-monospace text-secondary">
                            Rs. {{ number_format($acc->opening_balance, 2) }}
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
                        <td colspan="7" class="text-center py-3 text-muted small">No accounts configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light border-top-2">
                <tr class="fw-bold">
                    <td colspan="2" class="text-dark">Total Accounts Turnover:</td>
                    <td class="text-end font-monospace text-secondary">Rs. {{ number_format($accountSummaries->sum('opening_balance'), 2) }}</td>
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

<!-- ==================== SECTION: DAILY TRANSACTIONS REGISTER (PAYMENTS IN & OUT) ==================== -->
<div class="card-custom p-3 p-md-4 mb-3 bg-white border shadow-sm">
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">
                <i class="bi bi-arrow-left-right"></i>
            </span>
            <div>
                <h6 class="fw-bold text-dark m-0">Daily Transactions Register (Payments In & Outflows)</h6>
                <small class="text-muted" style="font-size: 0.76rem;">All collections received and payments disbursed across all cash, bank, and digital channels</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-1.5 flex-wrap">
            <span class="badge bg-success-subtle text-success border border-success px-2.5 py-1 small">
                <i class="bi bi-arrow-down-left me-1"></i> Inflow (+): <strong class="font-monospace ms-1">+Rs. {{ number_format($totalPartyInflow, 2) }}</strong>
            </span>
            <span class="badge bg-danger-subtle text-danger border border-danger px-2.5 py-1 small">
                <i class="bi bi-arrow-up-right me-1"></i> Outflow (-): <strong class="font-monospace ms-1">-Rs. {{ number_format($totalPartyOutflow, 2) }}</strong>
            </span>
            <span class="badge {{ $netPartyMovement >= 0 ? 'bg-primary-subtle text-primary border border-primary' : 'bg-warning-subtle text-warning-emphasis border border-warning' }} px-2.5 py-1 small">
                <i class="bi bi-cash-stack me-1"></i> Net Flow: <strong class="font-monospace ms-1">{{ $netPartyMovement >= 0 ? '+' : '-' }}Rs. {{ number_format(abs($netPartyMovement), 2) }}</strong>
            </span>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 22%;">Party / Payee / Category</th>
                    <th style="width: 14%;">Type</th>
                    <th style="width: 16%;">Shift / Timing</th>
                    <th style="width: 14%;">Channel / Account</th>
                    <th style="width: 20%;">Detail / Reference</th>
                    <th style="width: 14%;" class="text-end">Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partyTransactions as $pt)
                    <tr>
                        <td class="fw-bold text-dark">
                            <i class="bi bi-{{ isset($pt->party_type) && str_contains(strtolower($pt->party_type), 'return') ? 'arrow-return-left text-danger' : 'person-circle text-secondary' }} me-1"></i>
                            {{ $pt->party_name }}
                            @if(isset($pt->party_type) && $pt->party_type !== 'Direct Voucher')
                                <span class="badge {{ str_contains(strtolower($pt->party_type), 'return') ? 'bg-danger-subtle text-danger border border-danger' : 'bg-light text-secondary border' }} ms-1" style="font-size: 0.68rem;">{{ ucfirst($pt->party_type) }}</span>
                            @endif
                            @if(isset($pt->party_phone) && $pt->party_phone)
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
                                <i class="bi bi-{{ isset($pt->channel_type) && $pt->channel_type === 'bank' ? 'building' : (isset($pt->channel_type) && $pt->channel_type === 'jazzcash' ? 'phone' : 'wallet2') }} text-muted me-1"></i>
                                {{ $pt->channel }}
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
                            <i class="bi bi-receipt fs-3 d-block text-secondary opacity-50 mb-1"></i>
                            No payments in or out recorded for this date.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($partyTransactions->isNotEmpty())
                <tfoot class="table-light border-top-2">
                    <tr class="fw-bold">
                        <td colspan="5" class="text-dark">Total Daily Transactions Turnover:</td>
                        <td class="text-end font-monospace {{ $netPartyMovement >= 0 ? 'text-success' : 'text-danger' }} fs-6">
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
                    @if($morningShift->return_invoice_start || $morningShift->return_invoice_number || $morningShift->returns_amount > 0)
                        <div class="col-6">Sales Return Bill: 
                            <strong class="text-dark">
                                @if($morningShift->return_invoice_start && $morningShift->return_invoice_end)
                                    #{{ $morningShift->return_invoice_start }} to #{{ $morningShift->return_invoice_end }}
                                    @if($morningShift->total_return_invoices > 0)
                                        ({{ $morningShift->total_return_invoices }} bills)
                                    @endif
                                @elseif($morningShift->return_invoice_number)
                                    #{{ $morningShift->return_invoice_number }}
                                @else
                                    -
                                @endif
                            </strong>
                        </div>
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
                    @if($eveningShift->return_invoice_start || $eveningShift->return_invoice_number || $eveningShift->returns_amount > 0)
                        <div class="col-6">Sales Return Bill: 
                            <strong class="text-dark">
                                @if($eveningShift->return_invoice_start && $eveningShift->return_invoice_end)
                                    #{{ $eveningShift->return_invoice_start }} to #{{ $eveningShift->return_invoice_end }}
                                    @if($eveningShift->total_return_invoices > 0)
                                        ({{ $eveningShift->total_return_invoices }} bills)
                                    @endif
                                @elseif($eveningShift->return_invoice_number)
                                    #{{ $eveningShift->return_invoice_number }}
                                @else
                                    -
                                @endif
                            </strong>
                        </div>
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
                    <th class="text-end">Opening Balance</th>
                    <th class="text-end">Total Inflows</th>
                    <th class="text-end">Total Outflows</th>
                    <th class="text-end">Closing Balance</th>
                    <th class="text-center">Shift Variance</th>
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
                        <td class="text-end small font-monospace">
                            <span class="d-block text-dark fw-semibold">Rs. {{ number_format($day->total_opening_all_accounts > 0 ? $day->total_opening_all_accounts : $day->opening_cash, 2) }}</span>
                            @if($day->total_opening_all_accounts > 0 && $day->total_opening_all_accounts != $day->opening_cash)
                                <small class="text-muted" style="font-size: 0.7rem;">Drawer: Rs. {{ number_format($day->opening_cash, 2) }}</small>
                            @endif
                        </td>
                        <td class="text-end small font-monospace text-success">+Rs. {{ number_format($day->total_in_all_accounts > 0 ? $day->total_in_all_accounts : $day->total_cash_in, 2) }}</td>
                        <td class="text-end small font-monospace text-danger">-Rs. {{ number_format($day->total_out_all_accounts > 0 ? $day->total_out_all_accounts : $day->total_payments_out, 2) }}</td>
                        <td class="text-end fw-bold font-monospace text-primary">
                            <span class="d-block fs-6">Rs. {{ number_format($day->total_closing_all_accounts > 0 ? $day->total_closing_all_accounts : $day->closing_cash, 2) }}</span>
                            @if($day->total_closing_all_accounts > 0 && $day->total_closing_all_accounts != $day->closing_cash)
                                <small class="text-muted fw-normal" style="font-size: 0.7rem;">Drawer: Rs. {{ number_format($day->closing_cash, 2) }}</small>
                            @endif
                        </td>
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
                            <span>Opening (Total):</span>
                            <span>Rs. {{ number_format($day->total_opening_all_accounts > 0 ? $day->total_opening_all_accounts : $day->opening_cash, 2) }}</span>
                        </div>
                        @if($day->total_opening_all_accounts > 0 && $day->total_opening_all_accounts != $day->opening_cash)
                            <div class="d-flex justify-content-between text-muted" style="font-size: 0.72rem;">
                                <span>&bull; Cash Drawer:</span>
                                <span>Rs. {{ number_format($day->opening_cash, 2) }}</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between text-success">
                            <span>(+) Total In:</span>
                            <span>+Rs. {{ number_format($day->total_in_all_accounts > 0 ? $day->total_in_all_accounts : $day->total_cash_in, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-danger">
                            <span>(-) Total Out:</span>
                            <span>-Rs. {{ number_format($day->total_out_all_accounts > 0 ? $day->total_out_all_accounts : $day->total_payments_out, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-primary fw-bold border-top pt-1 mt-1">
                            <span>(=) Closing (Total):</span>
                            <span>Rs. {{ number_format($day->total_closing_all_accounts > 0 ? $day->total_closing_all_accounts : $day->closing_cash, 2) }}</span>
                        </div>
                        @if($day->total_closing_all_accounts > 0 && $day->total_closing_all_accounts != $day->closing_cash)
                            <div class="d-flex justify-content-between text-dark fw-semibold" style="font-size: 0.74rem;">
                                <span>&bull; Cash Drawer:</span>
                                <span>Rs. {{ number_format($day->closing_cash, 2) }}</span>
                            </div>
                        @endif
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
                    <!-- Step-by-Step Math Box (Matches 4 Performance Cards Above) -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <!-- Step 1: Total Starting Balance (All Accounts) -->
                        <div class="d-flex justify-content-between align-items-center py-1 small">
                            <div>
                                <span class="text-secondary fw-semibold d-block">Step 1 &bull; Total Starting Balance:</span>
                                <small class="text-muted" style="font-size: 0.72rem;">Cash Drawer: Rs. {{ number_format($cashOpening, 2) }} | Bank: Rs. {{ number_format($bankOpening, 2) }}</small>
                            </div>
                            <span class="fw-bold text-dark font-monospace text-nowrap fs-6">Rs. {{ number_format($totalOpeningAllAccounts, 2) }}</span>
                        </div>

                        <!-- Step 2: Total Inflows -->
                        <div class="d-flex justify-content-between align-items-center py-1 small text-success border-top pt-2 mt-1">
                            <div>
                                <span class="fw-semibold d-block">(+) Step 2 &bull; Total Payments Received:</span>
                                <small class="text-muted" style="font-size: 0.72rem;">
                                    Cash In: +Rs. {{ number_format($cashIn, 2) }} | Bank In: +Rs. {{ number_format($bankIn + $jazzcashIn, 2) }}
                                </small>
                            </div>
                            <span class="fw-bold font-monospace text-nowrap">+Rs. {{ number_format($totalInAllAccounts, 2) }}</span>
                        </div>

                        <!-- Step 3: Total Outflows -->
                        <div class="d-flex justify-content-between align-items-center py-1 small text-danger border-top pt-2 mt-1">
                            <div>
                                <span class="fw-semibold d-block">(-) Step 3 &bull; Total Payments Disbursed:</span>
                                <small class="text-muted" style="font-size: 0.72rem;">
                                    Cash Out: -Rs. {{ number_format($cashOut, 2) }} | Bank Out: -Rs. {{ number_format($bankOut + $jazzcashOut, 2) }}
                                </small>
                            </div>
                            <span class="fw-bold font-monospace text-nowrap">-Rs. {{ number_format($totalOutAllAccounts, 2) }}</span>
                        </div>

                        <!-- Step 4: Total Business Closing Balance -->
                        <div class="d-flex justify-content-between align-items-center py-2 border-top mt-2 bg-white px-2 rounded border">
                            <div>
                                <span class="fw-bold text-dark d-block">(=) Step 4 &bull; Total Business Closing:</span>
                                <small class="text-muted" style="font-size: 0.72rem;">Total Business Capital (Cash + Bank)</small>
                            </div>
                            <span class="fw-bold fs-5 text-primary font-monospace text-nowrap ms-2">Rs. {{ number_format($totalClosingAllAccounts, 2) }}</span>
                        </div>
                    </div>

                    <!-- Physical Register Drawer Reconciliation Box -->
                    <div class="p-2.5 rounded-3 bg-white border border-primary-subtle shadow-xs mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary p-1.5 rounded-circle">
                                    <i class="bi bi-wallet2 fs-6"></i>
                                </span>
                                <div>
                                    <span class="fw-bold text-dark small d-block">Physical Cash in Register Drawer:</span>
                                    <small class="text-muted" style="font-size: 0.72rem;">Locks tomorrow's physical cash opening balance</small>
                                </div>
                            </div>
                            <span class="fw-bold fs-6 text-success font-monospace text-nowrap ms-2">Rs. {{ number_format($cashClosing, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top small text-muted" style="font-size: 0.74rem;">
                            <span><i class="bi bi-building me-1"></i>Bank & Digital Wallets Balance:</span>
                            <span class="font-monospace fw-semibold text-dark">Rs. {{ number_format($bankClosing + $jazzcashClosing, 2) }}</span>
                        </div>
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
