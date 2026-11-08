@extends('layouts.app')

@section('title', $product->name . ' - Product Details')
@section('page_title', $product->name)

@section('page_badge')
    <div class="d-flex align-items-center gap-2">
        {!! $product->category_badge !!}
        {!! $product->status_badge !!}
        @if($product->is_fuel)
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1">
                <i class="bi bi-fuel-pump-fill me-1"></i>Fuel Nozzle Dispensed
            </span>
        @endif
    </div>
@endsection

@section('page_actions')
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-success btn-sm rounded-3 shadow-sm text-nowrap"
                onclick="openQuickPriceModal('{{ $product->id }}', '{{ addslashes($product->name) }}', '{{ $product->sale_price }}', '{{ $product->purchase_price }}', '{{ $product->unit_label }}')">
            <i class="bi bi-tags me-1"></i> Quick Rate Revision
        </button>
        <a href="{{ route('products.edit', $product) }}" class="btn btn-primary btn-sm rounded-3 shadow-sm text-nowrap">
            <i class="bi bi-pencil me-1"></i> Edit Product
        </a>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 shadow-sm text-nowrap">
            <i class="bi bi-arrow-left me-1"></i> Back to Products
        </a>
    </div>
@endsection

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

    body, body *, input, button, select, textarea, .form-monospace, code, kbd, samp, pre {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
        letter-spacing: normal !important;
    }
    .fa, .fas, .fa-solid, [class^="fa-"], [class*=" fa-"] {
        font-family: "Font Awesome 6 Free", "FontAwesome" !important;
        font-weight: 900 !important;
    }
    .far, .fa-regular {
        font-family: "Font Awesome 6 Free", "FontAwesome" !important;
        font-weight: 400 !important;
    }
    .fab, .fa-brands {
        font-family: "Font Awesome 6 Brands", "FontAwesome" !important;
        font-weight: 400 !important;
    }
    .bi, [class^="bi-"], [class*=" bi-"] {
        font-family: "bootstrap-icons" !important;
    }

    /* Sub-header Compact Override */
    .sub-header {
        padding: 0.3rem 0 !important;
        margin-bottom: 0.45rem !important;
    }
    .sub-header h5 {
        font-size: 0.98rem !important;
        margin: 0 !important;
    }

    .stat-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.25rem;
    }
</style>
@endpush

