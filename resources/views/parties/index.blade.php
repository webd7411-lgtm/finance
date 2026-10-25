@extends('layouts.app')

@section('title', 'Parties Management')
@section('page_title', 'Parties & Ledgers Directory')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-person-lines-fill me-1 text-primary"></i> Total: {{ $totalParties }}
    </span>
@endsection

@section('page_actions')
    <button type="button" class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm text-nowrap w-100 w-sm-auto" data-bs-toggle="modal" data-bs-target="#createPartyModal">
        <i class="bi bi-person-plus-fill me-1"></i> Add New Party
    </button>
@endsection

@push('styles')
<style>
    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .filter-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .filter-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    @media (max-width: 575.98px) {
        .card-custom {
            border-radius: 10px;
        }
    }
</style>
@endpush

@section('content')
<!-- Quick Filter Summary Cards -->
<div class="row row-cols-2 row-cols-sm-3 row-cols-lg-5 g-2 mb-3">
    <div class="col">
        <a href="{{ route('parties.index', array_filter(['mode' => request('mode')])) }}" class="card-custom filter-card p-2.5 text-decoration-none d-block text-center h-100 {{ !request('type') ? 'border-primary bg-primary bg-opacity-10' : '' }}">
            <div class="text-secondary small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.3px;">ALL PARTIES</div>
            <div class="fw-bold fs-6 text-dark mt-0.5">{{ $totalParties }}</div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('parties.index', array_filter(['type' => 'supplier', 'mode' => request('mode')])) }}" class="card-custom filter-card p-2.5 text-decoration-none d-block text-center h-100 {{ request('type') == 'supplier' ? 'border-danger bg-danger bg-opacity-10' : '' }}">
            <div class="text-danger small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.3px;">SUPPLIERS</div>
            <div class="fw-bold fs-6 text-dark mt-0.5">{{ $totalSuppliers }}</div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('parties.index', array_filter(['type' => 'trader', 'mode' => request('mode')])) }}" class="card-custom filter-card p-2.5 text-decoration-none d-block text-center h-100 {{ request('type') == 'trader' ? 'border-primary bg-primary bg-opacity-10' : '' }}">
            <div class="text-primary small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.3px;">TRADERS</div>
            <div class="fw-bold fs-6 text-dark mt-0.5">{{ $totalTraders }}</div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('parties.index', array_filter(['type' => 'staff', 'mode' => request('mode')])) }}" class="card-custom filter-card p-2.5 text-decoration-none d-block text-center h-100 {{ request('type') == 'staff' ? 'border-warning bg-warning bg-opacity-10' : '' }}">
            <div class="text-warning-emphasis small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.3px;">STAFF (ADVANCE)</div>
            <div class="fw-bold fs-6 text-dark mt-0.5">{{ $totalStaff }}</div>
        </a>
    </div>
    <div class="col col-12 col-sm-auto flex-sm-grow-1 flex-lg-grow-0">
        <a href="{{ route('parties.index', array_filter(['type' => 'customer', 'mode' => request('mode')])) }}" class="card-custom filter-card p-2.5 text-decoration-none d-block text-center h-100 {{ request('type') == 'customer' ? 'border-success bg-success bg-opacity-10' : '' }}">
            <div class="text-success small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.3px;">CUSTOMERS</div>
            <div class="fw-bold fs-6 text-dark mt-0.5">{{ $totalCustomers }}</div>
        </a>
    </div>
</div>

