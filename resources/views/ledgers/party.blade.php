@extends('layouts.app')

@section('title', 'Party Ledger Statement - ' . ($statementTitle ?? ($party ? $party->name : 'Select Party')))
@section('page_title', ($ledgerScope ?? 'single') === 'all_credit' ? 'Credit Parties Ledger (Combined)' : (($ledgerScope ?? 'single') === 'all_cash' ? 'Cash Parties Ledger (Combined)' : (($ledgerScope ?? 'single') === 'all' ? 'All Parties Consolidated Ledger' : 'Party Ledger Statement')))

@section('page_badge')
    @if(($ledgerScope ?? 'single') === 'all_credit')
        <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1 small">
            <i class="bi bi-credit-card me-1"></i> Combined Credit Ledger &bull; {{ $selectedParties->count() }} Parties
        </span>
    @elseif(($ledgerScope ?? 'single') === 'all_cash')
        <span class="badge bg-success-subtle text-success border border-success px-2 py-1 small">
            <i class="bi bi-cash-stack me-1"></i> Combined Cash Ledger &bull; {{ $selectedParties->count() }} Parties
        </span>
    @elseif(($ledgerScope ?? 'single') === 'all')
        <span class="badge bg-dark-subtle text-dark border px-2 py-1 small">
            <i class="bi bi-people-fill me-1"></i> All Parties &bull; {{ $selectedParties->count() }} Parties
        </span>
    @elseif($party)
        <span class="badge bg-light text-secondary border px-2 py-1 small">
            ID #PRT-{{ str_pad($party->id, 4, '0', STR_PAD_LEFT) }} &bull; {!! $party->type_badge !!} &bull; {!! $party->dealing_badges !!}
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
    #partyOptions [hidden],
    #partyOptions .d-none {
        display: none !important;
    }
    @media (max-width: 575.98px) {
        .card-custom {
            border-radius: 10px;
        }
    }
    /* =========================================================
       BANK STATEMENT PRINT & PDF STYLES
       ========================================================= */
    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        html, body {
            background: #ffffff !important;
            color: #0f172a !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            font-size: 8.8pt !important;
            line-height: 1.3 !important;
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Hide Web Navigation and UI elements */
        .vip-navbar, .sub-header, .no-print, .btn, .alert, footer, nav, header, .modal, .modal-backdrop, .btn-close, .dropdown-menu {
            display: none !important;
        }

        /* Container Resets */
        main, .container-fluid, .row, .col-12, .col-xl-12 {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        #partyStatementSheet, .card-custom {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            background: transparent !important;
        }

        /* Bank Statement Header */
        .bank-stmt-header {
            display: block !important;
            border-bottom: 2px solid #0f172a !important;
            padding-bottom: 8px !important;
            margin-bottom: 12px !important;
        }

        /* Force Ledger Table Display */
        .statement-desktop-table {
            display: block !important;
            width: 100% !important;
            overflow: visible !important;
        }
        .table-responsive {
            overflow: visible !important;
            display: block !important;
        }

        /* Statement Grid & Tables */
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        thead {
            display: table-header-group;
        }
        tfoot {
            display: table-footer-group;
        }

        .table th, .table td,
        .bank-summary-table th, .bank-summary-table td {
            border: 1px solid #475569 !important;
            padding: 4px 6px !important;
            font-size: 8.2pt !important;
            color: #0f172a !important;
        }

        .table thead th,
        .bank-summary-table thead th {
            background-color: #f1f5f9 !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            color: #0f172a !important;
        }

        .badge {
            border: 1px solid #94a3b8 !important;
            background: transparent !important;
            color: #0f172a !important;
            font-size: 7.5pt !important;
            padding: 1px 3px !important;
        }

        /* Signatures block */
        .statement-signatures {
            page-break-inside: avoid;
            margin-top: 25px !important;
            padding-top: 15px !important;
            border-top: 1px solid #0f172a !important;
        }

        .statement-sig-line {
            border-top: 1px dashed #475569;
            width: 80%;
            margin: 0 auto;
            padding-top: 4px;
        }
    }
</style>
@endpush