@section('content')
<div class="row g-3 g-lg-4">

    <!-- Left Column: Specs & Pricing Details -->
    <div class="col-12 col-lg-8">
        <div class="card-custom mb-3">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-dark m-0">Pricing & Commercial Specifications</h6>
                <span class="badge bg-light text-secondary border font-monospace">SKU: {{ $product->code ?? 'N/A' }}</span>
            </div>
            <div class="p-3 p-md-4">
                <!-- Three Commercial Metric Boxes -->
                <div class="row g-3 mb-4">
                    <!-- Purchase Rate -->
                    <div class="col-12 col-md-4">
                        <div class="stat-box">
                            <span class="text-secondary small fw-semibold d-block mb-1">Purchase / Cost Rate</span>
                            <h4 class="fw-bold text-dark m-0">Rs. {{ number_format($product->purchase_price, 2) }}</h4>
                            <span class="text-muted small" style="font-size: 0.72rem;">per {{ $product->unit_label }}</span>
                        </div>
                    </div>

                    <!-- Sale Rate -->
                    <div class="col-12 col-md-4">
                        <div class="stat-box bg-primary bg-opacity-10 border-primary border-opacity-25">
                            <span class="text-primary small fw-semibold d-block mb-1">Sale / Retail Rate</span>
                            <h4 class="fw-bold text-primary m-0">Rs. {{ number_format($product->sale_price, 2) }}</h4>
                            <span class="text-secondary small" style="font-size: 0.72rem;">per {{ $product->unit_label }}</span>
                        </div>
                    </div>

                    <!-- Gross Margin -->
                    <div class="col-12 col-md-4">
                        @php
                            $margin = $product->profit_margin;
                            $pct = $product->profit_percentage;
                        @endphp
                        <div class="stat-box {{ $margin >= 0 ? 'bg-success bg-opacity-10 border-success border-opacity-25' : 'bg-danger bg-opacity-10 border-danger border-opacity-25' }}">
                            <span class="{{ $margin >= 0 ? 'text-success' : 'text-danger' }} small fw-semibold d-block mb-1">Estimated Unit Margin</span>
                            <h4 class="fw-bold {{ $margin >= 0 ? 'text-success' : 'text-danger' }} m-0">
                                {{ $margin >= 0 ? '+' : '-' }}Rs. {{ number_format(abs($margin), 2) }}
                            </h4>
                            <span class="{{ $margin >= 0 ? 'text-success' : 'text-danger' }} small" style="font-size: 0.72rem;">
                                {{ $pct }}% gross profit
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Product Attributes Grid -->
                <h6 class="fw-bold text-dark small text-uppercase mb-3" style="letter-spacing: 0.4px;">
                    Technical & Operational Parameters
                </h6>

                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <span class="text-secondary small d-block">Category</span>
                        <span class="fw-bold text-dark">{{ $product->category_label }}</span>
                    </div>

                    <div class="col-6 col-md-4">
                        <span class="text-secondary small d-block">Unit of Measurement</span>
                        <span class="fw-bold text-dark">{{ $product->unit_label }}</span>
                    </div>

                    <div class="col-6 col-md-4">
                        <span class="text-secondary small d-block">Dispensed as Liquid Fuel?</span>
                        <span class="fw-bold {{ $product->is_fuel ? 'text-primary' : 'text-secondary' }}">
                            {{ $product->is_fuel ? 'Yes (Fuel Dispenser / Tank)' : 'No (Packaged Good / Mart)' }}
                        </span>
                    </div>

                    <div class="col-6 col-md-4">
                        <span class="text-secondary small d-block">Octane / Density / Grade</span>
                        <span class="fw-bold text-dark">{{ $product->density_or_octane ?? 'N/A' }}</span>
                    </div>

                    <div class="col-6 col-md-4">
                        <span class="text-secondary small d-block">Tank / Shelf Location</span>
                        <span class="fw-bold text-dark">{{ $product->tank_or_shelf ?? 'N/A' }}</span>
                    </div>

                    <div class="col-6 col-md-4">
                        <span class="text-secondary small d-block">Primary Supplier / OMC</span>
                        @if($product->supplier)
                            <a href="{{ route('ledgers.party', $product->supplier_id) }}" class="fw-bold text-primary text-decoration-none">
                                <i class="bi bi-truck me-1"></i>{{ $product->supplier->name }}
                            </a>
                        @else
                            <span class="text-muted">Not assigned</span>
                        @endif
                    </div>
                </div>

                @if($product->description)
                    <div class="mt-4 pt-3 border-top">
                        <span class="text-secondary small d-block mb-1">Description & Specifications:</span>
                        <div class="p-3 bg-light rounded-3 text-secondary small">
                            {{ $product->description }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Connected Fuel Pump Machines & Dispensers -->
        @if($product->is_fuel || $product->category === 'fuel')
            <div class="card-custom mb-3">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold text-dark m-0">
                            <i class="bi bi-speedometer2 text-primary me-1.5"></i> Connected Pump Dispensers & Live Readings
                        </h6>
                        <small class="text-muted" style="font-size: 0.75rem;">Dispenser units pulling from this fuel stock</small>
                    </div>
                    <a href="{{ route('pumps.create') }}" class="btn btn-outline-primary btn-sm py-1 px-2.5 rounded-2" style="font-size: 0.75rem;">
                        <i class="bi bi-plus-circle me-1"></i> Add Dispenser
                    </a>
                </div>
                <div class="p-3">
                    @if($product->pumps->isNotEmpty())
                        <div class="row g-2">
                            @foreach($product->pumps as $pump)
                                <div class="col-12 col-md-6">
                                    <div class="p-2.5 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $pump->name }}</div>
                                            <div class="text-muted small" style="font-size: 0.72rem;">
                                                <span class="badge bg-white text-dark border">{{ $pump->code ?: '#' . $pump->id }}</span>
                                                &bull; {{ $pump->tank_no ?: 'Tank' }} &bull; {{ $pump->nozzle_number ?: 'Standard Nozzle' }}
                                            </div>
                                            <div class="mt-1 font-monospace small">
                                                Meter: <strong class="text-primary">{{ number_format($pump->last_reading, 2) }}</strong>
                                            </div>
                                        </div>
                                        <div>
                                            <a href="{{ route('pumps.show', $pump->id) }}" class="btn btn-primary btn-sm py-1 px-2.5" style="font-size: 0.75rem;">
                                                Readings &rarr;
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-3 text-muted small">
                            No dispensers currently linked to this fuel.
                            <a href="{{ route('pumps.create') }}" class="text-primary text-decoration-none ms-1">Link a pump machine &rarr;</a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Right Column: Live Inventory Health -->
    <div class="col-12 col-lg-4">
        <!-- Stock Status Card -->
        <div class="card-custom mb-3">
            <div class="p-3 border-bottom">
                <h6 class="fw-bold text-dark m-0">Inventory Health & Stock</h6>
                <small class="text-muted" style="font-size: 0.78rem;">Physical volume & reorder threshold</small>
            </div>
            <div class="p-3 p-md-4 text-center">
                <div class="display-5 fw-bold {{ $product->is_low_stock ? 'text-danger' : 'text-dark' }} mb-1">
                    {{ number_format($product->current_stock, 2) }}
                </div>
                <div class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing: 0.5px;">
                    {{ $product->unit_label }} Available
                </div>

                @if($product->is_low_stock)
                    <div class="alert alert-danger py-2 px-3 small border-0 mb-3 d-flex align-items-center gap-2 text-start">
                        <i class="bi bi-exclamation-triangle-fill fs-5 text-danger flex-shrink-0"></i>
                        <div>
                            <strong>Low Stock Alert!</strong> Current volume is at or below the safety threshold of {{ number_format($product->min_stock_alert, 2) }} {{ $product->unit_label }}.
                        </div>
                    </div>
                @else
                    <div class="alert alert-success py-2 px-3 small border-0 mb-3 d-flex align-items-center gap-2 text-start">
                        <i class="bi bi-shield-check fs-5 text-success flex-shrink-0"></i>
                        <div>
                            <strong>Stock Level Healthy.</strong> Safety threshold: {{ $product->min_stock_alert ? number_format($product->min_stock_alert, 2) . ' ' . $product->unit_label : 'Not set' }}.
                        </div>
                    </div>
                @endif

                <div class="d-flex justify-content-between p-2 bg-light rounded-2 small text-secondary mb-2">
                    <span>Initial Opening Stock:</span>
                    <strong class="text-dark">{{ number_format($product->opening_stock, 2) }} {{ $product->unit_label }}</strong>
                </div>

                <div class="d-flex justify-content-between p-2 bg-light rounded-2 small text-secondary mb-2">
                    <span>Reorder Safety Level:</span>
                    <strong class="text-dark">{{ $product->min_stock_alert ? number_format($product->min_stock_alert, 2) . ' ' . $product->unit_label : 'None' }}</strong>
                </div>

                <div class="d-flex justify-content-between p-2 bg-light rounded-2 small text-secondary">
                    <span>Total Stock Valuation:</span>
                    <strong class="text-dark">Rs. {{ number_format($product->stock_value, 2) }}</strong>
                </div>
            </div>
        </div>

        <!-- Danger Zone Card -->
        <div class="card-custom border-danger-subtle">
            <div class="p-3 border-bottom bg-danger bg-opacity-10 rounded-top-3">
                <h6 class="fw-bold text-danger m-0">Archive / Delete Product</h6>
            </div>
            <div class="p-3 text-secondary small">
                <p class="mb-3">Deleting this product soft-deletes the record. All historical sales and ledger vouchers remain safe.</p>
                <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100 rounded-3">
                        <i class="bi bi-trash me-1"></i> Delete Product
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- Quick Price Revision Modal -->
<div class="modal fade" id="quickPriceModal" tabindex="-1" aria-labelledby="quickPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom p-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-2">
                        <i class="bi bi-fuel-pump fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark" id="quickPriceModalLabel">Rate Revision</h6>
                        <small class="text-muted" id="modalProductName">Product Name</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickPriceForm" method="POST" action="">
                @csrf
                <div class="modal-body p-3 p-md-4">
                    <div class="alert alert-info py-2 px-3 small border-0 mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill fs-6"></i>
                        <span>Rate updates are immediately logged into the official audit trail.</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">New Selling / Retail Rate (Rs.) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold">Rs.</span>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm py-2 fs-6 fw-bold" id="modalSalePrice" name="sale_price" required>
                            <span class="input-group-text bg-light text-muted" id="modalUnitLabel">per Liter</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Purchase / Cost Rate (Rs.) <span class="text-muted">(Optional)</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">Rs.</span>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm py-2" id="modalPurchasePrice" name="purchase_price">
                            <span class="input-group-text bg-light text-muted" id="modalUnitLabel2">per Liter</span>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Revision Reason / Reference</label>
                        <input type="text" class="form-control form-control-sm py-2" name="price_change_reason" placeholder="e.g. OGRA Fortnightly Revision">
                    </div>
                </div>
                <div class="modal-footer border-top p-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm rounded-3 px-3 shadow-sm">
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
