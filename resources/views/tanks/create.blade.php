@extends('layouts.app')

@section('title', 'Add Fuel Storage Tank - Master Setup')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" style="max-width: 900px;">

    <!-- Breadcrumb & Title -->
    <div class="mb-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tanks.index') }}" class="text-decoration-none">Storage Tanks</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add New Tank</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle-fill text-primary"></i>
                <span>Add Fuel Storage Tank</span>
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
        <form action="{{ route('tanks.store') }}" method="POST">
            @csrf

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
                            <option value="">-- Select Station Site --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ (old('location_id') == $loc->id || ($loc->is_default && !old('location_id'))) ? 'selected' : '' }}>
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
                        <select name="product_id" id="product_id" class="form-select form-select-sm py-2 @error('product_id') is-invalid @enderror" required onchange="handleProductSelect(this)">
                            <option value="">-- Select Fuel Product --</option>
                            @foreach($fuelProducts as $fp)
                                <option value="{{ $fp->id }}"
                                        data-name="{{ $fp->name }}"
                                        data-stock="{{ $fp->current_stock }}"
                                        {{ old('product_id') == $fp->id ? 'selected' : '' }}>
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
                        <input type="text" name="tank_number" id="tank_number" class="form-control form-control-sm py-2 text-uppercase font-monospace @error('tank_number') is-invalid @enderror"
                               placeholder="e.g. TK-01, TANK-1" value="{{ old('tank_number') }}" required>
                        @error('tank_number') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-5">
                        <label class="form-label small fw-semibold text-secondary">
                            Display Name / Label <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" id="tank_name" class="form-control form-control-sm py-2 @error('name') is-invalid @enderror"
                               placeholder="e.g. Underground Tank #1 (Super Petrol)" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-secondary">
                            Installation Type <span class="text-danger">*</span>
                        </label>
                        <select name="tank_type" class="form-select form-select-sm py-2" required>
                            <option value="underground" {{ old('tank_type', 'underground') === 'underground' ? 'selected' : '' }}>Underground (UGT)</option>
                            <option value="aboveground" {{ old('tank_type') === 'aboveground' ? 'selected' : '' }}>Aboveground (AGT)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3: Capacity, Stock & Dip Calibration -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom">
                    <span class="badge bg-primary rounded-pill px-2 py-0.5">3</span>
                    <h6 class="fw-bold mb-0 text-dark">CAPACITY, OPENING STOCK &amp; DIP CALIBRATION</h6>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">
                            Storage Capacity (Liters) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="1" name="capacity" class="form-control font-monospace py-2 @error('capacity') is-invalid @enderror"
                                   placeholder="e.g. 25000" value="{{ old('capacity', '25000') }}" required>
                            <span class="input-group-text">Liters</span>
                        </div>
                        @error('capacity') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">Maximum volume limit of the tank</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">
                            Opening / Current Fuel (Ltr) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" name="opening_stock" id="opening_stock" class="form-control font-monospace py-2 @error('opening_stock') is-invalid @enderror"
                                   placeholder="e.g. 15000" value="{{ old('opening_stock', '0') }}" required>
                            <span class="input-group-text">Liters</span>
                        </div>
                        @error('opening_stock') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">Initial physical fuel in tank at setup</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">
                            Dead Stock Buffer (Liters) <span class="text-muted">(Optional)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" name="dead_stock" class="form-control font-monospace py-2"
                                   placeholder="e.g. 500" value="{{ old('dead_stock', '500') }}">
                            <span class="input-group-text">Liters</span>
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">Unusable fuel level below suction pipe</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">
                            Current Dip Stick Height <span class="text-muted">(Optional)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" name="current_dip_reading" class="form-control font-monospace py-2"
                                   placeholder="e.g. 142.5" value="{{ old('current_dip_reading') }}">
                            <span class="input-group-text">cm</span>
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">Measured dip stick reading</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">
                            Max Calibration Height <span class="text-muted">(Optional)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" name="max_dip_reading" class="form-control font-monospace py-2"
                                   placeholder="e.g. 220" value="{{ old('max_dip_reading', '220') }}">
                            <span class="input-group-text">cm</span>
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">Dip stick chart height limit</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">
                            Operational Status <span class="text-danger">*</span>
                        </label>
                        <select name="status" class="form-select form-select-sm py-2" required>
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Ready for dispensing)</option>
                            <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance / Cleaning</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive / Decommissioned</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 4: Notes -->
            <div class="mb-4">
                <label class="form-label small fw-semibold text-secondary">
                    Description &amp; Maintenance Notes <span class="text-muted">(Optional)</span>
                </label>
                <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="e.g. Calibrated underground double-wall steel tank with Veeder-Root sensor port...">{{ old('description') }}</textarea>
            </div>

            <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('tanks.index') }}" class="btn btn-secondary btn-sm px-3">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                    <i class="bi bi-check-circle-fill me-1"></i> Save Storage Tank
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function handleProductSelect(select) {
    const selectedOption = select.options[select.selectedIndex];
    const prodName = selectedOption.getAttribute('data-name');
    const prodStock = selectedOption.getAttribute('data-stock');

    const nameInput = document.getElementById('tank_name');
    const openingStock = document.getElementById('opening_stock');

    if (prodName && !nameInput.value) {
        nameInput.value = 'Underground Tank (' + prodName + ')';
    }
    if (prodStock && (!openingStock.value || openingStock.value === '0')) {
        openingStock.value = prodStock;
    }
}
</script>
@endsection
