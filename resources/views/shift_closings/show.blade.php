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
            <i class="bi bi-printer me-1"></i> Print Sheet
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
        <!-- Printable Shift Sheet Card -->
        <div class="card-custom p-4 p-md-5 bg-white shadow-sm" id="printableSheet">
            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4 flex-wrap gap-3">
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

            <!-- Two-Column Breakdown -->
            <div class="row g-4 mb-4">
                <!-- Left Table: Denomination Breakdown -->
                <div class="col-12 col-md-7">
                    <h6 class="fw-bold text-dark mb-3 pb-1 border-bottom">
                        <i class="bi bi-cash-stack text-success me-1"></i> Physical Currency Count (Drawer Denominations)
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
                            <tr class="table-light">
                                <th colspan="2">Total Physical Cash:</th>
                                <th class="text-end text-success font-monospace fs-6">Rs. {{ number_format($shiftClosing->total_counted_cash, 2) }}</th>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Right Table: Sales, Online Transfers & Reconciliation -->
                <div class="col-12 col-md-5">
                    <h6 class="fw-bold text-dark mb-3 pb-1 border-bottom">
                        <i class="bi bi-receipt text-primary me-1"></i> Sales & Expenses Summary
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
                                    @if($shiftClosing->return_invoice_number)
                                        <small class="d-block text-muted">Invoice: {{ $shiftClosing->return_invoice_number }}</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">(-) Shift Expenses:</td>
                                <td class="text-end font-monospace text-danger">
                                    - Rs. {{ number_format($shiftClosing->expenses_amount, 2) }}
                                    @if($shiftClosing->expenses_details)
                                        <small class="d-block text-muted">{{ $shiftClosing->expenses_details }}</small>
                                    @endif
                                </td>
                                @foreach($shiftClosing->partyPayments as $payment)
                                    <tr>
                                        <td class="text-muted">(-) Cash Paid to {{ $payment->party->name ?? 'Deleted Party' }}:</td>
                                        <td class="text-end font-monospace text-danger">
                                            - Rs. {{ number_format($payment->amount, 2) }}
                                            <small class="d-block text-muted">{{ $payment->details }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tr>
                            <tr class="table-light">
                                <th class="text-secondary">Expected Net Cash:</th>
                                <th class="text-end font-monospace text-secondary fs-6">Rs. {{ number_format($shiftClosing->expected_cash, 2) }}</th>
                            </tr>
                        </tbody>
                    </table>

                    <h6 class="fw-bold text-dark mb-3 pb-1 border-bottom">
                        <i class="bi bi-bank text-primary me-1"></i> Non-Cash / Account Collections
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
            <div class="p-3 rounded-3 mb-4 {{ $shiftClosing->difference < 0 ? 'bg-danger bg-opacity-10 border border-danger' : ($shiftClosing->difference > 0 ? 'bg-info bg-opacity-10 border border-info' : 'bg-success bg-opacity-10 border border-success') }}">
                <div class="row align-items-center text-center text-md-start">
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <div class="small text-muted fw-semibold">EXPECTED TO RECEIVE</div>
                        <div class="fs-5 fw-bold text-dark">Rs. {{ number_format($shiftClosing->expected_cash, 2) }}</div>
                    </div>
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <div class="small text-muted fw-semibold">ACTUALLY COLLECTED</div>
                        <div class="fs-5 fw-bold text-dark">Rs. {{ number_format($shiftClosing->total_actual_received, 2) }}</div>
                    </div>
                    <div class="col-12 col-md-4 text-md-end">
                        <div class="small text-muted fw-semibold">SHIFT VARIANCE</div>
                        <div class="fs-5 fw-bold {{ $shiftClosing->difference < 0 ? 'text-danger' : ($shiftClosing->difference > 0 ? 'text-info' : 'text-success') }}">
                            {{ $shiftClosing->difference > 0 ? '+' : '' }}Rs. {{ number_format($shiftClosing->difference, 2) }}
                            <div style="font-size: 0.75rem;" class="fw-normal">
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
                <div class="p-3 bg-light rounded-3 mb-4">
                    <div class="small fw-bold text-secondary mb-1">Cashier Remarks:</div>
                    <div class="small text-dark">{{ $shiftClosing->remarks }}</div>
                </div>
            @endif

            <!-- Signatures Section for Printing -->
            <div class="row mt-5 pt-4 border-top">
                <div class="col-4 text-center">
                    <div class="border-top border-dark mx-auto pt-2" style="max-width: 180px;">
                        <small class="fw-semibold text-dark d-block">Cashier Signature</small>
                        <small class="text-muted">{{ $shiftClosing->cashier->name ?? 'Cashier' }}</small>
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div class="border-top border-dark mx-auto pt-2" style="max-width: 180px;">
                        <small class="fw-semibold text-dark d-block">Branch Incharge</small>
                        <small class="text-muted">{{ $shiftClosing->verifier->name ?? 'Supervisor Verification' }}</small>
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div class="border-top border-dark mx-auto pt-2" style="max-width: 180px;">
                        <small class="fw-semibold text-dark d-block">Owner / Admin</small>
                        <small class="text-muted">Final Audit</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
