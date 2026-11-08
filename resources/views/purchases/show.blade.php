@extends('layouts.app')

@section('title', 'Purchase #' . $purchase->id . ' Details')
@section('page_title', 'Stock Purchase Voucher #' . $purchase->id)

@section('page_badge')
    <div class="d-flex align-items-center gap-2">
        {!! $purchase->payment_status_badge !!}
        <span class="badge bg-light text-secondary border font-monospace">
            {{ $purchase->invoice_no ?: ('PO #' . $purchase->id) }}
        </span>
    </div>
@endsection

@section('page_actions')
    <div class="d-flex gap-2">
        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 shadow-sm text-nowrap">
            <i class="bi bi-arrow-left me-1"></i> Back to Purchases
        </a>
    </div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-9">

        <div class="card-custom shadow-sm mb-4">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold text-dark m-0">Fuel / Inventory Delivery Slip</h6>
                    <small class="text-muted" style="font-size: 0.78rem;">Recorded on {{ $purchase->purchase_date->format('d M Y') }}</small>
                </div>
                <div class="text-muted small">
                    Created by: <strong>{{ $purchase->creator->name ?? 'Admin' }}</strong>
                </div>
            </div>

            <div class="p-4">
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <span class="text-secondary small d-block">Delivered Quantity:</span>
                            <h4 class="fw-bold text-primary m-0">
                                {{ number_format($purchase->quantity, 2) }} {{ $purchase->product->unit_label ?? 'Units' }}
                            </h4>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <span class="text-secondary small d-block">Unit Purchase Rate:</span>
                            <h4 class="fw-bold text-dark m-0">
                                Rs. {{ number_format($purchase->unit_price, 2) }}
                            </h4>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <span class="text-secondary small d-block">Total Purchase Amount:</span>
                            <h4 class="fw-bold text-dark m-0">
                                Rs. {{ number_format($purchase->total_amount, 2) }}
                            </h4>
                        </div>
                    </div>
                </div>

                <!-- Product & Supplier Details -->
                <h6 class="fw-bold text-dark small text-uppercase mb-3" style="letter-spacing: 0.4px;">
                    Delivery Information
                </h6>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm m-0">
                        <tbody>
                            <tr>
                                <th class="table-light w-25">Product / Fuel:</th>
                                <td>
                                    <strong>{{ $purchase->product->name ?? 'N/A' }}</strong>
                                    <span class="badge bg-light text-secondary border ms-1">{{ $purchase->product->category_label ?? '' }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th class="table-light">Supplier / OMC:</th>
                                <td>
                                    @if($purchase->supplier)
                                        <a href="{{ route('ledgers.party', $purchase->supplier_id) }}" class="fw-bold text-primary text-decoration-none">
                                            {{ $purchase->supplier->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="table-light">Supplier Invoice / Bill No:</th>
                                <td class="font-monospace">{{ $purchase->invoice_no ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="table-light">Tanker / Bowser Vehicle No:</th>
                                <td class="font-monospace">{{ $purchase->vehicle_no ?: 'N/A' }}</td>
                            </tr>
                            @if($purchase->tank_no || $purchase->pump)
                                <tr>
                                    <th class="table-light">Destination Tank / Dispenser:</th>
                                    <td>
                                        <i class="bi bi-database text-primary me-1"></i>
                                        <strong>{{ $purchase->tank_no ?: 'Storage Tank' }}</strong>
                                        @if($purchase->pump)
                                            <a href="{{ route('pumps.show', $purchase->pump_id) }}" class="badge bg-light text-primary border ms-2 text-decoration-none">
                                                <i class="bi bi-speedometer2 me-1"></i>{{ $purchase->pump->name }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th class="table-light">Payment Breakdown:</th>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <span>Paid: <strong class="text-success">Rs. {{ number_format($purchase->paid_amount, 2) }}</strong>
                                            @if($purchase->paymentAccount)
                                                ({{ $purchase->paymentAccount->name }})
                                            @endif
                                        </span>
                                        <span>Balance Added to Supplier Khata: <strong class="text-danger">Rs. {{ number_format($purchase->unpaid_balance, 2) }}</strong></span>
                                    </div>
                                </td>
                            </tr>
                            @if($purchase->notes)
                                <tr>
                                    <th class="table-light">Chamber Dip / Notes:</th>
                                    <td>{{ $purchase->notes }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Danger Zone (Delete / Reversal) -->
                <div class="p-3 bg-danger bg-opacity-10 rounded-3 border border-danger-subtle d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <span class="fw-bold text-danger d-block">Reverse / Delete This Delivery</span>
                        <small class="text-secondary">Deleting this record automatically deducts {{ number_format($purchase->quantity, 2) }} units from current stock and reverses supplier/account balances.</small>
                    </div>
                    <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete and reverse this purchase delivery?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm px-3 rounded-2">
                            <i class="bi bi-trash me-1"></i> Delete & Reverse
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
