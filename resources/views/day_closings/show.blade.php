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

            <!-- Statement Header (Print Only: Official Corporate Document Header) -->
            <div class="d-none d-print-block statement-print-header mb-3 pb-2 border-bottom border-dark border-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h4 class="fw-bold text-uppercase m-0" style="letter-spacing: 0.5px; font-size: 15pt; color: #000;">PROWARE TECHNOLOGIES</h4>
                        <div class="fw-bold text-dark" style="font-size: 11pt; margin-top: 2px;">DAILY CASH POSITION & DAY CLOSING STATEMENT</div>
                        <div class="text-secondary small" style="font-size: 8.5pt;">Financial Audit & Closing Ledger &bull; Statement Ref #DC-{{ str_pad($dayClosing->id, 5, '0', STR_PAD_LEFT) }}</div>
                    </div>
                    <div class="text-end" style="font-size: 9pt; line-height: 1.45; color: #000;">
                        <div><strong>Date:</strong> {{ $dayClosing->date->format('l, d F, Y') }}</div>
                        <div><strong>Status:</strong> {{ strtoupper($dayClosing->status) }}</div>
                        <div><strong>Finalized At:</strong> {{ $dayClosing->created_at->format('h:i A') }}</div>
                        <div><strong>Authorized Closer:</strong> {{ $dayClosing->closer->name ?? 'User #' . $dayClosing->closed_by }}</div>
                    </div>
                </div>
            </div>

            <!-- Header Section (Screen Only: Modern Web UI with Branding & Badges) -->
            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4 flex-wrap gap-3 d-print-none">
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

            <!-- Executive Mathematical Summary (Screen View: 4 Unified Performance Cards) -->
            <div class="day-equation-cards mb-4 d-print-none">
                <!-- 1. Total Opening Balance (All Accounts) -->
                <div class="day-equation-col">
                    <div class="p-3 rounded-3 bg-light border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem;">Step 1 &bull; Base</span>
                            <span class="badge bg-secondary text-white small px-2 py-0.5">All Accounts</span>
                        </div>
                        <div class="text-secondary small fw-semibold">Opening Balance</div>
                        <div class="fs-4 fw-bold font-monospace text-dark mt-1">Rs. {{ number_format($totalOpeningAllAccounts, 2) }}</div>
                        
                        <div class="mt-2 pt-1 border-top small text-dark" style="font-size: 0.72rem;">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><i class="bi bi-wallet2 me-1"></i>Cash Drawer:</span>
                                <span class="font-monospace fw-semibold">Rs. {{ number_format($cashOpening, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><i class="bi bi-building me-1"></i>Bank Accounts:</span>
                                <span class="font-monospace fw-semibold">Rs. {{ number_format($bankOpening, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><i class="bi bi-phone me-1"></i>JazzCash / Wallets:</span>
                                <span class="font-monospace fw-semibold">Rs. {{ number_format($jazzcashOpening, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Total Inflows (+) -->
                <div class="day-equation-col">
                    <div class="p-3 rounded-3 bg-success-subtle border border-success h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-success small fw-bold text-uppercase" style="font-size: 0.7rem;">Step 2 &bull; Inflow</span>
                            <span class="badge bg-success text-white small px-2 py-0.5"><i class="bi bi-plus-lg me-1"></i>PLUS</span>
                        </div>
                        <div class="text-success fw-bold small">(+) Payments Received</div>
                        <div class="fs-4 fw-bold font-monospace text-success mt-1">+Rs. {{ number_format($totalInAllAccounts, 2) }}</div>
                        
                        <div class="mt-2 pt-1 border-top border-success-subtle small text-dark" style="font-size: 0.72rem;">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-wallet2 me-1"></i>Cash Received:</span>
                                <span class="font-monospace fw-semibold">+Rs. {{ number_format($cashIn, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-building me-1"></i>Bank Deposits:</span>
                                <span class="font-monospace fw-semibold">+Rs. {{ number_format($bankIn, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-phone me-1"></i>JazzCash In:</span>
                                <span class="font-monospace fw-semibold">+Rs. {{ number_format($jazzcashIn, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Total Outflows (-) -->
                <div class="day-equation-col">
                    <div class="p-3 rounded-3 bg-danger-subtle border border-danger h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-danger small fw-bold text-uppercase" style="font-size: 0.7rem;">Step 3 &bull; Outflow</span>
                            <span class="badge bg-danger text-white small px-2 py-0.5"><i class="bi bi-dash-lg me-1"></i>MINUS</span>
                        </div>
                        <div class="text-danger fw-bold small">(-) Payments Disbursed</div>
                        <div class="fs-4 fw-bold font-monospace text-danger mt-1">-Rs. {{ number_format($totalOutAllAccounts, 2) }}</div>
                        
                        <div class="mt-2 pt-1 border-top border-danger-subtle small text-dark" style="font-size: 0.72rem;">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-wallet2 me-1"></i>Cash Paid Out:</span>
                                <span class="font-monospace fw-semibold">-Rs. {{ number_format($cashOut, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-building me-1"></i>Bank Out:</span>
                                <span class="font-monospace fw-semibold">-Rs. {{ number_format($bankOut, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-phone me-1"></i>JazzCash Out:</span>
                                <span class="font-monospace fw-semibold">-Rs. {{ number_format($jazzcashOut, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Final Total Closing Balance (=) -->
                <div class="day-equation-col">
                    <div class="p-3 rounded-3 bg-primary text-white border border-primary h-100 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-white-50 small fw-bold text-uppercase" style="font-size: 0.7rem;">Step 4 &bull; Result</span>
                            <span class="badge bg-white text-primary small px-2 py-0.5 fw-bold">= EQUALS</span>
                        </div>
                        <div class="text-white fw-bold small">Closing Balance</div>
                        <div class="fs-4 fw-bold font-monospace text-white mt-1">Rs. {{ number_format($totalClosingAllAccounts, 2) }}</div>
                        
                        <div class="mt-2 pt-1 border-top border-white-50 small" style="font-size: 0.72rem;">
                            <div class="d-flex justify-content-between text-white">
                                <span><i class="bi bi-wallet2 me-1 text-white-50"></i>Cash in Drawer:</span>
                                <span class="font-monospace fw-semibold">Rs. {{ number_format($cashClosing, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between text-white">
                                <span><i class="bi bi-building me-1 text-white-50"></i>Bank Balance:</span>
                                <span class="font-monospace fw-semibold">Rs. {{ number_format($bankClosing, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between text-white">
                                <span><i class="bi bi-phone me-1 text-white-50"></i>JazzCash:</span>
                                <span class="font-monospace fw-semibold">Rs. {{ number_format($jazzcashClosing, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Official Bank Statement Cash Flow Summary Strip (Print Only) -->
            <div class="mb-4 d-none d-print-block">
                <table class="table table-bordered align-middle mb-0 bank-summary-table" style="border: 2px solid #0f172a; width: 100%;">
                    <thead style="background-color: #f1f5f9; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0f172a;">
                        <tr>
                            <th class="text-center py-2 text-secondary" style="width: 25%;">1. Opening (All Accounts)</th>
                            <th class="text-center py-2 text-success" style="width: 25%;">2. (+) Inflow Received</th>
                            <th class="text-center py-2 text-danger" style="width: 25%;">3. (-) Outflow Paid</th>
                            <th class="text-center py-2 text-dark bg-secondary bg-opacity-10" style="width: 25%;">4. (=) Closing Business Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center py-2">
                                <span class="fw-bold font-monospace text-dark fs-6 d-block">Rs. {{ number_format($totalOpeningAllAccounts, 2) }}</span>
                                <small class="text-muted" style="font-size: 7pt;">Drawer: Rs. {{ number_format($cashOpening, 2) }} | Bank: Rs. {{ number_format($bankOpening, 2) }}</small>
                            </td>
                            <td class="text-center py-2" style="background-color: #f0fdf4;">
                                <span class="fw-bold font-monospace text-success fs-6 d-block">+ Rs. {{ number_format($totalInAllAccounts, 2) }}</span>
                                <small class="text-muted" style="font-size: 7pt;">Cash: +Rs. {{ number_format($cashIn, 2) }} | Bank: +Rs. {{ number_format($bankIn + $jazzcashIn, 2) }}</small>
                            </td>
                            <td class="text-center py-2" style="background-color: #fef2f2;">
                                <span class="fw-bold font-monospace text-danger fs-6 d-block">- Rs. {{ number_format($totalOutAllAccounts, 2) }}</span>
                                <small class="text-muted" style="font-size: 7pt;">Cash: -Rs. {{ number_format($cashOut, 2) }} | Bank: -Rs. {{ number_format($bankOut + $jazzcashOut, 2) }}</small>
                            </td>
                            <td class="text-center py-2" style="background-color: #f1f5f9;">
                                <span class="fw-bold font-monospace text-dark fs-6 d-block" style="color: #0f172a !important;">Rs. {{ number_format($totalClosingAllAccounts, 2) }}</span>
                                <small class="text-muted fw-bold" style="font-size: 7pt;">Drawer: Rs. {{ number_format($cashClosing, 2) }} | Bank: Rs. {{ number_format($bankClosing, 2) }}</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Shift Breakdown Comparison Table -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark mb-3 pb-1 border-bottom statement-section-title">
                    <span class="statement-icon me-1"><i class="bi bi-clock-history text-primary"></i></span> Shift Performance Summary
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Shift Details</th>
                                <th>Cashier</th>
                                <th class="text-end">Gross Sale</th>
                                <th class="text-end">(-) Returns</th>
                                <th class="text-end">(-) Expenses</th>
                                <th class="text-end">(-) Party Out</th>
                                <th class="text-end">(+) Counted Cash</th>
                                <th class="text-end">Online / Digital</th>
                                <th class="text-end">Shift Variance</th>
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
                                    <td class="text-end font-monospace text-danger">
                                        - Rs. {{ number_format($morningShift->returns_amount, 2) }}
                                        @if($morningShift->return_invoice_start && $morningShift->return_invoice_end)
                                            <small class="d-block text-muted">Invoices #{{ $morningShift->return_invoice_start }}-#{{ $morningShift->return_invoice_end }}{{ $morningShift->total_return_invoices > 0 ? ' (' . $morningShift->total_return_invoices . ')' : '' }}</small>
                                        @elseif($morningShift->return_invoice_number)
                                            <small class="d-block text-muted">Invoice {{ $morningShift->return_invoice_number }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace text-danger">
                                        - Rs. {{ number_format($morningShift->expenses_amount, 2) }}
                                        @if($morningShift->expenses_details)<small class="d-block text-muted">{{ $morningShift->expenses_details }}</small>@endif
                                    </td>
                                    <td class="text-end font-monospace text-danger">- Rs. {{ number_format($morningShift->partyPayments->sum('amount'), 2) }}</td>
                                    <td class="text-end font-monospace fw-bold text-success">+ Rs. {{ number_format($morningShift->total_counted_cash, 2) }}</td>
                                    <td class="text-end font-monospace">Rs. {{ number_format($morningShift->total_online_transfer, 2) }}</td>
                                    <td class="text-end font-monospace fw-bold {{ $morningShift->difference < 0 ? 'text-danger' : ($morningShift->difference > 0 ? 'text-primary' : 'text-success') }}">
                                        {{ $morningShift->difference > 0 ? '+' : '' }}Rs. {{ number_format($morningShift->difference, 2) }}
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
                                    <td class="text-end font-monospace text-danger">
                                        - Rs. {{ number_format($eveningShift->returns_amount, 2) }}
                                        @if($eveningShift->return_invoice_start && $eveningShift->return_invoice_end)
                                            <small class="d-block text-muted">Invoices #{{ $eveningShift->return_invoice_start }}-#{{ $eveningShift->return_invoice_end }}{{ $eveningShift->total_return_invoices > 0 ? ' (' . $eveningShift->total_return_invoices . ')' : '' }}</small>
                                        @elseif($eveningShift->return_invoice_number)
                                            <small class="d-block text-muted">Invoice {{ $eveningShift->return_invoice_number }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace text-danger">
                                        - Rs. {{ number_format($eveningShift->expenses_amount, 2) }}
                                        @if($eveningShift->expenses_details)<small class="d-block text-muted">{{ $eveningShift->expenses_details }}</small>@endif
                                    </td>
                                    <td class="text-end font-monospace text-danger">- Rs. {{ number_format($eveningShift->partyPayments->sum('amount'), 2) }}</td>
                                    <td class="text-end font-monospace fw-bold text-success">+ Rs. {{ number_format($eveningShift->total_counted_cash, 2) }}</td>
                                    <td class="text-end font-monospace">Rs. {{ number_format($eveningShift->total_online_transfer, 2) }}</td>
                                    <td class="text-end font-monospace fw-bold {{ $eveningShift->difference < 0 ? 'text-danger' : ($eveningShift->difference > 0 ? 'text-primary' : 'text-success') }}">
                                        {{ $eveningShift->difference > 0 ? '+' : '' }}Rs. {{ number_format($eveningShift->difference, 2) }}
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
                            <tr class="fw-bold total-row">
                                <td colspan="2">Cumulative Shift Totals:</td>
                                <td class="text-end font-monospace">Rs. {{ number_format($totSales, 2) }}</td>
                                <td class="text-end font-monospace text-danger">- Rs. {{ number_format($totReturns, 2) }}</td>
                                <td class="text-end font-monospace text-danger">- Rs. {{ number_format($totExpenses, 2) }}</td>
                                <td class="text-end font-monospace text-danger">- Rs. {{ number_format($totPartyPayments, 2) }}</td>
                                <td class="text-end font-monospace text-success">+ Rs. {{ number_format($totCounted, 2) }}</td>
                                <td class="text-end font-monospace">Rs. {{ number_format($totOnline, 2) }}</td>
                                <td class="text-end font-monospace {{ $dayClosing->total_difference < 0 ? 'text-danger' : ($dayClosing->total_difference > 0 ? 'text-primary' : 'text-success') }}">
                                    {{ $dayClosing->total_difference > 0 ? '+' : '' }}Rs. {{ number_format($dayClosing->total_difference, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Bank & Digital Accounts Position Table -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-1 border-bottom">
                    <h6 class="fw-bold text-dark m-0 statement-section-title">
                        <span class="statement-icon me-1"><i class="bi bi-bank text-primary"></i></span> Bank & Digital Accounts Daily Summary
                    </h6>
                    <span class="badge bg-light text-secondary border small">
                        Total Digital In: <strong class="font-monospace ms-1 text-primary">+ Rs. {{ number_format($totalDigitalIn, 2) }}</strong>
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
                                        {{ $acc->inflow > 0 ? '+ Rs. ' . number_format($acc->inflow, 2) : '-' }}
                                    </td>
                                    <td class="text-end font-monospace {{ $acc->outflow > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                        {{ $acc->outflow > 0 ? '- Rs. ' . number_format($acc->outflow, 2) : '-' }}
                                    </td>
                                    <td class="text-end font-monospace fw-bold {{ $acc->net > 0 ? 'text-success' : ($acc->net < 0 ? 'text-danger' : 'text-muted') }}">
                                        @if($acc->net > 0)
                                            + Rs. {{ number_format($acc->net, 2) }}
                                        @elseif($acc->net < 0)
                                            - Rs. {{ number_format(abs($acc->net), 2) }}
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
                            <tr class="fw-bold total-row">
                                <td colspan="2">Total Accounts Position:</td>
                                <td class="text-end font-monospace text-success">+ Rs. {{ number_format($accountSummaries->sum('inflow'), 2) }}</td>
                                <td class="text-end font-monospace text-danger">- Rs. {{ number_format($accountSummaries->sum('outflow'), 2) }}</td>
                                <td class="text-end font-monospace text-primary">
                                    {{ ($accountSummaries->sum('inflow') - $accountSummaries->sum('outflow')) >= 0 ? '+' : '-' }} Rs. {{ number_format(abs($accountSummaries->sum('inflow') - $accountSummaries->sum('outflow')), 2) }}
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
                    <h6 class="fw-bold text-dark m-0 statement-section-title">
                        <span class="statement-icon me-1"><i class="bi bi-people-fill text-danger"></i></span> Party & Supplier Transactions (Inflows & Outflows)
                    </h6>
                    <div class="small">
                        <span class="text-success me-2">In: <strong class="font-monospace">+ Rs. {{ number_format($totalPartyInflow, 2) }}</strong></span>
                        <span class="text-danger me-2">Out: <strong class="font-monospace">- Rs. {{ number_format($totalPartyOutflow, 2) }}</strong></span>
                        <span class="text-dark">Net: <strong class="font-monospace">{{ $netPartyMovement >= 0 ? '+' : '-' }} Rs. {{ number_format(abs($netPartyMovement), 2) }}</strong></span>
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
                                <th class="text-end">Amount (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($partyTransactions as $pt)
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <span class="statement-icon"><i class="bi bi-{{ isset($pt->party_type) && str_contains(strtolower($pt->party_type), 'return') ? 'arrow-return-left text-danger' : 'person-circle text-secondary' }} me-1"></i></span>
                                        {{ $pt->party_name }}
                                        <span class="badge {{ isset($pt->party_type) && str_contains(strtolower($pt->party_type), 'return') ? 'bg-danger-subtle text-danger border border-danger' : 'bg-light text-secondary border' }} ms-1" style="font-size: 0.68rem;">{{ ucfirst($pt->party_type ?? 'Party') }}</span>
                                        @if($pt->party_phone)
                                            <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;">{{ $pt->party_phone }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pt->type === 'payment_in')
                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-0.5 small">+ Payment In</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-0.5 small">- Payment Out</span>
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
                                        {{ $pt->type === 'payment_in' ? '+' : '-' }} Rs. {{ number_format($pt->amount, 2) }}
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
                                <tr class="fw-bold total-row">
                                    <td colspan="5">Total Net Party Cash Flow:</td>
                                    <td class="text-end font-monospace {{ $netPartyMovement >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $netPartyMovement >= 0 ? '+' : '-' }} Rs. {{ number_format(abs($netPartyMovement), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Direct Transactions (Payment In & Payment Out) -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark mb-3 pb-1 border-bottom statement-section-title">
                    <span class="statement-icon me-1"><i class="bi bi-journal-text text-secondary"></i></span> Direct Daily Vouchers & Payments ({{ $transactions->count() }})
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
                                    <th class="text-end">Amount (Rs.)</th>
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
                    <div class="p-3 bg-light rounded text-center text-muted small statement-remarks-box">
                        No direct vouchers recorded for this specific date. All cash activity originated from shifts.
                    </div>
                @endif
            </div>

            <!-- Remarks & Notes -->
            @if($dayClosing->remarks)
                <div class="p-3 bg-light rounded-3 border mb-4 statement-remarks-box">
                    <strong class="text-dark small d-block mb-1"><i class="bi bi-chat-left-quote me-1"></i> Remarks & Audit Notes:</strong>
                    <p class="m-0 text-secondary small">{{ $dayClosing->remarks }}</p>
                </div>
            @endif

            <!-- Signatures Section for Physical Print -->
            <div class="pt-5 mt-4 border-top statement-signatures">
                <div class="row text-center w-100 m-0">
                    <div class="col-4">
                        <div class="statement-sig-line pt-2">
                            <div class="small fw-bold text-dark mt-1">Prepared By</div>
                            <div class="small text-muted">{{ $dayClosing->closer->name ?? 'Cashier / Incharge' }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="statement-sig-line pt-2">
                            <div class="small fw-bold text-dark mt-1">Supervisor Verified</div>
                            <div class="small text-muted">Branch Incharge</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="statement-sig-line pt-2">
                            <div class="small fw-bold text-dark mt-1">Authorized Approval</div>
                            <div class="small text-muted">Managing Owner</div>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>

<style>
.day-equation-cards {
    display: grid !important;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 12px !important;
    width: 100% !important;
}
.day-equation-col {
    width: 100% !important;
    max-width: 100% !important;
    padding: 0 !important;
}
@media (max-width: 991px) {
    .day-equation-cards {
        display: flex !important;
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        padding-bottom: 6px !important;
    }
    .day-equation-col {
        min-width: 210px !important;
        flex: 0 0 210px !important;
    }
}
.bank-summary-table {
    border-collapse: collapse !important;
    width: 100% !important;
    border: 2px solid #0f172a !important;
}
.bank-summary-table th, .bank-summary-table td {
    border: 1px solid #cbd5e1 !important;
}

@media print {
    /* 1. Page Geometry & Base Resets */
    @page {
        size: A4 portrait;
        margin: 8mm 10mm 8mm 10mm;
    }
    html, body {
        background: #ffffff !important;
        color: #0f172a !important;
        font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
        font-size: 8.5pt !important;
        line-height: 1.25 !important;
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    /* Hide all web browser chrome & page buttons */
    .vip-navbar, .sub-header, .no-print, .btn, .alert, footer, nav, header, .modal, .modal-backdrop, .btn-close, .d-print-none {
        display: none !important;
    }

    /* CRITICAL: Force table-responsive to never scroll in print */
    .table-responsive {
        overflow: visible !important;
        display: block !important;
        width: 100% !important;
    }

    /* Full width statement layout - remove web card borders, shadows & margins */
    main, .container-fluid, .row, .col-12, .col-xl-10 {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }
    #printableStatement, .card-custom {
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
    }

    /* Hide web icons in print for clean statement feel */
    .statement-icon, i, .bi {
        display: none !important;
    }

    /* Official Statement Print Header */
    .statement-print-header {
        display: block !important;
        border-bottom: 2px solid #0f172a !important;
        margin-bottom: 10px !important;
        padding-bottom: 5px !important;
    }

    /* Bank Statement Summary Table */
    .bank-summary-table {
        width: 100% !important;
        border: 2px solid #0f172a !important;
        margin-bottom: 10px !important;
        page-break-inside: avoid !important;
    }
    .bank-summary-table th, .bank-summary-table td {
        border: 1px solid #0f172a !important;
    }

    /* Section titles */
    .statement-section-title {
        font-size: 9pt !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        border-bottom: 1.5px solid #0f172a !important;
        padding-bottom: 2px !important;
        margin-bottom: 5px !important;
        color: #0f172a !important;
    }

    /* Table styling - Crisp Accounting Statement Borders */
    .table {
        width: 100% !important;
        border-collapse: collapse !important;
        border: 1px solid #334155 !important;
        margin-bottom: 8px !important;
        page-break-inside: auto !important;
        table-layout: auto !important;
    }
    .table tr {
        page-break-inside: avoid !important;
    }
    .table th, .table td {
        border: 1px solid #cbd5e1 !important;
        padding: 2.5px 4px !important;
        font-size: 7.8pt !important;
        line-height: 1.2 !important;
        color: #0f172a !important;
        background-color: transparent !important;
    }
    .table thead th, .table th.table-light, tr.table-light th, tr.table-light td {
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        font-weight: 700 !important;
        border-bottom: 1.5px solid #0f172a !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .table tfoot tr, tr.total-row {
        border-top: 1.5px solid #0f172a !important;
        border-bottom: 2px solid #0f172a !important;
        font-weight: 800 !important;
        background-color: #f8fafc !important;
    }

    /* Statement Badges */
    .badge {
        border: none !important;
        background: transparent !important;
        color: #0f172a !important;
        font-weight: 600 !important;
        font-size: 7.5pt !important;
        padding: 0 !important;
    }

    /* Remarks Box */
    .statement-remarks-box {
        border: 1px solid #94a3b8 !important;
        background-color: #f8fafc !important;
        border-radius: 3px !important;
        padding: 4px 8px !important;
        margin: 6px 0 !important;
        page-break-inside: avoid !important;
        font-size: 8pt !important;
    }

    /* Signatures Section */
    .statement-signatures {
        margin-top: 20px !important;
        padding-top: 10px !important;
        border-top: 1px solid #94a3b8 !important;
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        page-break-inside: avoid !important;
    }
    .statement-signatures .col-4 {
        flex: 1 1 33.33% !important;
        width: 33.33% !important;
    }
    .statement-sig-line {
        border-top: 1.2px solid #0f172a !important;
        margin: 0 auto !important;
        padding-top: 4px !important;
        width: 75% !important;
    }
    .statement-sig-line small, .statement-sig-line .small {
        font-size: 8pt !important;
    }
}
</style>
@endsection
