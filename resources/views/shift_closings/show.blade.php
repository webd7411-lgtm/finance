@extends('layouts.app')

@section('title', 'Shift Closing Sheet #' . $shiftClosing->id)
@section('page_title', 'Shift Closing Sheet')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        Ref #{{ str_pad($shiftClosing->id, 5, '0', STR_PAD_LEFT) }}
    </span>
@endsection

@section('page_actions')
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 no-print" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Statement
        </button>
        @if(auth()->user()->isOwner())
            <form action="{{ route('shift-closings.destroy', $shiftClosing->id) }}" method="POST" class="d-inline no-print" onsubmit="return confirm('Are you sure you want to delete this shift sheet? Party balances will be safely reversed.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-3">
                    <i class="bi bi-trash me-1"></i> Delete Sheet
                </button>
            </form>
        @endif
        <a href="{{ route('shift-closings.index') }}" class="btn btn-light btn-sm rounded-3 no-print">
            <i class="bi bi-arrow-left me-1"></i> Back to Shift History
        </a>
    </div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <!-- Printable Shift Statement Card -->
        <div class="card-custom p-4 p-md-5 bg-white shadow-sm" id="printableSheet">

            <!-- Statement Header (Print Only: Clean Official Financial Document Header) -->
            <div class="d-none d-print-block statement-print-header mb-3 pb-2 border-bottom border-dark border-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h4 class="fw-bold text-uppercase m-0" style="letter-spacing: 0.5px; font-size: 15pt; color: #000;">PROWARE TECHNOLOGIES</h4>
                        <div class="fw-bold text-dark" style="font-size: 11pt; margin-top: 2px;">DAILY SHIFT CLOSING STATEMENT</div>
                        <div class="text-secondary small" style="font-size: 8.5pt;">Finance Desk &bull; Register Reference #SC-{{ str_pad($shiftClosing->id, 5, '0', STR_PAD_LEFT) }}</div>
                    </div>
                    <div class="text-end" style="font-size: 9pt; line-height: 1.45; color: #000;">
                        <div><strong>Date:</strong> {{ $shiftClosing->date->format('d F, Y') }}</div>
                        <div><strong>Shift:</strong> {{ strtoupper($shiftClosing->shift_type) }} SHIFT &nbsp;|&nbsp; <strong>Status:</strong> {{ strtoupper($shiftClosing->status) }}</div>
                        <div><strong>Closing Time:</strong> {{ $shiftClosing->created_at->format('h:i A') }}</div>
                        <div><strong>Cashier:</strong> {{ $shiftClosing->cashier->name ?? 'User #' . $shiftClosing->cashier_id }}</div>
                    </div>
                </div>
            </div>

            <!-- Header Section (Screen Only: Modern Web UI with Branding & Badges) -->
            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4 flex-wrap gap-3 d-print-none">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="bg-primary text-white rounded-2 p-1 px-2 fw-bold">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <h4 class="fw-bold text-dark m-0">FinanceDesk</h4>
                    </div>
                    <h6 class="text-secondary fw-semibold m-0">Daily Shift Closing Register</h6>
                    <small class="text-muted">Proware Technologies &bull; Finance Management System</small>
                </div>
                <div class="text-md-end">
                    <div class="d-inline-block text-start text-md-end">
                        <div class="mb-1">{!! $shiftClosing->shift_badge !!} {!! $shiftClosing->status_badge !!}</div>
                        <div class="small fw-bold text-dark">Date: {{ $shiftClosing->date->format('d F, Y') }}</div>
                        <div class="small text-muted">Submitted: {{ $shiftClosing->created_at->format('h:i A') }}</div>
                        <div class="small text-muted">Cashier: <strong>{{ $shiftClosing->cashier->name ?? 'User #' . $shiftClosing->cashier_id }}</strong></div>
                    </div>
                </div>
            </div>

            <!-- Two-Column Breakdown (Physical Currency vs Sales Reconciliation) -->
            <div class="row g-4 mb-4 statement-grid">
                <!-- Left Table: Denomination Breakdown -->
                <div class="col-12 col-md-7 statement-col-left">
                    <h6 class="fw-bold text-dark mb-3 pb-1 border-bottom statement-section-title">
                        <span class="statement-icon me-1"><i class="bi bi-cash-stack text-success"></i></span> Physical Currency Count (Drawer Denominations)
                    </h6>
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Denomination</th>
                                <th class="text-center">Note Count</th>
                                <th class="text-end">Total Amount (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold">Rs. 5,000</td>
                                <td class="text-center font-monospace">{{ $shiftClosing->note_5000 }}</td>
                                <td class="text-end font-monospace">{{ number_format($shiftClosing->note_5000 * 5000, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Rs. 1,000</td>
                                <td class="text-center font-monospace">{{ $shiftClosing->note_1000 }}</td>
                                <td class="text-end font-monospace">{{ number_format($shiftClosing->note_1000 * 1000, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Rs. 500</td>
                                <td class="text-center font-monospace">{{ $shiftClosing->note_500 }}</td>
                                <td class="text-end font-monospace">{{ number_format($shiftClosing->note_500 * 500, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Rs. 100</td>
                                <td class="text-center font-monospace">{{ $shiftClosing->note_100 }}</td>
                                <td class="text-end font-monospace">{{ number_format($shiftClosing->note_100 * 100, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Rs. 50</td>
                                <td class="text-center font-monospace">{{ $shiftClosing->note_50 }}</td>
                                <td class="text-end font-monospace">{{ number_format($shiftClosing->note_50 * 50, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Rs. 20</td>
                                <td class="text-center font-monospace">{{ $shiftClosing->note_20 }}</td>
                                <td class="text-end font-monospace">{{ number_format($shiftClosing->note_20 * 20, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Rs. 10</td>
                                <td class="text-center font-monospace">{{ $shiftClosing->note_10 }}</td>
                                <td class="text-end font-monospace">{{ number_format($shiftClosing->note_10 * 10, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Coins</td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-end font-monospace">{{ number_format($shiftClosing->coins, 2) }}</td>
                            </tr>
                            <tr class="table-light total-row">
                                <th colspan="2">Total Physical Cash:</th>
                                <th class="text-end text-success font-monospace fs-6">Rs. {{ number_format($shiftClosing->total_counted_cash, 2) }}</th>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Right Table: Sales, Online Transfers & Reconciliation -->
                <div class="col-12 col-md-5 statement-col-right">
                    <h6 class="fw-bold text-dark mb-3 pb-1 border-bottom statement-section-title">
                        <span class="statement-icon me-1"><i class="bi bi-receipt text-primary"></i></span> Sales & Deductions Summary
                    </h6>
                    <table class="table table-sm table-bordered align-middle mb-4">
                        <tbody>
                            <tr>
                                <td class="text-muted">Invoice Range / Count:</td>
                                <td class="text-end font-monospace fw-bold">
                                    {{ $shiftClosing->total_invoices }} bills
                                    @if($shiftClosing->invoice_start && $shiftClosing->invoice_end)
                                        <small class="d-block text-muted">#{{ $shiftClosing->invoice_start }} - #{{ $shiftClosing->invoice_end }}</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Total Gross Sale:</td>
                                <td class="text-end font-monospace fw-bold text-dark">Rs. {{ number_format($shiftClosing->total_sale, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">(-) Sales Returns:</td>
                                <td class="text-end font-monospace text-danger">
                                    - Rs. {{ number_format($shiftClosing->returns_amount, 2) }}
                                    @if($shiftClosing->return_invoice_start && $shiftClosing->return_invoice_end)
                                        <small class="d-block text-muted">Invoices #{{ $shiftClosing->return_invoice_start }} - #{{ $shiftClosing->return_invoice_end }} ({{ $shiftClosing->total_return_invoices }} bills)</small>
                                    @elseif($shiftClosing->return_invoice_number)
                                        <small class="d-block text-muted">Invoice: {{ $shiftClosing->return_invoice_number }}</small>
                                    @endif
                                </td>
                            </tr>
                            @foreach($shiftClosing->partyPayments as $payment)
                                <tr>
                                    <td class="text-muted">(-) Cash Paid to {{ $payment->party->name ?? 'Deleted Party' }}:</td>
                                    <td class="text-end font-monospace text-danger">
                                        - Rs. {{ number_format($payment->amount, 2) }}
                                        <small class="d-block text-muted">{{ $payment->details }}</small>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="table-light total-row">
                                <th class="text-secondary">Expected Net Cash:</th>
                                <th class="text-end font-monospace text-secondary fs-6">Rs. {{ number_format($shiftClosing->expected_cash, 2) }}</th>
                            </tr>
                        </tbody>
                    </table>

                    <h6 class="fw-bold text-dark mb-3 pb-1 border-bottom statement-section-title">
                        <span class="statement-icon me-1"><i class="bi bi-bank text-primary"></i></span> Non-Cash / Account Collections
                    </h6>
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <tbody>
                            @if($shiftClosing->transactions->isNotEmpty())
                                @foreach($shiftClosing->transactions as $tx)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $tx->account->name ?? 'Account' }}</span>
                                            <span class="badge bg-light text-secondary border small">{{ ucfirst($tx->account->type ?? 'online') }}</span>
                                            @if($tx->description)
                                                <small class="d-block text-muted">{{ $tx->description }}</small>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-primary">Rs. {{ number_format($tx->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td>JazzCash / Wallets:</td>
                                    <td class="text-end font-monospace">Rs. {{ number_format($shiftClosing->jazzcash_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Direct Bank Transfers:</td>
                                    <td class="text-end font-monospace">Rs. {{ number_format($shiftClosing->bank_amount, 2) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Final Reconciliation Banner Box -->
            <div class="p-3 rounded-3 mb-4 statement-recon-box {{ $shiftClosing->difference < 0 ? 'bg-danger bg-opacity-10 border border-danger' : ($shiftClosing->difference > 0 ? 'bg-info bg-opacity-10 border border-info' : 'bg-success bg-opacity-10 border border-success') }}">
                <div class="row align-items-center text-center text-md-start g-2">
                    <div class="col-6 col-md-3 mb-2 mb-md-0 statement-recon-cell">
                        <div class="small text-muted fw-semibold recon-label">EXPECTED TO RECEIVE</div>
                        <div class="fs-5 fw-bold text-dark recon-val">Rs. {{ number_format($shiftClosing->expected_cash, 2) }}</div>
                    </div>
                    <div class="col-6 col-md-3 mb-2 mb-md-0 statement-recon-cell">
                        <div class="small text-muted fw-semibold recon-label">TOTAL CASH COLLECTED</div>
                        <div class="fs-5 fw-bold text-dark recon-val">Rs. {{ number_format($shiftClosing->total_counted_cash, 2) }}</div>
                    </div>
                    <div class="col-6 col-md-3 mb-2 mb-md-0 statement-recon-cell">
                        <div class="small text-muted fw-semibold recon-label">ACTUALLY COLLECTED</div>
                        <div class="fs-5 fw-bold text-dark recon-val">Rs. {{ number_format($shiftClosing->total_actual_received, 2) }}</div>
                    </div>
                    <div class="col-6 col-md-3 text-center text-md-end statement-recon-cell statement-recon-last">
                        <div class="small text-muted fw-semibold recon-label">SHIFT VARIANCE</div>
                        <div class="fs-5 fw-bold recon-variance {{ $shiftClosing->difference < 0 ? 'text-danger' : ($shiftClosing->difference > 0 ? 'text-info' : 'text-success') }}">
                            {{ $shiftClosing->difference > 0 ? '+' : '' }}Rs. {{ number_format($shiftClosing->difference, 2) }}
                            <div style="font-size: 0.75rem;" class="fw-normal recon-sub">
                                @if($shiftClosing->difference == 0)
                                    Balanced (Zero Variance)
                                @elseif($shiftClosing->difference > 0)
                                    Cash Surplus
                                @else
                                    Cash Shortage
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($shiftClosing->remarks)
                <div class="p-3 bg-light rounded-3 mb-4 statement-remarks-box">
                    <div class="small fw-bold text-secondary mb-1 recon-label">Cashier Remarks:</div>
                    <div class="small text-dark">{{ $shiftClosing->remarks }}</div>
                </div>
            @endif

            <!-- Signatures Section for Printing -->
            <div class="row mt-5 pt-4 border-top statement-signatures">
                <div class="col-4 text-center">
                    <div class="statement-sig-line pt-2">
                        <small class="fw-semibold text-dark d-block">Cashier Signature</small>
                        <small class="text-muted">{{ $shiftClosing->cashier->name ?? 'Cashier' }}</small>
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div class="statement-sig-line pt-2">
                        <small class="fw-semibold text-dark d-block">Branch Incharge</small>
                        <small class="text-muted">{{ $shiftClosing->verifier->name ?? 'Supervisor Verification' }}</small>
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div class="statement-sig-line pt-2">
                        <small class="fw-semibold text-dark d-block">Owner / Admin</small>
                        <small class="text-muted">Final Audit</small>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>

<style>
@media print {
    /* 1. Page Geometry & Base Resets */
    @page {
        size: A4 portrait;
        margin: 10mm 12mm 10mm 12mm;
    }
    html, body {
        background: #ffffff !important;
        color: #0f172a !important;
        font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
        font-size: 9.5pt !important;
        line-height: 1.3 !important;
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    /* Hide all web browser chrome & page buttons */
    .vip-navbar, .sub-header, .no-print, .btn, .alert, footer, nav, header, .modal, .modal-backdrop, .btn-close {
        display: none !important;
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
    #printableSheet, .card-custom {
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
    }

    /* Hide web icons in print for clean statement feel */
    .statement-icon {
        display: none !important;
    }

    /* Official Statement Print Header */
    .statement-print-header {
        display: block !important;
        border-bottom: 2px solid #0f172a !important;
        margin-bottom: 14px !important;
        padding-bottom: 8px !important;
    }

    /* Section titles */
    .statement-section-title {
        font-size: 9.5pt !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.4px !important;
        border-bottom: 1.5px solid #0f172a !important;
        padding-bottom: 3px !important;
        margin-bottom: 6px !important;
        color: #0f172a !important;
    }

    /* Side-by-Side 2-Column Statement Grid */
    .statement-grid {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        gap: 14px !important;
        width: 100% !important;
        margin-bottom: 10px !important;
    }
    .statement-col-left {
        flex: 0 0 56% !important;
        width: 56% !important;
        max-width: 56% !important;
    }
    .statement-col-right {
        flex: 0 0 42% !important;
        width: 42% !important;
        max-width: 42% !important;
    }

    /* Table styling - Crisp Accounting Statement Borders */
    .table {
        width: 100% !important;
        border-collapse: collapse !important;
        border: 1px solid #334155 !important;
        margin-bottom: 10px !important;
    }
    .table th, .table td {
        border: 1px solid #cbd5e1 !important;
        padding: 3.5px 6px !important;
        font-size: 9pt !important;
        line-height: 1.25 !important;
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
    .table tr.total-row th, .table tr.total-row td {
        border-top: 1.5px solid #0f172a !important;
        border-bottom: 2px solid #0f172a !important;
        font-weight: 800 !important;
        background-color: #f8fafc !important;
        color: #0f172a !important;
    }

    /* Statement Badges */
    .badge {
        border: 1px solid #475569 !important;
        background: transparent !important;
        color: #0f172a !important;
        font-weight: 600 !important;
        font-size: 8pt !important;
        padding: 1px 4px !important;
        border-radius: 3px !important;
    }

    /* Statement Reconciliation Summary Box */
    .statement-recon-box {
        border: 1.5px solid #0f172a !important;
        border-radius: 4px !important;
        background-color: #f8fafc !important;
        padding: 8px 12px !important;
        margin: 10px 0 !important;
        page-break-inside: avoid !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .statement-recon-box .row {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
    }
    .statement-recon-cell {
        flex: 1 1 25% !important;
        width: 25% !important;
        text-align: left !important;
        padding: 0 4px !important;
    }
    .statement-recon-last {
        text-align: right !important;
    }
    .recon-label {
        font-size: 7.5pt !important;
        text-transform: uppercase !important;
        font-weight: 700 !important;
        color: #475569 !important;
        letter-spacing: 0.5px !important;
    }
    .recon-val {
        font-size: 11pt !important;
        font-weight: 800 !important;
        color: #0f172a !important;
    }
    .recon-variance {
        font-size: 11pt !important;
        font-weight: 800 !important;
        color: #0f172a !important;
    }
    .recon-sub {
        font-size: 8pt !important;
        font-weight: 600 !important;
        color: #334155 !important;
    }

    /* Remarks Box */
    .statement-remarks-box {
        border: 1px solid #94a3b8 !important;
        background-color: #f8fafc !important;
        border-radius: 4px !important;
        padding: 6px 10px !important;
        margin: 8px 0 !important;
        page-break-inside: avoid !important;
    }

    /* Signatures Section */
    .statement-signatures {
        margin-top: 25px !important;
        padding-top: 12px !important;
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
    .statement-sig-line small {
        font-size: 8.5pt !important;
    }
}
</style>
@endsection