<!-- Search & Data Card -->
<div class="card-custom overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h6 class="fw-bold text-dark m-0">Party Accounts</h6>
            <span class="badge bg-light text-primary border" style="font-size: 0.7rem;" title="Sorted Alphabetically (A–Z)">
                <i class="bi bi-sort-alpha-down me-1"></i>A–Z Order
            </span>
            @if(request('type'))
                <span class="badge bg-secondary text-uppercase" style="font-size: 0.7rem;">{{ request('type') }}</span>
            @endif
            @if(request('mode'))
                <span class="badge {{ request('mode') === 'credit' ? 'bg-primary' : 'bg-success' }} text-uppercase" style="font-size: 0.7rem;">
                    <i class="bi {{ request('mode') === 'credit' ? 'bi-credit-card' : 'bi-cash-stack' }} me-1"></i>{{ request('mode') }}
                </span>
            @endif
            @if(request('type') || request('mode') || request('search') || request('sort'))
                <a href="{{ route('parties.index') }}" class="text-muted small text-decoration-none" title="Reset all filters"><i class="bi bi-x-circle"></i> Clear All</a>
            @endif
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap w-100 w-md-auto justify-content-between justify-content-md-end">
            <!-- Dealing Mode Filter Pills (Credit / Cash) -->
            <div class="btn-group btn-group-sm p-0.5 rounded-pill bg-light border" role="group" aria-label="Filter by dealing mode">
                <a href="{{ route('parties.index', array_filter(['type' => request('type'), 'sort' => request('sort'), 'search' => request('search')])) }}" 
                   class="btn btn-sm py-1 px-2.5 rounded-pill {{ !request('mode') ? 'btn-dark text-white fw-bold shadow-xs' : 'btn-light text-secondary border-0' }}" 
                   style="font-size: 0.73rem;">
                    All Modes <span class="badge bg-dark-subtle text-dark ms-1">{{ $totalParties }}</span>
                </a>
                <a href="{{ route('parties.index', array_filter(['type' => request('type'), 'mode' => 'credit', 'sort' => request('sort'), 'search' => request('search')])) }}" 
                   class="btn btn-sm py-1 px-2.5 rounded-pill {{ request('mode') === 'credit' ? 'btn-primary text-white fw-bold shadow-xs' : 'btn-light text-secondary border-0' }}" 
                   style="font-size: 0.73rem;">
                    <i class="bi bi-credit-card me-1"></i>Credit <span class="badge bg-primary-subtle text-primary ms-1">{{ $totalCredit }}</span>
                </a>
                <a href="{{ route('parties.index', array_filter(['type' => request('type'), 'mode' => 'cash', 'sort' => request('sort'), 'search' => request('search')])) }}" 
                   class="btn btn-sm py-1 px-2.5 rounded-pill {{ request('mode') === 'cash' ? 'btn-success text-white fw-bold shadow-xs' : 'btn-light text-secondary border-0' }}" 
                   style="font-size: 0.73rem;">
                    <i class="bi bi-cash-stack me-1"></i>Cash <span class="badge bg-success-subtle text-success ms-1">{{ $totalCash }}</span>
                </a>
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('parties.index') }}" class="d-flex gap-2 flex-grow-1 flex-md-grow-0" style="min-width: 200px; max-width: 320px;">
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                @if(request('mode'))
                    <input type="hidden" name="mode" value="{{ request('mode') }}">
                @endif
                @if(request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif
                <div class="input-group input-group-sm w-100">
                    <input type="text" name="search" class="form-control" placeholder="Search name or phone..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    @if(request('search'))
                        <a href="{{ route('parties.index', array_filter(['type' => request('type'), 'mode' => request('mode'), 'sort' => request('sort')])) }}" class="btn btn-outline-secondary" title="Clear Search"><i class="bi bi-x"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>
                        <a href="{{ route('parties.index', array_merge(request()->query(), ['sort' => request('sort') === 'name_desc' ? 'name_asc' : 'name_desc'])) }}" 
                           class="text-decoration-none text-dark d-inline-flex align-items-center gap-1" 
                           title="Click to toggle A–Z / Z–A order">
                            <span>Party Name</span>
                            @if(request('sort') === 'name_desc')
                                <i class="bi bi-sort-alpha-up-alt text-primary" title="Sorted Z–A"></i>
                            @else
                                <i class="bi bi-sort-alpha-down text-primary" title="Sorted A–Z"></i>
                            @endif
                        </a>
                    </th>
                    <th>Category Type</th>
                    <th>Dealing Mode</th>
                    <th>Contact Phone</th>
                    <th>Address / Location</th>
                    <th class="text-end">Opening Balance</th>
                    <th class="text-end">Current Balance</th>
                    <th class="text-end" style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parties as $index => $party)
                    <tr>
                        <td class="text-muted small">{{ $parties->firstItem() + $index }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $party->name }}</div>
                        </td>
                        <td>{!! $party->type_badge !!}</td>
                        <td>{!! $party->dealing_badges !!}</td>
                        <td class="text-secondary small font-monospace">
                            @if($party->phone)
                                <a href="tel:{{ $party->phone }}" class="text-decoration-none text-secondary">
                                    <i class="bi bi-telephone me-1 text-primary"></i>{{ $party->phone }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-muted small text-truncate" style="max-width: 220px;" title="{{ $party->address }}">
                            {{ $party->address ?? '-' }}
                        </td>
                        <td class="text-end font-monospace small">
                            Rs. {{ number_format($party->opening_balance, 2) }}
                        </td>
                        <td class="text-end font-monospace fw-bold {{ $party->current_balance < 0 ? 'text-danger' : 'text-dark' }}">
                            Rs. {{ number_format($party->current_balance, 2) }}
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('ledgers.party', ['party_id' => $party->id]) }}" class="btn btn-outline-info btn-sm py-1 px-2" title="View Ledger">
                                    <i class="bi bi-journal-text"></i>
                                </a>
                                <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" data-bs-toggle="modal" data-bs-target="#editPartyModal{{ $party->id }}" title="Edit Party">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if(abs($party->current_balance) > 0.001)
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 disabled" title="Cannot delete: active balance of Rs. {{ number_format($party->current_balance, 2) }}. Clear to Rs. 0.00 first.">
                                        <i class="bi bi-trash opacity-50"></i>
                                    </button>
                                @else
                                    <form action="{{ route('parties.destroy', $party->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this party?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete Party">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted small">
                            <i class="bi bi-person-x fs-3 d-block text-secondary opacity-50 mb-2"></i>
                            No parties found. Click <strong>"Add New Party"</strong> to register suppliers, traders, staff, or customers.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll) -->
    <div class="d-block d-md-none p-2 p-sm-3">
        <div class="d-flex flex-column gap-2">
            @forelse($parties as $party)
                <div class="p-3 rounded-3 border bg-white shadow-xs">
                    <!-- Top Row: Name & Type Badge -->
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 36px; height: 36px; font-size: 0.9rem; border: 1px solid rgba(37,99,235,0.2);">
                                {{ strtoupper(substr($party->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="fw-bold text-dark text-truncate" style="font-size: 0.92rem;">
                                    {{ $party->name }}
                                </div>
                                @if($party->phone)
                                    <a href="tel:{{ $party->phone }}" class="text-decoration-none text-muted font-monospace small" style="font-size: 0.74rem;">
                                        <i class="bi bi-telephone text-primary me-1"></i>{{ $party->phone }}
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="flex-shrink-0 text-end">
                            <div>{!! $party->type_badge !!}</div>
                            <div class="mt-1">{!! $party->dealing_badges !!}</div>
                        </div>
                    </div>

                    @if($party->address)
                        <div class="text-muted small text-truncate mb-2" style="font-size: 0.74rem;">
                            <i class="bi bi-geo-alt text-secondary me-1"></i>{{ $party->address }}
                        </div>
                    @endif

                    <!-- Balances Strip -->
                    <div class="p-2 rounded-2 bg-light border d-flex justify-content-between align-items-center mb-2" style="font-size: 0.78rem;">
                        <div>
                            <span class="text-muted d-block" style="font-size: 0.68rem;">Opening Balance</span>
                            <span class="font-monospace text-secondary">Rs. {{ number_format($party->opening_balance, 0) }}</span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted d-block" style="font-size: 0.68rem;">Current Balance</span>
                            <span class="font-monospace fw-bold {{ $party->current_balance < 0 ? 'text-danger' : 'text-dark' }}" style="font-size: 0.88rem;">
                                Rs. {{ number_format($party->current_balance, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="d-flex align-items-center gap-1.5 pt-1">
                        <a href="{{ route('ledgers.party', ['party_id' => $party->id]) }}" class="btn btn-outline-info btn-sm flex-fill py-1.5 px-2 rounded-2 fw-semibold" style="font-size: 0.76rem;">
                            <i class="bi bi-journal-text me-1"></i> Ledger
                        </a>
                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill py-1.5 px-2 rounded-2 fw-semibold" style="font-size: 0.76rem;" data-bs-toggle="modal" data-bs-target="#editPartyModal{{ $party->id }}">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>
                        @if(abs($party->current_balance) > 0.001)
                            <button type="button" class="btn btn-outline-secondary btn-sm py-1.5 px-2.5 rounded-2 disabled" style="font-size: 0.76rem;" title="Active balance Rs. {{ number_format($party->current_balance, 2) }}. Cannot delete.">
                                <i class="bi bi-trash opacity-50"></i>
                            </button>
                        @else
                            <form action="{{ route('parties.destroy', $party->id) }}" method="POST" class="flex-shrink-0 m-0" onsubmit="return confirm('Are you sure you want to delete this party?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm py-1.5 px-2.5 rounded-2" style="font-size: 0.76rem;" title="Delete Party">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-person-x fs-3 d-block text-secondary opacity-50 mb-2"></i>
                    No parties found. Click <strong>"Add New Party"</strong> to register suppliers, traders, staff, or customers.
                </div>
            @endforelse
        </div>
    </div>

    @if($parties->hasPages())
        <div class="p-3 border-top bg-light overflow-x-auto">
            {{ $parties->links() }}
        </div>
    @endif
</div>

<!-- Edit Party Modals (Rendered outside loop container to prevent duplicate IDs) -->
@foreach($parties as $party)
    <div class="modal fade" id="editPartyModal{{ $party->id }}" tabindex="-1" aria-labelledby="editPartyModalLabel{{ $party->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow text-start">
                <div class="modal-header border-bottom py-3">
                    <div>
                        <h6 class="modal-title fw-bold text-dark m-0" id="editPartyModalLabel{{ $party->id }}">Edit Party: {{ $party->name }}</h6>
                        <small class="text-muted" style="font-size: 0.78rem;">Update party credentials and contact details</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('parties.update', $party->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-3 p-sm-4 text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Party Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm py-2" name="name" value="{{ $party->name }}" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold text-secondary">Party Type <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-2" name="type" required>
                                    <option value="supplier" {{ $party->type == 'supplier' ? 'selected' : '' }}>Supplier</option>
                                    <option value="trader" {{ $party->type == 'trader' ? 'selected' : '' }}>Trader</option>
                                    <option value="staff" {{ $party->type == 'staff' ? 'selected' : '' }}>Staff (Advance)</option>
                                    <option value="customer" {{ $party->type == 'customer' ? 'selected' : '' }}>Customer</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold text-secondary">Phone Number</label>
                                <input type="text" class="form-control form-control-sm py-2" name="phone" value="{{ $party->phone }}" placeholder="0300-1234567">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Address / Location</label>
                            <input type="text" class="form-control form-control-sm py-2" name="address" value="{{ $party->address }}" placeholder="Shop / Market / City address">
                        </div>
                        <!-- Dealing Terms / Mode (Credit & Cash) -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary d-flex justify-content-between align-items-center mb-1">
                                <span>Dealing Mode <span class="text-danger">*</span></span>
                                <span class="text-muted fw-normal" style="font-size: 0.72rem;">Credit, Cash, or both</span>
                            </label>
                            <input type="hidden" name="dealing_mode_submitted" value="1">
                            <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center gap-4">
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" name="is_credit" id="edit_is_credit_{{ $party->id }}" value="1" {{ old('dealing_mode_submitted') ? (old('is_credit') ? 'checked' : '') : ($party->is_credit ? 'checked' : '') }}>
                                    <label class="form-check-label small fw-semibold text-dark user-select-none" for="edit_is_credit_{{ $party->id }}">
                                        <i class="bi bi-credit-card text-primary me-1"></i> Credit
                                    </label>
                                </div>
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" name="is_cash" id="edit_is_cash_{{ $party->id }}" value="1" {{ old('dealing_mode_submitted') ? (old('is_cash') ? 'checked' : '') : ($party->is_cash ? 'checked' : '') }}>
                                    <label class="form-check-label small fw-semibold text-dark user-select-none" for="edit_is_cash_{{ $party->id }}">
                                        <i class="bi bi-cash-stack text-success me-1"></i> Cash
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary d-flex justify-content-between align-items-center">
                                <span>Opening Balance (Rs.)</span>
                                <span class="badge bg-light text-muted border small"><i class="bi bi-lock-fill me-1"></i>Locked</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light fw-bold text-secondary">PKR</span>
                                <input type="text" class="form-control bg-light text-muted font-monospace" value="{{ number_format($party->opening_balance, 2) }}" readonly disabled>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">
                                <i class="bi bi-info-circle me-1"></i>Opening balance cannot be edited once party is created.
                            </small>
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

<!-- Create Party Modal -->
<div class="modal fade" id="createPartyModal" tabindex="-1" aria-labelledby="createPartyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow text-start">
            <div class="modal-header border-bottom py-3">
                <div>
                    <h6 class="modal-title fw-bold text-dark m-0" id="createPartyModalLabel">Register New Party</h6>
                    <small class="text-muted" style="font-size: 0.78rem;">Add suppliers, traders, staff, or customers</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createPartyForm" action="{{ route('parties.store') }}" method="POST">
                @csrf
                <div class="modal-body p-3 p-sm-4 text-start">
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 border-danger-subtle">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="mb-3 position-relative">
                        <label for="modal_party_name" class="form-label small fw-semibold text-secondary">
                            Party Name <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-control-sm py-2 @error('name') is-invalid @enderror" id="modal_party_name" name="name" value="{{ old('name') }}" placeholder="e.g. Apex Traders / John Doe" autocomplete="off" required>
                            <span class="input-group-text bg-white" id="modal_party_name_spinner" style="display: none;">
                                <span class="spinner-border spinner-border-sm text-primary" role="status" style="width: 0.9rem; height: 0.9rem;"></span>
                            </span>
                        </div>
                        
                        <!-- Suggestions / Live Duplicate Alert container -->
                        <div id="modal_party_name_feedback" class="mt-2" style="display: none;"></div>

                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="modal_party_type" class="form-label small fw-semibold text-secondary">Party Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm py-2 @error('type') is-invalid @enderror" id="modal_party_type" name="type" required>
                                <option value="" disabled {{ old('type') ? '' : 'selected' }}>-- Select Type --</option>
                                <option value="supplier" {{ old('type') == 'supplier' ? 'selected' : '' }}>Supplier</option>
                                <option value="trader" {{ old('type') == 'trader' ? 'selected' : '' }}>Trader</option>
                                <option value="staff" {{ old('type') == 'staff' ? 'selected' : '' }}>Staff (Advance)</option>
                                <option value="customer" {{ old('type') == 'customer' ? 'selected' : '' }}>Customer</option>
                            </select>
                            @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="modal_party_phone" class="form-label small fw-semibold text-secondary">Phone Number</label>
                            <input type="text" class="form-control form-control-sm py-2 @error('phone') is-invalid @enderror" id="modal_party_phone" name="phone" value="{{ old('phone') }}" placeholder="0300-1234567">
                            @error('phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="modal_party_address" class="form-label small fw-semibold text-secondary">Address / Location</label>
                        <input type="text" class="form-control form-control-sm py-2 @error('address') is-invalid @enderror" id="modal_party_address" name="address" value="{{ old('address') }}" placeholder="Shop / Market / City address">
                        @error('address')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- Dealing Terms / Mode (Credit & Cash) -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary d-flex justify-content-between align-items-center mb-1">
                            <span>Dealing Mode <span class="text-danger">*</span></span>
                            <span class="text-muted fw-normal" style="font-size: 0.72rem;">Select Credit, Cash, or both</span>
                        </label>
                        <input type="hidden" name="dealing_mode_submitted" value="1">
                        <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center gap-4">
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" name="is_credit" id="modal_is_credit" value="1" {{ old('dealing_mode_submitted') ? (old('is_credit') ? 'checked' : '') : 'checked' }}>
                                <label class="form-check-label small fw-semibold text-dark user-select-none" for="modal_is_credit">
                                    <i class="bi bi-credit-card text-primary me-1"></i> Credit
                                </label>
                            </div>
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" name="is_cash" id="modal_is_cash" value="1" {{ old('is_cash') ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold text-dark user-select-none" for="modal_is_cash">
                                    <i class="bi bi-cash-stack text-success me-1"></i> Cash
                                </label>
                            </div>
                        </div>
                        @error('dealing_mode')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <label for="modal_party_opening" class="form-label small fw-semibold text-secondary">Opening Balance (Rs.)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-secondary">PKR</span>
                            <input type="text" inputmode="decimal" class="form-control amount-format @error('opening_balance') is-invalid @enderror" id="modal_party_opening" name="opening_balance" value="{{ old('opening_balance', '0.00') }}" placeholder="0.00" autocomplete="off">
                        </div>
                        @error('opening_balance')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="modal_party_submit_btn" class="btn btn-primary btn-sm px-4 fw-semibold rounded-3 shadow-sm">
                        <i class="bi bi-save me-1"></i> Save Party
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function setupPartyDuplicateChecker(config) {
    const input = document.getElementById(config.inputId);
    const feedback = document.getElementById(config.feedbackId);
    const spinner = document.getElementById(config.spinnerId);
    const submitBtn = document.getElementById(config.submitBtnId);
    const form = document.getElementById(config.formId);
    const excludeId = config.excludeId || null;
    const checkUrl = "{{ route('parties.check-duplicate') }}";

    if (!input || !feedback) return;

    let debounceTimer = null;
    let abortController = null;
    let hasExactDuplicate = false;

    function resetState() {
        feedback.innerHTML = '';
        feedback.style.display = 'none';
        input.classList.remove('is-invalid', 'is-valid');
        if (spinner) spinner.style.display = 'none';
        hasExactDuplicate = false;
        if (submitBtn) submitBtn.disabled = false;
    }

    input.addEventListener('input', function() {
        const query = input.value.trim();

        if (debounceTimer) clearTimeout(debounceTimer);
        if (abortController) abortController.abort();

        if (query.length < 1) {
            resetState();
            return;
        }

        if (spinner) spinner.style.display = 'inline-block';

        debounceTimer = setTimeout(() => {
            abortController = new AbortController();
            let url = checkUrl + '?name=' + encodeURIComponent(query);
            if (excludeId) {
                url += '&exclude_id=' + encodeURIComponent(excludeId);
            }

            fetch(url, {
                signal: abortController.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (spinner) spinner.style.display = 'none';

                if (data.exact_match && data.exact_party) {
                    hasExactDuplicate = true;
                    input.classList.add('is-invalid');
                    input.classList.remove('is-valid');
                    if (submitBtn) submitBtn.disabled = true;

                    feedback.innerHTML = `
                        <div class="alert alert-danger p-2.5 mb-0 rounded-3 shadow-xs border border-danger-subtle">
                            <div class="d-flex align-items-center gap-1.5 fw-bold text-danger mb-1" style="font-size: 0.83rem;">
                                <i class="bi bi-exclamation-octagon-fill text-danger fs-6"></i>
                                <span>Already Registered! Duplicate not allowed.</span>
                            </div>
                            <div class="small text-dark mb-2" style="font-size: 0.8rem;">
                                A party named <strong class="text-danger">"${data.exact_party.name}"</strong> is already registered as 
                                <span class="badge bg-secondary">${data.exact_party.type}</span>
                                ${data.exact_party.phone && data.exact_party.phone !== 'N/A' ? `<span class="text-muted ms-1">(Phone: ${data.exact_party.phone})</span>` : ''}.
                            </div>
                            <div class="d-flex align-items-center justify-content-between pt-1.5 border-top border-danger-subtle">
                                <a href="${data.exact_party.ledger_url}" target="_blank" class="btn btn-outline-danger btn-xs py-1 px-2 fw-semibold text-decoration-none" style="font-size: 0.75rem;">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> View Existing Ledger
                                </a>
                                <span class="text-muted small" style="font-size: 0.73rem;">Change the name to proceed</span>
                            </div>
                        </div>
                    `;
                    feedback.style.display = 'block';
                } else if (data.suggestions && data.suggestions.length > 0) {
                    hasExactDuplicate = false;
                    input.classList.remove('is-invalid', 'is-valid');
                    if (submitBtn) submitBtn.disabled = false;

                    let listItems = data.suggestions.map(item => `
                        <div class="list-group-item d-flex justify-content-between align-items-center py-1.5 px-2.5 bg-white">
                            <div class="me-2 text-truncate">
                                <span class="fw-semibold text-dark" style="font-size: 0.82rem;">${item.name}</span>
                                <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.7rem;">${item.type}</span>
                                ${item.phone && item.phone !== 'N/A' ? `<small class="text-muted ms-2"><i class="bi bi-telephone"></i> ${item.phone}</small>` : ''}
                            </div>
                            <a href="${item.ledger_url}" target="_blank" class="btn btn-light btn-xs border text-primary fw-semibold py-0.5 px-1.5 text-nowrap" style="font-size: 0.72rem;">
                                Ledger <i class="bi bi-box-arrow-up-right ms-0.5"></i>
                            </a>
                        </div>
                    `).join('');

                    feedback.innerHTML = `
                        <div class="card border border-warning-subtle bg-warning bg-opacity-10 rounded-3 shadow-xs p-2 mb-0">
                            <div class="d-flex align-items-center justify-content-between mb-1.5 px-1">
                                <span class="small fw-bold text-dark d-flex align-items-center" style="font-size: 0.78rem;">
                                    <i class="bi bi-search text-warning me-1.5"></i>
                                    Existing similar parties (${data.suggestions.length} found):
                                </span>
                                <small class="text-muted" style="font-size: 0.72rem;">Check to prevent duplicate</small>
                            </div>
                            <div class="list-group list-group-flush rounded border overflow-hidden">
                                ${listItems}
                            </div>
                            <div class="text-muted px-1 mt-1.5 small" style="font-size: 0.73rem;">
                                <i class="bi bi-info-circle text-primary me-1"></i> If you are registering a different party, you can proceed.
                            </div>
                        </div>
                    `;
                    feedback.style.display = 'block';
                } else {
                    hasExactDuplicate = false;
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    if (submitBtn) submitBtn.disabled = false;

                    feedback.innerHTML = `
                        <div class="text-success small d-flex align-items-center px-1" style="font-size: 0.78rem;">
                            <i class="bi bi-check-circle-fill me-1.5 text-success"></i>
                            <span>Party name is unique & available.</span>
                        </div>
                    `;
                    feedback.style.display = 'block';
                }
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    if (spinner) spinner.style.display = 'none';
                }
            });
        }, 250);
    });

    if (form) {
        form.addEventListener('submit', function(e) {
            if (hasExactDuplicate) {
                e.preventDefault();
                input.focus();
                alert('A party with this exact name already exists. Please choose a different name.');
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setupPartyDuplicateChecker({
        inputId: 'modal_party_name',
        feedbackId: 'modal_party_name_feedback',
        spinnerId: 'modal_party_name_spinner',
        submitBtnId: 'modal_party_submit_btn',
        formId: 'createPartyForm'
    });

    @if ($errors->any())
        const modalEl = document.getElementById('createPartyModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    @endif
});
</script>
@endpush
