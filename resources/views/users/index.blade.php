@extends('layouts.app')

@section('title', 'Users Management')
@section('page_title', 'Users & Access Control')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-people me-1 text-primary"></i> Total: {{ $users->total() }}
    </span>
@endsection

@section('page_actions')
    <button type="button" class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm text-nowrap w-100 w-sm-auto" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="bi bi-person-plus-fill me-1"></i> Add New User
    </button>
@endsection

@push('styles')
<style>
    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    @media (max-width: 575.98px) {
        .card-custom {
            border-radius: 10px;
        }
    }
</style>
@endpush

@section('content')
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 py-2 px-3 mb-3 small" role="alert">
        <div class="d-flex align-items-center mb-1">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-6 text-danger"></i>
            <div class="fw-bold">Validation Error:</div>
        </div>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card-custom overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="fw-bold text-dark m-0">Authorized System Users</h6>
            <small class="text-muted" style="font-size: 0.78rem;">Manage system access for Cashiers, Incharges, and Owners</small>
        </div>
    </div>

    <!-- Desktop View: Full Data Table -->
    <div class="d-none d-md-block table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>User Name</th>
                    <th>Email Address</th>
                    <th>Role</th>
                    <th>Account Status</th>
                    <th>Created On</th>
                    <th class="text-end" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $index => $u)
                    <tr>
                        <td class="text-muted small">{{ $users->firstItem() + $index }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem; border: 1px solid #e2e8f0;">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">{{ $u->name }}</div>
                                    @if($u->id === auth()->id())
                                        <span class="badge bg-primary-subtle text-primary border border-primary" style="font-size: 0.65rem; padding: 2px 6px;">You</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="font-monospace text-secondary small">{{ $u->email }}</td>
                        <td>
                            @if($u->isOwner())
                                <span class="badge-role-owner"><i class="bi bi-shield-lock me-1"></i>Owner</span>
                            @elseif($u->isIncharge())
                                <span class="badge-role-incharge"><i class="bi bi-person-badge me-1"></i>Incharge</span>
                            @else
                                <span class="badge-role-cashier"><i class="bi bi-cash me-1"></i>Cashier</span>
                            @endif
                        </td>
                        <td>
                            @if($u->status === 'active')
                                <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.72rem; padding: 3px 8px;">
                                    <i class="bi bi-check-circle me-1"></i>Active
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger" style="font-size: 0.72rem; padding: 3px 8px;">
                                    <i class="bi bi-slash-circle me-1"></i>Inactive
                                </span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $u->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}" title="Edit Profile">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                @if($u->id !== auth()->id())
                                    <form action="{{ route('users.toggle-status', $u->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning btn-sm py-1 px-2" title="Toggle Status (Active/Inactive)">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('users.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete User">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted small">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll) -->
    <div class="d-block d-md-none p-2 p-sm-3">
        <div class="d-flex flex-column gap-2">
            @forelse($users as $u)
                <div class="p-3 rounded-3 border bg-white shadow-xs">
                    <!-- User Header: Avatar, Name, Role -->
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 38px; height: 38px; font-size: 0.95rem; border: 1px solid rgba(37,99,235,0.2);">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="fw-bold text-dark text-truncate" style="font-size: 0.92rem;">
                                    {{ $u->name }}
                                    @if($u->id === auth()->id())
                                        <span class="badge bg-primary-subtle text-primary border border-primary ms-1" style="font-size: 0.65rem; padding: 2px 6px;">You</span>
                                    @endif
                                </div>
                                <div class="text-muted text-truncate font-monospace" style="font-size: 0.74rem;">
                                    <i class="bi bi-envelope text-secondary me-1"></i>{{ $u->email }}
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            @if($u->isOwner())
                                <span class="badge-role-owner"><i class="bi bi-shield-lock me-1"></i>Owner</span>
                            @elseif($u->isIncharge())
                                <span class="badge-role-incharge"><i class="bi bi-person-badge me-1"></i>Incharge</span>
                            @else
                                <span class="badge-role-cashier"><i class="bi bi-cash me-1"></i>Cashier</span>
                            @endif
                        </div>
                    </div>

                    <!-- Info Meta: Status & Date -->
                    <div class="d-flex align-items-center justify-content-between py-2 border-top border-bottom text-muted" style="font-size: 0.75rem;">
                        <div>
                            <span class="text-secondary">Status:</span>
                            @if($u->status === 'active')
                                <span class="badge bg-success-subtle text-success border border-success ms-1" style="font-size: 0.72rem; padding: 2px 7px;">
                                    <i class="bi bi-check-circle me-1"></i>Active
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger ms-1" style="font-size: 0.72rem; padding: 2px 7px;">
                                    <i class="bi bi-slash-circle me-1"></i>Inactive
                                </span>
                            @endif
                        </div>
                        <div>
                            <span class="text-secondary"><i class="bi bi-calendar3 me-1"></i>{{ $u->created_at->format('d M Y') }}</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex align-items-center gap-1.5 pt-2 mt-1">
                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill py-1.5 px-2 rounded-2 fw-semibold" style="font-size: 0.78rem;" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>

                        @if($u->id !== auth()->id())
                            <form action="{{ route('users.toggle-status', $u->id) }}" method="POST" class="flex-fill m-0">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning btn-sm w-100 py-1.5 px-2 rounded-2 fw-semibold" style="font-size: 0.78rem;" title="Toggle Account Status">
                                    <i class="bi bi-power me-1"></i> {{ $u->status === 'active' ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>

                            <form action="{{ route('users.destroy', $u->id) }}" method="POST" class="flex-shrink-0 m-0" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm py-1.5 px-2.5 rounded-2" style="font-size: 0.78rem;" title="Delete User">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">No users found.</div>
            @endforelse
        </div>
    </div>

    @if($users->hasPages())
        <div class="p-3 border-top bg-light overflow-x-auto">
            {{ $users->links() }}
        </div>
    @endif
</div>

<!-- Edit User Modals (Rendered outside table/card containers to avoid duplicate IDs) -->
@foreach($users as $u)
    <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $u->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow text-start">
                <div class="modal-header border-bottom py-3">
                    <div>
                        <h6 class="modal-title fw-bold text-dark m-0" id="editModalLabel{{ $u->id }}">Edit Profile: {{ $u->name }}</h6>
                        <small class="text-muted" style="font-size: 0.78rem;">Update user credentials, role or account status</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('users.update', $u->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-3 p-sm-4 text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm py-2" name="name" value="{{ old('name', $u->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Email Address (Login ID) <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-sm py-2" name="email" value="{{ old('email', $u->email) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">New Password <span class="text-muted fw-normal">(Leave blank to keep current)</span></label>
                            <input type="password" class="form-control form-control-sm py-2" name="password" placeholder="Leave empty if unchanged">
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold text-secondary">Role <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-2" name="role" required>
                                    <option value="cashier" {{ old('role', $u->role) === 'cashier' ? 'selected' : '' }}>Cashier (Shift Entry)</option>
                                    <option value="incharge" {{ old('role', $u->role) === 'incharge' ? 'selected' : '' }}>Incharge (Supervisor)</option>
                                    @if(auth()->user()->isOwner() || $u->isOwner())
                                        <option value="owner" {{ old('role', $u->role) === 'owner' ? 'selected' : '' }}>Owner (Administrator)</option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold text-secondary">Status <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-2" name="status" required>
                                    <option value="active" {{ old('status', $u->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status', $u->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-2">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold rounded-3 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <div>
                    <h6 class="modal-title fw-bold text-dark m-0" id="createUserModalLabel">New User Information</h6>
                    <small class="text-muted" style="font-size: 0.78rem;">Create credentials for Cashier, Incharge, or Owner</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-body p-3 p-sm-4 text-start">
                    <div class="mb-3">
                        <label for="create_name" class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm py-2 @error('name') is-invalid @enderror" id="create_name" name="name" value="{{ old('name') }}" placeholder="e.g. John Doe" required>
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="create_email" class="form-label small fw-semibold text-secondary">Email Address (Login ID) <span class="text-danger">*</span></label>
                        <input type="email" class="form-control form-control-sm py-2 @error('email') is-invalid @enderror" id="create_email" name="email" value="{{ old('email') }}" placeholder="name@domain.com" required>
                        @error('email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="create_password" class="form-label small fw-semibold text-secondary">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-sm py-2 @error('password') is-invalid @enderror" id="create_password" name="password" placeholder="Minimum 6 characters" required>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-12 col-sm-6">
                            <label for="create_role" class="form-label small fw-semibold text-secondary">Role <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm py-2 @error('role') is-invalid @enderror" id="create_role" name="role" required>
                                <option value="" disabled {{ old('role') ? '' : 'selected' }}>-- Select Role --</option>
                                <option value="cashier" {{ old('role') === 'cashier' ? 'selected' : '' }}>Cashier (Shift Entry)</option>
                                <option value="incharge" {{ old('role') === 'incharge' ? 'selected' : '' }}>Incharge (Supervisor)</option>
                                @if(auth()->user()->isOwner())
                                    <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>Owner (Administrator)</option>
                                @endif
                            </select>
                            @error('role')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="create_status" class="form-label small fw-semibold text-secondary">Status <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm py-2 @error('status') is-invalid @enderror" id="create_status" name="status" required>
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', 'inactive') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold rounded-3 shadow-sm">
                        <i class="bi bi-save me-1"></i> Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@if($errors->any() && !old('_method'))
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var createModalEl = document.getElementById('createUserModal');
        if (createModalEl) {
            var createModal = new bootstrap.Modal(createModalEl);
            createModal.show();
        }
    });
</script>
@endpush
@endif
