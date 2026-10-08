@extends('layouts.app')

@section('title', 'Payment Accounts')
@section('page_title', 'Payment Accounts (Cash & Banks)')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-wallet2 me-1 text-primary"></i> Total: {{ $accounts->count() }} Accounts
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-2 w-100 justify-content-start justify-content-sm-end">
        <button type="button" class="btn btn-outline-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#transferModal">
            <i class="bi bi-arrow-left-right me-1"></i> Transfer Funds
        </button>
        <button type="button" class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#createAccountModal">
            <i class="bi bi-plus-circle me-1"></i> Add Account
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
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom p-3 bg-primary bg-opacity-10 border-primary h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-primary small fw-semibold text-uppercase kpi-title">Total Liquid Balance</span>
                <i class="bi bi-wallet2 text-primary fs-5"></i>
            </div>
            <div class="fw-bold font-monospace text-dark kpi-amount">Rs. {{ number_format($totalBalance, 2) }}</div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">All active counter & bank funds</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom p-3 bg-success bg-opacity-10 border-success h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-success small fw-semibold text-uppercase kpi-title">Cash in Hand</span>
                <i class="bi bi-cash-stack text-success fs-5"></i>
            </div>
            <div class="fw-bold font-monospace text-success kpi-amount">Rs. {{ number_format($cashBalance, 2) }}</div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Physical counter drawer funds</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom p-3 bg-danger bg-opacity-10 border-danger h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-danger small fw-semibold text-uppercase kpi-title">JazzCash / Wallets</span>
                <i class="bi bi-phone text-danger fs-5"></i>
            </div>
            <div class="fw-bold font-monospace text-danger kpi-amount">Rs. {{ number_format($jazzCashBalance, 2) }}</div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Mobile wallet accounts</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom p-3 bg-info bg-opacity-10 border-info h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-info small fw-semibold text-uppercase kpi-title">Bank Accounts</span>
                <i class="bi bi-bank text-info fs-5"></i>
            </div>
            <div class="fw-bold font-monospace text-info kpi-amount">Rs. {{ number_format($bankBalance, 2) }}</div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Commercial bank balances</div>
        </div>
    </div>
</div>

