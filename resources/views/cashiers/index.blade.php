@extends('layouts.app')

@section('title', 'Cashiers Setup')
@section('page_title', 'Shift Cashiers Setup')

@section('page_badge')
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1 small">
            <i class="bi bi-person-badge me-1"></i> Total Cashiers: {{ $totalCashiers }}
        </span>
        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1 small">
            <i class="bi bi-check-circle me-1"></i> Active: {{ $activeCashiers }}
        </span>
    </div>
@endsection

@section('page_actions')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('shift-closings.create') }}" class="btn btn-primary btn-sm rounded-3 shadow-sm text-nowrap">
            <i class="bi bi-plus-circle me-1"></i> New Shift Closing
        </a>
    </div>
@endsection

@push('styles')
<style>
    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    @media (max-width: 575.98px) {
        .card-custom {
            border-radius: 10px;
        }
    }
</style>
@endpush

@section('content')
<div class="row g-3 g-lg-4">
    <!-- Quick Add Cashier Form -->
    <div class="col-12 col-lg-4">
        <div class="card-custom">
            <div class="p-3 border-bottom">
                <h6 class="fw-bold text-dark m-0">
                    <i class="bi bi-person-plus text-primary me-1"></i> Add New Cashier
                </h6>
                <small class="text-muted" style="font-size: 0.78rem;">Register cashiers who handle counter sales & shift closing</small>
            </div>

            <div class="p-3 p-md-4">
                <form action="{{ route('cashiers.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-secondary">Cashier Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm py-2 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Muhammad Ali" required>
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="code" class="form-label small fw-semibold text-secondary">Cashier Code / ID</label>
                        <input type="text" class="form-control form-control-sm py-2 @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" placeholder="Auto-generated if left empty (e.g. CSH-001)">
                        <small class="text-muted" style="font-size: 0.72rem;">Optional unique cashier counter ID</small>
                        @error('code')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label small fw-semibold text-secondary">Phone Number</label>
                        <input type="text" class="form-control form-control-sm py-2 @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="e.g. 0300-1234567">
                        @error('phone')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Initial Status</label>
                        <select name="status" class="form-select form-select-sm py-2" required>
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Available in Shifts)</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100 py-2 fw-semibold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-check2-circle"></i>
                        <span>Save Cashier</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Cashiers Directory Table -->
    <div class="col-12 col-lg-8">
        <div class="card-custom">
            <div class="p-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                <div>
                    <h6 class="fw-bold text-dark m-0">Shift Cashiers Directory</h6>
                    <small class="text-muted" style="font-size: 0.78rem;">List of all authorized cashiers for shift handovers</small>
                </div>

                <!-- Search and Filters -->
                <form action="{{ route('cashiers.index') }}" method="GET" class="d-flex gap-2">
                    <input type="search" name="search" class="form-control form-control-sm" placeholder="Search name or code..." value="{{ request('search') }}" style="max-width: 180px;">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 110px;">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Search">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request('search') || request('status'))
                        <a href="{{ route('cashiers.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear filter">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 80px;">Code</th>
                            <th>Cashier Name</th>
                            <th>Phone</th>
                            <th class="text-center" style="width: 110px;">Status</th>
                            <th class="text-end pe-3" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashiers as $cashier)
                            <tr>
                                <td class="ps-3 font-monospace small fw-bold text-secondary">
                                    {{ $cashier->code ?: '#' . $cashier->id }}
                                </td>
                                <td>
                                    <div class="fw-bold text-dark d-flex align-items-center gap-1.5">
                                        <i class="bi bi-person-badge text-primary"></i>
                                        <span>{{ $cashier->name }}</span>
                                    </div>
                                </td>
                                <td class="small text-secondary">
                                    {{ $cashier->phone ?: '-' }}
                                </td>
                                <td class="text-center">
                                    @if($cashier->status === 'active')
                                        <span class="badge bg-success-subtle text-success border border-success small">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary small">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <!-- Toggle Status -->
                                        <form action="{{ route('cashiers.toggle-status', $cashier) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary btn-sm p-1 rounded-2" title="{{ $cashier->status === 'active' ? 'Mark Inactive' : 'Mark Active' }}">
                                                <i class="bi {{ $cashier->status === 'active' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' }} fs-6"></i>
                                            </button>
                                        </form>

                                        <!-- Edit Modal Trigger -->
                                        <button type="button" class="btn btn-outline-primary btn-sm p-1 px-2 rounded-2" data-bs-toggle="modal" data-bs-target="#editModal{{ $cashier->id }}" title="Edit Cashier">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <!-- Delete -->
                                        <form action="{{ route('cashiers.destroy', $cashier) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this cashier?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm p-1 px-2 rounded-2" title="Delete Cashier">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Edit Modal -->
                                    <div class="modal fade text-start" id="editModal{{ $cashier->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $cashier->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('cashiers.update', $cashier) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold" id="editModalLabel{{ $cashier->id }}">
                                                            <i class="bi bi-pencil-square text-primary me-1"></i> Edit Cashier
                                                        </h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Cashier Name <span class="text-danger">*</span></label>
                                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $cashier->name) }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Cashier Code</label>
                                                            <input type="text" name="code" class="form-control form-control-sm" value="{{ old('code', $cashier->code) }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Phone Number</label>
                                                            <input type="text" name="phone" class="form-control form-control-sm" value="{{ old('phone', $cashier->phone) }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Status <span class="text-danger">*</span></label>
                                                            <select name="status" class="form-select form-select-sm" required>
                                                                <option value="active" {{ old('status', $cashier->status) === 'active' ? 'selected' : '' }}>Active</option>
                                                                <option value="inactive" {{ old('status', $cashier->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary btn-sm px-3">Update Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-person-x fs-3 d-block mb-1"></i>
                                    No cashiers found. Add your first cashier using the form on the left.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($cashiers->hasPages())
                <div class="p-3 border-top">
                    {{ $cashiers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
