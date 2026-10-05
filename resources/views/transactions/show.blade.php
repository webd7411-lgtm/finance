@extends('layouts.app')

@section('title', 'Voucher #' . $transaction->id)
@section('page_title', 'Transaction Voucher Receipt')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        Voucher #{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}
    </span>
@endsection

@section('page_actions')
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 no-print" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Voucher
        </button>
        <a href="{{ route('transactions.index') }}" class="btn btn-light btn-sm rounded-3 no-print">
            <i class="bi bi-arrow-left me-1"></i> Back to Journal
        </a>
    </div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card-custom p-4 bg-white shadow-sm" id="printableVoucher">
            <!-- Voucher Header -->
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary text-white rounded-2 p-1 px-2 fw-bold">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark m-0">FinanceDesk</h5>
                        <small class="text-muted">Proware Technologies</small>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-bold font-monospace text-dark">#VCH-{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</div>
                    <small class="text-muted">{{ $transaction->date->format('d M, Y') }}</small>
                </div>
            </div>

            <!-- Voucher Title -->
            <div class="text-center py-2 mb-3 rounded-2 {{ $transaction->type == 'payment_in' ? 'bg-success bg-opacity-10 text-success border border-success' : ($transaction->type == 'purchase_bill' ? 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning' : 'bg-danger bg-opacity-10 text-danger border border-danger') }}">
                <h6 class="fw-bold m-0 text-uppercase">
                    @if($transaction->type == 'payment_in')
                        Receipt Voucher (Payment In)
                    @elseif($transaction->type == 'purchase_bill')
                        Purchase Bill (Credit Voucher)
                    @else
                        Payment Voucher (Payment Out)
                    @endif
                </h6>
            </div>

            <!-- Key Details Table -->
            <table class="table table-sm table-bordered align-middle mb-4">
                <tbody>
                    <tr>
                        <td class="text-muted" style="width: 35%;">Transaction Date:</td>
                        <td class="fw-bold text-dark">{{ $transaction->date->format('l, d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Payment Channel:</td>
                        <td class="fw-semibold text-dark">
                            @if($transaction->account)
                                {{ $transaction->account->name }} ({!! $transaction->account->type_badge !!})
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">Credit Purchase (No Cash Deduction)</span>
                            @endif
                        </td>
                    </tr>
                    @if($transaction->party)
                        <tr>
                            <td class="text-muted">{{ $transaction->type == 'payment_in' ? 'Received From:' : ($transaction->type == 'purchase_bill' ? 'Supplier / Vendor:' : 'Paid To:') }}</td>
                            <td class="fw-bold text-dark">
                                {{ $transaction->party->name }} 
                                <span class="badge bg-light text-secondary border ms-1">{{ strtoupper($transaction->party->type) }}</span>
                            </td>
                        </tr>
                    @endif
                    @if($transaction->category)
                        <tr>
                            <td class="text-muted">Expense Category:</td>
                            <td><span class="badge bg-warning-subtle text-warning border border-warning">{{ $transaction->category->name }}</span></td>
                        </tr>
                    @endif
                    @if($transaction->bill_no)
                        <tr>
                            <td class="text-muted">Bill / Reference #:</td>
                            <td class="font-monospace text-dark">{{ $transaction->bill_no }}</td>
                        </tr>
                    @endif
                    @if($transaction->description)
                        <tr>
                            <td class="text-muted">Description:</td>
                            <td class="text-dark small">{{ $transaction->description }}</td>
                        </tr>
                    @endif
                    <tr class="table-light">
                        <td class="fw-bold fs-6 text-dark">Total Amount:</td>
                        <td class="fw-bold fs-5 font-monospace {{ $transaction->type == 'payment_in' ? 'text-success' : ($transaction->type == 'purchase_bill' ? 'text-warning-emphasis' : 'text-danger') }}">
                            Rs. {{ number_format($transaction->amount, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Signatures Section -->
            <div class="row pt-4 mt-4 border-top">
                <div class="col-6 text-center">
                    <div class="border-top border-dark mx-auto pt-1" style="max-width: 140px;">
                        <small class="text-muted d-block">Prepared By</small>
                        <small class="fw-semibold text-dark">{{ $transaction->creator->name ?? 'User' }}</small>
                    </div>
                </div>
                <div class="col-6 text-center">
                    <div class="border-top border-dark mx-auto pt-1" style="max-width: 140px;">
                        <small class="text-muted d-block">Authorized Sign</small>
                        <small class="fw-semibold text-dark">Receiver / Payee</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
