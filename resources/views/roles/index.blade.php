@extends('layouts.app')

@section('title', 'Role Management - Caltex Rao Filling Station')
@section('page_title', 'Roles & Access Control')

@section('content')
<style>
    :root {
        --role-primary: #6366f1;
        --role-success: #22c55e;
        --role-warning: #f59e0b;
        --role-danger: #ef4444;
        --role-bg: #f8fafc;
        --role-card: #ffffff;
        --role-border: #e2e8f0;
        --role-text: #1e293b;
        --role-muted: #64748b;
    }

    .roles-page-wrapper {
        padding: 6px 0 24px 0;
    }

    .page-header {
        margin-bottom: 24px;
    }

    .page-title {
        font-size: 1.65rem;
        font-weight: 700;
        color: var(--role-text);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .page-title i {
        color: var(--role-primary);
    }

    .page-subtitle {
        color: var(--role-muted);
        font-size: 0.9rem;
        margin-top: 4px;
        margin-bottom: 0;
    }

    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: var(--role-card);
        border-radius: 14px;
        padding: 18px 20px;
        border: 1px solid var(--role-border);
        transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }

    .stat-card .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 12px;
    }

    .stat-card.primary .stat-icon {
        background: #eef2ff;
        color: var(--role-primary);
    }

    .stat-card.success .stat-icon {
        background: #dcfce7;
        color: var(--role-success);
    }

    .stat-card.warning .stat-icon {
        background: #fef3c7;
        color: var(--role-warning);
    }

    .stat-card.danger .stat-icon {
        background: #fee2e2;
        color: var(--role-danger);
    }

    .stat-card .stat-value {
        font-size: 1.65rem;
        font-weight: 700;
        color: var(--role-text);
        line-height: 1.2;
    }

    .stat-card .stat-label {
        font-size: 0.78rem;
        color: var(--role-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 4px;
        font-weight: 600;
    }

    /* Roles Card Container */
    .roles-card {
        background: var(--role-card);
        border-radius: 16px;
        border: 1px solid var(--role-border);
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    }

    .roles-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--role-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .roles-header-left {
        display: flex;
        align-items: center;
        gap: 14px;
        flex: 1;
    }

    .search-box {
        position: relative;
        max-width: 340px;
        flex: 1;
    }

    .search-box input {
        width: 100%;
        padding: 9px 16px 9px 40px;
        border: 1px solid var(--role-border);
        border-radius: 10px;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .search-box input:focus {
        outline: none;
        border-color: var(--role-primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
    }

    .search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--role-muted);
    }

    .btn-create-role {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        border: none;
        padding: 9px 18px;
        border-radius: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        box-shadow: 0 2px 6px rgba(99, 102, 241, 0.25);
    }

    .btn-create-role:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        color: white;
    }

    /* Role Cards */
    .roles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 20px;
        padding: 24px;
    }

    .role-card {
        background: var(--role-card);
        border: 1px solid var(--role-border);
        border-radius: 14px;
        padding: 20px;
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
    }

    .role-card:hover {
        border-color: var(--role-primary);
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.12);
        transform: translateY(-2px);
    }

    .role-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .role-avatar {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: 700;
        color: white;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
    }

    .role-info {
        flex: 1;
        margin-left: 14px;
    }

    .role-name {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--role-text);
        margin: 0;
        text-transform: capitalize;
    }

    .role-meta {
        font-size: 0.8rem;
        color: var(--role-muted);
        margin-top: 3px;
    }

    .role-actions {
        display: flex;
        gap: 6px;
    }

    .role-actions .btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        transition: all 0.2s;
    }

    .role-actions .btn-edit-perm {
        background: #eef2ff;
        color: var(--role-primary);
        border: none;
    }

    .role-actions .btn-edit-perm:hover {
        background: var(--role-primary);
        color: white;
    }

    .role-actions .btn-edit {
        background: #fef3c7;
        color: var(--role-warning);
        border: none;
    }

    .role-actions .btn-edit:hover {
        background: var(--role-warning);
        color: white;
    }

    .role-actions .btn-delete {
        background: #fee2e2;
        color: var(--role-danger);
        border: none;
    }

    .role-actions .btn-delete:hover {
        background: var(--role-danger);
        color: white;
    }

    /* Permission Tags */
    .permissions-section {
        margin-top: 14px;
    }

    .permissions-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--role-muted);
        margin-bottom: 8px;
        font-weight: 600;
    }

    .permission-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .permission-tag {
        background: #f1f5f9;
        color: var(--role-text);
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 500;
        border: 1px solid var(--role-border);
    }

    .permission-tag.more {
        background: var(--role-primary);
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.2s;
    }

    .permission-tag.more:hover {
        background: #4f46e5;
    }

    /* Permission count badge */
    .perm-count-badge {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
    }

    /* Modal Improvements */
    .modal-content {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .modal-header.gradient {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        padding: 20px 26px;
        border: none;
    }

    .modal-header.gradient .modal-title {
        font-weight: 700;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-header.gradient .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.85;
    }

    .modal-header.gradient .btn-close:hover {
        opacity: 1;
    }

    .modal-body {
        padding: 26px;
        background: #ffffff;
    }

    /* Form Styling */
    .form-group-modern {
        margin-bottom: 20px;
    }

    .form-group-modern .form-label {
        font-weight: 600;
        color: var(--role-text);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
    }

    .form-group-modern .form-label i {
        color: var(--role-primary);
        font-size: 0.9rem;
    }

    .form-group-modern .form-control {
        border: 2px solid var(--role-border);
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        background: #f8fafc;
    }

    .form-group-modern .form-control:focus {
        border-color: var(--role-primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        background: #ffffff;
    }

    .form-group-modern .form-hint {
        font-size: 0.82rem;
        color: var(--role-muted);
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .modal-footer-modern {
        padding: 18px 26px;
        background: #f8fafc;
        border-top: 1px solid var(--role-border);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .btn-cancel {
        background: #f1f5f9;
        color: var(--role-text);
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-cancel:hover {
        background: #e2e8f0;
        color: var(--role-text);
    }

    .btn-save {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        box-shadow: 0 2px 6px rgba(99, 102, 241, 0.25);
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
        color: white;
    }

    /* Permission Controls Bar */
    .perm-controls-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
        padding: 14px 18px;
        background: white;
        border-radius: 14px;
        border: 1px solid var(--role-border);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .perm-search-wrapper {
        flex: 1;
        max-width: 380px;
    }

    .perm-search-box {
        position: relative;
        width: 100%;
    }

    .perm-search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--role-muted);
        font-size: 0.9rem;
    }

    .perm-search-box input {
        width: 100%;
        padding: 10px 16px 10px 38px;
        border: 2px solid var(--role-border);
        border-radius: 10px;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        background: #f8fafc;
    }

    .perm-search-box input:focus {
        outline: none;
        border-color: var(--role-primary);
        background: white;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .perm-actions-wrapper {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .select-all-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 8px 14px;
        background: #f1f5f9;
        border-radius: 10px;
        transition: all 0.2s;
        margin: 0;
    }

    .select-all-toggle:hover {
        background: #e2e8f0;
    }

    .select-all-toggle input[type="checkbox"] {
        width: 17px;
        height: 17px;
        accent-color: var(--role-primary);
        cursor: pointer;
    }

    .select-all-toggle .toggle-label {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--role-text);
    }

    .selected-badge {
        display: flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 600;
    }

    /* Permission Group */
    .permission-group {
        background: white;
        border-radius: 12px;
        border: 1px solid var(--role-border);
        margin-bottom: 14px;
        overflow: hidden;
    }

    .permission-group-header {
        background: #f8fafc;
        padding: 12px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--role-border);
    }

    .permission-group-header .module-name {
        font-weight: 600;
        color: var(--role-text);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
    }

    .permission-group-header .module-name .module-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
    }

    .permission-group-body {
        padding: 14px;
    }

    .perm-item {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.15s;
        border: 1px solid transparent;
        cursor: pointer;
    }

    .perm-item:hover {
        background: #f1f5f9;
    }

    .perm-item.checked {
        background: #eef2ff;
        border-color: #c7d2fe;
    }

    .perm-item input[type="checkbox"] {
        width: 17px;
        height: 17px;
        margin-right: 10px;
        accent-color: var(--role-primary);
        cursor: pointer;
    }

    .perm-item label {
        font-size: 0.88rem;
        color: var(--role-text);
        cursor: pointer;
        flex: 1;
        margin: 0;
        font-weight: 500;
    }

    .perm-action-badge {
        font-size: 0.68rem;
        padding: 2px 7px;
        border-radius: 4px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .perm-action-badge.view { background: #dbeafe; color: #1e40af; }
    .perm-action-badge.create { background: #dcfce7; color: #166534; }
    .perm-action-badge.edit { background: #fef3c7; color: #92400e; }
    .perm-action-badge.delete { background: #fee2e2; color: #991b1b; }
    .perm-action-badge.unlock { background: #ffedd5; color: #c2410c; }
    .perm-action-badge.reopen { background: #f3e8ff; color: #7c3aed; }
    .perm-action-badge.approve { background: #f3e8ff; color: #7c3aed; }
    .perm-action-badge.other { background: #f1f5f9; color: #64748b; }

    /* Responsive */
    @media (max-width: 992px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .roles-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 576px) {
        .stats-row {
            grid-template-columns: 1fr;
        }
        .roles-header {
            flex-direction: column;
            align-items: stretch;
        }
        .search-box {
            max-width: 100%;
        }
    }
</style>

<div class="roles-page-wrapper">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title"><i class="fa fa-user-shield"></i> Role Management</h1>
            <p class="page-subtitle">Manage user roles and their security permissions</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary rounded-3 fw-semibold px-3 shadow-sm">
                <i class="fa fa-users me-1"></i> Employees & Users
            </a>
            <button type="button" class="btn btn-create-role" data-bs-toggle="modal" data-bs-target="#roleModal" id="createRoleBtn">
                <i class="fa fa-plus"></i> Create Role
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-card primary">
            <div class="stat-icon"><i class="fa fa-user-shield"></i></div>
            <div class="stat-value">{{ $roles->count() }}</div>
            <div class="stat-label">Total Roles</div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="fa fa-key"></i></div>
            <div class="stat-value">{{ $allPermissions->count() }}</div>
            <div class="stat-label">Total Permissions</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fa fa-users"></i></div>
            <div class="stat-value">{{ \App\Models\User::count() }}</div>
            <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-icon"><i class="fa fa-layer-group"></i></div>
            <div class="stat-value">
                {{ collect($allPermissions)->map(fn($p) => explode('.', $p->name)[0])->unique()->count() }}
            </div>
            <div class="stat-label">System Modules</div>
        </div>
    </div>

    <!-- Roles Card -->
    <div class="roles-card">
        <div class="roles-header">
            <div class="roles-header-left">
                <div class="search-box">
                    <i class="fa fa-search"></i>
                    <input type="search" id="roleSearch" placeholder="Search roles or permissions...">
                </div>
                <button class="btn btn-outline-secondary btn-sm rounded-3 px-2.5 py-1.5" id="refreshBtn" title="Refresh">
                    <i class="fa fa-sync"></i>
                </button>
            </div>
        </div>

        <div class="roles-grid" id="rolesGrid">
            @forelse($roles as $role)
                @php
                    $isCore = in_array(strtolower($role->name), ['owner', 'incharge', 'cashier']);
                    $assignedCount = $role->permissions->count();
                @endphp
                <div class="role-card" data-role-id="{{ $role->id }}"
                    data-name="{{ strtolower($role->name) }}"
                    data-permissions="{{ json_encode($role->getPermissionNames()) }}">
                    <div class="role-card-header">
                        <div class="d-flex align-items-center">
                            <div class="role-avatar">{{ strtoupper(substr($role->name, 0, 2)) }}</div>
                            <div class="role-info">
                                <h4 class="role-name">{{ ucfirst($role->name) }}</h4>
                                <div class="role-meta">
                                    ID: {{ $role->id }} • 
                                    @if($isCore)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle py-0.5 px-1.5" style="font-size: 0.68rem;">System Core</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border py-0.5 px-1.5" style="font-size: 0.68rem;">Custom</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="role-actions">
                            <button class="btn btn-edit-perm edit-permission-btn" title="Edit Permissions">
                                <i class="fa fa-key"></i>
                            </button>
                            <button class="btn btn-edit edit-role-btn" title="Edit Role">
                                <i class="fa fa-pen"></i>
                            </button>
                            @if(strtolower($role->name) !== 'owner')
                                <button class="btn btn-delete delete-role-btn" data-id="{{ $role->id }}" title="Delete Role">
                                    <i class="fa fa-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="permissions-section">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="permissions-label m-0">Permissions</div>
                            <span class="perm-count-badge">{{ $assignedCount }} permissions</span>
                        </div>
                        <div class="permission-tags">
                            @foreach ($role->getPermissionNames()->take(5) as $perm)
                                <span class="permission-tag">{{ $perm }}</span>
                            @endforeach
                            @if ($role->getPermissionNames()->count() > 5)
                                <span class="permission-tag more edit-permission-btn" title="Click to view all">+{{ $role->getPermissionNames()->count() - 5 }} more</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-4 text-center text-muted" style="grid-column: 1/-1;">
                    <i class="fa fa-user-shield fs-1 text-secondary opacity-50 mb-2"></i>
                    <p class="m-0">No roles found. Create your first role!</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Create/Edit Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header gradient">
                <h5 class="modal-title" id="roleModalTitle">
                    <i class="fa fa-user-shield"></i>
                    <span>Create New Role</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="roleForm" action="{{ route('roles.store') }}" method="POST">
                @csrf
                <input type="hidden" name="edit_id" id="roleEditId">

                <div class="modal-body">
                    <div class="form-group-modern">
                        <label class="form-label">
                            <i class="fa fa-tag"></i>
                            Role Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" id="roleName" class="form-control"
                            placeholder="Enter role name (e.g., Manager, Accountant)" required autocomplete="off">
                        <div class="form-hint">
                            <i class="fa fa-info-circle"></i>
                            Choose a descriptive name that reflects the role's purpose
                        </div>
                    </div>
                </div>

                <div class="modal-footer-modern">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-save" id="saveRoleBtn">
                        <i class="fa fa-check me-1"></i>
                        <span>Save Role</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Permissions Modal -->
<div class="modal fade" id="permissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header gradient">
                <div class="d-flex flex-column">
                    <h5 class="modal-title">
                        <i class="fa fa-key"></i>
                        <span>Edit Permissions</span>
                    </h5>
                    <small class="text-white-50 mt-1" id="permRoleName">Role: Owner</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto; background: #f8fafc;">
                <form id="permissionForm" action="{{ route('roles.update.permission') }}" method="POST">
                    @csrf
                    <input type="hidden" name="edit_id" id="permEditId">
                    <input type="hidden" name="name" id="permRoleNameInput">

                    <!-- Search and Controls -->
                    <div class="perm-controls-bar">
                        <div class="perm-search-wrapper">
                            <div class="perm-search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="permSearch" placeholder="Search permissions...">
                            </div>
                        </div>
                        <div class="perm-actions-wrapper">
                            <label class="select-all-toggle">
                                <input type="checkbox" id="selectAllPerms">
                                <span class="toggle-label">Select All</span>
                            </label>
                            <span class="selected-badge" id="selectedCount">
                                <i class="fa fa-check-circle"></i>
                                <span>0 selected</span>
                            </span>
                        </div>
                    </div>

                    <!-- Permission Groups Container -->
                    <div id="permissionGroupsContainer">
                        <!-- Populated dynamically by JS -->
                    </div>
                </form>
            </div>
            <div class="modal-footer-modern">
                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Cancel
                </button>
                <button type="submit" form="permissionForm" class="btn btn-save" id="savePermsBtn">
                    <i class="fa fa-check me-1"></i>
                    <span>Save Permissions</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const allPermissions = @json($allPermissions);

    $(document).ready(function() {
        // Create Role Button
        $('#createRoleBtn').click(function() {
            $('#roleEditId').val('');
            $('#roleName').val('');
            $('#roleModalTitle').html('<i class="fa fa-user-shield"></i><span>Create New Role</span>');
        });

        // Edit Role Button
        $(document).on('click', '.edit-role-btn', function() {
            var card = $(this).closest('.role-card');
            var id = card.data('role-id');
            var name = card.find('.role-name').text().trim();

            $('#roleEditId').val(id);
            $('#roleName').val(name);
            $('#roleModalTitle').html('<i class="fa fa-pen"></i><span>Edit Role</span>');
            $('#roleModal').modal('show');
        });

        // Role Form Submit
        $('#roleForm').submit(function(e) {
            e.preventDefault();
            var $submitBtn = $('#saveRoleBtn');
            var origText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success',
                            text: response.message || 'Role saved successfully',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', response.message || 'Something went wrong', 'error');
                        $submitBtn.prop('disabled', false).html(origText);
                    }
                },
                error: function(xhr) {
                    var msg = 'Something went wrong';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', msg, 'error');
                    $submitBtn.prop('disabled', false).html(origText);
                }
            });
        });

        // Delete Role
        $(document).on('click', '.delete-role-btn', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Delete Role?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '{{ url('roles/delete') }}/' + id;
                }
            });
        });

        // Edit Permissions Button
        $(document).on('click', '.edit-permission-btn', function(e) {
            e.stopPropagation();
            var card = $(this).closest('.role-card');
            var id = card.data('role-id');
            var name = card.find('.role-name').text().trim();
            var assignedPerms = card.data('permissions') || [];

            $('#permEditId').val(id);
            $('#permRoleName').text('Role: ' + name);
            $('#permRoleNameInput').val(name);

            buildPermissionGroups(assignedPerms);
            $('#permissionModal').modal('show');
        });

        // Build Permission Groups UI
        function buildPermissionGroups(assignedPerms) {
            var container = $('#permissionGroupsContainer');
            container.empty();

            var groups = {};
            var moduleIcons = {
                'hr': 'fa-users',
                'users': 'fa-user',
                'roles': 'fa-user-shield',
                'shift-closings': 'fa-gas-pump',
                'day-closings': 'fa-calendar-check',
                'purchases': 'fa-truck',
                'transactions': 'fa-receipt',
                'products': 'fa-boxes-stacked',
                'parties': 'fa-address-book',
                'accounts': 'fa-building-columns',
                'cashiers': 'fa-cash-register',
                'expense-categories': 'fa-tags',
                'ledgers': 'fa-book',
                'reports': 'fa-chart-pie',
                'audit-logs': 'fa-clipboard-list',
                'dashboard': 'fa-gauge-high'
            };

            allPermissions.forEach(function(p) {
                var parts = p.name.split('.');
                var module = 'General';
                if (parts.length > 1) {
                    parts.pop();
                    module = parts.join('.');
                } else {
                    module = parts[0];
                }

                if (!groups[module]) groups[module] = [];
                groups[module].push(p);
            });

            Object.keys(groups).sort().forEach(function(module) {
                var perms = groups[module];

                var order = {
                    'view': 1,
                    'create': 2,
                    'edit': 3,
                    'delete': 4,
                    'unlock': 5,
                    'reopen': 6,
                    'approve': 7
                };

                perms.sort(function(a, b) {
                    var actionA = a.name.split('.').pop();
                    var actionB = b.name.split('.').pop();
                    var valA = order[actionA] || 99;
                    var valB = order[actionB] || 99;
                    return valA - valB;
                });

                var mainCategory = module.split('.')[0];
                var icon = moduleIcons[module] || moduleIcons[mainCategory] || 'fa-folder-open';

                var title = module.replace(/\./g, ' ')
                    .replace(/\b\w/g, l => l.toUpperCase())
                    .replace('Hr ', 'HR ');

                var checkedCount = perms.filter(p => assignedPerms.includes(p.name)).length;

                var html = `
                    <div class="permission-group" data-module="${module}">
                        <div class="permission-group-header">
                            <div class="module-name">
                                <span class="module-icon"><i class="fa ${icon}"></i></span>
                                <span>${title}</span>
                                <span class="badge bg-secondary opacity-75">${perms.length}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">${checkedCount} active</span>
                                <div class="form-check mb-0">
                                    <input class="form-check-input module-select" type="checkbox" data-module="${module}">
                                    <label class="form-check-label small fw-semibold">All</label>
                                </div>
                            </div>
                        </div>
                        <div class="permission-group-body">
                            <div class="row g-2">
                `;

                perms.forEach(function(p) {
                    var checked = assignedPerms.includes(p.name);
                    var action = p.name.split('.').pop();
                    var knownActions = ['view', 'create', 'edit', 'delete', 'unlock', 'reopen', 'approve'];
                    var actionClass = knownActions.includes(action) ? action : 'other';

                    html += `
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3 perm-item-wrapper" data-name="${p.name.toLowerCase()}">
                            <div class="perm-item ${checked ? 'checked' : ''}" onclick="window.togglePerm('${p.id}')">
                                <input type="checkbox" name="permissions[]" value="${p.name}" id="perm-${p.id}" ${checked ? 'checked' : ''} onclick="event.stopPropagation()">
                                <label for="perm-${p.id}" onclick="event.stopPropagation(); window.togglePerm('${p.id}')">${p.name}</label>
                                <span class="perm-action-badge ${actionClass}">${action}</span>
                            </div>
                        </div>
                    `;
                });

                html += `
                            </div>
                        </div>
                    </div>
                `;

                container.append(html);
            });

            window.togglePerm = function(id) {
                var checkbox = $('#perm-' + id);
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            };

            updateSelectedCount();
            updateModuleSelectStates();
        }

        // Update selected count
        function updateSelectedCount() {
            var count = $('#permissionGroupsContainer input[type="checkbox"][name="permissions[]"]:checked').length;
            $('#selectedCount span').text(count + ' selected');
        }

        // Update module select states
        function updateModuleSelectStates() {
            $('.permission-group').each(function() {
                var total = $(this).find('input[name="permissions[]"]').length;
                var checked = $(this).find('input[name="permissions[]"]:checked').length;
                var $select = $(this).find('.module-select');

                if (checked === 0) {
                    $select.prop('checked', false).prop('indeterminate', false);
                } else if (checked === total) {
                    $select.prop('checked', true).prop('indeterminate', false);
                } else {
                    $select.prop('checked', false).prop('indeterminate', true);
                }
            });
        }

        // Checkbox change handler
        $(document).on('change', 'input[name="permissions[]"]', function() {
            $(this).closest('.perm-item').toggleClass('checked', $(this).is(':checked'));
            updateSelectedCount();
            updateModuleSelectStates();
        });

        // Module select all
        $(document).on('change', '.module-select', function() {
            var checked = $(this).is(':checked');
            $(this).closest('.permission-group').find('input[name="permissions[]"]').prop('checked', checked).each(function() {
                $(this).closest('.perm-item').toggleClass('checked', checked);
            });
            updateSelectedCount();
        });

        // Select all permissions globally
        $('#selectAllPerms').change(function() {
            var checked = $(this).is(':checked');
            $('#permissionGroupsContainer input[name="permissions[]"]').prop('checked', checked).each(function() {
                $(this).closest('.perm-item').toggleClass('checked', checked);
            });
            updateSelectedCount();
            updateModuleSelectStates();
        });

        // Search in permissions modal
        $('#permSearch').on('input', function() {
            var q = $(this).val().toLowerCase().trim();
            $('.perm-item-wrapper').each(function() {
                var name = $(this).data('name') || '';
                $(this).toggle(name.indexOf(q) !== -1);
            });
            $('.permission-group').each(function() {
                var hasVisible = $(this).find('.perm-item-wrapper:visible').length > 0;
                $(this).toggle(hasVisible);
            });
        });

        // Search roles cards on main screen
        $('#roleSearch').on('input', function() {
            var q = $(this).val().toLowerCase().trim();
            $('.role-card').each(function() {
                var name = $(this).data('name') || '';
                var perms = JSON.stringify($(this).data('permissions') || []).toLowerCase();
                $(this).toggle(name.indexOf(q) !== -1 || perms.indexOf(q) !== -1);
            });
        });

        // Refresh button
        $('#refreshBtn').click(function() {
            location.reload();
        });

        // Permission Form Submit via AJAX
        $('#permissionForm').submit(function(e) {
            e.preventDefault();
            var $btn = $('#savePermsBtn');
            var origText = $btn.html();
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Success',
                        text: response.message || 'Permissions updated successfully',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                },
                error: function(xhr) {
                    var msg = 'Something went wrong';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', msg, 'error');
                    $btn.prop('disabled', false).html(origText);
                }
            });
        });
    });
</script>

@if (session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: "{{ session('success') }}",
            timer: 2000,
            showConfirmButton: false
        });
    </script>
@endif

@if (session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: "{{ session('error') }}",
        });
    </script>
@endif
@endpush
