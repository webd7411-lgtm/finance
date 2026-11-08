@extends('layouts.app')

@section('title', 'Petrol Pump Shift Closing')
@section('page_title', 'Petrol Pump Shift Closing Sheet')

@section('page_badge')
    <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-0.5 small" style="font-size: 0.72rem;">
        <i class="bi bi-fuel-pump me-1"></i> Meter Reading & Cash Reconciliation
    </span>
@endsection

@section('page_actions')
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-secondary border px-2 py-1 small d-none d-md-inline-block" style="font-size: 0.72rem;">
            <i class="bi bi-person-circle me-1 text-primary"></i> {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})
        </span>
        <a href="{{ route('shift-closings.index') }}" class="btn btn-outline-secondary btn-sm py-0.5 px-2 rounded-2" style="font-size: 0.75rem;">
            <i class="bi bi-arrow-left me-1"></i> History
        </a>
    </div>
@endsection

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

    body, body *, input, button, select, textarea, .font-monospace, code, kbd, samp, pre {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
        letter-spacing: normal !important;
    }
    /* Preserve Icon Fonts */
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

    /* Compact Layout Overrides */
    .sub-header {
        padding: 0.3rem 0 !important;
        margin-bottom: 0.4rem !important;
    }
    .sub-header h5 {
        font-size: 0.95rem !important;
        margin: 0 !important;
    }

    /* Compact Cards */
    .closing-card {
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        padding: 0.45rem 0.75rem;
        margin-bottom: 0.45rem;
    }
    .closing-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 0.25rem;
        margin-bottom: 0.35rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .closing-step-num {
        width: 19px;
        height: 19px;
        font-size: 0.7rem;
        font-weight: 700;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .closing-card-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    /* Form Inputs */
    .compact-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        line-height: 1.2;
    }
    .compact-input {
        height: 29px !important;
        padding: 0.15rem 0.45rem !important;
        font-size: 0.8rem !important;
        border-radius: 5px !important;
    }
    .compact-addon {
        height: 29px !important;
        padding: 0.15rem 0.45rem !important;
        font-size: 0.75rem !important;
    }

    /* Top KPI Ribbon */
    .kpi-compact-card {
        background: #ffffff;
        border-radius: 6px;
        padding: 0.3rem 0.55rem;
        border-left: 3.5px solid;
        border-top: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        min-height: 52px;
    }
    .kpi-title {
        font-size: 0.62rem;
        letter-spacing: 0.4px;
        line-height: 1.1;
    }
    .kpi-value {
        font-size: 1.02rem;
        line-height: 1.2;
        margin-top: 1px;
    }

    /* Denomination Table */
    .denomination-table {
        table-layout: fixed;
        width: 100%;
        margin-bottom: 0.3rem;
    }
    .denomination-table th, .denomination-table td {
        padding: 0.15rem 0.35rem !important;
        vertical-align: middle;
        font-size: 0.78rem;
    }
    .note-input {
        height: 23px !important;
        font-size: 0.8rem !important;
        padding: 0.05rem 0.3rem !important;
        text-align: center;
        border-radius: 4px;
    }
    .note-input::-webkit-inner-spin-button,
    .note-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
</style>
@endpush

