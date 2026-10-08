@extends('layouts.app')

@section('title', 'Audit Trail & Activity Log - FinanceDesk')
@section('page_title', 'Audit Trail & System Logs')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-shield-check me-1 text-primary"></i> Security & Audit Trail
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-1 gap-sm-2 w-100 justify-content-start justify-content-sm-end">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Log Sheet
        </button>
        <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
        </a>
    </div>
@endsection

@push('styles')
<style>
    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .kpi-title {
        font-size: 0.7rem;
        letter-spacing: 0.4px;
    }
    .kpi-amount {
        font-size: clamp(1.05rem, 3.5vw, 1.35rem);
        line-height: 1.25;
        word-break: break-word;
    }
    @media (max-width: 575.98px) {
        .card-custom {
            border-radius: 10px;
        }
    }
    /* =========================================================
       BANK STATEMENT PRINT & PDF STYLES
       ========================================================= */
    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm 12mm 10mm 12mm;
        }
        html, body {
            background: #ffffff !important;
            color: #0f172a !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            font-size: 8.8pt !important;
            line-height: 1.3 !important;
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Hide Web Navigation and UI elements */
        .vip-navbar, .sub-header, .no-print, .btn, .alert, footer, nav, header, .modal, .modal-backdrop, .btn-close, .dropdown-menu, .pagination {
            display: none !important;
        }

        /* Container Resets */
        main, .container-fluid, .row, .col-12, .col-xl-12 {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        #auditLogSheet, .card-custom {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            background: transparent !important;
        }

        /* Bank Statement Header */
        .bank-stmt-header {
            display: block !important;
            border-bottom: 2px solid #0f172a !important;
            padding-bottom: 8px !important;
            margin-bottom: 12px !important;
        }

        /* Force Desktop Table Display */
        .statement-desktop-table {
            display: block !important;
            width: 100% !important;
            overflow: visible !important;
        }
        .table-responsive {
            overflow: visible !important;
            display: block !important;
        }

        /* Statement Grid & Tables */
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        thead {
            display: table-header-group;
        }
        tfoot {
            display: table-footer-group;
        }

        .table th, .table td,
        .table-custom th, .table-custom td {
            border: 1px solid #475569 !important;
            padding: 4px 6px !important;
            font-size: 8.2pt !important;
            color: #0f172a !important;
        }

        .table thead th,
        .table-custom thead th {
            background-color: #f1f5f9 !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            color: #0f172a !important;
        }

        .badge {
            border: 1px solid #94a3b8 !important;
            background: transparent !important;
            color: #0f172a !important;
            font-size: 7.5pt !important;
            padding: 1px 3px !important;
        }

        /* Signatures block */
        .statement-signatures {
            page-break-inside: avoid;
            margin-top: 25px !important;
            padding-top: 15px !important;
            border-top: 1px solid #0f172a !important;
        }

        .statement-sig-line {
            border-top: 1px dashed #475569;
            width: 80%;
            margin: 0 auto;
            padding-top: 4px;
        }
    }
</style>
@endpush

@section('content')
<!-- KPI Header Cards -->
<div class="row g-2 g-md-3 mb-3 mb-md-4 d-print-none">
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 h-100 shadow-xs">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-muted small d-block kpi-title">Total System Logs</span>
                <div class="bg-primary-subtle text-primary rounded-3 p-1.5 fs-6">
                    <i class="bi bi-journal-text"></i>
                </div>
            </div>
            <h5 class="fw-bold text-dark font-monospace m-0 kpi-amount">{{ number_format($totalLogsCount) }}</h5>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 h-100 shadow-xs">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-muted small d-block kpi-title">Today's Activities</span>
                <div class="bg-success-subtle text-success rounded-3 p-1.5 fs-6">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
            <h5 class="fw-bold text-success font-monospace m-0 kpi-amount">{{ number_format($todayLogsCount) }}</h5>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 h-100 shadow-xs">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-muted small d-block kpi-title">Audited Modules</span>
                <div class="bg-info-subtle text-info rounded-3 p-1.5 fs-6">
                    <i class="bi bi-layers"></i>
                </div>
            </div>
            <h5 class="fw-bold text-info font-monospace m-0 kpi-amount">{{ $modules->count() }} Modules</h5>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 h-100 shadow-xs">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-muted small d-block kpi-title">Audit Status</span>
                <div class="bg-light text-secondary rounded-3 p-1.5 fs-6 border">
                    <i class="bi bi-shield-lock"></i>
                </div>
            </div>
            <h5 class="fw-bold text-success m-0" style="font-size: clamp(0.85rem, 3vw, 0.95rem);">
                <i class="bi bi-check-circle-fill me-1"></i>Active Trail
            </h5>
        </div>
    </div>
</div>