@section('content')
<!-- Scope Switcher Pills (Single Party vs Combined Credit vs Combined Cash) -->
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap no-print">
    <span class="text-secondary small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.3px;">Ledger Mode:</span>
    <a href="{{ route('ledgers.party', array_filter(['from_date' => $fromDate, 'to_date' => $toDate])) }}" 
       class="btn btn-sm py-1.5 px-3 rounded-pill {{ ($ledgerScope ?? 'single') === 'single' ? 'btn-dark text-white fw-bold shadow-xs' : 'btn-outline-secondary' }}" 
       style="font-size: 0.78rem;">
        <i class="bi bi-person me-1"></i> Individual Party
    </a>
    <a href="{{ route('ledgers.party', array_filter(['party_id' => 'all_credit', 'from_date' => $fromDate, 'to_date' => $toDate])) }}" 
       class="btn btn-sm py-1.5 px-3 rounded-pill {{ ($ledgerScope ?? 'single') === 'all_credit' ? 'btn-primary text-white fw-bold shadow-xs' : 'btn-outline-primary' }}" 
       style="font-size: 0.78rem;">
        <i class="bi bi-credit-card me-1"></i> All Credit Parties (Grouped) <span class="badge bg-primary-subtle text-primary ms-1">{{ $creditPartiesCount ?? 0 }}</span>
    </a>
    <a href="{{ route('ledgers.party', array_filter(['party_id' => 'all_cash', 'from_date' => $fromDate, 'to_date' => $toDate])) }}" 
       class="btn btn-sm py-1.5 px-3 rounded-pill {{ ($ledgerScope ?? 'single') === 'all_cash' ? 'btn-success text-white fw-bold shadow-xs' : 'btn-outline-success' }}" 
       style="font-size: 0.78rem;">
        <i class="bi bi-cash-stack me-1"></i> All Cash Parties (Grouped) <span class="badge bg-success-subtle text-success ms-1">{{ $cashPartiesCount ?? 0 }}</span>
    </a>
</div>

