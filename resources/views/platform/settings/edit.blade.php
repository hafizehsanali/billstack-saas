@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">Platform Settings</h1>
        <div class="text-muted">Manage registration access, support details, and subscription payment guidance.</div>
    </div>
    <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">Platform</a>
</div>

<form method="POST" action="{{ route('platform.settings.update') }}">
    @csrf
    @method('PUT')

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="card-title mb-0">Platform Identity</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Platform Name <span class="text-danger">*</span></label>
                    <input type="text"
                           name="platform_name"
                           value="{{ old('platform_name', $settings->platform_name) }}"
                           class="form-control @error('platform_name') is-invalid @enderror"
                           required>
                    @error('platform_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Currency Code <span class="text-danger">*</span></label>
                    <input type="text"
                           name="currency_code"
                           maxlength="3"
                           value="{{ old('currency_code', $settings->currency_code) }}"
                           class="form-control text-uppercase @error('currency_code') is-invalid @enderror"
                           required>
                    @error('currency_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Support Email</label>
                    <input type="email"
                           name="support_email"
                           value="{{ old('support_email', $settings->support_email) }}"
                           class="form-control @error('support_email') is-invalid @enderror">
                    @error('support_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Support Phone</label>
                    <input type="text"
                           name="support_phone"
                           value="{{ old('support_phone', $settings->support_phone) }}"
                           class="form-control @error('support_phone') is-invalid @enderror">
                    @error('support_phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title mb-0">Subscription Access</h2>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-check">
                    <input type="hidden" name="allow_registration" value="0">
                    <input type="checkbox"
                           name="allow_registration"
                           value="1"
                           class="form-check-input"
                           @checked(old('allow_registration', $settings->allow_registration))>
                    <span class="form-check-label">Allow new business registrations</span>
                </label>
            </div>

            <div class="mb-3">
                <label class="form-label">Payment Instructions</label>
                <textarea name="payment_instructions"
                          rows="5"
                          class="form-control @error('payment_instructions') is-invalid @enderror">{{ old('payment_instructions', $settings->payment_instructions) }}</textarea>
                @error('payment_instructions')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary">Save Settings</button>
        </div>
    </div>
</form>
@endsection
