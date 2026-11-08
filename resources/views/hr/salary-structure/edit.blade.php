@extends('layouts.app')

@section('title', 'Salary Structure: ' . $employee->full_name . ' - Caltex Rao Filling Station')
@section('page_title', 'Salary Structure: ' . $employee->full_name)

@section('page_actions')
    <a href="{{ route('hr.salary-structure.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold shadow-sm text-nowrap">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
@endsection

@section('content')
    @include('hr.partials.hr-styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif !important;
        }

        body, body *, input, select, textarea, button, .form-control, .form-select, .btn, .card, .alert, .badge, table, th, td {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        }

        /* Preserve Icon Fonts */
        .fa, .fas, .fa-solid, .fa-regular, .fa-brands, [class^="fa-"], [class*=" fa-"] {
            font-family: "Font Awesome 6 Free", "FontAwesome" !important;
        }
        .fab, [class*="fa-brands"] {
            font-family: "Font Awesome 6 Brands", "FontAwesome" !important;
        }
        .bi, [class^="bi-"], [class*=" bi-"] {
            font-family: "bootstrap-icons" !important;
        }

        .compact-form-wrapper {
            max-width: 100%;
        }

        .compact-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .font-xs { font-size: 0.76rem !important; }
        .font-sm { font-size: 0.84rem !important; }
        .font-md { font-size: 0.95rem !important; }
        .font-xxs { font-size: 0.70rem !important; }

        .form-label-compact {
            font-size: 0.78rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 2px;
        }

        .compact-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .compact-scrollbar::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        .compact-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .compact-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .sticky-summary-bar {
            position: sticky;
            bottom: 10px;
            z-index: 1020;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.22);
            border-radius: 8px;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid px-0 compact-form-wrapper">

                @if ($readOnly ?? false)
                    <div class="alert alert-warning py-1.5 px-3 mb-2 rounded-2 small d-flex align-items-center gap-2">
                        <i class="fa fa-eye"></i>
                        <span><strong>View Only Mode:</strong> You have view permission only. All fields are disabled.</span>
                    </div>
                @endif

                <form id="salaryForm" action="{{ route('hr.salary-structure.update', $employee->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Employee Details Bar -->
                    <div class="alert alert-info py-1.5 px-3 mb-2 d-flex align-items-center justify-content-between rounded-2 border-0 shadow-xs"
                        style="background: #e0f2fe; color: #0369a1;">
                        <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 font-xs">
                            <div><strong>Employee:</strong> {{ $employee->full_name }}</div>
                            <span class="opacity-50">|</span>
                            <div><strong>Department:</strong> {{ $employee->department->name ?? 'N/A' }}</div>
                            <span class="opacity-50">|</span>
                            <div><strong>Designation:</strong> {{ $employee->designation->name ?? 'N/A' }}</div>
                            <span class="opacity-50">|</span>
                            <div><strong>Joining:</strong> {{ $employee->joining_date }}</div>
                        </div>
                    </div>

                    <!-- Top Configuration Card -->
                    <div class="compact-card shadow-sm p-2.5 mb-2">
                        <div class="row g-2 align-items-end">
                            <!-- Salary Type -->
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label-compact d-block">Salary Type <span class="text-danger">*</span></label>
                                <select name="salary_type" id="salary_type" class="form-select form-select-sm rounded-2" required
                                    {{ $readOnly ?? false ? 'disabled' : '' }}>
                                    <option value="salary" {{ ($salaryStructure->salary_type ?? '') == 'salary' ? 'selected' : '' }}>Salary Based (Fixed)</option>
                                    <option value="commission" {{ ($salaryStructure->salary_type ?? '') == 'commission' ? 'selected' : '' }}>Commission Based</option>
                                    <option value="both" {{ ($salaryStructure->salary_type ?? '') == 'both' ? 'selected' : '' }}>Both (Salary + Commission)</option>
                                </select>
                            </div>

                            <!-- Base Salary -->
                            <div class="col-lg-3 col-md-6" id="base_salary_container">
                                <label class="form-label-compact d-block">Base Salary (Rs.)</label>
                                <input type="number" step="0.01" name="base_salary" id="base_salary"
                                    class="form-control form-control-sm text-end fw-semibold rounded-2"
                                    value="{{ $salaryStructure->base_salary ?? ($employee->basic_salary ?? 0) }}"
                                    {{ $readOnly ?? false ? 'disabled' : '' }}>
                            </div>

                            <!-- Daily Wages -->
                            <div class="col-lg-3 col-md-6" id="daily_wages_container">
                                <div class="d-flex justify-content-between align-items-center mb-0.5">
                                    <label class="form-label-compact mb-0">Daily Wages (Rs.)</label>
                                    <div class="form-check form-switch m-0 font-xs">
                                        <input class="form-check-input" type="checkbox" id="use_daily_wages"
                                            name="use_daily_wages" value="1"
                                            {{ $salaryStructure->use_daily_wages ? 'checked' : '' }}
                                            {{ $readOnly ?? false ? 'disabled' : '' }}>
                                        <label class="form-check-label font-xs" for="use_daily_wages">Enable</label>
                                    </div>
                                </div>
                                <input type="number" step="0.01" name="daily_wages" id="daily_wages"
                                    class="form-control form-control-sm text-end fw-semibold rounded-2"
                                    value="{{ $salaryStructure->daily_wages ?? '' }}"
                                    placeholder="Daily Rate"
                                    {{ ($readOnly ?? false) || !($salaryStructure->use_daily_wages ?? false) ? 'disabled' : '' }}>
                            </div>

                            <!-- Absent Deduction -->
                            <div class="col-lg-3 col-md-6" id="absent_deduction_container">
                                <div class="d-flex justify-content-between align-items-center mb-0.5">
                                    <label class="form-label-compact mb-0">Absent Deduction</label>
                                    <div class="d-flex gap-2 font-xs">
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input" type="radio" name="absent_deduction_type" id="deduction_manual" value="manual" 
                                                {{ ($salaryStructure->absent_deduction_type ?? 'manual') == 'manual' ? 'checked' : '' }}
                                                {{ $readOnly ?? false ? 'disabled' : '' }}>
                                            <label class="form-check-label font-xs" for="deduction_manual">Manual</label>
                                        </div>
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input" type="radio" name="absent_deduction_type" id="deduction_automatic" value="automatic"
                                                {{ ($salaryStructure->absent_deduction_type ?? 'manual') == 'automatic' ? 'checked' : '' }}
                                                {{ $readOnly ?? false ? 'disabled' : '' }}>
                                            <label class="form-check-label font-xs" for="deduction_automatic">Auto</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text py-0 px-2 font-xs bg-light">Per Day Rs.</span>
                                    <input type="number" step="0.01" name="leave_salary_per_day" id="leave_salary_per_day"
                                        class="form-control form-control-sm text-end fw-semibold rounded-end-2"
                                        value="{{ $salaryStructure->leave_salary_per_day ?? '' }}"
                                        placeholder="0.00"
                                        {{ ($readOnly ?? false) || (($salaryStructure->absent_deduction_type ?? 'manual') == 'automatic') ? 'disabled' : '' }}>
                                </div>
                                <small class="text-muted font-xxs d-block text-truncate mt-0.5" id="absent_deduction_note">
                                    {{ ($salaryStructure->absent_deduction_type ?? 'manual') == 'automatic' ? 'Calculated automatically by work hours.' : 'Fixed deduction per absent day.' }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Commission Settings Section (Compact Card) -->
                    <div class="compact-card shadow-sm mb-2" id="commission_section" style="display: none; border-left: 3px solid #2563eb;">
                        <div class="card-header py-1.5 px-3 bg-light d-flex align-items-center justify-content-between border-bottom">
                            <span class="fw-bold font-sm text-primary">
                                <i class="fa fa-chart-line me-1"></i> Commission Settings
                            </span>
                        </div>
                        <div class="p-2.5">
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-md-5">
                                    <label class="form-label-compact d-block">
                                        <i class="fa fa-bullseye text-danger me-1"></i> Monthly Sales Target (Rs.)
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text font-xs">Rs.</span>
                                        <input type="number" step="0.01" name="sales_target" id="sales_target"
                                            class="form-control form-control-sm"
                                            value="{{ $salaryStructure->sales_target ?? '' }}" placeholder="e.g. 50000">
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label-compact d-block">
                                        <i class="fa fa-toggle-on text-success me-1"></i> Commission Structure Mode
                                    </label>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <input type="radio" class="btn-check" name="commission_mode" id="mode_flat" value="flat"
                                            {{ !$salaryStructure->commission_tiers || count($salaryStructure->commission_tiers ?? []) == 0 ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary py-1 font-xs" for="mode_flat">
                                            <i class="fa fa-percent me-1"></i> Flat Commission %
                                        </label>

                                        <input type="radio" class="btn-check" name="commission_mode" id="mode_tiered" value="tiered"
                                            {{ $salaryStructure->commission_tiers && count($salaryStructure->commission_tiers ?? []) > 0 ? 'checked' : '' }}>
                                        <label class="btn btn-outline-warning text-dark py-1 font-xs" for="mode_tiered">
                                            <i class="fa fa-layer-group me-1"></i> Tiered Rates
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Flat Commission Section -->
                            <div id="flat_commission_section" class="p-2 rounded-2 bg-light border" style="display: none;">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-6">
                                        <label class="form-label-compact d-block">Commission Percentage (%)</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.01" min="0" max="100"
                                                name="commission_percentage" id="commission_percentage"
                                                class="form-control form-control-sm text-end fw-semibold"
                                                value="{{ $salaryStructure->commission_percentage ?? '' }}"
                                                placeholder="e.g. 5">
                                            <span class="input-group-text font-xs">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-info py-1 px-2 mb-0 font-xs rounded-2">
                                            <i class="fa fa-info-circle me-1"></i> Applies uniformly to all eligible sales.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tiered Commission Section -->
                            <div id="tiered_commission_section" class="p-2 rounded-2 bg-light border" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-1.5 pb-1 border-bottom">
                                    <span class="font-xs fw-bold text-dark">
                                        <i class="fa fa-layer-group me-1"></i> Define Sales Ranges & %
                                    </span>
                                    <button type="button" class="btn btn-dark btn-sm py-0.5 px-2 font-xs rounded-2" id="addCommissionTier">
                                        <i class="fa fa-plus me-1"></i> Add Tier
                                    </button>
                                </div>

                                <div class="row g-1 mb-1 fw-bold text-muted font-xs px-1" id="tier_headers" style="display: none;">
                                    <div class="col-1 text-center">#</div>
                                    <div class="col-3">Commission %</div>
                                    <div class="col-4">Sales Range (Up to)</div>
                                    <div class="col-3">Tier Range</div>
                                    <div class="col-1 text-center"></div>
                                </div>

                                <div id="commission_tiers_container" class="compact-scrollbar" style="max-height: 180px; overflow-y: auto;">
                                    @if ($salaryStructure->commission_tiers)
                                        @php $prevAmount = 0; @endphp
                                        @foreach ($salaryStructure->commission_tiers as $index => $tier)
                                            <div class="row g-1 mb-1 commission-tier-row align-items-center px-1" data-index="{{ $index }}">
                                                <div class="col-1 text-center">
                                                    <span class="badge bg-secondary tier-number font-xs">{{ $index + 1 }}</span>
                                                </div>
                                                <div class="col-3">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" min="0" max="100"
                                                            name="commission_tiers[{{ $index }}][percentage]"
                                                            class="form-control form-control-sm tier-percentage text-end fw-semibold"
                                                            placeholder="e.g. 5"
                                                            value="{{ $tier['percentage'] ?? '' }}">
                                                        <span class="input-group-text py-0 px-1 font-xs">%</span>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text py-0 px-1 font-xs bg-light">Rs.</span>
                                                        <input type="number" step="0.01" min="1"
                                                            name="commission_tiers[{{ $index }}][upto_amount]"
                                                            class="form-control form-control-sm tier-upto text-end fw-semibold"
                                                            placeholder="e.g. 10000"
                                                            value="{{ $tier['upto_amount'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <span class="tier-range-display badge bg-white border text-dark font-xs w-100 text-truncate">
                                                        Rs. {{ number_format($prevAmount) }} - {{ number_format($tier['upto_amount'] ?? 0) }}
                                                    </span>
                                                </div>
                                                <div class="col-1 text-center">
                                                    <button type="button" class="btn btn-outline-danger btn-sm p-0.5 border-0 remove-row" title="Remove">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @php $prevAmount = $tier['upto_amount'] ?? 0; @endphp
                                        @endforeach
                                    @endif
                                </div>

                                <div id="no_tiers_message" class="text-center text-muted py-2 font-xs"
                                    style="{{ $salaryStructure->commission_tiers && count($salaryStructure->commission_tiers) > 0 ? 'display:none;' : '' }}">
                                    <i class="fa fa-info-circle me-1"></i> No commission tiers defined. Click "+ Add Tier" above.
                                </div>

                                <div id="tier_validation_error" class="alert alert-danger py-1 px-2 mt-1.5 font-xs" style="display: none;">
                                    <i class="fa fa-exclamation-triangle me-1"></i> <span id="tier_error_text"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Allowances & Deductions Side-by-Side (2 Columns) -->
                    <div class="row g-2 mb-2">
                        <!-- Allowances Card (col-lg-6) -->
                        <div class="col-lg-6">
                            <div class="compact-card shadow-sm h-100" style="border-top: 3px solid #16a34a;">
                                <div class="card-header py-1.5 px-3 bg-light d-flex align-items-center justify-content-between border-bottom">
                                    <div class="d-flex align-items-center gap-1.5">
                                        <i class="fa fa-gift text-success"></i>
                                        <span class="fw-bold text-success-emphasis font-sm">Allowances</span>
                                        <span class="badge bg-success text-white font-xxs rounded-pill" id="allowance_count_badge"></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-success py-0.5 px-2 rounded-2 fw-semibold shadow-xs font-xs" id="addAllowance">
                                        <i class="fa fa-plus me-1"></i> Add Allowance
                                    </button>
                                </div>
                                <div class="p-2">
                                    <div class="row g-1 mb-1 fw-semibold text-muted font-xs px-1" id="allowance_headers"
                                        style="{{ $salaryStructure->allowances && count($salaryStructure->allowances) > 0 ? '' : 'display:none;' }}">
                                        <div class="col-1 text-center">#</div>
                                        <div class="col-1 text-center" title="Active Status">Act</div>
                                        <div class="col-5">Allowance Name</div>
                                        <div class="col-4 text-end pe-2">Amount (Rs.)</div>
                                        <div class="col-1 text-center"></div>
                                    </div>

                                    <div id="allowances_container" class="compact-scrollbar" style="max-height: 220px; overflow-y: auto;">
                                        @if ($salaryStructure->allowances)
                                            @foreach ($salaryStructure->allowances as $index => $allowance)
                                                <div class="row g-1 mb-1 allowance-row align-items-center px-1">
                                                    <div class="col-1 text-center">
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle allowance-number font-xs">{{ $index + 1 }}</span>
                                                    </div>
                                                    <div class="col-1 text-center">
                                                        <div class="form-check form-switch d-flex justify-content-center m-0">
                                                            <input class="form-check-input allowance-active" type="checkbox"
                                                                name="allowances[{{ $index }}][is_active]" value="1"
                                                                {{ !isset($allowance['is_active']) || $allowance['is_active'] ? 'checked' : '' }}
                                                                {{ $readOnly ?? false ? 'disabled' : '' }}>
                                                        </div>
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="text" name="allowances[{{ $index }}][name]" class="form-control form-control-sm rounded-2"
                                                            placeholder="e.g. Housing Allowance" value="{{ $allowance['name'] ?? '' }}">
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text py-0 px-1 font-xs bg-light">Rs.</span>
                                                            <input type="number" step="0.01" name="allowances[{{ $index }}][amount]"
                                                                class="form-control form-control-sm allowance-amount text-end fw-semibold rounded-end-2"
                                                                placeholder="0.00" value="{{ $allowance['amount'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-1 text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 p-0.5 remove-row" title="Remove">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>

                                    <div id="no_allowances_message" class="text-center text-muted py-2 font-xs"
                                        style="{{ $salaryStructure->allowances && count($salaryStructure->allowances) > 0 ? 'display:none;' : '' }}">
                                        <i class="fa fa-gift text-success opacity-50 me-1"></i> No allowances added. Click <strong>+ Add Allowance</strong>.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Deductions Card (col-lg-6) -->
                        <div class="col-lg-6">
                            <div class="compact-card shadow-sm h-100" style="border-top: 3px solid #dc2626;">
                                <div class="card-header py-1.5 px-3 bg-light d-flex align-items-center justify-content-between border-bottom">
                                    <div class="d-flex align-items-center gap-1.5">
                                        <i class="fa fa-minus-circle text-danger"></i>
                                        <span class="fw-bold text-danger-emphasis font-sm">Deductions</span>
                                        <span class="badge bg-danger text-white font-xxs rounded-pill" id="deduction_count_badge"></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger py-0.5 px-2 rounded-2 fw-semibold shadow-xs font-xs" id="addDeduction">
                                        <i class="fa fa-plus me-1"></i> Add Deduction
                                    </button>
                                </div>
                                <div class="p-2">
                                    <div class="row g-1 mb-1 fw-semibold text-muted font-xs px-1" id="deduction_headers"
                                        style="{{ $salaryStructure->deductions && count($salaryStructure->deductions) > 0 ? '' : 'display:none;' }}">
                                        <div class="col-1 text-center">#</div>
                                        <div class="col-1 text-center" title="Active Status">Act</div>
                                        <div class="col-5">Deduction Name</div>
                                        <div class="col-4 text-end pe-2">Amount (Rs.)</div>
                                        <div class="col-1 text-center"></div>
                                    </div>

                                    <div id="deductions_container" class="compact-scrollbar" style="max-height: 220px; overflow-y: auto;">
                                        @if ($salaryStructure->deductions)
                                            @foreach ($salaryStructure->deductions as $index => $deduction)
                                                <div class="row g-1 mb-1 deduction-row align-items-center px-1">
                                                    <div class="col-1 text-center">
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle deduction-number font-xs">{{ $index + 1 }}</span>
                                                    </div>
                                                    <div class="col-1 text-center">
                                                        <div class="form-check form-switch d-flex justify-content-center m-0">
                                                            <input class="form-check-input deduction-active" type="checkbox"
                                                                name="deductions[{{ $index }}][is_active]" value="1"
                                                                {{ !isset($deduction['is_active']) || $deduction['is_active'] ? 'checked' : '' }}
                                                                {{ $readOnly ?? false ? 'disabled' : '' }}>
                                                        </div>
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="text" name="deductions[{{ $index }}][name]" class="form-control form-control-sm rounded-2"
                                                            placeholder="e.g. Income Tax" value="{{ $deduction['name'] ?? '' }}">
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text py-0 px-1 font-xs bg-light">Rs.</span>
                                                            <input type="number" step="0.01" name="deductions[{{ $index }}][amount]"
                                                                class="form-control form-control-sm deduction-amount text-end fw-semibold rounded-end-2"
                                                                placeholder="0.00" value="{{ $deduction['amount'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-1 text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 p-0.5 remove-row" title="Remove">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>

                                    <div id="no_deductions_message" class="text-center text-muted py-2 font-xs"
                                        style="{{ $salaryStructure->deductions && count($salaryStructure->deductions) > 0 ? 'display:none;' : '' }}">
                                        <i class="fa fa-minus-circle text-danger opacity-50 me-1"></i> No deductions added. Click <strong>+ Add Deduction</strong>.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Deduction Rules Section (Conditional) -->
                    <div class="compact-card shadow-sm mb-2" id="attendance_rules_section" style="display: none; border-left: 3px solid #64748b;">
                        <div class="card-header py-1.5 px-3 bg-light d-flex align-items-center justify-content-between border-bottom">
                            <span class="fw-bold font-sm text-dark">
                                <i class="fa fa-clock me-1"></i> Attendance Deduction Rules (Daily Wage)
                            </span>
                            <div class="form-check form-switch m-0 font-xs">
                                <input class="form-check-input" type="checkbox" id="carry_forward_deductions"
                                    name="carry_forward_deductions" value="1"
                                    {{ $salaryStructure->carry_forward_deductions ? 'checked' : '' }}
                                    {{ $readOnly ?? false ? 'disabled' : '' }}>
                                <label class="form-check-label font-xs fw-semibold" for="carry_forward_deductions">
                                    Carry forward remaining deduction if daily wage insufficient
                                </label>
                            </div>
                        </div>
                        <div class="p-2.5">
                            <div class="row g-2">
                                <!-- Late Rules -->
                                <div class="col-md-6 border-end pe-md-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-xs fw-bold text-danger"><i class="fa fa-clock me-1"></i> Late Check-in Rules</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0.5 px-2 font-xs rounded-2" id="addLateRule">
                                            <i class="fa fa-plus me-1"></i> Add Rule
                                        </button>
                                    </div>

                                    <div class="row g-1 mb-1 fw-bold text-muted font-xs px-1" id="late_rules_headers" style="display:none;">
                                        <div class="col-3">Range (min)</div>
                                        <div class="col-3">Max min</div>
                                        <div class="col-2">Type</div>
                                        <div class="col-3">Deduction</div>
                                        <div class="col-1 text-center"></div>
                                    </div>

                                    <div id="late_rules_container" class="compact-scrollbar" style="max-height: 180px; overflow-y: auto;">
                                        @if (isset($salaryStructure->attendance_deduction_policy['late_rules']))
                                            @foreach ($salaryStructure->attendance_deduction_policy['late_rules'] as $index => $rule)
                                                <div class="row g-1 mb-1 rule-row late-rule-row align-items-center px-1">
                                                    <div class="col-3">
                                                        <span class="badge bg-light text-dark font-xs range-display w-100 text-truncate">
                                                            {{ $rule['min_minutes'] }} - {{ $rule['max_minutes'] ?? '∞' }}
                                                        </span>
                                                        <input type="hidden" name="late_rules[{{ $index }}][min_minutes]"
                                                            class="rule-min" value="{{ $rule['min_minutes'] }}">
                                                    </div>
                                                    <div class="col-3">
                                                        <input type="number" name="late_rules[{{ $index }}][max_minutes]"
                                                            class="form-control form-control-sm rule-max text-end"
                                                            placeholder="{{ $rule['max_minutes'] ? '' : '∞' }}"
                                                            value="{{ $rule['max_minutes'] }}">
                                                    </div>
                                                    <div class="col-2">
                                                        <select name="late_rules[{{ $index }}][type]" class="form-select form-select-sm px-1 font-xs">
                                                            <option value="fixed" {{ ($rule['type'] ?? 'fixed') == 'fixed' ? 'selected' : '' }}>Rs.</option>
                                                            <option value="percentage" {{ ($rule['type'] ?? 'fixed') == 'percentage' ? 'selected' : '' }}>%</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-3">
                                                        <input type="number" step="0.01" name="late_rules[{{ $index }}][amount]"
                                                            class="form-control form-control-sm rule-amount text-end fw-semibold"
                                                            placeholder="Amt" value="{{ $rule['amount'] }}">
                                                    </div>
                                                    <div class="col-1 text-center">
                                                        <button type="button" class="btn btn-outline-danger btn-sm p-0.5 border-0 remove-rule">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                    <div id="late_rules_validation" class="alert alert-danger py-1 px-2 mt-1 font-xs" style="display:none;"></div>
                                </div>

                                <!-- Early Rules -->
                                <div class="col-md-6 ps-md-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-xs fw-bold text-danger"><i class="fa fa-sign-out-alt me-1"></i> Early Check-out Rules</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0.5 px-2 font-xs rounded-2" id="addEarlyRule">
                                            <i class="fa fa-plus me-1"></i> Add Rule
                                        </button>
                                    </div>

                                    <div class="row g-1 mb-1 fw-bold text-muted font-xs px-1" id="early_rules_headers" style="display:none;">
                                        <div class="col-3">Range (min)</div>
                                        <div class="col-3">Max min</div>
                                        <div class="col-2">Type</div>
                                        <div class="col-3">Deduction</div>
                                        <div class="col-1 text-center"></div>
                                    </div>

                                    <div id="early_rules_container" class="compact-scrollbar" style="max-height: 180px; overflow-y: auto;">
                                        @if (isset($salaryStructure->attendance_deduction_policy['early_rules']))
                                            @foreach ($salaryStructure->attendance_deduction_policy['early_rules'] as $index => $rule)
                                                <div class="row g-1 mb-1 rule-row early-rule-row align-items-center px-1">
                                                    <div class="col-3">
                                                        <span class="badge bg-light text-dark font-xs range-display w-100 text-truncate">
                                                            {{ $rule['min_minutes'] }} - {{ $rule['max_minutes'] ?? '∞' }}
                                                        </span>
                                                        <input type="hidden" name="early_rules[{{ $index }}][min_minutes]"
                                                            class="rule-min" value="{{ $rule['min_minutes'] }}">
                                                    </div>
                                                    <div class="col-3">
                                                        <input type="number" name="early_rules[{{ $index }}][max_minutes]"
                                                            class="form-control form-control-sm rule-max text-end"
                                                            placeholder="{{ $rule['max_minutes'] ? '' : '∞' }}"
                                                            value="{{ $rule['max_minutes'] }}">
                                                    </div>
                                                    <div class="col-2">
                                                        <select name="early_rules[{{ $index }}][type]" class="form-select form-select-sm px-1 font-xs">
                                                            <option value="fixed" {{ ($rule['type'] ?? 'fixed') == 'fixed' ? 'selected' : '' }}>Rs.</option>
                                                            <option value="percentage" {{ ($rule['type'] ?? 'fixed') == 'percentage' ? 'selected' : '' }}>%</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-3">
                                                        <input type="number" step="0.01" name="early_rules[{{ $index }}][amount]"
                                                            class="form-control form-control-sm rule-amount text-end fw-semibold"
                                                            placeholder="Amt" value="{{ $rule['amount'] }}">
                                                    </div>
                                                    <div class="col-1 text-center">
                                                        <button type="button" class="btn btn-outline-danger btn-sm p-0.5 border-0 remove-rule">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                    <div id="early_rules_validation" class="alert alert-danger py-1 px-2 mt-1 font-xs" style="display:none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Clean Light Theme Sticky Bottom Summary & Action Bar -->
                    <div class="sticky-summary-bar p-2.5 mt-2" style="position: sticky; bottom: 12px; z-index: 1020; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2.5">
                            <!-- Real-Time Calculation KPI Badges -->
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <div class="stat-pill px-2.5 py-1 rounded-2 bg-light border">
                                    <small class="text-muted font-xxs text-uppercase d-block fw-semibold">Base / Daily</small>
                                    <span class="fw-bold font-sm text-dark" id="summary_base">0.00</span>
                                </div>

                                <div class="stat-pill px-2.5 py-1 rounded-2 bg-success-subtle border border-success-subtle">
                                    <small class="text-success font-xxs text-uppercase d-block fw-semibold">+ Allowances</small>
                                    <span class="fw-bold font-sm text-success" id="summary_allowances">0.00</span>
                                </div>

                                <div class="stat-pill px-2.5 py-1 rounded-2 bg-danger-subtle border border-danger-subtle">
                                    <small class="text-danger font-xxs text-uppercase d-block fw-semibold">- Deductions</small>
                                    <span class="fw-bold font-sm text-danger" id="summary_deductions">0.00</span>
                                </div>

                                <div class="stat-pill px-3 py-1 rounded-2 bg-primary-subtle border border-primary-subtle shadow-xs">
                                    <small class="text-primary font-xxs text-uppercase d-block fw-bold">Est. Net Salary</small>
                                    <span class="fw-bolder font-md text-primary" id="summary_net">Rs. 0.00</span>
                                </div>
                            </div>

                            @if (!($readOnly ?? false))
                                <div class="d-flex align-items-center gap-2 ms-auto">
                                    <a href="{{ route('hr.salary-structure.index') }}" class="btn btn-sm btn-outline-secondary px-3 py-1.5 rounded-2 font-xs fw-semibold">
                                        Cancel
                                    </a>
                                    <button type="submit" class="btn btn-sm btn-primary px-3.5 py-1.5 fw-bold rounded-2 shadow-sm d-flex align-items-center gap-1.5 font-xs">
                                        <i class="fa fa-save"></i> Save Salary Structure
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            var allowanceIndex = {{ count($salaryStructure->allowances ?? []) }};
            var deductionIndex = {{ count($salaryStructure->deductions ?? []) }};
            var lateRuleIndex = {{ count($salaryStructure->attendance_deduction_policy['late_rules'] ?? []) }};
            var earlyRuleIndex = {{ count($salaryStructure->attendance_deduction_policy['early_rules'] ?? []) }};
            var commissionTierIndex = {{ count($salaryStructure->commission_tiers ?? []) }};
            var isReadOnly = {{ $readOnly ?? false ? 'true' : 'false' }};

            if (isReadOnly) {
                $('#salaryForm input, #salaryForm select, #salaryForm textarea').prop('disabled', true);
                $('#salaryForm .remove-row, #salaryForm .remove-rule').hide();
                $('#addAllowance, #addDeduction, #addCommissionTier, #addLateRule, #addEarlyRule').hide();
                $('input[name="commission_mode"]').prop('disabled', true);
            }

            function toggleCommissionSection() {
                var type = $('#salary_type').val();
                if (type === 'commission' || type === 'both') {
                    $('#commission_section').slideDown(150);
                } else {
                    $('#commission_section').slideUp(150);
                }

                if (type === 'commission') {
                    $('#base_salary_container').hide();
                } else {
                    $('#base_salary_container').show();
                }
                recalculate();
            }

            function toggleCommissionMode() {
                var mode = $('input[name="commission_mode"]:checked').val();
                if (mode === 'flat') {
                    $('#flat_commission_section').slideDown(150);
                    $('#tiered_commission_section').slideUp(150);
                    if (!isReadOnly) $('#commission_percentage').prop('disabled', false);
                } else {
                    $('#flat_commission_section').slideUp(150);
                    $('#tiered_commission_section').slideDown(150);
                    if (!isReadOnly) $('#commission_percentage').val('').prop('disabled', true);
                }
            }

            $('input[name="commission_mode"]').change(toggleCommissionMode);
            $('#salary_type').change(toggleCommissionSection);

            toggleCommissionSection();
            toggleCommissionMode();

            $('input[name="absent_deduction_type"]').on('change', function() {
                const type = $(this).val();
                if (type === 'automatic') {
                    $('#leave_salary_per_day').prop('disabled', true);
                    $('#absent_deduction_note').text('Calculated automatically by work hours.');
                } else {
                    $('#leave_salary_per_day').prop('disabled', isReadOnly ? true : false);
                    $('#absent_deduction_note').text('Fixed deduction per absent day.');
                }
                recalculate();
            });

            function updateTierDisplay() {
                var tiers = $('.commission-tier-row');
                var tierCount = tiers.length;

                if (tierCount > 0) {
                    $('#tier_headers').show();
                    $('#no_tiers_message').hide();
                } else {
                    $('#tier_headers').hide();
                    $('#no_tiers_message').show();
                }

                var prevAmount = 0;
                tiers.each(function(index) {
                    $(this).find('.tier-number').text(index + 1);
                    var uptoAmount = parseFloat($(this).find('.tier-upto').val()) || 0;
                    $(this).find('.tier-range-display').text('Rs. ' + prevAmount.toLocaleString() + ' - ' + uptoAmount.toLocaleString());
                    prevAmount = uptoAmount;
                });
            }

            function validateTiers() {
                var salesTarget = parseFloat($('#sales_target').val()) || 0;
                var tiers = $('.commission-tier-row');
                var isValid = true;
                var errorMsg = '';
                var prevAmount = 0;

                tiers.each(function(index) {
                    var uptoAmount = parseFloat($(this).find('.tier-upto').val()) || 0;

                    if (salesTarget > 0 && uptoAmount > salesTarget) {
                        isValid = false;
                        errorMsg = 'Tier ' + (index + 1) + ' amount (Rs. ' + uptoAmount.toLocaleString() + ') exceeds sales target (Rs. ' + salesTarget.toLocaleString() + ')';
                        $(this).find('.tier-upto').addClass('is-invalid');
                    } else {
                        $(this).find('.tier-upto').removeClass('is-invalid');
                    }

                    if (uptoAmount <= prevAmount && uptoAmount > 0) {
                        isValid = false;
                        errorMsg = 'Tier ' + (index + 1) + ' must be greater than previous tier (Rs. ' + prevAmount.toLocaleString() + ')';
                        $(this).find('.tier-upto').addClass('is-invalid');
                    }

                    prevAmount = uptoAmount;
                });

                if (!isValid) {
                    $('#tier_validation_error').show();
                    $('#tier_error_text').text(errorMsg);
                } else {
                    $('#tier_validation_error').hide();
                }

                return isValid;
            }

            $(document).on('input', '.tier-upto, #sales_target', function() {
                updateTierDisplay();
                validateTiers();
            });

            updateTierDisplay();

            $('#addCommissionTier').click(function() {
                var tierNum = $('.commission-tier-row').length + 1;
                var html = `
                    <div class="row g-1 mb-1 commission-tier-row align-items-center px-1" data-index="${commissionTierIndex}">
                        <div class="col-1 text-center">
                            <span class="badge bg-secondary tier-number font-xs">${tierNum}</span>
                        </div>
                        <div class="col-3">
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="100" name="commission_tiers[${commissionTierIndex}][percentage]" class="form-control form-control-sm tier-percentage text-end fw-semibold" placeholder="e.g. 5">
                                <span class="input-group-text py-0 px-1 font-xs">%</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text py-0 px-1 font-xs bg-light">Rs.</span>
                                <input type="number" step="0.01" min="1" name="commission_tiers[${commissionTierIndex}][upto_amount]" class="form-control form-control-sm tier-upto text-end fw-semibold" placeholder="e.g. 10000">
                            </div>
                        </div>
                        <div class="col-3">
                            <span class="tier-range-display badge bg-white border text-dark font-xs w-100 text-truncate">Rs. 0 - ?</span>
                        </div>
                        <div class="col-1 text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm p-0.5 border-0 remove-row" title="Remove"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                `;
                $('#commission_tiers_container').append(html);
                commissionTierIndex++;
                updateTierDisplay();
            });

            function updateAllowanceDisplay() {
                var allowances = $('.allowance-row');
                var count = allowances.length;

                if (count > 0) {
                    $('#allowance_headers').show();
                    $('#no_allowances_message').hide();
                    $('#allowance_count_badge').text(count).show();
                } else {
                    $('#allowance_headers').hide();
                    $('#no_allowances_message').show();
                    $('#allowance_count_badge').hide();
                }

                allowances.each(function(index) {
                    $(this).find('.allowance-number').text(index + 1);
                });
                recalculate();
            }

            updateAllowanceDisplay();

            $('#addAllowance').click(function() {
                var num = $('.allowance-row').length + 1;
                var html = `
                    <div class="row g-1 mb-1 allowance-row align-items-center px-1">
                        <div class="col-1 text-center">
                            <span class="badge bg-success-subtle text-success border border-success-subtle allowance-number font-xs">${num}</span>
                        </div>
                        <div class="col-1 text-center">
                            <div class="form-check form-switch d-flex justify-content-center m-0">
                                <input class="form-check-input allowance-active" type="checkbox" name="allowances[${allowanceIndex}][is_active]" value="1" checked>
                            </div>
                        </div>
                        <div class="col-5">
                            <input type="text" name="allowances[${allowanceIndex}][name]" class="form-control form-control-sm rounded-2" placeholder="e.g. Housing Allowance">
                        </div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text py-0 px-1 font-xs bg-light">Rs.</span>
                                <input type="number" step="0.01" name="allowances[${allowanceIndex}][amount]" class="form-control form-control-sm allowance-amount text-end fw-semibold rounded-end-2" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-1 text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-0.5 remove-row" title="Remove"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                `;
                $('#allowances_container').append(html);
                allowanceIndex++;
                updateAllowanceDisplay();
            });

            function updateDeductionDisplay() {
                var deductions = $('.deduction-row');
                var count = deductions.length;

                if (count > 0) {
                    $('#deduction_headers').show();
                    $('#no_deductions_message').hide();
                    $('#deduction_count_badge').text(count).show();
                } else {
                    $('#deduction_headers').hide();
                    $('#no_deductions_message').show();
                    $('#deduction_count_badge').hide();
                }

                deductions.each(function(index) {
                    $(this).find('.deduction-number').text(index + 1);
                });
                recalculate();
            }

            updateDeductionDisplay();

            $('#addDeduction').click(function() {
                var num = $('.deduction-row').length + 1;
                var html = `
                    <div class="row g-1 mb-1 deduction-row align-items-center px-1">
                        <div class="col-1 text-center">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle deduction-number font-xs">${num}</span>
                        </div>
                        <div class="col-1 text-center">
                            <div class="form-check form-switch d-flex justify-content-center m-0">
                                <input class="form-check-input deduction-active" type="checkbox" name="deductions[${deductionIndex}][is_active]" value="1" checked>
                            </div>
                        </div>
                        <div class="col-5">
                            <input type="text" name="deductions[${deductionIndex}][name]" class="form-control form-control-sm rounded-2" placeholder="e.g. Income Tax">
                        </div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text py-0 px-1 font-xs bg-light">Rs.</span>
                                <input type="number" step="0.01" name="deductions[${deductionIndex}][amount]" class="form-control form-control-sm deduction-amount text-end fw-semibold rounded-end-2" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-1 text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-0.5 remove-row" title="Remove"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                `;
                $('#deductions_container').append(html);
                deductionIndex++;
                updateDeductionDisplay();
            });

            $(document).on('click', '.remove-row', function() {
                var row = $(this).closest('.row');
                var isAllowance = row.hasClass('allowance-row');
                var isDeduction = row.hasClass('deduction-row');
                row.remove();
                if (isAllowance) updateAllowanceDisplay();
                if (isDeduction) updateDeductionDisplay();
                updateTierDisplay();
                validateTiers();
                recalculate();
            });

            function recalculateRuleRanges(containerSelector, validationSelector, headersSelector) {
                var rules = $(containerSelector + ' .rule-row');
                var currentMin = 1;
                var hasOpenEnded = false;
                var isValid = true;
                var errorMsg = '';

                if (rules.length > 0) {
                    $(headersSelector).show();
                } else {
                    $(headersSelector).hide();
                }

                currentMin = 1;
                hasOpenEnded = false;

                rules.each(function(index) {
                    var row = $(this);
                    var maxVal = row.find('.rule-max').val();
                    var maxMinutes = (maxVal === '' || maxVal === null) ? null : parseInt(maxVal);
                    var isOpenEnded = (maxMinutes === null);

                    if (isOpenEnded && hasOpenEnded) {
                        isValid = false;
                        errorMsg = 'Only one open-ended (∞) rule is allowed.';
                        row.find('.rule-max').addClass('is-invalid');
                    } else {
                        row.find('.rule-max').removeClass('is-invalid');
                    }

                    if (!isOpenEnded && maxMinutes < currentMin) {
                        isValid = false;
                        errorMsg = 'Rule ' + (index + 1) + ': Max (' + maxMinutes + ') cannot be less than Min (' + currentMin + ').';
                        row.find('.rule-max').addClass('is-invalid');
                    }

                    row.find('.rule-min').val(currentMin);
                    var displayMax = isOpenEnded ? '∞' : maxMinutes;
                    row.find('.range-display').text(currentMin + ' - ' + displayMax);

                    if (isOpenEnded) {
                        row.find('.rule-max').attr('placeholder', '∞');
                    } else {
                        row.find('.rule-max').attr('placeholder', '');
                    }

                    if (!isOpenEnded && maxMinutes !== null) {
                        currentMin = maxMinutes + 1;
                    }

                    if (isOpenEnded) {
                        hasOpenEnded = true;
                    }
                });

                if (!isValid) {
                    $(validationSelector).html('<i class="fa fa-exclamation-triangle"></i> ' + errorMsg).show();
                } else {
                    $(validationSelector).hide();
                }

                return isValid;
            }

            function getNextMinForContainer(containerSelector) {
                var rules = $(containerSelector + ' .rule-row');
                var maxMin = 1;
                var hasOpenEnded = false;

                rules.each(function() {
                    var maxVal = $(this).find('.rule-max').val();
                    if (maxVal === '' || maxVal === null) {
                        hasOpenEnded = true;
                    } else {
                        var max = parseInt(maxVal);
                        if (max >= maxMin) {
                            maxMin = max + 1;
                        }
                    }
                });

                return {
                    nextMin: maxMin,
                    hasOpenEnded: hasOpenEnded
                };
            }

            $('#addLateRule').click(function() {
                var info = getNextMinForContainer('#late_rules_container');
                if (info.hasOpenEnded) {
                    Swal.fire('Cannot Add Rule', 'An open-ended rule already exists. No new rules can be added after it.', 'warning');
                    return;
                }

                var html = `
                    <div class="row g-1 mb-1 rule-row late-rule-row align-items-center px-1">
                        <div class="col-3">
                            <span class="badge bg-light text-dark font-xs range-display w-100 text-truncate">${info.nextMin} - ?</span>
                            <input type="hidden" name="late_rules[${lateRuleIndex}][min_minutes]" class="rule-min" value="${info.nextMin}">
                        </div>
                        <div class="col-3">
                            <input type="number" name="late_rules[${lateRuleIndex}][max_minutes]" class="form-control form-control-sm rule-max text-end" placeholder="">
                        </div>
                        <div class="col-2">
                            <select name="late_rules[${lateRuleIndex}][type]" class="form-select form-select-sm px-1 font-xs">
                                <option value="fixed">Rs.</option>
                                <option value="percentage">%</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <input type="number" step="0.01" name="late_rules[${lateRuleIndex}][amount]" class="form-control form-control-sm rule-amount text-end fw-semibold" placeholder="Amt">
                        </div>
                        <div class="col-1 text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm p-0.5 border-0 remove-rule"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                `;
                $('#late_rules_container').append(html);
                lateRuleIndex++;
                recalculateRuleRanges('#late_rules_container', '#late_rules_validation', '#late_rules_headers');
            });

            $('#addEarlyRule').click(function() {
                var info = getNextMinForContainer('#early_rules_container');
                if (info.hasOpenEnded) {
                    Swal.fire('Cannot Add Rule', 'An open-ended rule already exists. No new rules can be added after it.', 'warning');
                    return;
                }

                var html = `
                    <div class="row g-1 mb-1 rule-row early-rule-row align-items-center px-1">
                        <div class="col-3">
                            <span class="badge bg-light text-dark font-xs range-display w-100 text-truncate">${info.nextMin} - ?</span>
                            <input type="hidden" name="early_rules[${earlyRuleIndex}][min_minutes]" class="rule-min" value="${info.nextMin}">
                        </div>
                        <div class="col-3">
                            <input type="number" name="early_rules[${earlyRuleIndex}][max_minutes]" class="form-control form-control-sm rule-max text-end" placeholder="">
                        </div>
                        <div class="col-2">
                            <select name="early_rules[${earlyRuleIndex}][type]" class="form-select form-select-sm px-1 font-xs">
                                <option value="fixed">Rs.</option>
                                <option value="percentage">%</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <input type="number" step="0.01" name="early_rules[${earlyRuleIndex}][amount]" class="form-control form-control-sm rule-amount text-end fw-semibold" placeholder="Amt">
                        </div>
                        <div class="col-1 text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm p-0.5 border-0 remove-rule"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                `;
                $('#early_rules_container').append(html);
                earlyRuleIndex++;
                recalculateRuleRanges('#early_rules_container', '#early_rules_validation', '#early_rules_headers');
            });

            $(document).on('click', '.remove-rule', function() {
                var row = $(this).closest('.rule-row');
                var isLate = row.hasClass('late-rule-row');
                var isEarly = row.hasClass('early-rule-row');
                row.remove();
                if (isLate) recalculateRuleRanges('#late_rules_container', '#late_rules_validation', '#late_rules_headers');
                if (isEarly) recalculateRuleRanges('#early_rules_container', '#early_rules_validation', '#early_rules_headers');
            });

            $(document).on('input change', '.rule-max', function() {
                var row = $(this).closest('.rule-row');
                var isLate = row.hasClass('late-rule-row');
                var isEarly = row.hasClass('early-rule-row');
                if (isLate) recalculateRuleRanges('#late_rules_container', '#late_rules_validation', '#late_rules_headers');
                if (isEarly) recalculateRuleRanges('#early_rules_container', '#early_rules_validation', '#early_rules_headers');
            });

            recalculateRuleRanges('#late_rules_container', '#late_rules_validation', '#late_rules_headers');
            recalculateRuleRanges('#early_rules_container', '#early_rules_validation', '#early_rules_headers');

            function parseVal(selector) {
                var val = parseFloat($(selector).val());
                return isNaN(val) ? 0 : val;
            }

            function recalculate() {
                var baseSalary = parseVal('#base_salary');
                var useDaily = $('#use_daily_wages').is(':checked');
                var dailyWages = parseVal('#daily_wages');

                var baseTotal = baseSalary;
                if (useDaily) {
                    baseTotal += (dailyWages * 30);
                }

                var totalAllowances = 0;
                $('.allowance-row').each(function() {
                    var isActive = $(this).find('.allowance-active').is(':checked');
                    if (isActive) {
                        var amt = parseFloat($(this).find('.allowance-amount').val()) || 0;
                        totalAllowances += amt;
                    }
                });

                var totalDeductions = 0;
                $('.deduction-row').each(function() {
                    var isActive = $(this).find('.deduction-active').is(':checked');
                    if (isActive) {
                        var amt = parseFloat($(this).find('.deduction-amount').val()) || 0;
                        totalDeductions += amt;
                    }
                });

                var net = baseTotal + totalAllowances - totalDeductions;

                $('#summary_base').text(baseTotal.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                $('#summary_allowances').text(totalAllowances.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                $('#summary_deductions').text(totalDeductions.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                $('#summary_net').text('Rs. ' + net.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            }

            $(document).on('input change',
                '#base_salary, #daily_wages, #use_daily_wages, .allowance-amount, .allowance-active, .deduction-amount, .deduction-active',
                function() {
                    recalculate();
                });

            $('#use_daily_wages').change(function() {
                if ($(this).is(':checked')) {
                    $('#daily_wages').prop('disabled', false);
                    $('#attendance_rules_section').slideDown(150);
                } else {
                    $('#daily_wages').prop('disabled', true).val('');
                    $('#attendance_rules_section').slideUp(150);
                }
                recalculate();
            });

            if ($('#use_daily_wages').is(':checked')) {
                $('#attendance_rules_section').show();
            } else {
                $('#attendance_rules_section').hide();
            }

            recalculate();

            $('#salaryForm').submit(function(e) {
                e.preventDefault();

                if ($('#commission_section').is(':visible') && $('input[name="commission_mode"]:checked').val() === 'tiered') {
                    if (!validateTiers()) {
                        Swal.fire('Validation Error', 'Please fix commission tier errors before saving.', 'error');
                        return;
                    }
                }

                if ($('#use_daily_wages').is(':checked')) {
                    var lateValid = recalculateRuleRanges('#late_rules_container', '#late_rules_validation', '#late_rules_headers');
                    var earlyValid = recalculateRuleRanges('#early_rules_container', '#early_rules_validation', '#early_rules_headers');

                    if (!lateValid || !earlyValid) {
                        Swal.fire('Validation Error', 'Please fix attendance deduction rule errors before saving.', 'error');
                        return;
                    }
                }

                var submitBtn = $(this).find('button[type="submit"]');
                var originalHtml = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                var form = this;
                $.ajax({
                    url: $(form).attr('action'),
                    method: $(form).attr('method') || 'POST',
                    data: $(form).serialize(),
                    success: function(response) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.success || 'Salary Structure updated successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                window.location.reload();
                            }
                        });
                    },
                    error: function(xhr) {
                        submitBtn.prop('disabled', false).html(originalHtml);
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            var errorMsg = 'Please correct the following errors:\n';
                            Object.keys(errors).forEach(function(key) {
                                errorMsg += '- ' + errors[key][0] + '\n';
                                var field = $('[name="' + key + '"]');
                                if (field.length) {
                                    field.addClass('is-invalid');
                                    if (!field.next('.invalid-feedback').length) {
                                        field.after('<div class="invalid-feedback">' + errors[key][0] + '</div>');
                                    }
                                }
                            });
                            Swal.fire('Validation Error', errorMsg, 'error');
                        } else {
                            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'An unexpected error occurred.';
                            Swal.fire('Error', msg, 'error');
                        }
                    }
                });
            });
        });
    </script>
@endsection
