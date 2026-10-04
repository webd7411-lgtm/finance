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
<!-- Filter & Selection Bar (No-Print) -->
<div class="card-custom p-3 mb-3 mb-md-4 no-print">
    <form method="GET" action="{{ route('ledgers.bank-book') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label text-muted small fw-semibold mb-1">Select Bank / Wallet Account</label>
            <select name="account_id" class="form-select form-select-sm py-2" onchange="this.form.submit()">
                <option value="">All Bank & Wallet Accounts</option>
                @foreach($bankAccounts as $acc)
                    <option value="{{ $acc->id }}" {{ $selectedAccountId == $acc->id ? 'selected' : '' }}>
                        [{{ ucfirst($acc->type) }}] {{ $acc->name }} (Current: Rs. {{ number_format($acc->current_balance, 2) }})
                    </option>
                @endforeach
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

<!-- Printable Bank Book Card -->
<div class="card-custom p-3 p-md-5 bg-white shadow-sm mb-4" id="bankBookSheet">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 pb-md-4 mb-3 mb-md-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="bg-primary text-white rounded-2 p-1 px-2 fw-bold">
                    <i class="bi bi-bank"></i>
                </div>
                <h4 class="fw-bold text-dark m-0">FinanceDesk</h4>
            </div>
            <h5 class="text-secondary fw-bold m-0" style="font-size: clamp(1rem, 3.5vw, 1.25rem);">Bank & Digital Wallet Register</h5>
            <small class="text-muted">Proware Technologies &bull; Institutional Liquidity Ledger</small>
        </div>
        <div class="text-start text-md-end w-100 w-md-auto">
            <span class="badge bg-light text-dark border px-2 py-1 mb-1 font-monospace d-inline-block">
                Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}
            </span>
            <div class="small text-muted" style="font-size: 0.72rem;">Audited: {{ now()->format('d M, Y - h:i A') }}</div>
            <div class="small text-muted" style="font-size: 0.72rem;">Operator: <strong>{{ auth()->user()->name }}</strong></div>
        </div>
    </div>

    <!-- Executive Metric Cards -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <div class="col-6 col-md-3">
            <div class="p-3 bg-light rounded-3 border h-100">
                <span class="text-muted small d-block mb-1 kpi-title">Opening Bank Balance</span>
                <h5 class="fw-bold font-monospace text-dark m-0 kpi-amount">Rs. {{ number_format($openingBalance, 2) }}</h5>
                <small class="text-muted" style="font-size: 0.7rem;">Prior to {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }}</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle h-100">
                <span class="text-success small fw-semibold d-block mb-1 kpi-title">(+) Total Deposits</span>
                <h5 class="fw-bold font-monospace text-success m-0 kpi-amount">+ Rs. {{ number_format($totalIn, 2) }}</h5>
                <small class="text-success-emphasis" style="font-size: 0.7rem;">Bank & Wallet Collections</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle h-100">
                <span class="text-danger small fw-semibold d-block mb-1 kpi-title">(-) Total Withdrawals</span>
                <h5 class="fw-bold font-monospace text-danger m-0 kpi-amount">- Rs. {{ number_format($totalOut, 2) }}</h5>
                <small class="text-danger-emphasis" style="font-size: 0.7rem;">Disbursements & Payments</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-primary text-white rounded-3 shadow-sm h-100">
                <span class="text-white-50 small fw-semibold d-block mb-1 kpi-title">(=) Ending Bank Balance</span>
                <h5 class="fw-bold font-monospace text-white m-0 kpi-amount">Rs. {{ number_format($closingBalance, 2) }}</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">As of {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</small>
            </div>
        </div>
    </div>

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block table-responsive mb-4">
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
    <div class="d-block d-md-none mb-3">
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

    <!-- Signatures -->
    <div class="pt-4 pt-md-5 mt-3 mt-md-4 border-top">
        <div class="row text-center g-3">
            <div class="col-12 col-md-4 mb-2 mb-md-0">
                <div class="border-top border-dark mx-auto" style="width: 75%;"></div>
                <div class="small fw-bold text-dark mt-1">Finance Officer</div>
                <div class="small text-muted">Accounts Desk</div>
            </div>
            <div class="col-12 col-md-4 mb-2 mb-md-0">
                <div class="border-top border-dark mx-auto" style="width: 75%;"></div>
                <div class="small fw-bold text-dark mt-1">Bank Reconciliation Officer</div>
                <div class="small text-muted">Branch Supervisor</div>
            </div>
            <div class="col-12 col-md-4">
                <div class="border-top border-dark mx-auto" style="width: 75%;"></div>
                <div class="small fw-bold text-dark mt-1">Managing Director</div>
                <div class="small text-muted">Executive Owner</div>
            </div>
        </div>
    </div>
</div>
@endsection
