@extends('layouts.app')

@section('title', 'Fuel & Inventory Purchases')
@section('page_title', 'Stock & Fuel Purchase Management')

@section('page_badge')
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1 small">
            <i class="bi bi-truck me-1"></i> Total Orders: {{ $totalPurchasesCount }}
        </span>
        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle px-2 py-1 small">
            <i class="bi bi-exclamation-circle text-warning me-1"></i> Unpaid Khata: Rs. {{ number_format($totalPayableDue, 2) }}
        </span>
    </div>
@endsection

@section('page_actions')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm rounded-3 shadow-sm text-nowrap">
            <i class="bi bi-plus-circle me-1"></i> Record New Purchase / Delivery
        </a>
    </div>
@endsection

@push('styles')
<style>
    .metric-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.1rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    .metric-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .table-custom thead th {
        background: #f8fafc;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        border-bottom: 2px solid #e2e8f0;
        padding: 0.75rem 1rem;
    }
    .table-custom tbody td {
        padding: 0.85rem 1rem;
        vertical-align: middle;
        font-size: 0.875rem;
        border-bottom: 1px solid #f1f5f9;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm mb-3 d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill fs-5 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metric Cards Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-semibold">Total Purchases</span>
                    <div class="metric-icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-cart-check"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-dark m-0">Rs. {{ number_format($totalPurchasesAmount, 2) }}</h4>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">Gross purchase invoices</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-semibold">Paid Amount</span>
                    <div class="metric-icon-box bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-success m-0">Rs. {{ number_format($totalPaidAmount, 2) }}</h4>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">Cash & bank payouts</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-semibold">Payable to Suppliers</span>
                    <div class="metric-icon-box bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-warning-emphasis m-0">Rs. {{ number_format($totalPayableDue, 2) }}</h4>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">Outstanding credit balance</div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-semibold">Total Deliveries</span>
                    <div class="metric-icon-box bg-info bg-opacity-10 text-info">
                        <i class="bi bi-truck"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-dark m-0">{{ number_format($totalPurchasesCount) }}</h4>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">Bowser / stock receipts</div>
            </div>
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="card-custom">
        <div class="p-3 border-bottom bg-white rounded-top-3">
            <form action="{{ route('purchases.index') }}" method="GET" class="row g-2 align-items-center">
                <!-- Search -->
                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control form-control-sm border-start-0 ps-0"
                               placeholder="Bill no, vehicle, product..." value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Supplier Filter -->
                <div class="col-6 col-md-2.5">
                    <select name="supplier_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Product Filter -->
                <div class="col-6 col-md-2.5">
                    <select name="product_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Products</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>
                                {{ $prod->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- From Date -->
                <div class="col-6 col-md-1.5">
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}" placeholder="From Date">
                </div>

                <!-- To Date -->
                <div class="col-6 col-md-1.5">
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}" placeholder="To Date">
                </div>

                <!-- Filter / Clear -->
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm w-100" title="Apply Filter">
                        <i class="bi bi-filter"></i>
                    </button>
                    @if(request()->hasAny(['search', 'supplier_id', 'product_id', 'from_date', 'to_date']))
                        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-custom align-middle m-0">
                <thead>
                    <tr>
                        <th style="min-width: 100px;">Date</th>
                        <th style="min-width: 120px;">Bill / Invoice No.</th>
                        <th style="min-width: 160px;">Supplier / OMC</th>
                        <th style="min-width: 180px;">Product & Volume</th>
                        <th style="min-width: 110px;">Tanker / Vehicle</th>
                        <th style="min-width: 110px;" class="text-end">Unit Rate</th>
                        <th style="min-width: 130px;" class="text-end">Total Bill</th>
                        <th style="min-width: 130px;" class="text-end">Paid Amount</th>
                        <th style="min-width: 130px;" class="text-center">Status</th>
                        <th style="min-width: 90px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $p)
                        <tr>
                            <td>
                                <span class="fw-semibold text-dark">{{ $p->purchase_date->format('d M Y') }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border font-monospace">
                                    {{ $p->invoice_no ?: ('PO #' . $p->id) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('ledgers.party', $p->supplier_id) }}" class="fw-bold text-dark text-decoration-none">
                                    <i class="bi bi-truck text-primary me-1"></i>{{ $p->supplier->name ?? 'N/A' }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $p->product->name ?? 'N/A' }}</div>
                                <span class="badge bg-primary bg-opacity-10 text-primary small">
                                    +{{ number_format($p->quantity, 2) }} {{ $p->product->unit_label ?? 'Ltr' }}
                                </span>
                                @if($p->tank_no || $p->pump)
                                    <div class="small text-muted mt-1" style="font-size: 0.68rem;">
                                        <i class="bi bi-database text-secondary me-0.5"></i>{{ $p->pump?->name ?: $p->tank_no }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($p->vehicle_no)
                                    <span class="text-secondary small font-monospace">
                                        <i class="bi bi-truck-front text-info me-1"></i>{{ $p->vehicle_no }}
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace text-muted">
                                Rs. {{ number_format($p->unit_price, 2) }}
                            </td>
                            <td class="text-end font-monospace fw-bold text-dark">
                                Rs. {{ number_format($p->total_amount, 2) }}
                            </td>
                            <td class="text-end font-monospace {{ $p->paid_amount > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                                Rs. {{ number_format($p->paid_amount, 2) }}
                                @if($p->paymentAccount)
                                    <div class="text-secondary small" style="font-size: 0.7rem;">via {{ $p->paymentAccount->name }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                {!! $p->payment_status_badge !!}
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <a href="{{ route('purchases.show', $p) }}" class="btn btn-sm btn-outline-info p-1 px-2" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form action="{{ route('purchases.destroy', $p) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this purchase? Stock and supplier balance will be reversed.');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2" title="Delete Purchase">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="py-4">
                                    <div class="display-6 text-muted mb-3"><i class="bi bi-truck text-primary"></i></div>
                                    <h5 class="fw-bold text-dark">No Purchases Recorded</h5>
                                    <p class="text-muted small mb-3">No incoming stock or fuel purchases found for this filter.</p>
                                    <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm">
                                        <i class="bi bi-plus-circle me-1"></i> Record First Purchase
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
            <div class="p-3 border-top bg-white rounded-bottom-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $purchases->firstItem() }} to {{ $purchases->lastItem() }} of {{ $purchases->total() }} purchases
                </div>
                <div>{{ $purchases->links() }}</div>
            </div>
        @endif
    </div>

</div>
@endsection
