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

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-secondary d-flex justify-content-between align-items-center">
                            <span>Party Name</span>
                            <span class="badge bg-light text-muted border small"><i class="bi bi-lock-fill me-1"></i>Locked</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-building"></i></span>
                            <input type="text" class="form-control bg-light text-muted fw-semibold" id="name" value="{{ $party->name }}" readonly disabled>
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">
                            <i class="bi bi-info-circle me-1"></i>Party name cannot be edited once party is created.
                        </small>
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
