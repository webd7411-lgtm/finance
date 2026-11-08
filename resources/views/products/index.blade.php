@extends('layouts.app')

@section('title', 'Petrol Pump Products & Rates')
@section('page_title', 'Products & Fuel Inventory Management')

@section('page_badge')
    <div class="d-flex align-items-center gap-1.5 flex-wrap">
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem; font-weight: 600;">
            <i class="bi bi-fuel-pump me-1"></i> Fuel Products: {{ $totalFuel }}
        </span>
        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem; font-weight: 600;">
            <i class="bi bi-droplet-half text-warning me-1"></i> Lubricants: {{ $totalLubricants }}
        </span>
        @if($totalLowStock > 0)
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem; font-weight: 600;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Low Stock: {{ $totalLowStock }}
            </span>
        @endif
    </div>
@endsection

@section('page_actions')
    <div class="d-flex gap-2">
        <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm px-3 py-1.5 rounded-3 shadow-xs fw-semibold text-nowrap d-inline-flex align-items-center gap-1.5" style="font-size: 0.81rem; background-color: #2563eb; border-color: #2563eb;">
            <i class="bi bi-plus-circle-fill"></i> Add Petrol Pump Product
        </a>
    </div>
@endsection

@push('styles')
<!-- Google Font: Plus Jakarta Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

