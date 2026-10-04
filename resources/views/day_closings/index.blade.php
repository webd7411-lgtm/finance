@extends('layouts.app')

@section('title', 'Day Closing')
@section('page_title', 'End-of-Day Financial Closing')

@section('page_badge')
    <span class="badge bg-light text-secondary border px-2 py-1 small">
        <i class="bi bi-calendar2-check me-1 text-primary"></i> {{ Carbon\Carbon::parse($selectedDate)->format('d M Y') }}
    </span>
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-1 gap-sm-2 w-100 justify-content-start justify-content-sm-end">
        @if(!$existingDayClosing)
            <button type="button" class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 shadow-sm text-nowrap w-100 w-sm-auto" data-bs-toggle="modal" data-bs-target="#finalizeDayModal">
                <i class="bi bi-check2-circle me-1"></i> Finalize Day Closing
            </button>
        @else
            <span class="badge bg-success-subtle text-success border border-success p-2 small fw-semibold text-truncate flex-grow-1 flex-sm-grow-0">
                <i class="bi bi-check-all me-1"></i> Day Finalized by {{ $existingDayClosing->closer->name ?? 'User' }}
            </span>
            <a href="{{ route('day-closings.show', $existingDayClosing->id) }}" class="btn btn-outline-primary btn-sm rounded-3 text-nowrap flex-grow-1 flex-sm-grow-0">
                <i class="bi bi-printer me-1"></i> View Statement
            </a>
        @endif
    </div>
@endsection

@push('styles')
<style>
    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .kpi-formula-value {
        font-size: clamp(1.05rem, 3.5vw, 1.35rem);
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
<!-- Date Selector Bar -->
<div class="card-custom p-3 mb-3 bg-white">
    <form method="GET" action="{{ route('day-closings.index') }}" class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap w-100 w-md-auto">
            <span class="small fw-bold text-secondary text-uppercase" style="font-size: 0.75rem;">Select Closing Date:</span>
            <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}" style="max-width: 170px;">
            <button type="submit" class="btn btn-outline-primary btn-sm text-nowrap"><i class="bi bi-arrow-repeat"></i> Load Summary</button>
        </div>
        <div class="small text-muted" style="font-size: 0.75rem;">
            <i class="bi bi-info-circle me-1 text-primary"></i> 
            Auto Opening Cash is fetched from previous day's finalized closing.
        </div>
    </form>
</div>

<!-- ==================== AUTOMATED CORE FORMULA BANNER ==================== -->
<div class="card-custom p-3 p-md-4 mb-3 mb-md-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; border-radius: 14px;">
    <div class="text-secondary small fw-bold text-uppercase mb-2.5" style="font-size: 0.72rem; letter-spacing: 0.05em; color: #94a3b8 !important;">
        Automated Daily Equation: Opening Cash + Total Cash In - Total Payments = Closing Cash
    </div>
    
    <div class="row g-3 align-items-center text-start">
        <!-- 1. Opening Cash -->
        <div class="col-6 col-md-3">
            <div class="text-white-50 small" style="font-size: 0.75rem;">1. Opening Cash</div>
            <div class="fw-bold font-monospace text-white mt-1 kpi-formula-value">Rs. {{ number_format($autoOpeningCash, 2) }}</div>
            <small class="text-white-50 d-block" style="font-size: 0.7rem;">Previous Day Balance</small>
        </div>

        <!-- 2. Cash In -->
        <div class="col-6 col-md-3">
            <div class="text-success small fw-semibold" style="font-size: 0.75rem;">+ 2. Today's Cash In</div>
            <div class="fw-bold font-monospace text-success mt-1 kpi-formula-value">+Rs. {{ number_format($totalCashIn, 2) }}</div>
            <small class="text-white-50 d-block" style="font-size: 0.7rem;">Shifts Cash + Receipts</small>
        </div>

        <!-- 3. Payments Out -->
        <div class="col-6 col-md-3">
            <div class="text-danger small fw-semibold" style="font-size: 0.75rem;">- 3. Total Payments Out</div>
            <div class="fw-bold font-monospace text-danger mt-1 kpi-formula-value">-Rs. {{ number_format($totalPaymentsOut, 2) }}</div>
            <small class="text-white-50 d-block" style="font-size: 0.7rem;">Expenses + Supplier Pay</small>
        </div>

        <!-- 4. Calculated Closing Cash -->
        <div class="col-6 col-md-3 text-start text-md-end border-start-md border-secondary border-opacity-50">
            <div class="text-info small fw-bold" style="font-size: 0.75rem;">= 4. Final Closing Cash</div>
            <div class="fw-bold font-monospace text-info mt-1 kpi-formula-value">Rs. {{ number_format($calculatedClosingCash, 2) }}</div>
            <small class="text-white-50 d-block" style="font-size: 0.7rem;">Tomorrow's Opening Cash</small>
        </div>
    </div>
