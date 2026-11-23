@extends('layouts.app')

@section('title', 'Fuel Rate Revisions & Tank Revaluation')
@section('page_title', 'Fuel Rate Revisions & Stock Gain/Loss')

@section('page_badge')
    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1 small">
        <i class="bi bi-clock-history me-1"></i> Midnight 12:00 AM Rate Changes & Tank Revaluation
    </span>
@endsection

@section('page_actions')
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('rate-revisions.create') }}" class="btn btn-primary btn-sm rounded-3 shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Apply Rate Revision
        </a>
    </div>
@endsection

@section('content')
<!-- KPI Metric Ribbon -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-custom p-3 border-start border-4 border-primary h-100 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Rate Revisions</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalRevisions) }}</div>
            <div class="text-secondary small mt-1" style="font-size: 0.75rem;">
                Official price adjustments logged
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-custom p-3 border-start border-4 border-success h-100 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Windfall Stock Gain (Profit)</div>
            <div class="fs-4 fw-bold text-success mt-1">Rs. {{ number_format($totalGain, 2) }}</div>
            <div class="text-secondary small mt-1" style="font-size: 0.75rem;">
                <i class="bi bi-graph-up-arrow text-success"></i> Tank inventory gains from rate hikes
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-custom p-3 border-start border-4 border-danger h-100 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Stock Devaluation (Loss)</div>
            <div class="fs-4 fw-bold text-danger mt-1">Rs. {{ number_format(abs($totalLoss), 2) }}</div>
            <div class="text-secondary small mt-1" style="font-size: 0.75rem;">
                <i class="bi bi-graph-down-arrow text-danger"></i> Inventory devaluation from rate drops
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-custom p-3 border-start border-4 {{ $netImpact >= 0 ? 'border-success' : 'border-danger' }} h-100 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Net Revaluation Balance</div>
            <div class="fs-4 fw-bold {{ $netImpact >= 0 ? 'text-success' : 'text-danger' }} mt-1">
                {{ $netImpact >= 0 ? '+' : '' }}Rs. {{ number_format($netImpact, 2) }}
            </div>
            <div class="text-secondary small mt-1" style="font-size: 0.75rem;">
                Cumulative net financial impact on tanks
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card-custom p-3 mb-4 shadow-sm">
    <form method="GET" action="{{ route('rate-revisions.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Fuel Product</label>
            <select name="product_id" class="form-select form-select-sm">
                <option value="">All Fuel Products</option>
                @foreach($fuelProducts as $fp)
                    <option value="{{ $fp->id }}" {{ request('product_id') == $fp->id ? 'selected' : '' }}>
                        {{ $fp->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-secondary mb-1">Impact Type</label>
            <select name="impact" class="form-select form-select-sm">
                <option value="">All (Gains & Losses)</option>
                <option value="gain" {{ request('impact') == 'gain' ? 'selected' : '' }}>Rate Hike (Stock Gain)</option>
                <option value="loss" {{ request('impact') == 'loss' ? 'selected' : '' }}>Rate Drop (Stock Loss)</option>
            </select>
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-secondary mb-1">From Date</label>
            <input type="date" name="from_date" class="form-control form-select-sm" value="{{ request('from_date') }}">
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-secondary mb-1">To Date</label>
            <input type="date" name="to_date" class="form-control form-select-sm" value="{{ request('to_date') }}">
        </div>

        <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                <i class="bi bi-filter me-1"></i> Filter
            </button>
            <a href="{{ route('rate-revisions.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Revisions Log Table -->
<div class="card-custom shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="text-uppercase small text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                    <th class="ps-3">Effective Date & Time</th>
                    <th>Product</th>
                    <th>Old Rate</th>
                    <th>New Rate</th>
                    <th>Difference (Δ)</th>
                    <th>Tank Stock Volume</th>
                    <th>Stock Gain / Loss (Rs.)</th>
                    <th>Circular / Ref</th>
                    <th class="pe-3 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($revisions as $rev)
                    @php
                        $isGain = $rev->rate_difference > 0;
                        $isLoss = $rev->rate_difference < 0;
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold text-dark">{{ $rev->effective_date->format('d M, Y') }}</div>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border small" style="font-size: 0.7rem;">
                                <i class="bi bi-moon-stars me-1"></i> {{ $rev->effective_time ?: '12:00 AM Midnight' }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $rev->product?->name ?? 'Fuel Item' }}</div>
                            <small class="text-muted">{{ $rev->product?->code }}</small>
                        </td>
                        <td>
                            <span class="text-muted">Rs. {{ number_format($rev->old_sale_rate, 2) }}</span>
                        </td>
                        <td>
                            <span class="fw-bold text-dark">Rs. {{ number_format($rev->new_sale_rate, 2) }}</span>
                        </td>
                        <td>
                            @if($isGain)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-arrow-up-right me-1"></i> +Rs. {{ number_format($rev->rate_difference, 2) }}
                                </span>
                            @elseif($isLoss)
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1">
                                    <i class="bi bi-arrow-down-right me-1"></i> -Rs. {{ number_format(abs($rev->rate_difference), 2) }}
                                </span>
                            @else
                                <span class="badge bg-secondary px-2 py-1">No Change</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ number_format($rev->stock_at_revision, 2) }} Ltr</div>
                            @if($rev->affectedTank)
                                <small class="text-secondary">{{ $rev->affectedTank->tank_number }} ({{ $rev->affectedTank->name }})</small>
                            @else
                                <small class="text-secondary">Combined Tank Storage</small>
                            @endif
                        </td>
                        <td>
                            @if($isGain)
                                <div class="fw-bold text-success fs-6">
                                    +Rs. {{ number_format($rev->stock_gain_loss_amount, 2) }}
                                </div>
                                <small class="text-success small"><i class="bi bi-shield-check"></i> Inventory Asset Gain</small>
                            @elseif($isLoss)
                                <div class="fw-bold text-danger fs-6">
                                    -Rs. {{ number_format(abs($rev->stock_gain_loss_amount), 2) }}
                                </div>
                                <small class="text-danger small"><i class="bi bi-exclamation-triangle"></i> Inventory Devaluation</small>
                            @else
                                <div class="fw-semibold text-muted">Rs. 0.00</div>
                            @endif
                        </td>
                        <td>
                            <div class="small text-truncate" style="max-width: 160px;" title="{{ $rev->notification_ref }}">
                                {{ $rev->notification_ref ?: 'OGRA / OMC Circular' }}
                            </div>
                        </td>
                        <td class="pe-3 text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('rate-revisions.show', $rev->id) }}" class="btn btn-outline-primary btn-sm rounded-start" title="View Revaluation Statement">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form action="{{ route('rate-revisions.destroy', $rev->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this rate revision record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-end" title="Delete Record">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-fuel-pump fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            No fuel rate revisions recorded yet.
                            <div class="mt-2">
                                <a href="{{ route('rate-revisions.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i> Apply First Rate Revision
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($revisions->hasPages())
        <div class="p-3 border-top">
            {{ $revisions->links() }}
        </div>
    @endif
</div>
@endsection
