@extends('layouts.app')

@section('title', 'New Shift Closing')
@section('page_title', 'New Shift Closing Sheet')

@section('page_badge')
    <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1 small">
        <i class="bi bi-clock-history me-1"></i> Register Closing
    </span>
@endsection

@section('page_actions')
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-secondary border px-2 py-1 small d-none d-md-inline-block">
            <i class="bi bi-person-circle me-1 text-primary"></i> {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})
        </span>
        <a href="{{ route('shift-closings.index') }}" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-arrow-left me-1"></i> <span class="d-none d-sm-inline">Back to Register</span><span class="d-sm-none">Back</span>
        </a>
    </div>
@endsection

@push('styles')
<style>
    .denomination-table {
        table-layout: fixed;
        width: 100%;
    }
    .denomination-table th, .denomination-table td {
        padding: 0.35rem 0.4rem;
        vertical-align: middle;
    }
    .note-input {
        font-size: 0.92rem;
        padding: 0.25rem 0.35rem;
    }
    .note-input::-webkit-inner-spin-button,
    .note-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .note-input {
        -moz-appearance: textfield;
    }
    .kpi-title {
        font-size: 0.68rem;
        letter-spacing: 0.5px;
    }
    .kpi-value {
        font-size: clamp(0.95rem, 3.5vw, 1.25rem);
    }
</style>
@endpush