</div>

<!-- ==================== SHIFT MERGING CARDS (MORNING & EVENING) ==================== -->
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <!-- Morning Shift -->
    <div class="col-12 col-md-6">
        <div class="card-custom p-3 h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1"><i class="bi bi-sun me-1"></i>Morning Shift</span>
                    <span class="fw-bold text-dark small">Morning Summary</span>
                </div>
                @if($morningShift)
                    <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.7rem;"><i class="bi bi-check-circle me-1"></i>Recorded</span>
                @else
                    <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">Not Submitted Yet</span>
                @endif
            </div>

            @if($morningShift)
                <div class="row g-2 small text-secondary">
                    <div class="col-6">Total Sales: <strong class="text-dark font-monospace">Rs. {{ number_format($morningShift->total_sale, 2) }}</strong></div>
                    <div class="col-6 text-end">Invoices: <strong class="text-dark">{{ $morningShift->total_invoices }}</strong>
                        @if($morningShift->invoice_start && $morningShift->invoice_end)
                            <small class="d-block text-muted font-monospace">#{{ $morningShift->invoice_start }}-#{{ $morningShift->invoice_end }}</small>
                        @endif
                    </div>
                    <div class="col-6">Cash Counted: <strong class="text-success fw-semibold font-monospace">Rs. {{ number_format($morningShift->total_counted_cash, 2) }}</strong></div>
                    <div class="col-6 text-end">JazzCash/Bank: <strong class="text-primary fw-semibold font-monospace">Rs. {{ number_format($morningShift->jazzcash_amount + $morningShift->bank_amount, 2) }}</strong></div>
                    <div class="col-6">Shift Expenses: <strong class="text-danger fw-semibold font-monospace">Rs. {{ number_format($morningShift->expenses_amount, 2) }}</strong></div>
                    <div class="col-6 text-end">Shift Variance: {!! $morningShift->difference_badge !!}</div>
                    @if($morningShift->return_invoice_number || $morningShift->returns_amount > 0)
                        <div class="col-6">Return Invoice: <strong class="text-dark">{{ $morningShift->return_invoice_number ? '#' . $morningShift->return_invoice_number : '-' }}</strong></div>
                        <div class="col-6 text-end">Return Amount: <strong class="text-danger fw-semibold font-monospace">Rs. {{ number_format($morningShift->returns_amount, 2) }}</strong></div>
                    @endif
                    @if($morningShift->expenses_details)
                        <div class="col-12">Expense Details: <strong class="text-dark">{{ $morningShift->expenses_details }}</strong></div>
                    @endif
                    @foreach($morningShift->partyPayments as $payment)
                        <div class="col-12">Cash Paid to {{ $payment->party->name ?? 'Party' }}: <strong class="text-danger font-monospace">Rs. {{ number_format($payment->amount, 2) }}</strong> <span class="text-muted">{{ $payment->details }}</span></div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted small">
                    Morning shift closing sheet has not been submitted for this date.
                </div>
            @endif
        </div>
    </div>

    <!-- Evening Shift -->
    <div class="col-12 col-md-6">
        <div class="card-custom p-3 h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1"><i class="bi bi-moon-stars me-1"></i>Evening Shift</span>
                    <span class="fw-bold text-dark small">Evening Summary</span>
                </div>
                @if($eveningShift)
                    <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.7rem;"><i class="bi bi-check-circle me-1"></i>Recorded</span>
                @else
                    <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">Not Submitted Yet</span>
                @endif
            </div>

            @if($eveningShift)
                <div class="row g-2 small text-secondary">
                    <div class="col-6">Total Sales: <strong class="text-dark font-monospace">Rs. {{ number_format($eveningShift->total_sale, 2) }}</strong></div>
                    <div class="col-6 text-end">Invoices: <strong class="text-dark">{{ $eveningShift->total_invoices }}</strong>
                        @if($eveningShift->invoice_start && $eveningShift->invoice_end)
                            <small class="d-block text-muted font-monospace">#{{ $eveningShift->invoice_start }}-#{{ $eveningShift->invoice_end }}</small>
                        @endif
                    </div>
                    <div class="col-6">Cash Counted: <strong class="text-success fw-semibold font-monospace">Rs. {{ number_format($eveningShift->total_counted_cash, 2) }}</strong></div>
                    <div class="col-6 text-end">JazzCash/Bank: <strong class="text-primary fw-semibold font-monospace">Rs. {{ number_format($eveningShift->jazzcash_amount + $eveningShift->bank_amount, 2) }}</strong></div>
                    <div class="col-6">Shift Expenses: <strong class="text-danger fw-semibold font-monospace">Rs. {{ number_format($eveningShift->expenses_amount, 2) }}</strong></div>
                    <div class="col-6 text-end">Shift Variance: {!! $eveningShift->difference_badge !!}</div>
                    @if($eveningShift->return_invoice_number || $eveningShift->returns_amount > 0)
                        <div class="col-6">Return Invoice: <strong class="text-dark">{{ $eveningShift->return_invoice_number ? '#' . $eveningShift->return_invoice_number : '-' }}</strong></div>
                        <div class="col-6 text-end">Return Amount: <strong class="text-danger fw-semibold font-monospace">Rs. {{ number_format($eveningShift->returns_amount, 2) }}</strong></div>
                    @endif
                    @if($eveningShift->expenses_details)
                        <div class="col-12">Expense Details: <strong class="text-dark">{{ $eveningShift->expenses_details }}</strong></div>
                    @endif
                    @foreach($eveningShift->partyPayments as $payment)
                        <div class="col-12">Cash Paid to {{ $payment->party->name ?? 'Party' }}: <strong class="text-danger font-monospace">Rs. {{ number_format($payment->amount, 2) }}</strong> <span class="text-muted">{{ $payment->details }}</span></div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted small">
                    Evening shift closing sheet has not been submitted for this date.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- ==================== DAY CLOSINGS HISTORY TABLE ==================== -->
