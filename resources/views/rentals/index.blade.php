@extends('layouts.app')

@section('title', 'Station Rentals & Lease Incomes')
@section('page_title', 'Station Rentals & Other Incomes')

@section('page_badge')
    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1 small">
        <i class="bi bi-shop me-1"></i> Car Wash, Tyre Shop & Mart Incomes
    </span>
@endsection

@section('page_actions')
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-primary btn-sm rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newFacilityModal">
            <i class="bi bi-plus-circle me-1"></i> Add Facility
        </button>
    </div>
@endsection

@section('content')
<!-- Top KPI Ribbon -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-custom p-3 border-start border-4 border-primary h-100 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Expected Monthly Rent</div>
            <div class="fs-4 fw-bold text-dark mt-1">Rs. {{ number_format($totalMonthlyExpected, 2) }}</div>
            <div class="text-secondary small mt-1" style="font-size: 0.75rem;">
                From {{ $rentals->where('status', 'active')->count() }} Active Leased Units
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-custom p-3 border-start border-4 border-success h-100 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Collected This Month</div>
            <div class="fs-4 fw-bold text-success mt-1">Rs. {{ number_format($currentMonthCollections, 2) }}</div>
            <div class="text-secondary small mt-1" style="font-size: 0.75rem;">
                Month: {{ $currentMonthName }}
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-custom p-3 border-start border-4 border-warning h-100 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Pending Balance</div>
            @php $pendingThisMonth = max(0, $totalMonthlyExpected - $currentMonthCollections); @endphp
            <div class="fs-4 fw-bold {{ $pendingThisMonth > 0 ? 'text-warning-emphasis' : 'text-success' }} mt-1">
                Rs. {{ number_format($pendingThisMonth, 2) }}
            </div>
            <div class="text-secondary small mt-1" style="font-size: 0.75rem;">
                {{ $pendingThisMonth == 0 ? 'All rentals collected!' : 'Due from active tenants' }}
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-custom p-3 border-start border-4 border-info h-100 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Lifetime Income</div>
            <div class="fs-4 fw-bold text-info mt-1">Rs. {{ number_format($allTimeCollections, 2) }}</div>
            <div class="text-secondary small mt-1" style="font-size: 0.75rem;">
                All-time collected &amp; ledgered
            </div>
        </div>
    </div>
</div>

