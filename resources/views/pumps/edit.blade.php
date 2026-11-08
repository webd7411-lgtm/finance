@extends('layouts.app')

@section('title', 'Edit Pump Machine')
@section('page_title', 'Edit Pump Machine: ' . $pump->name)

@section('page_badge')
    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem; font-weight: 600;">
        <i class="bi bi-pencil-square me-1"></i> Edit Specifications
    </span>
@endsection

@section('page_actions')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('pumps.show', $pump->id) }}" class="btn btn-outline-secondary btn-sm px-3 py-1.5 rounded-3 fw-semibold shadow-xs" style="font-size: 0.81rem;">
            <i class="bi bi-arrow-left me-1"></i> View Machine
        </a>
        <a href="{{ route('pumps.index') }}" class="btn btn-outline-dark btn-sm px-3 py-1.5 rounded-3 fw-semibold shadow-xs" style="font-size: 0.81rem;">
            <i class="bi bi-list me-1"></i> All Machines
        </a>
    </div>
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
        padding: 1.1rem 1.4rem;
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
        width: 20px;
        height: 20px;
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
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Main Pump Entry Form Card (Full Page Space Occupied) -->
        <div class="pump-form-card">
            
            <!-- Card Header -->
            <div class="d-flex align-items-center justify-content-between pb-2.5 mb-3 border-bottom flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="text-white rounded-3 d-flex align-items-center justify-content-center shadow-xs" style="width: 36px; height: 36px; background-color: #1e40af !important;">
                        <i class="bi bi-sliders fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark m-0" style="font-size: 0.95rem;">Edit Machine Hardware &amp; Settings</h6>
                        <small class="text-secondary" style="font-size: 0.73rem;">Update name, code, nozzle settings, and assigned fuel product.</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-secondary border px-2.5 py-1" style="font-size: 0.7rem; font-weight: 500;">
                        <i class="bi bi-tag me-1 text-primary"></i>ID: #{{ $pump->id }}
                    </span>
                </div>
            </div>

            <form action="{{ route('pumps.update', $pump->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- SECTION 1: HARDWARE IDENTIFICATION & STATUS -->
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="section-step-badge">1</span>
                        <h6 class="section-title">HARDWARE IDENTIFICATION &amp; OPERATIONAL STATUS</h6>
                    </div>

                    <div class="row g-2.5">
                        <!-- Machine Name -->
                        <div class="col-12 col-md-4">
                            <label class="field-label">
                                <span>Pump Machine / Dispenser Name <span class="text-danger">*</span></span>
                                <span class="field-helper-right">Required</span>
                            </label>
                            <input type="text" name="name" class="form-control compact-input @error('name') is-invalid @enderror"
                                   value="{{ old('name', $pump->name) }}" required>
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
                                    <option value="{{ $loc->id }}" {{ old('location_id', $pump->location_id) == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('location_id')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Machine Code -->
                        <div class="col-12 col-md-2">
                            <label class="field-label">
                                <span>Machine Code</span>
                                <span class="field-helper-right">Optional</span>
                            </label>
                            <input type="text" name="code" class="form-control compact-input text-uppercase @error('code') is-invalid @enderror"
                                   value="{{ old('code', $pump->code) }}">
                            @error('code')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Operational Status -->
                        <div class="col-12 col-md-3">
                            <label class="field-label">
                                <span>Operational Status <span class="text-danger">*</span></span>
                                <span class="field-helper-right">Shift Availability</span>
                            </label>
                            <select name="status" class="form-select compact-input @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $pump->status) === 'active' ? 'selected' : '' }}>Active (Available for Shifts)</option>
                                <option value="maintenance" {{ old('status', $pump->status) === 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                                <option value="inactive" {{ old('status', $pump->status) === 'inactive' ? 'selected' : '' }}>Inactive / Decommissioned</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: FUEL PRODUCT & TANK CONFIGURATION -->
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="section-step-badge">2</span>
                        <h6 class="section-title">FUEL PRODUCT &amp; TANK CONFIGURATION</h6>
                    </div>

                    <div class="row g-2.5">
                        <!-- Connected Storage Tank -->
                        <div class="col-12 col-md-5">
                            <label class="field-label">
                                <span>Connected Storage Tank</span>
                                <span class="field-helper-right"><a href="{{ route('tanks.create') }}" target="_blank" class="text-decoration-none">+ New Tank</a></span>
                            </label>
                            <select name="tank_id" id="tank_id" class="form-select compact-input @error('tank_id') is-invalid @enderror" onchange="handleTankSelect(this)">
                                <option value="">-- Select Dedicated Storage Tank --</option>
                                @foreach($tanks as $t)
                                    <option value="{{ $t->id }}"
                                            data-product-id="{{ $t->product_id }}"
                                            data-location-id="{{ $t->location_id }}"
                                            data-name="{{ $t->name }}"
                                            {{ old('tank_id', $pump->tank_id) == $t->id ? 'selected' : '' }}>
                                        [{{ $t->tank_number }}] {{ $t->name }} (Cap: {{ number_format($t->capacity) }}L • Live: {{ number_format($t->current_stock, 1) }}L)
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="tank_no" id="tank_no" value="{{ old('tank_no', $pump->tank_no) }}">
                            @error('tank_id')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Dispensed Fuel Product -->
                        <div class="col-12 col-md-4">
                            <label class="field-label">
                                <span>Dispensed Fuel Product <span class="text-danger">*</span></span>
                            </label>
                            <select name="product_id" id="product_id" class="form-select compact-input @error('product_id') is-invalid @enderror" required>
                                <option value="">-- Select Fuel Product --</option>
                                @foreach($fuelProducts as $fp)
                                    <option value="{{ $fp->id }}" {{ old('product_id', $pump->product_id) == $fp->id ? 'selected' : '' }}>
                                        {{ $fp->name }} (Rs. {{ number_format($fp->sale_price, 2) }} / {{ $fp->unit_label }}) - Stock: {{ number_format($fp->current_stock, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Nozzle Configuration / Identifier -->
                        <div class="col-12 col-md-3">
                            <label class="field-label">
                                <span>Nozzle Configuration / Identifier</span>
                                <span class="field-helper-right">Optional</span>
                            </label>
                            <input type="text" name="nozzle_number" class="form-control compact-input @error('nozzle_number') is-invalid @enderror"
                                   value="{{ old('nozzle_number', $pump->nozzle_number) }}">
                            @error('nozzle_number')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: METER TYPE & LIVE READING CALIBRATION -->
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="section-step-badge">3</span>
                        <h6 class="section-title">METER TYPE &amp; LIVE READING CALIBRATION</h6>
                    </div>

                    <div class="row g-2.5">
                        <!-- Meter Type -->
                        <div class="col-12 col-md-4">
                            <label class="field-label">
                                <span>Dispenser Meter Type <span class="text-danger">*</span></span>
                            </label>
                            <select name="meter_type" class="form-select compact-input @error('meter_type') is-invalid @enderror" required>
                                <option value="electronic" {{ old('meter_type', $pump->meter_type) === 'electronic' ? 'selected' : '' }}>Electronic / Digital Totalizer</option>
                                <option value="mechanical" {{ old('meter_type', $pump->meter_type) === 'mechanical' ? 'selected' : '' }}>Mechanical Roller Counter</option>
                            </select>
                            <div class="field-helper-bottom">Digital counter or mechanical roller numbers</div>
                            @error('meter_type')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Current Meter Reading Info Box -->
                        <div class="col-12 col-md-8">
                            <div class="alert alert-secondary py-2 px-3 d-flex align-items-center justify-content-between mb-0 rounded-2" style="font-size: 0.8rem; min-height: 52px;">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <i class="bi bi-info-circle-fill text-primary fs-5"></i>
                                    <div>
                                        <strong>Current Live Meter Reading:</strong>
                                        <span class="font-monospace fw-bold text-primary fs-6 ms-1">{{ number_format($pump->last_reading, 2) }} Ltr</span>
                                        <span class="text-muted ms-2">(Baseline: {{ number_format($pump->initial_reading, 2) }})</span>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-warning btn-sm py-1 px-2.5 rounded-2 fw-semibold shadow-xs" data-bs-toggle="modal" data-bs-target="#adjustModal">
                                        <i class="bi bi-tools me-1 text-warning"></i>Calibrate Reading
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: HARDWARE SPECIFICATIONS & NOTES -->
                <div class="mb-3">
                    <label class="field-label">
                        <span>Machine Specifications &amp; Hardware Notes</span>
                        <span class="field-helper-right">Optional</span>
                    </label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2"
                              placeholder="Hardware details, dispenser serial number, calibration seal notes, model number, etc."
                              style="font-size: 0.82rem; border-radius: 7px; border: 1px solid #cbd5e1;">{{ old('description', $pump->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- CARD FOOTER ACTIONS -->
                <div class="pt-2.5 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <a href="{{ route('pumps.show', $pump->id) }}" class="btn btn-secondary btn-sm px-3 rounded-2" style="font-size: 0.8rem;">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold rounded-2 d-inline-flex align-items-center gap-1.5 shadow-sm" style="background-color: #2563eb; border-color: #2563eb; font-size: 0.82rem; height: 35px;">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Meter Reading Calibration / Adjustment -->
<div class="modal fade" id="adjustModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('pumps.adjust-reading', $pump->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-dark text-white py-2 px-3">
                    <h6 class="modal-title fw-bold mb-0" style="font-size: 0.92rem;">
                        <i class="bi bi-tools text-warning me-1.5"></i> Calibrate / Adjust Meter: {{ $pump->name }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="alert alert-info py-2 px-3 small mb-3" style="font-size: 0.78rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Current Live Reading:</strong> {{ number_format($pump->last_reading, 2) }} Liters.
                        Use this to record technician calibration, meter rollover, or repairs with full audit trail.
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-dark mb-1">New Meter Reading <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="new_reading" class="form-control compact-input fw-bold" value="{{ old('new_reading', $pump->last_reading) }}" required>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-dark mb-1">Reason / Adjustment Type <span class="text-danger">*</span></label>
                        <select name="adjustment_type" class="form-select compact-input" required>
                            <option value="calibration">Routine Inspection / Volumetric Calibration Test</option>
                            <option value="manual_adjustment">Meter Reset / Counter Rollover / Mechanical Repair</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark mb-1">Test Volume (Ltr)</label>
                            <input type="number" step="0.01" min="0" name="volume_tested" class="form-control compact-input" placeholder="e.g. 10.00" value="0">
                        </div>
                        <div class="col-6 d-flex align-items-end pb-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="returned_to_tank" value="1" id="returnCheck" checked>
                                <label class="form-check-label small" for="returnCheck">
                                    Poured back into Tank
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">Technician / Calibration Notes <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Tested nozzle with 10L calibration measure bucket; seal verified." style="font-size: 0.82rem; border-radius: 7px; border: 1px solid #cbd5e1;" required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold rounded-2" style="background-color: #2563eb; border-color: #2563eb;">Save Calibration Log</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function handleTankSelect(select) {
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return;

        const prodId = opt.getAttribute('data-product-id');
        const locId = opt.getAttribute('data-location-id');
        const tankName = opt.getAttribute('data-name');

        if (prodId) {
            const prodSelect = document.getElementById('product_id');
            if (prodSelect) {
                prodSelect.value = prodId;
            }
        }

        if (locId) {
            const locSelect = document.getElementById('location_id');
            if (locSelect) {
                locSelect.value = locId;
            }
        }

        if (tankName) {
            document.getElementById('tank_no').value = tankName;
        }
    }
</script>
@endpush
