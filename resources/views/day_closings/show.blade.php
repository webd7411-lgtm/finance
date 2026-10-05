@extends('layouts.app')

@section('title', 'Day Closing Statement - ' . $dayClosing->date->format('d M, Y'))
@section('page_title', 'Day Closing Statement')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        Ref #DC-{{ str_pad($dayClosing->id, 5, '0', STR_PAD_LEFT) }}
    </span>
@endsection

@section('page_actions')
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 no-print" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Statement
        </button>
        <a href="{{ route('day-closings.index') }}" class="btn btn-light btn-sm rounded-3 no-print">
            <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
    </div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <!-- Printable Day Closing Statement Sheet -->
        <div class="card-custom p-4 p-md-5 bg-white shadow-sm" id="printableStatement">
            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4 flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="bg-primary text-white rounded-2 p-1 px-2 fw-bold">
                            <i class="bi bi-calendar2-check"></i>
                        </div>
                        <h4 class="fw-bold text-dark m-0">FinanceDesk</h4>
                    </div>
                    <h5 class="text-secondary fw-bold m-0">Daily Cash Position & Day Closing Statement</h5>
                    <small class="text-muted">Proware Technologies &bull; Financial Audit & Closing Ledger</small>
                </div>
                <div class="text-md-end">
                    <div class="d-inline-block text-start text-md-end">
                        <div class="mb-1">{!! $dayClosing->status_badge !!}</div>
                        <div class="small fw-bold text-dark fs-6">Date: {{ $dayClosing->date->format('l, d F, Y') }}</div>
                        <div class="small text-muted">Finalized At: {{ $dayClosing->created_at->format('h:i A') }}</div>
                        <div class="small text-muted">Authorized Closer: <strong>{{ $dayClosing->closer->name ?? 'User #' . $dayClosing->closed_by }}</strong></div>
                    </div>
                </div>
            </div>

            <!-- Executive Mathematical Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-3">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Step 1 &bull; Base</span>
                            <span class="badge bg-secondary text-white small">Starting</span>
                        </div>
                        <span class="text-secondary small d-block">Opening Cash (Carried Over)</span>
                        <h5 class="fw-bold text-dark font-monospace m-0 mt-1">Rs. {{ number_format($dayClosing->opening_cash, 2) }}</h5>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-success small fw-semibold text-uppercase" style="font-size: 0.7rem;">Step 2 &bull; Inflow</span>
                            <span class="badge bg-success text-white small">+ PLUS</span>
                        </div>
                        <span class="text-success small fw-semibold d-block">(+) Total Physical Cash In</span>
                        <h5 class="fw-bold text-success font-monospace m-0 mt-1">+ Rs. {{ number_format($dayClosing->total_cash_in, 2) }}</h5>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-danger small fw-semibold text-uppercase" style="font-size: 0.7rem;">Step 3 &bull; Outflow</span>
                            <span class="badge bg-danger text-white small">- MINUS</span>
                        </div>
                        <span class="text-danger small fw-semibold d-block">(-) Direct Voucher Payments</span>
                        <h5 class="fw-bold text-danger font-monospace m-0 mt-1">- Rs. {{ number_format($dayClosing->total_payments_out, 2) }}</h5>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="p-3 bg-primary text-white rounded-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-white-50 small fw-semibold text-uppercase" style="font-size: 0.7rem;">Step 4 &bull; Result</span>
                            <span class="badge bg-white text-primary small fw-bold">= EQUALS</span>
                        </div>
                        <span class="text-white-50 small fw-semibold d-block">(=) Final Verified Closing Cash</span>
                        <h5 class="fw-bold text-white font-monospace m-0 mt-1">Rs. {{ number_format($dayClosing->closing_cash, 2) }}</h5>
                    </div>
                </div>
            </div>

            <!-- Shift Breakdown Comparison Table -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark mb-3 pb-1 border-bottom">
                    <i class="bi bi-clock-history text-primary me-1"></i> Shift Performance Summary
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Shift Details</th>
                                <th>Cashier</th>
                                <th class="text-end">Gross Sale</th>
                                <th class="text-end">Returns</th>
                                <th class="text-end">Expenses</th>
                                <th class="text-end">Party Cash Out</th>
                                <th class="text-end">Counted Cash</th>
                                <th class="text-end">JazzCash / Bank</th>
                                <th class="text-end">Difference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Morning Shift -->
                            <tr>
                                <td class="fw-bold">
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1">Morning Shift</span>
                                    @if($morningShift && $morningShift->invoice_start && $morningShift->invoice_end)
                                        <small class="d-block text-muted mt-1">Invoices #{{ $morningShift->invoice_start }}-#{{ $morningShift->invoice_end }} ({{ $morningShift->total_invoices }})</small>
                                    @endif
                                </td>
                                @if($morningShift)
                                    <td>{{ $morningShift->cashier->name ?? 'N/A' }}</td>
                                    <td class="text-end font-monospace">Rs. {{ number_format($morningShift->total_sale, 2) }}</td>
                                    <td class="text-end font-monospace text-danger">Rs. {{ number_format($morningShift->returns_amount, 2) }}
                                        @if($morningShift->return_invoice_number)<small class="d-block text-muted">Invoice {{ $morningShift->return_invoice_number }}</small>@endif
                                    </td>
                                    <td class="text-end font-monospace text-danger">Rs. {{ number_format($morningShift->expenses_amount, 2) }}
                                        @if($morningShift->expenses_details)<small class="d-block text-muted">{{ $morningShift->expenses_details }}</small>@endif
                                    </td>
                                    <td class="text-end font-monospace text-danger">Rs. {{ number_format($morningShift->partyPayments->sum('amount'), 2) }}</td>
                                    <td class="text-end font-monospace fw-bold text-success">Rs. {{ number_format($morningShift->total_counted_cash, 2) }}</td>
                                    <td class="text-end font-monospace">Rs. {{ number_format($morningShift->total_online_transfer, 2) }}</td>
                                    <td class="text-end font-monospace fw-bold {{ $morningShift->difference < 0 ? 'text-danger' : ($morningShift->difference > 0 ? 'text-primary' : 'text-success') }}">
                                        Rs. {{ number_format($morningShift->difference, 2) }}
                                    </td>
                                @else
                                    <td colspan="8" class="text-center text-muted fst-italic py-2">No morning shift recorded for this date</td>
                                @endif
                            </tr>
                            <!-- Evening Shift -->
                            <tr>
                                <td class="fw-bold">
                                    <span class="badge bg-indigo-subtle text-indigo-emphasis border px-2 py-1" style="background-color: #ede9fe; color: #4338ca;">Evening Shift</span>
                                    @if($eveningShift && $eveningShift->invoice_start && $eveningShift->invoice_end)
                                        <small class="d-block text-muted mt-1">Invoices #{{ $eveningShift->invoice_start }}-#{{ $eveningShift->invoice_end }} ({{ $eveningShift->total_invoices }})</small>
                                    @endif
                                </td>
                                @if($eveningShift)
                                    <td>{{ $eveningShift->cashier->name ?? 'N/A' }}</td>
                                    <td class="text-end font-monospace">Rs. {{ number_format($eveningShift->total_sale, 2) }}</td>
                                    <td class="text-end font-monospace text-danger">Rs. {{ number_format($eveningShift->returns_amount, 2) }}
                                        @if($eveningShift->return_invoice_number)<small class="d-block text-muted">Invoice {{ $eveningShift->return_invoice_number }}</small>@endif
                                    </td>
                                    <td class="text-end font-monospace text-danger">Rs. {{ number_format($eveningShift->expenses_amount, 2) }}
                                        @if($eveningShift->expenses_details)<small class="d-block text-muted">{{ $eveningShift->expenses_details }}</small>@endif
                                    </td>
                                    <td class="text-end font-monospace text-danger">Rs. {{ number_format($eveningShift->partyPayments->sum('amount'), 2) }}</td>
                                    <td class="text-end font-monospace fw-bold text-success">Rs. {{ number_format($eveningShift->total_counted_cash, 2) }}</td>
                                    <td class="text-end font-monospace">Rs. {{ number_format($eveningShift->total_online_transfer, 2) }}</td>
                                    <td class="text-end font-monospace fw-bold {{ $eveningShift->difference < 0 ? 'text-danger' : ($eveningShift->difference > 0 ? 'text-primary' : 'text-success') }}">
                                        Rs. {{ number_format($eveningShift->difference, 2) }}
                                    </td>
                                @else
                                    <td colspan="8" class="text-center text-muted fst-italic py-2">No evening shift recorded for this date</td>
                                @endif
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            @php
                                $totSales = ($morningShift ? $morningShift->total_sale : 0) + ($eveningShift ? $eveningShift->total_sale : 0);
                                $totReturns = ($morningShift ? $morningShift->returns_amount : 0) + ($eveningShift ? $eveningShift->returns_amount : 0);
                                $totExpenses = ($morningShift ? $morningShift->expenses_amount : 0) + ($eveningShift ? $eveningShift->expenses_amount : 0);
                                $totPartyPayments = ($morningShift ? $morningShift->partyPayments->sum('amount') : 0) + ($eveningShift ? $eveningShift->partyPayments->sum('amount') : 0);
                                $totCounted = ($morningShift ? $morningShift->total_counted_cash : 0) + ($eveningShift ? $eveningShift->total_counted_cash : 0);
                                $totOnline = ($morningShift ? $morningShift->total_online_transfer : 0) + ($eveningShift ? $eveningShift->total_online_transfer : 0);
                            @endphp
                            <tr class="fw-bold">
                                <td colspan="2">Cumulative Shift Totals:</td>
                                <td class="text-end font-monospace">Rs. {{ number_format($totSales, 2) }}</td>
                                <td class="text-end font-monospace text-danger">Rs. {{ number_format($totReturns, 2) }}</td>
                                <td class="text-end font-monospace text-danger">Rs. {{ number_format($totExpenses, 2) }}</td>
                                <td class="text-end font-monospace text-danger">Rs. {{ number_format($totPartyPayments, 2) }}</td>
                                <td class="text-end font-monospace text-success">Rs. {{ number_format($totCounted, 2) }}</td>
                                <td class="text-end font-monospace">Rs. {{ number_format($totOnline, 2) }}</td>
                                <td class="text-end font-monospace {{ $dayClosing->total_difference < 0 ? 'text-danger' : ($dayClosing->total_difference > 0 ? 'text-primary' : 'text-success') }}">
                                    Rs. {{ number_format($dayClosing->total_difference, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Bank & Digital Accounts Position Table -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-1 border-bottom">
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-bank text-primary me-1"></i> Bank & Digital Accounts Daily Summary
                    </h6>
                    <span class="badge bg-light text-secondary border small">
                        Total Digital Collections: <strong class="font-monospace ms-1 text-primary">Rs. {{ number_format($totalDigitalIn, 2) }}</strong>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Account / Channel</th>
                                <th>Type</th>
                                <th class="text-end">Today's Inflow (+)</th>
                                <th class="text-end">Today's Outflow (-)</th>
                                <th class="text-end">Net Movement</th>
                                <th class="text-end">Closing Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accountSummaries as $acc)
                                <tr>
                                    <td class="fw-bold text-dark">
                                        {{ $acc->name }}
                                        @if($acc->account_number)
                                            <small class="text-muted d-block font-monospace">Acc: {{ $acc->account_number }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ ucfirst($acc->type) }}</span>
                                    </td>
                                    <td class="text-end font-monospace {{ $acc->inflow > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                                        {{ $acc->inflow > 0 ? '+Rs. ' . number_format($acc->inflow, 2) : '-' }}
                                    </td>
                                    <td class="text-end font-monospace {{ $acc->outflow > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                        {{ $acc->outflow > 0 ? '-Rs. ' . number_format($acc->outflow, 2) : '-' }}
                                    </td>
                                    <td class="text-end font-monospace fw-bold {{ $acc->net > 0 ? 'text-success' : ($acc->net < 0 ? 'text-danger' : 'text-muted') }}">
                                        @if($acc->net > 0)
                                            +Rs. {{ number_format($acc->net, 2) }}
                                        @elseif($acc->net < 0)
                                            -Rs. {{ number_format(abs($acc->net), 2) }}
                                        @else
                                            Rs. 0.00
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-dark">
                                        Rs. {{ number_format($acc->current_balance, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-2 text-muted small">No account activity recorded for this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="2">Total Accounts Position:</td>
                                <td class="text-end font-monospace text-success">+Rs. {{ number_format($accountSummaries->sum('inflow'), 2) }}</td>
                                <td class="text-end font-monospace text-danger">-Rs. {{ number_format($accountSummaries->sum('outflow'), 2) }}</td>
                                <td class="text-end font-monospace text-primary">
                                    Rs. {{ number_format($accountSummaries->sum('inflow') - $accountSummaries->sum('outflow'), 2) }}
                                </td>
                                <td class="text-end font-monospace text-dark">
                                    Rs. {{ number_format($accountSummaries->sum('current_balance'), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Daily Party & Khata Transactions Table -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-1 border-bottom">
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-people-fill text-danger me-1"></i> Party & Supplier Transactions (Inflows & Outflows)
                    </h6>
                    <div class="small">
                        <span class="text-success me-2">In: <strong class="font-monospace">+Rs. {{ number_format($totalPartyInflow, 2) }}</strong></span>
                        <span class="text-danger me-2">Out: <strong class="font-monospace">-Rs. {{ number_format($totalPartyOutflow, 2) }}</strong></span>
                        <span class="text-dark">Net: <strong class="font-monospace">{{ $netPartyMovement >= 0 ? '+' : '-' }}Rs. {{ number_format(abs($netPartyMovement), 2) }}</strong></span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Party / Payee</th>
                                <th>Type</th>
                                <th>Shift / Timing</th>
                                <th>Channel / Mode</th>
                                <th>Description / Memo</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($partyTransactions as $pt)
                                <tr>
                                    <td class="fw-bold text-dark">
                                        {{ $pt->party_name }}
                                        <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.68rem;">{{ ucfirst($pt->party_type) }}</span>
                                        @if($pt->party_phone)
                                            <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;">{{ $pt->party_phone }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pt->type === 'payment_in')
                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-0.5 small">Payment In</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-0.5 small">Payment Out</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pt->shift_name)
                                            <span class="badge bg-light text-dark border px-2 py-0.5 small">{{ $pt->shift_name }}</span>
                                        @else
                                            <span class="badge bg-light text-secondary border px-2 py-0.5 small">Direct (Non-Shift)</span>
                                        @endif
                                    </td>
                                    <td>{{ $pt->channel }}</td>
                                    <td class="small text-muted">{{ $pt->details }}</td>
                                    <td class="text-end font-monospace fw-bold {{ $pt->type === 'payment_in' ? 'text-success' : 'text-danger' }}">
                                        {{ $pt->type === 'payment_in' ? '+' : '-' }}Rs. {{ number_format($pt->amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-2 text-muted small">No party payments recorded for this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($partyTransactions->isNotEmpty())
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="5">Total Net Party Cash Flow:</td>
                                    <td class="text-end font-monospace {{ $netPartyMovement >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $netPartyMovement >= 0 ? '+' : '-' }}Rs. {{ number_format(abs($netPartyMovement), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Direct Transactions (Payment In & Payment Out) -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark mb-3 pb-1 border-bottom">
                    <i class="bi bi-journal-text text-secondary me-1"></i> Direct Daily Vouchers & Payments ({{ $transactions->count() }})
                </h6>
                @if($transactions->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Voucher #</th>
                                    <th>Type</th>
                                    <th>Party / Counterparty</th>
                                    <th>Account / Mode</th>
                                    <th>Description / Category</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $tx)
                                    <tr>
                                        <td class="font-monospace fw-bold">#{{ str_pad($tx->id, 5, '0', STR_PAD_LEFT) }}</td>
                                        <td>{!! $tx->type_badge !!}</td>
                                        <td class="fw-semibold">{{ $tx->party->name ?? 'Direct Counter' }}</td>
                                        <td>{{ $tx->account->name ?? 'N/A' }}</td>
                                        <td class="small text-muted">{{ $tx->description ?? '-' }}</td>
                                        <td class="text-end font-monospace fw-bold {{ $tx->type === 'payment_in' ? 'text-success' : 'text-danger' }}">
                                            {{ $tx->type === 'payment_in' ? '+' : '-' }} Rs. {{ number_format($tx->amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-3 bg-light rounded text-center text-muted small">
                        No direct vouchers recorded for this specific date. All cash activity originated from shifts.
                    </div>
                @endif
            </div>

            <!-- Remarks & Notes -->
            @if($dayClosing->remarks)
                <div class="p-3 bg-light rounded-3 border mb-4">
                    <strong class="text-dark small d-block mb-1"><i class="bi bi-chat-left-quote me-1"></i> Remarks & Audit Notes:</strong>
                    <p class="m-0 text-secondary small">{{ $dayClosing->remarks }}</p>
                </div>
            @endif

            <!-- Signatures Section for Physical Print -->
            <div class="pt-5 mt-4 border-top">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border-top border-dark mx-auto" style="width: 75%;"></div>
                        <div class="small fw-bold text-dark mt-1">Prepared By</div>
                        <div class="small text-muted">{{ $dayClosing->closer->name ?? 'Cashier / Incharge' }}</div>
                    </div>
                    <div class="col-4">
                        <div class="border-top border-dark mx-auto" style="width: 75%;"></div>
                        <div class="small fw-bold text-dark mt-1">Supervisor Verified</div>
                        <div class="small text-muted">Branch Incharge</div>
                    </div>
                    <div class="col-4">
                        <div class="border-top border-dark mx-auto" style="width: 75%;"></div>
                        <div class="small fw-bold text-dark mt-1">Authorized Approval</div>
                        <div class="small text-muted">Managing Owner</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
@media print {
    .vip-navbar, .no-print, header, nav, .btn, .card-custom {
        box-shadow: none !important;
        border: none !important;
    }
    body {
        background-color: #fff !important;
        color: #000 !important;
        padding: 0 !important;
    }
    .card-custom {
        padding: 0 !important;
    }
}
</style>
@endsection
