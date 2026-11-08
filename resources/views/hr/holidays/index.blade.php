@extends('layouts.app')

@section('title', 'Holidays Calendar - Caltex Rao Filling Station')
@section('page_title', 'Holidays Calendar')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-calendar-event me-1 text-primary"></i> Year: {{ $year }}
    </span>
@endsection

@section('page_actions')
    <div class="d-flex align-items-center gap-2">
        <select id="yearSelect" class="form-select form-select-sm" style="width: 100px; height: 34px;">
            @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        @can('hr.holidays.create')
            <button type="button" class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm text-nowrap" id="createBtn">
                <i class="bi bi-plus-circle me-1"></i> Add Holiday
            </button>
        @endcan
    </div>
@endsection

@section('content')
    @include('hr.partials.hr-styles')

    <style>
        .holiday-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .holiday-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }

        .holiday-header {
            padding: 10px 14px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .holiday-header.public {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }

        .holiday-header.company {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
        }

        .holiday-header.optional {
            background: linear-gradient(135deg, #d97706, #b45309);
        }

        .holiday-body {
            padding: 16px 14px;
            text-align: center;
        }

        .holiday-date-big {
            font-size: 2.2rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
        }

        .holiday-month {
            font-size: 0.9rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
        }

        .holiday-day {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 2px;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid px-0">
                <!-- Stats Row -->
                @php
                    $allHolidays = \App\Models\Hr\Holiday::whereYear('date', $year)->get();
                    $publicCount = $allHolidays->where('type', 'public')->count();
                    $companyCount = $allHolidays->where('type', 'company')->count();
                    $optionalCount = $allHolidays->where('type', 'optional')->count();
                @endphp
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa fa-calendar-alt"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-value">{{ $holidays->total() }}</div>
                            <div class="stat-label">Total Holidays</div>
                        </div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-icon"><i class="fa fa-flag"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-value">{{ $publicCount }}</div>
                            <div class="stat-label">Public</div>
                        </div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-building"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-value">{{ $companyCount }}</div>
                            <div class="stat-label">Company</div>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-question-circle"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-value">{{ $optionalCount }}</div>
                            <div class="stat-label">Optional</div>
                        </div>
                    </div>
                </div>

                <!-- Holidays Card -->
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="holidaySearch" placeholder="Search holidays...">
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary btn-sm" id="refreshBtn"><i
                                        class="fa fa-sync"></i></button>
                            </div>
                        </div>
                        <span class="text-muted small" id="holidayCount">{{ $holidays->total() }} holidays in
                            {{ $year }}</span>
                    </div>

                    <div class="hr-grid" id="holidayGrid">
                        @forelse($holidays as $holiday)
                            <div class="holiday-card" data-id="{{ $holiday->id }}"
                                data-name="{{ strtolower($holiday->name) }}">
                                <div class="holiday-header {{ $holiday->type }}">
                                    <strong>{{ $holiday->name }}</strong>
                                    <div class="hr-actions">
                                        @can('hr.holidays.edit')
                                            <button class="btn btn-sm text-white assign-btn"
                                                style="background: linear-gradient(135deg, #0ea5e9, #3b82f6); border: none; margin-right: 4px;"
                                                data-id="{{ $holiday->id }}" data-name="{{ $holiday->name }}"
                                                data-employees="{{ json_encode($holiday->employees->pluck('id')) }}"
                                                title="Assign Employees">
                                                <i class="fa fa-users"></i>
                                            </button>
                                            <button class="btn btn-sm text-white edit-btn"
                                                style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none; margin-right: 4px;"
                                                data-id="{{ $holiday->id }}" data-name="{{ $holiday->name }}"
                                                data-date="{{ $holiday->date->format('Y-m-d') }}"
                                                data-end_date="{{ $holiday->end_date ? $holiday->end_date->format('Y-m-d') : '' }}"
                                                data-type="{{ $holiday->type }}"
                                                data-description="{{ $holiday->description }}">
                                                <i class="fa fa-pen"></i>
                                            </button>
                                        @endcan
                                        @can('hr.holidays.delete')
                                            <button class="btn btn-sm text-white delete-btn"
                                                style="background: linear-gradient(135deg, #ef4444, #dc2626); border: none;"
                                                data-id="{{ $holiday->id }}" title="Delete Holiday">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                                <div class="holiday-body">
                                    <div class="holiday-date-big">{{ $holiday->date->format('d') }}
                                        {{ $holiday->end_date && $holiday->end_date->format('Y-m-d') != $holiday->date->format('Y-m-d') ? '- ' . $holiday->end_date->format('d') : '' }}
                                    </div>
                                    <div class="holiday-month">{{ $holiday->date->format('F') }}</div>
                                    <div class="holiday-day">{{ $holiday->date->format('l') }}</div>
                                    @if ($holiday->description)
                                        <p class="text-muted small mt-3 mb-0">{{ $holiday->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="empty-state" style="grid-column: 1/-1;">
                                <i class="fa fa-calendar-times"></i>
                                <p>No holidays defined for {{ $year }}. Add your first!</p>
                            </div>
                        @endforelse
                    </div>
                    <div class="px-4 py-3 border-top">
                        {{ $holidays->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="holidayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #ef4444, #dc2626) !important;">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fa fa-calendar-alt"></i>
                        <span>Add Holiday</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="holidayForm" action="{{ route('hr.holidays.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-tag"></i> Holiday Name</label>
                            <input type="text" name="name" id="name" class="form-control"
                                placeholder="e.g., Eid ul Fitr" required>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-calendar"></i> Start Date</label>
                            <input type="date" name="date" id="date" class="form-control" required>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-calendar"></i> End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                            <small class="text-muted">Leave blank for a single day holiday.</small>
                        </div>

                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-tag"></i> Type</label>
                            <select name="type" id="type" class="form-select" required>
                                <option value="public">Public Holiday</option>
                                <option value="company">Company Holiday</option>
                                <option value="optional">Optional Holiday</option>
                            </select>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-align-left"></i> Description</label>
                            <textarea name="description" id="description" class="form-control" rows="2"
                                placeholder="Optional description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save" id="saveHolidayBtn"
                            style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <i class="fa fa-check"></i>
                            <span>Save Holiday</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header gradient py-3 px-4"
                    style="background: linear-gradient(135deg, #0ea5e9, #2563eb) !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white bg-opacity-25 rounded-3 p-2 text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa fa-user-check fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title text-white fw-bold mb-0">Assign Employees</h5>
                            <small class="text-white-50 fs-7">Holiday: <span id="assignHolidayName" class="fw-bold text-white"></span></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="assignForm" action="#" method="POST">
                    @csrf
                    <div class="modal-body p-4" style="background: #f8fafc;">
                        <!-- Filter Bar -->
                        <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius: 12px; background: white;">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 8px 0 0 8px;">
                                            <i class="fa fa-search"></i>
                                        </span>
                                        <input type="text" id="employeeSearch" class="form-control bg-light border-start-0 ps-0"
                                            placeholder="Search by name, email..." style="border-radius: 0 8px 8px 0;">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select id="filterDepartment" class="form-select bg-light" style="border-radius: 8px;">
                                        <option value="">All Departments</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ strtolower($dept->name) }}">{{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select id="filterDesignation" class="form-select bg-light" style="border-radius: 8px;">
                                        <option value="">All Designations</option>
                                        @foreach ($designations as $desig)
                                            <option value="{{ strtolower($desig->name) }}">{{ $desig->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Selection Summary & Quick Actions -->
                        <div class="d-flex justify-content-between align-items-center px-1 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge px-3 py-2 fs-7 fw-bold" id="selectedCounterBadge" style="background: #e0f2fe; color: #0284c7; border-radius: 20px;">
                                    <i class="fa fa-users me-1"></i> <span id="selectedCountText">0 Selected (Universal - All)</span>
                                </span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-light border text-secondary fw-semibold" id="btnSelectAllVisible" style="border-radius: 8px;">
                                    <i class="fa fa-check-square me-1"></i> Select Visible
                                </button>
                                <button type="button" class="btn btn-sm btn-light border text-secondary fw-semibold" id="btnDeselectAll" style="border-radius: 8px;">
                                    <i class="fa fa-square me-1"></i> Clear Selection
                                </button>
                            </div>
                        </div>

                        <!-- Employee Table Container -->
                        <div class="assign-table-container shadow-sm" style="max-height: 380px; overflow-y: auto;">
                            <table class="assign-emp-table align-middle mb-0" id="employeeTable">
                                <thead>
                                    <tr>
                                        <th style="width: 48px;" class="text-center">
                                            <div class="custom-chk-wrapper">
                                                <input type="checkbox" id="selectAllEmployees" class="custom-chk-input">
                                            </div>
                                        </th>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Designation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($employees as $emp)
                                        @php
                                            $firstName = $emp->first_name ?? '';
                                            $lastName = $emp->last_name ?? '';
                                            $fullName = trim($firstName . ' ' . $lastName);
                                            $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                                            if (empty($initials)) {
                                                $initials = strtoupper(substr($fullName, 0, 2));
                                            }
                                        @endphp
                                        <tr class="employee-row"
                                            data-name="{{ strtolower($fullName) }}"
                                            data-email="{{ strtolower($emp->email ?? '') }}"
                                            data-department="{{ strtolower($emp->department->name ?? '') }}"
                                            data-designation="{{ strtolower($emp->designation->name ?? '') }}">
                                            <td class="text-center">
                                                <div class="custom-chk-wrapper">
                                                    <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                                        class="custom-chk-input emp-checkbox">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="emp-avatar-sm">{{ $initials }}</div>
                                                    <div>
                                                        <div class="fw-bold text-dark mb-0 fs-6">{{ $fullName }}</div>
                                                        <div class="text-muted small fs-7">{{ $emp->email ?? 'No email' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge-soft-info">{{ $emp->department->name ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <span class="badge-soft-secondary">{{ $emp->designation->name ?? 'N/A' }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr id="noEmployeeRow" style="display: none;">
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="fa fa-user-slash fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                            No employees match the selected filters
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Banner Info -->
                        <div class="alert alert-light border shadow-sm mt-3 mb-0 d-flex align-items-center gap-2 p-2 px-3" style="border-radius: 10px;">
                            <i class="fa fa-info-circle text-primary fs-5"></i>
                            <span class="small text-muted mb-0">
                                <strong>Note:</strong> Leave all unchecked to apply universally to <strong>all employees</strong>. Check specific employees to assign only to selected ones.
                            </span>
                        </div>
                    </div>
                    <div class="modal-footer-modern bg-white">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save shadow-sm" id="saveAssignBtn"
                            style="background: linear-gradient(135deg, #0ea5e9, #2563eb);">
                            <i class="fa fa-check me-1"></i>
                            <span>Save Assignments</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {

            $('#yearSelect').change(function() {
                window.location.href = '{{ route('hr.holidays.index') }}?year=' + $(this).val();
            });

            $('#createBtn').click(function() {
                $('#holidayForm')[0].reset();
                $('#edit_id').val('');
                $('#modalTitle').html('<i class="fa fa-calendar-alt"></i><span>Add Holiday</span>');
                $('#holidayModal').modal('show');
            });

            $(document).on('click', '.edit-btn', function() {
                $('#edit_id').val($(this).data('id'));
                $('#name').val($(this).data('name'));
                $('#date').val($(this).data('date'));
                $('#end_date').val($(this).data('end_date'));
                $('#type').val($(this).data('type'));
                $('#description').val($(this).data('description'));
                $('#modalTitle').html('<i class="fa fa-pen"></i><span>Edit Holiday</span>');
                $('#holidayModal').modal('show');
            });

            $(document).on('click', '.assign-btn', function() {
                var holidayId = $(this).data('id');
                var holidayName = $(this).data('name');
                var assignedEmpIds = $(this).data('employees');

                $('#assignHolidayName').text(holidayName);

                // Uncheck all first
                $('.emp-checkbox').prop('checked', false);

                // Check previously assigned
                if (assignedEmpIds && assignedEmpIds.length > 0) {
                    assignedEmpIds.forEach(function(id) {
                        $('.emp-checkbox[value="' + id + '"]').prop('checked', true);
                    });
                }

                updateSelectAllCheckbox();
                $('#assignForm').attr('action', '/hr/holidays/' + holidayId + '/assign-employees');
                $('#employeeSearch').val('');
                $('#filterDepartment').val('');
                $('#filterDesignation').val('');
                filterEmployees(); // resetting filters
                $('#assignModal').modal('show');
            });

            function filterEmployees() {
                var search = $('#employeeSearch').val().toLowerCase();
                var dept = $('#filterDepartment').val();
                var desig = $('#filterDesignation').val();
                var visibleCount = 0;

                $('.employee-row').each(function() {
                    var matchName = $(this).data('name').indexOf(search) > -1;
                    var matchEmail = $(this).data('email').indexOf(search) > -1;
                    var matchSearch = search === '' || matchName || matchEmail;

                    var matchDept = dept === '' || $(this).data('department') === dept;
                    var matchDesig = desig === '' || $(this).data('designation') === desig;

                    if (matchSearch && matchDept && matchDesig) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });

                if (visibleCount === 0) {
                    $('#noEmployeeRow').show();
                } else {
                    $('#noEmployeeRow').hide();
                }

                updateSelectAllCheckbox();
            }

            $('#employeeSearch').on('input', filterEmployees);
            $('#filterDepartment, #filterDesignation').on('change', filterEmployees);

            // Row-level Click Selection Handler
            $(document).on('click', '.employee-row', function(e) {
                if ($(e.target).is('input[type="checkbox"]') || $(e.target).closest('.custom-chk-wrapper').length) {
                    // Handled directly by checkbox change
                    return;
                }
                var $chk = $(this).find('.emp-checkbox');
                $chk.prop('checked', !$chk.prop('checked')).trigger('change');
            });

            $('#selectAllEmployees').change(function() {
                var isChecked = $(this).prop('checked');
                $('.employee-row:visible .emp-checkbox').prop('checked', isChecked).trigger('change');
            });

            $('#btnSelectAllVisible').click(function() {
                $('.employee-row:visible .emp-checkbox').prop('checked', true).trigger('change');
            });

            $('#btnDeselectAll').click(function() {
                $('.emp-checkbox').prop('checked', false).trigger('change');
                $('#selectAllEmployees').prop('checked', false);
            });

            $(document).on('change', '.emp-checkbox', function() {
                updateSelectAllCheckbox();
            });

            function updateSelectAllCheckbox() {
                var visibleRows = $('.employee-row:visible').length;
                var checkedVisibleRows = $('.employee-row:visible .emp-checkbox:checked').length;
                var totalChecked = $('.emp-checkbox:checked').length;

                // Sync Row Selected Class
                $('.employee-row').each(function() {
                    var isChecked = $(this).find('.emp-checkbox').prop('checked');
                    if (isChecked) {
                        $(this).addClass('selected-row');
                    } else {
                        $(this).removeClass('selected-row');
                    }
                });

                // Update Counter Badge
                if (totalChecked === 0) {
                    $('#selectedCounterBadge').css({'background': '#e0f2fe', 'color': '#0284c7'});
                    $('#selectedCountText').text('0 Selected (Universal - All Employees)');
                } else {
                    $('#selectedCounterBadge').css({'background': '#dbeafe', 'color': '#1e40af'});
                    $('#selectedCountText').text(totalChecked + ' Employee(s) Selected');
                }

                // Update Select All Checkbox header state
                if (visibleRows > 0 && visibleRows === checkedVisibleRows) {
                    $('#selectAllEmployees').prop('checked', true);
                } else {
                    $('#selectAllEmployees').prop('checked', false);
                }
            }

            $(document).on('click', '.delete-btn', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Holiday?',
                    text: 'This cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/hr/holidays/' + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: response.success,
                                        icon: 'success',
                                        confirmButtonColor: '#3b82f6'
                                    }).then(() => location.reload());
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'There was a problem deleting the holiday.',
                                    icon: 'error',
                                    confirmButtonColor: '#ef4444'
                                });
                            }
                        });
                    }
                });
            });

            $('#holidaySearch').on('input', function() {
                var q = $(this).val().toLowerCase();
                $('.holiday-card').each(function() {
                    var name = $(this).data('name') || '';
                    $(this).toggle(name.indexOf(q) !== -1);
                });
                $('#holidayCount').text($('.holiday-card:visible').length + ' holidays');
            });

            $('#refreshBtn').click(() => location.reload());

            // AJAX Submit for Holiday Form
            $('#holidayForm').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitBtn = $('#saveHolidayBtn');
                var originalHtml = submitBtn.html();

                submitBtn.html('<i class="fa fa-spinner fa-spin"></i> <span>Saving...</span>').prop(
                    'disabled', true);

                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: form.serialize(),
                    success: function(response) {
                        submitBtn.html(originalHtml).prop('disabled', false);

                        if (response.success) {
                            $('#holidayModal').modal('hide');
                            Swal.fire({
                                title: 'Success!',
                                text: response.success,
                                icon: 'success',
                                confirmButtonColor: '#10b981',
                                iconColor: '#10b981',
                                backdrop: `rgba(0,0,0,0.4)`
                            }).then(() => {
                                if (response.reload) location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        submitBtn.html(originalHtml).prop('disabled', false);

                        var errorMsg = 'An unexpected error occurred.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var firstError = Object.values(xhr.responseJSON.errors)[0][0];
                            errorMsg = firstError;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            title: 'Failed to Save',
                            text: errorMsg,
                            icon: 'error',
                            confirmButtonColor: '#ef4444',
                            iconColor: '#ef4444',
                            backdrop: `rgba(0,0,0,0.4)`
                        });
                    }
                });
            });

            // AJAX Submit for Assign Employees Form
            $('#assignForm').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitBtn = $('#saveAssignBtn');
                var originalHtml = submitBtn.html();

                submitBtn.html('<i class="fa fa-spinner fa-spin"></i> <span>Assigning...</span>').prop(
                    'disabled', true);

                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: form.serialize(),
                    success: function(response) {
                        submitBtn.html(originalHtml).prop('disabled', false);

                        if (response.success) {
                            $('#assignModal').modal('hide');
                            Swal.fire({
                                title: 'Assigned Successfully!',
                                text: response.success,
                                icon: 'success',
                                confirmButtonColor: '#3b82f6',
                                iconColor: '#3b82f6',
                                backdrop: `rgba(0,0,0,0.4)`
                            }).then(() => {
                                if (response.reload) location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        submitBtn.html(originalHtml).prop('disabled', false);

                        var errorMsg = 'An unexpected error occurred.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var firstError = Object.values(xhr.responseJSON.errors)[0][0];
                            errorMsg = firstError;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            title: 'Assignment Failed',
                            text: errorMsg,
                            icon: 'error',
                            confirmButtonColor: '#ef4444',
                            iconColor: '#ef4444',
                            backdrop: `rgba(0,0,0,0.4)`
                        });
                    }
                });
            });
        });
    </script>
@endsection
