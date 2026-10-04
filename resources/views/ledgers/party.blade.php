@extends('layouts.app')

@section('title', 'Party Ledger Statement - ' . ($party ? $party->name : 'Select Party'))
@section('page_title', 'Party Ledger Statement')

@section('page_badge')
    @if($party)
        <span class="badge bg-light text-secondary border px-2 py-1 small">
            ID #PRT-{{ str_pad($party->id, 4, '0', STR_PAD_LEFT) }} &bull; {!! $party->type_badge !!}
        </span>
    @endif
@endsection

@section('page_actions')
    <div class="d-flex flex-wrap gap-1 gap-sm-2 w-100 justify-content-start justify-content-sm-end">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Statement
        </button>
        <a href="{{ route('parties.index') }}" class="btn btn-light btn-sm rounded-3 no-print flex-fill flex-sm-grow-0 text-nowrap">
            <i class="bi bi-person-lines-fill me-1"></i> Parties Directory
        </a>
    </div>
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
        font-size: clamp(1.05rem, 3.5vw, 1.35rem);
        line-height: 1.25;
        word-break: break-word;
    }
    .party-combobox-menu {
        max-height: 16rem;
        overflow-y: auto;
    }
    @media (max-width: 575.98px) {
        .card-custom {
            border-radius: 10px;
        }
    }
    @media print {
        .vip-navbar, .no-print, header, nav, .btn, .sub-header {
            display: none !important;
        }
        body {
            background-color: #fff !important;
            color: #000 !important;
            padding: 0 !important;
        }
        .card-custom {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }
    }
</style>
@endpush

@section('content')
<!-- Filter & Selection Bar (No-Print) -->
<div class="card-custom p-3 mb-3 mb-md-4 no-print">
    <form method="GET" action="{{ route('ledgers.party') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label for="partySearch" class="form-label text-muted small fw-semibold mb-1">Select Counterparty / Party</label>
            <div id="partyCombobox" class="position-relative">
                <input type="search" id="partySearch" class="form-control form-control-sm py-2" placeholder="Search or select a party..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="partyOptions">
                <input type="hidden" name="party_id" id="partyId" value="{{ $party->id ?? '' }}">
                <div id="partyOptions" class="party-combobox-menu dropdown-menu w-100 p-1 shadow" role="listbox">
                @foreach($allParties as $p)
                    <button type="button" class="dropdown-item rounded-1 small text-start py-2" role="option" data-party-id="{{ $p->id }}">
                        [{{ ucfirst($p->type) }}] {{ $p->name }} ({{ $p->phone ?? 'No Phone' }})
                    </button>
                @endforeach
                    <div id="partySearchEmpty" class="dropdown-item small text-muted" aria-live="polite" hidden>No matching party found.</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">From Date</label>
            <input type="date" name="from_date" class="form-control form-control-sm py-2" value="{{ $fromDate }}">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">To Date</label>
            <input type="date" name="to_date" class="form-control form-control-sm py-2" value="{{ $toDate }}">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm rounded-3 w-100 fw-semibold py-2">
                <i class="bi bi-filter me-1"></i> Apply Filter
            </button>
        </div>
    </form>
</div>

