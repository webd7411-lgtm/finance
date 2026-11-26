@extends('layouts.app')

@section('title', 'Tank & Dip Reconciliation Settings')
@section('page_title', 'Tank Behaviour & Dip Reconciliation Settings')

@section('page_badge')
    <span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info-subtle px-2 py-1 small">
        <i class="bi bi-sliders me-1"></i> Client Configurable Rules
    </span>
@endsection

@section('page_actions')
    <div class="d-flex gap-2">
        <a href="{{ route('reports.tank-reconciliation') }}" class="btn btn-outline-primary btn-sm rounded-3">
            <i class="bi bi-clipboard-data me-1"></i> Wet-Stock Report
        </a>
        <a href="{{ route('tanks.index') }}" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Tanks
        </a>
    </div>
@endsection

@section('content')
<div class="container-fluid px-2 px-md-3" style="max-width: 980px;">

    <!-- Operational Guide Banner -->
    <div class="alert alert-info border-0 shadow-sm rounded-3 mb-3 small">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-info-circle-fill fs-5 text-info mt-0.5"></i>
            <div>
                <div class="fw-bold text-dark mb-1">Understanding Underground Fuel Tank Fluctuations (±10 to 20 Liters)</div>
                <p class="mb-0 text-secondary">
                    Minor daily dip rod differences (such as 10–20 Ltr short in cool mornings or +10 Ltr excess in warm afternoons) happen naturally due to
                    <strong>thermal expansion/contraction</strong>, <strong>dip stick millimeter sensitivity</strong>, <strong>evaporation</strong>, and <strong>dispenser meter tolerances</strong>.
                    Use these flexible settings to define what counts as <em>Normal Tolerance</em> versus an <em>Actionable Alert</em>, and choose how book stock should be reconciled.
                    You can also customize tolerance limits individually on each tank.
                </p>
            </div>
        </div>
    </div>

    <div class="card-custom shadow-sm p-3 p-md-4">
        <form action="{{ route('tanks.settings.update') }}" method="POST">
            @csrf

            <!-- Section 1: Measurement & Tolerance Policy -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-shield-check text-primary me-1"></i> 1. Variance Tolerance &amp; Alert Thresholds
                </h6>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Dip Stick Measurement Unit</label>
                        <select name="dip_unit" class="form-select form-select-sm py-2" required>
                            <option value="mm" {{ $tankConfig['dip_unit'] === 'mm' ? 'selected' : '' }}>Millimeters (mm) — Standard</option>
                            <option value="cm" {{ $tankConfig['dip_unit'] === 'cm' ? 'selected' : '' }}>Centimeters (cm)</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-8">
                        <label class="form-label small fw-semibold text-secondary">Tolerance Calculation Mode</label>
                        <select name="tolerance_mode" class="form-select form-select-sm py-2" required>
                            @foreach($toleranceModes as $mKey => $mLabel)
                                <option value="{{ $mKey }}" {{ $tankConfig['tolerance_mode'] === $mKey ? 'selected' : '' }}>
                                    {{ $mLabel }}
                                </option>
                            @endforeach
                        </select>
                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                            "Whichever is Higher" uses the larger of Fixed Liters or % of Book Stock so both small and large tanks have practical margins.
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-secondary">Normal / Warning Limit (Liters)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">&plusmn;</span>
                            <input type="number" step="0.01" min="0" name="warning_liters" class="form-control font-monospace py-2"
                                   value="{{ old('warning_liters', $tankConfig['warning_liters']) }}" required>
                            <span class="input-group-text">Ltr</span>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-secondary">Critical Alert Limit (Liters)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">&plusmn;</span>
                            <input type="number" step="0.01" min="0" name="critical_liters" class="form-control font-monospace py-2"
                                   value="{{ old('critical_liters', $tankConfig['critical_liters']) }}" required>
                            <span class="input-group-text">Ltr</span>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-secondary">Normal / Warning Limit (%)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">&plusmn;</span>
                            <input type="number" step="0.01" min="0" max="100" name="warning_percent" class="form-control font-monospace py-2"
                                   value="{{ old('warning_percent', $tankConfig['warning_percent']) }}" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold text-secondary">Critical Alert Limit (%)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">&plusmn;</span>
                            <input type="number" step="0.01" min="0" max="100" name="critical_percent" class="form-control font-monospace py-2"
                                   value="{{ old('critical_percent', $tankConfig['critical_percent']) }}" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Consecutive Shortage Trend Alert</label>
                        <div class="input-group input-group-sm">
                            <input type="number" min="0" max="30" name="consecutive_shortage_alert" class="form-control font-monospace py-2"
                                   value="{{ old('consecutive_shortage_alert', $tankConfig['consecutive_shortage_alert']) }}" required>
                            <span class="input-group-text">Consecutive Negative Dips</span>
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                            Flags a leak/meter-calibration trend alert when a tank reports negative variances repeatedly without recovery (set 0 to disable).
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Book Stock & Bowser Workflow -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-arrow-left-right text-success me-1"></i> 2. Book Stock Reconciliation &amp; Bowser Delivery Workflow
                </h6>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Book Stock Adjustment Policy</label>
                        <select name="reconciliation_mode" class="form-select form-select-sm py-2" required>
                            @foreach($reconciliationModes as $rKey => $rLabel)
                                <option value="{{ $rKey }}" {{ $tankConfig['reconciliation_mode'] === $rKey ? 'selected' : '' }}>
                                    {{ $rLabel }}
                                </option>
                            @endforeach
                        </select>
                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                            <strong>Manual Approval</strong> keeps book stock untouched until Owner/Incharge clicks Reconcile (prevents daily temperature swings from distorting stock).
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Bowser Delivery Pre / Post Dip Mode</label>
                        <select name="bowser_dip_mode" class="form-select form-select-sm py-2" required>
                            @foreach($bowserDipModes as $bKey => $bLabel)
                                <option value="{{ $bKey }}" {{ $tankConfig['bowser_dip_mode'] === $bKey ? 'selected' : '' }}>
                                    {{ $bLabel }}
                                </option>
                            @endforeach
                        </select>
                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                            Controls whether Pre-Delivery and Post-Delivery tank dips appear as optional or mandatory on Fuel Purchase forms.
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch p-2.5 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                            <div>
                                <label class="form-check-label fw-semibold text-dark small ms-2" for="showVarianceSwitch">
                                    Show Wet-Stock Reconciliation &amp; Dip Variance Summary on Executive Dashboard
                                </label>
                                <div class="text-muted small ms-2" style="font-size: 0.72rem;">
                                    Displays tank short/excess badges on dashboard tank cards and the wet-stock health summary bar.
                                </div>
                            </div>
                            <input class="form-check-input ms-2" type="checkbox" role="switch" id="showVarianceSwitch"
                                   name="show_variance_on_dashboard" value="1"
                                   {{ $tankConfig['show_variance_on_dashboard'] ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('tanks.index') }}" class="btn btn-secondary btn-sm px-3">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                    <i class="bi bi-check-circle-fill me-1"></i> Save Tank &amp; Dip Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
