@extends('layouts.app')

@section('title', 'Shift Closing')
@section('page_title', 'Shift Closing Sheets (Morning & Evening)')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-clock-history me-1 text-primary"></i> Daily Register
    </span>
@endsection

@section('page_actions')
    <a href="{{ route('shift-closings.create') }}" class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm text-nowrap w-100 w-sm-auto">
        <i class="bi bi-plus-circle me-1"></i> New Shift Closing
    </a>
@endsection

@push('styles')
<style>
    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .kpi-title {
        font-size: 0.7rem;
        letter-spacing: 0.4px;
    }
    .kpi-amount {
        font-size: clamp(1.15rem, 3.8vw, 1.45rem);
        line-height: 1.25;
        word-break: break-word;
    }
    @media (max-width: 575.98px) {
        .card-custom {
            border-radius: 10px;
        }
    }
</style>
@endpush

@section('content')
<!-- Today's Summary Metrics -->
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <div class="col-12 col-md-4">
        <div class="card-custom p-3 bg-primary bg-opacity-10 border-primary h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-primary small fw-semibold text-uppercase kpi-title">Today's Recorded Sales</span>
                <i class="bi bi-receipt text-primary fs-5"></i>
            </div>
            <div class="fw-bold font-monospace text-dark kpi-amount">Rs. {{ number_format($totalSalesToday, 2) }}</div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Gross sales across submitted shifts</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card-custom p-3 bg-success bg-opacity-10 border-success h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-success small fw-semibold text-uppercase kpi-title">Actual Funds Collected</span>
                <i class="bi bi-cash-coin text-success fs-5"></i>
            </div>
            <div class="fw-bold font-monospace text-success kpi-amount">Rs. {{ number_format($totalReceivedToday, 2) }}</div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Counted cash + online collections</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card-custom p-3 {{ $totalDifferenceToday < 0 ? 'bg-danger bg-opacity-10 border-danger' : ($totalDifferenceToday > 0 ? 'bg-info bg-opacity-10 border-info' : 'bg-light border-secondary border-opacity-25') }} h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="{{ $totalDifferenceToday < 0 ? 'text-danger' : ($totalDifferenceToday > 0 ? 'text-info' : 'text-secondary') }} small fw-semibold text-uppercase kpi-title">Today's Net Difference</span>
                <i class="bi bi-shield-check fs-5 {{ $totalDifferenceToday < 0 ? 'text-danger' : ($totalDifferenceToday > 0 ? 'text-info' : 'text-secondary') }}"></i>
            </div>
            <div class="fw-bold font-monospace {{ $totalDifferenceToday < 0 ? 'text-danger' : ($totalDifferenceToday > 0 ? 'text-info' : 'text-dark') }} kpi-amount">
                {{ $totalDifferenceToday > 0 ? '+' : '' }}Rs. {{ number_format($totalDifferenceToday, 2) }}
            </div>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                Status: <strong class="{{ $totalDifferenceToday < 0 ? 'text-danger' : ($totalDifferenceToday > 0 ? 'text-info' : 'text-success') }}">
                    {{ $totalDifferenceToday < 0 ? 'Cash Shortage' : ($totalDifferenceToday > 0 ? 'Cash Surplus' : 'Balanced (Zero Diff)') }}
                </strong>
            </div>
        </div>
    </div>
</div>

