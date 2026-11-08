@extends('layouts.app')

@section('title', 'Edit Role Permissions - Caltex Rao Filling Station')
@section('page_title', 'Configure Role: ' . ucfirst($role->name))

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-shield-shaded me-1 text-primary"></i> {{ count($rolePermissions) }} Active Permissions
    </span>
@endsection

@section('page_actions')
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold px-3 shadow-sm text-nowrap">
        <i class="bi bi-arrow-left me-1"></i> Back to Roles
    </a>
@endsection

@push('styles')
<style>
    .perm-group-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.2s ease;
    }
    .perm-group-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 0.75rem 1rem;
    }
    .perm-checkbox-label {
        font-size: 0.88rem;
        cursor: pointer;
        user-select: none;
    }
    .perm-checkbox-label:hover {
        color: #2563eb;
    }
    .perm-item {
        border-radius: 6px;
        padding: 6px 10px;
        transition: background-color 0.15s ease;
    }
    .perm-item:hover {
        background-color: #f1f5f9;
    }
</style>
@endpush

@section('content')
@php
    $isCore = in_array(strtolower($role->name), ['owner', 'incharge', 'cashier']);
@endphp

<form action="{{ route('roles.update', $role->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card-custom p-3 p-md-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-6">
                <label for="name" class="form-label fw-bold text-dark">
                    Role Name <span class="text-danger">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       class="form-control @error('name') is-invalid @enderror" 
                       value="{{ old('name', ucfirst($role->name)) }}" 
                       {{ $isCore ? 'readonly' : 'required' }}>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if($isCore)
                    <small class="text-primary fw-semibold"><i class="bi bi-info-circle me-1"></i> System core role names cannot be renamed.</small>
                @else
                    <small class="text-muted">Unique role identifier.</small>
                @endif
            </div>

            <div class="col-12 col-md-6 text-md-end pt-md-3">
                @if(strtolower($role->name) !== 'owner')
                    <div class="btn-group shadow-sm">
                        <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" id="selectAllGlobal">
                            <i class="bi bi-check-all me-1"></i> Select All
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold" id="deselectAllGlobal">
                            <i class="bi bi-x-lg me-1"></i> Deselect All
                        </button>
                    </div>
                @else
                    <span class="badge bg-danger p-2"><i class="bi bi-shield-lock me-1"></i> Owner has unconditional super-admin access</span>
                @endif
            </div>
        </div>
    </div>

    @if(strtolower($role->name) === 'owner')
        <div class="alert alert-info border-0 shadow-sm rounded-3 py-2 px-3 mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
            <div>
                <strong>Notice:</strong> The <strong>Owner</strong> role is the Master Administrator account for this station. It permanently retains all system permissions by design.
            </div>
        </div>
    @endif

    <!-- Grouped Permissions -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="bi bi-key text-primary"></i> Configured Permissions
        </h5>
        <div class="input-group" style="max-width: 280px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="filterPerms" class="form-control border-start-0 ps-0" placeholder="Search permissions...">
        </div>
    </div>

    <div class="row g-3" id="permsContainer">
        @foreach($groupedPermissions as $groupName => $permissions)
            <div class="col-12 col-lg-6 perm-group-wrapper">
                <div class="perm-group-card h-100 shadow-sm">
                    <div class="perm-group-header d-flex justify-content-between align-items-center">
                        <div class="fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-folder2-open text-primary"></i>
                            <span class="group-title">{{ $groupName }}</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border small">{{ count($permissions) }}</span>
                        </div>
                        @if(strtolower($role->name) !== 'owner')
                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 toggle-group-btn" data-group="{{ Str::slug($groupName) }}">
                                Toggle All
                            </button>
                        @endif
                    </div>
                    <div class="p-3">
                        <div class="row g-2">
                            @foreach($permissions as $permKey => $permLabel)
                                @php
                                    $isChecked = in_array($permKey, $rolePermissions) || strtolower($role->name) === 'owner';
                                @endphp
                                <div class="col-12 perm-item-col" data-perm-name="{{ strtolower($permKey . ' ' . $permLabel) }}">
                                    <div class="form-check perm-item d-flex align-items-center m-0">
                                        <input class="form-check-input perm-checkbox group-{{ Str::slug($groupName) }} me-2" 
                                               type="checkbox" 
                                               name="permissions[]" 
                                               value="{{ $permKey }}" 
                                               id="perm_{{ Str::slug($permKey) }}"
                                               {{ $isChecked ? 'checked' : '' }}
                                               {{ strtolower($role->name) === 'owner' ? 'disabled' : '' }}>
                                        <label class="form-check-label perm-checkbox-label flex-grow-1" for="perm_{{ Str::slug($permKey) }}">
                                            <span class="fw-semibold text-dark">{{ $permLabel }}</span>
                                            <span class="d-block small text-muted font-monospace">{{ $permKey }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card-custom p-3 mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary rounded-3 px-4">
            Cancel
        </a>
        @if(strtolower($role->name) !== 'owner')
            <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold shadow-sm">
                <i class="bi bi-check-circle me-1"></i> Update Permissions
            </button>
        @else
            <button type="submit" class="btn btn-secondary rounded-3 px-4" disabled>
                Permissions Locked for Owner
            </button>
        @endif
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAllGlobal = document.getElementById('selectAllGlobal');
        const deselectAllGlobal = document.getElementById('deselectAllGlobal');

        if (selectAllGlobal && deselectAllGlobal) {
            selectAllGlobal.addEventListener('click', function () {
                document.querySelectorAll('.perm-checkbox:not([disabled])').forEach(cb => cb.checked = true);
            });
            deselectAllGlobal.addEventListener('click', function () {
                document.querySelectorAll('.perm-checkbox:not([disabled])').forEach(cb => cb.checked = false);
            });
        }

        // Group Toggle
        document.querySelectorAll('.toggle-group-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const groupSlug = this.getAttribute('data-group');
                const checkboxes = document.querySelectorAll('.group-' + groupSlug + ':not([disabled])');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !allChecked);
            });
        });

        // Filter permissions live
        const filterInput = document.getElementById('filterPerms');
        filterInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('.perm-item-col').forEach(col => {
                const text = col.getAttribute('data-perm-name');
                if (!query || text.includes(query)) {
                    col.style.display = '';
                } else {
                    col.style.display = 'none';
                }
            });

            // Hide empty group wrappers
            document.querySelectorAll('.perm-group-wrapper').forEach(wrapper => {
                const visibleItems = wrapper.querySelectorAll('.perm-item-col:not([style*="display: none"])');
                wrapper.style.display = visibleItems.length === 0 ? 'none' : '';
            });
        });
    });
</script>
@endpush
