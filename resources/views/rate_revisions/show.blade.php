@extends('layouts.app')

@section('title', 'Fuel Rate Revision Certificate #' . $rateRevision->id)
@section('page_title', 'Rate Revision & Stock Revaluation Certificate')

@section('page_badge')
    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 small">
        <i class="bi bi-file-earmark-text me-1"></i> Revision #{{ str_pad($rateRevision->id, 4, '0', STR_PAD_LEFT) }}
    </span>
@endsection

@section('page_actions')
    <div class="d-flex align-items-center gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-3 shadow-sm">
            <i class="bi bi-printer me-1"></i> Print Certificate
        </button>
        <a href="{{ route('rate-revisions.index') }}" class="btn btn-outline-primary btn-sm rounded-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Revisions
        </a>
    </div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-9">
        <div class="card-custom shadow-sm p-4 p-md-5 bg-white">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">
                        <i class="bi bi-fuel-pump text-danger me-2"></i> Fuel Price Revision & Inventory Revaluation
                    </h4>
                    <div class="text-secondary small">
                        Caltex Rao Filling Station • Commercial ERP System
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge {{ $rateRevision->isGain() ? 'bg-success' : ($rateRevision->isLoss() ? 'bg-danger' : 'bg-secondary') }} px-3 py-2 fs-6">
                        {{ $rateRevision->isGain() ? 'WINDFALL STOCK GAIN' : ($rateRevision->isLoss() ? 'STOCK DEVALUATION' : 'NO CHANGE') }}
                    </span>
                    <div class="text-secondary small mt-1">Logged: {{ $rateRevision->created_at->format('d M, Y h:i A') }}</div>
                </div>
            </div>

            <!-- Meta details grid -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded-3">
                        <small class="text-muted d-block">Effective Date</small>
                        <span class="fw-bold text-dark">{{ $rateRevision->effective_date->format('d M, Y') }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded-3">
                        <small class="text-muted d-block">Effective Time</small>
                        <span class="fw-bold text-dark">{{ $rateRevision->effective_time ?: '12:00 AM Midnight' }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded-3">
                        <small class="text-muted d-block">Fuel Product</small>
                        <span class="fw-bold text-primary">{{ $rateRevision->product?->name }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded-3">
                        <small class="text-muted d-block">Notification Ref</small>
                        <span class="fw-bold text-dark">{{ $rateRevision->notification_ref ?: 'OGRA / OMC Circular' }}</span>
                    </div>
                </div>
            </div>

            <!-- Price & Tank Impact Table -->
            <div class="card border rounded-3 p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">Price Adjustment Breakdown</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light text-secondary small">
                            <tr>
                                <th>Old Sale Rate</th>
                                <th>New Sale Rate</th>
                                <th>Rate Difference (Δ)</th>
                                <th>Tank Volume (Midnight)</th>
                                <th class="text-end">Total Stock Impact (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fs-6">Rs. {{ number_format($rateRevision->old_sale_rate, 2) }}</td>
                                <td class="fs-6 fw-bold text-dark">Rs. {{ number_format($rateRevision->new_sale_rate, 2) }}</td>
                                <td>
                                    <span class="badge {{ $rateRevision->isGain() ? 'bg-success' : ($rateRevision->isLoss() ? 'bg-danger' : 'bg-secondary') }} px-2 py-1">
                                        {{ $rateRevision->formatted_difference }} / Ltr
                                    </span>
                                </td>
                                <td class="fw-semibold">
                                    {{ number_format($rateRevision->stock_at_revision, 2) }} Liters
                                    @if($rateRevision->affectedTank)
                                        <small class="d-block text-secondary">Tank: {{ $rateRevision->affectedTank->tank_number }}</small>
                                    @endif
                                </td>
                                <td class="text-end fs-5 fw-bold {{ $rateRevision->isGain() ? 'text-success' : ($rateRevision->isLoss() ? 'text-danger' : 'text-dark') }}">
                                    {{ $rateRevision->isGain() ? '+' : '' }}Rs. {{ number_format($rateRevision->stock_gain_loss_amount, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Notes & Signatures -->
            @if($rateRevision->remarks)
                <div class="p-3 bg-light rounded-3 mb-4">
                    <small class="text-muted d-block fw-bold mb-1">Remarks & Observations:</small>
                    <p class="mb-0 text-dark small">{{ $rateRevision->remarks }}</p>
                </div>
            @endif

            <div class="row pt-5 mt-4 border-top text-center text-secondary small">
                <div class="col-4">
                    <div class="border-top pt-2 mx-3">Station Manager</div>
                </div>
                <div class="col-4">
                    <div class="border-top pt-2 mx-3">Verified By (Admin)</div>
                </div>
                <div class="col-4">
                    <div class="border-top pt-2 mx-3">Proprietor / Owner</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
