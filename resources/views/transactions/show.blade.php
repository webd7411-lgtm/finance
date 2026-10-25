@extends('layouts.app')

@section('title', 'Voucher #' . $transaction->id . ' (80mm Thermal)')
@section('page_title', 'Transaction Voucher Receipt')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        Voucher #{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }} &bull; 80mm Thermal
    </span>
@endsection

@section('page_actions')
    <div class="d-flex gap-2 no-print">
        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 shadow-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Voucher (80mm)
        </button>
        <a href="{{ route('transactions.index', $transaction->trashed() ? ['view' => 'trash'] : []) }}" class="btn btn-light btn-sm rounded-3 border">
            <i class="bi bi-arrow-left me-1"></i> Back to Journal
        </a>
    </div>
@endsection

@push('styles')
<style>
    /* ----------------------------------------------------
       Screen Preview Styling (80mm Thermal Simulation)
       ---------------------------------------------------- */
    .thermal-preview-container {
        display: flex;
        justify-content: center;
        padding: 10px 0 30px;
    }

    .thermal-receipt {
        width: 100%;
        max-width: 380px;
        background: #ffffff;
        color: #111827;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace, sans-serif;
        font-size: 12px;
        line-height: 1.4;
        padding: 24px 20px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        box-sizing: border-box;
    }

    .thermal-divider-dashed {
        border-top: 1px dashed #cbd5e1;
        margin: 10px 0;
    }

    .thermal-divider-double {
        border-top: 1px dashed #cbd5e1;
        border-bottom: 1px dashed #cbd5e1;
        height: 4px;
        margin: 10px 0;
    }

    .thermal-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .thermal-table td {
        padding: 4px 0;
        vertical-align: top;
        font-size: 12px;
    }

    .thermal-label {
        color: #475569;
        white-space: nowrap;
        padding-right: 10px !important;
        font-size: 11.5px;
    }

    .thermal-value {
        color: #0f172a;
        word-break: break-word;
    }

    .thermal-sig-line {
        border-top: 1px dashed #94a3b8;
        padding-top: 5px;
        margin-top: 30px;
    }

    /* ----------------------------------------------------
       80mm Thermal Printer Print Media Rules
       ---------------------------------------------------- */
    @media print {
        @page {
            size: 80mm auto;
            margin: 0;
        }

        html, body {
            background: #ffffff !important;
            color: #000000 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Courier New", monospace, sans-serif !important;
            font-size: 11px !important;
            line-height: 1.3 !important;
            width: 80mm !important;
            max-width: 80mm !important;
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Hide all UI elements */
        .vip-navbar, .sub-header, .no-print, .btn, .alert, footer, nav, header, .card-header, .page-header {
            display: none !important;
        }

        main, .container-fluid, .row, [class*="col-"], .thermal-preview-container {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        .thermal-receipt {
            width: 74mm !important;
            max-width: 74mm !important;
            min-width: 74mm !important;
            margin: 0 auto !important;
            padding: 4mm 2mm !important;
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            background: #ffffff !important;
            color: #000000 !important;
            page-break-inside: avoid !important;
        }

        .thermal-receipt * {
            color: #000000 !important;
            background: transparent !important;
        }

        .thermal-divider-dashed {
            border-top: 1px dashed #000000 !important;
            margin: 6px 0 !important;
        }

        .thermal-divider-double {
            border-top: 1px dashed #000000 !important;
            border-bottom: 1px dashed #000000 !important;
            height: 3px !important;
            margin: 6px 0 !important;
        }

        .thermal-table td {
            padding: 2.5px 0 !important;
            font-size: 10.5px !important;
        }

        .thermal-label {
            color: #000000 !important;
            font-size: 10.5px !important;
        }

        .thermal-value {
            color: #000000 !important;
            font-size: 10.5px !important;
        }

        .thermal-sig-line {
            border-top: 1px dashed #000000 !important;
            padding-top: 4px !important;
            margin-top: 25px !important;
        }
    }
</style>
@endpush

@section('content')
<div class="thermal-preview-container flex-column align-items-center">
    @if($transaction->trashed())
        <div class="alert alert-danger mb-3 p-2.5 small border-danger no-print w-100 shadow-xs" style="max-width: 380px;">
            <i class="bi bi-trash3-fill me-1"></i> <strong>Audit Record:</strong> This voucher was deleted on {{ $transaction->deleted_at ? $transaction->deleted_at->format('d M Y, h:i A') : '-' }} by {{ $transaction->deletedBy->name ?? 'Admin' }}. Account and party balances were safely reversed at the time of deletion.
        </div>
    @endif
    <div id="printableVoucher" class="thermal-receipt">

        <!-- Business Header -->
        <div class="text-center mb-2">
            <img src="{{ asset('images/logo.png') }}" alt="Green Pharmacie" style="height: 48px; max-width: 175px; object-fit: contain;" class="mb-1" onerror="this.onerror=null; this.src='/images/logo.png';">
            <h6 class="fw-bold text-uppercase m-0" style="letter-spacing: 0.5px; font-size: 13px;">Green Pharmacie</h6>
            <div class="text-dark small" style="font-size: 10px;">Your Health Companion &bull; 24 Hrs 7 Days</div>
        </div>

        <div class="thermal-divider-dashed"></div>

        <!-- Voucher Type Banner -->
        <div class="text-center my-1">
            <div class="fw-bold text-uppercase" style="font-size: 12.5px; letter-spacing: 0.3px;">
                @if($transaction->type == 'payment_in')
                    *** RECEIPT VOUCHER ***
                @elseif($transaction->type == 'purchase_bill')
                    *** PURCHASE BILL ***
                @elseif(empty($transaction->party_id) && !empty($transaction->category_id))
                    *** EXPENSE VOUCHER ***
                @else
                    *** PAYMENT VOUCHER ***
                @endif
            </div>
            <div class="fw-bold font-monospace" style="font-size: 12px;">
                #VCH-{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}
            </div>
        </div>

        <div class="thermal-divider-dashed"></div>

        <!-- Voucher Meta Details -->
        <table class="thermal-table">
            <tbody>
                <tr>
                    <td class="thermal-label">Date:</td>
                    <td class="thermal-value text-end">{{ $transaction->date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="thermal-label">Time:</td>
                    <td class="thermal-value text-end">{{ $transaction->created_at ? $transaction->created_at->format('h:i A') : '-' }}</td>
                </tr>
                <tr>
                    <td class="thermal-label">Voucher Type:</td>
                    <td class="thermal-value text-end fw-semibold">
                        @if($transaction->type == 'payment_in')
                            Payment In (Received)
                        @elseif($transaction->type == 'purchase_bill')
                            Purchase Bill (Credit)
                        @elseif(empty($transaction->party_id) && !empty($transaction->category_id))
                            Expense Voucher
                        @else
                            Payment Out (Paid)
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="thermal-label">Channel:</td>
                    <td class="thermal-value text-end">
                        @if($transaction->account)
                            {{ $transaction->account->name }} <span class="small">({{ ucfirst($transaction->account->type) }})</span>
                        @else
                            Credit Purchase (No Cash Deduction)
                        @endif
                    </td>
                </tr>
                @if($transaction->party)
                <tr>
                    <td class="thermal-label">{{ $transaction->type == 'payment_in' ? 'Received From:' : ($transaction->type == 'purchase_bill' ? 'Supplier/Vendor:' : 'Paid To:') }}</td>
                    <td class="thermal-value text-end fw-bold">
                        {{ $transaction->party->name }}
                        <div class="small fw-normal text-muted" style="font-size: 9.5px;">
                            [{{ strtoupper($transaction->party->type) }}]@if($transaction->party->phone) &bull; {{ $transaction->party->phone }}@endif
                        </div>
                    </td>
                </tr>
                @endif
                @if($transaction->category)
                <tr>
                    <td class="thermal-label">Category:</td>
                    <td class="thermal-value text-end">{{ $transaction->category->name }}</td>
                </tr>
                @endif
                @if($transaction->bill_no)
                <tr>
                    <td class="thermal-label">Bill / Ref #:</td>
                    <td class="thermal-value text-end font-monospace">{{ $transaction->bill_no }}</td>
                </tr>
                @endif
                @if($transaction->description)
                <tr>
                    <td class="thermal-label">Description:</td>
                    <td class="thermal-value text-end" style="font-size: 10.5px;">{{ $transaction->description }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="thermal-divider-double"></div>

        <!-- Total Amount -->
        <div class="d-flex justify-content-between align-items-center py-1">
            <span class="fw-bold" style="font-size: 13px;">TOTAL AMOUNT:</span>
            <span class="fw-bold font-monospace" style="font-size: 16px;">
                Rs. {{ number_format($transaction->amount, 2) }}
            </span>
        </div>

        <div class="thermal-divider-double"></div>

        <!-- Signature Section -->
        <div class="d-flex justify-content-between text-center pt-2">
            <div style="width: 46%;">
                <div class="thermal-sig-line">
                    <div class="text-muted" style="font-size: 9.5px;">Prepared By</div>
                    <div class="fw-semibold" style="font-size: 11px;">{{ $transaction->creator->name ?? 'User' }}</div>
                </div>
            </div>
            <div style="width: 46%;">
                <div class="thermal-sig-line">
                    <div class="text-muted" style="font-size: 9.5px;">Receiver Sign</div>
                    <div class="fw-semibold" style="font-size: 11px;">&nbsp;</div>
                </div>
            </div>
        </div>

        <!-- Thermal Footer -->
        <div class="text-center mt-3 pt-2" style="border-top: 1px dashed #94a3b8; font-size: 9.5px; color: #64748b;">
            <div class="fw-semibold">*** SYSTEM GENERATED VOUCHER ***</div>
            <div style="font-size: 8.5px;">Printed: {{ now()->format('d/m/Y h:i A') }}</div>
            <div style="font-size: 8.5px; margin-top: 2px; color: #0f172a;">Developed by <strong>Prowave Technologies</strong> &bull; Ph: <strong>+92 317 3836223</strong></div>
        </div>

    </div>
</div>
@endsection