<!-- Filter & Selection Bar (No-Print) -->
<div class="card-custom p-3 mb-3 mb-md-4 no-print">
    <form method="GET" action="{{ route('ledgers.party') }}" id="partyLedgerFilterForm" class="row g-2 align-items-end">
        <div class="col-12 col-md-5">
            <label for="partySearch" class="form-label text-muted small fw-semibold mb-1">Select Counterparty / Multi-Party Ledger</label>
            <div id="partyCombobox" class="position-relative">
                <input type="search" id="partySearch" class="form-control form-control-sm py-2" placeholder="Search or select parties..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="partyOptions">
                <input type="hidden" name="party_id" id="partyId" value="{{ in_array($ledgerScope ?? 'single', ['all_credit', 'all_cash', 'all']) ? $ledgerScope : (($ledgerScope ?? 'single') === 'single' ? ($party->id ?? '') : '') }}">
                
                <div id="partyOptions" class="party-combobox-menu dropdown-menu w-100 p-1 shadow" role="listbox" style="max-height: 440px; overflow-y: auto;">
                    <!-- Quick Combined Ledger Options -->
                    <div class="px-2 py-1 text-muted fw-bold text-uppercase combined-header" style="font-size: 0.65rem; letter-spacing: 0.4px;">Combined Statements</div>
                    <button type="button" class="dropdown-item rounded-1 small text-start py-2 d-flex justify-content-between align-items-center combined-scope-btn {{ ($ledgerScope ?? 'single') === 'all_credit' ? 'active bg-primary text-white' : '' }}" role="option" data-party-id="all_credit" data-display-title="All Credit Parties (Grouped Statement)">
                        <span><i class="bi bi-credit-card me-1 text-primary"></i> <strong>All Credit Parties</strong> (Grouped Statement)</span>
                        <span class="badge {{ ($ledgerScope ?? 'single') === 'all_credit' ? 'bg-white text-primary' : 'bg-primary-subtle text-primary border' }}">{{ $creditPartiesCount ?? 0 }}</span>
                    </button>
                    <button type="button" class="dropdown-item rounded-1 small text-start py-2 d-flex justify-content-between align-items-center combined-scope-btn {{ ($ledgerScope ?? 'single') === 'all_cash' ? 'active bg-success text-white' : '' }}" role="option" data-party-id="all_cash" data-display-title="All Cash Parties (Grouped Statement)">
                        <span><i class="bi bi-cash-stack me-1 text-success"></i> <strong>All Cash Parties</strong> (Grouped Statement)</span>
                        <span class="badge {{ ($ledgerScope ?? 'single') === 'all_cash' ? 'bg-white text-success' : 'bg-success-subtle text-success border' }}">{{ $cashPartiesCount ?? 0 }}</span>
                    </button>
                    
                    <div class="dropdown-divider my-1 combined-divider"></div>
                    <div class="px-2 py-1 text-muted fw-bold text-uppercase d-flex justify-content-between align-items-center" style="font-size: 0.65rem; letter-spacing: 0.4px;">
                        <span>Select Parties (Multi-Select)</span>
                        <div class="btn-group btn-group-sm" id="comboboxModeFilter">
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1.5 active" data-filter-mode="all" style="font-size: 0.65rem;">All</button>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1.5" data-filter-mode="credit" style="font-size: 0.65rem;">Credit</button>
                            <button type="button" class="btn btn-xs btn-outline-success py-0 px-1.5" data-filter-mode="cash" style="font-size: 0.65rem;">Cash</button>
                        </div>
                    </div>

                    <div class="px-2 py-1 bg-light border-bottom d-flex justify-content-between align-items-center small">
                        <span class="text-secondary fw-semibold" style="font-size: 0.68rem;">Check multiple parties:</span>
                        <div class="d-flex gap-1.5">
                            <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none fw-semibold" id="selectAllVisibleBtn" style="font-size: 0.68rem;">Select Visible</button>
                            <span class="text-muted">|</span>
                            <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none text-danger fw-semibold" id="clearAllSelectedBtn" style="font-size: 0.68rem;">Clear</button>
                        </div>
                    </div>

                    <div id="partiesCheckboxList" style="max-height: 220px; overflow-y: auto;">
                        @php
                            $requestedPartyIds = array_map('strval', (array) request('party_ids', []));
                        @endphp
                        @foreach($allParties as $p)
                            @php
                                $isChecked = in_array((string)$p->id, $requestedPartyIds) || (($ledgerScope ?? 'single') === 'single' && ($party->id ?? null) == $p->id);
                            @endphp
                            <div class="dropdown-item rounded-1 small py-1.5 px-2 party-item d-flex align-items-center gap-2 cursor-pointer" data-party-id="{{ $p->id }}" data-party-name="{{ $p->name }}" data-is-credit="{{ $p->is_credit ? '1' : '0' }}" data-is-cash="{{ $p->is_cash ? '1' : '0' }}">
                                <input type="checkbox" name="party_ids[]" value="{{ $p->id }}" class="form-check-input mt-0 party-check" id="partyCheck_{{ $p->id }}" {{ $isChecked ? 'checked' : '' }}>
                                <label for="partyCheck_{{ $p->id }}" class="flex-grow-1 d-flex justify-content-between align-items-center mb-0 cursor-pointer user-select-none">
                                    <span>[{{ ucfirst($p->type) }}] <strong>{{ $p->name }}</strong> <small class="text-muted">({{ $p->phone ?? 'No Phone' }})</small></span>
                                    <span class="ms-1">{!! $p->dealing_badges !!}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div id="partySearchEmpty" class="dropdown-item small text-muted" aria-live="polite" hidden>No matching party found.</div>

                    <div class="p-2 border-top bg-light d-flex justify-content-between align-items-center mt-1">
                        <span class="small text-muted fw-semibold" id="selectedCountBadge" style="font-size: 0.72rem;">0 selected</span>
                        <button type="button" class="btn btn-primary btn-xs py-1 px-3 fw-bold rounded-pill shadow-xs" id="applyMultiSelectBtn" style="font-size: 0.75rem;">
                            <i class="bi bi-check2-circle me-1"></i> View Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">From Date</label>
            <input type="date" name="from_date" class="form-control form-control-sm py-2" value="{{ $fromDate }}">
        </div>
        <div class="col-6 col-md-2">
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

