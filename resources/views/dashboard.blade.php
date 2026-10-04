@extends('layouts.app')

@section('title', 'Executive Financial Dashboard - FinanceDesk')
@section('page_title', 'Executive Financial Dashboard')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-clock me-1 text-primary"></i> Live Financial Position
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-1 gap-sm-2 w-100 justify-content-start justify-content-sm-end">
        <a href="{{ route('shift-closings.index') }}" class="btn btn-primary btn-sm rounded-3 fw-semibold shadow-sm flex-fill flex-sm-grow-0 text-nowrap px-2 px-sm-3 btn-action-mobile">
            <i class="bi bi-plus-circle me-1"></i> Shift Closing
        </a>
        <a href="{{ route('transactions.index') }}" class="btn btn-dark btn-sm rounded-3 fw-semibold flex-fill flex-sm-grow-0 text-nowrap px-2 px-sm-3 btn-action-mobile">
            <i class="bi bi-arrow-left-right me-1"></i> Voucher
        </a>
        <a href="{{ route('day-closings.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold flex-fill flex-sm-grow-0 text-nowrap px-2 px-sm-3 btn-action-mobile">
            <i class="bi bi-calendar2-check me-1"></i> Day Closing
        </a>
    </div>
@endsection

@push('styles')
<style>
    /* Full mobile responsive rules - strictly zero horizontal scroll */
    .dashboard-kpi-card {
        padding: 0.95rem;
    }
    .kpi-value-text {
        font-size: clamp(1.2rem, 4.5vw, 1.7rem);
        line-height: 1.25;
        word-break: break-word;
    }
    .chart-box-wrapper {
        position: relative;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }
    @media (max-width: 767.98px) {
        .card-custom {
            padding: 0.85rem !important;
            border-radius: 10px;
        }
        .btn-action-mobile {
            font-size: 0.78rem !important;
            padding: 0.38rem 0.6rem !important;
        }
    }
    @media (max-width: 575.98px) {
        .dashboard-kpi-card {
            padding: 0.8rem !important;
        }
    }
</style>
@endpush