<!-- Search & Filter Bar (No-Print) -->
<div class="card-custom p-3 mb-3 mb-md-4 no-print">
    <form method="GET" action="{{ route('audit-logs.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">Search Details</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control form-control-sm py-2" placeholder="Search keywords..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">User</label>
            <select name="user_id" class="form-select form-select-sm py-2">
                <option value="">All Users</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }} ({{ ucfirst($u->role) }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">Module</label>
            <select name="module" class="form-select form-select-sm py-2">
                <option value="">All Modules</option>
                @foreach($modules as $m)
                    <option value="{{ $m }}" {{ request('module') == $m ? 'selected' : '' }}>
                        {{ $m }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label text-muted small fw-semibold mb-1">Action</label>
            <select name="action" class="form-select form-select-sm py-2">
                <option value="">All Actions</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}" {{ request('action') == $a ? 'selected' : '' }}>
                        {{ ucfirst($a) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm rounded-3 flex-grow-1 fw-semibold py-2">
                <i class="bi bi-filter me-1"></i> Filter
            </button>
            <a href="{{ route('audit-logs.index') }}" class="btn btn-light btn-sm rounded-3 border px-3 d-flex align-items-center justify-content-center" title="Reset Filters">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Logs Data Card -->
<div class="card-custom overflow-hidden" id="auditLogSheet">
    <!-- OFFICIAL BANK STATEMENT PRINT HEADER (Visible on Print) -->
    <div class="bank-stmt-header d-none d-print-block">
        <div class="d-flex justify-content-between align-items-start pb-2 border-bottom border-dark">
            <div>
                <h3 class="fw-bold m-0 text-uppercase tracking-tight" style="color: #0f172a; font-size: 16pt;">FINANCEDESK ENTERPRISE</h3>
                <div class="small fw-semibold text-secondary">SYSTEM AUDIT TRAIL & COMPLIANCE DIVISION</div>
                <div class="small text-muted" style="font-size: 8pt;">Proware Technologies &bull; Immutable Audit Logs & Security Operations</div>
            </div>
            <div class="text-end">
                <div class="badge bg-dark text-white text-uppercase px-2 py-1 mb-1" style="font-size: 8.5pt;">Security Register</div>
                <div class="fw-bold" style="font-size: 11pt; color: #0f172a;">SYSTEM AUDIT TRAIL LOG REGISTER</div>
                <div class="small text-muted" style="font-size: 8pt;">Printed Date: {{ now()->format('d M, Y h:i A') }}</div>
            </div>
        </div>

        <div class="row pt-2 pb-1" style="font-size: 8.5pt;">
            <div class="col-7">
                <table class="w-100 border-0" style="border: none !important;">
                    <tr style="border: none !important;"><td style="border: none !important; padding: 1px 0; width: 120px;" class="text-muted fw-semibold">Filtered Module:</td><td style="border: none !important; padding: 1px 0;" class="fw-bold text-dark">{{ request('module') ?: 'All System Modules' }}</td></tr>
                    <tr style="border: none !important;"><td style="border: none !important; padding: 1px 0;" class="text-muted fw-semibold">Operator Scope:</td><td style="border: none !important; padding: 1px 0;" class="fw-semibold">{{ request('user_id') ? ($users->firstWhere('id', request('user_id'))->name ?? 'User') : 'All Active Users' }}</td></tr>
                    <tr style="border: none !important;"><td style="border: none !important; padding: 1px 0;" class="text-muted fw-semibold">Action Filter:</td><td style="border: none !important; padding: 1px 0;" class="fw-semibold">{{ request('action') ? ucfirst(request('action')) : 'All Recorded Actions' }}</td></tr>
                </table>
            </div>
            <div class="col-5 text-end">
                <table class="w-100 border-0 ms-auto" style="border: none !important; max-width: 280px;">
                    <tr style="border: none !important;"><td style="border: none !important; padding: 1px 0;" class="text-muted fw-semibold text-end pe-2">Total System Records:</td><td style="border: none !important; padding: 1px 0;" class="fw-bold text-dark text-end font-monospace">{{ number_format($totalLogsCount) }}</td></tr>
                    <tr style="border: none !important;"><td style="border: none !important; padding: 1px 0;" class="text-muted fw-semibold text-end pe-2">Current Query Records:</td><td style="border: none !important; padding: 1px 0;" class="fw-bold text-primary text-end font-monospace">{{ $logs->total() }}</td></tr>
                    <tr style="border: none !important;"><td style="border: none !important; padding: 1px 0;" class="text-muted fw-semibold text-end pe-2">Register Page:</td><td style="border: none !important; padding: 1px 0;" class="fw-bold text-dark text-end font-monospace">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 d-print-none">
        <h6 class="fw-bold text-dark m-0">
            <i class="bi bi-list-check text-primary me-2"></i>Audit Trail Records ({{ $logs->total() }})
        </h6>
        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.72rem;">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>
    </div>

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block statement-desktop-table table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 140px;">Timestamp</th>
                    <th style="width: 160px;">Operator / User</th>
                    <th style="width: 110px;">Action</th>
                    <th style="width: 140px;">Module</th>
                    <th>Activity Details</th>
                    <th style="width: 120px;" class="text-end">IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="font-monospace text-muted small">
                            {{ $log->created_at->format('d-m-Y') }}
                            <span class="d-block text-dark fw-semibold" style="font-size: 0.72rem;">{{ $log->created_at->format('h:i:s A') }}</span>
                        </td>
                        <td>
                            @if($log->user)
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 26px; height: 26px; font-size: 0.72rem;">
                                        {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-dark small text-truncate">{{ $log->user->name }}</div>
                                        <span class="text-muted d-block" style="font-size: 0.7rem;">{{ ucfirst($log->user->role) }}</span>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted fst-italic small">System / Deleted User</span>
                            @endif
                        </td>
                        <td>{!! $log->action_badge !!}</td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1 small">
                                {{ $log->module }}
                            </span>
                        </td>
                        <td>
                            <div class="text-dark small text-break">{{ $log->details }}</div>
                        </td>
                        <td class="text-end font-monospace text-muted small">
                            {{ $log->ip_address ?? '127.0.0.1' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-shield-check text-muted display-4 d-block mb-2"></i>
                            <h6 class="fw-bold text-dark">No Audit Logs Found</h6>
                            <p class="small text-secondary m-0">No system activities match your filter criteria or no actions recorded yet.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll) -->
    <div class="d-block d-md-none d-print-none p-2 p-sm-3">
        <div class="d-flex flex-column gap-2">
            @forelse($logs as $log)
                <div class="p-3 rounded-3 border bg-white shadow-xs">
                    <!-- Top Row: User & Action Badge -->
                    <div class="d-flex align-items-center justify-content-between gap-1 mb-2">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            @if($log->user)
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.72rem;">
                                    {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="fw-bold text-dark small text-truncate">{{ $log->user->name }}</div>
                                    <span class="text-muted" style="font-size: 0.68rem;">{{ ucfirst($log->user->role) }}</span>
                                </div>
                            @else
                                <span class="text-muted fst-italic small">System Activity</span>
                            @endif
                        </div>
                        <div class="flex-shrink-0">
                            {!! $log->action_badge !!}
                        </div>
                    </div>

                    <!-- Meta Strip: Timestamp, Module & IP -->
                    <div class="d-flex align-items-center justify-content-between gap-1 py-1.5 px-2 rounded-2 bg-light border mb-2" style="font-size: 0.72rem;">
                        <span class="text-muted font-monospace">
                            <i class="bi bi-clock me-1"></i>{{ $log->created_at->format('d M, h:i A') }}
                        </span>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-secondary-subtle text-secondary border px-1.5 py-0.5" style="font-size: 0.68rem;">{{ $log->module }}</span>
                            <span class="font-monospace text-muted">{{ $log->ip_address ?? '127.0.0.1' }}</span>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="text-dark small text-break" style="font-size: 0.78rem;">
                        {{ $log->details }}
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-shield-check text-muted fs-3 d-block mb-1"></i>
                    No audit logs found matching your filter criteria.
                </div>
            @endforelse
        </div>
    </div>

    @if($logs->hasPages())
        <div class="p-3 border-top d-flex justify-content-center overflow-x-auto d-print-none">
            {{ $logs->links() }}
        </div>
    @endif

    <!-- OFFICIAL STATEMENT SIGNATURES (Bank Statement Format) -->
    <div class="statement-signatures d-none d-print-block mt-4">
        <div class="row text-center">
            <div class="col-4">
                <div class="statement-sig-line"></div>
                <div class="fw-bold small text-uppercase mt-1" style="font-size: 8pt;">System Auditor / IT Admin</div>
                <div class="text-muted" style="font-size: 7.5pt;">FinanceDesk Security</div>
            </div>
            <div class="col-4">
                <div class="statement-sig-line"></div>
                <div class="fw-bold small text-uppercase mt-1" style="font-size: 8pt;">Compliance Officer</div>
                <div class="text-muted" style="font-size: 7.5pt;">Verified & Logged</div>
            </div>
            <div class="col-4">
                <div class="statement-sig-line"></div>
                <div class="fw-bold small text-uppercase mt-1" style="font-size: 8pt;">Authorized Director</div>
                <div class="text-muted" style="font-size: 7.5pt;">Executive Oversight</div>
            </div>
        </div>
    </div>
</div>
@endsection