<div class="card-custom overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold text-dark m-0">Finalized Day Closings Register</h6>
            <small class="text-muted" style="font-size: 0.78rem;">Permanent record of daily opening, cash in, payments, and closing balances</small>
        </div>
    </div>

    <!-- Desktop View: Table -->
    <div class="d-none d-md-block table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Date</th>
                    <th class="text-end">Opening Cash</th>
                    <th class="text-end">Total Cash In</th>
                    <th class="text-end">Total Payments</th>
                    <th class="text-end">Final Closing Cash</th>
                    <th class="text-center">Total Variance</th>
                    <th class="text-center">Status</th>
                    <th>Finalized By</th>
                    <th class="text-end" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($history as $index => $day)
                    <tr>
                        <td class="text-muted small">{{ $history->firstItem() + $index }}</td>
                        <td class="fw-bold text-dark">{{ $day->date->format('d M Y') }}</td>
                        <td class="text-end small font-monospace">Rs. {{ number_format($day->opening_cash, 2) }}</td>
                        <td class="text-end small font-monospace text-success">+Rs. {{ number_format($day->total_cash_in, 2) }}</td>
                        <td class="text-end small font-monospace text-danger">-Rs. {{ number_format($day->total_payments_out, 2) }}</td>
                        <td class="text-end fw-bold fs-6 font-monospace text-info">Rs. {{ number_format($day->closing_cash, 2) }}</td>
                        <td class="text-center">
                            @if($day->total_difference == 0)
                                <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.72rem;">Balanced</span>
                            @elseif($day->total_difference > 0)
                                <span class="badge bg-info-subtle text-info border border-info" style="font-size: 0.72rem;">+Rs. {{ number_format($day->total_difference, 2) }}</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger" style="font-size: 0.72rem;">-Rs. {{ number_format(abs($day->total_difference), 2) }}</span>
                            @endif
                        </td>
                        <td class="text-center">{!! $day->status_badge !!}</td>
                        <td class="small text-secondary"><i class="bi bi-person me-1"></i>{{ $day->closer->name ?? 'User #' . $day->closed_by }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('day-closings.show', $day->id) }}" class="btn btn-outline-primary btn-sm py-1 px-2" title="Print Statement">
                                    <i class="bi bi-printer"></i>
                                </a>
                                @if(auth()->user()->isOwner())
                                    @if($day->status === 'closed')
                                        <form action="{{ route('day-closings.reopen', $day->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Reopen this day closing for revisions?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-warning btn-sm py-1 px-2" title="Reopen Day">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('day-closings.destroy', $day->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this day closing?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted small">
                            <i class="bi bi-calendar-x fs-3 d-block text-secondary opacity-50 mb-2"></i>
                            No finalized day closings found. Click <strong>"Finalize Day Closing"</strong> above to close today's balance.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll) -->
    <div class="d-block d-md-none p-2 p-sm-3">
        <div class="d-flex flex-column gap-2">
            @forelse($history as $day)
                <div class="p-3 rounded-3 border bg-white shadow-xs">
                    <!-- Top Row: Date, Status Badge & Variance -->
                    <div class="d-flex align-items-center justify-content-between gap-1 mb-2">
                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                            <i class="bi bi-calendar-check text-primary me-1"></i>{{ $day->date->format('d M Y') }}
                        </div>
                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            {!! $day->status_badge !!}
                            @if($day->total_difference == 0)
                                <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.68rem;">Balanced</span>
                            @elseif($day->total_difference > 0)
                                <span class="badge bg-info-subtle text-info border border-info" style="font-size: 0.68rem;">+Rs. {{ number_format($day->total_difference, 0) }}</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger" style="font-size: 0.68rem;">-Rs. {{ number_format(abs($day->total_difference), 0) }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Financial Movement Box -->
                    <div class="p-2.5 rounded-2 bg-light border mb-2">
                        <div class="row g-2" style="font-size: 0.78rem;">
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Opening Cash</span>
                                <span class="font-monospace text-secondary">Rs. {{ number_format($day->opening_cash, 2) }}</span>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Total Cash In</span>
                                <strong class="text-success font-monospace">+Rs. {{ number_format($day->total_cash_in, 2) }}</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Total Payments</span>
                                <span class="text-danger font-monospace fw-semibold">-Rs. {{ number_format($day->total_payments_out, 2) }}</span>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Closing Cash</span>
                                <strong class="text-info font-monospace fs-6">Rs. {{ number_format($day->closing_cash, 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Closer Info & Actions Row -->
                    <div class="d-flex justify-content-between align-items-center pt-1">
                        <div class="small text-muted text-truncate me-2" style="font-size: 0.74rem;">
                            <i class="bi bi-person me-1 text-secondary"></i>{{ $day->closer->name ?? 'User #' . $day->closed_by }}
                        </div>

                        <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                            <a href="{{ route('day-closings.show', $day->id) }}" class="btn btn-outline-primary btn-sm py-1.5 px-2.5 rounded-2 fw-semibold" style="font-size: 0.76rem;">
                                <i class="bi bi-printer me-1"></i> Statement
                            </a>

                            @if(auth()->user()->isOwner())
                                @if($day->status === 'closed')
                                    <form action="{{ route('day-closings.reopen', $day->id) }}" method="POST" class="m-0" onsubmit="return confirm('Reopen this day closing for revisions?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning btn-sm py-1.5 px-2 rounded-2" style="font-size: 0.76rem;" title="Reopen Day">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('day-closings.destroy', $day->id) }}" method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to delete this day closing?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-1.5 px-2 rounded-2" style="font-size: 0.76rem;" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-calendar-x fs-3 d-block text-secondary opacity-50 mb-2"></i>
                    No finalized day closings found. Click <strong>"Finalize Day Closing"</strong> above to close today's balance.
                </div>
            @endforelse
        </div>
    </div>

    @if($history->hasPages())
        <div class="p-3 border-top bg-light overflow-x-auto">
            {{ $history->links() }}
        </div>
    @endif
</div>

<!-- ==================== FINALIZE DAY CLOSING MODAL ==================== -->
<div class="modal fade" id="finalizeDayModal" tabindex="-1" aria-labelledby="finDayLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg text-start">
            <div class="modal-header border-bottom py-3" style="background-color: #0f172a; color: #ffffff;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar2-check-fill fs-5 text-primary"></i>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="finDayLabel">Finalize Day Closing: {{ Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</h6>
                        <small class="text-white-50" style="font-size: 0.75rem;">Consolidate shifts & lock opening balance for tomorrow</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('day-closings.store') }}" method="POST">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate }}">

                <div class="modal-body p-3 p-sm-4 text-start">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between py-1 small">
                            <span class="text-muted">Opening Cash:</span>
                            <span class="fw-bold text-dark font-monospace">Rs. {{ number_format($autoOpeningCash, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small">
                            <span class="text-success fw-semibold">(+) Total Today's Cash In:</span>
                            <span class="fw-bold text-success font-monospace">+Rs. {{ number_format($totalCashIn, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small">
                            <span class="text-danger fw-semibold">(-) Direct Voucher Payments Out:</span>
                            <span class="fw-bold text-danger font-monospace">-Rs. {{ number_format($directPaymentsOut, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-top mt-2 align-items-center">
                            <span class="fw-bold text-dark">Calculated Final Closing Cash:</span>
                            <span class="fw-bold fs-5 text-primary font-monospace">Rs. {{ number_format($calculatedClosingCash, 2) }}</span>
                        </div>
                    </div>

                    <input type="hidden" name="opening_cash" value="{{ $autoOpeningCash }}">
                    <input type="hidden" name="total_cash_in" value="{{ $totalCashIn }}">
                    <input type="hidden" name="total_payments_out" value="{{ $totalPaymentsOut }}">
                    <input type="hidden" name="closing_cash" value="{{ $calculatedClosingCash }}">
                    <input type="hidden" name="total_difference" value="{{ $totalDifference }}">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Consolidated Variance (Shift Differences):</label>
                        <div class="p-2 rounded bg-light border fw-semibold small font-monospace {{ $totalDifference < 0 ? 'text-danger' : ($totalDifference > 0 ? 'text-primary' : 'text-success') }}">
                            {{ $totalDifference >= 0 ? '+' : '' }}Rs. {{ number_format($totalDifference, 2) }} 
                            <span class="fw-normal text-muted">({{ $totalDifference == 0 ? 'Balanced' : ($totalDifference > 0 ? 'Cash Surplus' : 'Cash Shortage') }})</span>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Closing Remarks / Audit Notes</label>
                        <textarea name="remarks" class="form-control form-control-sm py-2" rows="3" placeholder="Notes for today's closing summary, verification notes..."></textarea>
                    </div>

                    <div class="small text-muted mt-2" style="font-size: 0.74rem;">
                        <i class="bi bi-shield-lock-fill text-warning me-1"></i> Once finalized, this closing cash will automatically become tomorrow's opening balance.
                    </div>
                </div>

                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Confirm & Finalize Day
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
