@extends('layouts.app')

@section('title', 'Edit Storage Tank - ' . $tank->name)

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" style="max-width: 900px;">

    <!-- Breadcrumb & Title -->
    <div class="mb-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tanks.index') }}" class="text-decoration-none">Storage Tanks</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Tank</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-primary"></i>
                <span>Edit Storage Tank Specifications</span>
            </h4>
        </div>
        <a href="{{ route('tanks.index') }}" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Tanks
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 small mb-3">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3 bg-white p-3.5">
        <form action="{{ route('tanks.update', $tank->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Section 1: Location & Product Selection -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom">
                    <span class="badge bg-primary rounded-pill px-2 py-0.5">1</span>
                    <h6 class="fw-bold mb-0 text-dark">STATION LOCATION &amp; FUEL ITEM</h6>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">
                            Station Site / Location <span class="text-danger">*</span>
                        </label>
                        <select name="location_id" class="form-select form-select-sm py-2 @error('location_id') is-invalid @enderror" required>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('location_id', $tank->location_id) == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }} ({{ $loc->city ?: 'Main' }})
                                </option>
                            @endforeach
                        </select>
                        @error('location_id') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">
                            Stored Fuel Product <span class="text-danger">*</span>
                        </label>
                        <select name="product_id" class="form-select form-select-sm py-2 @error('product_id') is-invalid @enderror" required>
                            @foreach($fuelProducts as $fp)
                                <option value="{{ $fp->id }}" {{ old('product_id', $tank->product_id) == $fp->id ? 'selected' : '' }}>
                                    {{ $fp->name }} (Live Stock: {{ number_format($fp->current_stock, 1) }} {{ $fp->unit_label }})
                                </option>
                            @endforeach
                        </select>
                        @error('product_id') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Tank Identification & Dimensions -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom">
                    <span class="badge bg-primary rounded-pill px-2 py-0.5">2</span>
                    <h6 class="fw-bold mb-0 text-dark">IDENTIFICATION &amp; TYPE</h6>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">
                            Tank Number / Code <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="tank_number" class="form-control form-control-sm py-2 text-uppercase font-monospace @error('tank_number') is-invalid @enderror"
                               value="{{ old('tank_number', $tank->tank_number) }}" required>
                        @error('tank_number') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-5">
                        <label class="form-label small fw-semibold text-secondary">
                            Display Name / Label <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" class="form-control form-control-sm py-2 @error('name') is-invalid @enderror"
                               value="{{ old('name', $tank->name) }}" required>
                        @error('name') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-secondary">
                            Installation Type <span class="text-danger">*</span>
                        </label>
                        <select name="tank_type" class="form-select form-select-sm py-2" required>
                            <option value="underground" {{ old('tank_type', $tank->tank_type) === 'underground' ? 'selected' : '' }}>Underground (UGT)</option>
                            <option value="aboveground" {{ old('tank_type', $tank->tank_type) === 'aboveground' ? 'selected' : '' }}>Aboveground (AGT)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3: Capacity, Stock & Dip Calibration -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom">
                    <span class="badge bg-primary rounded-pill px-2 py-0.5">3</span>
                    <h6 class="fw-bold mb-0 text-dark">CAPACITY &amp; DIP CALIBRATION</h6>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">
                            Storage Capacity (Liters) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="1" name="capacity" class="form-control font-monospace py-2 @error('capacity') is-invalid @enderror"
                                   value="{{ old('capacity', $tank->capacity) }}" required>
                            <span class="input-group-text">Liters</span>
                        </div>
                        @error('capacity') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">
                            Dead Stock Buffer (Liters) <span class="text-muted">(Optional)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" name="dead_stock" class="form-control font-monospace py-2"
                                   value="{{ old('dead_stock', $tank->dead_stock) }}">
                            <span class="input-group-text">Liters</span>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">
                            Operational Status <span class="text-danger">*</span>
                        </label>
                        <select name="status" class="form-select form-select-sm py-2" required>
                            <option value="active" {{ old('status', $tank->status) === 'active' ? 'selected' : '' }}>Active (Ready for dispensing)</option>
                            <option value="maintenance" {{ old('status', $tank->status) === 'maintenance' ? 'selected' : '' }}>Maintenance / Cleaning</option>
                            <option value="inactive" {{ old('status', $tank->status) === 'inactive' ? 'selected' : '' }}>Inactive / Decommissioned</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">
                            Current Dip Stick Height <span class="text-muted">(Optional)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" name="current_dip_reading" class="form-control font-monospace py-2"
                                   value="{{ old('current_dip_reading', $tank->current_dip_reading) }}">
                            <span class="input-group-text">mm</span>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">
                            Max Calibration Height <span class="text-muted">(Optional)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" name="max_dip_reading" class="form-control font-monospace py-2"
                                   value="{{ old('max_dip_reading', $tank->max_dip_reading) }}">
                            <span class="input-group-text">mm</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Per-Tank Variance Tolerance Overrides -->
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2 pb-1 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill px-2 py-0.5">4</span>
                        <h6 class="fw-bold mb-0 text-dark">DIP VARIANCE TOLERANCE RULES</h6>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input type="hidden" name="use_default_tolerance" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="use_default_tolerance"
                               name="use_default_tolerance" value="1"
                               {{ old('use_default_tolerance', $tank->use_default_tolerance) ? 'checked' : '' }} onchange="toggleCustomTolerance()">
                        <label class="form-check-label small fw-semibold text-primary" for="use_default_tolerance">
                            Use Station Default (&plusmn;{{ $tankConfig['warning_liters'] }}L / {{ $tankConfig['warning_percent'] }}%)
                        </label>
                    </div>
                </div>

                <div id="customToleranceBox" class="row g-3 p-2.5 bg-light rounded-3 border {{ old('use_default_tolerance', $tank->use_default_tolerance) ? 'd-none' : '' }}">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Tolerance Mode</label>
                        <select name="tolerance_mode" class="form-select form-select-sm py-2">
                            @foreach($toleranceModes as $mKey => $mLabel)
                                <option value="{{ $mKey }}" {{ old('tolerance_mode', $tank->tolerance_mode ?: $tankConfig['tolerance_mode']) === $mKey ? 'selected' : '' }}>
                                    {{ $mLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-secondary">Warn (Ltr)</label>
                        <input type="number" step="0.01" min="0" name="warning_tolerance_liters" class="form-control form-control-sm font-monospace py-2"
                               value="{{ old('warning_tolerance_liters', $tank->warning_tolerance_liters ?? $tankConfig['warning_liters']) }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-secondary">Critical (Ltr)</label>
                        <input type="number" step="0.01" min="0" name="critical_tolerance_liters" class="form-control form-control-sm font-monospace py-2"
                               value="{{ old('critical_tolerance_liters', $tank->critical_tolerance_liters ?? $tankConfig['critical_liters']) }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-secondary">Warn (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="warning_tolerance_percent" class="form-control form-control-sm font-monospace py-2"
                               value="{{ old('warning_tolerance_percent', $tank->warning_tolerance_percent ?? $tankConfig['warning_percent']) }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-semibold text-secondary">Critical (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="critical_tolerance_percent" class="form-control form-control-sm font-monospace py-2"
                               value="{{ old('critical_tolerance_percent', $tank->critical_tolerance_percent ?? $tankConfig['critical_percent']) }}">
                    </div>
                </div>
            </div>

            <!-- Section 5: Notes -->
            <div class="mb-4">
                <label class="form-label small fw-semibold text-secondary">
                    Description &amp; Maintenance Notes <span class="text-muted">(Optional)</span>
                </label>
                <textarea name="description" class="form-control form-control-sm" rows="2">{{ old('description', $tank->description) }}</textarea>
            </div>

            <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('tanks.index') }}" class="btn btn-secondary btn-sm px-3">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                    <i class="bi bi-check-circle-fill me-1"></i> Update Storage Tank
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function toggleCustomTolerance() {
    const chk = document.getElementById('use_default_tolerance');
    const box = document.getElementById('customToleranceBox');
    if (chk && box) {
        box.classList.toggle('d-none', chk.checked);
    }
}
</script>
@endsection
