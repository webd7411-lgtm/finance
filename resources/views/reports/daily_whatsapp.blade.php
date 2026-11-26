@extends('layouts.app')

@section('title', 'Owner Daily WhatsApp Summary')
@section('page_title', 'Owner Daily WhatsApp Closing Report')

@section('page_badge')
    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1 small">
        <i class="bi bi-whatsapp me-1"></i> Executive WhatsApp Broadcast
    </span>
@endsection

@section('page_actions')
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#ownerPhoneModal">
            <i class="bi bi-gear me-1"></i> Owner Phone ({{ $ownerPhone }})
        </button>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-3 shadow-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
        </a>
    </div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">

        <!-- Date Selector Card -->
        <div class="card-custom mb-3 p-3 shadow-sm">
            <form method="GET" action="{{ route('reports.daily-whatsapp') }}" class="row g-2 align-items-center">
                <div class="col-12 col-sm-auto">
                    <label class="form-label small fw-bold text-dark m-0"><i class="bi bi-calendar3 me-1 text-primary"></i> Select Date:</label>
                </div>
                <div class="col-12 col-sm-auto">
                    <input type="date" name="date" class="form-control form-control-sm fw-semibold" value="{{ $date }}" max="{{ date('Y-m-d') }}">
                </div>
                <div class="col-12 col-sm-auto">
                    <button type="submit" class="btn btn-primary btn-sm px-3 rounded-2">
                        <i class="bi bi-arrow-repeat me-1"></i> Generate Report
                    </button>
                </div>
                <div class="col-12 col-sm-auto ms-auto text-muted small">
                    Showing executive closing for <strong>{{ $carbonDate->format('d M, Y (l)') }}</strong>
                </div>
            </form>
        </div>

        <div class="row g-3">
            <!-- Left: Formatted Preview & WhatsApp Action -->
            <div class="col-12 col-lg-7">
                <div class="card-custom shadow-sm h-100 d-flex flex-column">
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-light">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-whatsapp text-success fs-5"></i>
                            <h6 class="fw-bold text-dark m-0">WhatsApp Formatted Message Preview</h6>
                        </div>
                        <span class="badge bg-success text-white py-1 px-2 small">Ready to Send</span>
                    </div>

                    <div class="p-3 flex-grow-1">
                        <div class="p-3 bg-dark text-light rounded-3 font-monospace small" style="white-space: pre-wrap; font-size: 0.84rem; line-height: 1.5; border-left: 4px solid #25D366; max-height: 520px; overflow-y: auto;" id="whatsappMessageText">{{ $msg }}</div>
                    </div>

                    <div class="p-3 border-top bg-light d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-2 fw-semibold" onclick="copyWhatsAppText()">
                            <i class="bi bi-clipboard me-1"></i> Copy Message to Clipboard
                        </button>
                        <a href="{{ $whatsappUrl }}" target="_blank" class="btn btn-success btn-sm px-4 rounded-2 fw-bold shadow-sm">
                            <i class="bi bi-whatsapp me-1.5"></i> Send to Owner on WhatsApp
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right: Executive Metrics Summary -->
            <div class="col-12 col-lg-5">
                <div class="card-custom shadow-sm p-3 mb-3">
                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                        <i class="bi bi-speedometer2 text-primary me-1"></i> Summary Key Figures
                    </h6>

                    <div class="d-flex justify-content-between p-2 bg-light rounded-2 mb-2 small">
                        <span class="text-secondary">Total Fuel Dispensed:</span>
                        <strong class="text-primary fs-6">{{ number_format($totalFuelLiters, 2) }} Ltr</strong>
                    </div>

                    @if($totalTestingLiters > 0)
                        <div class="d-flex justify-content-between p-2 bg-warning-subtle rounded-2 mb-2 small">
                            <span class="text-warning-emphasis">Testing / Paimana Liters:</span>
                            <strong class="text-warning-emphasis">{{ number_format($totalTestingLiters, 2) }} Ltr</strong>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between p-2 bg-light rounded-2 mb-2 small">
                        <span class="text-secondary">Gross Sales Revenue:</span>
                        <strong class="text-dark fs-6">Rs. {{ number_format($totalGrossSales, 2) }}</strong>
                    </div>

                    @if($totalCreditSales > 0)
                        <div class="d-flex justify-content-between p-2 bg-danger-subtle rounded-2 mb-2 small">
                            <span class="text-danger">Credit / Udhar Given:</span>
                            <strong class="text-danger">Rs. {{ number_format($totalCreditSales, 2) }}</strong>
                        </div>
                    @endif

                    @if($totalExpenses > 0)
                        <div class="d-flex justify-content-between p-2 bg-light rounded-2 mb-2 small">
                            <span class="text-secondary">Counter Shift Expenses:</span>
                            <strong class="text-danger">-Rs. {{ number_format($totalExpenses, 2) }}</strong>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between p-2 bg-success-subtle rounded-2 mb-2 small">
                        <span class="text-success fw-bold">Counted Physical Cash:</span>
                        <strong class="text-success fs-6">Rs. {{ number_format($totalCountedCash, 2) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between p-2 bg-light rounded-2 small">
                        <span class="text-secondary">Vault Cash in Hand:</span>
                        <strong class="text-dark">Rs. {{ number_format($cashInHand, 2) }}</strong>
                    </div>
                </div>

                <!-- Storage Tanks Live Status Box -->
                <div class="card-custom shadow-sm p-3">
                    <h6 class="fw-bold text-dark mb-2 pb-2 border-bottom">
                        <i class="bi bi-database text-info me-1"></i> Storage Tanks Position
                    </h6>

                    @foreach($tanks as $tank)
                        <div class="mb-2 pb-2 border-bottom border-light">
                            <div class="d-flex justify-content-between align-items-center small gap-1">
                                <span class="fw-bold text-dark">{{ $tank->name }}</span>
                                <span class="text-success fw-bold font-monospace">{{ number_format($tank->current_stock, 1) }} L</span>
                            </div>
                            <div class="progress mt-1" style="height: 6px;">
                                @php $pct = $tank->capacity > 0 ? min(100, round(($tank->current_stock / $tank->capacity) * 100)) : 0; @endphp
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center text-muted mt-1" style="font-size: 0.72rem;">
                                <span>Dip: {{ $tank->current_dip_reading ?? 0 }}</span>
                                <span>{!! $tank->variance_status_badge !!}</span>
                                <span>Ullage: {{ number_format($tank->available_capacity, 1) }} L</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Configure Owner Phone -->
<div class="modal fade" id="ownerPhoneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('reports.whatsapp-settings') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-whatsapp me-1"></i> Set Owner's WhatsApp Phone Number
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label small fw-semibold">Owner WhatsApp Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" name="owner_whatsapp_phone" class="form-control form-control-sm fw-bold" value="{{ $ownerPhone }}" placeholder="e.g. 03003647318" required>
                    <small class="text-muted d-block mt-2">
                        Format: 03001234567 or 923001234567. When you click "Send to Owner", WhatsApp opens with this number pre-filled.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-3">Save Number</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function copyWhatsAppText() {
        const text = document.getElementById('whatsappMessageText').innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert('WhatsApp Daily Summary copied to clipboard! You can paste it directly into WhatsApp.');
        }).catch(err => {
            console.error('Failed to copy', err);
        });
    }
</script>
@endpush
@endsection