<!-- Shift Closings History Card -->
<div class="card-custom overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="fw-bold text-dark m-0">Shift Closing Sheets</h6>
            <small class="text-muted" style="font-size: 0.78rem;">Detailed shift submissions with note counts & variance audit</small>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('shift-closings.index') }}" class="d-flex gap-2 flex-wrap w-100 w-sm-auto align-items-center">
            <div class="input-group input-group-sm flex-fill flex-sm-grow-0" style="min-width: 140px; max-width: 170px;">
                <span class="input-group-text bg-light"><i class="bi bi-calendar"></i></span>
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>
            <select name="shift_type" class="form-select form-select-sm flex-fill flex-sm-grow-0" style="min-width: 120px; max-width: 140px;">
                <option value="">All Shifts</option>
                <option value="morning" {{ request('shift_type') == 'morning' ? 'selected' : '' }}>Morning</option>
                <option value="evening" {{ request('shift_type') == 'evening' ? 'selected' : '' }}>Evening</option>
            </select>
            <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filter"></i> Filter</button>
            @if(request('date') || request('shift_type'))
                <a href="{{ route('shift-closings.index') }}" class="btn btn-link btn-sm text-secondary p-1 text-decoration-none">Clear</a>
            @endif
        </form>
    </div>

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Date</th>
                    <th>Shift</th>
                    <th>Cashier</th>
                    <th class="text-center">Invoices</th>
                    <th class="text-end">Total Sale</th>
                    <th class="text-end">Cash Counted</th>
                    <th class="text-end">JazzCash / Bank</th>
                    <th class="text-end">Expected Cash</th>
                    <th class="text-center">Difference</th>
                    <th class="text-center">Status</th>
                    <th class="text-end" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($closings as $index => $c)
                    <tr>
                        <td class="text-muted small">{{ $closings->firstItem() + $index }}</td>
                        <td class="fw-semibold text-dark small">{{ $c->date->format('d M Y') }}</td>
                        <td>{!! $c->shift_badge !!}</td>
                        <td class="small text-secondary">
                            <i class="bi bi-person me-1"></i>{{ $c->cashier->name ?? 'User #' . $c->cashier_id }}
                        </td>
                        <td class="text-center small font-monospace">
                            {{ $c->total_invoices }}
                            @if($c->invoice_start && $c->invoice_end)
                                <small class="d-block text-muted">#{{ $c->invoice_start }}-#{{ $c->invoice_end }}</small>
                            @endif
                        </td>
                        <td class="text-end font-monospace small fw-bold">Rs. {{ number_format($c->total_sale, 2) }}</td>
                        <td class="text-end font-monospace small text-success">Rs. {{ number_format($c->total_counted_cash, 2) }}</td>
                        <td class="text-end font-monospace small text-primary">Rs. {{ number_format($c->jazzcash_amount + $c->bank_amount, 2) }}</td>
                        <td class="text-end font-monospace small text-secondary">Rs. {{ number_format($c->expected_cash, 2) }}</td>
                        <td class="text-center">{!! $c->difference_badge !!}</td>
                        <td class="text-center">{!! $c->status_badge !!}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('shift-closings.show', $c->id) }}" class="btn btn-outline-primary btn-sm py-1 px-2" title="View & Print Closing Sheet">
                                    <i class="bi bi-eye"></i> Sheet
                                </a>
                                @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                                    @if($c->status === 'locked')
                                        <form action="{{ route('shift-closings.unlock', $c->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Unlock this shift sheet for supervisor editing?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-warning btn-sm py-1 px-2" title="Unlock Sheet">
                                                <i class="bi bi-unlock"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('shift-closings.lock', $c->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Lock Sheet">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                                @if(auth()->user()->isOwner())
                                    <form action="{{ route('shift-closings.destroy', $c->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this shift sheet? Party balances will be safely reversed.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete Sheet">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center py-5 text-muted small">
                            <i class="bi bi-clock-history fs-3 d-block text-secondary opacity-50 mb-2"></i>
                            No shift closing records found.
                            <div class="mt-2">
                                <a href="{{ route('shift-closings.create') }}" class="btn btn-sm btn-outline-primary rounded-3">
                                    <i class="bi bi-plus-circle me-1"></i> Submit New Shift Closing
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll) -->
    <div class="d-block d-md-none p-2 p-sm-3">
        <div class="d-flex flex-column gap-2">
            @forelse($closings as $c)
                <div class="p-3 rounded-3 border bg-white shadow-xs">
                    <!-- Top Row: Date, Shift & Status -->
                    <div class="d-flex align-items-center justify-content-between gap-1 mb-2">
                        <div class="d-flex align-items-center gap-1.5 min-w-0">
                            <span class="fw-bold text-dark small">{{ $c->date->format('d M Y') }}</span>
                            {!! $c->shift_badge !!}
                        </div>
                        <div class="flex-shrink-0">
                            {!! $c->status_badge !!}
                        </div>
                    </div>

                    <!-- Cashier & Invoices Row -->
                    <div class="d-flex justify-content-between align-items-center text-muted small pb-2 border-bottom mb-2" style="font-size: 0.75rem;">
                        <span class="text-truncate">
                            <i class="bi bi-person me-1 text-secondary"></i>{{ $c->cashier->name ?? 'User #' . $c->cashier_id }}
                        </span>
                        <span class="font-monospace text-secondary flex-shrink-0">
                            {{ $c->total_invoices }} Invoices
                            @if($c->invoice_start && $c->invoice_end)
                                (#{{ $c->invoice_start }}-{{ $c->invoice_end }})
                            @endif
                        </span>
                    </div>

                    <!-- Financials 2x2 Grid -->
                    <div class="p-2.5 rounded-2 bg-light border mb-2">
                        <div class="row g-2" style="font-size: 0.78rem;">
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Total Sale</span>
                                <strong class="text-dark font-monospace">Rs. {{ number_format($c->total_sale, 2) }}</strong>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Counted Cash</span>
                                <strong class="text-success font-monospace">Rs. {{ number_format($c->total_counted_cash, 2) }}</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">JazzCash / Bank</span>
                                <span class="text-primary font-monospace fw-semibold">Rs. {{ number_format($c->jazzcash_amount + $c->bank_amount, 2) }}</span>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Expected Cash</span>
                                <span class="text-secondary font-monospace">Rs. {{ number_format($c->expected_cash, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Variance Badge & Difference -->
                    <div class="d-flex justify-content-between align-items-center py-1.5 px-2 rounded-2 border mb-2 {{ $c->difference < 0 ? 'bg-danger bg-opacity-10 border-danger' : ($c->difference > 0 ? 'bg-primary bg-opacity-10 border-primary' : 'bg-success bg-opacity-10 border-success') }}" style="font-size: 0.76rem;">
                        <span class="text-secondary fw-semibold">Shift Variance:</span>
                        <div class="d-flex align-items-center gap-1.5">
                            {!! $c->difference_badge !!}
                            <span class="font-monospace fw-bold {{ $c->difference < 0 ? 'text-danger' : ($c->difference > 0 ? 'text-primary' : 'text-success') }}">
                                {{ $c->difference >= 0 ? '+' : '' }}Rs. {{ number_format($c->difference, 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="d-flex align-items-center gap-1.5 pt-1">
                        <a href="{{ route('shift-closings.show', $c->id) }}" class="btn btn-outline-primary btn-sm flex-fill py-1.5 px-2 rounded-2 fw-semibold" style="font-size: 0.76rem;">
                            <i class="bi bi-eye me-1"></i> View Sheet
                        </a>

                        @if(auth()->user()->isOwner() || auth()->user()->isIncharge())
                            @if($c->status === 'locked')
                                <form action="{{ route('shift-closings.unlock', $c->id) }}" method="POST" class="m-0 flex-shrink-0" onsubmit="return confirm('Unlock this shift sheet for supervisor editing?');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-warning btn-sm py-1.5 px-2.5 rounded-2" style="font-size: 0.76rem;" title="Unlock Sheet">
                                        <i class="bi bi-unlock"></i>
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('shift-closings.lock', $c->id) }}" method="POST" class="m-0 flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm py-1.5 px-2.5 rounded-2" style="font-size: 0.76rem;" title="Lock Sheet">
                                        <i class="bi bi-lock"></i>
                                    </button>
                                </form>
                            @endif
                        @endif

                        @if(auth()->user()->isOwner())
                            <form action="{{ route('shift-closings.destroy', $c->id) }}" method="POST" class="m-0 flex-shrink-0" onsubmit="return confirm('Are you sure you want to delete this shift sheet? Party balances will be safely reversed.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm py-1.5 px-2.5 rounded-2" style="font-size: 0.76rem;" title="Delete Sheet">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-clock-history fs-3 d-block text-secondary opacity-50 mb-2"></i>
                    No shift closing records found.
                    <div class="mt-2">
                        <a href="{{ route('shift-closings.create') }}" class="btn btn-sm btn-outline-primary rounded-3">
                            <i class="bi bi-plus-circle me-1"></i> Submit New Shift Closing
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    @if($closings->hasPages())
        <div class="p-3 border-top bg-light overflow-x-auto">
            {{ $closings->links() }}
        </div>
    @endif
</div>
@endsection
