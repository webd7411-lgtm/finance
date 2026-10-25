@extends('layouts.app')

@section('title', 'Payments In & Out')
@section('page_title', 'Daily Vouchers (Payment In & Out)')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-arrow-left-right me-1 text-primary"></i> Daily Register
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-1 gap-sm-2 w-100 justify-content-start justify-content-sm-end">
        <button type="button" class="btn btn-warning btn-sm rounded-3 fw-semibold px-2.5 px-sm-3 shadow-sm flex-fill flex-sm-grow-0 text-nowrap text-dark" data-bs-toggle="modal" data-bs-target="#purchaseBillModal">
            <i class="bi bi-file-earmark-plus me-1"></i> Add Purchase Bill
        </button>

        <button type="button" class="btn btn-primary btn-sm rounded-3 fw-semibold px-2.5 px-sm-3 shadow-sm flex-fill flex-sm-grow-0 text-nowrap" data-bs-toggle="modal" data-bs-target="#transferModal">
            <i class="bi bi-arrow-left-right me-1"></i> Transfer Funds
        </button>

        <button type="button" class="btn btn-success btn-sm rounded-3 fw-semibold px-2.5 px-sm-3 shadow-sm flex-fill flex-sm-grow-0 text-nowrap" data-bs-toggle="modal" data-bs-target="#paymentInModal">
            <i class="bi bi-arrow-down-left me-1"></i> Payment In
        </button>

        <button type="button" class="btn btn-danger btn-sm rounded-3 fw-semibold px-2.5 px-sm-3 shadow-sm flex-fill flex-sm-grow-0 text-nowrap" data-bs-toggle="modal" data-bs-target="#paymentOutModal">
            <i class="bi bi-arrow-up-right me-1"></i> Payment Out
        </button>
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
        font-size: clamp(1.15rem, 3.8vw, 1.45rem);
        line-height: 1.25;
        word-break: break-word;
    }
    @media (max-width: 575.98px) {
        .card-custom {
            border-radius: 10px;
        }
    }
</style>
@endpush

@section('content')
<!-- Financial Summary Cards -->
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <div class="col-12 col-md-4">
        <div class="card-custom p-3 bg-success bg-opacity-10 border-success h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-success small fw-semibold text-uppercase kpi-title">Total Payments In</span>
                <i class="bi bi-arrow-down-left-circle text-success fs-5"></i>
            </div>
            <div class="fw-bold font-monospace text-success kpi-amount">+Rs. {{ number_format($totalIn, 2) }}</div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Customer receipts & incoming transfers</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card-custom p-3 bg-danger bg-opacity-10 border-danger h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-danger small fw-semibold text-uppercase kpi-title">Total Payments Out</span>
                <i class="bi bi-arrow-up-right-circle text-danger fs-5"></i>
            </div>
            <div class="fw-bold font-monospace text-danger kpi-amount">-Rs. {{ number_format($totalOut, 2) }}</div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Supplier, staff, and general expenses</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card-custom p-3 {{ $netCashFlow >= 0 ? 'bg-primary bg-opacity-10 border-primary' : 'bg-warning bg-opacity-10 border-warning' }} h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="{{ $netCashFlow >= 0 ? 'text-primary' : 'text-dark' }} small fw-semibold text-uppercase kpi-title">Net Cash Movement</span>
                <i class="bi bi-graph-up-arrow fs-5 {{ $netCashFlow >= 0 ? 'text-primary' : 'text-warning-emphasis' }}"></i>
            </div>
            <div class="fw-bold font-monospace text-dark kpi-amount">{{ $netCashFlow >= 0 ? '+' : '' }}Rs. {{ number_format($netCashFlow, 2) }}</div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Net inflow / outflow for selected filters</div>
        </div>
    </div>
</div>