@if($party || ($ledgerScope ?? 'single') !== 'single')
    <!-- Printable Statement Card -->
    <div class="card-custom p-3 p-md-5 bg-white shadow-sm mb-4" id="partyStatementSheet">
        <!-- OFFICIAL BANK STATEMENT PRINT HEADER (Visible on Print) -->
        <div class="bank-stmt-header d-none d-print-block">
            <div class="d-flex justify-content-between align-items-start pb-2 border-bottom border-dark">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="Green Pharmacie" style="height: 52px; max-width: 175px; object-fit: contain;" onerror="this.onerror=null; this.src='/images/logo.png';">
                    <div>
                        <h3 class="fw-bold m-0 text-uppercase tracking-tight" style="color: #0f172a; font-size: 15pt;">GREEN PHARMACIE</h3>
                        <div class="small fw-semibold text-secondary">CENTRAL FINANCIAL TREASURY &amp; COMMERCIAL AUDIT</div>
                        <div class="small text-muted" style="font-size: 8pt;">Your Health Companion &bull; Verified Accounts &amp; Commercial Ledger &bull; Software by <strong>Prowave Technologies (+92 317 3836223)</strong></div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="badge bg-dark text-white text-uppercase px-2 py-1 mb-1" style="font-size: 8.5pt;">Official Statement</div>
                    <div class="fw-bold" style="font-size: 11pt; color: #0f172a;">STATEMENT OF ACCOUNT &amp; PARTY LEDGER</div>
                    <div class="small text-muted" style="font-size: 8pt;">Statement Date: {{ now()->format('d M, Y h:i A') }}</div>
                </div>
            </div>

            <div class="row mt-2 py-1 text-dark" style="font-size: 8.5pt;">
                <div class="col-7">
                    @if($party)
                        <div><strong>Account / Party:</strong> {{ $party->name }} (ID #PRT-{{ str_pad($party->id, 4, '0', STR_PAD_LEFT) }})</div>
                        <div><strong>Category / Type:</strong> {{ ucfirst($party->type) }} &bull; <strong>Contact:</strong> {{ $party->phone ?? 'N/A' }}</div>
                        @if($party->address)
                        <div><strong>Address:</strong> {{ $party->address }}</div>
                        @endif
                    @else
                        <div><strong>Account / Statement:</strong> {{ $statementTitle }}</div>
                        <div><strong>Consolidated Scope:</strong> {{ $selectedParties->count() }} Parties included</div>
                    @endif
                </div>
                <div class="col-5 text-end">
                    <div><strong>Statement Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} &ndash; {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}</div>
                    <div><strong>Base Currency:</strong> PKR (Pakistani Rupee)</div>
                    <div><strong>Auditor:</strong> {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</div>
                </div>
            </div>
        </div>

        <!-- Bank Statement Summary Table (Print Only) -->
        <table class="bank-summary-table d-none d-print-table mb-3">
            <thead>
                <tr>
                    <th class="text-start" style="width: 25%;">Opening Balance</th>
                    <th class="text-end" style="width: 25%;">Total Debits (Dr)</th>
                    <th class="text-end" style="width: 25%;">Total Credits (Cr)</th>
                    <th class="text-end" style="width: 25%;">Net Ending Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-start font-monospace">Rs. {{ number_format($openingBalance, 2) }}</td>
                    <td class="text-end font-monospace text-danger">Rs. {{ number_format($totalDebit, 2) }}</td>
                    <td class="text-end font-monospace text-success">Rs. {{ number_format($totalCredit, 2) }}</td>
                    <td class="text-end font-monospace fw-bold">
                        Rs. {{ number_format(abs($closingBalance), 2) }}
                        <small style="font-size: 7pt;">
                            @if($party)
                                @if($party->type === 'customer' || $party->type === 'staff')
                                    {{ $closingBalance > 0 ? '(Dr - Receivable)' : ($closingBalance < 0 ? '(Cr - Advance)' : '(Settled)') }}
                                @else
                                    {{ $closingBalance > 0 ? '(Cr - Payable)' : ($closingBalance < 0 ? '(Dr - Overpaid)' : '(Settled)') }}
                                @endif
                            @else
                                {{ $closingBalance > 0 ? '(Cr - Net Payable)' : ($closingBalance < 0 ? '(Dr - Net Receivable)' : '(Settled)') }}
                            @endif
                        </small>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Letterhead Header -->
        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 pb-md-4 mb-3 mb-md-4 flex-wrap gap-2 d-print-none">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="Green Pharmacie" style="height: 48px; max-width: 170px; object-fit: contain;" class="rounded-1" onerror="this.onerror=null; this.src='/images/logo.png';">
                <div>
                    <h4 class="fw-bold text-dark m-0">Green Pharmacie</h4>
                    <h5 class="text-secondary fw-bold m-0" style="font-size: clamp(1rem, 3.5vw, 1.25rem);">Statement of Account & Ledger</h5>
                    <small class="text-muted">Your Health Companion &bull; Financial Accounting Ledger &bull; Software by <strong>Prowave Technologies (+92 317 3836223)</strong></small>
                </div>
            </div>
            <div class="text-start text-md-end">
                <span class="badge bg-light text-dark border px-2 py-1 mb-1 font-monospace d-inline-block">
                    Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M, Y') }}
                </span>
                <div class="small text-muted" style="font-size: 0.72rem;">Generated: {{ now()->format('d M, Y - h:i A') }}</div>
                <div class="small text-muted" style="font-size: 0.72rem;">Auditor: <strong>{{ auth()->user()->name }}</strong></div>
            </div>
        </div>

        <!-- Party / Group Profile Box -->
        <div class="row g-2 g-md-3 mb-3 mb-md-4 p-3 bg-light rounded-3 border d-print-none">
            <div class="col-12 col-md-7">
                <div class="text-muted small fw-semibold" style="font-size: 0.75rem;">Account / Statement Target:</div>
                <h5 class="fw-bold text-dark m-0">{{ $statementTitle }}</h5>
                <div class="small text-muted mt-1" style="font-size: 0.74rem;">
                    @if($party)
                        <i class="bi bi-geo-alt me-1"></i>{{ $party->address ?? 'No physical address registered' }}
                    @else
                        <i class="bi bi-people me-1"></i>Combined statement of <strong>{{ $selectedParties->count() }}</strong> active parties
                    @endif
                </div>
            </div>
            <div class="col-12 col-md-5 text-start text-md-end">
                <div class="text-muted small fw-semibold" style="font-size: 0.75rem;">Category & Dealing Mode:</div>
                @if($party)
                    <div class="mt-1">{!! $party->type_badge !!} {!! $party->dealing_badges !!}</div>
                    <div class="small font-monospace text-dark mt-1" style="font-size: 0.74rem;">
                        <i class="bi bi-telephone me-1 text-primary"></i>{{ $party->phone ?? 'N/A' }}
                    </div>
                @else
                    <div class="mt-1">
                        @if(($ledgerScope ?? 'single') === 'all_credit')
                            <span class="badge bg-primary text-white px-2 py-1"><i class="bi bi-credit-card me-1"></i>Credit Parties Only</span>
                        @elseif(($ledgerScope ?? 'single') === 'all_cash')
                            <span class="badge bg-success text-white px-2 py-1"><i class="bi bi-cash-stack me-1"></i>Cash Parties Only</span>
                        @else
                            <span class="badge bg-dark text-white px-2 py-1"><i class="bi bi-people-fill me-1"></i>All Parties Consolidated</span>
                        @endif
                    </div>
                    <div class="small text-muted mt-1" style="font-size: 0.74rem;">
                        Total Accounts Included: <strong>{{ $selectedParties->count() }}</strong>
                    </div>
                @endif
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-2 g-md-3 mb-3 mb-md-4 d-print-none">
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
                        @if($party)
                            @if($party->type === 'customer' || $party->type === 'staff')
                                {{ $closingBalance > 0 ? 'Receivable (Dr)' : ($closingBalance < 0 ? 'Advance / Credit (Cr)' : 'Settled / Zero') }}
                            @else
                                {{ $closingBalance > 0 ? 'Payable to Supplier' : ($closingBalance < 0 ? 'Debit Balance (Overpaid)' : 'Settled / Zero') }}
                            @endif
                        @else
                            {{ $closingBalance > 0 ? 'Net Payable Balance' : ($closingBalance < 0 ? 'Net Receivable Balance' : 'Settled / Zero') }}
                        @endif
                    </small>
                </div>
            </div>
        </div>

        @if(($ledgerScope ?? 'single') === 'single')
            <!-- Desktop View: Single Party Ledger Table -->
            <div class="d-none d-md-block statement-desktop-table table-responsive mb-4">
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

            <!-- Mobile View: Single Party -->
            <div class="d-block d-md-none mb-3 d-print-none">
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
        @else
            <!-- Multi-Party / Combined Groups: Each Party in its own Group Table -->
            <div class="party-groups-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-collection me-1.5 text-primary"></i>Party-Wise Statements ({{ count($partyGroups) }} {{ count($partyGroups) === 1 ? 'Party' : 'Parties' }})
                    </h6>
                    <span class="text-muted small">Each party displayed in an individual group with its own opening &amp; closing balance</span>
                </div>

                @forelse($partyGroups as $index => $group)
                    <div class="card-custom p-3 p-md-4 mb-4 bg-white border shadow-xs party-statement-block" style="page-break-inside: avoid;">
                        <!-- Party Group Header -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-2 mb-3 border-bottom">
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge bg-dark text-white rounded-circle px-2 py-1" style="font-size: 0.72rem;">#{{ $index + 1 }}</span>
                                    <a href="{{ route('ledgers.party', ['party_id' => $group->party->id, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="fs-5 fw-bold text-dark text-decoration-none" title="Click to view dedicated single statement">
                                        {{ $group->party->name }}
                                    </a>
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5" style="font-size: 0.7rem;">{{ ucfirst($group->party->type) }}</span>
                                    {!! $group->party->dealing_badges !!}
                                    @if($group->party->phone)
                                        <span class="text-muted small ms-1"><i class="bi bi-telephone me-1"></i>{{ $group->party->phone }}</span>
                                    @endif
                                </div>
                                @if($group->party->address)
                                    <small class="text-muted d-block mt-0.5" style="font-size: 0.75rem;"><i class="bi bi-geo-alt me-1"></i>{{ $group->party->address }}</small>
                                @endif
                            </div>
                            <div class="d-flex gap-3 align-items-center bg-light px-3 py-1.5 rounded-3 border">
                                <div class="text-end">
                                    <span class="text-muted small d-block" style="font-size: 0.68rem;">Starting Balance:</span>
                                    <span class="font-monospace fw-bold text-dark" style="font-size: 0.85rem;">Rs. {{ number_format($group->opening_balance, 2) }}</span>
                                </div>
                                <div class="vr my-1"></div>
                                <div class="text-end">
                                    <span class="text-muted small d-block" style="font-size: 0.68rem;">Closing Balance:</span>
                                    <span class="font-monospace fw-bold {{ $group->closing_balance > 0 ? 'text-primary' : ($group->closing_balance < 0 ? 'text-danger' : 'text-success') }}" style="font-size: 0.85rem;">
                                        Rs. {{ number_format(abs($group->closing_balance), 2) }}
                                        <small style="font-size: 0.65rem;">
                                            @if($group->party->type === 'customer' || $group->party->type === 'staff')
                                                {{ $group->closing_balance > 0 ? '(Dr)' : ($group->closing_balance < 0 ? '(Cr)' : '(0)') }}
                                            @else
                                                {{ $group->closing_balance > 0 ? '(Cr)' : ($group->closing_balance < 0 ? '(Dr)' : '(0)') }}
                                            @endif
                                        </small>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Table for this party -->
                        <div class="table-responsive statement-desktop-table mb-2">
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
                                    <tr class="table-secondary bg-opacity-25 fw-semibold">
                                        <td>{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                                        <td class="font-monospace text-muted">-</td>
                                        <td class="text-muted">Opening Balance</td>
                                        <td class="text-muted">Balance prior to {{ \Carbon\Carbon::parse($fromDate)->format('d M, Y') }}</td>
                                        <td class="text-end font-monospace text-muted">-</td>
                                        <td class="text-end font-monospace text-muted">-</td>
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            Rs. {{ number_format($group->opening_balance, 2) }}
                                        </td>
                                    </tr>
                                    @forelse($group->transactions as $entry)
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
                                            <td colspan="7" class="text-center text-muted py-3 small">
                                                <i class="bi bi-info-circle me-1"></i> No transactions recorded during this period. (Carrying forward Opening Balance of Rs. {{ number_format($group->opening_balance, 2) }})
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="4" class="text-end">{{ $group->party->name }} &ndash; Subtotals:</td>
                                        <td class="text-end font-monospace text-danger">Rs. {{ number_format($group->total_debit, 2) }}</td>
                                        <td class="text-end font-monospace text-success">Rs. {{ number_format($group->total_credit, 2) }}</td>
                                        <td class="text-end font-monospace text-primary">Rs. {{ number_format($group->closing_balance, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Mobile View for this Group -->
                        <div class="d-block d-md-none d-print-none mb-2">
                            <div class="d-flex flex-column gap-2">
                                @forelse($group->transactions as $entry)
                                    <div class="p-2.5 rounded-3 border bg-white shadow-xs">
                                        <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                            <span class="fw-bold text-dark small">{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</span>
                                            <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.68rem;">
                                                {{ $entry->account_name }}
                                            </span>
                                        </div>
                                        @if($entry->description)
                                            <div class="text-muted small text-truncate mb-1.5" style="font-size: 0.72rem;">
                                                {{ $entry->description }}
                                            </div>
                                        @endif
                                        <div class="p-1.5 rounded-2 bg-light border d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
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
                                                <span class="font-monospace fw-bold text-dark">Rs. {{ number_format($entry->running_balance, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-muted py-2 small">
                                        No transactions recorded.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card-custom p-4 text-center text-muted">
                        No parties matched your selection.
                    </div>
                @endforelse
            </div>
        @endif

        <!-- Official Bank Statement Signatures (Visible on Print) -->
        <div class="statement-signatures pt-4 mt-4">
            <div class="row text-center w-100 m-0">
                <div class="col-4">
                    <div class="statement-sig-line">
                        <div class="small fw-bold text-dark">Prepared By (Accountant)</div>
                        <div class="small text-muted" style="font-size: 8pt;">{{ auth()->user()->name }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="statement-sig-line">
                        <div class="small fw-bold text-dark">Supervisor / Audit Verified</div>
                        <div class="small text-muted" style="font-size: 8pt;">Accounts Department</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="statement-sig-line">
                        <div class="small fw-bold text-dark">Party / Client Acknowledgment</div>
                        <div class="small text-muted" style="font-size: 8pt;">Acceptance Signature</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statement Footer Notice -->
        <div class="text-center pt-3 mt-2 border-top text-muted small" style="font-size: 8pt;">
            <span>Official Statement of Account &bull; Software Developed by <strong>Prowave Technologies</strong> &bull; Support Helpline: <a href="tel:+923173836223" class="text-decoration-none text-dark fw-bold">+92 317 3836223</a></span>
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
    const partyOptions = Array.from(document.querySelectorAll('.combined-scope-btn'));
    const partyItems = Array.from(document.querySelectorAll('.party-item'));
    const partyChecks = Array.from(document.querySelectorAll('.party-check'));
    const partyOptionsMenu = document.getElementById('partyOptions');
    const partySearchEmpty = document.getElementById('partySearchEmpty');
    const comboboxModeFilter = document.getElementById('comboboxModeFilter');
    const selectAllVisibleBtn = document.getElementById('selectAllVisibleBtn');
    const clearAllSelectedBtn = document.getElementById('clearAllSelectedBtn');
    const applyMultiSelectBtn = document.getElementById('applyMultiSelectBtn');
    const selectedCountBadge = document.getElementById('selectedCountBadge');
    let currentDealingFilter = 'all';

    function updateSelectedCount() {
        const checkedCount = partyChecks.filter(function (cb) { return cb.checked; }).length;
        if (selectedCountBadge) {
            selectedCountBadge.textContent = checkedCount + (checkedCount === 1 ? ' party selected' : ' parties selected');
        }
        return checkedCount;
    }

    // Set initial search input display value based on scope
    const currentScope = @json($ledgerScope ?? 'single');
    const selectedCountOnLoad = updateSelectedCount();

    if (partySearch) {
        if (currentScope === 'all_credit') {
            partySearch.value = 'All Credit Parties (Grouped Statement)';
        } else if (currentScope === 'all_cash') {
            partySearch.value = 'All Cash Parties (Grouped Statement)';
        } else if (currentScope === 'multiple') {
            partySearch.value = selectedCountOnLoad + ' Parties Selected';
        } else if (currentScope === 'single') {
            const singlePartyName = @json($party->name ?? '');
            if (singlePartyName) partySearch.value = singlePartyName;
        }
    }

    function closePartyOptions() {
        if (partyOptionsMenu) {
            partyOptionsMenu.classList.remove('show');
            if (partySearch) partySearch.setAttribute('aria-expanded', 'false');
        }
    }

    function filterPartyOptions(query) {
        const normalizedQuery = (query || '').trim().toLocaleLowerCase();
        let matchingParties = 0;

        let anyCombinedVisible = false;
        partyOptions.forEach(function (btn) {
            const matches = !normalizedQuery || btn.textContent.toLocaleLowerCase().includes(normalizedQuery);
            let modeMatches = true;
            if (currentDealingFilter === 'credit') {
                modeMatches = btn.dataset.partyId === 'all_credit';
            } else if (currentDealingFilter === 'cash') {
                modeMatches = btn.dataset.partyId === 'all_cash';
            }
            const visible = matches && modeMatches;
            btn.hidden = !visible;
            btn.classList.toggle('d-none', !visible);
            if (visible) anyCombinedVisible = true;
        });

        const combinedHeader = partyOptionsMenu ? partyOptionsMenu.querySelector('.combined-header') : null;
        const combinedDivider = partyOptionsMenu ? partyOptionsMenu.querySelector('.combined-divider') : null;
        if (combinedHeader) {
            combinedHeader.hidden = !anyCombinedVisible;
            combinedHeader.classList.toggle('d-none', !anyCombinedVisible);
        }
        if (combinedDivider) {
            combinedDivider.hidden = !anyCombinedVisible;
            combinedDivider.classList.toggle('d-none', !anyCombinedVisible);
        }

        partyItems.forEach(function (item) {
            const partyName = (item.dataset.partyName || item.textContent || '').toLocaleLowerCase();
            const textMatches = !normalizedQuery || partyName.includes(normalizedQuery) || item.textContent.toLocaleLowerCase().includes(normalizedQuery);
            let modeMatches = true;
            if (currentDealingFilter === 'credit') {
                modeMatches = item.dataset.isCredit === '1';
            } else if (currentDealingFilter === 'cash') {
                modeMatches = item.dataset.isCash === '1';
            }
            const visible = textMatches && modeMatches;
            item.hidden = !visible;
            item.classList.toggle('d-none', !visible);
            if (visible) matchingParties++;
        });

        if (partySearchEmpty) {
            partySearchEmpty.hidden = matchingParties > 0;
            partySearchEmpty.classList.toggle('d-none', matchingParties > 0);
        }
    }

    function openPartyOptions(query) {
        filterPartyOptions(query);
        if (partyOptionsMenu) {
            partyOptionsMenu.classList.add('show');
            if (partySearch) partySearch.setAttribute('aria-expanded', 'true');
        }
    }

    if (comboboxModeFilter) {
        comboboxModeFilter.addEventListener('click', function (e) {
            e.stopPropagation();
            const btn = e.target.closest('[data-filter-mode]');
            if (!btn) return;
            
            currentDealingFilter = btn.dataset.filterMode;
            comboboxModeFilter.querySelectorAll('[data-filter-mode]').forEach(function (b) {
                b.classList.remove('active');
            });
            btn.classList.add('active');
            filterPartyOptions(partySearch ? partySearch.value : '');
            if (partySearch) partySearch.focus();
        });
    }

    // Combined scope quick buttons click (All Credit / All Cash)
    partyOptions.forEach(function (btn) {
        btn.addEventListener('click', function () {
            partyChecks.forEach(function (cb) { cb.checked = false; });
            if (partyId) partyId.value = this.dataset.partyId;
            if (partySearch) partySearch.value = this.dataset.displayTitle || this.textContent.trim();
            partyCombobox.closest('form').submit();
        });
    });

    // Checkbox changes
    partyChecks.forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (partyId) partyId.value = '';
            const count = updateSelectedCount();
            if (partySearch) {
                partySearch.value = count === 0 ? '' : (count === 1 ? '1 Party Selected' : count + ' Parties Selected');
            }
        });
    });

    // Select visible parties
    if (selectAllVisibleBtn) {
        selectAllVisibleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (partyId) partyId.value = '';
            partyItems.forEach(function (item) {
                if (!item.hidden && !item.classList.contains('d-none')) {
                    const cb = item.querySelector('.party-check');
                    if (cb) cb.checked = true;
                }
            });
            const count = updateSelectedCount();
            if (partySearch) partySearch.value = count + ' Parties Selected';
        });
    }

    // Clear all selected parties
    if (clearAllSelectedBtn) {
        clearAllSelectedBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (partyId) partyId.value = '';
            partyChecks.forEach(function (cb) { cb.checked = false; });
            updateSelectedCount();
            if (partySearch) partySearch.value = '';
        });
    }

    // Apply Multi-Select button
    if (applyMultiSelectBtn) {
        applyMultiSelectBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const checkedCount = partyChecks.filter(function (cb) { return cb.checked; }).length;
            if (checkedCount === 0) {
                alert('Please select at least one party by checking its checkbox.');
                return;
            }
            if (partyId) partyId.value = '';
            partyCombobox.closest('form').submit();
        });
    }

    if (partySearch) {
        partySearch.addEventListener('focus', function () {
            this.select();
            openPartyOptions('');
        });

        partySearch.addEventListener('click', function () {
            if (partyOptionsMenu && !partyOptionsMenu.classList.contains('show')) {
                openPartyOptions(this.value);
            }
        });

        partySearch.addEventListener('input', function () {
            openPartyOptions(this.value);
        });

        partySearch.addEventListener('search', function () {
            openPartyOptions(this.value);
        });

        partySearch.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closePartyOptions();
            } else if (event.key === 'Enter' && partyOptionsMenu.classList.contains('show')) {
                event.preventDefault();
                const visibleItems = partyItems.filter(function (it) {
                    return !it.hidden && !it.classList.contains('d-none');
                });
                const checkedCount = partyChecks.filter(function (cb) { return cb.checked; }).length;
                if (checkedCount === 0 && visibleItems.length === 1) {
                    const cb = visibleItems[0].querySelector('.party-check');
                    if (cb) cb.checked = true;
                }
                applyMultiSelectBtn.click();
            }
        });
    }

    // Don't close dropdown on clicking inside menu controls or checkboxes
    if (partyOptionsMenu) {
        partyOptionsMenu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    document.addEventListener('click', function (event) {
        if (partyCombobox && !partyCombobox.contains(event.target)) closePartyOptions();
    });
</script>
@endpush
@endsection
