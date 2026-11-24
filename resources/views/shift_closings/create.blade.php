@extends('layouts.app')

@section('title', 'Petrol Pump Shift Closing')
@section('page_title', 'Petrol Pump Shift Closing Sheet')

@section('page_badge')
    <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-0.5 small" style="font-size: 0.72rem;">
        <i class="bi bi-fuel-pump me-1"></i> Multi-Dispenser Meter Reading &amp; Cash Reconciliation
    </span>
@endsection

@section('page_actions')
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('reports.daily-whatsapp') }}" target="_blank" class="btn btn-outline-success btn-sm py-0.5 px-2 rounded-2" style="font-size: 0.75rem;">
            <i class="bi bi-whatsapp me-1"></i> WhatsApp Report
        </a>
        <a href="{{ route('shift-closings.index') }}" class="btn btn-outline-secondary btn-sm py-0.5 px-2 rounded-2" style="font-size: 0.75rem;">
            <i class="bi bi-arrow-left me-1"></i> History
        </a>
    </div>
@endsection

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

    body, body *, input, button, select, textarea, .font-monospace, code, kbd, samp, pre {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
        letter-spacing: normal !important;
    }
    .bi, [class^="bi-"], [class*=" bi-"] {
        font-family: "bootstrap-icons" !important;
    }

    /* Compact Cards */
    .closing-card {
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        padding: 0.55rem 0.85rem;
        margin-bottom: 0.65rem;
    }
    .closing-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 0.35rem;
        margin-bottom: 0.45rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .closing-step-num {
        width: 22px;
        height: 22px;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .closing-card-title {
        font-size: 0.82rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .compact-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        line-height: 1.2;
    }
    .compact-input {
        height: 30px !important;
        padding: 0.2rem 0.5rem !important;
        font-size: 0.8rem !important;
        border-radius: 5px !important;
    }
    .compact-addon {
        height: 30px !important;
        padding: 0.2rem 0.5rem !important;
        font-size: 0.75rem !important;
    }

    /* Top KPI Ribbon */
    .kpi-compact-card {
        background: #ffffff;
        border-radius: 6px;
        padding: 0.35rem 0.6rem;
        border-left: 3.5px solid;
        border-top: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        min-height: 54px;
    }
    .kpi-title {
        font-size: 0.62rem;
        letter-spacing: 0.4px;
        line-height: 1.1;
    }
    .kpi-value {
        font-size: 1.05rem;
        line-height: 1.2;
        margin-top: 1px;
    }

    .nozzle-row-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 0.45rem 0.65rem;
        margin-bottom: 0.45rem;
    }
    .dispenser-badge {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.15rem 0.45rem;
        border-radius: 4px;
    }

    /* Denomination Table */
    .denomination-table {
        table-layout: fixed;
        width: 100%;
        margin-bottom: 0.3rem;
    }
    .denomination-table th, .denomination-table td {
        padding: 0.18rem 0.4rem !important;
        vertical-align: middle;
        font-size: 0.78rem;
    }
    .note-input {
        height: 24px !important;
        font-size: 0.8rem !important;
        padding: 0.05rem 0.3rem !important;
        text-align: center;
        border-radius: 4px;
    }
    .note-input::-webkit-inner-spin-button,
    .note-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Hide spinner arrows on all amount and number inputs */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button,
    .no-spin::-webkit-inner-spin-button,
    .no-spin::-webkit-outer-spin-button {
        -webkit-appearance: none !important;
        margin: 0 !important;
    }
    input[type=number], .no-spin {
        -moz-appearance: textfield !important;
    }
</style>
@endpush