<!-- Transactions Journal Card -->
<div class="card-custom overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div>
                <h6 class="fw-bold text-dark m-0">{{ $isTrashView ? 'Deleted Vouchers (Trash Audit)' : 'Voucher Journal & History' }}</h6>
                <small class="text-muted" style="font-size: 0.78rem;">
                    {{ $isTrashView ? 'Permanently archived deleted vouchers with reversal audit' : 'Complete record of receipts, payouts, and party settlements' }}
                </small>
            </div>
            <!-- View Tabs -->
            <div class="btn-group btn-group-sm ms-sm-2" role="group">
                <a href="{{ route('transactions.index') }}" class="btn btn-sm {{ !$isTrashView ? 'btn-primary active' : 'btn-outline-secondary' }}">
                    <i class="bi bi-clock-history me-1"></i> Active Vouchers
                    <span class="badge {{ !$isTrashView ? 'bg-white text-primary' : 'bg-secondary text-white' }} ms-1">{{ $activeCount }}</span>
                </a>
                <a href="{{ route('transactions.index', ['view' => 'trash']) }}" class="btn btn-sm {{ $isTrashView ? 'btn-danger active' : 'btn-outline-danger' }}">
                    <i class="bi bi-trash3 me-1"></i> Deleted Vouchers
                    <span class="badge {{ $isTrashView ? 'bg-white text-danger' : 'bg-danger text-white' }} ms-1">{{ $trashCount }}</span>
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('transactions.index') }}" class="d-flex gap-1.5 flex-wrap w-100 w-sm-auto align-items-center">
            @if($isTrashView)
                <input type="hidden" name="view" value="trash">
            @endif
            <select name="type" class="form-select form-select-sm flex-fill flex-sm-grow-0" style="min-width: 110px; max-width: 140px;">
                <option value="">All Types</option>
                <option value="payment_in" {{ request('type') == 'payment_in' ? 'selected' : '' }}>Payment In</option>
                <option value="payment_out" {{ request('type') == 'payment_out' ? 'selected' : '' }}>Payment Out</option>
                <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Expense</option>
                <option value="purchase_bill" {{ request('type') == 'purchase_bill' ? 'selected' : '' }}>Purchase Bill</option>
            </select>

            <select name="account_id" class="form-select form-select-sm flex-fill flex-sm-grow-0" style="min-width: 120px; max-width: 150px;">
                <option value="">All Accounts</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->name }}{{ $acc->isActive() ? '' : ' (Inactive)' }}</option>
                @endforeach
            </select>

            <select name="expense_category_id" class="form-select form-select-sm flex-fill flex-sm-grow-0" style="min-width: 135px; max-width: 165px;" title="Filter by Expense Category">
                <option value="">Expense Category</option>
                @foreach($expenseCategories as $ec)
                    <option value="{{ $ec->id }}" {{ request('expense_category_id') == $ec->id ? 'selected' : '' }}>{{ $ec->name }}</option>
                @endforeach
            </select>

            <select name="purchase_category_id" class="form-select form-select-sm flex-fill flex-sm-grow-0" style="min-width: 135px; max-width: 165px;" title="Filter by Purchase Category">
                <option value="">Purchase Category</option>
                @foreach($purchaseCategories as $pc)
                    <option value="{{ $pc->id }}" {{ request('purchase_category_id') == $pc->id ? 'selected' : '' }}>{{ $pc->name }}</option>
                @endforeach
            </select>

            <input type="date" name="from_date" class="form-control form-control-sm flex-fill flex-sm-grow-0" value="{{ request('from_date') }}" title="From Date" style="min-width: 125px; max-width: 135px;">
            <input type="date" name="to_date" class="form-control form-control-sm flex-fill flex-sm-grow-0" value="{{ request('to_date') }}" title="To Date" style="min-width: 125px; max-width: 135px;">

            @if(!$isTrashView)
                <select name="status" class="form-select form-select-sm flex-fill flex-sm-grow-0" style="min-width: 105px; max-width: 130px;">
                    <option value="">All Status</option>
                    <option value="locked" {{ request('status') == 'locked' ? 'selected' : '' }}>Locked</option>
                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                </select>
            @endif

            <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filter"></i></button>
            @if(request()->hasAny(['type', 'account_id', 'party_id', 'category_id', 'expense_category_id', 'purchase_category_id', 'from_date', 'to_date', 'status']))
                <a href="{{ route('transactions.index', $isTrashView ? ['view' => 'trash'] : []) }}" class="btn btn-link btn-sm text-secondary p-1 text-decoration-none">Clear</a>
            @endif
        </form>
    </div>

    @if($isTrashView)
        <div class="p-3 bg-danger bg-opacity-10 border-bottom d-flex align-items-center gap-2 text-danger small">
            <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
            <div>
                <strong>Audit Record:</strong> Deleted payment vouchers are preserved here permanently. Financial account and party balances for these vouchers were safely reversed at the time of deletion. Click <strong>"Audit Voucher"</strong> to inspect details.
            </div>
        </div>
    @endif

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                @if($isTrashView)
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Voucher Date</th>
                        <th>Voucher Type</th>
                        <th>Party / Payee</th>
                        <th>Payment Channel</th>
                        <th>Bill # / Ref</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th>Deleted On</th>
                        <th>Deleted By</th>
                        <th class="text-end" style="width: 140px;">Audit Voucher</th>
                    </tr>
                @else
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Date</th>
                        <th>Voucher Type</th>
                        <th>Party / Payee</th>
                        <th>Payment Channel</th>
                        <th>Bill # / Ref</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center" style="width: 100px;">Status</th>
                        <th class="text-end" style="width: 135px;">Actions</th>
                    </tr>
                @endif
            </thead>
            <tbody>
                @forelse($transactions as $index => $trx)
                    @if($isTrashView)
                        <tr class="table-light">
                            <td class="text-muted small">{{ $transactions->firstItem() + $index }}</td>
                            <td class="small fw-semibold text-dark">{{ $trx->date->format('d M Y') }}</td>
                            <td>{!! $trx->type_badge !!}</td>
                            <td>
                                @if($trx->party)
                                    <div class="fw-semibold text-dark">{{ $trx->party->name }}</div>
                                    <small class="text-muted">{!! $trx->party->type_badge !!}</small>
                                @elseif($trx->category)
                                    <span class="badge bg-light text-dark border"><i class="bi bi-tag me-1"></i>{{ $trx->category->name }}</span>
                                @else
                                    <span class="text-muted small">Direct Cash Flow</span>
                                @endif
                            </td>
                            <td>
                                @if($trx->account)
                                    <div class="small fw-semibold text-dark">{{ $trx->account->name }}</div>
                                    <small class="text-muted">{!! $trx->account->type_badge !!}</small>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border"><i class="bi bi-clock-history me-1"></i>Credit Purchase</span>
                                @endif
                            </td>
                            <td class="small font-monospace text-secondary">
                                {{ $trx->bill_no ?? '-' }}
                            </td>
                            <td class="small text-muted text-truncate" style="max-width: 220px;" title="{{ $trx->description }}">
                                {{ $trx->description ?? '-' }}
                            </td>
                            <td class="text-end font-monospace fw-bold {{ $trx->type == 'payment_in' ? 'text-success' : ($trx->type == 'purchase_bill' ? 'text-warning-emphasis' : 'text-danger') }}">
                                {{ $trx->type == 'payment_in' ? '+' : ($trx->type == 'purchase_bill' ? '•' : '-') }}Rs. {{ number_format($trx->amount, 2) }}
                            </td>
                            <td class="small text-danger font-monospace">
                                <i class="bi bi-calendar-x me-1"></i>{{ $trx->deleted_at ? $trx->deleted_at->format('d M Y, h:i A') : '-' }}
                            </td>
                            <td class="small text-dark fw-semibold">
                                <i class="bi bi-person-badge me-1 text-danger"></i>{{ $trx->deletedBy->name ?? 'Admin' }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('transactions.show', $trx->id) }}" class="btn btn-outline-danger btn-sm py-1 px-2" title="Inspect Deleted Voucher">
                                    <i class="bi bi-file-earmark-lock me-1"></i> Audit Voucher
                                </a>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td class="text-muted small">{{ $transactions->firstItem() + $index }}</td>
                            <td class="small fw-semibold text-dark">{{ $trx->date->format('d M Y') }}</td>
                            <td>{!! $trx->type_badge !!}</td>
                            <td>
                                @if($trx->party)
                                    <div class="fw-semibold text-dark">{{ $trx->party->name }}</div>
                                    <small class="text-muted">{!! $trx->party->type_badge !!}</small>
                                @elseif($trx->category)
                                    <span class="badge bg-light text-dark border"><i class="bi bi-tag me-1"></i>{{ $trx->category->name }}</span>
                                @else
                                    <span class="text-muted small">Direct Cash Flow</span>
                                @endif
                            </td>
                            <td>
                                @if($trx->account)
                                    <div class="small fw-semibold text-dark">{{ $trx->account->name }}</div>
                                    <small class="text-muted">{!! $trx->account->type_badge !!}</small>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border"><i class="bi bi-clock-history me-1"></i>Credit Purchase</span>
                                @endif
                            </td>
                            <td class="small font-monospace text-secondary">
                                {{ $trx->bill_no ?? '-' }}
                            </td>
                            <td class="small text-muted text-truncate" style="max-width: 220px;" title="{{ $trx->description }}">
                                {{ $trx->description ?? '-' }}
                            </td>
                            <td class="text-end font-monospace fw-bold {{ $trx->type == 'payment_in' ? 'text-success' : ($trx->type == 'purchase_bill' ? 'text-warning-emphasis' : 'text-danger') }}">
                                {{ $trx->type == 'payment_in' ? '+' : ($trx->type == 'purchase_bill' ? '•' : '-') }}Rs. {{ number_format($trx->amount, 2) }}
                            </td>
                            <td class="text-center">{!! $trx->status_badge !!}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('transactions.show', $trx->id) }}" class="btn btn-outline-primary btn-sm py-1 px-2" title="View & Print Voucher">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                    @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                                        @if($trx->status === 'locked')
                                            <form action="{{ route('transactions.unlock', $trx->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Unlock this voucher to allow modifications or deletion?');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning btn-sm py-1 px-2" title="Unlock Voucher">
                                                    <i class="bi bi-unlock"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('transactions.lock', $trx->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Lock Voucher">
                                                    <i class="bi bi-lock"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                    @if(auth()->user()->isOwner())
                                        @if($trx->status === 'locked')
                                            <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 disabled opacity-50" title="Voucher is locked. Unlock before deleting." disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @else
                                            <form action="{{ route('transactions.destroy', $trx->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this voucher? This will revert account and party balances.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete Voucher">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ $isTrashView ? 11 : 10 }}" class="text-center py-5 text-muted small">
                            @if($isTrashView)
                                <i class="bi bi-trash3 fs-3 d-block text-secondary opacity-50 mb-2"></i>
                                No deleted vouchers in trash archive.
                            @else
                                <i class="bi bi-receipt-cutoff fs-3 d-block text-secondary opacity-50 mb-2"></i>
                                No transactions found. Use <strong>"Payment In"</strong> or <strong>"Payment Out"</strong> buttons above to record receipts and payouts.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll) -->
    <div class="d-block d-md-none p-2 p-sm-3">
        <div class="d-flex flex-column gap-2">
            @forelse($transactions as $trx)
                <div class="p-3 rounded-3 border bg-white shadow-xs {{ $isTrashView ? 'border-danger-subtle bg-danger bg-opacity-10' : '' }}">
                    <!-- Top Row: Ref #, Date & Type Badge -->
                    <div class="d-flex align-items-center justify-content-between gap-1 mb-2">
                        <div class="d-flex align-items-center gap-1.5 min-w-0">
                            <span class="font-monospace fw-bold text-muted small">#{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</span>
                            <span class="text-muted small">·</span>
                            <span class="fw-semibold text-dark small">{{ $trx->date->format('d M Y') }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            {!! $trx->type_badge !!}
                            @if($isTrashView)
                                <span class="badge bg-danger-subtle text-danger border border-danger"><i class="bi bi-trash3 me-1"></i>Deleted</span>
                            @else
                                {!! $trx->status_badge !!}
                            @endif
                        </div>
                    </div>

                    @if($isTrashView)
                        <div class="small text-danger pb-2 border-bottom mb-2 font-monospace" style="font-size: 0.74rem;">
                            <i class="bi bi-calendar-x me-1"></i>Deleted: {{ $trx->deleted_at ? $trx->deleted_at->format('d M Y, h:i A') : '-' }} &bull; By: {{ $trx->deletedBy->name ?? 'Admin' }}
                        </div>
                    @endif

                    <!-- Party / Payee & Channel Row -->
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-bold text-dark text-truncate" style="font-size: 0.92rem;">
                                @if($trx->party)
                                    {{ $trx->party->name }}
                                @elseif($trx->category)
                                    <span class="badge bg-light text-dark border"><i class="bi bi-tag me-1"></i>{{ $trx->category->name }}</span>
                                @else
                                    <span class="text-muted small">Direct Cash Flow</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                @if($trx->account)
                                    <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.68rem;">
                                        <i class="bi bi-wallet2 me-1"></i>{{ $trx->account->name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border px-1.5 py-0.5" style="font-size: 0.68rem;">
                                        <i class="bi bi-clock-history me-1"></i>Credit Purchase
                                    </span>
                                @endif
                                @if($trx->party)
                                    {!! $trx->party->type_badge !!}
                                @endif
                                @if($trx->bill_no)
                                    <span class="font-monospace text-muted" style="font-size: 0.68rem;">Ref: {{ $trx->bill_no }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="font-monospace fw-bold {{ $trx->type == 'payment_in' ? 'text-success' : ($trx->type == 'purchase_bill' ? 'text-warning-emphasis' : 'text-danger') }}" style="font-size: 0.95rem;">
                                {{ $trx->type == 'payment_in' ? '+' : ($trx->type == 'purchase_bill' ? '•' : '-') }}Rs. {{ number_format($trx->amount, 2) }}
                            </div>
                        </div>
                    </div>

                    @if($trx->description)
                        <div class="text-muted small text-truncate p-1.5 rounded-2 bg-light border mb-2" style="font-size: 0.72rem;" title="{{ $trx->description }}">
                            <i class="bi bi-chat-left-text me-1 text-secondary"></i>{{ $trx->description }}
                        </div>
                    @endif

                    <!-- Actions Row -->
                    <div class="d-flex align-items-center gap-1.5 pt-1">
                        @if($isTrashView)
                            <a href="{{ route('transactions.show', $trx->id) }}" class="btn btn-outline-danger btn-sm flex-fill py-1.5 px-2 rounded-2 fw-semibold" style="font-size: 0.76rem;">
                                <i class="bi bi-file-earmark-lock me-1"></i> Audit Deleted Voucher
                            </a>
                        @else
                            <a href="{{ route('transactions.show', $trx->id) }}" class="btn btn-outline-primary btn-sm flex-fill py-1.5 px-2 rounded-2 fw-semibold" style="font-size: 0.76rem;">
                                <i class="bi bi-receipt me-1"></i> View Voucher
                            </a>

                            @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                                @if($trx->status === 'locked')
                                    <form action="{{ route('transactions.unlock', $trx->id) }}" method="POST" class="m-0 flex-shrink-0" onsubmit="return confirm('Unlock this voucher to allow modifications or deletion?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning btn-sm py-1.5 px-2.5 rounded-2" style="font-size: 0.76rem;" title="Unlock Voucher">
                                            <i class="bi bi-unlock"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('transactions.lock', $trx->id) }}" method="POST" class="m-0 flex-shrink-0">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm py-1.5 px-2.5 rounded-2" style="font-size: 0.76rem;" title="Lock Voucher">
                                            <i class="bi bi-lock"></i>
                                        </button>
                                    </form>
                                @endif
                            @endif

                            @if(auth()->user()->isOwner())
                                @if($trx->status === 'locked')
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-1.5 px-2.5 rounded-2 disabled opacity-50 flex-shrink-0" style="font-size: 0.76rem;" title="Voucher is locked. Unlock before deleting." disabled>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @else
                                    <form action="{{ route('transactions.destroy', $trx->id) }}" method="POST" class="m-0 flex-shrink-0" onsubmit="return confirm('Are you sure you want to delete this voucher? This will revert account and party balances.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-1.5 px-2.5 rounded-2" style="font-size: 0.76rem;" title="Delete Voucher">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">
                    @if($isTrashView)
                        <i class="bi bi-trash3 fs-3 d-block text-secondary opacity-50 mb-2"></i>
                        No deleted vouchers in trash archive.
                    @else
                        <i class="bi bi-receipt-cutoff fs-3 d-block text-secondary opacity-50 mb-2"></i>
                        No transactions found. Use <strong>"Payment In"</strong> or <strong>"Payment Out"</strong> buttons above to record receipts and payouts.
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    @if($transactions->hasPages())
        <div class="p-3 border-top bg-light overflow-x-auto">
            {{ $transactions->links() }}
        </div>
    @endif
</div>

<!-- ==================== PAYMENT IN MODAL ==================== -->
<div class="modal fade" id="paymentInModal" tabindex="-1" aria-labelledby="payInLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg text-start">
            <div class="modal-header border-bottom py-3 bg-success text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-down-left-circle fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="payInLabel">Record Payment In</h6>
                        <small class="text-white-50" style="font-size: 0.75rem;">Receive funds into cash counter or bank accounts</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('transactions.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="payment_in">

                <div class="modal-body p-3 p-sm-4 text-start">
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control form-control-sm py-2" value="{{ date('Y-m-d') }}" min="{{ now()->subDay()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                            <small class="text-muted" style="font-size: 0.68rem;">Today or Yesterday only</small>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Deposit Into Account <span class="text-danger">*</span></label>
                            <select name="account_id" class="form-select form-select-sm py-2" required>
                                @foreach($accounts->filter(fn($a) => $a->isActive()) as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Amount Received (Rs.) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-success">PKR</span>
                            <input type="text" inputmode="decimal" name="amount" class="form-control form-control-sm py-2 fw-bold fs-6 text-success amount-format" placeholder="0.00" autocomplete="off" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Received From Party <span class="text-muted fw-normal">(Optional)</span></label>
                        <select name="party_id" class="form-select form-select-sm py-2">
                            <option value="">-- Direct Sale / Counter Receipt (No Party) --</option>
                            @foreach($parties as $p)
                                <option value="{{ $p->id }}">
                                    {{ $p->name }} [{{ strtoupper($p->type) }}] - Balance: Rs. {{ number_format($p->current_balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted" style="font-size: 0.72rem;">Selecting a party will deduct this amount from their outstanding receivable balance.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Bill Number / Slip #</label>
                        <input type="text" name="bill_no" class="form-control form-control-sm py-2" placeholder="e.g. REC-1082 / Bank Txn ID">
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Description / Remarks</label>
                        <textarea name="description" class="form-control form-control-sm py-2" rows="2" placeholder="Details about this payment receipt..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Save Payment In
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== PAYMENT OUT MODAL ==================== -->
<div class="modal fade" id="paymentOutModal" tabindex="-1" aria-labelledby="payOutLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg text-start">
            <div class="modal-header border-bottom py-3 bg-danger text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-up-right-circle fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="payOutLabel">Record Payment Out (Party Settlement)</h6>
                        <small class="text-white-50" style="font-size: 0.75rem;">Supplier payments and staff advances</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('transactions.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="payment_out">

                <div class="modal-body p-3 p-sm-4 text-start">
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control form-control-sm py-2" value="{{ date('Y-m-d') }}" min="{{ now()->subDay()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                            <small class="text-muted" style="font-size: 0.68rem;">Today or Yesterday only</small>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Pay From Account <span class="text-danger">*</span></label>
                            <select name="account_id" id="payOutAccountSelect" class="form-select form-select-sm py-2" required>
                                <option value="" disabled selected>-- Select Account --</option>
                                @foreach($accounts->filter(fn($a) => $a->isActive()) as $acc)
                                    <option value="{{ $acc->id }}" data-balance="{{ (float) $acc->current_balance }}">{{ $acc->name }} (Rs. {{ number_format($acc->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                            <div id="payOutBalanceFeedback" class="small mt-1" style="font-size: 0.75rem;"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Paid To Party <span class="text-danger">*</span></label>
                        <select name="party_id" class="form-select form-select-sm py-2" required>
                            <option value="" disabled selected>-- Select Supplier / Trader / Staff --</option>
                            @foreach($parties as $p)
                                <option value="{{ $p->id }}">
                                    {{ $p->name }} [{{ strtoupper($p->type) }}] - Balance: Rs. {{ number_format($p->current_balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted" style="font-size: 0.72rem;">Selecting a supplier reduces payable balance; selecting staff registers an advance loan.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Amount Paid (Rs.) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-danger">PKR</span>
                            <input type="text" inputmode="decimal" name="amount" id="payOutAmountInput" class="form-control form-control-sm py-2 fw-bold fs-6 text-danger amount-format" placeholder="0.00" autocomplete="off" required>
                        </div>
                        <div id="payOutAmountWarning" class="text-danger small fw-semibold mt-1" style="display: none; font-size: 0.75rem;"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Voucher / Cheque / Ref #</label>
                        <input type="text" name="bill_no" class="form-control form-control-sm py-2" placeholder="e.g. CHQ-9042, TR-104">
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Description / Remarks</label>
                        <textarea name="description" class="form-control form-control-sm py-2" rows="2" placeholder="Details about this payment to party..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Save Payment Out
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== TRANSFER FUNDS MODAL ==================== -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg text-start">
            <div class="modal-header border-bottom py-3 bg-primary text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left-right fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="transferModalLabel">Transfer Funds Between Accounts</h6>
                        <small class="text-white-50" style="font-size: 0.75rem;">Move funds between Bank, Drawer, or Digital Wallets</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('transactions.transfer') }}" method="POST">
                @csrf
                <div class="modal-body p-3 p-sm-4 text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Transfer Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control form-control-sm py-2" value="{{ date('Y-m-d') }}" min="{{ now()->subDay()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                        <small class="text-muted" style="font-size: 0.68rem;">Today or Yesterday only</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-danger">Transfer From (Source) <span class="text-danger">*</span></label>
                            <select name="from_account_id" id="transferFromAccountSelect" class="form-select form-select-sm py-2" required>
                                <option value="" disabled selected>-- Select Source --</option>
                                @foreach($accounts->filter(fn($a) => $a->isActive()) as $acc)
                                    <option value="{{ $acc->id }}" data-balance="{{ (float) $acc->current_balance }}">{{ $acc->name }} (Rs. {{ number_format($acc->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                            <div id="transferBalanceFeedback" class="small mt-1" style="font-size: 0.75rem;"></div>
                            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Funds will be deducted from this account.</div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-success">Transfer To (Destination) <span class="text-danger">*</span></label>
                            <select name="to_account_id" class="form-select form-select-sm py-2" required>
                                <option value="" disabled selected>-- Select Destination --</option>
                                @foreach($accounts->filter(fn($a) => $a->isActive()) as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} (Rs. {{ number_format($acc->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Funds will be added to this account.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Transfer Amount (Rs.) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-primary">PKR</span>
                            <input type="text" inputmode="decimal" name="amount" id="transferAmountInput" class="form-control form-control-sm py-2 fw-bold fs-6 text-primary amount-format" placeholder="e.g. 5,000.00" autocomplete="off" required>
                        </div>
                        <div id="transferAmountWarning" class="text-danger small fw-semibold mt-1" style="display: none; font-size: 0.75rem;"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Reference / Cheque # / Slip #</label>
                        <input type="text" name="reference" class="form-control form-control-sm py-2" placeholder="e.g. ATM-Withdrawal / Cheque-1049 / Raast">
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Description / Remarks</label>
                        <textarea name="description" class="form-control form-control-sm py-2" rows="2" placeholder="e.g. Cash transferred from Meezan bank to drawer..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-arrow-left-right me-1"></i> Transfer Funds Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== PURCHASE BILL (CREDIT) MODAL ==================== -->
<div class="modal fade" id="purchaseBillModal" tabindex="-1" aria-labelledby="purchaseBillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg text-start">
            <div class="modal-header border-bottom py-3 bg-warning text-dark">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-plus fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="purchaseBillModalLabel">Record Purchase Bill (Credit)</h6>
                        <small class="text-dark-50" style="font-size: 0.75rem;">Credit supplies & stock received without immediate cash payment</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('transactions.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="purchase_bill">

                <div class="modal-body p-3 p-sm-4 text-start">
                    <div class="alert alert-warning py-2 px-3 small border-0 d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-info-circle-fill text-warning fs-6"></i>
                        <div>
                            <strong>Credit Purchase:</strong> No cash or bank balance will be deducted now. The bill amount will be added to the supplier's payable ledger balance.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Bill Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control form-control-sm py-2" value="{{ date('Y-m-d') }}" min="{{ now()->subDay()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                        <small class="text-muted" style="font-size: 0.68rem;">Today or Yesterday only</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Supplier / Vendor <span class="text-danger">*</span></label>
                        <select name="party_id" class="form-select form-select-sm py-2" required>
                            <option value="" disabled selected>-- Select Supplier / Trader --</option>
                            @foreach($parties as $p)
                                <option value="{{ $p->id }}">
                                    {{ $p->name }} [{{ strtoupper($p->type) }}] - Current Balance: Rs. {{ number_format($p->current_balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted" style="font-size: 0.72rem;">The bill amount will be credited to this party's ledger.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Bill Total Amount (Rs.) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-dark">PKR</span>
                            <input type="text" inputmode="decimal" name="amount" class="form-control form-control-sm py-2 fw-bold fs-6 text-dark amount-format" placeholder="0.00" autocomplete="off" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Purchase Category</label>
                            <select name="category_id" class="form-select form-select-sm py-2">
                                <option value="">-- Select Purchase Category --</option>
                                @foreach($purchaseCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="text-muted small" style="font-size: 0.72rem;">Optional inventory category</span>
                                <a href="{{ route('expense-categories.index', ['tab' => 'purchase']) }}" class="small text-decoration-none" style="font-size: 0.72rem;">+ Manage Categories</a>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Supplier Bill / Invoice #</label>
                            <input type="text" name="bill_no" class="form-control form-control-sm py-2" placeholder="e.g. BILL-4509 / Bilty #">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Description / Item Details</label>
                        <textarea name="description" class="form-control form-control-sm py-2" rows="2" placeholder="e.g. 50 cartons of inventory stock received on 30-day credit..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm px-4 fw-semibold text-dark shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Save Purchase Bill
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupBalanceCheck(selectId, inputId, feedbackId, warningId) {
        const select = document.getElementById(selectId);
        const input = document.getElementById(inputId);
        const feedback = document.getElementById(feedbackId);
        const warning = document.getElementById(warningId);
        const form = select ? select.closest('form') : null;

        if (!select || !input || !form) return;

        function updateCheck() {
            const selectedOpt = select.options[select.selectedIndex];
            if (!selectedOpt || !selectedOpt.value) {
                if (feedback) feedback.innerHTML = '';
                if (warning) {
                    warning.style.display = 'none';
                    warning.innerHTML = '';
                }
                return true;
            }

            const bal = parseFloat(selectedOpt.getAttribute('data-balance') || '0');
            const formattedBal = window.formatWithCommas(bal.toFixed(2));
            
            if (feedback) {
                feedback.innerHTML = `<span class="badge ${bal > 0 ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger'}"><i class="bi bi-wallet2 me-1"></i>Available: Rs. ${formattedBal}</span>`;
            }

            const entered = window.parseAmount(input.value);
            if (entered > 0 && entered > bal) {
                input.classList.add('is-invalid');
                if (warning) {
                    warning.style.display = 'block';
                    warning.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>Amount (Rs. ${window.formatWithCommas(entered.toFixed(2))}) exceeds available balance of Rs. ${formattedBal}!`;
                }
                return false;
            } else {
                input.classList.remove('is-invalid');
                if (warning) {
                    warning.style.display = 'none';
                    warning.innerHTML = '';
                }
                return true;
            }
        }

        select.addEventListener('change', updateCheck);
        input.addEventListener('input', updateCheck);
        input.addEventListener('keyup', updateCheck);

        form.addEventListener('submit', function(e) {
            if (!updateCheck()) {
                e.preventDefault();
                e.stopPropagation();
                alert('Transaction cannot be completed: Amount exceeds available account balance.');
            }
        });
    }

    setupBalanceCheck('payOutAccountSelect', 'payOutAmountInput', 'payOutBalanceFeedback', 'payOutAmountWarning');
    setupBalanceCheck('transferFromAccountSelect', 'transferAmountInput', 'transferBalanceFeedback', 'transferAmountWarning');
});
</script>
@endpush
