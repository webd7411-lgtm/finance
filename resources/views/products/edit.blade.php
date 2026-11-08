@extends('layouts.app')

@section('title', 'Edit ' . $product->name)
@section('page_title', 'Edit Petrol Pump Product')

@section('page_badge')
    <div class="d-flex align-items-center gap-1.5">
        {!! $product->category_badge !!}
        {!! $product->status_badge !!}
    </div>
@endsection

@section('page_actions')
    <div class="d-flex gap-1.5">
        <a href="{{ route('products.show', $product) }}" class="btn btn-outline-info btn-sm rounded-2 py-1 px-2.5 shadow-sm text-nowrap" style="font-size: 0.78rem;">
            <i class="bi bi-eye me-1"></i> View Details
        </a>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 py-1 px-2.5 shadow-sm text-nowrap" style="font-size: 0.78rem;">
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

    /* Sub-header Bar */
    .sub-header {
        padding: 0.35rem 0 !important;
        margin-bottom: 0.75rem !important;
    }
    .sub-header h5 {
        font-size: 1rem !important;
        font-weight: 700;
        margin: 0 !important;
    }

    /* PDF Spec Exact Card Layout - Full Page Space Occupied */
    .pdf-form-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        padding: 1.1rem 1.4rem;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 767.98px) {
        .pdf-form-card {
            padding: 0.9rem 0.85rem;
            border-radius: 10px;
        }
    }

    /* Section Numbered Step Badge */
    .section-step-badge {
        width: 19px;
        height: 19px;
        border-radius: 50%;
        background: #1e40af;
        color: #ffffff;
        font-size: 0.68rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .section-title {
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.4px;
        color: #0f172a;
        text-transform: uppercase;
        margin: 0;
    }

    /* Form Labels & Helpers */
    .pdf-label {
        font-size: 0.72rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 3px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        line-height: 1.2;
    }
    .pdf-helper-right {
        font-size: 0.67rem;
        font-weight: 400;
        color: #94a3b8;
    }
    .pdf-helper-bottom {
        font-size: 0.66rem;
        color: #94a3b8;
        margin-top: 2px;
        line-height: 1.2;
    }

    /* Clean Crisp Input Styling */
    .pdf-input {
        height: 35px !important;
        padding: 0.3rem 0.7rem !important;
        font-size: 0.82rem !important;
        border-radius: 7px !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #ffffff !important;
        color: #0f172a !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .pdf-input:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
        outline: none !important;
    }
    .pdf-addon {
        height: 35px !important;
        padding: 0.3rem 0.7rem !important;
        font-size: 0.76rem !important;
        background-color: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        color: #64748b !important;
    }
    .pdf-addon-prefix {
        border-top-left-radius: 7px !important;
        border-bottom-left-radius: 7px !important;
    }
    .pdf-addon-suffix {
        border-top-right-radius: 7px !important;
        border-bottom-right-radius: 7px !important;
    }

    /* Dispensed Fuel Product Setup Box */
    .dispensed-fuel-box {
        background: #f8faff;
        border: 1px solid #e0e7ff;
        border-radius: 9px;
        padding: 0.65rem 0.95rem;
        margin-top: 0.55rem;
        margin-bottom: 0.75rem;
    }

    /* Estimated Gross Margin Box */
    .margin-display-box {
        height: 35px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 10px;
    }

    /* Submit Button (Dark Blue Pill/Rounded) */
    .btn-register-action {
        background: #1e3a8a;
        border: 1px solid #1e3a8a;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.82rem;
        padding: 0.4rem 1.5rem;
        border-radius: 7px;
        box-shadow: 0 2px 6px rgba(30, 58, 138, 0.22);
        transition: all 0.15s ease;
    }
    .btn-register-action:hover {
        background: #172554;
        border-color: #172554;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(30, 58, 138, 0.32);
        transform: translateY(-1px);
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">

        <!-- Main Product Entry Form Card (Full Page Width) -->
        <div class="pdf-form-card">
            
            <!-- Card Header matching PDF -->
            <div class="d-flex align-items-center justify-content-between pb-2 mb-2.5 border-bottom flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="text-white rounded-3 d-flex align-items-center justify-content-center shadow-xs" style="width: 34px; height: 34px; background-color: #1e40af !important;">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark m-0" style="font-size: 0.95rem;">Edit Product: {{ $product->name }}</h6>
                        <small class="text-secondary" style="font-size: 0.72rem;">Fuel grades, lubricants, pricing aur inventory parameters modify karein</small>
                    </div>
                </div>
                <span class="badge bg-light text-secondary border px-2.5 py-1" style="font-size: 0.7rem; font-weight: 500;">
                    SKU: {{ $product->code ?? 'N/A' }}
                </span>
            </div>

            <!-- Form Body -->
            <form action="{{ route('products.update', $product) }}" method="POST" id="productEditForm">
                @csrf
                @method('PUT')

                <!-- SECTION 1: PRODUCT NAME & CLASSIFICATION -->
                <div class="mb-2.5">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="section-step-badge">1</span>
                        <h6 class="section-title">PRODUCT NAME & CLASSIFICATION</h6>
                    </div>

                    <!-- Row 1: Name and SKU -->
                    <div class="row g-2.5 mb-2">
                        <div class="col-12 col-md-8 col-lg-8">
                            <label for="name" class="pdf-label">
                                <span>Product / Fuel Name <span class="text-danger">*</span></span>
                                <span class="pdf-helper-right">Naam khud likh sakte hain</span>
                            </label>
                            <input type="text" class="form-control pdf-input fw-semibold @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $product->name) }}"
                                   required>
                            @error('name')
                                <div class="text-danger small mt-0.5" style="font-size: 0.68rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4 col-lg-4">
                            <label for="code" class="pdf-label">
                                <span>Product Code / SKU</span>
                                <span class="pdf-helper-right">Optional</span>
                            </label>
                            <input type="text" class="form-control pdf-input @error('code') is-invalid @enderror"
                                   id="code" name="code" value="{{ old('code', $product->code) }}">
                            <div class="pdf-helper-bottom">Khali chhorein to auto-generate hoga</div>
                            @error('code')
                                <div class="text-danger small mt-0.5" style="font-size: 0.68rem;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Row 2: Category and Unit of Measurement -->
                    <div class="row g-2.5 mb-1.5">
                        <div class="col-12 col-md-6">
                            <label for="category" class="pdf-label">
                                <span>Category <span class="text-danger">*</span></span>
                                <span class="d-inline-flex align-items-center gap-2">
                                    <span class="pdf-helper-right d-none d-sm-inline">Product Classification</span>
                                    <button type="button" class="btn btn-link p-0 text-primary fw-bold text-decoration-none" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#quickAddCategoryModal">
                                        <i class="bi bi-plus-circle-fill me-0.5"></i> + Add Category
                                    </button>
                                </span>
                            </label>
                            <select class="form-select pdf-input fw-semibold @error('category') is-invalid @enderror"
                                    id="category" name="category" required onchange="handleCategoryChange()">
                                @foreach($categories ?? \App\Models\Product::getAllCategories() as $key => $label)
                                    <option value="{{ $key }}" {{ old('category', $product->category) === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="text-danger small mt-0.5" style="font-size: 0.68rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="unit" class="pdf-label">
                                <span>Unit of Measurement <span class="text-danger">*</span></span>
                                <button type="button" class="btn btn-link p-0 text-primary fw-bold text-decoration-none" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#quickAddUnitModal">
                                    <i class="bi bi-plus-circle-fill me-0.5"></i> + Add Unit
                                </button>
                            </label>
                            <select class="form-select pdf-input fw-semibold @error('unit') is-invalid @enderror"
                                    id="unit" name="unit" required onchange="updateUnitLabels()">
                                @foreach($units ?? \App\Models\Product::getAllUnits() as $key => $label)
                                    <option value="{{ $key }}" {{ old('unit', $product->unit) === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('unit')
                                <div class="text-danger small mt-0.5" style="font-size: 0.68rem;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Dispensed Fuel Product Setup Box -->
                    <div class="dispensed-fuel-box">
                        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0" type="checkbox" id="is_fuel" name="is_fuel" value="1"
                                       {{ old('is_fuel', $product->is_fuel) ? 'checked' : '' }} style="cursor: pointer; width: 17px; height: 17px;">
                                <label class="form-check-label fw-bold text-dark" for="is_fuel" style="font-size: 0.8rem; cursor: pointer;">
                                    Dispensed Fuel Product (Nozzle / Underground Tank)
                                </label>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5" style="font-size: 0.67rem; font-weight: 500;">
                                Tank Dipping & Shift Meter Tracking enabled
                            </span>
                        </div>

                        <div class="row g-2">
                            <div class="col-12 col-md-4">
                                <label for="density_or_octane" class="pdf-label">
                                    <span>Octane / Density / Grade</span>
                                </label>
                                <input type="text" class="form-control pdf-input" id="density_or_octane" name="density_or_octane"
                                       value="{{ old('density_or_octane', $product->density_or_octane) }}"
                                       placeholder="200 Bar Pressure">
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="tank_or_shelf" class="pdf-label">
                                    <span>Storage Location / Tank No.</span>
                                </label>
                                <input type="text" class="form-control pdf-input" id="tank_or_shelf" name="tank_or_shelf"
                                       value="{{ old('tank_or_shelf', $product->tank_or_shelf) }}"
                                       placeholder="Compressor Station">
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="supplier_id" class="pdf-label">
                                    <span>Primary Supplier / OMC</span>
                                    <span class="pdf-helper-right">Optional</span>
                                </label>
                                <select class="form-select pdf-input" id="supplier_id" name="supplier_id">
                                    <option value="">Select supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: RATES & REAL-TIME PROFIT MARGIN -->
                <div class="mb-2.5">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="section-step-badge">2</span>
                        <h6 class="section-title">RATES & REAL-TIME PROFIT MARGIN</h6>
                    </div>

                    <div class="row g-2.5 align-items-end">
                        <div class="col-12 col-md-4">
                            <label for="purchase_price" class="pdf-label">
                                <span>Purchase / Cost Rate <span class="text-danger">*</span></span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text pdf-addon pdf-addon-prefix fw-semibold">Rs.</span>
                                <input type="number" step="0.01" min="0" class="form-control pdf-input fw-semibold @error('purchase_price') is-invalid @enderror"
                                       id="purchase_price" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}"
                                       required oninput="calculateMargin()">
                                <span class="input-group-text pdf-addon pdf-addon-suffix unit-suffix per-unit">/ Kg</span>
                            </div>
                            @error('purchase_price')
                                <div class="text-danger small mt-0.5" style="font-size: 0.68rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="sale_price" class="pdf-label">
                                <span>Sale / Retail Rate <span class="text-danger">*</span></span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text pdf-addon pdf-addon-prefix fw-bold text-primary" style="background: #eff6ff;">Rs.</span>
                                <input type="number" step="0.01" min="0" class="form-control pdf-input fw-bold @error('sale_price') is-invalid @enderror"
                                       id="sale_price" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}"
                                       required oninput="calculateMargin()">
                                <span class="input-group-text pdf-addon pdf-addon-suffix unit-suffix per-unit">/ Kg</span>
                            </div>
                            @error('sale_price')
                                <div class="text-danger small mt-0.5" style="font-size: 0.68rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Estimated Gross Margin -->
                        <div class="col-12 col-md-4">
                            <label class="pdf-label">
                                <span>Estimated Gross Margin</span>
                            </label>
                            <div class="margin-display-box" id="marginDisplayBox">
                                <div class="fw-bold text-success" id="marginAmountText" style="font-size: 0.85rem;">
                                    +Rs. 0.00 per unit
                                </div>
                                <span id="marginBadge" class="badge bg-success text-white px-2 py-1 fw-bold" style="font-size: 0.7rem;">+0%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: STOCK LEVELS & AVAILABILITY -->
                <div class="mb-2">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="section-step-badge">3</span>
                        <h6 class="section-title">STOCK LEVELS & AVAILABILITY</h6>
                    </div>

                    <!-- Row 1: Stock, Alert, Status -->
                    <div class="row g-2.5 mb-2">
                        <div class="col-12 col-md-4">
                            <label for="current_stock" class="pdf-label">
                                <span>Current Live Stock <span class="text-danger">*</span></span>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control pdf-input fw-bold @error('current_stock') is-invalid @enderror"
                                       id="current_stock" name="current_stock" value="{{ old('current_stock', $product->current_stock) }}" required>
                                <span class="input-group-text pdf-addon pdf-addon-suffix unit-suffix">Kg</span>
                            </div>
                            <div class="pdf-helper-bottom">Tank ya shelf mein maujooda quantity</div>
                            @error('current_stock')
                                <div class="text-danger small mt-0.5" style="font-size: 0.68rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="min_stock_alert" class="pdf-label">
                                <span>Low Stock Alert</span>
                                <span class="pdf-helper-right">Threshold</span>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control pdf-input @error('min_stock_alert') is-invalid @enderror"
                                       id="min_stock_alert" name="min_stock_alert" value="{{ old('min_stock_alert', $product->min_stock_alert) }}">
                                <span class="input-group-text pdf-addon pdf-addon-suffix unit-suffix">Kg</span>
                            </div>
                            <div class="pdf-helper-bottom">Level girne par warning aayegi</div>
                            @error('min_stock_alert')
                                <div class="text-danger small mt-0.5" style="font-size: 0.68rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="status" class="pdf-label">
                                <span>Product Status <span class="text-danger">*</span></span>
                            </label>
                            <select class="form-select pdf-input fw-semibold @error('status') is-invalid @enderror"
                                    id="status" name="status" required>
                                <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Active (Available for Sale)</option>
                                <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Inactive (Archived)</option>
                            </select>
                            <div class="pdf-helper-bottom">Active products Shift Closing & Sales mein nazar aate hain</div>
                            @error('status')
                                <div class="text-danger small mt-0.5" style="font-size: 0.68rem;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Row 2: Description / Specifications -->
                    <div class="mb-2">
                        <label for="description" class="pdf-label">
                            <span>Packaging & Technical Specifications</span>
                            <span class="pdf-helper-right">Optional</span>
                        </label>
                        <input type="text" class="form-control pdf-input @error('description') is-invalid @enderror"
                               id="description" name="description" value="{{ old('description', $product->description) }}"
                               placeholder="e.g. 4 Liters Tin Pack, Euro-5 compliant diesel, API SN/CF rating...">
                        @error('description')
                            <div class="text-danger small mt-0.5" style="font-size: 0.68rem;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="d-flex align-items-center justify-content-end gap-2 pt-2 border-top">
                    <a href="{{ route('products.index') }}" class="btn btn-light rounded-2 px-3 py-1 text-secondary border" style="font-size: 0.8rem;">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-register-action">
                        Update Product
                    </button>
                </div>

            </form>
        </div>

    </div>
</div>

<!-- Quick Add Category Modal -->
<div class="modal fade" id="quickAddCategoryModal" tabindex="-1" aria-labelledby="quickAddCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-2.5 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="text-white rounded-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background-color: #1e40af;">
                        <i class="bi bi-tag-fill text-white" style="font-size: 0.85rem;"></i>
                    </div>
                    <h6 class="modal-title fw-bold text-dark m-0" id="quickAddCategoryModalLabel" style="font-size: 0.92rem;">Quick Add Product Category</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickAddCategoryForm" onsubmit="handleQuickCategorySubmit(event)">
                <div class="modal-body p-3">
                    <div id="quickCategoryAlert" class="alert alert-danger py-1.5 px-2.5 small mb-2.5" style="display: none; font-size: 0.75rem;"></div>

                    <div class="mb-2.5">
                        <label for="quick_cat_name" class="pdf-label">
                            <span>Category Name <span class="text-danger">*</span></span>
                        </label>
                        <input type="text" class="form-control pdf-input fw-semibold" id="quick_cat_name" required
                               placeholder="e.g. Car Accessories & Tools">
                    </div>

                    <div class="mb-2.5">
                        <label for="quick_cat_slug" class="pdf-label">
                            <span>Category Identifier / Slug</span>
                            <span class="pdf-helper-right">Auto-generated if blank</span>
                        </label>
                        <input type="text" class="form-control pdf-input" id="quick_cat_slug"
                               placeholder="e.g. car_accessories">
                    </div>

                    <div class="mb-1">
                        <label for="quick_cat_desc" class="pdf-label">
                            <span>Description</span>
                            <span class="pdf-helper-right">Optional</span>
                        </label>
                        <input type="text" class="form-control pdf-input" id="quick_cat_desc"
                               placeholder="Brief note or description...">
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3">
                    <button type="button" class="btn btn-light btn-sm rounded-2 px-3 border" data-bs-dismiss="modal" style="font-size: 0.8rem;">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-2 px-3.5 fw-semibold" id="quickCatSubmitBtn" style="background-color: #1e40af; border-color: #1e40af; font-size: 0.8rem;">
                        <span class="spinner-border spinner-border-sm me-1" id="quickCatSpinner" style="display: none;"></span>
                        Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Add Unit Modal -->
<div class="modal fade" id="quickAddUnitModal" tabindex="-1" aria-labelledby="quickAddUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-2.5 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="text-white rounded-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background-color: #1e40af;">
                        <i class="bi bi-rulers text-white" style="font-size: 0.85rem;"></i>
                    </div>
                    <h6 class="modal-title fw-bold text-dark m-0" id="quickAddUnitModalLabel" style="font-size: 0.92rem;">Quick Add Unit of Measurement</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickAddUnitForm" onsubmit="handleQuickUnitSubmit(event)">
                <div class="modal-body p-3">
                    <div id="quickUnitAlert" class="alert alert-danger py-1.5 px-2.5 small mb-2.5" style="display: none; font-size: 0.75rem;"></div>

                    <div class="mb-2.5">
                        <label for="quick_unit_name" class="pdf-label">
                            <span>Unit Full Name <span class="text-danger">*</span></span>
                        </label>
                        <input type="text" class="form-control pdf-input fw-semibold" id="quick_unit_name" required
                               placeholder="e.g. Gallons (Gal) or Barrels (Bbl)">
                    </div>

                    <div class="mb-2.5">
                        <label for="quick_unit_code" class="pdf-label">
                            <span>Short Suffix / Unit Code <span class="text-danger">*</span></span>
                            <span class="pdf-helper-right">Shown on rates (e.g. / Gal)</span>
                        </label>
                        <input type="text" class="form-control pdf-input fw-semibold" id="quick_unit_code" required
                               placeholder="e.g. Gal, Bbl, Pouch">
                    </div>

                    <div class="mb-1">
                        <label for="quick_unit_desc" class="pdf-label">
                            <span>Description</span>
                            <span class="pdf-helper-right">Optional</span>
                        </label>
                        <input type="text" class="form-control pdf-input" id="quick_unit_desc"
                               placeholder="e.g. Standard US Liquid Gallon (3.785 L)">
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3">
                    <button type="button" class="btn btn-light btn-sm rounded-2 px-3 border" data-bs-dismiss="modal" style="font-size: 0.8rem;">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-2 px-3.5 fw-semibold" id="quickUnitSubmitBtn" style="background-color: #1e40af; border-color: #1e40af; font-size: 0.8rem;">
                        <span class="spinner-border spinner-border-sm me-1" id="quickUnitSpinner" style="display: none;"></span>
                        Save Unit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function calculateMargin() {
        const purchase = parseFloat(document.getElementById('purchase_price').value) || 0;
        const sale = parseFloat(document.getElementById('sale_price').value) || 0;
        const margin = sale - purchase;
        const pct = purchase > 0 ? ((margin / purchase) * 100).toFixed(0) : 0;

        const marginText = document.getElementById('marginAmountText');
        const marginBadge = document.getElementById('marginBadge');
        const marginBox = document.getElementById('marginDisplayBox');

        if (margin >= 0) {
            marginText.innerHTML = `<span class="text-success fw-bold">+Rs. ${margin.toFixed(2)} per unit</span>`;
            marginBadge.className = 'badge bg-success text-white px-2 py-1 fw-bold';
            marginBadge.textContent = `+${pct}%`;
            marginBox.style.background = '#f0fdf4';
            marginBox.style.borderColor = '#bbf7d0';
        } else {
            marginText.innerHTML = `<span class="text-danger fw-bold">-Rs. ${Math.abs(margin).toFixed(2)} (Loss)</span>`;
            marginBadge.className = 'badge bg-danger text-white px-2 py-1 fw-bold';
            marginBadge.textContent = `${pct}%`;
            marginBox.style.background = '#fef2f2';
            marginBox.style.borderColor = '#fca5a5';
        }
    }

    // Dynamic Unit Suffixes (Handles both preset and custom units)
    function updateUnitLabels() {
        const unitSelect = document.getElementById('unit');
        const unit = unitSelect ? unitSelect.value : 'liter';
        const selectedOption = unitSelect && unitSelect.selectedOptions ? unitSelect.selectedOptions[0] : null;
        const suffixes = document.querySelectorAll('.unit-suffix');
        
        const unitMap = {
            'liter': 'Ltr',
            'can': 'Can',
            'bottle': 'Bottle',
            'drum': 'Drum',
            'pack': 'Pack',
            'piece': 'Item',
            'kg': 'Kg'
        };

        let text = unitMap[unit];
        if (!text) {
            if (selectedOption && selectedOption.dataset && selectedOption.dataset.short) {
                text = selectedOption.dataset.short;
            } else if (selectedOption) {
                const match = selectedOption.textContent.match(/\(([^)]+)\)/);
                text = match ? match[1].trim() : selectedOption.textContent.trim();
            } else {
                text = unit;
            }
        }

        suffixes.forEach(el => {
            if (el.classList.contains('per-unit')) {
                el.textContent = '/ ' + text;
            } else {
                el.textContent = text;
            }
        });
    }

    // AJAX Quick Category Add
    function handleQuickCategorySubmit(e) {
        e.preventDefault();
        const alertBox = document.getElementById('quickCategoryAlert');
        const submitBtn = document.getElementById('quickCatSubmitBtn');
        const spinner = document.getElementById('quickCatSpinner');
        const nameInput = document.getElementById('quick_cat_name');
        const slugInput = document.getElementById('quick_cat_slug');
        const descInput = document.getElementById('quick_cat_desc');

        alertBox.style.display = 'none';
        submitBtn.disabled = true;
        spinner.style.display = 'inline-block';

        fetch("{{ route('products.categories.quick-store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: nameInput.value,
                slug: slugInput.value,
                description: descInput.value
            })
        })
        .then(async (res) => {
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || 'Validation error while saving category.');
            }
            return data;
        })
        .then((data) => {
            if (data.success && data.category) {
                const catSelect = document.getElementById('category');
                const opt = document.createElement('option');
                opt.value = data.category.slug;
                opt.textContent = data.category.name;
                opt.selected = true;
                catSelect.appendChild(opt);

                handleCategoryChange();

                // Close modal & reset form
                const modalEl = document.getElementById('quickAddCategoryModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.hide();
                document.getElementById('quickAddCategoryForm').reset();
            }
        })
        .catch((err) => {
            alertBox.style.display = 'block';
            alertBox.textContent = err.message || 'An error occurred while creating category.';
        })
        .finally(() => {
            submitBtn.disabled = false;
            spinner.style.display = 'none';
        });
    }

    // AJAX Quick Unit Add
    function handleQuickUnitSubmit(e) {
        e.preventDefault();
        const alertBox = document.getElementById('quickUnitAlert');
        const submitBtn = document.getElementById('quickUnitSubmitBtn');
        const spinner = document.getElementById('quickUnitSpinner');
        const nameInput = document.getElementById('quick_unit_name');
        const codeInput = document.getElementById('quick_unit_code');
        const descInput = document.getElementById('quick_unit_desc');

        alertBox.style.display = 'none';
        submitBtn.disabled = true;
        spinner.style.display = 'inline-block';

        fetch("{{ route('products.units.quick-store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: nameInput.value,
                short_code: codeInput.value,
                description: descInput.value
            })
        })
        .then(async (res) => {
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || 'Validation error while saving unit.');
            }
            return data;
        })
        .then((data) => {
            if (data.success && data.unit) {
                const unitSelect = document.getElementById('unit');
                const opt = document.createElement('option');
                opt.value = data.unit.short_code;
                opt.textContent = data.unit.name;
                opt.dataset.short = data.unit.short_code;
                opt.selected = true;
                unitSelect.appendChild(opt);

                updateUnitLabels();

                // Close modal & reset form
                const modalEl = document.getElementById('quickAddUnitModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.hide();
                document.getElementById('quickAddUnitForm').reset();
            }
        })
        .catch((err) => {
            alertBox.style.display = 'block';
            alertBox.textContent = err.message || 'An error occurred while creating unit.';
        })
        .finally(() => {
            submitBtn.disabled = false;
            spinner.style.display = 'none';
        });
    }

    function handleCategoryChange() {
        const cat = document.getElementById('category').value;
        const isFuelCheck = document.getElementById('is_fuel');
        if (cat === 'fuel' || cat === 'cng_lpg') {
            isFuelCheck.checked = true;
        }
        updateUnitLabels();
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateUnitLabels();
        calculateMargin();
    });
</script>
@endpush
@endsection
