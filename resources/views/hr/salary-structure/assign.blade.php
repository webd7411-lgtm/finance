@extends('layouts.app')

@section('title', 'Assign Employees - ' . ($salaryStructure->name ?? 'Salary Structure') . ' - Caltex Rao Filling Station')
@section('page_title', 'Assign Employees to Structure')

@section('page_badge')
    <span class="badge bg-white text-dark border px-2.5 py-1 small rounded-pill shadow-xs">
        <i class="bi bi-wallet2 me-1 text-primary"></i> {{ $salaryStructure->name }}
    </span>
@endsection

@section('page_actions')
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('hr.salary-structure.view-assigned', $salaryStructure->id) }}" class="btn btn-outline-info btn-sm rounded-3 fw-semibold shadow-sm text-dark font-xs py-1">
            <i class="fa fa-eye me-1 text-info"></i> View Assigned ({{ $salaryStructure->assigned_count }})
        </a>
        <a href="{{ route('hr.salary-structure.individual-update-page', $salaryStructure->id) }}" class="btn btn-outline-warning btn-sm rounded-3 fw-semibold shadow-sm text-dark font-xs py-1">
            <i class="fa fa-user-cog me-1 text-warning"></i> Edit Individual
        </a>
        <a href="{{ route('hr.salary-structure.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold shadow-sm text-nowrap font-xs py-1">
            <i class="bi bi-arrow-left me-1"></i> Back to Structures
        </a>
    </div>
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

        .font-xs { font-size: 0.76rem !important; }
        .font-sm { font-size: 0.84rem !important; }
        .font-md { font-size: 0.95rem !important; }
        .font-xxs { font-size: 0.70rem !important; }

        .btn-xs {
            padding: 0.2rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 6px;
        }

        .employee-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .employee-row {
            transition: background 0.15s;
        }

        .employee-row:hover {
            background: #f8fafc;
        }

        .employee-row.already-assigned {
            background: #fffbeb;
        }

        .employee-row.has-other-structure {
            background: #fef2f2;
        }

        /* Compact Scrollbar for Table */
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

        /* Sticky Action Bar */
        .sticky-action-bar {
            position: sticky;
            bottom: 10px;
            z-index: 1020;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.24);
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid px-0">

                <!-- Structure Info Strip (Compact 1-row Executive Card) -->
                <div class="card border-0 shadow-sm rounded-3 mb-2" style="background: linear-gradient(135deg, #090e17 0%, #1e293b 100%); color: #ffffff;">
                    <div class="card-body py-1.5 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary p-1 rounded-2">
                                <i class="fa fa-file-invoice-dollar fs-6"></i>
                            </span>
                            <div>
                                <span class="fw-bold font-sm text-white lh-sm">
                                    {{ $salaryStructure->name }}
                                </span>
                                <span class="badge bg-light text-dark font-xxs ms-1 text-uppercase">
                                    {{ ucfirst($salaryStructure->salary_type) }} {{ $salaryStructure->use_daily_wages ? '(Daily Wages)' : '(Monthly Salary)' }}
                                </span>
                            </div>
                        </div>

                        <!-- KPI Badges Row -->
                        <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 font-xs">
                            <div class="d-flex align-items-center gap-1">
                                <span class="text-uppercase" style="color: #94a3b8;">Base/Daily:</span>
                                <span class="badge bg-secondary-subtle text-dark fw-bold px-2 py-0.5 font-xs">
                                    Rs. {{ number_format($salaryStructure->use_daily_wages ? $salaryStructure->daily_wages : $salaryStructure->base_salary, 2) }}
                                </span>
                            </div>
                            @if (!$salaryStructure->use_daily_wages)
                                <span class="opacity-40 d-none d-sm-inline" style="color: #64748b;">|</span>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-uppercase" style="color: #94a3b8;">Allowances:</span>
                                    <span class="badge bg-success-subtle text-success fw-bold px-2 py-0.5 font-xs">
                                        + Rs. {{ number_format($salaryStructure->total_allowances, 2) }}
                                    </span>
                                </div>
                                <span class="opacity-40 d-none d-sm-inline" style="color: #64748b;">|</span>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-uppercase" style="color: #94a3b8;">Deductions:</span>
                                    <span class="badge bg-danger-subtle text-danger fw-bold px-2 py-0.5 font-xs">
                                        - Rs. {{ number_format($salaryStructure->total_deductions, 2) }}
                                    </span>
                                </div>
                                <span class="opacity-40 d-none d-sm-inline" style="color: #64748b;">|</span>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-white fw-bold text-uppercase">Net:</span>
                                    <span class="badge bg-primary text-white fw-bold px-2 py-0.5 font-xs shadow-xs">
                                        Rs. {{ number_format($salaryStructure->base_salary + $salaryStructure->total_allowances - $salaryStructure->total_deductions, 2) }}
                                    </span>
                                </div>
                            @endif
                            <span class="opacity-40 d-none d-sm-inline" style="color: #64748b;">|</span>
                            <span class="badge bg-white text-dark fw-semibold font-xs px-2 py-0.5 shadow-xs">
                                <i class="fa fa-users text-primary me-1"></i> Currently Assigned: {{ $salaryStructure->assigned_count }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Filter Strip (Compact 1-row Bar) -->
                <div class="card border shadow-sm rounded-3 mb-2 p-2" style="background: #ffffff; border-color: #e2e8f0 !important;">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-auto d-flex align-items-center gap-1.5 text-primary fw-bold font-xs">
                            <i class="fa fa-filter"></i> <span>Filter:</span>
                        </div>
                        <div class="col-md-auto">
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="filter_type" id="filter_all" value="all" checked>
                                <label class="btn btn-outline-primary py-1 px-2.5 font-xs" for="filter_all">All Employees</label>

                                <input type="radio" class="btn-check" name="filter_type" id="filter_dept" value="department">
                                <label class="btn btn-outline-primary py-1 px-2.5 font-xs" for="filter_dept">Department</label>

                                <input type="radio" class="btn-check" name="filter_type" id="filter_desig" value="designation">
                                <label class="btn btn-outline-primary py-1 px-2.5 font-xs" for="filter_desig">Designation</label>
                            </div>
                        </div>
                        <div class="col-md-3" id="department_selector" style="display: none;">
                            <select class="form-select form-select-sm rounded-2 font-xs" id="department_id">
                                <option value="">Select Department</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3" id="designation_selector" style="display: none;">
                            <select class="form-select form-select-sm rounded-2 font-xs" id="designation_id">
                                <option value="">Select Designation</option>
                                @foreach ($designations as $desig)
                                    <option value="{{ $desig->id }}">{{ $desig->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-auto ms-md-auto">
                            <button class="btn btn-primary btn-sm px-3 py-1 fw-bold rounded-2 font-xs shadow-xs" id="fetchEmployeesBtn">
                                <i class="fa fa-sync me-1"></i> Load Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Employees Table Card (Controlled Scroll Area) -->
                <div class="card border shadow-sm rounded-3 mb-2" style="background: #ffffff; border-color: #e2e8f0 !important;">
                    <div class="card-header py-1.5 px-3 bg-light d-flex align-items-center justify-content-between border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold font-sm text-dark">Select Employees</span>
                            <span class="badge bg-secondary-subtle text-secondary font-xxs rounded-pill" id="employeeCount">0 employees loaded</span>
                        </div>
                        <div class="d-flex gap-1.5">
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-2 rounded-2 font-xs" id="selectAllBtn">
                                <i class="fa fa-check-square me-1"></i> Select All
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-2 rounded-2 font-xs" id="clearAllBtn">
                                <i class="fa fa-square me-1"></i> Clear All
                            </button>
                        </div>
                    </div>

                    <div class="employee-table compact-scrollbar" id="employeesContainer" style="max-height: 240px; overflow-y: auto;">
                        <div class="text-center text-muted py-4 font-xs" id="emptyState">
                            <i class="fa fa-users fs-3 text-secondary opacity-50 mb-2 d-block"></i>
                            Click <strong>"Load Filters"</strong> to fetch employees for assignment
                        </div>
                    </div>
                </div>

                <!-- Sticky Bottom Assignment Action Bar -->
                <form id="assignmentForm" style="display: none;">
                    @csrf
                    <input type="hidden" name="salary_structure_id" value="{{ $salaryStructure->id }}">
                    <div id="selectedEmployeesInputs"></div>

                    <div class="card border shadow-sm sticky-action-bar rounded-3 mt-2 p-2.5" style="background: #ffffff; border-color: #e2e8f0 !important; box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2.5">
                            <!-- Selected Badge -->
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary px-3 py-1.5 font-sm fw-bold shadow-xs rounded-2">
                                    <i class="fa fa-users me-1"></i> <span id="selectedCount">0</span> Employees Selected
                                </span>
                            </div>

                            <!-- Assignment Parameters (Inline) -->
                            <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1 mx-md-2" style="max-width: 650px;">
                                <div class="d-flex align-items-center gap-1.5">
                                    <label class="font-xs text-uppercase fw-bold mb-0 text-nowrap text-secondary">
                                        Start Date <span class="text-danger">*</span>:
                                    </label>
                                    <input type="date" name="start_date" class="form-control form-control-sm rounded-2 font-xs fw-semibold border"
                                        value="{{ date('Y-m-d') }}" required style="max-width: 145px;">
                                </div>
                                <div class="d-flex align-items-center gap-1.5 flex-grow-1">
                                    <label class="font-xs text-uppercase fw-bold mb-0 text-nowrap text-secondary">
                                        Notes:
                                    </label>
                                    <input type="text" name="notes" class="form-control form-control-sm rounded-2 font-xs border"
                                        placeholder="e.g. Annual increment 2026">
                                </div>
                            </div>

                            <!-- Confirm Button -->
                            <button type="submit" class="btn btn-sm btn-success px-4 py-1.5 fw-bold rounded-2 font-sm shadow-xs d-flex align-items-center gap-1.5" id="assignBtn">
                                <i class="fa fa-check-circle"></i> Confirm Assignment
                            </button>
                        </div>
                    </div>
                                <i class="fa fa-check-circle"></i> Confirm Assignment
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            let employees = [];

            // Show/hide filter selectors
            $('input[name="filter_type"]').change(function() {
                const filterType = $(this).val();
                $('#department_selector').toggle(filterType === 'department');
                $('#designation_selector').toggle(filterType === 'designation');
            });

            // Fetch employees
            $('#fetchEmployeesBtn').click(function() {
                const filterType = $('input[name="filter_type"]:checked').val();
                const departmentId = $('#department_id').val();
                const designationId = $('#designation_id').val();

                if (filterType === 'department' && !departmentId) {
                    Swal.fire('Error', 'Please select a department', 'error');
                    return;
                }

                if (filterType === 'designation' && !designationId) {
                    Swal.fire('Error', 'Please select a designation', 'error');
                    return;
                }

                const btn = $(this);
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');

                Swal.fire({
                    title: 'Loading Data...',
                    html: 'Fetching employee records. Please wait.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '{{ route('hr.salary-structure.fetch-employees') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        filter_type: filterType,
                        department_id: departmentId,
                        designation_id: designationId,
                        salary_structure_id: {{ $salaryStructure->id }}
                    },
                    success: function(response) {
                        employees = response.employees;
                        renderEmployees(employees);
                        $('#employeeCount').text(response.count + ' employees loaded');

                        Swal.fire({
                            icon: 'success',
                            title: 'Loaded!',
                            text: `Successfully fetched ${response.count} employees.`,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        let msg = 'Failed to fetch employees';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            msg = xhr.responseJSON.error;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Fetch Failed',
                            text: msg,
                            confirmButtonColor: '#d33'
                        });
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fa fa-sync me-1"></i> Load Filters');
                    }
                });
            });

            function renderEmployees(employees) {
                if (employees.length === 0) {
                    $('#employeesContainer').html(`
                        <div class="text-center text-muted py-4 font-xs">
                            <i class="fa fa-users fs-3 text-secondary opacity-50 mb-2 d-block"></i>
                            <p class="mb-0">No active employees found for this filter</p>
                        </div>
                    `);
                    $('#assignmentForm').hide();
                    return;
                }

                let html = `
                    <table class="table table-sm table-hover align-middle mb-0 font-xs">
                        <thead class="table-light">
                            <tr>
                                <th width="40" class="text-center"><input type="checkbox" id="selectAllCheckbox" class="form-check-input m-0"></th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Designation</th>
                                <th>Status</th>
                                <th width="120" class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                employees.forEach(emp => {
                    let rowClass = '';
                    let statusBadge = '';
                    let disabled = '';
                    let actionButtons = '';

                    if (emp.status !== 'active') {
                        rowClass = 'text-muted';
                        statusBadge = `<span class="badge bg-secondary font-xxs px-2 py-0.5">${emp.status ? emp.status.toUpperCase() : 'INACTIVE'}</span>`;
                        disabled = 'disabled';
                        actionButtons = '<small class="text-muted">N/A</small>';
                    } else if (emp.is_already_assigned) {
                        rowClass = 'already-assigned';
                        statusBadge = '<span class="badge bg-warning text-dark font-xxs px-2 py-0.5">Already Assigned</span>';
                        disabled = 'disabled';
                        actionButtons = `
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm btn-outline-danger py-0 px-1.5" onclick="unassignEmployee(${emp.id}, '${emp.first_name} ${emp.last_name || ''}')" title="Unassign">
                                    <i class="fa fa-user-times"></i>
                                </button>
                                <a href="${'{{ route('hr.salary-structure.edit-individual', ':empId') }}'.replace(':empId', emp.id)}" class="btn btn-sm btn-outline-info py-0 px-1.5" title="Edit Individual">
                                    <i class="fa fa-edit"></i>
                                </a>
                            </div>
                        `;
                    } else if (emp.has_other_structure) {
                        rowClass = 'has-other-structure';
                        statusBadge = '<span class="badge bg-info-subtle text-info font-xxs px-2 py-0.5">Has Other Structure</span>';
                        actionButtons = '<small class="text-muted">Assign to replace</small>';
                    } else {
                        statusBadge = '<span class="badge bg-success-subtle text-success font-xxs px-2 py-0.5">Available</span>';
                        actionButtons = '<small class="text-muted">Select to assign</small>';
                    }

                    html += `
                        <tr class="employee-row ${rowClass}">
                            <td class="text-center"><input type="checkbox" class="employee-checkbox form-check-input m-0" value="${emp.id}" ${disabled}></td>
                            <td>
                                <strong class="text-dark">${emp.first_name} ${emp.last_name || ''}</strong>
                                ${emp.status !== 'active' ? '<br><small class="text-danger font-xxs"><i class="fa fa-exclamation-triangle"></i> Not Active</small>' : ''}
                            </td>
                            <td>${emp.department ? emp.department.name : 'N/A'}</td>
                            <td>${emp.designation ? emp.designation.name : 'N/A'}</td>
                            <td>${statusBadge}</td>
                            <td class="text-end pe-3">${actionButtons}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
                $('#employeesContainer').html(html);
                $('#emptyState').hide();
                updateSelectedCount();
            }

            // Select All / Clear All
            $(document).on('change', '#selectAllCheckbox', function() {
                $('.employee-checkbox:not(:disabled)').prop('checked', this.checked);
                updateSelectedCount();
            });

            $('#selectAllBtn').click(function() {
                $('.employee-checkbox:not(:disabled)').prop('checked', true);
                $('#selectAllCheckbox').prop('checked', true);
                updateSelectedCount();
            });

            $('#clearAllBtn').click(function() {
                $('.employee-checkbox').prop('checked', false);
                $('#selectAllCheckbox').prop('checked', false);
                updateSelectedCount();
            });

            $(document).on('change', '.employee-checkbox', function() {
                updateSelectedCount();
            });

            function updateSelectedCount() {
                const count = $('.employee-checkbox:checked').length;
                $('#selectedCount').text(count);

                if (count > 0) {
                    $('#assignmentForm').slideDown(150);

                    let html = '';
                    $('.employee-checkbox:checked').each(function() {
                        html += `<input type="hidden" name="employee_ids[]" value="${$(this).val()}">`;
                    });
                    $('#selectedEmployeesInputs').html(html);
                } else {
                    $('#assignmentForm').slideUp(150);
                }
            }

            // Submit assignment
            $('#assignmentForm').submit(function(e) {
                e.preventDefault();

                const count = $('.employee-checkbox:checked').length;
                if (count === 0) {
                    Swal.fire('Error', 'Please select at least one employee', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Confirm Assignment',
                    html: `Assign this salary structure to <strong>${count}</strong> employee(s)?<br><small class="text-muted">Previous active structures will be ended automatically.</small>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Assign',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Processing Assignment...',
                            html: 'Applying salary structures to selected employees.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        performAssignment();
                    }
                });
            });

            function performAssignment() {
                const btn = $('#assignBtn');
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Assigning...');

                $.ajax({
                    url: '{{ route('hr.salary-structure.assign', $salaryStructure->id) }}',
                    method: 'POST',
                    data: $('#assignmentForm').serialize(),
                    success: function(response) {
                        let message = response.success;
                        if (response.skipped && response.skipped.length > 0) {
                            message += '<br><br><strong>Skipped Employees:</strong><ul class="text-start">';
                            response.skipped.forEach(skip => {
                                message += `<li><span class="text-danger fw-bold">${skip.name}</span>: ${skip.reason}</li>`;
                            });
                            message += '</ul>';
                        }

                        let assignedHtml = '';
                        if (response.assigned_count > 0) {
                            assignedHtml = `<div class="alert alert-success mt-2 py-1.5 font-xs"><i class="fa fa-check-circle me-1"></i> Successfully Assigned: ${response.assigned_count}</div>`;
                        }

                        Swal.fire({
                            title: 'Assignment Complete',
                            html: `${assignedHtml} ${message}`,
                            icon: 'success',
                            confirmButtonText: 'View Assigned List',
                            confirmButtonColor: '#10b981'
                        }).then(() => {
                            window.location.href = response.redirect;
                        });
                    },
                    error: function(xhr) {
                        let errorMsg = 'Assignment failed';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        }
                        Swal.fire('Error', errorMsg, 'error');
                        btn.prop('disabled', false).html('<i class="fa fa-check-circle me-1"></i> Confirm Assignment');
                    }
                });
            }

            // Unassign employee function
            window.unassignEmployee = function(employeeId, employeeName) {
                Swal.fire({
                    title: 'Unassign Employee?',
                    html: `Remove salary structure assignment from <strong>${employeeName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Unassign',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('hr.salary-structure.remove-assignment', ['salaryStructure' => $salaryStructure->id, 'employee' => ':empId']) }}'
                                .replace(':empId', employeeId),
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                _method: 'DELETE'
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: 'Unassigned!',
                                    text: response.success,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    $('#fetchEmployeesBtn').click();
                                });
                            },
                            error: function(xhr) {
                                let errorMsg = 'Failed to unassign employee';
                                if (xhr.responseJSON && xhr.responseJSON.error) {
                                    errorMsg = xhr.responseJSON.error;
                                }
                                Swal.fire('Error', errorMsg, 'error');
                            }
                        });
                    }
                });
            };
        });
    </script>
@endsection