<style>
    /* Strict font family as requested: body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; } */
    body {
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif !important;
        background-color: #f8fafc;
        color: #0f172a;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    html, body, button, input, optgroup, select, textarea, table, th, td, h1, h2, h3, h4, h5, h6, p, span, a, label {
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif !important;
    }

    /* Icon font safeguards */
    .bi, [class^="bi-"], [class*=" bi-"] {
        font-family: "bootstrap-icons" !important;
    }
    .fa, .fas, .fa-solid, .far, .fa-regular, .fab, .fa-brands, [class^="fa-"], [class*=" fa-"] {
        font-family: "Font Awesome 6 Free", "FontAwesome" !important;
    }

    /* Minimal scroll spacing */
    .sub-header {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.45rem 0 !important;
        margin-bottom: 0.55rem !important;
    }
    main.container-fluid {
        margin-bottom: 0.65rem !important;
    }

    .shadow-xs {
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .card-custom {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }

    /* 5-Cards Unified Grid in ONE Single Row */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.55rem;
    }
    @media (max-width: 1199px) {
        .metrics-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 767px) {
        .metrics-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 480px) {
        .metrics-grid {
            grid-template-columns: 1fr;
        }
    }

    .metric-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.55rem 0.8rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 70px;
    }
    .metric-card:hover {
        transform: translateY(-1.5px);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        border-color: #cbd5e1;
    }

    /* Special Accent for Active Inventory Value in the 5th slot */
    .metric-card-valuation {
        background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
        border: 1px solid #1d4ed8;
        color: #ffffff;
    }
    .metric-card-valuation:hover {
        border-color: #1e40af;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
    }

    .metric-label {
        font-size: 0.73rem;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
    }
    .metric-icon-box {
        width: 30px;
        height: 30px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .metric-value {
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1.15;
        margin: 2px 0 1px 0;
        letter-spacing: -0.02em;
    }
    .metric-value-currency {
        font-size: 1.05rem;
        font-weight: 800;
        line-height: 1.15;
        margin: 2px 0 1px 0;
        letter-spacing: -0.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .metric-sub {
        font-size: 0.68rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #64748b;
        font-weight: 500;
    }

    /* Category Filter Pills (Modern Rounded Pill Navigation) */
    .category-pills-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .filter-pill {
        font-size: 0.76rem;
        font-weight: 600;
        padding: 0.32rem 0.75rem;
        border-radius: 50px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #475569;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        transition: all 0.15s ease;
        line-height: 1.2;
    }
    .filter-pill:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .filter-pill.active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.28);
    }

    /* Filter Form Elements */
    .filter-input {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        font-size: 0.78rem;
        padding: 0.35rem 0.65rem;
        background-color: #ffffff;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .filter-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }
    .btn-filter-submit {
        background-color: #0f172a;
        color: #ffffff;
        border: 1px solid #0f172a;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.78rem;
        padding: 0.35rem 0.85rem;
        transition: all 0.15s ease;
    }
    .btn-filter-submit:hover {
        background-color: #1e293b;
        color: #ffffff;
        border-color: #1e293b;
    }

    /* Table Styles */
    .table-custom {
        margin-bottom: 0;
    }
    .table-custom thead th {
        background: #f8fafc;
        font-size: 0.71rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.45rem 0.75rem;
        white-space: nowrap;
        vertical-align: middle;
    }
    .table-custom tbody tr {
        transition: background-color 0.1s ease;
        background-color: #ffffff;
    }
    .table-custom tbody tr:hover {
        background-color: #f8fafc;
    }
    .table-custom tbody td {
        padding: 0.42rem 0.75rem;
        vertical-align: middle;
        font-size: 0.81rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .product-icon {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
    }
    .spec-pill {
        font-size: 0.69rem;
        padding: 1px 6px;
        border-radius: 4px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        white-space: nowrap;
        font-weight: 500;
    }
    .category-badge-pill {
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 9px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .status-badge-pill {
        border-radius: 20px;
        font-size: 0.71rem;
        font-weight: 600;
        padding: 3px 9px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .sku-badge {
        font-size: 0.74rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        padding: 2px 7px;
        display: inline-block;
    }
    .action-btn-trigger {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
        transition: all 0.15s ease;
    }
    .action-btn-trigger:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .hover-primary:hover {
        color: #2563eb !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <!-- Low Stock Alert Banner (Compact) -->
    @if($totalLowStock > 0 && request('stock_status') !== 'low_stock')
        <div class="alert alert-warning border border-warning-subtle rounded-3 py-1.5 px-3 mb-2 d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-xs">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-6"></i>
                <span class="small">
                    <strong>Low Stock Warning:</strong>
                    <span class="text-secondary">{{ $totalLowStock }} product(s) have reached or fallen below safety replenishment threshold!</span>
                </span>
            </div>
            <a href="{{ route('products.index', ['stock_status' => 'low_stock']) }}" class="btn btn-warning btn-sm py-0.5 px-2.5 fw-semibold rounded-2" style="font-size: 0.74rem;">
                <i class="bi bi-filter me-1"></i> View Low Stock Items
            </a>
        </div>
    @endif

    <!-- Top Key Metrics: 5 Unified Cards in ONE Single Row -->
    <div class="metrics-grid mb-2">
        <!-- 1. All Products Card -->
        <div class="metric-card">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label">All Products</span>
                <div class="metric-icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
            <div class="metric-value text-dark">{{ number_format($totalProducts) }}</div>
            <div class="metric-sub">Registered items</div>
        </div>

        <!-- 2. Fuel Types Card -->
        <div class="metric-card">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label">Fuel Types</span>
                <div class="metric-icon-box bg-success bg-opacity-10 text-success">
                    <i class="bi bi-fuel-pump"></i>
                </div>
            </div>
            <div class="metric-value text-dark">{{ number_format($totalFuel) }}</div>
            <div class="metric-sub">Petrol, Diesel, Octane, Gas</div>
        </div>

        <!-- 3. Lubricants Card -->
        <div class="metric-card">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label">Lubricants</span>
                <div class="metric-icon-box bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-droplet-half"></i>
                </div>
            </div>
            <div class="metric-value text-dark">{{ number_format($totalLubricants) }}</div>
            <div class="metric-sub">Engine oils & fluids</div>
        </div>

        <!-- 4. Low Stock Alerts Card -->
        <div class="metric-card {{ $totalLowStock > 0 ? 'border-danger-subtle' : '' }}">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label">Low Stock</span>
                <div class="metric-icon-box {{ $totalLowStock > 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-secondary bg-opacity-10 text-secondary' }}">
                    <i class="bi bi-bell"></i>
                </div>
            </div>
            <div class="metric-value {{ $totalLowStock > 0 ? 'text-danger' : 'text-dark' }}">{{ number_format($totalLowStock) }}</div>
            <div class="metric-sub {{ $totalLowStock > 0 ? 'text-danger' : 'text-muted' }}">
                {{ $totalLowStock > 0 ? 'Needs replenishment' : 'All levels adequate' }}
            </div>
        </div>

        <!-- 5. Active Inventory Value Card (Adjusted in same row) -->
        <div class="metric-card metric-card-valuation">
            <div class="d-flex align-items-center justify-content-between mb-0.5">
                <span class="metric-label text-white text-opacity-75">Inventory Value</span>
                <div class="metric-icon-box bg-white bg-opacity-20 text-white">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <div class="metric-value-currency text-white" title="Rs. {{ number_format($totalInventoryValue, 2) }}">
                <span style="font-size: 0.82em; opacity: 0.88; font-weight: 600;">Rs.</span> {{ number_format($totalInventoryValue, 2) }}
            </div>
            <div class="metric-sub text-white text-opacity-75">At purchase cost</div>
        </div>
    </div>

    <!-- Main Card Container -->
    <div class="card-custom">
        <!-- Filter Header Bar -->
        <div class="px-3 py-2 border-bottom bg-white rounded-top-3">
            <!-- Category Filter Pills -->
            <div class="category-pills-wrap mb-2">
                <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => 'all'])) }}"
                   class="filter-pill {{ !request('category') || request('category') === 'all' ? 'active' : '' }}">
                    <i class="bi bi-grid-fill"></i> All Categories
                </a>
                <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => 'fuel'])) }}"
                   class="filter-pill {{ request('category') === 'fuel' ? 'active' : '' }}">
                    <i class="bi bi-fuel-pump-fill text-primary"></i> Fuel Products
                </a>
                <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => 'lubricant'])) }}"
                   class="filter-pill {{ request('category') === 'lubricant' ? 'active' : '' }}">
                    <i class="bi bi-droplet-fill text-warning"></i> Lubricants & Oils
                </a>
                <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => 'cng_lpg'])) }}"
                   class="filter-pill {{ request('category') === 'cng_lpg' ? 'active' : '' }}">
                    <i class="bi bi-wind text-info"></i> CNG & LPG Gas
                </a>
                <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => 'car_care'])) }}"
                   class="filter-pill {{ request('category') === 'car_care' ? 'active' : '' }}">
                    <i class="bi bi-shield-check text-success"></i> Car Care & Coolant
                </a>
                <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => 'tuck_shop'])) }}"
                   class="filter-pill {{ request('category') === 'tuck_shop' ? 'active' : '' }}">
                    <i class="bi bi-shop text-secondary"></i> Tuck Shop
                </a>
                <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => 'service'])) }}"
                   class="filter-pill {{ request('category') === 'service' ? 'active' : '' }}">
                    <i class="bi bi-wrench text-dark"></i> Services
                </a>
            </div>

            <!-- Search & Control Row -->
            <form action="{{ route('products.index') }}" method="GET" class="row g-1.5 align-items-center">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif

                <!-- Search Input -->
                <div class="col-12 col-md-4 col-xl-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted ps-2.5 pe-2" style="border-radius: 8px 0 0 8px; border-color: #e2e8f0;">
                            <i class="bi bi-search" style="font-size: 0.78rem;"></i>
                        </span>
                        <input type="text" name="search" class="form-control form-control-sm border-start-0 ps-1 py-1"
                               placeholder="Search product name, code, tank, or octane..."
                               value="{{ request('search') }}"
                               style="font-size: 0.78rem; border-radius: 0 8px 8px 0; border-color: #e2e8f0;">
                    </div>
                </div>

                <!-- Stock Filter -->
                <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                    <select name="stock_status" class="form-select form-select-sm filter-input" onchange="this.form.submit()">
                        <option value="">Stock Status: All</option>
                        <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>⚠️ Low Stock Only</option>
                        <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock (>0)</option>
                        <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock (0)</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                    <select name="status" class="form-select form-select-sm filter-input" onchange="this.form.submit()">
                        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Status: All</option>
                        <option value="active" {{ !request('status') || request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                    </select>
                </div>

                <!-- Sort Order -->
                <div class="col-6 col-sm-4 col-md-2 col-xl-2">
                    <select name="sort" class="form-select form-select-sm filter-input" onchange="this.form.submit()">
                        <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Sort: Latest</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name (A - Z)</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Name (Z - A)</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Sale Rate (High to Low)</option>
                        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Sale Rate (Low to High)</option>
                        <option value="stock_desc" {{ request('sort') === 'stock_desc' ? 'selected' : '' }}>Stock (High to Low)</option>
                        <option value="stock_asc" {{ request('sort') === 'stock_asc' ? 'selected' : '' }}>Stock (Low to High)</option>
                    </select>
                </div>

                <!-- Submit / Clear Buttons -->
                <div class="col-6 col-sm-12 col-md-2 col-xl-2 d-flex gap-1">
                    <button type="submit" class="btn btn-filter-submit btn-sm flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    @if(request()->hasAny(['search', 'stock_status', 'status', 'sort', 'category']))
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm py-1 px-2 rounded-2" style="font-size: 0.78rem;" title="Clear All Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="table-responsive">
            <table class="table table-hover table-custom align-middle m-0">
                <thead>
                    <tr>
                        <th style="min-width: 175px;">Product Name</th>
                        <th style="min-width: 210px;">Specifications &amp; Tank / Shelf</th>
                        <th style="min-width: 95px;">SKU / Code</th>
                        <th style="min-width: 100px;">Category</th>
                        <th style="min-width: 115px;" class="text-end">Purchase Rate</th>
                        <th style="min-width: 125px;" class="text-end">Sale Rate (Retail)</th>
                        <th style="min-width: 110px;" class="text-end">Margin</th>
                        <th style="min-width: 130px;">Current Stock</th>
                        <th style="min-width: 85px;" class="text-center">Status</th>
                        <th style="min-width: 70px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr class="{{ $product->is_low_stock ? 'table-warning bg-opacity-25' : '' }}">
                            <!-- Product Name -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="product-icon-wrap flex-shrink-0">
                                        @if($product->is_fuel)
                                            <span class="product-icon bg-primary bg-opacity-10 text-primary">
                                                <i class="bi bi-fuel-pump-fill"></i>
                                            </span>
                                        @elseif($product->category === 'lubricant')
                                            <span class="product-icon bg-warning bg-opacity-10 text-warning">
                                                <i class="bi bi-droplet-half"></i>
                                            </span>
                                        @else
                                            <span class="product-icon bg-secondary bg-opacity-10 text-secondary">
                                                <i class="bi bi-box-seam"></i>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('products.show', $product) }}" class="fw-bold text-dark text-decoration-none hover-primary d-inline-block text-truncate" style="max-width: 230px; font-size: 0.83rem;" title="{{ $product->name }}">
                                            {{ $product->name }}
                                        </a>
                                    </div>
                                </div>
                            </td>

                            <!-- Specifications & Tank / Shelf (New Dedicated Column) -->
                            <td>
                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                    @if($product->density_or_octane)
                                        <span class="spec-pill">
                                            <i class="bi bi-speedometer2 text-primary"></i> {{ $product->density_or_octane }}
                                        </span>
                                    @endif
                                    @if($product->tank_or_shelf)
                                        <span class="spec-pill text-danger">
                                            <i class="bi bi-geo-alt"></i> {{ $product->tank_or_shelf }}
                                        </span>
                                    @endif
                                    @if($product->supplier)
                                        <span class="spec-pill text-info">
                                            <i class="bi bi-truck"></i> {{ $product->supplier->name }}
                                        </span>
                                    @endif
                                    @if(!$product->density_or_octane && !$product->tank_or_shelf && !$product->supplier)
                                        <span class="text-muted small" style="font-size: 0.75rem;">—</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Code / SKU -->
                            <td>
                                <span class="sku-badge">
                                    {{ $product->code ?? '—' }}
                                </span>
                            </td>

                            <!-- Category -->
                            <td>
                                @if($product->category === 'fuel')
                                    <span class="category-badge-pill bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                                        <i class="bi bi-fuel-pump"></i> Fuel
                                    </span>
                                @elseif($product->category === 'lubricant')
                                    <span class="category-badge-pill bg-warning bg-opacity-10 text-dark border border-warning-subtle">
                                        <i class="bi bi-droplet-half text-warning"></i> Lubricant
                                    </span>
                                @elseif($product->category === 'cng_lpg')
                                    <span class="category-badge-pill bg-info bg-opacity-10 text-info border border-info-subtle">
                                        <i class="bi bi-wind"></i> Gas / CNG
                                    </span>
                                @elseif($product->category === 'car_care')
                                    <span class="category-badge-pill bg-success bg-opacity-10 text-success border border-success-subtle">
                                        <i class="bi bi-shield-check"></i> Car Care
                                    </span>
                                @elseif($product->category === 'tuck_shop')
                                    <span class="category-badge-pill bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle">
                                        <i class="bi bi-shop"></i> Tuck Shop
                                    </span>
                                @else
                                    <span class="category-badge-pill bg-light text-dark border">
                                        {{ ucfirst($product->category) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Purchase Rate -->
                            <td class="text-end">
                                <div class="fw-semibold text-secondary" style="font-size: 0.81rem; font-variant-numeric: tabular-nums;">
                                    Rs. {{ number_format($product->purchase_price, 2) }}
                                </div>
                                <div class="text-muted" style="font-size: 0.68rem;">per {{ $product->unit_label }}</div>
                            </td>

                            <!-- Sale Rate (Retail) -->
                            <td class="text-end">
                                <span class="fw-bold text-dark" style="font-size: 0.88rem; font-variant-numeric: tabular-nums;">
                                    Rs. {{ number_format($product->sale_price, 2) }}
                                </span>
                                <div class="text-muted" style="font-size: 0.68rem;">per {{ $product->unit_label }}</div>
                            </td>

                            <!-- Margin -->
                            <td class="text-end" style="font-variant-numeric: tabular-nums;">
                                @php
                                    $margin = $product->profit_margin;
                                    $pct = $product->profit_percentage;
                                @endphp
                                @if($margin >= 0)
                                    <span class="text-success fw-bold" style="font-size: 0.79rem;">
                                        +Rs. {{ number_format($margin, 2) }}
                                    </span>
                                    <div class="text-success" style="font-size: 0.68rem; font-weight: 600;">({{ $pct }}%)</div>
                                @else
                                    <span class="text-danger fw-bold" style="font-size: 0.79rem;">
                                        -Rs. {{ number_format(abs($margin), 2) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Current Stock -->
                            <td>
                                <div class="d-flex align-items-baseline gap-1">
                                    <span class="fw-bold {{ $product->is_low_stock ? 'text-danger' : 'text-dark' }}" style="font-size: 0.85rem; font-variant-numeric: tabular-nums;">
                                        {{ number_format($product->current_stock, 2) }}
                                    </span>
                                    <span class="text-muted" style="font-size: 0.72rem;">{{ $product->unit_label }}</span>
                                </div>

                                @if($product->is_low_stock)
                                    <div class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-1.5 py-0 mt-0.5" style="font-size: 0.65rem;">
                                        <i class="bi bi-exclamation-triangle-fill me-0.5"></i>Min: {{ number_format($product->min_stock_alert, 2) }}
                                    </div>
                                @elseif($product->min_stock_alert !== null)
                                    <div class="text-muted" style="font-size: 0.67rem;">
                                        Min: {{ number_format($product->min_stock_alert, 2) }}
                                    </div>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="text-center">
                                <form action="{{ route('products.toggle-status', $product) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-link p-0 text-decoration-none border-0" title="Click to toggle status">
                                        @if($product->status === 'active')
                                            <span class="status-badge-pill bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-check-circle-fill" style="font-size: 0.7rem;"></i> Active
                                            </span>
                                        @else
                                            <span class="status-badge-pill bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                <i class="bi bi-slash-circle" style="font-size: 0.7rem;"></i> Inactive
                                            </span>
                                        @endif
                                    </button>
                                </form>
                            </td>

                            <!-- Actions -->
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="action-btn-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3" style="font-size: 0.81rem; border: 1px solid #e2e8f0 !important;">
                                        <li>
                                            <a class="dropdown-item py-1.5 d-flex align-items-center gap-2" href="{{ route('products.show', $product) }}">
                                                <i class="bi bi-eye text-info"></i> View Details
                                            </a>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item py-1.5 d-flex align-items-center gap-2"
                                                    onclick="openQuickPriceModal('{{ $product->id }}', '{{ addslashes($product->name) }}', '{{ $product->sale_price }}', '{{ $product->purchase_price }}', '{{ $product->unit_label }}')">
                                                <i class="bi bi-tags text-success"></i> Update Fuel / Sale Rate
                                            </button>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-1.5 d-flex align-items-center gap-2" href="{{ route('products.edit', $product) }}">
                                                <i class="bi bi-pencil text-primary"></i> Edit Product
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product? Historical data will be preserved.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item py-1.5 d-flex align-items-center gap-2 text-danger">
                                                    <i class="bi bi-trash"></i> Delete Product
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <div class="py-2">
                                    <div class="display-6 text-muted mb-2">
                                        <i class="bi bi-fuel-pump text-primary"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark">No Products Found</h6>
                                    <p class="text-muted small mb-2">No petrol pump products have been added yet or none match your filter.</p>
                                    <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm px-3 rounded-2 shadow-xs">
                                        <i class="bi bi-plus-circle me-1"></i> Add First Petrol Pump Product
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($products->hasPages())
            <div class="px-3 py-2 border-top bg-white rounded-bottom-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted" style="font-size: 0.75rem;">
                    Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products
                </div>
                <div>
                    {{ $products->links() }}
                </div>
            </div>
        @endif
    </div>

</div>

<!-- Quick Price Revision Modal -->
<div class="modal fade" id="quickPriceModal" tabindex="-1" aria-labelledby="quickPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-2.5 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-success bg-opacity-10 text-success rounded-2 p-1.5 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-fuel-pump fs-6"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark m-0" id="quickPriceModalLabel" style="font-size: 0.95rem;">Rate Revision</h6>
                        <small class="text-muted" id="modalProductName" style="font-size: 0.75rem;">Product Name</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickPriceForm" method="POST" action="">
                @csrf
                <div class="modal-body p-3">
                    <div class="alert alert-info py-1.5 px-2.5 small border-0 mb-2.5 d-flex align-items-center gap-2 rounded-2" style="font-size: 0.75rem;">
                        <i class="bi bi-info-circle-fill fs-6 text-info"></i>
                        <span>Rate updates are immediately logged into the official audit trail.</span>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.78rem;">New Selling / Retail Rate (Rs.) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold">Rs.</span>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm py-1.5 fs-6 fw-bold" id="modalSalePrice" name="sale_price" required>
                            <span class="input-group-text bg-light text-muted" id="modalUnitLabel">per Liter</span>
                        </div>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.78rem;">Purchase / Cost Rate (Rs.) <span class="text-muted">(Optional)</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">Rs.</span>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm py-1.5" id="modalPurchasePrice" name="purchase_price">
                            <span class="input-group-text bg-light text-muted" id="modalUnitLabel2">per Liter</span>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.78rem;">Revision Reason / Reference</label>
                        <input type="text" class="form-control form-control-sm py-1.5" name="price_change_reason" placeholder="e.g. OGRA Fortnightly Revision, Supplier Increase" style="font-size: 0.78rem;">
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3 bg-light rounded-bottom-3">
                    <button type="button" class="btn btn-secondary btn-sm rounded-2 py-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm rounded-2 px-3 py-1 shadow-xs fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Save New Rate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openQuickPriceModal(productId, productName, currentSale, currentPurchase, unitLabel) {
        const form = document.getElementById('quickPriceForm');
        form.action = `/products/${productId}/quick-price`;
        
        document.getElementById('modalProductName').textContent = productName;
        document.getElementById('modalSalePrice').value = currentSale;
        document.getElementById('modalPurchasePrice').value = currentPurchase;
        document.getElementById('modalUnitLabel').textContent = 'per ' + unitLabel;
        document.getElementById('modalUnitLabel2').textContent = 'per ' + unitLabel;
        
        const modal = new bootstrap.Modal(document.getElementById('quickPriceModal'));
        modal.show();
    }
</script>
@endpush
@endsection
