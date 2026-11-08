@extends('layouts.app')

@section('title', 'Record Stock / Fuel Purchase')
@section('page_title', 'Record Stock & Fuel Delivery')

@section('page_badge')
    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1 small">
        <i class="bi bi-truck me-1"></i> Inventory Intake
    </span>
@endsection

@section('page_actions')
    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 shadow-sm text-nowrap">
        <i class="bi bi-arrow-left me-1"></i> Back to Purchases
    </a>
@endsection

@push('styles')
<style>
    .calculation-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.25rem;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">

        <div class="card-custom shadow-sm">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold text-dark m-0">Fuel / Stock Delivery Intake</h6>
                    <small class="text-muted" style="font-size: 0.78rem;">Record incoming tank bowsers, engine oils, or inventory</small>
                </div>
                <span class="badge bg-light text-secondary border">Purchase Voucher</span>
            </div>

            <div class="p-3 p-md-4">
                <form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm">
                    @csrf

                    <!-- Supplier & Date Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label for="purchase_date" class="form-label small fw-semibold text-secondary">
                                Delivery / Purchase Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-sm py-2 @error('purchase_date') is-invalid @enderror"
                                   id="purchase_date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                            @error('purchase_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="supplier_id" class="form-label small fw-semibold text-secondary">
                                Supplier / Oil Marketing Company <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm py-2 @error('supplier_id') is-invalid @enderror"
                                    id="supplier_id" name="supplier_id" required>
                                <option value="">-- Choose Supplier / OMC --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }} (Balance: Rs. {{ number_format($supplier->current_balance, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="invoice_no" class="form-label small fw-semibold text-secondary">
                                Supplier Bill / Invoice No. <span class="text-muted">(Optional)</span>
                            </label>
                            <input type="text" class="form-control form-control-sm py-2 font-monospace @error('invoice_no') is-invalid @enderror"
                                   id="invoice_no" name="invoice_no" value="{{ old('invoice_no') }}" placeholder="e.g. PSO-INV-84920">
                            @error('invoice_no')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Product, Destination Tanker & Rate Row -->
                    <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                        <div class="col-12 col-md-4">
                            <label for="product_id" class="form-label small fw-semibold text-secondary">
                                Product / Fuel Item <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm py-2 @error('product_id') is-invalid @enderror"
                                    id="product_id" name="product_id" required onchange="handleProductChange()">
                                <option value="">-- Select Product --</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}"
                                            data-unit="{{ $prod->unit_label }}"
                                            data-cost="{{ $prod->purchase_price }}"
                                            data-stock="{{ $prod->current_stock }}"
                                            data-tank="{{ $prod->tank_or_shelf }}"
                                            data-fuel="{{ $prod->is_fuel ? '1' : '0' }}"
                                            {{ (old('product_id', $selectedProductId ?? null) == $prod->id) ? 'selected' : '' }}>
                                        {{ $prod->name }} (Stock: {{ number_format($prod->current_stock, 2) }} {{ $prod->unit_label }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div class="text-muted small mt-1" id="productStockBadge" style="font-size: 0.72rem;">Select a product to view stock</div>
                        </div>

                        <!-- Linked Pump Machine / Dispenser (Optional) -->
                        <div class="col-12 col-md-4">
                            <label for="pump_id" class="form-label small fw-semibold text-secondary">
                                Destination Dispenser / Tank <span class="text-muted">(Optional)</span>
                            </label>
                            <select class="form-select form-select-sm py-2" id="pump_id" name="pump_id" onchange="handlePumpChange()">
                                <option value="">-- General Storage Tank --</option>
                                @foreach($pumps as $p)
                                    <option value="{{ $p->id }}"
                                            data-product-id="{{ $p->product_id }}"
                                            data-tank="{{ $p->tank_no }}"
                                            {{ (old('pump_id', $selectedPumpId ?? null) == $p->id) ? 'selected' : '' }}>
                                        #{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} • {{ $p->name }} ({{ $p->tank_no ?: 'Tank' }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="tank_no" id="tank_no" value="{{ old('tank_no') }}">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="vehicle_no" class="form-label small fw-semibold text-secondary">
                                Tanker / Vehicle No. <span class="text-muted">(Bowser)</span>
                            </label>
                            <input type="text" class="form-control form-control-sm py-2 font-monospace"
                                   id="vehicle_no" name="vehicle_no" value="{{ old('vehicle_no') }}" placeholder="e.g. TL-8492">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="quantity" class="form-label small fw-semibold text-secondary">
                                Delivered Quantity <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm py-2 fw-bold text-primary @error('quantity') is-invalid @enderror"
                                       id="quantity" name="quantity" value="{{ old('quantity') }}" required oninput="calculateBill()">
                                <span class="input-group-text bg-white text-muted" id="quantityUnitLabel">Ltr</span>
                            </div>
                            @error('quantity')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="unit_price" class="form-label small fw-semibold text-secondary">
                                Purchase Rate (Rs.) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white">Rs.</span>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm py-2 fw-bold @error('unit_price') is-invalid @enderror"
                                       id="unit_price" name="unit_price" value="{{ old('unit_price') }}" required oninput="calculateBill()">
                            </div>
                            @error('unit_price')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Commercial Totals & Immediate Payment Calculation -->
                    <div class="calculation-box mb-4">
                        <div class="row g-3 align-items-center">
                            <!-- Total Bill -->
                            <div class="col-12 col-md-4">
                                <span class="text-secondary small fw-semibold d-block">Gross Total Purchase Bill:</span>
                                <h4 class="fw-bold text-dark m-0" id="totalBillDisplay">Rs. 0.00</h4>
                                <small class="text-muted" style="font-size: 0.72rem;">Delivered Quantity &times; Purchase Rate</small>
                            </div>

                            <!-- Immediate Payment Input -->
                            <div class="col-12 col-md-4">
                                <label for="paid_amount" class="form-label small fw-semibold text-secondary mb-1">
                                    Immediate Payment (Rs.) <span class="text-muted">(Optional)</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-success fw-bold">Rs.</span>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm py-2 fw-bold text-success @error('paid_amount') is-invalid @enderror"
                                           id="paid_amount" name="paid_amount" value="{{ old('paid_amount', '0.00') }}" oninput="calculateBill()">
                                </div>
                                @error('paid_amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Payment Source Account -->
                            <div class="col-12 col-md-4">
                                <label for="payment_account_id" class="form-label small fw-semibold text-secondary mb-1">
                                    Paid From Account <span class="text-muted">(If Paid)</span>
                                </label>
                                <select class="form-select form-select-sm py-2 @error('payment_account_id') is-invalid @enderror"
                                        id="payment_account_id" name="payment_account_id">
                                    <option value="">-- Select Payment Account --</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}" {{ old('payment_account_id') == $acc->id ? 'selected' : '' }}>
                                            {{ $acc->name }} (Rs. {{ number_format($acc->current_balance, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_account_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Unpaid Balance Banner -->
                            <div class="col-12 border-top pt-2 mt-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-secondary small fw-semibold">Net Added to Supplier Khata (Payable):</span>
                                    <h5 class="fw-bold text-warning-emphasis m-0" id="unpaidDueDisplay">Rs. 0.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes & Chamber Dip Details -->
                    <div class="mb-4">
                        <label for="notes" class="form-label small fw-semibold text-secondary">
                            Delivery Notes, Chamber Dip / Temperature Measurements <span class="text-muted">(Optional)</span>
                        </label>
                        <textarea class="form-control form-control-sm @error('notes') is-invalid @enderror"
                                  id="notes" name="notes" rows="2"
                                  placeholder="e.g. Tanker Chamber 1: 5,000L, Chamber 2: 5,000L, Temperature: 28C, Density: 0.742, Dip stick measurement: 142cm...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('purchases.index') }}" class="btn btn-secondary btn-sm rounded-3 px-3">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm rounded-3 px-4 shadow-sm" id="submitBtn">
                            <i class="bi bi-check2-circle me-1"></i> Save Delivery & Update Stock
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    function handleProductChange() {
        const select = document.getElementById('product_id');
        const selected = select.options[select.selectedIndex];
        
        if (selected && selected.value) {
            const unit = selected.getAttribute('data-unit') || 'Ltr';
            const cost = selected.getAttribute('data-cost') || '0';
            const stock = selected.getAttribute('data-stock') || '0';
            const tank = selected.getAttribute('data-tank');

            document.getElementById('quantityUnitLabel').textContent = unit;
            document.getElementById('unit_price').value = parseFloat(cost).toFixed(2);
            document.getElementById('productStockBadge').innerHTML = `Current Live Stock: <strong>${parseFloat(stock).toFixed(2)} ${unit}</strong>` + (tank ? ` &bull; Assigned to: <em>${tank}</em>` : '');

            if (tank && !document.getElementById('tank_no').value) {
                document.getElementById('tank_no').value = tank;
            }
        } else {
            document.getElementById('quantityUnitLabel').textContent = 'Ltr';
            document.getElementById('productStockBadge').textContent = 'Select a product to view stock';
        }
        calculateBill();
    }

    function handlePumpChange() {
        const pumpSelect = document.getElementById('pump_id');
        const selected = pumpSelect.options[pumpSelect.selectedIndex];

        if (selected && selected.value) {
            const prodId = selected.getAttribute('data-product-id');
            const tank = selected.getAttribute('data-tank');

            if (tank) {
                document.getElementById('tank_no').value = tank;
            }

            if (prodId) {
                const prodSelect = document.getElementById('product_id');
                if (prodSelect.value != prodId) {
                    prodSelect.value = prodId;
                    handleProductChange();
                }
            }
        }
    }

    function calculateBill() {
        const qty = parseFloat(document.getElementById('quantity').value) || 0;
        const rate = parseFloat(document.getElementById('unit_price').value) || 0;
        const total = qty * rate;

        const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
        const unpaid = Math.max(0, total - paid);

        document.getElementById('totalBillDisplay').textContent = 'Rs. ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('unpaidDueDisplay').textContent = 'Rs. ' + unpaid.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('pump_id').value) {
            handlePumpChange();
        }
        handleProductChange();
        calculateBill();
    });
</script>
@endpush
@endsection
