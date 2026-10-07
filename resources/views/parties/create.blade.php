@extends('layouts.app')

@section('title', 'Add New Party')
@section('page_title', 'Register New Party')

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
                <h6 class="fw-bold text-dark m-0">Party Profile & Opening Balance</h6>
                <small class="text-muted" style="font-size: 0.78rem;">Register suppliers, traders, staff, or customers for ledger tracking</small>
            </div>

            <div class="p-4">
                <form action="{{ route('parties.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-secondary">Party Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm py-2 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Al-Madina Traders / Haji Rashid" required>
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="type" class="form-label small fw-semibold text-secondary">Party Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm py-2 @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="" disabled selected>-- Select Type --</option>
                                <option value="supplier" {{ old('type') == 'supplier' ? 'selected' : '' }}>Supplier</option>
                                <option value="trader" {{ old('type') == 'trader' ? 'selected' : '' }}>Trader</option>
                                <option value="staff" {{ old('type') == 'staff' ? 'selected' : '' }}>Staff (Advance)</option>
                                <option value="customer" {{ old('type') == 'customer' ? 'selected' : '' }}>Customer</option>
                            </select>
                            @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6">
                            <label for="phone" class="form-label small fw-semibold text-secondary">Phone Number</label>
                            <input type="text" class="form-control form-control-sm py-2 @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="0300-1234567">
                            @error('phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label small fw-semibold text-secondary">Address / Location</label>
                        <input type="text" class="form-control form-control-sm py-2 @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}" placeholder="Shop / Market / City address">
                        @error('address')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="opening_balance" class="form-label small fw-semibold text-secondary">
                            Opening Balance (Rs.)
                            <span class="text-muted fw-normal">(Previous balance if any)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-secondary">PKR</span>
                            <input type="text" inputmode="decimal" class="form-control amount-format @error('opening_balance') is-invalid @enderror" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', '0.00') }}" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="form-text small text-muted" style="font-size: 0.75rem;">
                            Enter positive amount if party has outstanding balance, or 0 if starting fresh.
                        </div>
                        @error('opening_balance')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('parties.index') }}" class="btn btn-light btn-sm px-3">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold rounded-3 shadow-sm">
                            <i class="bi bi-save me-1"></i> Save Party
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