<!-- Accounts Data Card -->
<div class="card-custom overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="fw-bold text-dark m-0">Payment Channels & Balances</h6>
            <small class="text-muted" style="font-size: 0.78rem;">Physical cash counter, mobile wallets, and official bank accounts</small>
        </div>
    </div>

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Account Name</th>
                    <th>Channel Type</th>
                    <th>Account / Mobile Number</th>
                    <th class="text-end">Opening Balance</th>
                    <th class="text-end">Current Balance</th>
                    <th class="text-end" style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $index => $acc)
                    <tr>
                        <td class="text-muted small">{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($acc->type === 'cash')
                                    <div class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="bi bi-cash-stack"></i>
                                    </div>
                                @elseif($acc->type === 'jazzcash')
                                    <div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="bi bi-phone"></i>
                                    </div>
                                @else
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="bi bi-bank"></i>
                                    </div>
                                @endif
                                <div class="fw-semibold text-dark">{{ $acc->name }}</div>
                            </div>
                        </td>
                        <td>{!! $acc->type_badge !!}</td>
                        <td class="font-monospace text-secondary small">
                            {{ $acc->account_number ?? '-' }}
                        </td>
                        <td class="text-end font-monospace small">
                            Rs. {{ number_format($acc->opening_balance, 2) }}
                        </td>
                        <td class="text-end font-monospace fw-bold fs-6 {{ $acc->current_balance < 0 ? 'text-danger' : 'text-dark' }}">
                            Rs. {{ number_format($acc->current_balance, 2) }}
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                @if($acc->type === 'cash')
                                    <a href="{{ route('ledgers.cash-book', ['account_id' => $acc->id]) }}" class="btn btn-outline-info btn-sm py-1 px-2" title="View Cash Book">
                                        <i class="bi bi-journal-text"></i>
                                    </a>
                                @else
                                    <a href="{{ route('ledgers.bank-book', ['account_id' => $acc->id]) }}" class="btn btn-outline-info btn-sm py-1 px-2" title="View Bank Book">
                                        <i class="bi bi-journal-text"></i>
                                    </a>
                                @endif
                                <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" data-bs-toggle="modal" data-bs-target="#editAccountModal{{ $acc->id }}" title="Edit Account">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if($acc->type === 'cash')
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 disabled" title="Auto Cash Account cannot be deleted" disabled>
                                        <i class="bi bi-shield-lock"></i>
                                    </button>
                                @else
                                    <form action="{{ route('accounts.destroy', $acc->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this account?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete Account">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted small">
                            No accounts found. Click <strong>"Add Account"</strong> to register Cash, JazzCash, or Bank accounts.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll) -->
    <div class="d-block d-md-none p-2 p-sm-3">
        <div class="d-flex flex-column gap-2">
            @forelse($accounts as $acc)
                <div class="p-3 rounded-3 border bg-white shadow-xs">
                    <!-- Top Row: Icon, Name & Type Badge -->
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            @if($acc->type === 'cash')
                                <div class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                    <i class="bi bi-cash-stack fs-6"></i>
                                </div>
                            @elseif($acc->type === 'jazzcash')
                                <div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                    <i class="bi bi-phone fs-6"></i>
                                </div>
                            @else
                                <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                    <i class="bi bi-bank fs-6"></i>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="fw-bold text-dark text-truncate" style="font-size: 0.92rem;">
                                    {{ $acc->name }}
                                </div>
                                @if($acc->account_number)
                                    <div class="text-muted font-monospace small text-truncate" style="font-size: 0.74rem;">
                                        {{ $acc->account_number }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            {!! $acc->type_badge !!}
                        </div>
                    </div>

                    <!-- Balances Strip -->
                    <div class="p-2 rounded-2 bg-light border d-flex justify-content-between align-items-center mb-2" style="font-size: 0.78rem;">
                        <div>
                            <span class="text-muted d-block" style="font-size: 0.68rem;">Opening Balance</span>
                            <span class="font-monospace text-secondary">Rs. {{ number_format($acc->opening_balance, 0) }}</span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted d-block" style="font-size: 0.68rem;">Current Balance</span>
                            <span class="font-monospace fw-bold {{ $acc->current_balance < 0 ? 'text-danger' : 'text-dark' }}" style="font-size: 0.88rem;">
                                Rs. {{ number_format($acc->current_balance, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="d-flex align-items-center gap-1.5 pt-1">
                        @if($acc->type === 'cash')
                            <a href="{{ route('ledgers.cash-book', ['account_id' => $acc->id]) }}" class="btn btn-outline-info btn-sm flex-fill py-1.5 px-2 rounded-2 fw-semibold" style="font-size: 0.76rem;">
                                <i class="bi bi-journal-text me-1"></i> Cash Book
                            </a>
                        @else
                            <a href="{{ route('ledgers.bank-book', ['account_id' => $acc->id]) }}" class="btn btn-outline-info btn-sm flex-fill py-1.5 px-2 rounded-2 fw-semibold" style="font-size: 0.76rem;">
                                <i class="bi bi-journal-text me-1"></i> Bank Book
                            </a>
                        @endif

                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill py-1.5 px-2 rounded-2 fw-semibold" style="font-size: 0.76rem;" data-bs-toggle="modal" data-bs-target="#editAccountModal{{ $acc->id }}">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>

                        @if($acc->type === 'cash')
                            <button type="button" class="btn btn-outline-secondary btn-sm py-1.5 px-2.5 rounded-2 disabled flex-shrink-0" title="Auto Cash Account cannot be deleted" disabled>
                                <i class="bi bi-shield-lock"></i>
                            </button>
                        @else
                            <form action="{{ route('accounts.destroy', $acc->id) }}" method="POST" class="flex-shrink-0 m-0" onsubmit="return confirm('Are you sure you want to delete this account?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm py-1.5 px-2.5 rounded-2" style="font-size: 0.76rem;" title="Delete Account">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">
                    No accounts found. Click <strong>"Add Account"</strong> to register Cash, JazzCash, or Bank accounts.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Edit Account Modals (Rendered outside table/card containers to prevent duplicate IDs) -->
@foreach($accounts as $acc)
    <div class="modal fade" id="editAccountModal{{ $acc->id }}" tabindex="-1" aria-labelledby="editAccountModalLabel{{ $acc->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow text-start">
                <div class="modal-header border-bottom py-3">
                    <div>
                        <h6 class="modal-title fw-bold text-dark m-0" id="editAccountModalLabel{{ $acc->id }}">Edit Account: {{ $acc->name }}</h6>
                        <small class="text-muted" style="font-size: 0.78rem;">Update payment channel title, account number, or balance</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('accounts.update', $acc->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-3 p-sm-4 text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm py-2" name="name" value="{{ $acc->name }}" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold text-secondary">Type <span class="text-danger">*</span></label>
                                @if($acc->type === 'cash')
                                    <input type="hidden" name="type" value="cash">
                                    <input type="text" class="form-control form-control-sm py-2 bg-light text-success fw-semibold" value="Cash in Hand (Auto Account)" readonly>
                                    <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-shield-lock me-1"></i>System auto cash account
                                    </div>
                                @else
                                    <select class="form-select form-select-sm py-2" name="type" required>
                                        <option value="bank" {{ $acc->type == 'bank' ? 'selected' : '' }}>Bank Account</option>
                                        <option value="jazzcash" {{ $acc->type == 'jazzcash' ? 'selected' : '' }}>JazzCash / Mobile Wallet</option>
                                    </select>
                                @endif
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold text-secondary">Account / Mobile Number</label>
                                <input type="text" class="form-control form-control-sm py-2" name="account_number" value="{{ $acc->account_number }}">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary">Opening Balance (Rs.)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light fw-bold text-secondary">PKR</span>
                                <input type="text" inputmode="decimal" class="form-control amount-format" name="opening_balance" value="{{ $acc->opening_balance }}" placeholder="0.00" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-2">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold rounded-3 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<!-- Create Account Modal -->
<div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow text-start">
            <div class="modal-header border-bottom py-3">
                <div>
                    <h6 class="modal-title fw-bold text-dark m-0" id="createAccountModalLabel">Create Payment Account</h6>
                    <small class="text-muted" style="font-size: 0.78rem;">Setup Bank accounts or digital wallets (JazzCash / Easypaisa). Cash is automatic.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('accounts.store') }}" method="POST">
                @csrf
                <div class="modal-body p-3 p-sm-4 text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Account Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm py-2" name="name" placeholder="e.g. Meezan Bank / JazzCash Shop" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Account Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm py-2" name="type" required>
                                <option value="" disabled selected>-- Select Type --</option>
                                <option value="bank">Bank Account</option>
                                <option value="jazzcash">JazzCash / Mobile Wallet</option>
                            </select>
                            <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                                <i class="bi bi-info-circle me-1"></i>Cash account is automatically maintained as <strong>Cash in Hand</strong>.
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Account / Mobile Number</label>
                            <input type="text" class="form-control form-control-sm py-2" name="account_number" placeholder="0101-0102030405">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Opening Balance (Rs.)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-secondary">PKR</span>
                            <input type="text" inputmode="decimal" class="form-control amount-format" name="opening_balance" value="0.00" placeholder="0.00" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold rounded-3 shadow-sm">
                        <i class="bi bi-save me-1"></i> Save Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== TRANSFER FUNDS MODAL ==================== -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalAccLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg text-start">
            <div class="modal-header border-bottom py-3 bg-primary text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left-right fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="transferModalAccLabel">Transfer Funds Between Accounts</h6>
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
                        <input type="date" name="date" class="form-control form-control-sm py-2" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-danger">Transfer From (Source) <span class="text-danger">*</span></label>
                            <select name="from_account_id" class="form-select form-select-sm py-2" required>
                                <option value="" disabled selected>-- Select Source --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                @endforeach
                            </select>
                            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Funds will be deducted from this account.</div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-success">Transfer To (Destination) <span class="text-danger">*</span></label>
                            <select name="to_account_id" class="form-select form-select-sm py-2" required>
                                <option value="" disabled selected>-- Select Destination --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                @endforeach
                            </select>
                            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Funds will be deposited into this account.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Transfer Amount (Rs.) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-primary">PKR</span>
                            <input type="text" inputmode="decimal" name="amount" class="form-control form-control-sm py-2 fw-bold fs-6 text-primary amount-format" placeholder="e.g. 5,000.00" autocomplete="off" required>
                        </div>
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
@endsection
