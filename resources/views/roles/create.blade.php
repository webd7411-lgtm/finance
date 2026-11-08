@extends('layouts.app')

@section('title', 'Create New Role - Caltex Rao Filling Station')
@section('page_title', 'Create New System Role')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-shield-plus me-1 text-primary"></i> Role Definition
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
<form action="{{ route('roles.store') }}" method="POST">
    @csrf

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
                       placeholder="e.g. Accountant, Auditor, Shift Supervisor"
                       value="{{ old('name') }}" 
                       required 
                       autofocus>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Unique role identifier. This will be available when assigning roles to users.</small>
            </div>
            <div class="col-12 col-md-6 text-md-end pt-md-3">
                <div class="btn-group shadow-sm">
                    <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" id="selectAllGlobal">
                        <i class="bi bi-check-all me-1"></i> Select All Permissions
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold" id="deselectAllGlobal">
                        <i class="bi bi-x-lg me-1"></i> Deselect All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Grouped Permissions -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="bi bi-key text-primary"></i> Assign Permissions
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
                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 toggle-group-btn" data-group="{{ Str::slug($groupName) }}">
                            Select All
                        </button>
                    </div>
                    <div class="p-3">
                        <div class="row g-2">
                            @foreach($permissions as $permKey => $permLabel)
                                <div class="col-12 perm-item-col" data-perm-name="{{ strtolower($permKey . ' ' . $permLabel) }}">
                                    <div class="form-check perm-item d-flex align-items-center m-0">
                                        <input class="form-check-input perm-checkbox group-{{ Str::slug($groupName) }} me-2" 
                                               type="checkbox" 
                                               name="permissions[]" 
                                               value="{{ $permKey }}" 
                                               id="perm_{{ Str::slug($permKey) }}">
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
        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold shadow-sm">
            <i class="bi bi-check-circle me-1"></i> Save & Create Role
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Global Select/Deselect
        document.getElementById('selectAllGlobal').addEventListener('click', function () {
            document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = true);
        });
        document.getElementById('deselectAllGlobal').addEventListener('click', function () {
            document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = false);
        });

        // Group Toggle
        document.querySelectorAll('.toggle-group-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const groupSlug = this.getAttribute('data-group');
                const checkboxes = document.querySelectorAll('.group-' + groupSlug);
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !allChecked);
                this.textContent = allChecked ? 'Select All' : 'Deselect All';
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