@section('content')
<form action="{{ route('shift-closings.store') }}" method="POST" id="shiftClosingForm" novalidate>
    @csrf

    <!-- Top Live KPI Ribbon (Instant Financial Health) -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card-custom p-2 p-sm-3 bg-white border-start border-4 border-primary h-100 shadow-sm">
                <div class="text-muted small fw-semibold text-uppercase kpi-title text-truncate">Gross Total Sales</div>
                <div class="fw-bold text-dark mt-1 font-monospace kpi-value text-truncate" id="kpi_sales">Rs. 0.00</div>
                <small class="text-secondary d-none d-sm-block" style="font-size: 0.72rem;">Shift gross billings</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card-custom p-2 p-sm-3 bg-white border-start border-4 border-info h-100 shadow-sm">
                <div class="text-muted small fw-semibold text-uppercase kpi-title text-truncate">Expected Net Cash</div>
                <div class="fw-bold text-info mt-1 font-monospace kpi-value text-truncate" id="kpi_expected">Rs. 0.00</div>
                <small class="text-secondary d-none d-sm-block" style="font-size: 0.72rem;">Sales minus returns & payouts</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card-custom p-2 p-sm-3 bg-white border-start border-4 border-success h-100 shadow-sm">
                <div class="text-muted small fw-semibold text-uppercase kpi-title text-truncate">Actual Collected</div>
                <div class="fw-bold text-success mt-1 font-monospace kpi-value text-truncate" id="kpi_actual">Rs. 0.00</div>
                <small class="text-secondary d-none d-sm-block" style="font-size: 0.72rem;">Cash & digital funds</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card-custom p-2 p-sm-3 bg-white border-start border-4 border-secondary h-100 shadow-sm" id="kpi_variance_card">
                <div class="d-flex justify-content-between align-items-center gap-1">
                    <span class="text-muted small fw-semibold text-uppercase kpi-title text-truncate">Shift Variance</span>
                    <span class="badge bg-success small py-0 px-1" id="kpi_variance_badge" style="font-size: 0.68rem;">Balanced</span>
                </div>
                <div class="fw-bold text-dark mt-1 font-monospace kpi-value text-truncate" id="kpi_variance">Rs. 0.00</div>
                <small class="text-secondary d-none d-sm-block" id="kpi_variance_text" style="font-size: 0.72rem;">Zero variance</small>
            </div>
        </div>
    </div>

    <!-- Step 1: Shift & Invoice Header Card -->
    <div class="card-custom p-3 mb-3 bg-white shadow-sm border">
        <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">1</span>
                <h6 class="fw-bold text-dark m-0">Shift Details & Invoice Serial Range</h6>
            </div>
            <div class="text-muted small d-none d-sm-block">
                <i class="bi bi-shield-check text-success me-1"></i> Authenticated Session &bull; Auto-calculating invoice count
            </div>
        </div>

        <div class="row g-2 g-md-3 align-items-end">
            <div class="col-12 col-sm-6 col-lg-2">
                <label class="form-label small fw-semibold text-secondary">Closing Date <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-2">
                <label class="form-label small fw-semibold text-secondary">Shift Cycle <span class="text-danger">*</span></label>
                <select name="shift_type" class="form-select form-select-sm fw-semibold" required>
                    <option value="morning">☀️ Morning Shift</option>
                    <option value="evening" selected>🌙 Evening Shift</option>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <label class="form-label small fw-semibold text-secondary">Invoice Start No.</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted">#</span>
                    <input type="number" min="1" id="invoice_start" name="invoice_start" class="form-control form-control-sm" placeholder="e.g. 1201" value="{{ old('invoice_start', $suggestedInvoiceStart ?? '') }}" oninput="calculateInvoiceCount()">
                </div>
                @if(!empty($suggestedInvoiceStart))
                    <small class="text-success d-block" style="font-size: 0.68rem;">
                        <i class="bi bi-magic me-1"></i>Auto (Prev #{{ $suggestedInvoiceStart - 1 }})
                    </small>
                @endif
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <label class="form-label small fw-semibold text-secondary">Invoice End No.</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted">#</span>
                    <input type="number" min="1" id="invoice_end" name="invoice_end" class="form-control form-control-sm" placeholder="e.g. 1250" value="{{ old('invoice_end') }}" oninput="calculateInvoiceCount()">
                </div>
                <small class="text-muted d-block" style="font-size: 0.68rem;">Enter ending bill #</small>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <label class="form-label small fw-semibold text-secondary">Total Invoices</label>
                <div class="input-group input-group-sm">
                    <input type="number" id="invoice_count_display" class="form-control form-control-sm bg-light fw-bold text-center text-primary" value="0" readonly>
                    <span class="input-group-text bg-light small text-muted">Bills</span>
                </div>
            </div>
            <div class="col-6 col-sm-6 col-lg-2">
                <label class="form-label small fw-semibold text-secondary">Logged Cashier</label>
                <input type="text" class="form-control form-control-sm bg-light text-muted" value="{{ auth()->user()->name }}" readonly>
            </div>
        </div>
    </div>

    <!-- Main Workspace (2-Column Balanced Premium Grid) -->
    <div class="row g-3">
        <!-- LEFT COLUMN: Sales, Adjustments, Party Payments & Digital Collections -->
        <div class="col-12 col-lg-6">
            <div class="d-flex flex-column gap-3">
                <!-- Step 2: Shift Gross Sales & Deductions -->
                <div class="card-custom p-3 bg-white shadow-sm border">
                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                        <span class="badge bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">2</span>
                        <h6 class="fw-bold text-dark m-0">
                            <i class="bi bi-receipt text-primary me-1"></i> Shift Sales & Deductions
                        </h6>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">
                            Total Shift Gross Sale (Rs.) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-dark">Rs.</span>
                            <input type="number" step="0.01" min="0" class="form-control fw-bold fs-5 text-dark" id="total_sale" name="total_sale" value="0" required oninput="calculateClosing()" placeholder="0.00">
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">Total sales billing recorded on POS terminal during this shift</small>
                    </div>

                    <div class="p-2 rounded bg-light border">
                        <div class="row g-2">
                            <div class="col-12 col-sm-5">
                                <label class="form-label small fw-semibold text-danger">(-) Sale Returns (Rs.)</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="returns_amount" name="returns_amount" value="0" oninput="calculateClosing()">
                            </div>
                            <div class="col-12 col-sm-7">
                                <label class="form-label small fw-semibold text-secondary">Return Invoice Number</label>
                                <input type="text" class="form-control form-control-sm" name="return_invoice_number" maxlength="100" placeholder="e.g. RET-1250">
                            </div>
                            <div class="col-12 col-sm-5">
                                <label class="form-label small fw-semibold text-danger">(-) Shift Expenses (Rs.)</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="expenses_amount" name="expenses_amount" value="0" oninput="calculateClosing()">
                            </div>
                            <div class="col-12 col-sm-7">
                                <label class="form-label small fw-semibold text-secondary">Expense Reason / Details</label>
                                <input type="text" class="form-control form-control-sm" name="expenses_details" maxlength="2000" placeholder="e.g. Shop supplies, fuel, tea">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Cash Paid to Parties -->
                <div class="card-custom p-3 bg-white shadow-sm border">
                    <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">3</span>
                            <h6 class="fw-bold text-dark m-0">
                                <i class="bi bi-cash-coin text-danger me-1"></i> Cash Paid to Parties / Suppliers (Outflow)
                            </h6>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" id="addPartyPayment">
                            <i class="bi bi-plus-lg me-1"></i> Add Row
                        </button>
                    </div>
                    <small class="text-muted d-block mb-3" style="font-size: 0.75rem;">
                        Instant cash payments given to suppliers or staff directly from register cash during this shift.
                    </small>

                    <div id="partyPaymentRows">
                        <div class="p-2 mb-2 rounded bg-light border" data-party-payment-row>
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-5">
                                    <label class="form-label small fw-semibold text-secondary">Search Party / Payee</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                        <input type="search" class="form-control form-control-sm" list="shiftClosingPartyList" data-party-search placeholder="Type name or phone..." autocomplete="off">
                                    </div>
                                    <input type="hidden" name="party_payments[0][party_id]" data-party-id>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small fw-semibold text-secondary">Amount (Rs.)</label>
                                    <input type="number" step="0.01" min="0.01" class="form-control form-control-sm shift-party-payment-amount fw-bold text-danger" name="party_payments[0][amount]" placeholder="0.00" oninput="calculateClosing()">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small fw-semibold text-secondary">Detail / Note</label>
                                    <input type="text" class="form-control form-control-sm" name="party_payments[0][details]" maxlength="1000" placeholder="Bill / memo reference">
                                </div>
                                <div class="col-12 col-md-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-party-payment title="Remove row">
                                        <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Remove Row</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <datalist id="shiftClosingPartyList">
                        @foreach($parties as $party)
                            <option value="{{ $party->name }}{{ $party->phone ? ' (' . $party->phone . ')' : '' }} [{{ ucfirst($party->type) }}] #{{ $party->id }}" data-party-id="{{ $party->id }}"></option>
                        @endforeach
                    </datalist>

                    <div class="d-flex justify-content-between align-items-center pt-2 small text-secondary">
                        <span>Total Cash Paid to Parties:</span>
                        <span class="font-monospace fw-bold text-danger fs-6" id="display_party_total">Rs. 0.00</span>
                    </div>
                </div>

                <!-- Step 4: Digital & Online Collections (Account Wise) -->
                <div class="card-custom p-3 bg-white shadow-sm border">
                    <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-info text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">4</span>
                            <h6 class="fw-bold text-dark m-0">
                                <i class="bi bi-bank text-primary me-1"></i> Digital & Online Collections Received
                            </h6>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" id="addAccountPayment">
                            <i class="bi bi-plus-lg me-1"></i> Add Row
                        </button>
                    </div>
                    <small class="text-muted d-block mb-3" style="font-size: 0.75rem;">
                        Select destination account for digital receipts (JazzCash, Bank, Wallets). Balance will be automatically updated.
                    </small>

                    <div id="accountPaymentRows">
                        <div class="p-2 mb-2 rounded bg-light border" data-account-payment-row>
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-5">
                                    <label class="form-label small fw-semibold text-secondary">Destination Account</label>
                                    <select class="form-select form-select-sm shift-account-select" name="account_payments[0][account_id]" onchange="calculateClosing()">
                                        <option value="">-- Choose Account / Wallet --</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}" data-type="{{ $acc->type }}">
                                                {{ $acc->name }} ({{ ucfirst($acc->type) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small fw-semibold text-secondary">Amount (Rs.)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white text-muted">Rs.</span>
                                        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm shift-account-payment-amount fw-bold text-primary" name="account_payments[0][amount]" placeholder="0.00" oninput="calculateClosing()">
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small fw-semibold text-secondary">Transaction Ref / Note</label>
                                    <input type="text" class="form-control form-control-sm" name="account_payments[0][description]" maxlength="500" placeholder="Txn ID / payer name">
                                </div>
                                <div class="col-12 col-md-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-account-payment title="Remove row">
                                        <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Remove Row</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 small text-secondary">
                        <span>Total Online Collections:</span>
                        <span class="font-monospace fw-bold text-primary fs-6" id="display_account_total">Rs. 0.00</span>
                    </div>

                    <!-- Hidden legacy inputs for total sum -->
                    <input type="hidden" id="jazzcash_amount" name="jazzcash_amount" value="0">
                    <input type="hidden" id="bank_amount" name="bank_amount" value="0">
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Physical Currency Denomination Counter & Audit Reconciliation Deck -->
        <div class="col-12 col-lg-6">
            <div class="d-flex flex-column gap-3">
                <!-- Step 5: Currency Denominations -->
                <div class="card-custom p-3 bg-white shadow-sm border">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">5</span>
                            <h6 class="fw-bold text-dark m-0">
                                <i class="bi bi-cash-stack text-success me-1"></i> Currency Denominations (Physical Drawer Count)
                            </h6>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 small" onclick="resetNotes()" style="font-size: 0.72rem;">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Notes
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 denomination-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 33%;">Denomination</th>
                                    <th style="width: 33%;" class="text-center">Count</th>
                                    <th style="width: 34%;" class="text-end">Subtotal (Rs.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="badge px-2 py-1" style="background:#e0e7ff; color:#3730a3; font-weight:700;">Rs. 5,000</span>
                                    </td>
                                    <td>
                                        <input type="number" min="0" class="form-control form-control-sm text-center fw-bold note-input" id="note_5000" name="note_5000" value="0" oninput="calculateClosing()" onfocus="this.select()">
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark text-nowrap" id="sub_5000">0.00</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge px-2 py-1" style="background:#f1f5f9; color:#0f172a; font-weight:700; border: 1px solid #cbd5e1;">Rs. 1,000</span>
                                    </td>
                                    <td>
                                        <input type="number" min="0" class="form-control form-control-sm text-center fw-bold note-input" id="note_1000" name="note_1000" value="0" oninput="calculateClosing()" onfocus="this.select()">
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark text-nowrap" id="sub_1000">0.00</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge px-2 py-1" style="background:#dcfce7; color:#166534; font-weight:700;">Rs. 500</span>
                                    </td>
                                    <td>
                                        <input type="number" min="0" class="form-control form-control-sm text-center fw-bold note-input" id="note_500" name="note_500" value="0" oninput="calculateClosing()" onfocus="this.select()">
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark text-nowrap" id="sub_500">0.00</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge px-2 py-1" style="background:#e0f2fe; color:#075985; font-weight:700;">Rs. 100</span>
                                    </td>
                                    <td>
                                        <input type="number" min="0" class="form-control form-control-sm text-center fw-bold note-input" id="note_100" name="note_100" value="0" oninput="calculateClosing()" onfocus="this.select()">
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark text-nowrap" id="sub_100">0.00</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge px-2 py-1" style="background:#ffedd5; color:#9a3412; font-weight:700;">Rs. 50</span>
                                    </td>
                                    <td>
                                        <input type="number" min="0" class="form-control form-control-sm text-center fw-bold note-input" id="note_50" name="note_50" value="0" oninput="calculateClosing()" onfocus="this.select()">
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark text-nowrap" id="sub_50">0.00</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge px-2 py-1" style="background:#ccfbf1; color:#115e59; font-weight:700;">Rs. 20</span>
                                    </td>
                                    <td>
                                        <input type="number" min="0" class="form-control form-control-sm text-center fw-bold note-input" id="note_20" name="note_20" value="0" oninput="calculateClosing()" onfocus="this.select()">
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark text-nowrap" id="sub_20">0.00</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge px-2 py-1" style="background:#f3e8ff; color:#6b21a8; font-weight:700;">Rs. 10</span>
                                    </td>
                                    <td>
                                        <input type="number" min="0" class="form-control form-control-sm text-center fw-bold note-input" id="note_10" name="note_10" value="0" oninput="calculateClosing()" onfocus="this.select()">
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark text-nowrap" id="sub_10">0.00</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge px-2 py-1" style="background:#fef3c7; color:#92400e; font-weight:700;">Coins & Change</span>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-center fw-bold note-input" id="coins" name="coins" value="0" oninput="calculateClosing()" onfocus="this.select()">
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark text-nowrap" id="sub_coins">0.00</td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light border-top-2">
                                <tr>
                                    <th colspan="2" class="text-dark py-2">
                                        <i class="bi bi-wallet2 text-success me-1"></i> Total Physical Cash:
                                    </th>
                                    <th class="text-end font-monospace fw-bold text-success py-2 text-nowrap" id="display_total_cash" style="font-size: clamp(1rem, 3.5vw, 1.25rem);">
                                        Rs. 0.00
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Step 6: Real-time Reconciliation & Submission Deck -->
                <div class="card-custom p-3 bg-white shadow border border-2 border-primary">
                    <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">6</span>
                            <h6 class="fw-bold text-dark m-0">Audit Reconciliation & Handover</h6>
                        </div>
                        <span class="badge bg-success small px-2 py-1" id="calc_status_badge">Balanced</span>
                    </div>

                    <div class="p-2 rounded bg-light mb-3">
                        <div class="d-flex justify-content-between align-items-center small text-secondary py-1 border-bottom gap-2">
                            <span>(+) Total Gross Sales:</span>
                            <span class="font-monospace fw-semibold text-nowrap" id="audit_gross">Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small text-danger py-1 border-bottom gap-2">
                            <span>(-) Returns, Expenses & Party Payouts:</span>
                            <span class="font-monospace fw-semibold text-nowrap" id="audit_deductions">- Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small text-dark fw-bold py-1 border-bottom gap-2">
                            <span>(=) Expected Register Cash:</span>
                            <span class="font-monospace text-primary text-nowrap" id="audit_expected">Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small text-success fw-bold py-1 border-bottom gap-2">
                            <span>Actual Collected (Physical Cash + Digital):</span>
                            <span class="font-monospace text-nowrap" id="audit_actual">Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 gap-2 flex-wrap">
                            <span class="fw-bold text-dark">Net Variance / Difference:</span>
                            <span class="font-monospace fw-bold fs-5 text-nowrap" id="display_difference">Rs. 0.00</span>
                        </div>
                    </div>

                    <div class="small text-center py-2 px-3 rounded mb-3" id="diff_alert_box" style="background-color: #f8fafc; font-size: 0.8rem;">
                        Cash and sales match with zero variance.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Closing Remarks / Handover Notes</label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Optional cashier handover remarks or explanation for any variance..."></textarea>
                    </div>

                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 pt-2 border-top">
                        <a href="{{ route('shift-closings.index') }}" class="btn btn-light btn-sm px-3 rounded-3 text-center order-2 order-sm-1">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2 order-1 order-sm-2" id="submitBtn">
                            <i class="bi bi-lock-fill"></i>
                            <span>Submit & Lock Shift Closing</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function calculateInvoiceCount() {
    const start = parseInt(document.getElementById('invoice_start').value) || 0;
    const end = parseInt(document.getElementById('invoice_end').value) || 0;
    document.getElementById('invoice_count_display').value = start > 0 && end >= start ? end - start + 1 : 0;
}

let partyPaymentIndex = 1;
const partyPaymentRows = document.getElementById('partyPaymentRows');
const partyOptions = Array.from(document.querySelectorAll('#shiftClosingPartyList option'));

function bindPartyPaymentRow(row) {
    const partyInput = row.querySelector('[data-party-search]');
    const partyIdInput = row.querySelector('[data-party-id]');

    partyInput.addEventListener('input', function () {
        const matchedParty = partyOptions.find(function (option) { return option.value === partyInput.value; });
        partyIdInput.value = matchedParty ? matchedParty.dataset.partyId : '';
        calculateClosing();
    });

    row.querySelector('[data-remove-party-payment]').addEventListener('click', function () {
        if (partyPaymentRows.querySelectorAll('[data-party-payment-row]').length > 1) {
            row.remove();
        } else {
            row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
        }
        calculateClosing();
    });
}

bindPartyPaymentRow(partyPaymentRows.querySelector('[data-party-payment-row]'));

document.getElementById('addPartyPayment').addEventListener('click', function () {
    const row = document.createElement('div');
    row.className = 'p-2 mb-2 rounded bg-light border';
    row.dataset.partyPaymentRow = '';
    row.innerHTML = `
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label small fw-semibold text-secondary">Search Party / Payee</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="search" class="form-control form-control-sm" list="shiftClosingPartyList" data-party-search placeholder="Type name or phone..." autocomplete="off">
                </div>
                <input type="hidden" name="party_payments[${partyPaymentIndex}][party_id]" data-party-id>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-secondary">Amount (Rs.)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted">Rs.</span>
                    <input type="number" step="0.01" min="0.01" class="form-control form-control-sm shift-party-payment-amount fw-bold text-danger" name="party_payments[${partyPaymentIndex}][amount]" placeholder="0.00" oninput="calculateClosing()">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-secondary">Detail / Note</label>
                <input type="text" class="form-control form-control-sm" name="party_payments[${partyPaymentIndex}][details]" maxlength="1000" placeholder="Bill / memo reference">
            </div>
            <div class="col-12 col-md-1">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-party-payment title="Remove row">
                    <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Remove Row</span>
                </button>
            </div>
        </div>`;
    partyPaymentRows.appendChild(row);
    bindPartyPaymentRow(row);
    partyPaymentIndex++;
});

let accountPaymentIndex = 1;
const accountPaymentRows = document.getElementById('accountPaymentRows');

function bindAccountPaymentRow(row) {
    row.querySelector('.shift-account-select')?.addEventListener('change', calculateClosing);
    row.querySelector('.shift-account-payment-amount')?.addEventListener('input', calculateClosing);
    row.querySelector('[data-remove-account-payment]')?.addEventListener('click', function () {
        if (accountPaymentRows.querySelectorAll('[data-account-payment-row]').length > 1) {
            row.remove();
        } else {
            row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
            const sel = row.querySelector('select');
            if (sel) sel.value = '';
        }
        calculateClosing();
    });
}

if (accountPaymentRows.querySelector('[data-account-payment-row]')) {
    bindAccountPaymentRow(accountPaymentRows.querySelector('[data-account-payment-row]'));
}

document.getElementById('addAccountPayment').addEventListener('click', function () {
    const row = document.createElement('div');
    row.className = 'p-2 mb-2 rounded bg-light border';
    row.dataset.accountPaymentRow = '';
    
    const firstSelect = accountPaymentRows.querySelector('.shift-account-select');
    const optionsHtml = firstSelect ? firstSelect.innerHTML : '';

    row.innerHTML = `
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label small fw-semibold text-secondary">Destination Account</label>
                <select class="form-select form-select-sm shift-account-select" name="account_payments[${accountPaymentIndex}][account_id]" onchange="calculateClosing()">
                    ${optionsHtml}
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-secondary">Amount (Rs.)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted">Rs.</span>
                    <input type="number" step="0.01" min="0.01" class="form-control form-control-sm shift-account-payment-amount fw-bold text-primary" name="account_payments[${accountPaymentIndex}][amount]" placeholder="0.00" oninput="calculateClosing()">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-secondary">Transaction Ref / Note</label>
                <input type="text" class="form-control form-control-sm" name="account_payments[${accountPaymentIndex}][description]" maxlength="500" placeholder="Txn ID / payer name">
            </div>
            <div class="col-12 col-md-1">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-account-payment title="Remove row">
                    <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Remove Row</span>
                </button>
            </div>
        </div>`;
    accountPaymentRows.appendChild(row);
    bindAccountPaymentRow(row);
    accountPaymentIndex++;
});

function resetNotes() {
    if (!confirm('Are you sure you want to reset currency note counts to 0?')) return;
    document.querySelectorAll('.note-input').forEach(function(input) { input.value = 0; });
    calculateClosing();
}

function formatRs(num) {
    return 'Rs. ' + Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function calculateClosing() {
    // 1. Note Calculations
    const n5000 = (parseInt(document.getElementById('note_5000').value) || 0);
    const n1000 = (parseInt(document.getElementById('note_1000').value) || 0);
    const n500  = (parseInt(document.getElementById('note_500').value) || 0);
    const n100  = (parseInt(document.getElementById('note_100').value) || 0);
    const n50   = (parseInt(document.getElementById('note_50').value) || 0);
    const n20   = (parseInt(document.getElementById('note_20').value) || 0);
    const n10   = (parseInt(document.getElementById('note_10').value) || 0);
    const coins = (parseFloat(document.getElementById('coins').value) || 0);

    const sub5000 = n5000 * 5000;
    const sub1000 = n1000 * 1000;
    const sub500  = n500 * 500;
    const sub100  = n100 * 100;
    const sub50   = n50 * 50;
    const sub20   = n20 * 20;
    const sub10   = n10 * 10;

    document.getElementById('sub_5000').innerText = sub5000.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('sub_1000').innerText = sub1000.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('sub_500').innerText  = sub500.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('sub_100').innerText  = sub100.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('sub_50').innerText   = sub50.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('sub_20').innerText   = sub20.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('sub_10').innerText   = sub10.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('sub_coins').innerText = coins.toLocaleString('en-US', {minimumFractionDigits: 2});

    const totalCashCounted = sub5000 + sub1000 + sub500 + sub100 + sub50 + sub20 + sub10 + coins;
    document.getElementById('display_total_cash').innerText = formatRs(totalCashCounted);

    // 2. Sales, Returns & Expenses
    const totalSale = parseFloat(document.getElementById('total_sale').value) || 0;
    const returns   = parseFloat(document.getElementById('returns_amount').value) || 0;
    const expenses  = parseFloat(document.getElementById('expenses_amount').value) || 0;

    // 3. Party Payments
    let totalPartyPayments = 0;
    document.querySelectorAll('[data-party-payment-row]').forEach(function(row) {
        const amt = parseFloat(row.querySelector('.shift-party-payment-amount').value) || 0;
        totalPartyPayments += amt;
    });
    document.getElementById('display_party_total').innerText = formatRs(totalPartyPayments);

    // Expected cash = Total Sale - Returns - Expenses - Party Payments
    const totalDeductions = returns + expenses + totalPartyPayments;
    const expectedCash = totalSale - totalDeductions;

    // 4. Digital Collections from Dynamic Account Rows
    let totalDigitalCollections = 0;
    let totalJazzcash = 0;
    let totalBank = 0;

    document.querySelectorAll('[data-account-payment-row]').forEach(function(row) {
        const select = row.querySelector('.shift-account-select');
        const amt = parseFloat(row.querySelector('.shift-account-payment-amount').value) || 0;
        if (select && select.value && amt > 0) {
            totalDigitalCollections += amt;
            const selectedOpt = select.options[select.selectedIndex];
            const accType = selectedOpt ? selectedOpt.dataset.type : '';
            if (accType === 'jazzcash') {
                totalJazzcash += amt;
            } else {
                totalBank += amt;
            }
        }
    });

    document.getElementById('display_account_total').innerText = formatRs(totalDigitalCollections);
    document.getElementById('jazzcash_amount').value = totalJazzcash;
    document.getElementById('bank_amount').value = totalBank;

    const actualCollected = totalCashCounted + totalDigitalCollections;

    // 5. Variance / Difference
    const diff = actualCollected - expectedCash;

    // 6. Update Top KPI Ribbon
    document.getElementById('kpi_sales').innerText = formatRs(totalSale);
    document.getElementById('kpi_expected').innerText = formatRs(expectedCash);
    document.getElementById('kpi_actual').innerText = formatRs(actualCollected);
    document.getElementById('kpi_variance').innerText = (diff > 0 ? '+' : '') + formatRs(diff);

    // 7. Update Audit Box
    document.getElementById('audit_gross').innerText = formatRs(totalSale);
    document.getElementById('audit_deductions').innerText = '- ' + formatRs(totalDeductions);
    document.getElementById('audit_expected').innerText = formatRs(expectedCash);
    document.getElementById('audit_actual').innerText = formatRs(actualCollected);

    const diffEl = document.getElementById('display_difference');
    const badgeEl = document.getElementById('calc_status_badge');
    const kpiBadge = document.getElementById('kpi_variance_badge');
    const kpiCard = document.getElementById('kpi_variance_card');
    const kpiText = document.getElementById('kpi_variance_text');
    const alertBox = document.getElementById('diff_alert_box');

    if (diff === 0) {
        diffEl.innerText = 'Rs. 0.00';
        diffEl.className = 'font-monospace fw-bold fs-5 text-success text-nowrap';
        badgeEl.className = 'badge bg-success';
        badgeEl.innerText = 'Balanced';
        kpiBadge.className = 'badge bg-success small py-0 px-1';
        kpiBadge.innerText = 'Balanced';
        kpiCard.className = 'card-custom p-2 p-sm-3 bg-white border-start border-4 border-success h-100 shadow-sm';
        kpiText.innerText = 'Exact match (Rs. 0)';
        alertBox.className = 'small text-center py-2 px-3 rounded mb-3 bg-success bg-opacity-10 text-success fw-semibold border border-success';
        alertBox.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Register is perfectly balanced. Counted funds match calculated sales.';
    } else if (diff > 0) {
        const text = '+Rs. ' + diff.toLocaleString('en-US', {minimumFractionDigits: 2});
        diffEl.innerText = text + ' (Surplus)';
        diffEl.className = 'font-monospace fw-bold fs-5 text-info text-nowrap';
        badgeEl.className = 'badge bg-info text-dark';
        badgeEl.innerText = 'Cash Surplus';
        kpiBadge.className = 'badge bg-info text-dark small py-0 px-1';
        kpiBadge.innerText = 'Surplus';
        kpiCard.className = 'card-custom p-2 p-sm-3 bg-white border-start border-4 border-info h-100 shadow-sm';
        kpiText.innerText = 'Excess received: ' + text;
        alertBox.className = 'small text-center py-2 px-3 rounded mb-3 bg-info bg-opacity-10 text-info fw-semibold border border-info';
        alertBox.innerHTML = '<i class="bi bi-info-circle-fill me-1"></i> <strong>Cash Surplus:</strong> Received ' + text + ' more than expected sales.';
    } else {
        const text = '-Rs. ' + Math.abs(diff).toLocaleString('en-US', {minimumFractionDigits: 2});
        diffEl.innerText = text + ' (Shortage)';
        diffEl.className = 'font-monospace fw-bold fs-5 text-danger text-nowrap';
        badgeEl.className = 'badge bg-danger';
        badgeEl.innerText = 'Cash Shortage';
        kpiBadge.className = 'badge bg-danger small py-0 px-1';
        kpiBadge.innerText = 'Shortage';
        kpiCard.className = 'card-custom p-2 p-sm-3 bg-white border-start border-4 border-danger h-100 shadow-sm';
        kpiText.innerText = 'Shortage: ' + text;
        alertBox.className = 'small text-center py-2 px-3 rounded mb-3 bg-danger bg-opacity-10 text-danger fw-semibold border border-danger';
        alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Cash Shortage:</strong> Register is short by ' + text + '.';
    }
}

// Keyboard shortcuts: pressing Enter in note inputs moves to next note input
const noteInputs = Array.from(document.querySelectorAll('.note-input'));
noteInputs.forEach(function(input, index) {
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (index + 1 < noteInputs.length) {
                noteInputs[index + 1].focus();
            } else {
                document.getElementById('total_sale').focus();
            }
        }
    });
});

calculateInvoiceCount();
calculateClosing();
</script>
@endpush