@section('content')
<form action="{{ route('shift-closings.store') }}" method="POST" id="shiftClosingForm" novalidate>
    @csrf

    <!-- Top Live KPI Ribbon (Instant Shift Position) -->
    <div class="row g-1.5 mb-2.5 row-cols-2 row-cols-md-3 row-cols-lg-6">
        <!-- KPI 1: Total Volume Dispensed -->
        <div class="col">
            <div class="kpi-compact-card border-start border-4 border-primary h-100">
                <div class="text-muted fw-semibold text-uppercase kpi-title text-truncate">Fuel Dispensed</div>
                <div class="fw-bold text-primary kpi-value text-truncate" id="kpi_liters">0.00 Ltr</div>
                <small class="text-secondary d-none d-sm-block" style="font-size: 0.62rem;">Net Sold Liters</small>
            </div>
        </div>

        <!-- KPI 2: Test Paimana Liters (Audio 2) -->
        <div class="col">
            <div class="kpi-compact-card border-start border-4 border-warning h-100">
                <div class="text-muted fw-semibold text-uppercase kpi-title text-truncate">Testing (Paimana)</div>
                <div class="fw-bold text-warning-emphasis kpi-value text-truncate" id="kpi_testing">0.00 Ltr</div>
                <small class="text-secondary d-none d-sm-block" style="font-size: 0.62rem;">Poured back into Tank</small>
            </div>
        </div>

        <!-- KPI 3: Total Gross Sales -->
        <div class="col">
            <div class="kpi-compact-card border-start border-4 border-info h-100">
                <div class="text-muted fw-semibold text-uppercase kpi-title text-truncate">Gross Total Sales</div>
                <div class="fw-bold text-dark kpi-value text-truncate" id="kpi_sales">Rs. 0.00</div>
                <small class="text-secondary d-none d-sm-block" style="font-size: 0.62rem;">Fuel + Lubricants</small>
            </div>
        </div>

        <!-- KPI 4: Expected Net Cash -->
        <div class="col">
            <div class="kpi-compact-card border-start border-4 border-secondary h-100">
                <div class="text-muted fw-semibold text-uppercase kpi-title text-truncate">Expected Cash</div>
                <div class="fw-bold text-dark kpi-value text-truncate" id="kpi_expected">Rs. 0.00</div>
                <small class="text-secondary d-none d-sm-block" style="font-size: 0.62rem;">Sale - Credit - Expense</small>
            </div>
        </div>

        <!-- KPI 5: Actual Received -->
        <div class="col">
            <div class="kpi-compact-card border-start border-4 border-success h-100">
                <div class="text-muted fw-semibold text-uppercase kpi-title text-truncate">Actual Collected</div>
                <div class="fw-bold text-success kpi-value text-truncate" id="kpi_actual">Rs. 0.00</div>
                <small class="text-secondary d-none d-sm-block" style="font-size: 0.62rem;">Cash + Digital + Subsidy</small>
            </div>
        </div>

        <!-- KPI 6: Shift Variance -->
        <div class="col col-12 col-md-12 col-lg">
            <div class="kpi-compact-card border-start border-4 border-secondary h-100" id="kpi_variance_card">
                <div class="d-flex justify-content-between align-items-center gap-1">
                    <span class="text-muted fw-semibold text-uppercase kpi-title text-truncate">Shift Variance</span>
                    <span class="badge bg-success small py-0 px-1" id="kpi_variance_badge" style="font-size: 0.62rem;">Balanced</span>
                </div>
                <div class="fw-bold text-dark kpi-value text-truncate" id="kpi_variance">Rs. 0.00</div>
                <small class="text-secondary d-none d-sm-block" id="kpi_variance_text" style="font-size: 0.62rem;">Actual minus Expected</small>
            </div>
        </div>
    </div>

    <!-- Step 1: Shift Session Setup -->
    <div class="closing-card">
        <div class="closing-card-header">
            <div class="d-flex align-items-center gap-1.5">
                <span class="badge bg-primary text-white closing-step-num">1</span>
                <h6 class="closing-card-title">Shift Session &amp; Cashier Setup</h6>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.68rem;">
                <i class="bi bi-infinity me-1"></i> Continuous Stations Workflow
            </span>
        </div>

        <div class="row g-2">
            <!-- Date -->
            <div class="col-12 col-sm-4">
                <label class="compact-label" for="closing_date">
                    <span><i class="bi bi-calendar3 text-primary me-1"></i>Closing Date <span class="text-danger">*</span></span>
                </label>
                <input type="date" id="closing_date" name="date"
                       class="form-control compact-input fw-semibold @error('date') is-invalid @enderror"
                       value="{{ old('date', date('Y-m-d')) }}"
                       min="{{ now()->subDays(2)->toDateString() }}" max="{{ now()->toDateString() }}" required>
                @error('date')
                    <div class="invalid-feedback d-block small" style="font-size: 0.68rem;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Shift Type -->
            <div class="col-12 col-sm-4">
                <label class="compact-label" for="shift_type_select">
                    <span><i class="bi bi-clock text-primary me-1"></i>Shift Cycle <span class="text-danger">*</span></span>
                </label>
                <select name="shift_type" id="shift_type_select" class="form-select compact-input fw-semibold @error('shift_type') is-invalid @enderror" required>
                    <option value="24_hours" {{ old('shift_type', '24_hours') === '24_hours' ? 'selected' : '' }}>🔄 24-Hours Full Cycle (9:00 AM - 9:00 AM)</option>
                    <option value="morning" {{ old('shift_type') === 'morning' ? 'selected' : '' }}>☀️ Morning Shift (9:00 AM - 5:00 PM)</option>
                    <option value="evening" {{ old('shift_type') === 'evening' ? 'selected' : '' }}>🌙 Evening Shift (5:00 PM - 1:00 AM)</option>
                    <option value="night" {{ old('shift_type') === 'night' ? 'selected' : '' }}>🌌 Night Shift (1:00 AM - 9:00 AM)</option>
                </select>
                @error('shift_type')
                    <div class="invalid-feedback d-block small" style="font-size: 0.68rem;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Cashier Selection -->
            <div class="col-12 col-sm-4">
                <label class="compact-label" for="cashier_id">
                    <span><i class="bi bi-person-badge text-primary me-1"></i>Shift Cashier <span class="text-danger">*</span></span>
                    <a href="{{ route('hr.employees.index') }}" target="_blank" class="text-primary text-decoration-none" style="font-size: 0.65rem;">+ Add / Manage</a>
                </label>
                <select name="cashier_id" id="cashier_id" class="form-select compact-input fw-semibold @error('cashier_id') is-invalid @enderror" required>
                    <option value="">-- Choose Cashier --</option>
                    @foreach($cashiers as $c)
                        <option value="{{ $c->id }}" {{ old('cashier_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->code ?: '#' . $c->id }})
                        </option>
                    @endforeach
                </select>
                @error('cashier_id')
                    <div class="invalid-feedback d-block small" style="font-size: 0.68rem;">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    @if(isset($recentRateRevisions) && $recentRateRevisions->isNotEmpty())
        <div class="alert alert-info border-info border-opacity-25 d-flex align-items-center justify-content-between p-2 mb-2 rounded-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-bell-fill text-primary fs-5"></i>
                <div class="small">
                    <strong>Official Fuel Rate Revision Active:</strong>
                    @foreach($recentRateRevisions as $rrr)
                        <span class="badge bg-white text-dark border ms-1">
                            {{ $rrr->product?->name }}: Rs. {{ number_format($rrr->old_sale_rate, 2) }} ➔ Rs. {{ number_format($rrr->new_sale_rate, 2) }} ({{ $rrr->formatted_difference }}/Ltr)
                        </span>
                    @endforeach
                    <span class="text-secondary d-block mt-0.5" style="font-size: 0.68rem;">If this 24-hr shift closed across midnight, turn on <strong>"Midnight 12:00 AM Rate Change"</strong> on affected nozzles below.</span>
                </div>
            </div>
            <a href="{{ route('rate-revisions.index') }}" class="btn btn-sm btn-outline-primary py-0 px-2 small" target="_blank">
                View Circulars
            </a>
        </div>
    @endif

    <!-- Step 2: Multi-Dispenser & Multi-Nozzle Meter Readings (Audio 1 & Audio 2) -->
    <div class="closing-card border-primary border-opacity-25" style="border-left: 3.5px solid #2563eb;">
        <div class="closing-card-header">
            <div class="d-flex align-items-center gap-1.5">
                <span class="badge bg-primary text-white closing-step-num">2</span>
                <h6 class="closing-card-title">
                    <i class="bi bi-speedometer2 text-primary me-1"></i> Dispensers &amp; Nozzles Meter Readings (Audio 1: All 3 Units)
                </h6>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-primary border" style="font-size: 0.68rem;">
                    Formula: Net Sold = (End - Start) - Testing Liters
                </span>
            </div>
        </div>

        <div class="d-flex flex-column gap-1.5">
            @php
                $groupedPumps = $pumps->groupBy(function($item) {
                    return $item->dispenser_unit ?: 'Dispenser Unit 1';
                });
            @endphp

            @foreach($groupedPumps as $dispenserUnit => $dispenserNozzles)
                <div class="border rounded-2 p-2 bg-white mb-2 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center pb-1 mb-1.5 border-bottom">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle dispenser-badge">
                            <i class="bi bi-fuel-pump me-1"></i>{{ $dispenserUnit }} ({{ $dispenserNozzles->count() }} Nozzles)
                        </span>
                        <span class="text-muted small" style="font-size: 0.68rem;">Dispenser Mechanical/Electronic Totalizers</span>
                    </div>

                    @foreach($dispenserNozzles as $nozzle)
                        @php
                            $nIndex = $nozzle->id;
                            $rate = $nozzle->product?->sale_price ?? 0;
                            $lastRead = $nozzle->last_reading ?? 0;
                        @endphp
                        <div class="nozzle-row-box" id="nozzle_box_{{ $nIndex }}">
                            <div class="row g-1.5 align-items-center">
                                <input type="hidden" name="nozzles[{{ $nIndex }}][pump_id]" value="{{ $nozzle->id }}">
                                <input type="hidden" name="nozzles[{{ $nIndex }}][tank_id]" value="{{ $nozzle->tank_id }}">
                                <input type="hidden" name="nozzles[{{ $nIndex }}][product_id]" value="{{ $nozzle->product_id }}">
                                <input type="hidden" name="nozzles[{{ $nIndex }}][nozzle_name]" value="{{ $nozzle->name }}">

                                <!-- Nozzle & Product Title -->
                                <div class="col-12 col-md-3">
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 0.78rem;">
                                        <i class="bi bi-droplet-fill text-primary me-1"></i>{{ $nozzle->name }}
                                    </div>
                                    <div class="text-secondary small d-flex gap-1.5" style="font-size: 0.65rem;">
                                        <span>Product: <strong class="text-dark">{{ $nozzle->product?->name }}</strong></span>
                                        <span>• Tank: <em>{{ $nozzle->tank?->tank_number ?: 'Tank' }}</em></span>
                                    </div>
                                </div>

                                <!-- Start Reading (Opening) -->
                                <div class="col-6 col-sm-4 col-md-2">
                                    <label class="compact-label">Start Reading <span class="text-muted" style="font-size: 0.6rem;">(Opening)</span></label>
                                    <input type="number" step="0.01" min="0"
                                           name="nozzles[{{ $nIndex }}][start_reading]"
                                           id="start_{{ $nIndex }}"
                                           class="form-control compact-input fw-semibold"
                                           value="{{ old("nozzles.{$nIndex}.start_reading", number_format($lastRead, 2, '.', '')) }}"
                                           oninput="calculateNozzleRow({{ $nIndex }})">
                                </div>

                                <!-- End Reading (Closing) -->
                                <div class="col-6 col-sm-4 col-md-2">
                                    <label class="compact-label">End Reading <span class="text-muted" style="font-size: 0.6rem;">(Closing)</span></label>
                                    <input type="number" step="0.01" min="0"
                                           name="nozzles[{{ $nIndex }}][end_reading]"
                                           id="end_{{ $nIndex }}"
                                           class="form-control compact-input fw-bold border-primary text-primary"
                                           value="{{ old("nozzles.{$nIndex}.end_reading", number_format($lastRead, 2, '.', '')) }}"
                                           placeholder="{{ number_format($lastRead, 2, '.', '') }}"
                                           oninput="calculateNozzleRow({{ $nIndex }})">
                                </div>

                                <!-- Testing / Paimana Liters (Audio 2) -->
                                <div class="col-6 col-sm-4 col-md-1.5" style="flex: 0 0 13%; max-width: 13%;">
                                    <label class="compact-label text-warning-emphasis" title="Test measure (paimana) liters poured back into tank">
                                        Test (Paimana) <i class="bi bi-info-circle text-muted" style="font-size: 0.6rem;"></i>
                                    </label>
                                    <input type="number" step="0.01" min="0"
                                           name="nozzles[{{ $nIndex }}][testing_liters]"
                                           id="testing_{{ $nIndex }}"
                                           class="form-control compact-input fw-semibold border-warning bg-warning bg-opacity-10"
                                           value="{{ old("nozzles.{$nIndex}.testing_liters", '0.00') }}"
                                           placeholder="0.00"
                                           oninput="calculateNozzleRow({{ $nIndex }})">
                                </div>

                                <!-- Rate / Ltr (Rs.) -->
                                <div class="col-6 col-sm-4 col-md-1.5" style="flex: 0 0 13%; max-width: 13%;">
                                    <label class="compact-label">Rate / Ltr</label>
                                    <input type="number" step="0.01" min="0"
                                           name="nozzles[{{ $nIndex }}][fuel_rate]"
                                           id="rate_{{ $nIndex }}"
                                           class="form-control compact-input fw-semibold"
                                           value="{{ old("nozzles.{$nIndex}.fuel_rate", number_format($rate, 2, '.', '')) }}"
                                           oninput="calculateNozzleRow({{ $nIndex }})">
                                </div>

                                <!-- Calculated Net Sale Liters & Subtotal Amount -->
                                <div class="col-12 col-md-2 text-md-end">
                                    <div class="fw-bold text-dark" style="font-size: 0.82rem;" id="subtotal_display_{{ $nIndex }}">Rs. 0.00</div>
                                    <div class="text-secondary small" style="font-size: 0.65rem;">
                                        Sold: <strong class="text-primary" id="liters_display_{{ $nIndex }}">0.00 Ltr</strong>
                                    </div>
                                    <input type="hidden" name="nozzles[{{ $nIndex }}][subtotal]" id="subtotal_val_{{ $nIndex }}" value="0.00">
                                </div>

                                <!-- Midnight Rate Change Switch -->
                                <div class="col-12 mt-1 pt-1 border-top d-flex align-items-center justify-content-between">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               id="rate_change_check_{{ $nIndex }}"
                                               name="nozzles[{{ $nIndex }}][has_rate_change]"
                                               value="1"
                                               onchange="toggleMidnightRateChange({{ $nIndex }})">
                                        <label class="form-check-label small fw-semibold text-secondary" for="rate_change_check_{{ $nIndex }}" style="font-size: 0.7rem; cursor: pointer;">
                                            <i class="bi bi-moon-stars text-primary me-1"></i> Midnight 12:00 AM Rate Change (Raat 12 Baje Rate Tabdeel Hua)
                                        </label>
                                    </div>
                                    <span class="badge bg-light text-secondary border small" id="split_badge_{{ $nIndex }}" style="font-size: 0.62rem; display: none;">
                                        Dual Rates Active (Pre-12 AM + Post-12 AM)
                                    </span>
                                </div>

                                <!-- Collapsible Midnight Split Box -->
                                <div class="col-12" id="midnight_split_{{ $nIndex }}" style="display: none;">
                                    <div class="p-2 bg-light bg-opacity-75 rounded-2 border border-primary-subtle mt-1">
                                        <div class="row g-1.5 align-items-center">
                                            <!-- 12:00 AM Midnight Reading -->
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <label class="compact-label text-primary fw-bold">12:00 AM Midnight Reading <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0"
                                                       name="nozzles[{{ $nIndex }}][midnight_reading]"
                                                       id="midnight_{{ $nIndex }}"
                                                       class="form-control compact-input fw-bold border-primary text-primary"
                                                       placeholder="Midnight Reading"
                                                       oninput="calculateNozzleRow({{ $nIndex }})">
                                                <small class="text-secondary" style="font-size: 0.6rem;">Reading recorded at 12:00 AM</small>
                                            </div>

                                            <!-- Pre-Midnight Rate -->
                                            <div class="col-6 col-sm-3 col-md-2">
                                                <label class="compact-label text-secondary">Old Rate (Pre-12 AM)</label>
                                                <input type="number" step="0.01" min="0"
                                                       name="nozzles[{{ $nIndex }}][old_fuel_rate]"
                                                       id="old_rate_{{ $nIndex }}"
                                                       class="form-control compact-input fw-semibold"
                                                       value="{{ old("nozzles.{$nIndex}.old_fuel_rate", number_format($rate, 2, '.', '')) }}"
                                                       oninput="calculateNozzleRow({{ $nIndex }})">
                                            </div>

                                            <!-- Post-Midnight Rate -->
                                            <div class="col-6 col-sm-3 col-md-2">
                                                <label class="compact-label text-success fw-bold">New Rate (Post-12 AM)</label>
                                                <input type="number" step="0.01" min="0"
                                                       name="nozzles[{{ $nIndex }}][new_fuel_rate]"
                                                       id="new_rate_{{ $nIndex }}"
                                                       class="form-control compact-input fw-bold border-success text-success"
                                                       value="{{ old("nozzles.{$nIndex}.new_fuel_rate", number_format($rate, 2, '.', '')) }}"
                                                       oninput="calculateNozzleRow({{ $nIndex }})">
                                            </div>

                                            <!-- Split Calculation Breakdown Display -->
                                            <div class="col-12 col-md-5">
                                                <div class="p-1.5 bg-white rounded border small" style="font-size: 0.65rem;">
                                                    <div class="d-flex justify-content-between text-secondary">
                                                        <span>Pre-12 AM (Old):</span>
                                                        <strong class="text-dark" id="pre_calc_{{ $nIndex }}">0.00 Ltr @ Rs. 0.00 = Rs. 0.00</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between text-secondary mt-0.5">
                                                        <span>Post-12 AM (New):</span>
                                                        <strong class="text-success" id="post_calc_{{ $nIndex }}">0.00 Ltr @ Rs. 0.00 = Rs. 0.00</strong>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="nozzles[{{ $nIndex }}][old_rate_liters]" id="old_ltr_{{ $nIndex }}" value="0.00">
                                                <input type="hidden" name="nozzles[{{ $nIndex }}][new_rate_liters]" id="new_ltr_{{ $nIndex }}" value="0.00">
                                                <input type="hidden" name="nozzles[{{ $nIndex }}][old_rate_subtotal]" id="old_sub_{{ $nIndex }}" value="0.00">
                                                <input type="hidden" name="nozzles[{{ $nIndex }}][new_rate_subtotal]" id="new_sub_{{ $nIndex }}" value="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <!-- Total Fuel Summary Banner -->
        <div class="p-2 bg-light rounded-2 border d-flex flex-wrap justify-content-between align-items-center mt-1">
            <span class="small fw-semibold text-secondary">
                Total Fuel Dispensed: <strong class="text-primary" id="disp_total_fuel_liters">0.00 Ltr</strong> &bull;
                Total Test (Paimana): <strong class="text-warning-emphasis" id="disp_total_testing_liters">0.00 Ltr</strong>
            </span>
            <span class="fw-bold text-dark fs-6">
                Total Fuel Revenue: <span class="text-success" id="disp_total_fuel_sales">Rs. 0.00</span>
            </span>
        </div>
    </div>

    <!-- Main Workspace (2-Column Balanced Grid) -->
    <div class="row g-2">
        <!-- LEFT COLUMN: Lubricant Sales, Credit Sales, Expenses -->
        <div class="col-12 col-lg-6">
            <div class="d-flex flex-column gap-2">

                <!-- Step 3: Engine Oil / Lubricants Counter Sales (Audio 6: Caltex Havoline, etc.) -->
                <div class="closing-card border-info border-opacity-25" style="border-left: 3.5px solid #0284c7;">
                    <div class="closing-card-header">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge bg-info text-white closing-step-num">3</span>
                            <h6 class="closing-card-title">
                                <i class="bi bi-box-seam text-info me-1"></i> Lubricants / Engine Oil Sales (Audio 6: Havoline &amp; Cans)
                            </h6>
                        </div>
                        <button type="button" class="btn btn-outline-info btn-sm rounded-pill py-0.5 px-2" style="font-size: 0.72rem;" onclick="addLubricantRow()">
                            <i class="bi bi-plus-circle me-1"></i> Add Oil Sale
                        </button>
                    </div>

                    <div id="lubricantSalesContainer" class="d-flex flex-column gap-1.5">
                        <!-- Dynamic Lubricant Rows -->
                    </div>

                    <div class="p-1.5 px-2 bg-light rounded-2 mt-1 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-secondary" style="font-size: 0.72rem;">Total Lubricants Sale Revenue:</span>
                        <strong class="text-info fw-bold" id="display_lubricant_total" style="font-size: 0.82rem;">Rs. 0.00</strong>
                    </div>
                </div>

                <!-- Step 4: Credit / Udhar Fuel Sales to Parties (Audio 8: Party Khata) -->
                <div class="closing-card border-warning border-opacity-25" style="border-left: 3.5px solid #d97706;">
                    <div class="closing-card-header">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge bg-warning text-dark closing-step-num">4</span>
                            <h6 class="closing-card-title">
                                <i class="bi bi-journal-arrow-down text-warning-emphasis me-1"></i> Credit / Udhar Sales (Audio 8: Party Khata)
                            </h6>
                        </div>
                        <button type="button" class="btn btn-outline-warning text-dark btn-sm rounded-pill py-0.5 px-2" style="font-size: 0.72rem;" onclick="addCreditRow()">
                            <i class="bi bi-plus-circle me-1"></i> Add Credit (Udhar)
                        </button>
                    </div>

                    <div id="creditSalesContainer" class="d-flex flex-column gap-1.5">
                        <!-- Dynamic Credit Rows -->
                    </div>

                    <div class="p-1.5 px-2 bg-light rounded-2 mt-1 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-secondary" style="font-size: 0.72rem;">Total Credit Given (Deducted from Cash):</span>
                        <strong class="text-danger fw-bold" id="display_credit_total" style="font-size: 0.82rem;">-Rs. 0.00</strong>
                    </div>
                </div>

                <!-- Step 5: Counter Shift Expenses (Video Walkthrough: Multi-Item Categories) -->
                <div class="closing-card border-danger border-opacity-25" style="border-left: 3.5px solid #dc2626;">
                    <div class="closing-card-header">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge bg-danger text-white closing-step-num">5</span>
                            <h6 class="closing-card-title">
                                <i class="bi bi-wallet2 text-danger me-1"></i> Shift Counter Expenses (Video Walkthrough)
                            </h6>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill py-0.5 px-2" style="font-size: 0.72rem;" onclick="addExpenseRow()">
                            <i class="bi bi-plus-circle me-1"></i> Add Expense
                        </button>
                    </div>

                    <div id="counterExpensesContainer" class="d-flex flex-column gap-1.5">
                        <!-- Dynamic Expense Rows -->
                    </div>

                    <div class="p-1.5 px-2 bg-light rounded-2 mt-1 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-secondary" style="font-size: 0.72rem;">Total Counter Petty Cash Expenses:</span>
                        <strong class="text-danger fw-bold" id="display_expenses_total" style="font-size: 0.82rem;">-Rs. 0.00</strong>
                    </div>
                </div>

                <!-- Step 6: Digital, Card & Government Fuel Subsidy Collections (Audio 3) -->
                <div class="closing-card">
                    <div class="closing-card-header">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge bg-primary text-white closing-step-num">6</span>
                            <h6 class="closing-card-title">
                                <i class="bi bi-bank text-primary me-1"></i> Digital, Card &amp; Govt Subsidy Collections
                            </h6>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill py-0.5 px-2" style="font-size: 0.72rem;" id="addAccountPayment">
                            <i class="bi bi-plus-circle me-1"></i> Add Digital
                        </button>
                    </div>

                    <div id="accountPaymentRows" class="d-flex flex-column gap-1.5">
                        <!-- Dynamic Digital Rows -->
                    </div>

                    <!-- Government Subsidy Section (Audio 3) -->
                    <div class="p-2 border rounded-2 bg-light mt-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="small fw-bold text-dark"><i class="bi bi-phone text-success me-1"></i> Government Fuel Subsidy (Audio 3)</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">App / BISP Voucher</span>
                        </div>
                        <div class="row g-1.5 align-items-center">
                            <div class="col-12 col-sm-4">
                                <label class="compact-label" for="govt_subsidy_amount">Subsidy Amount (Rs.)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white compact-addon fw-bold text-success">Rs.</span>
                                    <input type="number" step="0.01" min="0" name="govt_subsidy_amount" id="govt_subsidy_amount"
                                           class="form-control compact-input fw-bold text-success no-spin"
                                           value="{{ old('govt_subsidy_amount', '0.00') }}" placeholder="0.00" oninput="calculateGrandTotals()">
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="compact-label" for="govt_subsidy_account_id">Receivable Account</label>
                                <select name="govt_subsidy_account_id" id="govt_subsidy_account_id" class="form-select compact-input">
                                    @if($subsidyAccount)
                                        <option value="{{ $subsidyAccount->id }}" selected>{{ $subsidyAccount->name }}</option>
                                    @endif
                                    @foreach($accounts as $acc)
                                        @if(!$subsidyAccount || $subsidyAccount->id !== $acc->id)
                                            <option value="{{ $acc->id }}">{{ $acc->name }} ({{ ucfirst($acc->type) }})</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="compact-label" for="govt_subsidy_remarks">Voucher Details / Notes</label>
                                <input type="text" name="govt_subsidy_remarks" id="govt_subsidy_remarks"
                                       class="form-control compact-input" placeholder="e.g. 5 App vouchers @ 500" value="{{ old('govt_subsidy_remarks') }}">
                            </div>
                        </div>
                    </div>

                    <div class="p-1.5 px-2 bg-light rounded-2 mt-1.5 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-secondary" style="font-size: 0.72rem;">Total Online &amp; Subsidy:</span>
                        <strong class="text-primary fw-bold" id="display_digital_total" style="font-size: 0.82rem;">Rs. 0.00</strong>
                    </div>
                </div>

                <!-- Operator Remarks -->
                <div class="closing-card">
                    <label class="compact-label mb-1" for="remarks">
                        <span><i class="bi bi-chat-text text-primary me-1"></i> Shift Operator Remarks / Notes</span>
                    </label>
                    <textarea class="form-control compact-input" id="remarks" name="remarks" rows="1"
                              style="height: 32px; resize: vertical;"
                              placeholder="Power outage, generator hours, dip check readings...">{{ old('remarks') }}</textarea>
                </div>

            </div>
        </div>

        <!-- RIGHT COLUMN: Physical Cash Denomination Counter & Variance -->
        <div class="col-12 col-lg-6">
            <div class="d-flex flex-column gap-2">

                <!-- Step 7: Cash Note Denominations -->
                <div class="closing-card border-success border-opacity-25" style="border-left: 3.5px solid #16a34a;">
                    <div class="closing-card-header">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge bg-success text-white closing-step-num">7</span>
                            <h6 class="closing-card-title">
                                <i class="bi bi-cash-stack text-success me-1"></i> Physical Cash Denomination Counter
                            </h6>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.65rem;">
                            Cash in Drawer
                        </span>
                    </div>

                    <table class="table table-sm denomination-table table-bordered align-middle mb-1">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 32%;">Note</th>
                                <th style="width: 33%;" class="text-center">Count</th>
                                <th style="width: 35%;" class="text-end">Total (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $notes = [
                                    ['name' => 'note_5000', 'value' => 5000, 'label' => 'Rs. 5,000'],
                                    ['name' => 'note_1000', 'value' => 1000, 'label' => 'Rs. 1,000'],
                                    ['name' => 'note_500',  'value' => 500,  'label' => 'Rs. 500'],
                                    ['name' => 'note_100',  'value' => 100,  'label' => 'Rs. 100'],
                                    ['name' => 'note_50',   'value' => 50,   'label' => 'Rs. 50'],
                                    ['name' => 'note_20',   'value' => 20,   'label' => 'Rs. 20'],
                                    ['name' => 'note_10',   'value' => 10,   'label' => 'Rs. 10'],
                                ];
                            @endphp

                            @foreach($notes as $note)
                                <tr>
                                    <td class="fw-bold text-dark py-0.5">{{ $note['label'] }}</td>
                                    <td class="py-0.5">
                                        <input type="number" min="0" name="{{ $note['name'] }}" id="{{ $note['name'] }}"
                                               class="form-control form-control-sm note-input fw-bold"
                                               value="{{ old($note['name'], 0) }}" data-multiplier="{{ $note['value'] }}"
                                               oninput="calculateDenominations()">
                                    </td>
                                    <td class="text-end fw-semibold text-secondary py-0.5" id="total_{{ $note['name'] }}">
                                        0.00
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Coins -->
                            <tr>
                                <td class="fw-bold text-dark py-0.5">Coins</td>
                                <td class="py-0.5">
                                    <input type="number" step="0.01" min="0" name="coins" id="coins"
                                           class="form-control form-control-sm note-input fw-bold"
                                           value="{{ old('coins', '0.00') }}" oninput="calculateDenominations()">
                                </td>
                                <td class="text-end fw-semibold text-secondary py-0.5" id="total_coins">
                                    0.00
                                </td>
                            </tr>

                            <!-- Counted Cash Total Row -->
                            <tr class="table-success bg-opacity-25">
                                <th colspan="2" class="fw-bold text-dark py-1">Total Counted Cash:</th>
                                <th class="text-end text-success fw-bold py-1" id="display_counted_cash">
                                    Rs. 0.00
                                </th>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Final Comprehensive Shift Reconciliation Card -->
                <div class="closing-card border-dark border-opacity-25">
                    <h6 class="fw-bold text-dark mb-1.5 pb-1 border-bottom" style="font-size: 0.8rem;">
                        <i class="bi bi-shield-check text-primary me-1"></i> Complete Shift Reconciliation
                    </h6>

                    <div class="d-flex justify-content-between p-1 px-2 bg-light rounded-2 small mb-1" style="font-size: 0.72rem;">
                        <span class="text-secondary">Gross Total Sales (Fuel + Oil):</span>
                        <strong class="text-dark fw-bold" id="summary_gross_sales">Rs. 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between p-1 px-2 bg-light rounded-2 small mb-1" style="font-size: 0.72rem;">
                        <span class="text-danger">Less Credit Sales (Party Khata):</span>
                        <strong class="text-danger fw-bold" id="summary_credit_sales">-Rs. 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between p-1 px-2 bg-light rounded-2 small mb-1" style="font-size: 0.72rem;">
                        <span class="text-danger">Less Counter Petty Expenses:</span>
                        <strong class="text-danger fw-bold" id="summary_expenses">-Rs. 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between p-1 px-2 bg-warning-subtle rounded-2 small mb-1" style="font-size: 0.72rem;">
                        <span class="text-warning-emphasis fw-bold">Net Expected Cash to Submit:</span>
                        <strong class="text-warning-emphasis fw-bold fs-6" id="summary_expected">Rs. 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between p-1 px-2 bg-light rounded-2 small mb-1" style="font-size: 0.72rem;">
                        <span class="text-secondary">Actual Received (Cash + Digital + Subsidy):</span>
                        <strong class="text-success fw-bold" id="summary_actual">Rs. 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between p-1.5 px-2 rounded-2 small mb-2 border" id="summary_variance_box" style="font-size: 0.75rem;">
                        <span class="fw-bold">Shift Variance (Difference):</span>
                        <strong class="fw-bold" id="summary_difference">Rs. 0.00</strong>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-sm rounded-2 fw-bold shadow-sm py-2" id="submitClosingBtn" style="font-size: 0.88rem;">
                            <i class="bi bi-lock-fill me-1"></i> Lock &amp; Finalize Shift Closing
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

</form>

@push('scripts')
<script>
    // Datasets
    const availableAccounts = @json($accounts);
    const lubricantProducts = @json($lubricantProducts);
    const creditParties = @json($creditParties);
    const expenseCategories = @json($expenseCategories);

    let digitalRowCount = 0;
    let lubricantRowCount = 0;
    let creditRowCount = 0;
    let expenseRowCount = 0;

    // 1. Calculate Single Nozzle Row
    function toggleMidnightRateChange(nIndex) {
        const chk = document.getElementById('rate_change_check_' + nIndex);
        const splitBox = document.getElementById('midnight_split_' + nIndex);
        const badge = document.getElementById('split_badge_' + nIndex);
        if (chk && chk.checked) {
            splitBox.style.display = 'block';
            badge.style.display = 'inline-block';
        } else {
            splitBox.style.display = 'none';
            badge.style.display = 'none';
        }
        calculateNozzleRow(nIndex);
    }

    function calculateNozzleRow(nIndex) {
        const start = parseFloat(document.getElementById('start_' + nIndex).value) || 0;
        const end = parseFloat(document.getElementById('end_' + nIndex).value) || 0;
        const test = parseFloat(document.getElementById('testing_' + nIndex).value) || 0;
        const rate = parseFloat(document.getElementById('rate_' + nIndex).value) || 0;
        const chk = document.getElementById('rate_change_check_' + nIndex);

        let soldLtr = 0;
        let subtotal = 0;

        if (chk && chk.checked) {
            const mid = parseFloat(document.getElementById('midnight_' + nIndex).value) || 0;
            const oldRate = parseFloat(document.getElementById('old_rate_' + nIndex).value) || 0;
            const newRate = parseFloat(document.getElementById('new_rate_' + nIndex).value) || 0;

            const oldLtr = Math.max(0, mid - start);
            const newLtr = Math.max(0, (end - mid) - test);
            const oldSub = oldLtr * oldRate;
            const newSub = newLtr * newRate;

            soldLtr = oldLtr + newLtr;
            subtotal = oldSub + newSub;

            document.getElementById('old_ltr_' + nIndex).value = oldLtr.toFixed(2);
            document.getElementById('new_ltr_' + nIndex).value = newLtr.toFixed(2);
            document.getElementById('old_sub_' + nIndex).value = oldSub.toFixed(2);
            document.getElementById('new_sub_' + nIndex).value = newSub.toFixed(2);

            const preCalc = document.getElementById('pre_calc_' + nIndex);
            if (preCalc) {
                preCalc.textContent = `${oldLtr.toFixed(2)} Ltr @ Rs. ${oldRate.toFixed(2)} = Rs. ${oldSub.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            }
            const postCalc = document.getElementById('post_calc_' + nIndex);
            if (postCalc) {
                postCalc.textContent = `${newLtr.toFixed(2)} Ltr @ Rs. ${newRate.toFixed(2)} = Rs. ${newSub.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            }
        } else {
            const meterDiff = Math.max(0, end - start);
            soldLtr = Math.max(0, meterDiff - test);
            subtotal = soldLtr * rate;
        }

        document.getElementById('liters_display_' + nIndex).textContent = soldLtr.toFixed(2) + ' Ltr';
        document.getElementById('subtotal_display_' + nIndex).textContent = 'Rs. ' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('subtotal_val_' + nIndex).value = subtotal.toFixed(2);

        calculateGrandTotals();
    }

    // 2. Add Lubricant Sale Row (Audio 6)
    function addLubricantRow() {
        const container = document.getElementById('lubricantSalesContainer');
        const id = ++lubricantRowCount;

        let options = '<option value="">-- Choose Oil / Pack --</option>';
        lubricantProducts.forEach(p => {
            options += `<option value="${p.id}" data-price="${p.sale_price}" data-unit="${p.unit}">${p.name} (Rs. ${parseFloat(p.sale_price).toFixed(2)})</option>`;
        });

        const row = document.createElement('div');
        row.className = 'p-1.5 bg-light rounded-2 border';
        row.id = 'lub_row_' + id;
        row.innerHTML = `
            <div class="row g-1 align-items-center">
                <div class="col-12 col-sm-5">
                    <select name="lubricant_sales[${id}][product_id]" class="form-select compact-input fw-semibold" onchange="onLubricantProductChange(${id})">
                        ${options}
                    </select>
                </div>
                <div class="col-4 col-sm-2">
                    <input type="number" step="1" min="1" name="lubricant_sales[${id}][quantity]" id="lub_qty_${id}" class="form-control compact-input text-center fw-bold" value="1" placeholder="Qty" oninput="calculateLubricantRow(${id})">
                </div>
                <div class="col-4 col-sm-2">
                    <input type="number" step="0.01" min="0" name="lubricant_sales[${id}][unit_price]" id="lub_price_${id}" class="form-control compact-input text-end fw-semibold" placeholder="Price" oninput="calculateLubricantRow(${id})">
                </div>
                <div class="col-3 col-sm-2.5" style="flex: 0 0 20%; max-width: 20%;">
                    <input type="text" readonly name="lubricant_sales[${id}][subtotal]" id="lub_sub_${id}" class="form-control compact-input text-end fw-bold bg-white text-info" value="0.00">
                </div>
                <div class="col-1 text-end" style="flex: 0 0 5%; max-width: 5%;">
                    <button type="button" class="btn btn-link text-danger p-0" onclick="document.getElementById('lub_row_${id}').remove(); calculateGrandTotals();">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(row);
    }

    function onLubricantProductChange(id) {
        const row = document.getElementById('lub_row_' + id);
        const sel = row.querySelector('select');
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.value) {
            document.getElementById('lub_price_' + id).value = parseFloat(opt.getAttribute('data-price') || 0).toFixed(2);
        }
        calculateLubricantRow(id);
    }

    function calculateLubricantRow(id) {
        const qty = parseFloat(document.getElementById('lub_qty_' + id).value) || 0;
        const price = parseFloat(document.getElementById('lub_price_' + id).value) || 0;
        const sub = qty * price;
        document.getElementById('lub_sub_' + id).value = sub.toFixed(2);
        calculateGrandTotals();
    }

    // 3. Add Credit / Udhar Sale Row (Audio 8)
    function addCreditRow() {
        const container = document.getElementById('creditSalesContainer');
        const id = ++creditRowCount;

        let partyOpts = '<option value="">-- Choose Customer Party --</option>';
        creditParties.forEach(p => {
            partyOpts += `<option value="${p.id}">${p.name}</option>`;
        });

        const row = document.createElement('div');
        row.className = 'p-1.5 bg-light rounded-2 border';
        row.id = 'credit_row_' + id;
        row.innerHTML = `
            <div class="row g-1 align-items-center">
                <div class="col-12 col-sm-4">
                    <select name="credit_sales[${id}][party_id]" class="form-select compact-input fw-semibold" required>
                        ${partyOpts}
                    </select>
                </div>
                <div class="col-6 col-sm-2">
                    <input type="text" name="credit_sales[${id}][vehicle_no]" class="form-control compact-input font-monospace" placeholder="Vehicle No.">
                </div>
                <div class="col-6 col-sm-2">
                    <input type="number" step="0.01" min="0" name="credit_sales[${id}][liters]" id="credit_ltr_${id}" class="form-control compact-input text-center" placeholder="Liters" oninput="calculateCreditRow(${id})">
                </div>
                <div class="col-6 col-sm-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white compact-addon text-danger fw-bold">Rs.</span>
                        <input type="number" step="0.01" min="1" name="credit_sales[${id}][amount]" id="credit_amt_${id}" class="form-control compact-input fw-bold text-danger" placeholder="Amount" oninput="calculateGrandTotals()">
                    </div>
                </div>
                <div class="col-1 text-end">
                    <button type="button" class="btn btn-link text-danger p-0" onclick="document.getElementById('credit_row_${id}').remove(); calculateGrandTotals();">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(row);
    }

    function calculateCreditRow(id) {
        // If rate is entered or fuel rate can be inferred
        calculateGrandTotals();
    }

    // 4. Add Counter Expense Row (Video Walkthrough)
    function addExpenseRow(selectedCatId = '', detailsVal = '', amountVal = '') {
        const container = document.getElementById('counterExpensesContainer');
        const id = ++expenseRowCount;

        let catOpts = '<option value="">-- Choose Category --</option>';
        expenseCategories.forEach(c => {
            const isSel = (selectedCatId && selectedCatId == c.id) ? 'selected' : '';
            catOpts += `<option value="${c.id}" data-name="${c.name}" ${isSel}>${c.name}</option>`;
        });

        const row = document.createElement('div');
        row.className = 'p-1.5 bg-light rounded-2 border shadow-xs mb-1';
        row.id = 'exp_row_' + id;
        row.innerHTML = `
            <div class="row g-1 align-items-center">
                <div class="col-12 col-sm-4">
                    <select name="counter_expenses[${id}][expense_category_id]" class="form-select compact-input fw-semibold" onchange="onExpenseCatChange(${id})">
                        ${catOpts}
                    </select>
                    <input type="hidden" name="counter_expenses[${id}][category_name]" id="exp_cat_name_${id}">
                </div>
                <div class="col-7 col-sm-4">
                    <input type="text" name="counter_expenses[${id}][details]" class="form-control compact-input" placeholder="Expense description (optional)..." value="${detailsVal ? detailsVal.replace(/"/g, '&quot;') : ''}">
                </div>
                <div class="col-4 col-sm-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white compact-addon text-danger fw-bold">Rs.</span>
                        <input type="number" step="any" min="0" name="counter_expenses[${id}][amount]" id="exp_amt_${id}" class="form-control compact-input fw-bold text-danger no-spin text-end font-monospace" placeholder="e.g. 5000" value="${amountVal || ''}" oninput="calculateGrandTotals()" onfocus="this.select()">
                    </div>
                </div>
                <div class="col-1 text-end">
                    <button type="button" class="btn btn-link text-danger p-0" onclick="document.getElementById('exp_row_${id}').remove(); calculateGrandTotals();" title="Remove Expense">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                <span class="text-secondary fw-semibold" style="font-size: 0.65rem;">Quick Amounts:</span>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1.5 rounded-pill" style="font-size: 0.67rem;" onclick="setExpenseAmount(${id}, 500)">Rs. 500</button>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1.5 rounded-pill" style="font-size: 0.67rem;" onclick="setExpenseAmount(${id}, 1000)">Rs. 1,000</button>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1.5 rounded-pill" style="font-size: 0.67rem;" onclick="setExpenseAmount(${id}, 2000)">Rs. 2,000</button>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1.5 rounded-pill" style="font-size: 0.67rem;" onclick="setExpenseAmount(${id}, 5000)">Rs. 5,000</button>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1.5 rounded-pill" style="font-size: 0.67rem;" onclick="setExpenseAmount(${id}, 10000)">Rs. 10,000</button>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1.5 rounded-pill" style="font-size: 0.67rem;" onclick="setExpenseAmount(${id}, 15000)">Rs. 15,000</button>
            </div>
        `;
        container.appendChild(row);
        onExpenseCatChange(id);
        calculateGrandTotals();
    }

    function setExpenseAmount(id, amt) {
        const inp = document.getElementById('exp_amt_' + id);
        if (inp) {
            inp.value = amt;
            calculateGrandTotals();
        }
    }

    function onExpenseCatChange(id) {
        const row = document.getElementById('exp_row_' + id);
        const sel = row.querySelector('select');
        const opt = sel.options[sel.selectedIndex];
        if (opt) {
            document.getElementById('exp_cat_name_' + id).value = opt.getAttribute('data-name') || '';
        }
    }

    // 5. Add Digital Account Row
    document.getElementById('addAccountPayment').addEventListener('click', () => {
        const container = document.getElementById('accountPaymentRows');
        const id = ++digitalRowCount;

        let options = '<option value="">-- Choose Bank / Wallet --</option>';
        availableAccounts.forEach(acc => {
            options += `<option value="${acc.id}">${acc.name} (${acc.type.toUpperCase()})</option>`;
        });

        const row = document.createElement('div');
        row.className = 'p-1.5 bg-light rounded-2 border';
        row.id = 'acc_row_' + id;
        row.innerHTML = `
            <div class="row g-1 align-items-center">
                <div class="col-12 col-sm-5">
                    <select name="account_payments[${id}][account_id]" class="form-select compact-input fw-semibold" required>
                        ${options}
                    </select>
                </div>
                <div class="col-6 col-sm-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white compact-addon text-primary fw-bold">Rs.</span>
                        <input type="number" step="0.01" min="1" name="account_payments[${id}][amount]" class="form-control compact-input fw-bold text-primary digital-amt-input" placeholder="0.00" oninput="calculateGrandTotals()" required>
                    </div>
                </div>
                <div class="col-5 col-sm-2.5" style="flex: 0 0 20%; max-width: 20%;">
                    <input type="text" name="account_payments[${id}][description]" class="form-control compact-input" placeholder="Tx / Ref #">
                </div>
                <div class="col-1 text-end" style="flex: 0 0 5%; max-width: 5%;">
                    <button type="button" class="btn btn-link text-danger p-0" onclick="document.getElementById('acc_row_${id}').remove(); calculateGrandTotals();">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(row);
    });

    // 6. Denominations Calculation
    function calculateDenominations() {
        let totalCounted = 0;
        const notes = [
            { id: 'note_5000', mul: 5000 },
            { id: 'note_1000', mul: 1000 },
            { id: 'note_500',  mul: 500 },
            { id: 'note_100',  mul: 100 },
            { id: 'note_50',   mul: 50 },
            { id: 'note_20',   mul: 20 },
            { id: 'note_10',   mul: 10 },
        ];

        notes.forEach(n => {
            const el = document.getElementById(n.id);
            const count = parseInt(el?.value, 10) || 0;
            const sub = count * n.mul;
            const target = document.getElementById('total_' + n.id);
            if (target) target.textContent = sub.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            totalCounted += sub;
        });

        const coins = parseFloat(document.getElementById('coins')?.value) || 0;
        const coinsTarget = document.getElementById('total_coins');
        if (coinsTarget) coinsTarget.textContent = coins.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        totalCounted += coins;

        document.getElementById('display_counted_cash').textContent = 'Rs. ' + totalCounted.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        calculateGrandTotals();
    }

    // 7. Grand Reconciliation Engine
    function calculateGrandTotals() {
        // Fuel
        let totalFuelLtr = 0;
        let totalTestingLtr = 0;
        let totalFuelSales = 0;

        document.querySelectorAll('input[id^="subtotal_val_"]').forEach(inp => {
            totalFuelSales += parseFloat(inp.value) || 0;
        });
        document.querySelectorAll('input[id^="end_"]').forEach(inp => {
            const id = inp.id.replace('end_', '');
            const start = parseFloat(document.getElementById('start_' + id)?.value) || 0;
            const end = parseFloat(inp.value) || 0;
            const test = parseFloat(document.getElementById('testing_' + id)?.value) || 0;
            const diff = Math.max(0, end - start);
            const sold = Math.max(0, diff - test);
            totalFuelLtr += sold;
            totalTestingLtr += test;
        });

        document.getElementById('disp_total_fuel_liters').textContent = totalFuelLtr.toFixed(2) + ' Ltr';
        document.getElementById('disp_total_testing_liters').textContent = totalTestingLtr.toFixed(2) + ' Ltr';
        document.getElementById('disp_total_fuel_sales').textContent = 'Rs. ' + totalFuelSales.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('kpi_liters').textContent = totalFuelLtr.toFixed(2) + ' Ltr';
        document.getElementById('kpi_testing').textContent = totalTestingLtr.toFixed(2) + ' Ltr';

        // Lubricants
        let totalLubricants = 0;
        document.querySelectorAll('input[id^="lub_sub_"]').forEach(inp => {
            totalLubricants += parseFloat(inp.value) || 0;
        });
        document.getElementById('display_lubricant_total').textContent = 'Rs. ' + totalLubricants.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Credit Sales
        let totalCredit = 0;
        document.querySelectorAll('input[id^="credit_amt_"]').forEach(inp => {
            const raw = (inp.value || '').toString().replace(/,/g, '').trim();
            totalCredit += parseFloat(raw) || 0;
        });
        document.getElementById('display_credit_total').textContent = '-Rs. ' + totalCredit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Expenses
        let totalExpenses = 0;
        document.querySelectorAll('input[id^="exp_amt_"]').forEach(inp => {
            const raw = (inp.value || '').toString().replace(/,/g, '').trim();
            totalExpenses += parseFloat(raw) || 0;
        });
        document.getElementById('display_expenses_total').textContent = '-Rs. ' + totalExpenses.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Digital Collections
        let totalDigital = 0;
        document.querySelectorAll('.digital-amt-input').forEach(inp => {
            const raw = (inp.value || '').toString().replace(/,/g, '').trim();
            totalDigital += parseFloat(raw) || 0;
        });
        const govtSubsidy = parseFloat((document.getElementById('govt_subsidy_amount')?.value || '').toString().replace(/,/g, '').trim()) || 0;
        const totalDigitalAndSubsidy = totalDigital + govtSubsidy;
        document.getElementById('display_digital_total').textContent = 'Rs. ' + totalDigitalAndSubsidy.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Counted Cash
        let countedCash = 0;
        const notes = [5000, 1000, 500, 100, 50, 20, 10];
        notes.forEach(val => {
            const c = parseInt(document.getElementById('note_' + val)?.value, 10) || 0;
            countedCash += c * val;
        });
        countedCash += parseFloat(document.getElementById('coins')?.value) || 0;

        // Reconciliation
        const grossSales = totalFuelSales + totalLubricants;
        const expectedCash = grossSales - totalCredit - totalExpenses;
        const actualReceived = countedCash + totalDigitalAndSubsidy;
        const difference = actualReceived - expectedCash;

        // Update UI
        document.getElementById('kpi_sales').textContent = 'Rs. ' + grossSales.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('kpi_expected').textContent = 'Rs. ' + expectedCash.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('kpi_actual').textContent = 'Rs. ' + actualReceived.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        document.getElementById('summary_gross_sales').textContent = 'Rs. ' + grossSales.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summary_credit_sales').textContent = '-Rs. ' + totalCredit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summary_expenses').textContent = '-Rs. ' + totalExpenses.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summary_expected').textContent = 'Rs. ' + expectedCash.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summary_actual').textContent = 'Rs. ' + actualReceived.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const varBadge = document.getElementById('kpi_variance_badge');
        const varCard = document.getElementById('kpi_variance_card');
        const varVal = document.getElementById('kpi_variance');
        const varText = document.getElementById('kpi_variance_text');
        const sumVarBox = document.getElementById('summary_variance_box');
        const sumVarDiff = document.getElementById('summary_difference');

        if (Math.abs(difference) < 0.01) {
            varBadge.className = 'badge bg-success small py-0 px-1';
            varBadge.textContent = 'Balanced';
            varVal.className = 'fw-bold text-success kpi-value text-truncate';
            varVal.textContent = 'Rs. 0.00';
            varText.textContent = 'Drawer Perfectly Reconciled';
            sumVarBox.className = 'd-flex justify-content-between p-1.5 px-2 rounded-2 small mb-2 border border-success bg-success-subtle text-success';
            sumVarDiff.textContent = 'Rs. 0.00 (Balanced)';
        } else if (difference > 0) {
            varBadge.className = 'badge bg-info text-dark small py-0 px-1';
            varBadge.textContent = 'Surplus';
            varVal.className = 'fw-bold text-info kpi-value text-truncate';
            varVal.textContent = '+Rs. ' + difference.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            varText.textContent = 'Cash Surplus in Drawer';
            sumVarBox.className = 'd-flex justify-content-between p-1.5 px-2 rounded-2 small mb-2 border border-info bg-info-subtle text-info';
            sumVarDiff.textContent = '+Rs. ' + difference.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (Surplus)';
        } else {
            varBadge.className = 'badge bg-danger text-white small py-0 px-1';
            varBadge.textContent = 'Shortage';
            varVal.className = 'fw-bold text-danger kpi-value text-truncate';
            varVal.textContent = '-Rs. ' + Math.abs(difference).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            varText.textContent = 'Cash Shortage Detected';
            sumVarBox.className = 'd-flex justify-content-between p-1.5 px-2 rounded-2 small mb-2 border border-danger bg-danger-subtle text-danger';
            sumVarDiff.textContent = '-Rs. ' + Math.abs(difference).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (Shortage)';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Calculate all pre-loaded nozzles
        @foreach($pumps as $pump)
            calculateNozzleRow({{ $pump->id }});
        @endforeach
        calculateDenominations();

        // Restore old expenses on validation failure, or auto-add one blank expense row on initial load
        @if(old('counter_expenses'))
            const oldExpenses = @json(old('counter_expenses'));
            const expKeys = Object.keys(oldExpenses);
            if (expKeys.length > 0) {
                document.getElementById('counterExpensesContainer').innerHTML = '';
                expKeys.forEach(k => {
                    const e = oldExpenses[k];
                    addExpenseRow(e.expense_category_id || '', e.details || '', e.amount || '');
                });
            } else {
                addExpenseRow();
            }
        @else
            addExpenseRow();
        @endif
    });

    // Prevent mouse wheel from accidentally changing number inputs when scrolling
    document.addEventListener('wheel', function(e) {
        if (document.activeElement && document.activeElement.type === 'number') {
            document.activeElement.blur();
        }
    }, { passive: true });
</script>
@endpush
@endsection