<!-- Leased Facilities Table -->
<div class="card-custom mb-4 shadow-sm">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-building-check text-primary fs-5"></i>
            <h6 class="fw-bold text-dark m-0">Station Commercial Units &amp; Monthly Rent Specs</h6>
        </div>
        <span class="badge bg-light text-secondary border">{{ $rentals->count() }} Units</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-secondary">
                    <th>Facility / Space</th>
                    <th>Type</th>
                    <th>Tenant Details</th>
                    <th class="text-end">Monthly Rent</th>
                    <th class="text-center">Due Day</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentals as $rental)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $rental->name }}</div>
                            @if($rental->notes)
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ Str::limit($rental->notes, 60) }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $rental->facility_type_label }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><i class="bi bi-person me-1 text-primary"></i>{{ $rental->tenant_name }}</div>
                            @if($rental->tenant_phone)
                                <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $rental->tenant_phone }}</div>
                            @endif
                        </td>
                        <td class="text-end fw-bold text-dark fs-6">
                            Rs. {{ number_format($rental->monthly_rent, 2) }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-secondary border">Every {{ $rental->due_day }}th</span>
                        </td>
                        <td class="text-center">
                            @if($rental->status === 'active')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-1.5">
                                <button type="button" class="btn btn-success btn-sm py-1 px-2.5 rounded-2 shadow-sm"
                                        style="font-size: 0.78rem;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#collectRentModal{{ $rental->id }}">
                                    <i class="bi bi-cash-coin me-1"></i> Collect Rent
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 rounded-2"
                                        style="font-size: 0.78rem;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editFacilityModal{{ $rental->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal: Collect Rent for this Facility -->
                    <div class="modal fade" id="collectRentModal{{ $rental->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <form action="{{ route('rentals.collect', $rental->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-header bg-success text-white">
                                        <h6 class="modal-title fw-bold">
                                            <i class="bi bi-cash-coin me-1"></i> Collect Monthly Rent &bull; {{ $rental->name }}
                                        </h6>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="mb-3 p-2.5 bg-light rounded-3 border">
                                            <div class="d-flex justify-content-between">
                                                <span class="text-secondary small">Tenant:</span>
                                                <strong class="text-dark">{{ $rental->tenant_name }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mt-1">
                                                <span class="text-secondary small">Standard Monthly Rent:</span>
                                                <strong class="text-success">Rs. {{ number_format($rental->monthly_rent, 2) }}</strong>
                                            </div>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
                                                <input type="date" name="payment_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small fw-semibold">Rent Month / Period <span class="text-danger">*</span></label>
                                                <input type="month" name="rent_month" class="form-control form-control-sm" value="{{ $currentMonth }}" required>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small fw-semibold">Amount Received (Rs.) <span class="text-danger">*</span></label>
                                                <input type="text" inputmode="decimal" name="amount" class="form-control form-control-sm fw-bold text-success amount-format" value="{{ (float) $rental->monthly_rent > 0 ? $rental->monthly_rent : '' }}" placeholder="e.g. 50,000" autocomplete="off" onfocus="this.select()" required>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small fw-semibold">Deposit Account <span class="text-danger">*</span></label>
                                                <select name="account_id" class="form-select form-select-sm" required>
                                                    @foreach($accounts as $acc)
                                                        <option value="{{ $acc->id }}" {{ $acc->type === 'cash' ? 'selected' : '' }}>
                                                            {{ $acc->name }} ({{ ucfirst($acc->type) }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-semibold">Receipt No. / Ref <span class="text-muted">(Optional)</span></label>
                                                <input type="text" name="receipt_no" class="form-control form-control-sm" placeholder="e.g. REC-{{ date('Ym') }}-01">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-semibold">Payment Notes</label>
                                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="e.g. Paid in full for {{ $currentMonthName }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                                            <i class="bi bi-check2-circle me-1"></i> Confirm &amp; Deposit to Ledger
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal: Edit Facility -->
                    <div class="modal fade" id="editFacilityModal{{ $rental->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <form action="{{ route('rentals.update', $rental->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h6 class="modal-title fw-bold">Edit Facility Specs &bull; {{ $rental->name }}</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label small fw-semibold">Facility Name <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control form-control-sm" value="{{ $rental->name }}" required>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <label class="form-label small fw-semibold mb-0">Facility Type <span class="text-danger">*</span></label>
                                                    <button type="button" class="btn btn-link p-0 text-primary fw-semibold text-decoration-none quick-add-type-trigger" style="font-size: 0.74rem;" data-bs-toggle="modal" data-bs-target="#quickAddFacilityTypeModal" data-target-select="#edit_facility_type_{{ $rental->id }}">
                                                        <i class="bi bi-plus-circle-fill me-0.5"></i> + Quick Add
                                                    </button>
                                                </div>
                                                <div class="input-group input-group-sm">
                                                    <select name="facility_type" id="edit_facility_type_{{ $rental->id }}" class="form-select form-select-sm facility-type-dropdown" required>
                                                        @foreach($facilityTypes as $type)
                                                            <option value="{{ $type->slug }}" {{ $rental->facility_type === $type->slug ? 'selected' : '' }}>
                                                                {{ $type->display_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="btn btn-outline-primary quick-add-type-trigger" data-bs-toggle="modal" data-bs-target="#quickAddFacilityTypeModal" data-target-select="#edit_facility_type_{{ $rental->id }}" title="Quick Add Facility Type">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small fw-semibold">Monthly Rent (Rs.) <span class="text-danger">*</span></label>
                                                <input type="text" inputmode="decimal" name="monthly_rent" class="form-control form-control-sm fw-bold amount-format" value="{{ $rental->monthly_rent }}" placeholder="e.g. 35,000" autocomplete="off" onfocus="this.select()" required>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small fw-semibold">Tenant Name <span class="text-danger">*</span></label>
                                                <input type="text" name="tenant_name" class="form-control form-control-sm" value="{{ $rental->tenant_name }}" required>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small fw-semibold">Tenant Phone</label>
                                                <input type="text" name="tenant_phone" class="form-control form-control-sm" value="{{ $rental->tenant_phone }}">
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small fw-semibold">Due Day of Month</label>
                                                <input type="number" min="1" max="31" name="due_day" class="form-control form-control-sm" value="{{ $rental->due_day }}">
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small fw-semibold">Status</label>
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="active" {{ $rental->status === 'active' ? 'selected' : '' }}>Active</option>
                                                    <option value="inactive" {{ $rental->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-semibold">Notes / Lease Agreement Details</label>
                                                <textarea name="notes" class="form-control form-control-sm" rows="2">{{ $rental->notes }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                            No rental facilities registered yet. Click "+ Add Facility" to register Car Wash, Tyre Shop, or Tuck Shop!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Collections History -->
<div class="card-custom shadow-sm">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-success fs-5"></i>
            <h6 class="fw-bold text-dark m-0">Recent Rental Collection Entries &bull; Income Ledger Log</h6>
        </div>
        <span class="badge bg-light text-secondary border">Last 20 Receipts</span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-secondary">
                    <th>Date</th>
                    <th>Facility &amp; Tenant</th>
                    <th>Rent Month</th>
                    <th>Deposited Account</th>
                    <th>Receipt #</th>
                    <th class="text-end">Amount</th>
                    <th>Received By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentCollections as $col)
                    <tr>
                        <td class="fw-semibold text-dark">{{ $col->payment_date->format('d M, Y') }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $col->rental?->name }}</div>
                            <div class="text-muted small">{{ $col->rental?->tenant_name }}</div>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                <i class="bi bi-calendar-event me-1"></i>{{ Carbon\Carbon::parse($col->rent_month . '-01')->format('M Y') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-wallet2 me-1 text-success"></i>{{ $col->account?->name ?? 'Cash Account' }}
                            </span>
                        </td>
                        <td class="font-monospace small text-muted">{{ $col->receipt_no ?: 'RENT-' . $col->id }}</td>
                        <td class="text-end fw-bold text-success fs-6">+Rs. {{ number_format($col->amount, 2) }}</td>
                        <td class="small text-secondary">{{ $col->receivedBy?->name ?? 'Admin' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            No rent collections recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add New Facility -->
<div class="modal fade" id="newFacilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('rentals.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-1"></i> Add Station Rental Facility
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Facility Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Car Wash Service Ramp #1" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold mb-0">Facility Type <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link p-0 text-primary fw-semibold text-decoration-none quick-add-type-trigger" style="font-size: 0.74rem;" data-bs-toggle="modal" data-bs-target="#quickAddFacilityTypeModal" data-target-select="#create_facility_type">
                                    <i class="bi bi-plus-circle-fill me-0.5"></i> + Quick Add
                                </button>
                            </div>
                            <div class="input-group input-group-sm">
                                <select name="facility_type" id="create_facility_type" class="form-select form-select-sm facility-type-dropdown" required>
                                    @foreach($facilityTypes as $type)
                                        <option value="{{ $type->slug }}" {{ old('facility_type', 'car_wash') === $type->slug ? 'selected' : '' }}>
                                            {{ $type->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary quick-add-type-trigger" data-bs-toggle="modal" data-bs-target="#quickAddFacilityTypeModal" data-target-select="#create_facility_type" title="Quick Add Facility Type">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold">Monthly Rent (Rs.) <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" name="monthly_rent" class="form-control form-control-sm fw-bold text-success amount-format" value="{{ old('monthly_rent') }}" placeholder="e.g. 35,000" autocomplete="off" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold">Tenant / Operator Name <span class="text-danger">*</span></label>
                            <input type="text" name="tenant_name" class="form-control form-control-sm" placeholder="e.g. Muhammad Aslam" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold">Tenant Phone</label>
                            <input type="text" name="tenant_phone" class="form-control form-control-sm" placeholder="e.g. 0300-1234567">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold">Due Day of Month</label>
                            <input type="number" min="1" max="31" name="due_day" class="form-control form-control-sm" value="5">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notes / Agreement Details</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Lease duration, terms, security deposit..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">
                        <i class="bi bi-check2 me-1"></i> Register Facility
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Quick Add Facility Type / Lease Entity -->
<div class="modal fade" id="quickAddFacilityTypeModal" tabindex="-1" aria-labelledby="quickAddFacilityTypeModalLabel" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-2.5 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-2 d-flex align-items-center justify-content-center bg-primary text-white" style="width: 28px; height: 28px;">
                        <i class="bi bi-building-add" style="font-size: 0.9rem;"></i>
                    </div>
                    <h6 class="modal-title fw-bold text-white m-0" id="quickAddFacilityTypeModalLabel" style="font-size: 0.92rem;">Quick Add Facility Entity</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickAddFacilityTypeForm" onsubmit="handleQuickFacilityTypeSubmit(event)">
                <div class="modal-body p-3">
                    <div id="quickFacilityTypeAlert" class="alert alert-danger py-2 px-3 small mb-3" style="display: none; font-size: 0.78rem;"></div>

                    <div class="mb-3">
                        <label for="quick_facility_name" class="form-label small fw-semibold">
                            Facility / Lease Entity Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm fw-semibold" id="quick_facility_name" name="name" required
                               placeholder="e.g. Electric Vehicle Charger, Restaurant, Warehouse">
                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">Enter the name of the commercial or leased entity.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Icon / Symbol</label>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-light" id="preview_icon" style="font-size: 1.1rem; width: 44px; justify-content: center;">🏢</span>
                            <input type="text" class="form-control form-control-sm" id="quick_facility_icon" name="icon" value="🏢" maxlength="10" placeholder="e.g. ⚡, 🍔, 🚗">
                        </div>
                        <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                            <span class="text-muted small me-1" style="font-size: 0.7rem;">Quick pick:</span>
                            @foreach(['🏢', '🚿', '🛞', '🏪', '🏧', '⚡', '☕', '🍔', '🔧', '📦', '🚗', '⛽', '🍕', '🩺'] as $emoji)
                                <button type="button" class="btn btn-sm btn-light border py-0 px-1.5 text-center select-quick-emoji" style="font-size: 0.85rem; line-height: 1.4;" data-emoji="{{ $emoji }}">
                                    {{ $emoji }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="quick_facility_desc" class="form-label small fw-semibold">Description / Notes <span class="text-muted fw-normal">(Optional)</span></label>
                        <input type="text" class="form-control form-control-sm" id="quick_facility_desc" name="description" placeholder="Short description or usage purpose">
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3" id="quickFacilitySubmitBtn">
                        <span class="spinner-border spinner-border-sm me-1" id="quickFacilitySpinner" style="display: none;" role="status"></span>
                        <i class="bi bi-check2-circle me-1" id="quickFacilitySubmitIcon"></i> Save &amp; Select
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let activeTargetSelectSelector = '#create_facility_type';

    // Set active select target on quick add button click
    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('.quick-add-type-trigger');
        if (trigger) {
            const target = trigger.getAttribute('data-target-select');
            if (target) {
                activeTargetSelectSelector = target;
            }
        }
    });

    // Quick emoji selector
    document.querySelectorAll('.select-quick-emoji').forEach(btn => {
        btn.addEventListener('click', function() {
            const emoji = this.getAttribute('data-emoji');
            const iconInput = document.getElementById('quick_facility_icon');
            const previewIcon = document.getElementById('preview_icon');
            if (iconInput) iconInput.value = emoji;
            if (previewIcon) previewIcon.textContent = emoji;
        });
    });

    const iconInput = document.getElementById('quick_facility_icon');
    if (iconInput) {
        iconInput.addEventListener('input', function() {
            const previewIcon = document.getElementById('preview_icon');
            if (previewIcon) {
                previewIcon.textContent = this.value.trim() || '🏢';
            }
        });
    }

    // Handle nested modals in Bootstrap 5
    const quickAddModalEl = document.getElementById('quickAddFacilityTypeModal');
    if (quickAddModalEl) {
        quickAddModalEl.addEventListener('show.bs.modal', function () {
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 1) {
                    backdrops[backdrops.length - 1].style.zIndex = '1065';
                }
            }, 10);
        });

        quickAddModalEl.addEventListener('hidden.bs.modal', function () {
            // Keep body scrollable/locked properly when returning to previous modal
            if (document.querySelectorAll('.modal.show').length > 0) {
                document.body.classList.add('modal-open');
            }
        });
    }

    function handleQuickFacilityTypeSubmit(e) {
        e.preventDefault();
        const alertBox = document.getElementById('quickFacilityTypeAlert');
        const submitBtn = document.getElementById('quickFacilitySubmitBtn');
        const spinner = document.getElementById('quickFacilitySpinner');
        const submitIcon = document.getElementById('quickFacilitySubmitIcon');
        const nameInput = document.getElementById('quick_facility_name');
        const iconInput = document.getElementById('quick_facility_icon');
        const descInput = document.getElementById('quick_facility_desc');

        alertBox.style.display = 'none';
        submitBtn.disabled = true;
        spinner.style.display = 'inline-block';
        if (submitIcon) submitIcon.style.display = 'none';

        fetch("{{ route('rentals.facility-types.quick-store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: nameInput.value,
                icon: iconInput.value,
                description: descInput.value
            })
        })
        .then(async (res) => {
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Error adding facility type.'));
            }
            return data;
        })
        .then((data) => {
            if (data.success && data.facility_type) {
                const ft = data.facility_type;

                // 1. Append option to all facility type dropdowns on the page
                const allDropdowns = document.querySelectorAll('.facility-type-dropdown');
                allDropdowns.forEach(selectEl => {
                    let existingOpt = Array.from(selectEl.options).find(opt => opt.value === ft.slug);
                    if (!existingOpt) {
                        const opt = document.createElement('option');
                        opt.value = ft.slug;
                        opt.textContent = ft.display_name;
                        selectEl.appendChild(opt);
                    }
                });

                // 2. Select the new facility type in the target dropdown
                if (activeTargetSelectSelector) {
                    const targetSelect = document.querySelector(activeTargetSelectSelector);
                    if (targetSelect) {
                        targetSelect.value = ft.slug;
                        targetSelect.dispatchEvent(new Event('change'));
                    }
                }

                // 3. Close the modal
                const modalInstance = bootstrap.Modal.getInstance(quickAddModalEl) || new bootstrap.Modal(quickAddModalEl);
                modalInstance.hide();

                // 4. Reset quick add form
                document.getElementById('quickAddFacilityTypeForm').reset();
                const previewIcon = document.getElementById('preview_icon');
                if (previewIcon) previewIcon.textContent = '🏢';
                if (iconInput) iconInput.value = '🏢';
            }
        })
        .catch((err) => {
            alertBox.style.display = 'block';
            alertBox.textContent = err.message || 'An error occurred while creating facility type.';
        })
        .finally(() => {
            submitBtn.disabled = false;
            spinner.style.display = 'none';
            if (submitIcon) submitIcon.style.display = 'inline-block';
        });
    }
</script>
@endpush
