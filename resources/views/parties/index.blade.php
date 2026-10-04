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
        <a href="{{ route('parties.index') }}" class="card-custom filter-card p-2.5 text-decoration-none d-block text-center h-100 {{ !request('type') ? 'border-primary bg-primary bg-opacity-10' : '' }}">
            <div class="text-secondary small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.3px;">ALL PARTIES</div>
            <div class="fw-bold fs-6 text-dark mt-0.5">{{ $totalParties }}</div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('parties.index', ['type' => 'supplier']) }}" class="card-custom filter-card p-2.5 text-decoration-none d-block text-center h-100 {{ request('type') == 'supplier' ? 'border-danger bg-danger bg-opacity-10' : '' }}">
            <div class="text-danger small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.3px;">SUPPLIERS</div>
            <div class="fw-bold fs-6 text-dark mt-0.5">{{ $totalSuppliers }}</div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('parties.index', ['type' => 'trader']) }}" class="card-custom filter-card p-2.5 text-decoration-none d-block text-center h-100 {{ request('type') == 'trader' ? 'border-primary bg-primary bg-opacity-10' : '' }}">
            <div class="text-primary small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.3px;">TRADERS</div>
            <div class="fw-bold fs-6 text-dark mt-0.5">{{ $totalTraders }}</div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('parties.index', ['type' => 'staff']) }}" class="card-custom filter-card p-2.5 text-decoration-none d-block text-center h-100 {{ request('type') == 'staff' ? 'border-warning bg-warning bg-opacity-10' : '' }}">
            <div class="text-warning-emphasis small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.3px;">STAFF (ADVANCE)</div>
            <div class="fw-bold fs-6 text-dark mt-0.5">{{ $totalStaff }}</div>
        </a>
    </div>
    <div class="col col-12 col-sm-auto flex-sm-grow-1 flex-lg-grow-0">
        <a href="{{ route('parties.index', ['type' => 'customer']) }}" class="card-custom filter-card p-2.5 text-decoration-none d-block text-center h-100 {{ request('type') == 'customer' ? 'border-success bg-success bg-opacity-10' : '' }}">
            <div class="text-success small fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.3px;">CUSTOMERS</div>
            <div class="fw-bold fs-6 text-dark mt-0.5">{{ $totalCustomers }}</div>
        </a>
    </div>
</div>

<!-- Search & Data Card -->
<div class="card-custom overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h6 class="fw-bold text-dark m-0">Party Accounts</h6>
            @if(request('type'))
                <span class="badge bg-secondary text-uppercase" style="font-size: 0.7rem;">{{ request('type') }}</span>
                <a href="{{ route('parties.index') }}" class="text-muted small text-decoration-none"><i class="bi bi-x-circle"></i> Clear</a>
            @endif
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('parties.index') }}" class="d-flex gap-2 w-100 w-sm-auto" style="max-width: 320px;">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            <div class="input-group input-group-sm w-100">
                <input type="text" name="search" class="form-control" placeholder="Search by name or phone..." value="{{ request('search') }}">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                @if(request('search'))
                    <a href="{{ route('parties.index', request()->only('type')) }}" class="btn btn-outline-secondary" title="Clear Search"><i class="bi bi-x"></i></a>
                @endif
            </div>
        </form>
    </div>

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Party Name</th>
                    <th>Category Type</th>
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
                                <form action="{{ route('parties.destroy', $party->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this party?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete Party">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted small">
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
                        <div class="flex-shrink-0">
                            {!! $party->type_badge !!}
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
                        <form action="{{ route('parties.destroy', $party->id) }}" method="POST" class="flex-shrink-0 m-0" onsubmit="return confirm('Are you sure you want to delete this party?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm py-1.5 px-2.5 rounded-2" style="font-size: 0.76rem;" title="Delete Party">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
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
                        <small class="text-muted" style="font-size: 0.78rem;">Update party credentials, category, or opening balance</small>
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
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary">Opening Balance (Rs.)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light fw-bold text-secondary">PKR</span>
                                <input type="number" step="0.01" class="form-control" name="opening_balance" value="{{ $party->opening_balance }}">
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
            <form action="{{ route('parties.store') }}" method="POST">
                @csrf
                <div class="modal-body p-3 p-sm-4 text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Party Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm py-2" name="name" placeholder="e.g. Apex Traders / John Doe" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Party Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm py-2" name="type" required>
                                <option value="" disabled selected>-- Select Type --</option>
                                <option value="supplier">Supplier</option>
                                <option value="trader">Trader</option>
                                <option value="staff">Staff (Advance)</option>
                                <option value="customer">Customer</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Phone Number</label>
                            <input type="text" class="form-control form-control-sm py-2" name="phone" placeholder="0300-1234567">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Address / Location</label>
                        <input type="text" class="form-control form-control-sm py-2" name="address" placeholder="Shop / Market / City address">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Opening Balance (Rs.)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-secondary">PKR</span>
                            <input type="number" step="0.01" class="form-control" name="opening_balance" value="0.00" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold rounded-3 shadow-sm">
                        <i class="bi bi-save me-1"></i> Save Party
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