@section('content')
<form action="{{ route('shift-closings.store') }}" method="POST" id="shiftClosingForm" novalidate>
    @csrf

    <!-- Top Live KPI Ribbon (Instant Shift Position) -->
    <div class="row g-1.5 mb-2 row-cols-2 row-cols-md-3 row-cols-lg-5">
        <!-- KPI 1: Total Volume Dispensed -->
        <div class="col">
            <div class="kpi-compact-card border-start border-4 border-primary h-100">
                <div class="text-muted fw-semibold text-uppercase kpi-title text-truncate">Fuel Dispensed</div>
                <div class="fw-bold text-primary kpi-value text-truncate" id="kpi_liters">0.00 Ltr</div>
                <small class="text-secondary d-none d-sm-block" style="font-size: 0.65rem; line-height: 1;">End - Start Reading</small>
            </div>
        </div>

        <!-- KPI 2: Total Gross Sales -->
        <div class="col">
            <div class="kpi-compact-card border-start border-4 border-info h-100">
                <div class="text-muted fw-semibold text-uppercase kpi-title text-truncate">Gross Total Sales</div>
                <div class="fw-bold text-dark kpi-value text-truncate" id="kpi_sales">Rs. 0.00</div>
                <small class="text-secondary d-none d-sm-block" style="font-size: 0.65rem; line-height: 1;">Liters &times; Fuel Rate</small>
            </div>
        </div>

        <!-- KPI 3: Expected Cash -->
        <div class="col">
            <div class="kpi-compact-card border-start border-4 border-warning h-100">
                <div class="text-muted fw-semibold text-uppercase kpi-title text-truncate">Expected Net Cash</div>
                <div class="fw-bold text-warning-emphasis kpi-value text-truncate" id="kpi_expected">Rs. 0.00</div>
                <small class="text-secondary d-none d-sm-block" style="font-size: 0.65rem; line-height: 1;">Total Sale - Expenses</small>
            </div>
        </div>

        <!-- KPI 4: Actual Received -->
        <div class="col">
            <div class="kpi-compact-card border-start border-4 border-success h-100">
                <div class="text-muted fw-semibold text-uppercase kpi-title text-truncate">Actual Collected</div>
                <div class="fw-bold text-success kpi-value text-truncate" id="kpi_actual">Rs. 0.00</div>
                <small class="text-secondary d-none d-sm-block" style="font-size: 0.65rem; line-height: 1;">Cash + Digital</small>
            </div>
        </div>

        <!-- KPI 5: Shift Variance / Reconciliation -->
        <div class="col col-12 col-md-12 col-lg">
            <div class="kpi-compact-card border-start border-4 border-secondary h-100" id="kpi_variance_card">
                <div class="d-flex justify-content-between align-items-center gap-1">
                    <span class="text-muted fw-semibold text-uppercase kpi-title text-truncate">Shift Variance</span>
                    <span class="badge bg-success small py-0 px-1" id="kpi_variance_badge" style="font-size: 0.62rem;">Balanced</span>
                </div>
                <div class="fw-bold text-dark kpi-value text-truncate" id="kpi_variance">Rs. 0.00</div>
                <small class="text-secondary d-none d-sm-block" id="kpi_variance_text" style="font-size: 0.65rem; line-height: 1;">Actual minus Expected</small>
            </div>
        </div>
    </div>

    <!-- Step 1: Shift & Pump Machine Configuration -->
    <div class="closing-card">
        <div class="closing-card-header">
            <div class="d-flex align-items-center gap-1.5">
                <span class="badge bg-primary text-white closing-step-num">1</span>
                <h6 class="closing-card-title">Shift Session & Pump Machine Setup</h6>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.68rem;">
                <i class="bi bi-infinity me-1"></i> Multiple Shifts Daily
            </span>
        </div>

        <div class="row g-1.5">
            <!-- Date -->
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="compact-label" for="closing_date">
                    <span><i class="bi bi-calendar3 text-primary me-1"></i>Closing Date <span class="text-danger">*</span></span>
                </label>
                <input type="date" id="closing_date" name="date"
                       class="form-control compact-input fw-semibold @error('date') is-invalid @enderror"
                       value="{{ old('date', date('Y-m-d')) }}"
                       min="{{ now()->subDay()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                @error('date')
                    <div class="invalid-feedback d-block small" style="font-size: 0.68rem;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Shift Type (Multiple allowed per day) -->
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="compact-label" for="shift_type_select">
                    <span><i class="bi bi-clock text-primary me-1"></i>Shift Cycle <span class="text-danger">*</span></span>
                </label>
                <select name="shift_type" id="shift_type_select" class="form-select compact-input fw-semibold @error('shift_type') is-invalid @enderror" required>
                    <option value="morning" {{ old('shift_type') === 'morning' ? 'selected' : '' }}>☀️ Morning Shift</option>
                    <option value="evening" {{ old('shift_type', 'evening') === 'evening' ? 'selected' : '' }}>🌙 Evening Shift</option>
                </select>
                @error('shift_type')
                    <div class="invalid-feedback d-block small" style="font-size: 0.68rem;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Cashier Selection -->
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="compact-label" for="cashier_id">
                    <span><i class="bi bi-person-badge text-primary me-1"></i>Shift Cashier <span class="text-danger">*</span></span>
                    <a href="{{ route('hr.employees.index') }}" target="_blank" class="text-primary text-decoration-none" style="font-size: 0.65rem;">+ Add / Manage</a>
                </label>
                <select name="cashier_id" id="cashier_id" class="form-select compact-input fw-semibold @error('cashier_id') is-invalid @enderror" required>
                    <option value="">-- Choose Cashier --</option>
                    @foreach($cashiers as $c)
                        <option value="{{ $c->id }}" {{ old('cashier_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->code ?: '#' . $c->id }})
                        </option>
                    @endforeach
                </select>
                @error('cashier_id')
                    <div class="invalid-feedback d-block small" style="font-size: 0.68rem;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Dynamic Pump Machine Selection -->
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="compact-label" for="pump_id">
                    <span><i class="bi bi-fuel-pump text-primary me-1"></i>Pump Machine <span class="badge bg-light text-primary border ms-1" style="font-size: 0.63rem;">{{ count($pumps) }} Units</span> <span class="text-danger">*</span></span>
                    <div class="d-flex gap-1.5 align-items-center">
                        <button type="button" class="btn btn-link p-0 text-primary text-decoration-none fw-semibold" style="font-size: 0.65rem;" data-bs-toggle="modal" data-bs-target="#quickAddPumpModal">
                            + Add New
                        </button>
                        <span class="text-muted" style="font-size: 0.6rem;">|</span>
                        <a href="{{ route('pumps.index') }}" target="_blank" class="text-primary text-decoration-none" style="font-size: 0.65rem;">Manage</a>
                    </div>
                </label>
                <select name="pump_id" id="pump_id" class="form-select compact-input fw-bold @error('pump_id') is-invalid @enderror" onchange="handlePumpSelection()">
                    <option value="">-- Choose Pump Machine ({{ count($pumps) }} Available) --</option>
                    @foreach($pumps as $pump)
                        <option value="{{ $pump->id }}"
                                data-name="{{ $pump->name }}"
                                data-reading="{{ $pump->last_reading }}"
                                data-product-id="{{ $pump->product_id }}"
                                data-product-rate="{{ $pump->product?->sale_price ?? 0 }}"
                                data-product-unit="{{ $pump->product?->unit_label ?? 'Ltr' }}"
                                data-tank="{{ $pump->tank_no ?: ($pump->product?->tank_or_shelf ?: 'Underground Tank') }}"
                                data-stock="{{ $pump->product?->current_stock ?? 0 }}"
                                {{ (old('pump_id', $selectedPumpId ?? null) == $pump->id) ? 'selected' : '' }}>
                            #{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} • {{ $pump->name }} (Last: {{ number_format($pump->last_reading, 2) }})
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="pump_machine" id="pump_machine" value="{{ old('pump_machine') }}">
                <div id="pump_stock_sync_badge" class="mt-1 d-none">
                    <span class="badge bg-light text-dark border py-0.5 px-1.5 small" style="font-size: 0.65rem;">
                        <i class="bi bi-database text-primary me-1"></i><span id="pump_tank_display">Tank</span>:
                        <strong id="pump_stock_display" class="text-success">0 Ltr</strong> stock
                    </span>
                </div>
                @error('pump_id')
                    <div class="invalid-feedback d-block small" style="font-size: 0.68rem;">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Step 2: Petrol Pump Meter Reading & Auto-Calculation System (Single Unified Row) -->
    <div class="closing-card border-primary border-opacity-25" style="border-left: 3.5px solid #2563eb;">
        <div class="closing-card-header">
            <div class="d-flex align-items-center gap-1.5">
                <span class="badge bg-primary text-white closing-step-num">2</span>
                <h6 class="closing-card-title">
                    <i class="bi bi-speedometer2 text-primary me-1"></i> Meter Readings & Automatic Sale Calculation
                </h6>
            </div>
            <div class="badge bg-light text-primary border px-1.5 py-0.5" style="font-size: 0.68rem;">
                Liters = End - Start Reading
            </div>
        </div>

        <div class="row g-1.5 align-items-end">
            <!-- 1. Fuel Product Selection -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                <label class="compact-label" for="product_id">
                    <span>Fuel Product <span class="text-danger">*</span></span>
                </label>
                <select name="product_id" id="product_id" class="form-select compact-input fw-semibold" onchange="handleProductRateChange()">
                    <option value="">-- Choose Product --</option>
                    @foreach($fuelProducts as $fProd)
                        <option value="{{ $fProd->id }}"
                                data-rate="{{ $fProd->sale_price }}"
                                data-unit="{{ $fProd->unit_label }}"
                                {{ old('product_id') == $fProd->id ? 'selected' : '' }}>
                            {{ $fProd->name }} (Rs. {{ number_format($fProd->sale_price, 2) }})
                        </option>
                    @endforeach
                </select>
                @error('product_id')
                    <div class="text-danger small mt-0.5" style="font-size: 0.68rem;">{{ $message }}</div>
                @enderror
            </div>

            <!-- 2. Start Meter Reading -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                <label class="compact-label" for="start_reading">
                    <span>Start Reading <span class="text-danger">*</span></span>
                    <span class="text-muted" style="font-size: 0.62rem;">Opening</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-success compact-addon"><i class="bi bi-play-fill"></i></span>
                    <input type="number" step="0.01" min="0" id="start_reading" name="start_reading"
                           class="form-control compact-input fw-bold @error('start_reading') is-invalid @enderror"
                           placeholder="0.00" value="{{ old('start_reading', '0.00') }}" required oninput="calculateReadings()">
                </div>
                @error('start_reading')
                    <div class="invalid-feedback d-block small" style="font-size: 0.68rem;">{{ $message }}</div>
                @enderror
            </div>

            <!-- 3. End Meter Reading -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                <label class="compact-label" for="end_reading">
                    <span>End Reading <span class="text-danger">*</span></span>
                    <span class="text-muted" style="font-size: 0.62rem;">Closing</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-danger compact-addon"><i class="bi bi-stop-fill"></i></span>
                    <input type="number" step="0.01" min="0" id="end_reading" name="end_reading"
                           class="form-control compact-input fw-bold @error('end_reading') is-invalid @enderror"
                           placeholder="0.00" value="{{ old('end_reading', '0.00') }}" required oninput="calculateReadings()">
                </div>
                @error('end_reading')
                    <div class="invalid-feedback d-block small" style="font-size: 0.68rem;">{{ $message }}</div>
                @enderror
            </div>

            <!-- 4. Fuel Unit Rate -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                <label class="compact-label" for="fuel_rate">
                    <span>Rate / Ltr (Rs.) <span class="text-danger">*</span></span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light fw-semibold compact-addon">Rs.</span>
                    <input type="number" step="0.01" min="0" id="fuel_rate" name="fuel_rate"
                           class="form-control compact-input fw-bold @error('fuel_rate') is-invalid @enderror"
                           placeholder="0.00" value="{{ old('fuel_rate', '0.00') }}" required oninput="calculateReadings()">
                </div>
                @error('fuel_rate')
                    <div class="invalid-feedback d-block small" style="font-size: 0.68rem;">{{ $message }}</div>
                @enderror
            </div>

            <!-- 5. Total Liters (Calculated) -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                <label class="compact-label">
                    <span>Total Dispensed</span>
                </label>
                <div class="input-group">
                    <input type="number" step="0.01" id="total_liters" name="total_liters"
                           class="form-control compact-input fw-bold text-center text-primary bg-light"
                           value="{{ old('total_liters', '0.00') }}" readonly>
                    <span class="input-group-text bg-light text-muted compact-addon" id="litersUnitBadge">Ltr</span>
                </div>
            </div>

            <!-- 6. Gross Sale (Auto Calculated & Inline) -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                <label class="compact-label text-success" for="total_sale">
                    <span class="fw-bold">Gross Sale (Rs.) <span class="text-danger">*</span></span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-success bg-opacity-10 text-success fw-bold compact-addon">Rs.</span>
                    <input type="number" step="0.01" min="0" id="total_sale" name="total_sale"
                           class="form-control compact-input fw-bold text-dark text-end bg-success bg-opacity-10 border-success"
                           value="{{ old('total_sale', '0.00') }}" required oninput="calculateCashBalance()">
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-1 pt-1 border-top border-light-subtle">
            <span class="text-secondary" style="font-size: 0.68rem;">
                <i class="bi bi-info-circle me-1"></i>Formula: <span id="saleFormulaDisplay" class="fw-semibold text-dark">0.00 Liters &times; Rs. 0.00</span>
            </span>
            <span class="text-muted" style="font-size: 0.65rem;">Gross sale editable if manual adjustment needed</span>
        </div>
    </div>

    <!-- Main Workspace (2-Column Balanced Grid) -->
    <div class="row g-2">
        <!-- LEFT COLUMN: Shift Expenses & Digital / Online Collections -->
        <div class="col-12 col-lg-6">
            <div class="d-flex flex-column gap-2">

                <!-- Step 3: Shift Counter Expenses -->
                <div class="closing-card">
                    <div class="closing-card-header">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge bg-primary text-white closing-step-num">3</span>
                            <h6 class="closing-card-title">
                                <i class="bi bi-wallet2 text-danger me-1"></i> Shift Counter Expenses
                            </h6>
                        </div>
                        <span class="text-muted" style="font-size: 0.65rem;">Petty cash from counter</span>
                    </div>

                    <div class="row g-1.5">
                        <div class="col-12 col-sm-5">
                            <label class="compact-label" for="expenses_amount">Total Cash Expenses</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-danger fw-bold compact-addon">Rs.</span>
                                <input type="number" step="0.01" min="0" class="form-control compact-input text-danger fw-bold"
                                       id="expenses_amount" name="expenses_amount"
                                       value="{{ old('expenses_amount', '0.00') }}" placeholder="0.00" oninput="calculateCashBalance()">
                            </div>
                        </div>

                        <div class="col-12 col-sm-7">
                            <label class="compact-label" for="expenses_details">Expense Details / Category</label>
                            <input type="text" class="form-control compact-input" id="expenses_details" name="expenses_details"
                                   value="{{ old('expenses_details') }}" placeholder="e.g. Pump cleaning, Tea, Generator oil">
                        </div>
                    </div>
                </div>

                <!-- Step 4: Digital & Card Collections -->
                <div class="closing-card">
                    <div class="closing-card-header">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge bg-primary text-white closing-step-num">4</span>
                            <h6 class="closing-card-title">
                                <i class="bi bi-bank text-info me-1"></i> Digital & Card Collections
                            </h6>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill py-0.5 px-2" style="font-size: 0.72rem;" id="addAccountPayment">
                            <i class="bi bi-plus-circle me-1"></i> Add Digital
                        </button>
                    </div>

                    <div id="accountPaymentRows" class="d-flex flex-column gap-1.5">
                        <!-- Dynamic Account Payment Rows Added by JS -->
                    </div>

                    <div class="p-1.5 px-2 bg-light rounded-2 mt-1.5 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-secondary" style="font-size: 0.75rem;">Total Online Collections:</span>
                        <span class="fw-bold text-primary" id="display_digital_total" style="font-size: 0.85rem;">Rs. 0.00</span>
                    </div>
                </div>

                <!-- Shift Remarks -->
                <div class="closing-card">
                    <label class="compact-label mb-1" for="remarks">
                        <span><i class="bi bi-chat-text text-primary me-1"></i> Shift Operator Remarks / Notes</span>
                    </label>
                    <textarea class="form-control compact-input" id="remarks" name="remarks" rows="1"
                              style="height: 32px; resize: vertical;"
                              placeholder="Optional pump calibration notes, power outage, or dip details...">{{ old('remarks') }}</textarea>
                </div>

            </div>
        </div>

        <!-- RIGHT COLUMN: Physical Cash Denomination Counter & Variance -->
        <div class="col-12 col-lg-6">
            <div class="d-flex flex-column gap-2">

                <!-- Step 5: Cash Note Denominations -->
                <div class="closing-card border-success border-opacity-25" style="border-left: 3.5px solid #16a34a;">
                    <div class="closing-card-header">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge bg-success text-white closing-step-num">5</span>
                            <h6 class="closing-card-title">
                                <i class="bi bi-cash-stack text-success me-1"></i> Physical Cash Denomination Counter
                            </h6>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.65rem;">
                            Drawer Cash
                        </span>
                    </div>

                    <table class="table table-sm denomination-table table-bordered align-middle mb-1">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 32%;">Note</th>
                                <th style="width: 33%;" class="text-center">Count</th>
                                <th style="width: 35%;" class="text-end">Total (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $notes = [
                                    ['name' => 'note_5000', 'value' => 5000, 'label' => 'Rs. 5,000'],
                                    ['name' => 'note_1000', 'value' => 1000, 'label' => 'Rs. 1,000'],
                                    ['name' => 'note_500',  'value' => 500,  'label' => 'Rs. 500'],
                                    ['name' => 'note_100',  'value' => 100,  'label' => 'Rs. 100'],
                                    ['name' => 'note_50',   'value' => 50,   'label' => 'Rs. 50'],
                                    ['name' => 'note_20',   'value' => 20,   'label' => 'Rs. 20'],
                                    ['name' => 'note_10',   'value' => 10,   'label' => 'Rs. 10'],
                                ];
                            @endphp

                            @foreach($notes as $note)
                                <tr>
                                    <td class="fw-bold text-dark py-0.5">{{ $note['label'] }}</td>
                                    <td class="py-0.5">
                                        <input type="number" min="0" name="{{ $note['name'] }}" id="{{ $note['name'] }}"
                                               class="form-control form-control-sm note-input fw-bold"
                                               value="{{ old($note['name'], 0) }}" data-multiplier="{{ $note['value'] }}"
                                               oninput="calculateDenominations()">
                                    </td>
                                    <td class="text-end fw-semibold text-secondary py-0.5" id="total_{{ $note['name'] }}">
                                        0.00
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Coins -->
                            <tr>
                                <td class="fw-bold text-dark py-0.5">Coins</td>
                                <td class="py-0.5">
                                    <input type="number" step="0.01" min="0" name="coins" id="coins"
                                           class="form-control form-control-sm note-input fw-bold"
                                           value="{{ old('coins', '0.00') }}" oninput="calculateDenominations()">
                                </td>
                                <td class="text-end fw-semibold text-secondary py-0.5" id="total_coins">
                                    0.00
                                </td>
                            </tr>

                            <!-- Counted Cash Total Row -->
                            <tr class="table-success bg-opacity-25">
                                <th colspan="2" class="fw-bold text-dark py-1">Total Counted Cash:</th>
                                <th class="text-end text-success fw-bold py-1" id="display_counted_cash">
                                    Rs. 0.00
                                </th>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Final Reconciliation Summary & Submit Card -->
                <div class="closing-card border-dark border-opacity-25">
                    <h6 class="fw-bold text-dark mb-1.5 pb-1 border-bottom" style="font-size: 0.8rem;">
                        <i class="bi bi-shield-check text-primary me-1"></i> Final Shift Reconciliation
                    </h6>

                    <div class="d-flex justify-content-between p-1 px-2 bg-light rounded-2 small mb-1" style="font-size: 0.75rem;">
                        <span class="text-secondary">Expected Cash (Sale - Expenses):</span>
                        <strong class="text-dark fw-bold" id="summary_expected">Rs. 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between p-1 px-2 bg-light rounded-2 small mb-1" style="font-size: 0.75rem;">
                        <span class="text-secondary">Actual Received (Cash + Digital):</span>
                        <strong class="text-success fw-bold" id="summary_actual">Rs. 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between p-1 px-2 rounded-2 small mb-2 border" id="summary_variance_box" style="font-size: 0.75rem;">
                        <span class="fw-bold">Shift Variance (Difference):</span>
                        <strong class="fw-bold" id="summary_difference">Rs. 0.00</strong>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-sm rounded-2 fw-bold shadow-sm py-1.5" id="submitClosingBtn" style="font-size: 0.88rem;">
                            <i class="bi bi-lock-fill me-1"></i> Lock & Finalize Shift Closing
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

</form>

@push('scripts')
<script>
    // Accounts dataset for online payment row creation
    const availableAccounts = @json($accounts);

    // 1. Pump Selection Handler
    function handlePumpSelection() {
        const pumpSelect = document.getElementById('pump_id');
        const selected = pumpSelect.options[pumpSelect.selectedIndex];
        const syncBadge = document.getElementById('pump_stock_sync_badge');

        if (selected && selected.value) {
            const name = selected.getAttribute('data-name');
            const lastReading = parseFloat(selected.getAttribute('data-reading')) || 0;
            const prodId = selected.getAttribute('data-product-id');
            const prodRate = parseFloat(selected.getAttribute('data-product-rate')) || 0;
            const tank = selected.getAttribute('data-tank') || 'Storage Tank';
            const stock = parseFloat(selected.getAttribute('data-stock')) || 0;

            document.getElementById('pump_machine').value = name;
            document.getElementById('start_reading').value = lastReading.toFixed(2);

            // Update Tank and Stock badge
            if (syncBadge) {
                syncBadge.classList.remove('d-none');
                document.getElementById('pump_tank_display').textContent = tank;
                const stockDisplay = document.getElementById('pump_stock_display');
                stockDisplay.textContent = stock.toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' Ltr';
                if (stock <= 3000) {
                    stockDisplay.className = 'text-danger fw-bold';
                } else {
                    stockDisplay.className = 'text-success fw-bold';
                }
            }

            // Select corresponding fuel product
            if (prodId) {
                const prodSelect = document.getElementById('product_id');
                prodSelect.value = prodId;
                handleProductRateChange();
            } else if (prodRate > 0) {
                document.getElementById('fuel_rate').value = prodRate.toFixed(2);
            }
        } else {
            if (syncBadge) {
                syncBadge.classList.add('d-none');
            }
        }
        calculateReadings();
    }

    // 2. Product Rate Selection Handler
    function handleProductRateChange() {
        const prodSelect = document.getElementById('product_id');
        const selected = prodSelect.options[prodSelect.selectedIndex];

        if (selected && selected.value) {
            const rate = parseFloat(selected.getAttribute('data-rate')) || 0;
            const unit = selected.getAttribute('data-unit') || 'Ltr';
            document.getElementById('fuel_rate').value = rate.toFixed(2);
            document.getElementById('litersUnitBadge').textContent = unit;
        }
        calculateReadings();
    }

    // 3. Real-Time Meter Reading & Sale Calculation (Core Feature!)
    function calculateReadings() {
        const start = parseFloat(document.getElementById('start_reading').value) || 0;
        const end = parseFloat(document.getElementById('end_reading').value) || 0;
        const rate = parseFloat(document.getElementById('fuel_rate').value) || 0;

        const liters = Math.max(0, end - start);
        const sale = liters * rate;

        document.getElementById('total_liters').value = liters.toFixed(2);
        document.getElementById('total_sale').value = sale.toFixed(2);

        document.getElementById('kpi_liters').textContent = liters.toFixed(2) + ' Ltr';
        document.getElementById('kpi_sales').textContent = 'Rs. ' + sale.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('saleFormulaDisplay').textContent = `${liters.toFixed(2)} Liters × Rs. ${rate.toFixed(2)}`;

        calculateCashBalance();
    }

    // 4. Denomination Calculator
    function calculateDenominations() {
        let totalCounted = 0;
        const multipliers = [5000, 1000, 500, 100, 50, 20, 10];

        multipliers.forEach(mult => {
            const input = document.getElementById('note_' + mult);
            const count = parseInt(input.value) || 0;
            const noteTotal = count * mult;
            totalCounted += noteTotal;
            document.getElementById('total_note_' + mult).textContent = noteTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        });

        const coins = parseFloat(document.getElementById('coins').value) || 0;
        totalCounted += coins;
        document.getElementById('total_coins').textContent = coins.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        document.getElementById('display_counted_cash').textContent = 'Rs. ' + totalCounted.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        calculateCashBalance();
    }

    // 5. Total Digital Collections
    function calculateDigitalTotal() {
        let digitalTotal = 0;
        document.querySelectorAll('.digital-account-amount').forEach(inp => {
            digitalTotal += parseFloat(inp.value) || 0;
        });
        document.getElementById('display_digital_total').textContent = 'Rs. ' + digitalTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return digitalTotal;
    }

    // 6. Overall Cash Reconciliation & Variance Calculator
    function calculateCashBalance() {
        const totalSale = parseFloat(document.getElementById('total_sale').value) || 0;
        const expenses = parseFloat(document.getElementById('expenses_amount').value) || 0;

        // Expected Cash = Total Sale - Shift Expenses
        const expectedCash = Math.max(0, totalSale - expenses);

        // Counted Cash
        let countedCash = 0;
        [5000, 1000, 500, 100, 50, 20, 10].forEach(mult => {
            countedCash += (parseInt(document.getElementById('note_' + mult)?.value) || 0) * mult;
        });
        countedCash += (parseFloat(document.getElementById('coins')?.value) || 0);

        // Digital Collections
        const digitalCollections = calculateDigitalTotal();

        // Actual Received
        const actualReceived = countedCash + digitalCollections;

        // Variance = Actual - Expected
        const difference = actualReceived - expectedCash;

        // Update KPIs & Summary
        document.getElementById('kpi_expected').textContent = 'Rs. ' + expectedCash.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('kpi_actual').textContent = 'Rs. ' + actualReceived.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summary_expected').textContent = 'Rs. ' + expectedCash.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summary_actual').textContent = 'Rs. ' + actualReceived.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const varianceCard = document.getElementById('kpi_variance_card');
        const varianceBadge = document.getElementById('kpi_variance_badge');
        const varianceText = document.getElementById('kpi_variance');
        const summaryDiff = document.getElementById('summary_difference');
        const summaryBox = document.getElementById('summary_variance_box');

        if (Math.abs(difference) < 0.01) {
            varianceCard.className = 'kpi-compact-card border-start border-4 border-success h-100 shadow-sm';
            varianceBadge.className = 'badge bg-success small py-0 px-1';
            varianceBadge.textContent = 'Balanced';
            varianceText.className = 'fw-bold text-success kpi-value text-truncate';
            varianceText.textContent = 'Rs. 0.00';
            summaryDiff.className = 'fs-6 text-success fw-bold';
            summaryDiff.textContent = 'Rs. 0.00 (Balanced)';
            summaryBox.className = 'd-flex justify-content-between p-1 px-2 rounded-2 small mb-2 border bg-success bg-opacity-10 border-success-subtle';
        } else if (difference > 0) {
            varianceCard.className = 'kpi-compact-card border-start border-4 border-info h-100 shadow-sm';
            varianceBadge.className = 'badge bg-info text-dark small py-0 px-1';
            varianceBadge.textContent = 'Surplus';
            varianceText.className = 'fw-bold text-info kpi-value text-truncate';
            varianceText.textContent = '+Rs. ' + difference.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            summaryDiff.className = 'fs-6 text-info fw-bold';
            summaryDiff.textContent = '+Rs. ' + difference.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (Surplus)';
            summaryBox.className = 'd-flex justify-content-between p-1 px-2 rounded-2 small mb-2 border bg-info bg-opacity-10 border-info-subtle';
        } else {
            varianceCard.className = 'kpi-compact-card border-start border-4 border-danger h-100 shadow-sm';
            varianceBadge.className = 'badge bg-danger small py-0 px-1';
            varianceBadge.textContent = 'Shortage';
            varianceText.className = 'fw-bold text-danger kpi-value text-truncate';
            varianceText.textContent = '-Rs. ' + Math.abs(difference).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            summaryDiff.className = 'fs-6 text-danger fw-bold';
            summaryDiff.textContent = '-Rs. ' + Math.abs(difference).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (Shortage)';
            summaryBox.className = 'd-flex justify-content-between p-1 px-2 rounded-2 small mb-2 border bg-danger bg-opacity-10 border-danger-subtle';
        }
    }

    // 7. Dynamic Online Payment Row Addition
    let digitalRowCounter = 0;
    function addDigitalAccountRow() {
        const container = document.getElementById('accountPaymentRows');
        const rowId = 'digital_row_' + digitalRowCounter++;

        let optionsHtml = '<option value="">-- Choose Account --</option>';
        availableAccounts.forEach(acc => {
            optionsHtml += `<option value="${acc.id}">${acc.name} (${acc.type})</option>`;
        });

        const rowHtml = `
            <div class="row g-1 align-items-center p-1 bg-light rounded-2 border" id="${rowId}">
                <div class="col-12 col-md-5">
                    <select name="account_payments[${digitalRowCounter}][account_id]" class="form-select compact-input" style="height: 27px !important; font-size: 0.75rem !important;" required>
                        ${optionsHtml}
                    </select>
                </div>
                <div class="col-7 col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white compact-addon py-0 px-1" style="height: 27px !important; font-size: 0.7rem !important;">Rs.</span>
                        <input type="number" step="0.01" min="0.01" name="account_payments[${digitalRowCounter}][amount]"
                               class="form-control compact-input fw-bold digital-account-amount" style="height: 27px !important; font-size: 0.75rem !important;" placeholder="0.00" required oninput="calculateCashBalance()">
                    </div>
                </div>
                <div class="col-3 col-md-3">
                    <input type="text" name="account_payments[${digitalRowCounter}][description]" class="form-control compact-input" style="height: 27px !important; font-size: 0.75rem !important;" placeholder="Ref/Note">
                </div>
                <div class="col-2 col-md-1 text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm p-0 px-1.5" style="height: 27px; line-height: 25px;" onclick="document.getElementById('${rowId}').remove(); calculateCashBalance();">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', rowHtml);
    }

    document.getElementById('addAccountPayment').addEventListener('click', addDigitalAccountRow);

    // Quick Add Pump Machine AJAX Handler
    const quickAddForm = document.getElementById('quickAddPumpForm');
    if (quickAddForm) {
        quickAddForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('quickAddSubmitBtn');
            const alertBox = document.getElementById('quickAddAlert');
            alertBox.classList.add('d-none');
            alertBox.textContent = '';
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

            const formData = new FormData(quickAddForm);

            fetch("{{ route('pumps.quick-store') }}", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(result => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save & Select Machine';

                if (result.status === 200 && result.body.success) {
                    const pump = result.body.pump;
                    const select = document.getElementById('pump_id');

                    // Create new option
                    const opt = document.createElement('option');
                    opt.value = pump.id;
                    opt.setAttribute('data-name', pump.name);
                    opt.setAttribute('data-reading', pump.last_reading);
                    opt.setAttribute('data-product-id', pump.product_id);
                    opt.setAttribute('data-product-rate', pump.fuel_rate);
                    opt.setAttribute('data-product-unit', pump.unit);
                    opt.setAttribute('data-tank', pump.tank_no);
                    opt.setAttribute('data-stock', pump.current_stock);
                    opt.textContent = `${pump.name} (Last: ${pump.last_reading.toFixed(2)})`;
                    opt.selected = true;

                    select.appendChild(opt);
                    handlePumpSelection();

                    // Close modal
                    const modalEl = document.getElementById('quickAddPumpModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    quickAddForm.reset();
                } else {
                    const msg = result.body.message || (result.body.errors ? Object.values(result.body.errors).flat().join('<br>') : 'Error creating machine.');
                    alertBox.innerHTML = msg;
                    alertBox.classList.remove('d-none');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save & Select Machine';
                alertBox.textContent = 'Server communication error. Please try again.';
                alertBox.classList.remove('d-none');
            });
        });
    }

    // Initial page load triggers
    document.addEventListener('DOMContentLoaded', () => {
        calculateDenominations();
        if (document.getElementById('pump_id').value) {
            handlePumpSelection();
        } else {
            calculateReadings();
        }
    });
</script>
@endpush

<!-- Modal: Quick Add Pump Machine -->
<div class="modal fade" id="quickAddPumpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="quickAddPumpForm">
                @csrf
                <div class="modal-header bg-primary text-white py-2 px-3">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="bi bi-fuel-pump-diesel me-1.5"></i> Add New Pump Machine / Dispenser
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div id="quickAddAlert" class="alert alert-danger py-2 px-3 small d-none mb-2.5"></div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">Dispenser Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Pump Machine 5 (Super Petrol)" required>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark mb-1">Code / ID</label>
                            <input type="text" name="code" class="form-control form-control-sm text-uppercase" placeholder="e.g. PUMP-05">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark mb-1">Meter Type <span class="text-danger">*</span></label>
                            <select name="meter_type" class="form-select form-select-sm" required>
                                <option value="electronic" selected>Electronic Digital</option>
                                <option value="mechanical">Mechanical Roller</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">Dispensed Fuel Product <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select form-select-sm" required>
                            <option value="">-- Select Fuel --</option>
                            @foreach($fuelProducts as $fp)
                                <option value="{{ $fp->id }}">
                                    {{ $fp->name }} (Rs. {{ number_format($fp->sale_price, 2) }}/{{ $fp->unit_label }}) - Stock: {{ number_format($fp->current_stock, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark mb-1">Storage Tank</label>
                            <input type="text" name="tank_no" class="form-control form-control-sm" placeholder="e.g. Tank #1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark mb-1">Nozzle Config</label>
                            <input type="text" name="nozzle_number" class="form-control form-control-sm" placeholder="e.g. Nozzle 1 & 2">
                        </div>
                    </div>

                    <div class="mb-2 p-2 bg-light rounded border">
                        <label class="form-label small fw-bold text-primary mb-1">
                            <i class="bi bi-speedometer2 me-1"></i>Initial / Starting Meter Reading <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="initial_reading" class="form-control form-control-sm fw-bold" placeholder="0.00" value="0.00" required>
                        <small class="text-muted" style="font-size: 0.65rem;">Opening reading on this dispenser.</small>
                    </div>

                    <input type="hidden" name="status" value="active">
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="quickAddSubmitBtn" class="btn btn-primary btn-sm px-3 fw-semibold">
                        <i class="bi bi-check-circle me-1"></i>Save & Select Machine
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
