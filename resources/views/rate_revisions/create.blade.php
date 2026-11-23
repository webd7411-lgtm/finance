@extends('layouts.app')

@section('title', 'Apply Fuel Rate Revision & Tank Revaluation')
@section('page_title', 'Apply Fuel Rate Revision')

@section('page_badge')
    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1 small">
        <i class="bi bi-clock-history me-1"></i> Midnight 12:00 AM Rate Revision & Stock Difference
    </span>
@endsection

@section('page_actions')
    <a href="{{ route('rate-revisions.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to History
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="card-custom shadow-sm p-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-fuel-pump text-danger me-2"></i> Official Price Revision & Stock Gain/Loss Log
                    </h5>
                    <p class="text-secondary small mb-0">
                        Record midnight price adjustments (up/down) and automatically calculate the financial gain or loss on bulk tank inventory.
                    </p>
                </div>
                <div class="text-end d-none d-md-block">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-2">
                        <i class="bi bi-moon-stars text-warning me-1"></i> Effective: 12:00 AM Midnight
                    </span>
                </div>
            </div>

            <form action="{{ route('rate-revisions.store') }}" method="POST" id="rateRevisionForm">
                @csrf

                <!-- Basic Details Row -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-5">
                        <label class="form-label fw-semibold text-dark">Fuel Product <span class="text-danger">*</span></label>
                        <select name="product_id" id="productId" class="form-select @error('product_id') is-invalid @enderror" required>
                            <option value="">-- Select Fuel Product --</option>
                            @foreach($fuelProducts as $fp)
                                <option value="{{ $fp->id }}" 
                                    data-sale-price="{{ $fp->sale_price }}"
                                    data-purchase-price="{{ $fp->purchase_price }}"
                                    data-stock="{{ $fp->current_stock }}"
                                    {{ (old('product_id', $selectedProductId) == $fp->id) ? 'selected' : '' }}>
                                    {{ $fp->name }} (Current: Rs. {{ number_format($fp->sale_price, 2) }}/Ltr | Stock: {{ number_format($fp->current_stock, 1) }} Ltr)
                                </option>
                            @endforeach
                        </select>
                        @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold text-dark">Underground Storage Tank (Optional)</label>
                        <select name="affected_tank_id" id="affectedTankId" class="form-select @error('affected_tank_id') is-invalid @enderror">
                            <option value="">-- All Connected Tanks / Overall Stock --</option>
                            @foreach($tanks as $tk)
                                <option value="{{ $tk->id }}" 
                                    data-product-id="{{ $tk->product_id }}"
                                    data-tank-stock="{{ $tk->current_stock }}"
                                    {{ old('affected_tank_id') == $tk->id ? 'selected' : '' }}>
                                    {{ $tk->tank_number }} - {{ $tk->name }} (Stock: {{ number_format($tk->current_stock, 1) }} Ltr)
                                </option>
                            @endforeach
                        </select>
                        <small class="text-secondary" style="font-size: 0.75rem;">Leave empty to evaluate all tanks containing this fuel.</small>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label fw-semibold text-dark">Effective Date <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" class="form-control @error('effective_date') is-invalid @enderror" 
                            value="{{ old('effective_date', now()->toDateString()) }}" required>
                        @error('effective_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label fw-semibold text-dark">Effective Time</label>
                        <input type="text" name="effective_time" class="form-control" value="{{ old('effective_time', '00:00:00 (12:00 AM Midnight)') }}" required>
                    </div>

                    <div class="col-12 col-md-9">
                        <label class="form-label fw-semibold text-dark">OGRA / OMC Notification Reference</label>
                        <input type="text" name="notification_ref" class="form-control" placeholder="e.g. OGRA Circular # 41-A/2026 or Hascol Price Notification" value="{{ old('notification_ref') }}">
                    </div>
                </div>

                <!-- Price & Stock Calculation Card -->
                <div class="card border-0 bg-light p-4 rounded-3 mb-4">
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="bi bi-calculator text-primary me-2"></i> Price Adjustment & Live Tank Stock Revaluation
                    </h6>
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold text-secondary">Existing / Old Sale Rate (Rs.) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">Rs.</span>
                                <input type="number" step="0.01" name="old_sale_rate" id="oldSaleRate" class="form-control bg-white fw-bold" 
                                    value="{{ old('old_sale_rate', '0.00') }}" required>
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold text-primary">New Revised Sale Rate (Rs.) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white">Rs.</span>
                                <input type="number" step="0.01" name="new_sale_rate" id="newSaleRate" class="form-control border-primary fw-bold fs-6" 
                                    value="{{ old('new_sale_rate') }}" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold text-secondary">Tank Stock Volume at Midnight (Liters)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="stock_at_revision" id="stockAtRevision" class="form-control bg-white fw-semibold" 
                                    value="{{ old('stock_at_revision', '0.00') }}" placeholder="e.g. 15000">
                                <span class="input-group-text bg-white">Ltr</span>
                            </div>
                            <small class="text-secondary" style="font-size: 0.72rem;">Auto-populated from tank dip / live stock.</small>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold text-secondary">New Purchase Rate (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">Rs.</span>
                                <input type="number" step="0.01" name="new_purchase_rate" id="newPurchaseRate" class="form-control bg-white" 
                                    value="{{ old('new_purchase_rate') }}" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Live Calculation Result Box -->
                    <div class="row g-3 mt-3 pt-3 border-top" id="liveCalcBox">
                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-white rounded-3 border h-100 shadow-sm">
                                <span class="text-secondary small fw-semibold">Rate Difference (per Liter)</span>
                                <div class="fs-4 fw-bold mt-1" id="rateDiffDisplay">Rs. 0.00</div>
                                <span class="badge bg-secondary small mt-1" id="rateDiffStatusBadge">No Change</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-8">
                            <div class="p-3 bg-white rounded-3 border h-100 shadow-sm" id="gainLossCard">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-secondary small fw-semibold" id="gainLossTitle">Estimated Tank Stock Gain / Loss</span>
                                    <span class="badge bg-secondary" id="impactPill">Neutral</span>
                                </div>
                                <div class="fs-3 fw-bold mt-1 text-dark" id="gainLossAmountDisplay">Rs. 0.00</div>
                                <div class="small text-secondary mt-1" id="gainLossExplanation">
                                    Formula: Tank Volume (Liters) × Price Difference (Rs./Ltr)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Remarks -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-dark">Remarks / Official Notes</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Price increased following midnight government notification. Nozzles reprogrammed at 12:00 AM.">{{ old('remarks') }}</textarea>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('rate-revisions.index') }}" class="btn btn-outline-secondary px-4">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Apply & Save Rate Revision
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const productSelect = document.getElementById('productId');
    const tankSelect = document.getElementById('affectedTankId');
    const oldSaleInput = document.getElementById('oldSaleRate');
    const newSaleInput = document.getElementById('newSaleRate');
    const stockInput = document.getElementById('stockAtRevision');
    const rateDiffDisplay = document.getElementById('rateDiffDisplay');
    const rateDiffBadge = document.getElementById('rateDiffStatusBadge');
    const gainLossAmount = document.getElementById('gainLossAmountDisplay');
    const impactPill = document.getElementById('impactPill');
    const gainLossTitle = document.getElementById('gainLossTitle');
    const gainLossCard = document.getElementById('gainLossCard');
    const gainLossExp = document.getElementById('gainLossExplanation');

    function syncProductDetails() {
        const opt = productSelect.options[productSelect.selectedIndex];
        if (!opt || !opt.value) return;

        const curSale = parseFloat(opt.getAttribute('data-sale-price')) || 0;
        const curStock = parseFloat(opt.getAttribute('data-stock')) || 0;

        oldSaleInput.value = curSale.toFixed(2);
        if (!stockInput.value || parseFloat(stockInput.value) === 0) {
            stockInput.value = curStock.toFixed(2);
        }
        calculateImpact();
    }

    function syncTankDetails() {
        const opt = tankSelect.options[tankSelect.selectedIndex];
        if (!opt || !opt.value) {
            syncProductDetails();
            return;
        }
        const tankStock = parseFloat(opt.getAttribute('data-tank-stock')) || 0;
        stockInput.value = tankStock.toFixed(2);
        calculateImpact();
    }

    function calculateImpact() {
        const oldRate = parseFloat(oldSaleInput.value) || 0;
        const newRate = parseFloat(newSaleInput.value) || 0;
        const stock = parseFloat(stockInput.value) || 0;

        if (newRate === 0 && oldRate === 0) {
            rateDiffDisplay.textContent = 'Rs. 0.00';
            gainLossAmount.textContent = 'Rs. 0.00';
            return;
        }

        const diff = newRate - oldRate;
        const totalImpact = stock * diff;

        // Rate diff UI
        if (diff > 0) {
            rateDiffDisplay.textContent = '+Rs. ' + diff.toFixed(2);
            rateDiffDisplay.className = 'fs-4 fw-bold mt-1 text-success';
            rateDiffBadge.textContent = 'Price Hike (+Rs. ' + diff.toFixed(2) + ')';
            rateDiffBadge.className = 'badge bg-success bg-opacity-10 text-success border border-success-subtle';
        } else if (diff < 0) {
            rateDiffDisplay.textContent = '-Rs. ' + Math.abs(diff).toFixed(2);
            rateDiffDisplay.className = 'fs-4 fw-bold mt-1 text-danger';
            rateDiffBadge.textContent = 'Price Reduction (-Rs. ' + Math.abs(diff).toFixed(2) + ')';
            rateDiffBadge.className = 'badge bg-danger bg-opacity-10 text-danger border border-danger-subtle';
        } else {
            rateDiffDisplay.textContent = 'Rs. 0.00';
            rateDiffDisplay.className = 'fs-4 fw-bold mt-1 text-secondary';
            rateDiffBadge.textContent = 'No Change';
            rateDiffBadge.className = 'badge bg-secondary';
        }

        // Total stock impact UI
        if (totalImpact > 0) {
            gainLossTitle.textContent = 'Windfall Stock Gain (Profit on Storage)';
            gainLossTitle.className = 'text-success small fw-bold';
            impactPill.textContent = 'Asset Gain';
            impactPill.className = 'badge bg-success';
            gainLossAmount.textContent = '+Rs. ' + totalImpact.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            gainLossAmount.className = 'fs-3 fw-bold mt-1 text-success';
            gainLossCard.style.borderColor = '#198754';
            gainLossExp.innerHTML = `<i class="bi bi-arrow-up-circle text-success me-1"></i> Stock of <strong>${stock.toLocaleString()} Liters</strong> gained <strong>+Rs. ${totalImpact.toLocaleString()}</strong> in value at midnight.`;
        } else if (totalImpact < 0) {
            gainLossTitle.textContent = 'Stock Devaluation (Inventory Loss on Storage)';
            gainLossTitle.className = 'text-danger small fw-bold';
            impactPill.textContent = 'Asset Loss';
            impactPill.className = 'badge bg-danger';
            gainLossAmount.textContent = '-Rs. ' + Math.abs(totalImpact).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            gainLossAmount.className = 'fs-3 fw-bold mt-1 text-danger';
            gainLossCard.style.borderColor = '#dc3545';
            gainLossExp.innerHTML = `<i class="bi bi-arrow-down-circle text-danger me-1"></i> Stock of <strong>${stock.toLocaleString()} Liters</strong> lost <strong>-Rs. ${Math.abs(totalImpact).toLocaleString()}</strong> in value at midnight.`;
        } else {
            gainLossTitle.textContent = 'Estimated Tank Stock Gain / Loss';
            gainLossTitle.className = 'text-secondary small fw-semibold';
            impactPill.textContent = 'Neutral';
            impactPill.className = 'badge bg-secondary';
            gainLossAmount.textContent = 'Rs. 0.00';
            gainLossAmount.className = 'fs-3 fw-bold mt-1 text-dark';
            gainLossCard.style.borderColor = '#dee2e6';
            gainLossExp.textContent = 'Formula: Tank Volume (Liters) × Price Difference (Rs./Ltr)';
        }
    }

    productSelect.addEventListener('change', syncProductDetails);
    tankSelect.addEventListener('change', syncTankDetails);
    oldSaleInput.addEventListener('input', calculateImpact);
    newSaleInput.addEventListener('input', calculateImpact);
    stockInput.addEventListener('input', calculateImpact);

    // Initial check
    if (productSelect.value) {
        syncProductDetails();
    }
});
</script>
@endpush
