@php
    $dipUnit = $tankConfig['dip_unit'] ?? 'mm';
    $contexts = $readingContexts ?? \App\Models\StationSetting::READING_CONTEXTS;
    $reasons = $reasonCategories ?? \App\Models\StationSetting::REASON_CATEGORIES;
@endphp

<!-- Modal: Record Physical Tank Dip Reading (Smart Calibration Lookup & Live Variance Preview) -->
<div class="modal fade" id="dipReadingModal" tabindex="-1" aria-labelledby="dipReadingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-dark text-white py-2.5 px-3">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2" id="dipReadingModalLabel">
                    <i class="bi bi-rulers text-warning"></i>
                    <span>Record Physical Tank Dip Reading</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="dipReadingForm" method="POST" action="">
                @csrf
                <input type="hidden" name="is_chart_calculated" id="modalIsChartCalculated" value="0">

                <div class="modal-body p-3.5">
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Target Storage Tank</label>
                            <input type="text" class="form-control form-control-sm bg-light fw-bold" id="modalTankName" readonly>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Reading Date <span class="text-danger">*</span></label>
                            <input type="date" name="reading_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Time</label>
                            <input type="time" name="reading_time" class="form-control form-control-sm" value="{{ now()->format('H:i') }}">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Dip Context</label>
                            <select name="reading_context" class="form-select form-select-sm" id="modalReadingContext">
                                @foreach($contexts as $ctxKey => $ctxLabel)
                                    @if($ctxKey !== 'initial')
                                        <option value="{{ $ctxKey }}" {{ $ctxKey === 'daily_routine' ? 'selected' : '' }}>{{ $ctxLabel }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Software Book Stock</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm bg-light font-monospace fw-bold" id="modalBookStock" readonly>
                                <span class="input-group-text bg-light">Ltr</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">
                                Dip Stick Height ({{ $dipUnit }}) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" name="dip_cm" id="modalDipCm"
                                       class="form-control form-control-sm font-monospace" placeholder="Enter measured dip height" required>
                                <span class="input-group-text">{{ $dipUnit }}</span>
                            </div>
                            <div id="modalChartStatus" class="small mt-1 text-muted" style="font-size: 0.72rem;"></div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">
                                Measured Volume (Ltr) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" name="measured_liters" id="modalMeasuredLiters"
                                       class="form-control form-control-sm font-monospace fw-bold" placeholder="Enter physical liters" required>
                                <span class="input-group-text">Ltr</span>
                            </div>
                            <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                                Auto-fills if a tank dip chart is uploaded; editable anytime.
                            </div>
                        </div>
                    </div>

                    <!-- Live Variance & Tolerance Preview Card -->
                    <div id="modalVariancePreviewBox" class="p-2.5 rounded-3 border bg-light mb-3 d-none">
                        <div class="row g-2 align-items-center text-center text-md-start">
                            <div class="col-6 col-md-4">
                                <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Calculated Variance</div>
                                <div id="modalPreviewVariance" class="fw-bold font-monospace fs-6">0.0 L</div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Tolerance Evaluation</div>
                                <div id="modalPreviewStatusBadge"></div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Cost Valuation Impact</div>
                                <div id="modalPreviewCost" class="fw-semibold font-monospace small">Rs. 0.00</div>
                            </div>
                        </div>
                        <div id="modalPreviewLimitsHint" class="text-muted mt-1 pt-1 border-top" style="font-size: 0.7rem;"></div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Variance Reason <span class="text-muted">(Optional / Recommended on Alerts)</span></label>
                            <select name="reason_category" id="modalReasonCategory" class="form-select form-select-sm">
                                <option value="">-- Auto / Standard Behaviour --</option>
                                @foreach($reasons as $rKey => $rLabel)
                                    <option value="{{ $rKey }}">{{ $rLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Fuel Temp (°C)</label>
                            <input type="number" step="0.1" name="temperature_c" class="form-control form-control-sm font-monospace" placeholder="e.g. 24.5">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Water Dip ({{ $dipUnit }})</label>
                            <input type="number" step="0.01" min="0" name="water_dip_mm" class="form-control form-control-sm font-monospace" placeholder="e.g. 0">
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-secondary">Notes / Remarks <span class="text-muted">(Optional)</span></label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. Morning physical dip verification before shift start..."></textarea>
                    </div>
                </div>

                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Save Dip Measurement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    if (window.__tankDipModalInitialized) return;
    window.__tankDipModalInitialized = true;

    let activeTankId = null;
    let activeBookStock = 0;
    let activeUnitCost = 0;
    let activeWarnLimit = 25;
    let activeCritLimit = 50;
    let activeHasChart = false;
    let lookupDebounce = null;

    function updateVariancePreview() {
        const measuredInput = document.getElementById('modalMeasuredLiters');
        const box = document.getElementById('modalVariancePreviewBox');
        const varEl = document.getElementById('modalPreviewVariance');
        const badgeEl = document.getElementById('modalPreviewStatusBadge');
        const costEl = document.getElementById('modalPreviewCost');
        const hintEl = document.getElementById('modalPreviewLimitsHint');

        if (!measuredInput || !box) return;

        const raw = measuredInput.value.trim();
        if (raw === '' || isNaN(parseFloat(raw))) {
            box.classList.add('d-none');
            return;
        }

        const measured = parseFloat(raw);
        const variance = measured - activeBookStock;
        const absVar = Math.abs(variance);
        const sign = variance > 0 ? '+' : '';
        const dir = variance > 0.009 ? 'Excess' : (variance < -0.009 ? 'Short' : 'Match');

        box.classList.remove('d-none');
        varEl.textContent = `${sign}${variance.toFixed(1)} L (${dir})`;
        varEl.className = 'fw-bold font-monospace fs-6 ' + (absVar < 0.01 ? 'text-success' : (variance < 0 ? 'text-danger' : 'text-info'));

        let badgeHtml = '';
        if (absVar < 0.01) {
            badgeHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle">Balanced (Exact)</span>';
        } else if (absVar <= activeWarnLimit) {
            badgeHtml = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Normal (Within Tolerance)</span>';
        } else if (absVar <= activeCritLimit) {
            badgeHtml = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Warning (Exceeds Normal Limit)</span>';
        } else {
            badgeHtml = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Critical Alert (Check Required)</span>';
        }
        badgeEl.innerHTML = badgeHtml;

        const costImpact = variance * activeUnitCost;
        const costSign = costImpact > 0 ? '+' : '';
        costEl.textContent = `${costSign}Rs. ${costImpact.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        hintEl.textContent = `Active Tank Thresholds: Normal up to ±${activeWarnLimit.toFixed(1)} L | Critical above ±${activeCritLimit.toFixed(1)} L. Daily dip logs variance without altering book stock unless reconciled.`;
    }

    function lookupChartForDip(dipVal) {
        if (!activeTankId || dipVal === '' || isNaN(parseFloat(dipVal))) return;
        const statusEl = document.getElementById('modalChartStatus');
        const measuredEl = document.getElementById('modalMeasuredLiters');
        const isChartEl = document.getElementById('modalIsChartCalculated');

        fetch(`{{ url('tanks') }}/${activeTankId}/lookup-dip?dip_mm=${encodeURIComponent(dipVal)}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.ok ? res.json() : null)
        .then(data => {
            if (!data) return;
            activeBookStock = Number(data.book_stock) || 0;
            activeUnitCost = Number(data.unit_cost) || 0;
            activeWarnLimit = Number(data.warning_limit) || 25;
            activeCritLimit = Number(data.critical_limit) || 50;
            activeHasChart = Boolean(data.has_chart);

            if (data.has_chart && data.liters !== null) {
                measuredEl.value = Number(data.liters).toFixed(2);
                isChartEl.value = '1';
                statusEl.innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Calculated from tank calibration chart (${data.matched_exact ? 'exact point' : 'interpolated'}).</span>`;
                updateVariancePreview();
            } else if (data.has_chart && data.out_of_range) {
                isChartEl.value = '0';
                statusEl.innerHTML = `<span class="text-warning-emphasis"><i class="bi bi-exclamation-triangle me-1"></i>Outside chart range (${data.min_mm} - ${data.max_mm} ${data.dip_unit}). Enter volume manually.</span>`;
            } else {
                isChartEl.value = '0';
                statusEl.textContent = 'No calibration chart uploaded for this tank — enter volume manually.';
            }
        })
        .catch(() => {});
    }

    document.addEventListener('DOMContentLoaded', function () {
        const dipInput = document.getElementById('modalDipCm');
        const measuredInput = document.getElementById('modalMeasuredLiters');
        const isChartEl = document.getElementById('modalIsChartCalculated');

        if (dipInput) {
            dipInput.addEventListener('input', function () {
                clearTimeout(lookupDebounce);
                const val = this.value;
                lookupDebounce = setTimeout(() => lookupChartForDip(val), 220);
            });
        }

        if (measuredInput) {
            measuredInput.addEventListener('input', function () {
                if (isChartEl) isChartEl.value = '0';
                updateVariancePreview();
            });
        }
    });

    window.openDipModal = function (tankId, tankName, currentDip, bookStock, unitCost, warnLimit, critLimit, hasChart) {
        activeTankId = tankId;
        activeBookStock = Number(bookStock) || 0;
        activeUnitCost = Number(unitCost) || 0;
        activeWarnLimit = warnLimit !== undefined ? Number(warnLimit) : 25;
        activeCritLimit = critLimit !== undefined ? Number(critLimit) : 50;
        activeHasChart = Boolean(hasChart);

        const form = document.getElementById('dipReadingForm');
        form.action = `{{ url('tanks') }}/${tankId}/record-dip`;

        document.getElementById('modalTankName').value = tankName;
        document.getElementById('modalBookStock').value = activeBookStock.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
        const dipField = document.getElementById('modalDipCm');
        dipField.value = '';
        dipField.placeholder = currentDip > 0 ? `Last: ${currentDip} {{ $dipUnit }}` : 'Enter measured dip height';
        document.getElementById('modalMeasuredLiters').value = '';
        document.getElementById('modalIsChartCalculated').value = '0';
        document.getElementById('modalReasonCategory').value = '';
        document.getElementById('modalVariancePreviewBox').classList.add('d-none');

        const statusEl = document.getElementById('modalChartStatus');
        if (activeHasChart) {
            statusEl.innerHTML = `<span class="text-primary"><i class="bi bi-table me-1"></i>Calibration chart active — enter dip height to auto-calculate volume.</span>`;
        } else {
            statusEl.textContent = 'Enter measured physical dip height and volume from tank dip chart.';
        }

        // Fetch fresh tank thresholds / chart metadata immediately
        fetch(`{{ url('tanks') }}/${tankId}/lookup-dip?dip_mm=${encodeURIComponent(currentDip > 0 ? currentDip : 0)}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.ok ? res.json() : null)
        .then(data => {
            if (!data) return;
            activeBookStock = Number(data.book_stock) || activeBookStock;
            activeUnitCost = Number(data.unit_cost) || activeUnitCost;
            activeWarnLimit = Number(data.warning_limit) || activeWarnLimit;
            activeCritLimit = Number(data.critical_limit) || activeCritLimit;
            activeHasChart = Boolean(data.has_chart);
        })
        .catch(() => {});

        const modal = new bootstrap.Modal(document.getElementById('dipReadingModal'));
        modal.show();
    };
})();
</script>
@endpush
