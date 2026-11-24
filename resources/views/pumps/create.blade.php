@extends('layouts.app')

@section('title', 'Add Petrol Pump Machine & Nozzles')
@section('page_title', 'Register Dispenser Machine & Configure Nozzles')

@section('page_badge')
    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem; font-weight: 600;">
        <i class="bi bi-speedometer2 me-1"></i> Multi-Nozzle Dispenser Setup
    </span>
@endsection

@section('page_actions')
    <a href="{{ route('pumps.index') }}" class="btn btn-outline-secondary btn-sm px-3 py-1.5 rounded-3 fw-semibold shadow-xs" style="font-size: 0.81rem;">
        <i class="bi bi-arrow-left me-1"></i> Back to Machines
    </a>
@endsection

@push('styles')
<!-- Google Font: Plus Jakarta Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

<style>
    body, body *, input, button, select, textarea, .form-monospace, code, kbd, samp, pre {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
        letter-spacing: normal !important;
    }
    .bi, [class^="bi-"], [class*=" bi-"] {
        font-family: "bootstrap-icons" !important;
    }

    /* Full Page Form Card */
    .pump-form-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        padding: 1.15rem 1.4rem;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 767.98px) {
        .pump-form-card {
            padding: 0.9rem 0.85rem;
            border-radius: 10px;
        }
    }

    /* Section Numbered Step Badge */
    .section-step-badge {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #1e40af;
        color: #ffffff;
        font-size: 0.7rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .section-title {
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.4px;
        color: #0f172a;
        text-transform: uppercase;
        margin: 0;
    }

    /* Labels & Helpers */
    .field-label {
        font-size: 0.73rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 3px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        line-height: 1.2;
    }
    .field-helper-right {
        font-size: 0.67rem;
        font-weight: 400;
        color: #94a3b8;
    }
    .field-helper-bottom {
        font-size: 0.67rem;
        color: #64748b;
        margin-top: 3px;
        line-height: 1.2;
    }

    /* Clean Compact Inputs */
    .compact-input {
        height: 35px !important;
        padding: 0.3rem 0.7rem !important;
        font-size: 0.82rem !important;
        border-radius: 7px !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #ffffff !important;
        color: #0f172a !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .compact-input:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
        outline: none !important;
    }
    .compact-addon {
        height: 35px !important;
        padding: 0.3rem 0.65rem !important;
        font-size: 0.78rem !important;
        background-color: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        color: #64748b !important;
    }
    .compact-addon-prefix {
        border-top-left-radius: 7px !important;
        border-bottom-left-radius: 7px !important;
    }
    .compact-addon-suffix {
        border-top-right-radius: 7px !important;
        border-bottom-right-radius: 7px !important;
    }

    /* Reading highlight boxes */
    .reading-highlight-box {
        background: #f0f7ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 0.45rem 0.65rem;
    }
    .reading-live-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.45rem 0.65rem;
    }

    /* Nozzle Card Item */
    .nozzle-card-item {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.85rem 1rem;
        transition: all 0.2s ease;
        position: relative;
    }
    .nozzle-card-item:hover {
        border-color: #93c5fd;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.06);
    }
    .nozzle-badge-pill {
        background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
        color: #ffffff;
        font-weight: 700;
        font-size: 0.72rem;
        padding: 0.22rem 0.65rem;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 1px 3px rgba(37, 99, 235, 0.2);
    }
    .btn-add-nozzle-main {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.81rem;
        height: 35px;
        padding: 0 1.1rem;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    .btn-add-nozzle-main:hover {
        background-color: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Main Pump Entry Form Card -->
        <div class="pump-form-card">
            
            <!-- Card Header -->
            <div class="d-flex align-items-center justify-content-between pb-2.5 mb-3 border-bottom flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="text-white rounded-3 d-flex align-items-center justify-content-center shadow-xs" style="width: 38px; height: 38px; background-color: #1e40af !important;">
                        <i class="bi bi-fuel-pump-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark m-0" style="font-size: 0.98rem;">Pump Machine &amp; Meter Baseline Setup</h6>
                        <small class="text-secondary" style="font-size: 0.74rem;">Configure dispenser hardware, connect multiple fuel nozzles to tanks, and set opening meter readings.</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-secondary border px-2.5 py-1" style="font-size: 0.7rem; font-weight: 500;">
                        <i class="bi bi-cpu me-1 text-primary"></i>Dispenser Registration
                    </span>
                </div>
            </div>

            <form action="{{ route('pumps.store') }}" method="POST" id="pumpMachineForm">
                @csrf

                <!-- SECTION 1: HARDWARE IDENTIFICATION & OPERATIONAL STATUS -->
                <div class="mb-3.5">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="section-step-badge">1</span>
                        <h6 class="section-title">HARDWARE IDENTIFICATION &amp; OPERATIONAL STATUS</h6>
                    </div>

                    <div class="row g-2.5">
                        <!-- Machine / Dispenser Name -->
                        <div class="col-12 col-md-3">
                            <label class="field-label">
                                <span>Dispenser / Machine Name <span class="text-danger">*</span></span>
                                <span class="field-helper-right">e.g. Dispenser 01</span>
                            </label>
                            <input type="text" name="name" id="machine_name" class="form-control compact-input @error('name') is-invalid @enderror"
                                   placeholder="e.g. Dispenser 01 or Pump Machine 1"
                                   list="existingDispenserList"
                                   value="{{ old('name', $prefilledDispenser ?? '') }}" required autofocus
                                   oninput="handleMachineNameChange()">
                            <datalist id="existingDispenserList">
                                @foreach($existingDispenserUnits as $du)
                                    <option value="{{ $du }}"></option>
                                @endforeach
                            </datalist>
                            <div class="field-helper-bottom">All configured nozzles below will be grouped under this machine.</div>
                            @error('name')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Station Site / Location -->
                        <div class="col-12 col-md-3">
                            <label class="field-label">
                                <span>Station Site / Location <span class="text-danger">*</span></span>
                            </label>
                            <select name="location_id" id="location_id" class="form-select compact-input @error('location_id') is-invalid @enderror">
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ (old('location_id') == $loc->id || ($loc->is_default && !old('location_id'))) ? 'selected' : '' }}>
                                        {{ $loc->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="field-helper-bottom">Physical site or station island location.</div>
                            @error('location_id')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Machine Code Prefix -->
                        <div class="col-12 col-md-2">
                            <label class="field-label">
                                <span>Machine Code</span>
                                <span class="field-helper-right">Optional</span>
                            </label>
                            <input type="text" name="code" id="machine_code" class="form-control compact-input text-uppercase @error('code') is-invalid @enderror"
                                   placeholder="e.g. D1 or PUMP-01"
                                   value="{{ old('code') }}"
                                   oninput="handleMachineCodeChange()">
                            <div class="field-helper-bottom">Prefix for nozzle codes (e.g. D1-N1).</div>
                            @error('code')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Dispenser Meter Type -->
                        <div class="col-12 col-md-2">
                            <label class="field-label">
                                <span>Dispenser Meter Type <span class="text-danger">*</span></span>
                            </label>
                            <select name="meter_type" id="meter_type" class="form-select compact-input @error('meter_type') is-invalid @enderror" required>
                                <option value="electronic" {{ old('meter_type', 'electronic') === 'electronic' ? 'selected' : '' }}>Electronic / Digital</option>
                                <option value="mechanical" {{ old('meter_type') === 'mechanical' ? 'selected' : '' }}>Mechanical Roller</option>
                            </select>
                            <div class="field-helper-bottom">Counter display mechanism.</div>
                            @error('meter_type')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Initial Status -->
                        <div class="col-12 col-md-2">
                            <label class="field-label">
                                <span>Initial Status <span class="text-danger">*</span></span>
                                <span class="field-helper-right">Shift Availability</span>
                            </label>
                            <select name="status" class="form-select compact-input @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Available)</option>
                                <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive / Off</option>
                            </select>
                            <div class="field-helper-bottom">Active machines appear in daily shifts.</div>
                            @error('status')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: CONFIGURED NOZZLES & BASELINE METERS -->
                <div class="mb-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2 pb-1 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="section-step-badge">2</span>
                            <h6 class="section-title">CONFIGURED NOZZLES &amp; METER BASELINES</h6>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5" id="nozzlesCountBadge" style="font-size: 0.72rem; font-weight: 700;">
                                1 Nozzle Configured
                            </span>
                        </div>
                        <div>
                            <!-- ADD NOZZLE BUTTON -->
                            <button type="button" class="btn btn-add-nozzle-main shadow-sm" id="btnAddNozzle" onclick="addNozzleRow()">
                                <i class="bi bi-plus-circle-fill"></i> Add Nozzle
                            </button>
                        </div>
                    </div>

                    <p class="text-secondary mb-2.5" style="font-size: 0.73rem;">
                        Add one or more fuel nozzles connected to this dispenser machine. Each nozzle tracks its own fuel product, underground tank, opening baseline meter, and live reading.
                    </p>

                    <!-- DYNAMIC NOZZLES CONTAINER -->
                    <div id="nozzlesContainer" class="d-flex flex-column gap-2.5">
                        <!-- Populated dynamically via JavaScript or Blade old() input -->
                    </div>
                </div>

                <!-- SECTION 3: MACHINE SPECIFICATIONS & HARDWARE NOTES -->
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="section-step-badge">3</span>
                        <h6 class="section-title">HARDWARE SPECIFICATIONS &amp; NOTES</h6>
                    </div>
                    <label class="field-label">
                        <span>Dispenser Hardware Specifications &amp; Notes</span>
                        <span class="field-helper-right">Optional</span>
                    </label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2"
                              placeholder="Dispenser serial number, manufacturer (Tokheim, Gilbarco, Tatsuno), calibration seal tags, model, notes, etc."
                              style="font-size: 0.82rem; border-radius: 7px; border: 1px solid #cbd5e1;">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- CARD FOOTER ACTIONS -->
                <div class="pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <a href="{{ route('pumps.index') }}" class="btn btn-secondary btn-sm px-3 rounded-2" style="font-size: 0.8rem;">
                        Cancel
                    </a>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-2 fw-semibold" onclick="addNozzleRow()" style="font-size: 0.8rem; height: 35px;">
                            <i class="bi bi-plus-lg me-1"></i> Add Another Nozzle
                        </button>
                        <button type="submit" id="btnSubmitForm" class="btn btn-primary btn-sm px-4 fw-semibold rounded-2 d-inline-flex align-items-center gap-1.5 shadow-sm" style="background-color: #2563eb; border-color: #2563eb; font-size: 0.83rem; height: 35px;">
                            <i class="bi bi-check-circle"></i> <span id="submitBtnText">Register Dispenser Machine (1 Nozzle)</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Server data passed cleanly to client
    const fuelProducts = @json($fuelProducts);
    const storageTanks = @json($tanks);
    let nozzleCounter = 0;

    // Pre-loaded old nozzles from validation errors (if any)
    const oldNozzles = @json(old('nozzles', []));

    /**
     * Build fuel product options HTML
     */
    function getProductOptionsHtml(selectedId = null) {
        let html = '<option value="">-- Select Fuel Product --</option>';
        fuelProducts.forEach(fp => {
            const isSel = selectedId && Number(selectedId) === Number(fp.id) ? 'selected' : '';
            const price = Number(fp.sale_price || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            const stock = Number(fp.current_stock || 0).toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1});
            html += `<option value="${fp.id}" data-name="${escapeHtml(fp.name)}" data-price="${fp.sale_price}" data-unit="${fp.unit_label || 'Ltr'}" data-tank="${escapeHtml(fp.tank_or_shelf || '')}" ${isSel}>` +
                    `${escapeHtml(fp.name)} (Rs. ${price}/${fp.unit_label || 'Ltr'}) - Live: ${stock}L` +
                    `</option>`;
        });
        return html;
    }

    /**
     * Build storage tank options HTML
     */
    function getTankOptionsHtml(selectedId = null) {
        let html = '<option value="">-- Storage Tank --</option>';
        storageTanks.forEach(t => {
            const isSel = selectedId && Number(selectedId) === Number(t.id) ? 'selected' : '';
            const cap = Number(t.capacity || 0).toLocaleString(undefined, {maximumFractionDigits: 0});
            const stock = Number(t.current_stock || 0).toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1});
            html += `<option value="${t.id}" data-product-id="${t.product_id || ''}" data-name="${escapeHtml(t.name)}" data-stock="${t.current_stock}" ${isSel}>` +
                    `[${escapeHtml(t.tank_number || 'Tank')}] ${escapeHtml(t.name)} (${stock}L / ${cap}L)` +
                    `</option>`;
        });
        return html;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    /**
     * Add a new Nozzle Row (Default completely empty)
     */
    function addNozzleRow(data = {}) {
        nozzleCounter++;
        const index = nozzleCounter;

        const container = document.getElementById('nozzlesContainer');
        const card = document.createElement('div');
        card.className = 'nozzle-card-item shadow-xs position-relative';
        card.id = `nozzle_card_${index}`;
        card.setAttribute('data-index', index);

        const machineCode = document.getElementById('machine_code').value.trim().toUpperCase() || '';
        const defaultNozzleNum = getNextNozzleNumber();
        const nozzleNumber = data.nozzle_number !== undefined ? data.nozzle_number : defaultNozzleNum;

        // Nozzle inputs are EMPTY by default
        const nozzleNameVal = data.name !== undefined ? data.name : '';
        const selectedProdId = data.product_id !== undefined ? data.product_id : '';
        const selectedTankId = data.tank_id !== undefined ? data.tank_id : '';
        const tankNo = data.tank_no !== undefined ? data.tank_no : '';
        const initialReading = data.initial_reading !== undefined ? data.initial_reading : '';
        const lastReading = data.last_reading !== undefined ? data.last_reading : '';

        let defaultCode = data.code || '';
        if (!defaultCode && machineCode) {
            defaultCode = `${machineCode}-N${nozzleNumber}`;
        }

        const previewTitleText = nozzleNameVal ? escapeHtml(nozzleNameVal) : `Nozzle #${nozzleNumber}`;

        card.innerHTML = `
            <!-- Top bar of Nozzle Card -->
            <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="nozzle-badge-pill">
                        <i class="bi bi-droplet-fill"></i> Nozzle #<span class="badge-num">${nozzleNumber}</span>
                    </span>
                    <span class="fw-bold text-dark nozzle-preview-title" id="nozzle_title_preview_${index}" style="font-size: 0.83rem;">
                        ${previewTitleText}
                    </span>
                    <span class="badge bg-light text-secondary border px-2 py-0.5 nozzle-product-badge" id="nozzle_product_badge_${index}" style="font-size: 0.68rem;">
                        Select Product
                    </span>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-danger btn-sm py-0.5 px-2 rounded-2 btn-remove-nozzle" onclick="removeNozzleRow(${index})" title="Remove Nozzle" style="font-size: 0.72rem;">
                        <i class="bi bi-trash3 me-1"></i>Remove Nozzle
                    </button>
                </div>
            </div>

            <!-- Inputs Grid (Single Row on Desktop) -->
            <div class="row g-2.5 align-items-start">
                <!-- 1. Nozzle Identifier / Name (Empty by default) -->
                <div class="col-12 col-md-3">
                    <label class="field-label">
                        <span>Nozzle Name / Label</span>
                        <span class="field-helper-right">Optional</span>
                    </label>
                    <input type="text" name="nozzles[${index}][name]" id="nozzle_name_${index}"
                           class="form-control compact-input fw-semibold nozzle-name-input"
                           placeholder="e.g. Nozzle ${nozzleNumber} (Super Petrol)"
                           value="${escapeHtml(nozzleNameVal)}"
                           oninput="handleNozzleNameInput(${index})">
                    <input type="hidden" name="nozzles[${index}][nozzle_number]" id="nozzle_number_${index}" class="nozzle-num-hidden" value="${nozzleNumber}">
                    <div class="field-helper-bottom">Leave empty for automatic naming.</div>
                </div>

                <!-- 2. Dispensed Fuel Product (Empty by default) -->
                <div class="col-12 col-md-3">
                    <label class="field-label">
                        <span>Fuel Product Dispensed <span class="text-danger">*</span></span>
                    </label>
                    <select name="nozzles[${index}][product_id]" id="nozzle_product_${index}"
                            class="form-select compact-input nozzle-product-select" required
                            onchange="handleProductSelect(${index}, true)">
                        ${getProductOptionsHtml(selectedProdId)}
                    </select>
                    <div class="field-helper-bottom">Select product for this nozzle.</div>
                </div>

                <!-- 3. Connected Storage Tank (Empty by default) -->
                <div class="col-12 col-md-2">
                    <label class="field-label">
                        <span>Storage Tank</span>
                        <span class="field-helper-right"><a href="{{ route('tanks.create') }}" target="_blank" class="text-decoration-none">+ Tank</a></span>
                    </label>
                    <select name="nozzles[${index}][tank_id]" id="nozzle_tank_${index}"
                            class="form-select compact-input nozzle-tank-select"
                            onchange="handleTankSelect(${index})">
                        ${getTankOptionsHtml(selectedTankId)}
                    </select>
                    <input type="hidden" name="nozzles[${index}][tank_no]" id="nozzle_tank_no_${index}" value="${escapeHtml(tankNo)}">
                    <div class="field-helper-bottom">Dedicated fuel tank.</div>
                </div>

                <!-- 4. Initial / Opening Reading -->
                <div class="col-12 col-md-2">
                    <div class="reading-highlight-box">
                        <label class="field-label text-primary p-0 m-0 mb-1">
                            <span class="fw-bold"><i class="bi bi-speedometer2 me-1"></i>Initial Reading <span class="text-danger">*</span></span>
                            <span class="badge bg-primary text-white" style="font-size: 0.58rem;">Baseline</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text compact-addon compact-addon-prefix bg-white border-end-0 text-primary fw-semibold p-1" style="height: 30px !important;"><i class="bi bi-play-fill"></i></span>
                            <input type="number" step="0.01" min="0" name="nozzles[${index}][initial_reading]" id="nozzle_initial_${index}"
                                   class="form-control compact-input fw-bold border-start-0 text-primary nozzle-initial-input"
                                   placeholder="0.00" value="${initialReading}" required
                                   style="height: 30px !important; font-size: 0.8rem !important;"
                                   oninput="handleInitialReadingInput(${index})">
                            <span class="input-group-text compact-addon compact-addon-suffix bg-white text-muted px-1.5" style="height: 30px !important; font-size: 0.68rem;">Ltr</span>
                        </div>
                    </div>
                </div>

                <!-- 5. Current / Live Reading -->
                <div class="col-12 col-md-2">
                    <div class="reading-live-box">
                        <label class="field-label text-dark p-0 m-0 mb-1">
                            <span class="fw-bold"><i class="bi bi-clock-history me-1"></i>Live Reading</span>
                            <span class="badge bg-secondary text-white" style="font-size: 0.58rem;">Live</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text compact-addon compact-addon-prefix bg-white border-end-0 text-muted p-1" style="height: 30px !important;"><i class="bi bi-stop-fill text-danger"></i></span>
                            <input type="number" step="0.01" min="0" name="nozzles[${index}][last_reading]" id="nozzle_last_${index}"
                                   class="form-control compact-input fw-bold border-start-0 text-dark nozzle-last-input"
                                   placeholder="0.00" value="${lastReading}"
                                   style="height: 30px !important; font-size: 0.8rem !important;">
                            <span class="input-group-text compact-addon compact-addon-suffix bg-white text-muted px-1.5" style="height: 30px !important; font-size: 0.68rem;">Ltr</span>
                        </div>
                    </div>
                </div>

                <!-- Optional Nozzle Code -->
                <input type="hidden" name="nozzles[${index}][code]" id="nozzle_code_${index}" value="${escapeHtml(defaultCode)}">
            </div>
        `;

        container.appendChild(card);

        // If data.product_id was already specified (e.g. from validation restore)
        if (selectedProdId) {
            handleProductSelect(index, false);
        }

        // Update overall UI badges and counters
        refreshNozzleIndices();
    }

    /**
     * Remove Nozzle Row
     */
    function removeNozzleRow(index) {
        const cards = document.querySelectorAll('.nozzle-card-item');
        if (cards.length <= 1) {
            alert('A pump machine must have at least one nozzle.');
            return;
        }

        const card = document.getElementById(`nozzle_card_${index}`);
        if (card) {
            card.remove();
            refreshNozzleIndices();
        }
    }

    /**
     * Get next nozzle number based on current count
     */
    function getNextNozzleNumber() {
        const cards = document.querySelectorAll('.nozzle-card-item');
        return cards.length + 1;
    }

    /**
     * Re-index remaining nozzles sequentially and update badges
     */
    function refreshNozzleIndices() {
        const cards = document.querySelectorAll('.nozzle-card-item');
        const count = cards.length;

        cards.forEach((card, i) => {
            const num = i + 1;
            const badgeNum = card.querySelector('.badge-num');
            if (badgeNum) badgeNum.textContent = num;

            const hiddenNum = card.querySelector('.nozzle-num-hidden');
            if (hiddenNum) hiddenNum.value = num;

            // Remove button disabled/hidden if only 1 nozzle remains
            const btnRemove = card.querySelector('.btn-remove-nozzle');
            if (btnRemove) {
                if (count <= 1) {
                    btnRemove.style.display = 'none';
                } else {
                    btnRemove.style.display = 'inline-flex';
                }
            }
        });

        // Update Nozzle Count Badge
        const countBadge = document.getElementById('nozzlesCountBadge');
        if (countBadge) {
            countBadge.textContent = count === 1 ? '1 Nozzle Configured' : `${count} Nozzles Configured`;
        }

        // Update Submit Button Text
        const submitText = document.getElementById('submitBtnText');
        if (submitText) {
            submitText.textContent = count === 1 ? 'Register Dispenser Machine (1 Nozzle)' : `Register Dispenser Machine (${count} Nozzles)`;
        }
    }

    /**
     * Handle Product Select Change
     */
    function handleProductSelect(index, isUserInteraction = true) {
        const prodSelect = document.getElementById(`nozzle_product_${index}`);
        const tankSelect = document.getElementById(`nozzle_tank_${index}`);
        const previewTitle = document.getElementById(`nozzle_title_preview_${index}`);
        const productBadge = document.getElementById(`nozzle_product_badge_${index}`);
        const nameInput = document.getElementById(`nozzle_name_${index}`);
        const card = document.getElementById(`nozzle_card_${index}`);
        const num = card ? (card.querySelector('.badge-num')?.textContent || '1') : '1';

        if (!prodSelect) return;

        const opt = prodSelect.options[prodSelect.selectedIndex];
        if (!opt || !opt.value) {
            if (productBadge) productBadge.textContent = 'Select Product';
            if (previewTitle) {
                previewTitle.textContent = (nameInput && nameInput.value.trim()) ? nameInput.value.trim() : `Nozzle #${num}`;
            }
            return;
        }

        const prodName = opt.getAttribute('data-name');
        const prodPrice = opt.getAttribute('data-price');
        const prodUnit = opt.getAttribute('data-unit') || 'Ltr';
        const prodTank = opt.getAttribute('data-tank');

        if (productBadge) {
            productBadge.textContent = `${prodName} • Rs. ${Number(prodPrice).toFixed(2)}/${prodUnit}`;
        }

        // Auto-select storage tank if not manually selected
        if (tankSelect && (isUserInteraction || !tankSelect.value)) {
            let matched = false;
            // 1. Try match by product_id on tank
            for (let i = 0; i < tankSelect.options.length; i++) {
                if (tankSelect.options[i].getAttribute('data-product-id') == opt.value) {
                    tankSelect.selectedIndex = i;
                    matched = true;
                    break;
                }
            }
            // 2. Try match by tank name string
            if (!matched && prodTank) {
                for (let i = 0; i < tankSelect.options.length; i++) {
                    if (tankSelect.options[i].getAttribute('data-name') == prodTank) {
                        tankSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
            }
            handleTankSelect(index);
        }

        // Keep nozzle name input empty by default; update header preview only
        if (previewTitle) {
            if (nameInput && nameInput.value.trim()) {
                previewTitle.textContent = nameInput.value.trim();
            } else {
                previewTitle.textContent = `Nozzle #${num} (${prodName})`;
            }
        }
    }

    /**
     * Handle Tank Select Change
     */
    function handleTankSelect(index) {
        const tankSelect = document.getElementById(`nozzle_tank_${index}`);
        const tankNoHidden = document.getElementById(`nozzle_tank_no_${index}`);
        if (!tankSelect || !tankNoHidden) return;

        const opt = tankSelect.options[tankSelect.selectedIndex];
        if (opt && opt.value) {
            tankNoHidden.value = opt.getAttribute('data-name') || opt.text;
        } else {
            tankNoHidden.value = '';
        }
    }

    /**
     * Handle Nozzle Name User Input
     */
    function handleNozzleNameInput(index) {
        const nameInput = document.getElementById(`nozzle_name_${index}`);
        const previewTitle = document.getElementById(`nozzle_title_preview_${index}`);
        if (nameInput && previewTitle) {
            previewTitle.textContent = nameInput.value || `Nozzle #${index}`;
        }
    }

    /**
     * Auto sync Live Reading when Initial Reading is entered
     */
    function handleInitialReadingInput(index) {
        const initialInput = document.getElementById(`nozzle_initial_${index}`);
        const lastInput = document.getElementById(`nozzle_last_${index}`);
        if (initialInput && lastInput) {
            // Keep last reading in sync with initial reading during creation
            lastInput.value = initialInput.value;
        }
    }

    /**
     * Handle Machine Name Change
     */
    function handleMachineNameChange() {
        // Keep nozzle name inputs empty by default; do not auto-overwrite user inputs
    }

    /**
     * Handle Machine Code Change
     */
    function handleMachineCodeChange() {
        const machineCode = document.getElementById('machine_code').value.trim().toUpperCase();
        const cards = document.querySelectorAll('.nozzle-card-item');

        cards.forEach((card) => {
            const idx = card.getAttribute('data-index');
            const num = card.querySelector('.badge-num')?.textContent || '1';
            const codeInput = document.getElementById(`nozzle_code_${idx}`);
            if (codeInput) {
                codeInput.value = machineCode ? `${machineCode}-N${num}` : '';
            }
        });
    }

    // INITIALIZATION ON PAGE LOAD
    document.addEventListener('DOMContentLoaded', function () {
        if (Array.isArray(oldNozzles) && oldNozzles.length > 0) {
            // Restore from Laravel old() validation errors
            oldNozzles.forEach(noz => addNozzleRow(noz));
        } else {
            // Add initial Nozzle 1 by default
            addNozzleRow();
        }
    });
</script>
@endpush
