@extends('layouts.app')

@section('title', 'Expense & Purchase Categories')
@section('page_title', 'Expense & Purchase Categories Setup')

@section('page_badge')
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1 small">
            <i class="bi bi-tag-fill me-1"></i> Expenses: {{ $expenseCount }}
        </span>
        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle px-2 py-1 small">
            <i class="bi bi-cart-check-fill me-1 text-warning"></i> Purchases: {{ $purchaseCount }}
        </span>
    </div>
@endsection

@section('page_actions')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 shadow-sm text-nowrap">
            <i class="bi bi-arrow-left-right me-1"></i> Daily Vouchers
        </a>
        <a href="{{ route('reports.expenses') }}" class="btn btn-danger btn-sm rounded-3 shadow-sm text-nowrap">
            <i class="bi bi-receipt-cutoff me-1"></i> View Expense Reports
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
    .nav-tabs .nav-link {
        font-weight: 600;
        font-size: 0.88rem;
        color: #64748b;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 0.75rem 1rem;
    }
    .nav-tabs .nav-link.active {
        color: #0f172a;
        background: transparent;
        border-bottom: 2px solid #2563eb;
    }
</style>
@endpush

@section('content')
<div class="row g-3 g-lg-4">
    <!-- Quick Add Category Form -->
    <div class="col-12 col-lg-4">
        <div class="card-custom">
            <div class="p-3 border-bottom">
                <h6 class="fw-bold text-dark m-0">Add Category</h6>
                <small class="text-muted" style="font-size: 0.78rem;">Create categories for expenses or purchase bills</small>
            </div>

            <div class="p-3 p-md-4">
                <form action="{{ route('expense-categories.store') }}" method="POST">
                    @csrf

                    <!-- Category Type Selector -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary d-block">Category Type <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="type" id="type_expense" value="expense" {{ old('type', $activeTab) === 'expense' ? 'checked' : '' }} required>
                                <label class="btn btn-outline-danger btn-sm w-100 py-2 d-flex align-items-center justify-content-center gap-1.5" for="type_expense">
                                    <i class="bi bi-tag-fill"></i>
                                    <span>Expense</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="type" id="type_purchase" value="purchase" {{ old('type', $activeTab) === 'purchase' ? 'checked' : '' }} required>
                                <label class="btn btn-outline-warning btn-sm w-100 py-2 text-dark d-flex align-items-center justify-content-center gap-1.5" for="type_purchase">
                                    <i class="bi bi-cart-check-fill text-warning"></i>
                                    <span>Purchase</span>
                                </label>
                            </div>
                        </div>
                        @error('type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-secondary">Category Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm py-2 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Shop Rent, Office Refreshments, Raw Materials" required>
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label small fw-semibold text-secondary">Description / Remarks</label>
                        <textarea class="form-control form-control-sm py-2" id="description" name="description" rows="3" placeholder="Optional notes about this category...">{{ old('description') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold rounded-3 py-2 shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> Save Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Category Directory Tabs -->
    <div class="col-12 col-lg-8">
        <div class="card-custom overflow-hidden">
            <!-- Nav Tabs -->
            <div class="border-bottom px-3 pt-2 bg-light bg-opacity-50">
                <ul class="nav nav-tabs border-bottom-0" id="categoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === 'expense' ? 'active' : '' }}" href="{{ route('expense-categories.index', ['tab' => 'expense']) }}">
                            <i class="bi bi-tag-fill text-danger me-1"></i> Expense Categories
                            <span class="badge bg-danger-subtle text-danger ms-1.5 rounded-pill">{{ $expenseCount }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === 'purchase' ? 'active' : '' }}" href="{{ route('expense-categories.index', ['tab' => 'purchase']) }}">
                            <i class="bi bi-cart-check-fill text-warning me-1"></i> Purchase Categories
                            <span class="badge bg-warning-subtle text-dark ms-1.5 rounded-pill">{{ $purchaseCount }}</span>
                        </a>
                    </li>
                </ul>
            </div>

            @php
                $currentCategories = $activeTab === 'purchase' ? $purchaseCategories : $expenseCategories;
                $isPurchaseTab = $activeTab === 'purchase';
            @endphp

            <!-- Table Header Info -->
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold text-dark m-0">
                        {{ $isPurchaseTab ? 'Registered Purchase Categories' : 'Registered Expense Categories' }}
                    </h6>
                    <small class="text-muted" style="font-size: 0.78rem;">
                        {{ $isPurchaseTab ? 'Categories used when recording credit purchase bills' : 'Categories used when recording direct operational expenses and vouchers' }}
                    </small>
                </div>
                <span class="badge {{ $isPurchaseTab ? 'bg-warning text-dark' : 'bg-danger text-white' }} px-2 py-1 small">
                    {{ $isPurchaseTab ? 'Purchase Type' : 'Expense Type' }}
                </span>
            </div>

            <!-- Desktop View: Table -->
            <div class="d-none d-md-block table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Category Title</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-end" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($currentCategories as $index => $cat)
                            <tr>
                                <td class="text-muted small">{{ $currentCategories->firstItem() + $index }}</td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        @if($cat->type === 'purchase')
                                            <i class="bi bi-cart-fill text-warning me-2"></i>{{ $cat->name }}
                                        @else
                                            <i class="bi bi-tag-fill text-danger opacity-75 me-2"></i>{{ $cat->name }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($cat->type === 'purchase')
                                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning-subtle px-2 py-1">Purchase</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1">Expense</span>
                                    @endif
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
                                <td colspan="5" class="text-center py-4 text-muted small">
                                    No {{ $isPurchaseTab ? 'purchase' : 'expense' }} categories registered yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile View: Responsive Cards -->
            <div class="d-block d-md-none p-2 p-sm-3">
                <div class="d-flex flex-column gap-2">
                    @forelse($currentCategories as $cat)
                        <div class="p-3 rounded-3 border bg-white shadow-xs">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <div class="{{ $cat->type === 'purchase' ? 'bg-warning-subtle text-dark' : 'bg-danger-subtle text-danger' }} rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px;">
                                        <i class="bi {{ $cat->type === 'purchase' ? 'bi-cart-fill' : 'bi-tag-fill' }}"></i>
                                    </div>
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 0.92rem;">
                                        {{ $cat->name }}
                                    </div>
                                </div>
                                <span class="badge {{ $cat->type === 'purchase' ? 'bg-warning-subtle text-dark' : 'bg-danger-subtle text-danger' }} px-2 py-0.5">
                                    {{ ucfirst($cat->type) }}
                                </span>
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
                        <div class="text-center py-4 text-muted small">
                            No {{ $isPurchaseTab ? 'purchase' : 'expense' }} categories registered yet.
                        </div>
                    @endforelse
                </div>
            </div>

            @if($currentCategories->hasPages())
                <div class="p-3 border-top bg-light overflow-x-auto">
                    {{ $currentCategories->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Edit Modals -->
@foreach($currentCategories as $cat)
    <div class="modal fade" id="editCatModal{{ $cat->id }}" tabindex="-1" aria-labelledby="modalLabel{{ $cat->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow text-start">
                <div class="modal-header border-bottom py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi {{ $cat->type === 'purchase' ? 'bi-cart-fill text-warning' : 'bi-tag-fill text-danger' }} fs-5"></i>
                        <div>
                            <h6 class="modal-title fw-bold text-dark m-0" id="modalLabel{{ $cat->id }}">Edit Category: {{ $cat->name }}</h6>
                            <small class="text-muted" style="font-size: 0.78rem;">Update category title, type or remarks</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('expense-categories.update', $cat->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-3 p-sm-4 text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary d-block">Category Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select form-select-sm py-2" required>
                                <option value="expense" {{ $cat->type === 'expense' ? 'selected' : '' }}>Expense Category</option>
                                <option value="purchase" {{ $cat->type === 'purchase' ? 'selected' : '' }}>Purchase Category</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Category Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm py-2" name="name" value="{{ $cat->name }}" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary">Description</label>
                            <textarea class="form-control form-control-sm py-2" name="description" rows="3">{{ $cat->description }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-2 bg-light">
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
