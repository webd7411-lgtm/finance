@extends('layouts.app')

@section('title', 'Edit Payment Account')
@section('page_title', 'Edit Payment Account')

@section('page_actions')
    <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary btn-sm rounded-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Accounts
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card-custom">
            <div class="p-3 border-bottom">
                <h6 class="fw-bold text-dark m-0">Edit Account: {{ $account->name }}</h6>
                <small class="text-muted" style="font-size: 0.78rem;">Update channel title, account number, or opening balance</small>
            </div>

            <div class="p-4">
                <form action="{{ route('accounts.update', $account->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-secondary">Account Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm py-2 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $account->name) }}" required>
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="type" class="form-label small fw-semibold text-secondary">Account Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm py-2 @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="cash" {{ old('type', $account->type) == 'cash' ? 'selected' : '' }}>Cash in Hand (Counter)</option>
                                <option value="jazzcash" {{ old('type', $account->type) == 'jazzcash' ? 'selected' : '' }}>JazzCash / Easypaisa</option>
                                <option value="bank" {{ old('type', $account->type) == 'bank' ? 'selected' : '' }}>Bank Account</option>
                            </select>
                            @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6">
                            <label for="account_number" class="form-label small fw-semibold text-secondary">Account / Mobile Number</label>
                            <input type="text" class="form-control form-control-sm py-2 @error('account_number') is-invalid @enderror" id="account_number" name="account_number" value="{{ old('account_number', $account->account_number) }}">
                            @error('account_number')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="opening_balance" class="form-label small fw-semibold text-secondary">Opening Balance (Rs.)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-secondary">PKR</span>
                            <input type="number" step="0.01" class="form-control @error('opening_balance') is-invalid @enderror" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', $account->opening_balance) }}">
                        </div>
                        @error('opening_balance')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('accounts.index') }}" class="btn btn-light btn-sm px-3">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold rounded-3 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Update Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
