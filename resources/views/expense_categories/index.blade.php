@extends('layouts.app')

@section('title', 'Expense Categories')
@section('page_title', 'Expense Categories Setup')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-tags me-1 text-primary"></i> Total: {{ $categories->total() }} Categories
    </span>
@endsection

@section('page_actions')
    <a href="{{ route('reports.expenses') }}" class="btn btn-danger btn-sm rounded-3 shadow-sm text-nowrap w-100 w-sm-auto">
        <i class="bi bi-receipt-cutoff me-1"></i> View Expense Reports
    </a>
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
    <!-- Quick Add Category Form -->
    <div class="col-12 col-lg-4">
        <div class="card-custom">
            <div class="p-3 border-bottom">
                <h6 class="fw-bold text-dark m-0">Add Expense Category</h6>
                <small class="text-muted" style="font-size: 0.78rem;">Define expense types for shift closing & payments</small>
            </div>

            <div class="p-3 p-md-4">
                <form action="{{ route('expense-categories.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-secondary">Category Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm py-2 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Office Refreshments, Shop Rent, Utility Bills" required>
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label small fw-semibold text-secondary">Description / Remarks</label>
                        <textarea class="form-control form-control-sm py-2" id="description" name="description" rows="3" placeholder="Optional notes about this expense category...">{{ old('description') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold rounded-3 py-2 shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> Save Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Category Directory -->
    <div class="col-12 col-lg-8">
        <div class="card-custom overflow-hidden">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold text-dark m-0">Registered Categories</h6>
                    <small class="text-muted" style="font-size: 0.78rem;">Standard categories for daily vouchers and shift expenses</small>
                </div>
            </div>

            <!-- Desktop View: Table -->
            <div class="d-none d-md-block table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Category Title</th>
                            <th>Description</th>
                            <th class="text-end" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $index => $cat)
                            <tr>
                                <td class="text-muted small">{{ $categories->firstItem() + $index }}</td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <i class="bi bi-tag-fill text-primary opacity-50 me-2"></i>{{ $cat->name }}
                                    </div>
                                </td>
                                <td class="text-muted small">
                                    {{ $cat->description ?? '-' }}
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" data-bs-toggle="modal" data-bs-target="#editCatModal{{ $cat->id }}" title="Edit Category">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('expense-categories.destroy', $cat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete Category">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted small">No expense categories registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll) -->
            <div class="d-block d-md-none p-2 p-sm-3">
                <div class="d-flex flex-column gap-2">
                    @forelse($categories as $cat)
                        <div class="p-3 rounded-3 border bg-white shadow-xs">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <div class="bg-primary-subtle text-primary rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px;">
                                        <i class="bi bi-tag-fill"></i>
                                    </div>
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 0.92rem;">
                                        {{ $cat->name }}
                                    </div>
                                </div>
                            </div>

                            @if($cat->description)
                                <div class="text-muted small mb-2 p-2 rounded-2 bg-light border" style="font-size: 0.74rem;">
                                    {{ $cat->description }}
                                </div>
                            @endif

                            <div class="d-flex align-items-center gap-1.5 pt-1">
                                <button type="button" class="btn btn-outline-primary btn-sm flex-fill py-1.5 px-2 rounded-2 fw-semibold" style="font-size: 0.76rem;" data-bs-toggle="modal" data-bs-target="#editCatModal{{ $cat->id }}">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </button>
                                <form action="{{ route('expense-categories.destroy', $cat->id) }}" method="POST" class="flex-shrink-0 m-0" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-1.5 px-2.5 rounded-2" style="font-size: 0.76rem;" title="Delete Category">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">No expense categories registered yet.</div>
                    @endforelse
                </div>
            </div>

            @if($categories->hasPages())
                <div class="p-3 border-top bg-light overflow-x-auto">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Edit Modals (Rendered outside table/card containers to prevent duplicate IDs) -->
@foreach($categories as $cat)
    <div class="modal fade" id="editCatModal{{ $cat->id }}" tabindex="-1" aria-labelledby="modalLabel{{ $cat->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow text-start">
                <div class="modal-header border-bottom py-3">
                    <div>
                        <h6 class="modal-title fw-bold text-dark m-0" id="modalLabel{{ $cat->id }}">Edit Category: {{ $cat->name }}</h6>
                        <small class="text-muted" style="font-size: 0.78rem;">Update category title or remarks</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('expense-categories.update', $cat->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-3 p-sm-4 text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Category Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm py-2" name="name" value="{{ $cat->name }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary">Description</label>
                            <textarea class="form-control form-control-sm py-2" name="description" rows="3">{{ $cat->description }}</textarea>
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
@endsection
