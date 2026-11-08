@extends('layouts.app')

@section('title', 'Station Locations & Sites - Master Setup')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Header Breadcrumb & Actions -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item text-secondary">Master Setup</li>
                    <li class="breadcrumb-item active" aria-current="page">Station Locations</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt-fill text-danger"></i>
                <span>Station Sites &amp; Filling Locations</span>
            </h4>
            <p class="text-muted small mb-0">Multi-site filling station locations, branch sites, addresses, and physical assets grouping.</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('tanks.index') }}" class="btn btn-outline-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1.5">
                <i class="bi bi-database-fill-gear"></i>
                <span>Storage Tanks</span>
            </a>
            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#createLocationModal">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Add Station Site</span>
            </button>
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

    <!-- Locations Table Card -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle small mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Station Name</th>
                        <th>Station Code</th>
                        <th>City / Address</th>
                        <th>Incharge / Manager</th>
                        <th>Phone</th>
                        <th class="text-center">Tanks</th>
                        <th class="text-center">Dispensers</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $loc)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark d-flex align-items-center gap-1.5">
                                    <i class="bi bi-fuel-pump text-primary"></i>
                                    <span>{{ $loc->name }}</span>
                                    @if($loc->is_default)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2" style="font-size: 0.65rem;">Primary Site</span>
                                    @endif
                                </div>
                            </td>
                            <td class="font-monospace text-secondary">{{ $loc->code ?: '-' }}</td>
                            <td>
                                <div>{{ $loc->city ?: 'N/A' }}</div>
                                @if($loc->address)
                                    <div class="text-muted small text-truncate" style="max-width: 200px;">{{ $loc->address }}</div>
                                @endif
                            </td>
                            <td>{{ $loc->manager_name ?: '-' }}</td>
                            <td class="font-monospace">{{ $loc->phone ?: '-' }}</td>
                            <td class="text-center">
                                <a href="{{ route('tanks.index', ['location_id' => $loc->id]) }}" class="badge bg-light text-dark border text-decoration-none">
                                    {{ $loc->tanks_count }} Tank{{ $loc->tanks_count === 1 ? '' : 's' }}
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-secondary border">
                                    {{ $loc->pumps_count }} Dispenser{{ $loc->pumps_count === 1 ? '' : 's' }}
                                </span>
                            </td>
                            <td class="text-center">{!! $loc->status_badge !!}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Edit Site" onclick="editLocation({{ json_encode($loc) }})">
                                        <i class="bi bi-pencil-square text-secondary"></i>
                                    </button>
                                    @if(!$loc->is_default && $loc->tanks_count == 0 && $loc->pumps_count == 0)
                                        <form action="{{ route('locations.destroy', $loc->id) }}" method="POST" onsubmit="return confirm('Delete location {{ addslashes($loc->name) }}?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border py-1 px-2 text-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No station locations configured yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($locations->hasPages())
            <div class="pt-3 border-top d-flex justify-content-end">
                {{ $locations->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal: Create Station Location -->
<div class="modal fade" id="createLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white py-2.5 px-3">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>Add Station Site / Location</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('locations.store') }}" method="POST">
                @csrf
                <div class="modal-body p-3.5">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Station Site Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Caltex Rao Filling Station - Bypass Branch" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Station Code</label>
                            <input type="text" name="code" class="form-control form-control-sm text-uppercase font-monospace" placeholder="e.g. CRFS-02">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">City</label>
                            <input type="text" name="city" class="form-control form-control-sm" placeholder="e.g. Hyderabad">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Manager / Incharge</label>
                            <input type="text" name="manager_name" class="form-control form-control-sm" placeholder="e.g. Station Supervisor">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Phone Helpline</label>
                            <input type="text" name="phone" class="form-control form-control-sm font-monospace" placeholder="e.g. +92 317 3836223">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Address / Location Landmark</label>
                        <textarea name="address" class="form-control form-control-sm" rows="2" placeholder="e.g. Main Highway Road, Near Toll Plaza..."></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Operational Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-sm" required>
                            <option value="active" selected>Active Site</option>
                            <option value="inactive">Inactive Site</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Save Station Site</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Station Location -->
<div class="modal fade" id="editLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-dark text-white py-2.5 px-3">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square text-warning"></i>
                    <span>Edit Station Site</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="editLocationForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-3.5">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Station Site Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_loc_name" class="form-control form-control-sm" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Station Code</label>
                            <input type="text" name="code" id="edit_loc_code" class="form-control form-control-sm text-uppercase font-monospace">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">City</label>
                            <input type="text" name="city" id="edit_loc_city" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Manager / Incharge</label>
                            <input type="text" name="manager_name" id="edit_loc_manager" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Phone Helpline</label>
                            <input type="text" name="phone" id="edit_loc_phone" class="form-control form-control-sm font-monospace">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Address / Location Landmark</label>
                        <textarea name="address" id="edit_loc_address" class="form-control form-control-sm" rows="2"></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Operational Status <span class="text-danger">*</span></label>
                        <select name="status" id="edit_loc_status" class="form-select form-select-sm" required>
                            <option value="active">Active Site</option>
                            <option value="inactive">Inactive Site</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Update Station Site</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editLocation(loc) {
    const form = document.getElementById('editLocationForm');
    form.action = "{{ url('locations') }}/" + loc.id;

    document.getElementById('edit_loc_name').value = loc.name || '';
    document.getElementById('edit_loc_code').value = loc.code || '';
    document.getElementById('edit_loc_city').value = loc.city || '';
    document.getElementById('edit_loc_manager').value = loc.manager_name || '';
    document.getElementById('edit_loc_phone').value = loc.phone || '';
    document.getElementById('edit_loc_address').value = loc.address || '';
    document.getElementById('edit_loc_status').value = loc.status || 'active';

    const modal = new bootstrap.Modal(document.getElementById('editLocationModal'));
    modal.show();
}
</script>
@endsection