@section('content')
<!-- Top VIP Executive KPI Cards -->
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <!-- Card 1: Total Liquid Assets -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-4 border-primary shadow-sm position-relative overflow-hidden">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Total Liquid Assets</span>
                <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 36px; height: 36px;">
                    <i class="bi bi-bank2 fs-5"></i>
                </div>
            </div>
            <div class="fw-bold text-dark mb-1 font-monospace kpi-value-text">
                Rs. {{ number_format($totalLiquidFunds, 2) }}
            </div>
            <div class="d-flex align-items-center justify-content-between text-muted flex-wrap gap-1" style="font-size: 0.74rem;">
                <span>Vault: <strong class="text-dark">Rs. {{ number_format($cashInVault, 0) }}</strong></span>
                <span>Banks: <strong class="text-dark">Rs. {{ number_format($bankFunds, 0) }}</strong></span>
            </div>
        </div>
    </div>

    <!-- Card 2: Today's Total Inflow -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-4 border-success shadow-sm position-relative overflow-hidden">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-success small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Today's Collections (In)</span>
                <div class="bg-success text-white rounded-3 p-2 d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 36px; height: 36px;">
                    <i class="bi bi-arrow-down-left-circle fs-5"></i>
                </div>
            </div>
            <div class="fw-bold text-success mb-1 font-monospace kpi-value-text">
                + Rs. {{ number_format($todayInflow, 2) }}
            </div>
            <div class="text-muted small text-truncate" style="font-size: 0.74rem;">
                <i class="bi bi-check-circle-fill text-success me-1"></i>Shift cash sales + customer receipts
            </div>
        </div>
    </div>

    <!-- Card 3: Today's Total Outflow -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-4 border-danger shadow-sm position-relative overflow-hidden">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-danger small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Today's Disbursements (Out)</span>
                <div class="bg-danger text-white rounded-3 p-2 d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 36px; height: 36px;">
                    <i class="bi bi-arrow-up-right-circle fs-5"></i>
                </div>
            </div>
            <div class="fw-bold text-danger mb-1 font-monospace kpi-value-text">
                - Rs. {{ number_format($todayOutflow, 2) }}
            </div>
            <div class="text-muted small text-truncate" style="font-size: 0.74rem;">
                <i class="bi bi-dash-circle-fill text-danger me-1"></i>Shift expenses + supplier & staff payments
            </div>
        </div>
    </div>

    <!-- Card 4: Shift Reconciliation & Discrepancy -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom dashboard-kpi-card h-100 border-start border-4 {{ $todayVariance < 0 ? 'border-danger' : ($todayVariance > 0 ? 'border-primary' : 'border-indigo') }} shadow-sm position-relative overflow-hidden" style="border-left-color: #6366f1 !important;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Today's Shift Variance</span>
                <div class="rounded-3 p-2 d-flex align-items-center justify-content-center text-white shadow-sm flex-shrink-0" style="width: 36px; height: 36px; background: #4f46e5;">
                    <i class="bi bi-shield-check fs-5"></i>
                </div>
            </div>
            <div class="fw-bold mb-1 font-monospace kpi-value-text {{ $todayVariance < 0 ? 'text-danger' : ($todayVariance > 0 ? 'text-primary' : 'text-dark') }}">
                {{ $todayVariance >= 0 ? '+' : '' }}Rs. {{ number_format($todayVariance, 2) }}
            </div>
            <div class="d-flex align-items-center justify-content-between text-muted flex-wrap gap-1" style="font-size: 0.74rem;">
                <span>
                    @if($todayVariance < 0)
                        <span class="badge bg-danger-subtle text-danger border border-danger">Cash Shortage</span>
                    @elseif($todayVariance > 0)
                        <span class="badge bg-primary-subtle text-primary border border-primary">Cash Surplus</span>
                    @else
                        <span class="badge bg-success-subtle text-success border border-success">Balanced (Zero Diff)</span>
                    @endif
                </span>
                <span class="text-muted">
                    {{ $todayClosing ? 'Day Finalized' : 'Day Open' }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Financial Charts Row -->
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <!-- Chart 1: 7-Day Inflow vs Outflow Performance -->
    <div class="col-12 col-xl-8">
        <div class="card-custom p-3 p-md-4 h-100 shadow-sm overflow-hidden">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-graph-up-arrow text-primary me-2"></i>7-Day Cash Movement Trend
                    </h6>
                    <small class="text-muted" style="font-size: 0.75rem;">Daily collections (In) versus disbursements (Out)</small>
                </div>
                <div class="d-flex align-items-center gap-2 gap-sm-3 small fw-semibold">
                    <span class="d-flex align-items-center gap-1 text-success" style="font-size: 0.78rem;">
                        <span style="display:inline-block; width:9px; height:9px; border-radius:50%; background:#10b981;"></span> In
                    </span>
                    <span class="d-flex align-items-center gap-1 text-danger" style="font-size: 0.78rem;">
                        <span style="display:inline-block; width:9px; height:9px; border-radius:50%; background:#ef4444;"></span> Out
                    </span>
                </div>
            </div>
            <div class="chart-box-wrapper" style="height: 250px;">
                <canvas id="cashFlowChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: Account Liquidity Breakdown -->
    <div class="col-12 col-xl-4">
        <div class="card-custom p-3 p-md-4 h-100 shadow-sm overflow-hidden">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-pie-chart text-primary me-2"></i>Fund Allocation
                    </h6>
                    <small class="text-muted" style="font-size: 0.75rem;">Share across physical vault and banks</small>
                </div>
                <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-2" style="font-size: 0.72rem;">
                    Accounts
                </a>
            </div>
            <div class="chart-box-wrapper" style="height: 190px;">
                <canvas id="liquidityDonutChart"></canvas>
            </div>
            <div class="row g-2 mt-2 pt-2 border-top text-center" style="font-size: 0.75rem;">
                <div class="col-6 border-end">
                    <span class="text-muted d-block" style="font-size: 0.7rem;">Party Receivables</span>
                    <strong class="text-primary fw-bold font-monospace">Rs. {{ number_format($totalReceivables, 0) }}</strong>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block" style="font-size: 0.7rem;">Party Payables</span>
                    <strong class="text-danger fw-bold font-monospace">Rs. {{ number_format($totalPayables, 0) }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Middle Section: Today's Shift Status & Liquid Accounts -->
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <!-- Left: Today's Morning & Evening Shift Cards -->
    <div class="col-12 col-lg-6">
        <div class="card-custom p-3 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark m-0">
                    <i class="bi bi-clock-history text-primary me-2"></i>Today's Shift Registers ({{ date('d M, Y') }})
                </h6>
                <a href="{{ route('shift-closings.index') }}" class="btn btn-light btn-sm rounded-2 py-0 px-2" style="font-size: 0.74rem;">
                    All Shifts <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="row g-2 g-sm-3">
                <!-- Morning Shift -->
                <div class="col-12 col-sm-6">
                    <div class="p-3 rounded-3 border {{ $morningShift ? 'bg-white' : 'bg-light' }} h-100 shadow-xs">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1">Morning Shift</span>
                            @if($morningShift)
                                {!! $morningShift->status_badge !!}
                            @else
                                <span class="badge bg-light text-muted border">Pending</span>
                            @endif
                        </div>
                        @if($morningShift)
                            <div class="small text-muted mb-1 text-truncate">Cashier: <strong>{{ $morningShift->cashier->name ?? 'User #' . $morningShift->cashier_id }}</strong></div>
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span class="text-muted">Gross Sale:</span>
                                <span class="fw-bold text-dark font-monospace">Rs. {{ number_format($morningShift->total_sale, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span class="text-muted">Physical Cash:</span>
                                <span class="fw-bold text-success font-monospace">Rs. {{ number_format($morningShift->total_counted_cash, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small pt-1">
                                <span class="text-muted">Variance:</span>
                                <span class="fw-bold font-monospace {{ $morningShift->difference < 0 ? 'text-danger' : 'text-success' }}">
                                    Rs. {{ number_format($morningShift->difference, 0) }}
                                </span>
                            </div>
                            <div class="mt-2 text-end">
                                <a href="{{ route('shift-closings.show', $morningShift->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.72rem;">
                                    View Sheet
                                </a>
                            </div>
                        @else
                            <div class="text-center py-3 text-muted">
                                <i class="bi bi-hourglass-split fs-4 d-block mb-1 text-warning"></i>
                                <span class="small">Morning register not submitted yet.</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Evening Shift -->
                <div class="col-12 col-sm-6">
                    <div class="p-3 rounded-3 border {{ $eveningShift ? 'bg-white' : 'bg-light' }} h-100 shadow-xs">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-indigo-subtle border px-2 py-1" style="background:#ede9fe; color:#4338ca;">Evening Shift</span>
                            @if($eveningShift)
                                {!! $eveningShift->status_badge !!}
                            @else
                                <span class="badge bg-light text-muted border">Pending</span>
                            @endif
                        </div>
                        @if($eveningShift)
                            <div class="small text-muted mb-1 text-truncate">Cashier: <strong>{{ $eveningShift->cashier->name ?? 'User #' . $eveningShift->cashier_id }}</strong></div>
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span class="text-muted">Gross Sale:</span>
                                <span class="fw-bold text-dark font-monospace">Rs. {{ number_format($eveningShift->total_sale, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span class="text-muted">Physical Cash:</span>
                                <span class="fw-bold text-success font-monospace">Rs. {{ number_format($eveningShift->total_counted_cash, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small pt-1">
                                <span class="text-muted">Variance:</span>
                                <span class="fw-bold font-monospace {{ $eveningShift->difference < 0 ? 'text-danger' : 'text-success' }}">
                                    Rs. {{ number_format($eveningShift->difference, 0) }}
                                </span>
                            </div>
                            <div class="mt-2 text-end">
                                <a href="{{ route('shift-closings.show', $eveningShift->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.72rem;">
                                    View Sheet
                                </a>
                            </div>
                        @else
                            <div class="text-center py-3 text-muted">
                                <i class="bi bi-hourglass-split fs-4 d-block mb-1 text-indigo" style="color: #6366f1;"></i>
                                <span class="small">Evening register not submitted yet.</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Liquid Balances & Accounts Grid -->
    <div class="col-12 col-lg-6">
        <div class="card-custom p-3 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark m-0">
                    <i class="bi bi-wallet2 text-success me-2"></i>Accounts & Payment Channels
                </h6>
                <a href="{{ route('accounts.index') }}" class="btn btn-light btn-sm rounded-2 py-0 px-2" style="font-size: 0.74rem;">
                    Manage <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Desktop View: Standard Table -->
            <div class="d-none d-md-block table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th class="text-end">Balance (Rs.)</th>
                            <th class="text-center" style="width: 70px;">Ledger</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($accounts as $acc)
                            <tr>
                                <td class="fw-bold text-dark">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($acc->type === 'cash')
                                            <i class="bi bi-cash-stack text-success"></i>
                                        @elseif($acc->type === 'jazzcash')
                                            <i class="bi bi-phone text-danger"></i>
                                        @else
                                            <i class="bi bi-bank text-primary"></i>
                                        @endif
                                        <span>{{ $acc->name }}</span>
                                    </div>
                                    @if($acc->account_number)
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">{{ $acc->account_number }}</small>
                                    @endif
                                </td>
                                <td>{!! $acc->type_badge !!}</td>
                                <td class="text-end font-monospace fw-bold {{ $acc->current_balance < 0 ? 'text-danger' : 'text-dark' }}">
                                    Rs. {{ number_format($acc->current_balance, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($acc->type === 'cash')
                                        <a href="{{ route('ledgers.cash-book', ['account_id' => $acc->id]) }}" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-2" title="View Cash Book">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('ledgers.bank-book', ['account_id' => $acc->id]) }}" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-2" title="View Bank Book">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile View: Clean Account Cards (Strictly 0 Horizontal Scroll) -->
            <div class="d-block d-md-none">
                <div class="d-flex flex-column gap-2">
                    @foreach($accounts as $acc)
                        <div class="p-2.5 rounded-3 border bg-light bg-opacity-50">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1 overflow-hidden">
                                    @if($acc->type === 'cash')
                                        <div class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-cash-stack"></i>
                                        </div>
                                    @elseif($acc->type === 'jazzcash')
                                        <div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-phone"></i>
                                        </div>
                                    @else
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-bank"></i>
                                        </div>
                                    @endif
                                    <div class="overflow-hidden min-w-0">
                                        <div class="fw-bold text-dark small text-truncate">{{ $acc->name }}</div>
                                        <div class="d-flex align-items-center gap-1 mt-0.5">
                                            {!! $acc->type_badge !!}
                                            @if($acc->account_number)
                                                <small class="text-muted font-monospace" style="font-size: 0.68rem;">{{ $acc->account_number }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="font-monospace fw-bold small {{ $acc->current_balance < 0 ? 'text-danger' : 'text-dark' }}">
                                        Rs. {{ number_format($acc->current_balance, 2) }}
                                    </div>
                                    <div class="mt-1">
                                        @if($acc->type === 'cash')
                                            <a href="{{ route('ledgers.cash-book', ['account_id' => $acc->id]) }}" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-2" style="font-size: 0.7rem;">
                                                <i class="bi bi-journal-text me-1"></i>Ledger
                                            </a>
                                        @else
                                            <a href="{{ route('ledgers.bank-book', ['account_id' => $acc->id]) }}" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-2" style="font-size: 0.7rem;">
                                                <i class="bi bi-journal-text me-1"></i>Ledger
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Section: Recent Vouchers & Activity Audit Feed -->
<div class="row g-2 g-md-3">
    <!-- Left: Recent Vouchers (Payments In & Out) -->
    <div class="col-12 col-xl-7">
        <div class="card-custom p-3 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark m-0">
                    <i class="bi bi-arrow-left-right text-primary me-2"></i>Recent Financial Vouchers
                </h6>
                <a href="{{ route('transactions.index') }}" class="btn btn-light btn-sm rounded-2 py-0 px-2" style="font-size: 0.74rem;">
                    All Vouchers <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Desktop View: Standard Table -->
            <div class="d-none d-md-block table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Ref #</th>
                            <th style="width: 110px;">Type</th>
                            <th>Party / Payee</th>
                            <th>Account</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center" style="width: 50px;">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $tx)
                            <tr>
                                <td class="font-monospace fw-bold text-muted">#{{ str_pad($tx->id, 5, '0', STR_PAD_LEFT) }}</td>
                                <td>{!! $tx->type_badge !!}</td>
                                <td class="fw-semibold text-dark">
                                    {{ $tx->party->name ?? 'Direct Counter' }}
                                    @if($tx->description)
                                        <small class="text-muted d-block text-truncate" style="max-width: 160px; font-size: 0.72rem;">{{ $tx->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 small">
                                        {{ $tx->account->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-end font-monospace fw-bold {{ $tx->type === 'payment_in' ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->type === 'payment_in' ? '+' : '-' }} Rs. {{ number_format($tx->amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('transactions.show', $tx->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2 rounded-2">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-receipt text-muted fs-4 d-block mb-1"></i>
                                    No financial vouchers recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile View: Clean Voucher Cards (Strictly 0 Horizontal Scroll) -->
            <div class="d-block d-md-none">
                <div class="d-flex flex-column gap-2">
                    @forelse($recentTransactions as $tx)
                        <div class="p-2.5 rounded-3 border bg-white shadow-xs">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="font-monospace fw-bold text-muted small">#{{ str_pad($tx->id, 5, '0', STR_PAD_LEFT) }}</span>
                                {!! $tx->type_badge !!}
                            </div>
                            <div class="d-flex justify-content-between align-items-end gap-2 mt-1">
                                <div class="overflow-hidden min-w-0 flex-grow-1">
                                    <div class="fw-bold text-dark small text-truncate">
                                        {{ $tx->party->name ?? 'Direct Counter' }}
                                    </div>
                                    <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                        <span class="badge bg-light text-dark border px-1.5 py-0.5" style="font-size: 0.68rem;">
                                            {{ $tx->account->name ?? 'N/A' }}
                                        </span>
                                        @if($tx->description)
                                            <small class="text-muted text-truncate d-inline-block" style="max-width: 140px; font-size: 0.7rem;" title="{{ $tx->description }}">{{ $tx->description }}</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="font-monospace fw-bold small {{ $tx->type === 'payment_in' ? 'text-success' : 'text-danger' }}">
                                        {{ $tx->type === 'payment_in' ? '+' : '-' }} Rs. {{ number_format($tx->amount, 2) }}
                                    </div>
                                    <div class="mt-1">
                                        <a href="{{ route('transactions.show', $tx->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2 rounded-2" style="font-size: 0.7rem;">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-receipt text-muted fs-4 d-block mb-1"></i>
                            No financial vouchers recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Live Audit Trail Activity Stream -->
    <div class="col-12 col-xl-5">
        <div class="card-custom p-3 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark m-0">
                    <i class="bi bi-shield-check text-info me-2"></i>Live Audit Trail
                </h6>
                @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-light btn-sm rounded-2 py-0 px-2" style="font-size: 0.74rem;">
                        Full Logs <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                @endif
            </div>

            <div class="d-flex flex-column gap-2">
                @forelse($recentLogs as $log)
                    <div class="p-2 rounded-2 border bg-light d-flex align-items-start gap-2 overflow-hidden">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold mt-1 flex-shrink-0" style="width: 26px; height: 26px; font-size: 0.7rem;">
                            {{ strtoupper(substr($log->user->name ?? 'S', 0, 1)) }}
                        </div>
                        <div class="flex-grow-1 overflow-hidden min-w-0">
                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                <span class="fw-semibold text-dark small text-truncate">{{ $log->user->name ?? 'System' }}</span>
                                <span class="text-muted flex-shrink-0" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-1 mb-1 flex-wrap">
                                {!! $log->action_badge !!}
                                <span class="badge bg-secondary-subtle text-secondary border px-1" style="font-size: 0.68rem;">{{ $log->module }}</span>
                            </div>
                            <div class="text-secondary small text-truncate" style="font-size: 0.75rem;" title="{{ $log->details }}">
                                {{ $log->details }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-shield-slash text-muted fs-4 d-block mb-1"></i>
                        No audit activities recorded yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isMobile = window.innerWidth < 768;

    // 1. Cash Movement Trend Bar Chart
    const ctxCashFlow = document.getElementById('cashFlowChart');
    if (ctxCashFlow) {
        new Chart(ctxCashFlow, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Collections (In)',
                        data: {!! json_encode($chartInflows) !!},
                        backgroundColor: '#10b981',
                        borderRadius: 5,
                        maxBarThickness: isMobile ? 14 : 26,
                    },
                    {
                        label: 'Disbursements (Out)',
                        data: {!! json_encode($chartOutflows) !!},
                        backgroundColor: '#ef4444',
                        borderRadius: 5,
                        maxBarThickness: isMobile ? 14 : 26,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': Rs. ' + Number(context.parsed.y).toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            callback: function(value) {
                                if (window.innerWidth < 576) {
                                    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return (value / 1000).toFixed(0) + 'k';
                                    return value;
                                }
                                return 'Rs. ' + Number(value).toLocaleString();
                            },
                            font: {
                                size: isMobile ? 9 : 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: isMobile ? 9 : 11
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Liquid Fund Allocation Doughnut Chart
    const ctxLiquidity = document.getElementById('liquidityDonutChart');
    if (ctxLiquidity) {
        new Chart(ctxLiquidity, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($accountLabels) !!},
                datasets: [{
                    data: {!! json_encode($accountBalances) !!},
                    backgroundColor: [
                        '#10b981', // Green for cash
                        '#2563eb', // Blue for bank
                        '#e11d48', // Red for jazzcash
                        '#8b5cf6', // Purple for others
                        '#f59e0b'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 8,
                            font: {
                                size: isMobile ? 10 : 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': Rs. ' + Number(context.parsed).toLocaleString();
                            }
                        }
                    }
                },
                cutout: '68%'
            }
        });
    }
});
</script>
@endpush
