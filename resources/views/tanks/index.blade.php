@extends('layouts.app')

@section('title', 'Fuel Storage Tanks - Master Setup')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Header Breadcrumb & Actions -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item text-secondary">Master Setup</li>
                    <li class="breadcrumb-item active" aria-current="page">Fuel Storage Tanks</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-database-fill-gear text-primary"></i>
                <span>Storage Tanks &amp; Fuel Reservoirs</span>
            </h4>
            <p class="text-muted small mb-0">Underground and aboveground bulk fuel storage tanks, live capacities, dip stick calibrations, and pump dispenser assignments.</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('locations.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm d-flex align-items-center gap-1.5">
                <i class="bi bi-geo-alt"></i>
                <span>Station Locations</span>
            </a>
            <a href="{{ route('pumps.index') }}" class="btn btn-outline-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1.5">
                <i class="bi bi-speedometer2"></i>
                <span>Pump Machines</span>
            </a>
            <a href="{{ route('tanks.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1.5">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Add Storage Tank</span>
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show small py-2 px-3 mb-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show small py-2 px-3 mb-3 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- High-Level KPI Summary Cards -->
    <div class="row g-2.5 mb-3">
        <!-- Card 1: Total Tanks -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Configured Tanks</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1"><i class="bi bi-database"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalTanks }}</h3>
                    <span class="text-muted small">Tanks</span>
                </div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">Underground &amp; Aboveground</div>
            </div>
        </div>

        <!-- Card 2: Total Storage Capacity -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Total Capacity</span>
                    <span class="badge bg-info-subtle text-info rounded-pill px-2 py-1"><i class="bi bi-fuel-pump"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-1">
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalCapacity) }}</h3>
                    <span class="text-muted small">Ltr</span>
                </div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">Combined station volume limit</div>
            </div>
        </div>

        <!-- Card 3: Live Fuel Stock -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Live Fuel In Tanks</span>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1"><i class="bi bi-droplet-half"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-1">
                    <h3 class="fw-bold mb-0 text-success">{{ number_format($totalCurrentStock) }}</h3>
                    <span class="text-muted small">Ltr</span>
                </div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                    @if($totalCapacity > 0)
                        {{ round(($totalCurrentStock / $totalCapacity) * 100, 1) }}% overall station fill
                    @else
                        0% filled
                    @endif
                </div>
            </div>
        </div>

        <!-- Card 4: Available Ullage / Space -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Available Space (Ullage)</span>
                    <span class="badge bg-warning-subtle text-warning rounded-pill px-2 py-1"><i class="bi bi-box-arrow-in-down"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-1">
                    <h3 class="fw-bold mb-0 text-primary">{{ number_format($totalAvailableSpace) }}</h3>
                    <span class="text-muted small">Ltr</span>
                </div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">Capacity for incoming bowsers</div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-3 p-2.5">
        <form method="GET" action="{{ route('tanks.index') }}" class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by tank name, code, or fuel item..." value="{{ request('search') }}">
                </div>
            </div>

            <!-- Filter by Product -->
            <div class="col-6 col-md-3">
                <select name="product_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">-- All Fuel Products --</option>
                    @foreach($fuelProducts as $fp)
                        <option value="{{ $fp->id }}" {{ request('product_id') == $fp->id ? 'selected' : '' }}>
                            {{ $fp->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter by Location -->
            <div class="col-6 col-md-3">
                <select name="location_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">-- All Station Sites --</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter by Status -->
            <div class="col-6 col-md-2 d-flex gap-1">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @if(request()->anyFilled(['search', 'product_id', 'location_id', 'status']))
                    <a href="{{ route('tanks.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tanks Grid -->
    @if($tanks->isEmpty())
        <div class="card border-0 shadow-sm rounded-3 bg-white p-5 text-center">
            <div class="text-muted mb-3"><i class="bi bi-database-x fs-1"></i></div>
            <h5 class="fw-bold text-dark">No Storage Tanks Found</h5>
            <p class="text-muted small mb-3">No fuel storage tanks match the selected filters or none have been added yet.</p>
            <div>
                <a href="{{ route('tanks.create') }}" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-plus-circle me-1"></i> Add First Storage Tank
                </a>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($tanks as $tank)
                @php
                    $pct = $tank->fill_percentage;
                    $fillColor = $pct < 20 ? 'danger' : ($pct < 45 ? 'warning' : 'success');
                @endphp
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-3 bg-white h-100 position-relative overflow-hidden">
                        <!-- Top Accent Line -->
                        <div class="bg-{{ $fillColor }}" style="height: 4px;"></div>

                        <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                            <!-- Card Header -->
                            <div>
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                    <div>
                                        <div class="d-flex align-items-center gap-1.5 mb-1">
                                            <span class="badge bg-dark text-white font-monospace px-2 py-0.5" style="font-size: 0.72rem;">{{ $tank->tank_number }}</span>
                                            <span class="badge bg-light text-secondary border px-2 py-0.5 text-capitalize" style="font-size: 0.7rem;">{{ $tank->tank_type }}</span>
                                            {!! $tank->status_badge !!}
                                        </div>
                                        <h5 class="fw-bold mb-0 text-dark">
                                            <a href="{{ route('tanks.show', $tank->id) }}" class="text-dark text-decoration-none hover-primary">
                                                {{ $tank->name }}
                                            </a>
                                        </h5>
                                    </div>

                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border p-1 rounded-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                                            <li>
                                                <a class="dropdown-item py-1.5" href="{{ route('tanks.show', $tank->id) }}">
                                                    <i class="bi bi-eye text-primary me-2"></i> View Details
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-1.5" href="javascript:void(0)" onclick="openDipModal({{ $tank->id }}, '{{ addslashes($tank->name) }}', {{ $tank->current_dip_reading ?? 0 }}, {{ $tank->current_stock }})">
                                                    <i class="bi bi-rulers text-success me-2"></i> Record Dip Reading
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-1.5" href="{{ route('purchases.create', ['tank_id' => $tank->id, 'product_id' => $tank->product_id]) }}">
                                                    <i class="bi bi-truck text-warning me-2"></i> Fuel Delivery Intake
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-1.5" href="{{ route('tanks.edit', $tank->id) }}">
                                                    <i class="bi bi-pencil-square text-secondary me-2"></i> Edit Tank Specs
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <form action="{{ route('tanks.destroy', $tank->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete storage tank {{ addslashes($tank->name) }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item py-1.5 text-danger">
                                                        <i class="bi bi-trash me-2"></i> Delete Tank
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Fuel Product & Site Location -->
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 text-muted small" style="font-size: 0.78rem;">
                                    <span><i class="bi bi-fuel-pump text-danger me-1"></i><strong>{{ $tank->product?->name ?? 'Unassigned' }}</strong></span>
                                    <span>&bull;</span>
                                    <span><i class="bi bi-geo-alt text-secondary me-1"></i>{{ $tank->location?->name ?? 'Main Site' }}</span>
                                </div>

                                <!-- Visual Tank Level Progress Bar -->
                                <div class="p-2.5 bg-light rounded-3 border mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                                        <span class="text-secondary small fw-semibold" style="font-size: 0.72rem;">TANK LEVEL</span>
                                        <span class="badge bg-{{ $fillColor }} font-monospace" style="font-size: 0.72rem;">{{ $pct }}% FULL</span>
                                    </div>

                                    <div class="progress rounded-pill shadow-inner" style="height: 14px; background-color: #e2e8f0;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-{{ $fillColor }}"
                                             role="progressbar"
                                             style="width: {{ $pct }}%;"
                                             aria-valuenow="{{ $pct }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between mt-2 small font-monospace" style="font-size: 0.73rem;">
                                        <span class="text-dark fw-bold">Live: {{ number_format($tank->current_stock, 1) }} L</span>
                                        <span class="text-muted">Cap: {{ number_format($tank->capacity) }} L</span>
                                    </div>
                                </div>

                                <!-- Key Specifications Stats Grid -->
                                <div class="row g-2 text-center mb-3">
                                    <div class="col-4">
                                        <div class="p-2 rounded-2 border bg-white">
                                            <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Available Ullage</div>
                                            <div class="fw-bold text-primary font-monospace" style="font-size: 0.8rem;">
                                                {{ number_format($tank->available_capacity, 0) }} L
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded-2 border bg-white">
                                            <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Dip Stick</div>
                                            <div class="fw-bold text-dark font-monospace" style="font-size: 0.8rem;">
                                                {{ $tank->current_dip_reading ? number_format($tank->current_dip_reading, 1) . ' cm' : 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded-2 border bg-white">
                                            <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Dead Buffer</div>
                                            <div class="fw-bold text-secondary font-monospace" style="font-size: 0.8rem;">
                                                {{ number_format($tank->dead_stock, 0) }} L
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Connected Pump Machines / Dispensers -->
                                <div class="pt-2 border-top">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="text-secondary small fw-semibold" style="font-size: 0.72rem;">CONNECTED DISPENSERS</span>
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill" style="font-size: 0.68rem;">
                                            {{ $tank->pumps->count() }} Machine{{ $tank->pumps->count() === 1 ? '' : 's' }}
                                        </span>
                                    </div>

                                    @if($tank->pumps->isEmpty())
                                        <div class="text-muted small fst-italic py-1" style="font-size: 0.72rem;">
                                            No dispensers currently drawing from this tank.
                                        </div>
                                    @else
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            @foreach($tank->pumps as $pump)
                                                <a href="{{ route('pumps.show', $pump->id) }}" class="badge bg-light text-dark border text-decoration-none hover-primary px-2 py-1" style="font-size: 0.72rem;">
                                                    <i class="bi bi-speedometer2 text-primary me-1"></i>{{ $pump->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="pt-3 mt-3 border-top d-flex align-items-center justify-content-between gap-2">
                                <button type="button" class="btn btn-outline-success btn-sm w-50 py-1.5" onclick="openDipModal({{ $tank->id }}, '{{ addslashes($tank->name) }}', {{ $tank->current_dip_reading ?? 0 }}, {{ $tank->current_stock }})">
                                    <i class="bi bi-rulers me-1"></i> Record Dip
                                </button>
                                <a href="{{ route('purchases.create', ['tank_id' => $tank->id, 'product_id' => $tank->product_id]) }}" class="btn btn-outline-primary btn-sm w-50 py-1.5">
                                    <i class="bi bi-truck me-1"></i> Intake Fuel
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3 pt-2">
            <div class="small text-muted">
                Showing {{ $tanks->firstItem() ?? 0 }} to {{ $tanks->lastItem() ?? 0 }} of {{ $tanks->total() }} tanks
            </div>
            <div>
                {{ $tanks->links() }}
            </div>
        </div>
    @endif

</div>

<!-- Modal: Record Daily Physical Dip Reading -->
<div class="modal fade" id="dipReadingModal" tabindex="-1" aria-labelledby="dipReadingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-dark text-white py-2.5 px-3">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2" id="dipReadingModalLabel">
                    <i class="bi bi-rulers text-warning"></i>
                    <span>Record Physical Dip Reading</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="dipReadingForm" method="POST" action="">
                @csrf
                <div class="modal-body p-3.5">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Record physical dip stick height (cm) to compare actual measured fuel in tank against the software book stock.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Target Storage Tank</label>
                        <input type="text" class="form-control form-control-sm bg-light" id="modalTankName" readonly>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Reading Date <span class="text-danger">*</span></label>
                            <input type="date" name="reading_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Current Book Stock</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm bg-light font-monospace" id="modalBookStock" readonly>
                                <span class="input-group-text bg-light">Ltr</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Dip Stick Height (cm) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" name="dip_cm" id="modalDipCm" class="form-control form-control-sm font-monospace" placeholder="e.g. 142.5" required>
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Measured Volume (Ltr) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" name="measured_liters" id="modalMeasuredLiters" class="form-control form-control-sm font-monospace" placeholder="e.g. 18500" required>
                                <span class="input-group-text">Ltr</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Notes / Calibration Remarks <span class="text-muted">(Optional)</span></label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. Morning physical dip verification before first shift..."></textarea>
                    </div>
                </div>

                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-3">
                        <i class="bi bi-check2-circle me-1"></i> Save Dip Measurement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openDipModal(tankId, tankName, currentDip, bookStock) {
    const form = document.getElementById('dipReadingForm');
    form.action = "{{ url('tanks') }}/" + tankId + "/record-dip";

    document.getElementById('modalTankName').value = tankName;
    document.getElementById('modalBookStock').value = Number(bookStock).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
    document.getElementById('modalDipCm').value = currentDip > 0 ? currentDip : '';
    document.getElementById('modalMeasuredLiters').value = bookStock > 0 ? bookStock : '';

    const modal = new bootstrap.Modal(document.getElementById('dipReadingModal'));
    modal.show();
}
</script>
@endsection