@if($party)
    <!-- Printable Statement Card -->
    <div class="card-custom p-3 p-md-5 bg-white shadow-sm mb-4" id="partyStatementSheet">
        <!-- Letterhead Header -->
        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 pb-md-4 mb-3 mb-md-4 flex-wrap gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="bg-primary text-white rounded-2 p-1 px-2 fw-bold">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h4 class="fw-bold text-dark m-0">FinanceDesk</h4>
                </div>
                <h5 class="text-secondary fw-bold m-0" style="font-size: clamp(1rem, 3.5vw, 1.25rem);">Statement of Account & Ledger</h5>
                <small class="text-muted">Proware Technologies &bull; Financial Accounting Ledger</small>
            </div>
            <div class="text-start text-md-end w-100 w-md-auto">
                <span class="badge bg-light text-dark border px-2 py-1 mb-1 font-monospace d-inline-block">
                    Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}
                </span>
                <div class="small text-muted" style="font-size: 0.72rem;">Generated: {{ now()->format('d M, Y - h:i A') }}</div>
                <div class="small text-muted" style="font-size: 0.72rem;">Auditor: <strong>{{ auth()->user()->name }}</strong></div>
            </div>
        </div>

        <!-- Party Profile Box -->
        <div class="row g-2 g-md-3 mb-3 mb-md-4 p-3 bg-light rounded-3 border">
            <div class="col-12 col-md-6">
                <div class="text-muted small fw-semibold" style="font-size: 0.75rem;">Account / Party Name:</div>
                <h5 class="fw-bold text-dark m-0">{{ $party->name }}</h5>
                <div class="small text-muted mt-1" style="font-size: 0.74rem;">
                    <i class="bi bi-geo-alt me-1"></i>{{ $party->address ?? 'No physical address registered' }}
                </div>
            </div>
            <div class="col-12 col-md-6 text-start text-md-end">
                <div class="text-muted small fw-semibold" style="font-size: 0.75rem;">Account Type & Contact:</div>
                <div class="mt-1">{!! $party->type_badge !!}</div>
                <div class="small font-monospace text-dark mt-1" style="font-size: 0.74rem;">
                    <i class="bi bi-telephone me-1 text-primary"></i>{{ $party->phone ?? 'N/A' }}
                </div>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-2 g-md-3 mb-3 mb-md-4">
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border h-100">
                    <span class="text-muted small d-block mb-1 kpi-title">Opening Balance</span>
                    <h5 class="fw-bold font-monospace text-dark m-0 kpi-amount">Rs. {{ number_format($openingBalance, 2) }}</h5>
                    <small class="text-muted" style="font-size: 0.7rem;">Prior to {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }}</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle h-100">
                    <span class="text-danger small fw-semibold d-block mb-1 kpi-title">Total Debits</span>
                    <h5 class="fw-bold font-monospace text-danger m-0 kpi-amount">Rs. {{ number_format($totalDebit, 2) }}</h5>
                    <small class="text-danger-emphasis" style="font-size: 0.7rem;">Invoiced / Paid Out</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle h-100">
                    <span class="text-success small fw-semibold d-block mb-1 kpi-title">Total Credits</span>
                    <h5 class="fw-bold font-monospace text-success m-0 kpi-amount">Rs. {{ number_format($totalCredit, 2) }}</h5>
                    <small class="text-success-emphasis" style="font-size: 0.7rem;">Received / Recovered</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-primary text-white rounded-3 shadow-sm h-100">
                    <span class="text-white-50 small fw-semibold d-block mb-1 kpi-title">Net Ending Balance</span>
                    <h5 class="fw-bold font-monospace text-white m-0 kpi-amount">Rs. {{ number_format(abs($closingBalance), 2) }}</h5>
                    <small class="text-white-50" style="font-size: 0.7rem;">
                        @if($party->type === 'customer' || $party->type === 'staff')
                            {{ $closingBalance > 0 ? 'Receivable (Dr)' : ($closingBalance < 0 ? 'Advance / Credit (Cr)' : 'Settled / Zero') }}
                        @else
                            {{ $closingBalance > 0 ? 'Payable to Supplier' : ($closingBalance < 0 ? 'Debit Balance (Overpaid)' : 'Settled / Zero') }}
                        @endif
                    </small>
                </div>
            </div>
        </div>

        <!-- Desktop View: Full Ledger Table -->
        <div class="d-none d-md-block table-responsive mb-4">
            <table class="table table-sm table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 105px;">Date</th>
                        <th style="width: 110px;">Ref / Voucher</th>
                        <th>Account / Mode</th>
                        <th>Description / Details</th>
                        <th class="text-end" style="width: 130px;">Debit (Rs.)</th>
                        <th class="text-end" style="width: 130px;">Credit (Rs.)</th>
                        <th class="text-end" style="width: 150px;">Balance (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row -->
                    <tr class="table-secondary bg-opacity-25 fw-semibold">
                        <td>{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                        <td class="font-monospace text-muted">-</td>
                        <td class="text-muted">Opening Balance</td>
                        <td class="text-muted">Balance brought forward prior to {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }}</td>
                        <td class="text-end font-monospace text-muted">-</td>
                        <td class="text-end font-monospace text-muted">-</td>
                        <td class="text-end font-monospace fw-bold text-dark">
                            Rs. {{ number_format($openingBalance, 2) }}
                        </td>
                    </tr>

                    @forelse($transactions as $entry)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($entry->date)->format('d-m-Y') }}</td>
                            <td class="font-monospace fw-bold">
                                @if($entry->transaction_id)
                                    <a href="{{ route('transactions.show', $entry->transaction_id) }}" class="text-decoration-none" target="_blank">
                                        #{{ str_pad($entry->transaction_id, 5, '0', STR_PAD_LEFT) }}
                                    </a>
                                @else
                                    {{ $entry->bill_no }}
                                @endif
                                @if($entry->bill_no)
                                    <span class="d-block text-muted small" style="font-size: 0.72rem;">Bill: {{ $entry->bill_no }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    {{ $entry->account_name }}
                                </span>
                            </td>
                            <td>
                                <div>{{ $entry->description ?? 'Financial Transaction' }}</div>
                                <small class="text-muted" style="font-size: 0.72rem;">User: {{ $entry->creator }}</small>
                            </td>
                            <td class="text-end font-monospace {{ $entry->debit > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ $entry->debit > 0 ? number_format($entry->debit, 2) : '-' }}
                            </td>
                            <td class="text-end font-monospace {{ $entry->credit > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                                {{ $entry->credit > 0 ? number_format($entry->credit, 2) : '-' }}
                            </td>
                            <td class="text-end font-monospace fw-bold text-dark">
                                Rs. {{ number_format($entry->running_balance, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                No ledger transactions found for this party within the selected date range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end">Period Totals & Ending Balance:</td>
                        <td class="text-end font-monospace text-danger">Rs. {{ number_format($totalDebit, 2) }}</td>
                        <td class="text-end font-monospace text-success">Rs. {{ number_format($totalCredit, 2) }}</td>
                        <td class="text-end font-monospace text-primary fs-6">Rs. {{ number_format($closingBalance, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Mobile View: Clean Responsive Cards (Zero Horizontal Scroll) -->
        <div class="d-block d-md-none mb-3">
            <!-- Opening Balance Card -->
            <div class="p-2.5 rounded-3 border bg-light mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold text-secondary small">Opening Balance</div>
                        <small class="text-muted" style="font-size: 0.7rem;">Prior to {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }}</small>
                    </div>
                    <div class="font-monospace fw-bold text-dark">
                        Rs. {{ number_format($openingBalance, 2) }}
                    </div>
                </div>
            </div>

            <!-- Transaction Entries -->
            <div class="d-flex flex-column gap-2">
                @forelse($transactions as $entry)
                    <div class="p-3 rounded-3 border bg-white shadow-xs">
                        <div class="d-flex align-items-center justify-content-between gap-1 mb-1.5">
                            <div class="d-flex align-items-center gap-1.5 min-w-0">
                                <span class="fw-bold text-dark small">{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</span>
                                @if($entry->transaction_id)
                                    <span class="font-monospace text-muted small">#{{ str_pad($entry->transaction_id, 5, '0', STR_PAD_LEFT) }}</span>
                                @endif
                            </div>
                            <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.68rem;">
                                {{ $entry->account_name }}
                            </span>
                        </div>

                        @if($entry->description)
                            <div class="text-muted small text-truncate mb-2" style="font-size: 0.74rem;">
                                {{ $entry->description }}
                            </div>
                        @endif

                        <!-- Money Movement Strip -->
                        <div class="p-2 rounded-2 bg-light border d-flex justify-content-between align-items-center" style="font-size: 0.78rem;">
                            <div>
                                @if($entry->debit > 0)
                                    <span class="text-danger fw-bold font-monospace">Dr: -Rs. {{ number_format($entry->debit, 2) }}</span>
                                @elseif($entry->credit > 0)
                                    <span class="text-success fw-bold font-monospace">Cr: +Rs. {{ number_format($entry->credit, 2) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="text-muted d-block" style="font-size: 0.65rem;">Balance</span>
                                <span class="font-monospace fw-bold text-dark">Rs. {{ number_format($entry->running_balance, 2) }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4 small">
                        No ledger transactions found for this party within the selected date range.
                    </div>
                @endforelse
            </div>

            <!-- Mobile Totals Card -->
            <div class="p-3 rounded-3 border bg-primary bg-opacity-10 mt-2">
                <div class="d-flex justify-content-between align-items-center small py-1">
                    <span class="text-danger fw-semibold">Total Debits:</span>
                    <span class="font-monospace fw-bold text-danger">Rs. {{ number_format($totalDebit, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center small py-1">
                    <span class="text-success fw-semibold">Total Credits:</span>
                    <span class="font-monospace fw-bold text-success">Rs. {{ number_format($totalCredit, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-1">
                    <span class="fw-bold text-dark">Net Ending Balance:</span>
                    <span class="font-monospace fw-bold fs-6 text-primary">Rs. {{ number_format($closingBalance, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Verification Signatures (Visible on Print) -->
        <div class="pt-4 pt-md-5 mt-3 mt-md-4 border-top">
            <div class="row text-center g-3">
                <div class="col-12 col-md-4 mb-2 mb-md-0">
                    <div class="border-top border-dark mx-auto" style="width: 75%;"></div>
                    <div class="small fw-bold text-dark mt-1">Prepared By</div>
                    <div class="small text-muted">{{ auth()->user()->name }}</div>
                </div>
                <div class="col-12 col-md-4 mb-2 mb-md-0">
                    <div class="border-top border-dark mx-auto" style="width: 75%;"></div>
                    <div class="small fw-bold text-dark mt-1">Accountant / Manager</div>
                    <div class="small text-muted">Accounts Department</div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="border-top border-dark mx-auto" style="width: 75%;"></div>
                    <div class="small fw-bold text-dark mt-1">Party / Client Signature</div>
                    <div class="small text-muted">Acknowledgment & Acceptance</div>
                </div>
            </div>
        </div>

    </div>
@else
    <div class="card-custom p-4 p-md-5 text-center">
        <i class="bi bi-person-bounding-box text-muted display-4 mb-3"></i>
        <h5 class="fw-bold text-dark">No Party Selected</h5>
        <p class="text-secondary small">Please select a customer, supplier, trader, or staff member from the dropdown above to display their statement of account.</p>
    </div>
@endif

@push('scripts')
<script>
    const partySearch = document.getElementById('partySearch');
    const partyCombobox = document.getElementById('partyCombobox');
    const partyId = document.getElementById('partyId');
    const partyOptions = Array.from(document.querySelectorAll('[data-party-id]'));
    const partyOptionsMenu = document.getElementById('partyOptions');
    const partySearchEmpty = document.getElementById('partySearchEmpty');
    const selectedParty = partyOptions.find(function (option) {
        return option.dataset.partyId === partyId.value;
    });

    if (selectedParty) partySearch.value = selectedParty.textContent.trim();

    function closePartyOptions() {
        partyOptionsMenu.classList.remove('show');
        partySearch.setAttribute('aria-expanded', 'false');
    }

    function filterPartyOptions(query) {
        const normalizedQuery = query.trim().toLocaleLowerCase();
        let matchingParties = 0;

        partyOptions.forEach(function (option) {
            const matches = option.textContent.toLocaleLowerCase().includes(normalizedQuery);
            option.hidden = !matches;
            option.setAttribute('aria-selected', option.dataset.partyId === partyId.value ? 'true' : 'false');
            if (matches) matchingParties++;
        });

        partySearchEmpty.hidden = matchingParties > 0;
    }

    function openPartyOptions(query) {
        filterPartyOptions(query);
        partyOptionsMenu.classList.add('show');
        partySearch.setAttribute('aria-expanded', 'true');
    }

    if (partySearch) {
        partySearch.addEventListener('focus', function () {
            this.select();
            openPartyOptions('');
        });

        partySearch.addEventListener('input', function () {
            if (selectedParty && this.value.trim() !== selectedParty.textContent.trim()) {
                partyId.value = '';
            }
            openPartyOptions(this.value);
        });

        partySearch.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                const firstMatch = partyOptions.find(function (option) { return !option.hidden; });
                if (firstMatch) firstMatch.focus();
            } else if (event.key === 'Escape') {
                closePartyOptions();
            } else if (event.key === 'Enter' && partyOptionsMenu.classList.contains('show')) {
                event.preventDefault();
                const selectedMatch = partyOptions.find(function (option) {
                    return !option.hidden && option.dataset.partyId === partyId.value;
                });
                const firstMatch = partyOptions.find(function (option) { return !option.hidden; });
                const optionToSelect = selectedMatch || firstMatch;
                if (optionToSelect) optionToSelect.click();
            }
        });
    }

    partyOptions.forEach(function (option) {
        option.addEventListener('click', function () {
            partySearch.value = this.textContent.trim();
            partyId.value = this.dataset.partyId;
            partyCombobox.closest('form').submit();
        });

        option.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closePartyOptions();
                partySearch.focus();
            } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const direction = event.key === 'ArrowDown' ? 1 : -1;
                const visibleOptions = partyOptions.filter(function (item) { return !item.hidden; });
                const nextOption = visibleOptions[visibleOptions.indexOf(this) + direction];
                if (nextOption) nextOption.focus();
            }
        });
    });

    document.addEventListener('click', function (event) {
        if (partyCombobox && !partyCombobox.contains(event.target)) closePartyOptions();
    });
</script>
@endpush
@endsection
