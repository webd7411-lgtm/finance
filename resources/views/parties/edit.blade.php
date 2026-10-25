@extends('layouts.app')

@section('title', 'Edit Party')
@section('page_title', 'Edit Party Details')

@section('page_actions')
    <a href="{{ route('parties.index') }}" class="btn btn-outline-secondary btn-sm rounded-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Parties
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card-custom">
            <div class="p-3 border-bottom">
                <h6 class="fw-bold text-dark m-0">Edit: {{ $party->name }}</h6>
                <small class="text-muted" style="font-size: 0.78rem;">Update party information and contact details</small>
            </div>

            <div class="p-4">
                <form id="editPartyForm" action="{{ route('parties.update', $party->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3 position-relative">
                        <label for="name" class="form-label small fw-semibold text-secondary">Party Name <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-control-sm py-2 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $party->name) }}" autocomplete="off" required>
                            <span class="input-group-text bg-white" id="name_spinner" style="display: none;">
                                <span class="spinner-border spinner-border-sm text-primary" role="status" style="width: 0.9rem; height: 0.9rem;"></span>
                            </span>
                        </div>
                        
                        <!-- Suggestions / Live Duplicate Alert container -->
                        <div id="name_feedback" class="mt-2" style="display: none;"></div>

                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="type" class="form-label small fw-semibold text-secondary">Party Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm py-2 @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="supplier" {{ old('type', $party->type) == 'supplier' ? 'selected' : '' }}>Supplier</option>
                                <option value="trader" {{ old('type', $party->type) == 'trader' ? 'selected' : '' }}>Trader</option>
                                <option value="staff" {{ old('type', $party->type) == 'staff' ? 'selected' : '' }}>Staff (Advance)</option>
                                <option value="customer" {{ old('type', $party->type) == 'customer' ? 'selected' : '' }}>Customer</option>
                            </select>
                            @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6">
                            <label for="phone" class="form-label small fw-semibold text-secondary">Phone Number</label>
                            <input type="text" class="form-control form-control-sm py-2 @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $party->phone) }}">
                            @error('phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label small fw-semibold text-secondary">Address / Location</label>
                        <input type="text" class="form-control form-control-sm py-2 @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $party->address) }}">
                        @error('address')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Dealing Terms / Mode (Credit & Cash) -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary d-flex justify-content-between align-items-center mb-1">
                            <span>Dealing Mode / Terms <span class="text-danger">*</span></span>
                            <span class="text-muted fw-normal" style="font-size: 0.72rem;">Select Credit, Cash, or both</span>
                        </label>
                        <input type="hidden" name="dealing_mode_submitted" value="1">
                        <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center gap-4">
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" name="is_credit" id="is_credit" value="1" {{ old('dealing_mode_submitted') ? (old('is_credit') ? 'checked' : '') : ($party->is_credit ? 'checked' : '') }}>
                                <label class="form-check-label small fw-semibold text-dark user-select-none" for="is_credit">
                                    <i class="bi bi-credit-card text-primary me-1"></i> Credit
                                </label>
                            </div>
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" name="is_cash" id="is_cash" value="1" {{ old('dealing_mode_submitted') ? (old('is_cash') ? 'checked' : '') : ($party->is_cash ? 'checked' : '') }}>
                                <label class="form-check-label small fw-semibold text-dark user-select-none" for="is_cash">
                                    <i class="bi bi-cash-stack text-success me-1"></i> Cash
                                </label>
                            </div>
                        </div>
                        @error('dealing_mode')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        @error('is_credit')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        @error('is_cash')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="opening_balance" class="form-label small fw-semibold text-secondary d-flex justify-content-between align-items-center">
                            <span>Opening Balance (Rs.)</span>
                            <span class="badge bg-light text-muted border small"><i class="bi bi-lock-fill me-1"></i>Locked</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-secondary">PKR</span>
                            <input type="text" class="form-control bg-light text-muted font-monospace" id="opening_balance" value="{{ number_format($party->opening_balance, 2) }}" readonly disabled>
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">
                            <i class="bi bi-info-circle me-1"></i>Opening balance cannot be edited once party is created.
                        </small>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('parties.index') }}" class="btn btn-light btn-sm px-3">Cancel</a>
                        <button type="submit" id="update_party_btn" class="btn btn-primary btn-sm px-4 fw-semibold rounded-3 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Update Party
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('name');
    const feedback = document.getElementById('name_feedback');
    const spinner = document.getElementById('name_spinner');
    const submitBtn = document.getElementById('update_party_btn');
    const form = document.getElementById('editPartyForm');
    const currentName = "{{ addslashes($party->name) }}";
    const excludeId = {{ $party->id }};
    const checkUrl = "{{ route('parties.check-duplicate') }}";

    if (!input || !feedback) return;

    let debounceTimer = null;
    let abortController = null;
    let hasExactDuplicate = false;

    function resetState() {
        feedback.innerHTML = '';
        feedback.style.display = 'none';
        input.classList.remove('is-invalid', 'is-valid');
        if (spinner) spinner.style.display = 'none';
        hasExactDuplicate = false;
        if (submitBtn) submitBtn.disabled = false;
    }

    input.addEventListener('input', function() {
        const query = input.value.trim();

        if (debounceTimer) clearTimeout(debounceTimer);
        if (abortController) abortController.abort();

        if (query.length < 1 || query.toLowerCase() === currentName.toLowerCase()) {
            resetState();
            return;
        }

        if (spinner) spinner.style.display = 'inline-block';

        debounceTimer = setTimeout(() => {
            abortController = new AbortController();
            let url = checkUrl + '?name=' + encodeURIComponent(query) + '&exclude_id=' + encodeURIComponent(excludeId);

            fetch(url, {
                signal: abortController.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (spinner) spinner.style.display = 'none';

                if (data.exact_match && data.exact_party) {
                    hasExactDuplicate = true;
                    input.classList.add('is-invalid');
                    input.classList.remove('is-valid');
                    if (submitBtn) submitBtn.disabled = true;

                    feedback.innerHTML = `
                        <div class="alert alert-danger p-2.5 mb-0 rounded-3 shadow-xs border border-danger-subtle">
                            <div class="d-flex align-items-center gap-1.5 fw-bold text-danger mb-1" style="font-size: 0.83rem;">
                                <i class="bi bi-exclamation-octagon-fill text-danger fs-6"></i>
                                <span>Already Registered! Duplicate not allowed.</span>
                            </div>
                            <div class="small text-dark mb-2" style="font-size: 0.8rem;">
                                Another party named <strong class="text-danger">"${data.exact_party.name}"</strong> is already registered as 
                                <span class="badge bg-secondary">${data.exact_party.type}</span>
                                ${data.exact_party.phone && data.exact_party.phone !== 'N/A' ? `<span class="text-muted ms-1">(Phone: ${data.exact_party.phone})</span>` : ''}.
                            </div>
                            <div class="d-flex align-items-center justify-content-between pt-1.5 border-top border-danger-subtle">
                                <a href="${data.exact_party.ledger_url}" target="_blank" class="btn btn-outline-danger btn-xs py-1 px-2 fw-semibold text-decoration-none" style="font-size: 0.75rem;">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> View Existing Ledger
                                </a>
                                <span class="text-muted small" style="font-size: 0.73rem;">Choose a different name</span>
                            </div>
                        </div>
                    `;
                    feedback.style.display = 'block';
                } else if (data.suggestions && data.suggestions.length > 0) {
                    hasExactDuplicate = false;
                    input.classList.remove('is-invalid', 'is-valid');
                    if (submitBtn) submitBtn.disabled = false;

                    let listItems = data.suggestions.map(item => `
                        <div class="list-group-item d-flex justify-content-between align-items-center py-1.5 px-2.5 bg-white">
                            <div class="me-2 text-truncate">
                                <span class="fw-semibold text-dark" style="font-size: 0.82rem;">${item.name}</span>
                                <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.7rem;">${item.type}</span>
                                ${item.phone && item.phone !== 'N/A' ? `<small class="text-muted ms-2"><i class="bi bi-telephone"></i> ${item.phone}</small>` : ''}
                            </div>
                            <a href="${item.ledger_url}" target="_blank" class="btn btn-light btn-xs border text-primary fw-semibold py-0.5 px-1.5 text-nowrap" style="font-size: 0.72rem;">
                                Ledger <i class="bi bi-box-arrow-up-right ms-0.5"></i>
                            </a>
                        </div>
                    `).join('');

                    feedback.innerHTML = `
                        <div class="card border border-warning-subtle bg-warning bg-opacity-10 rounded-3 shadow-xs p-2 mb-0">
                            <div class="d-flex align-items-center justify-content-between mb-1.5 px-1">
                                <span class="small fw-bold text-dark d-flex align-items-center" style="font-size: 0.78rem;">
                                    <i class="bi bi-search text-warning me-1.5"></i>
                                    Other similar parties (${data.suggestions.length} found):
                                </span>
                                <small class="text-muted" style="font-size: 0.72rem;">Avoid duplicate entry</small>
                            </div>
                            <div class="list-group list-group-flush rounded border overflow-hidden">
                                ${listItems}
                            </div>
                        </div>
                    `;
                    feedback.style.display = 'block';
                } else {
                    hasExactDuplicate = false;
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    if (submitBtn) submitBtn.disabled = false;

                    feedback.innerHTML = `
                        <div class="text-success small d-flex align-items-center px-1" style="font-size: 0.78rem;">
                            <i class="bi bi-check-circle-fill me-1.5 text-success"></i>
                            <span>Party name is unique & available.</span>
                        </div>
                    `;
                    feedback.style.display = 'block';
                }
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    if (spinner) spinner.style.display = 'none';
                }
            });
        }, 250);
    });

    if (form) {
        form.addEventListener('submit', function(e) {
            if (hasExactDuplicate) {
                e.preventDefault();
                input.focus();
                alert('Another party with this exact name already exists. Please choose a different name.');
            }
        });
    }
});
</script>
@endpush
