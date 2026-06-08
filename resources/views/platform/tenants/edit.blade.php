@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-0">Tenant Subscription</h3>
            <div class="text-muted small">{{ $tenant->name }}</div>
        </div>

        <a href="{{ route('platform.tenants.index') }}" class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('platform.tenants.update', $tenant) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Plan <span class="text-danger">*</span></label>
                    <select name="subscription_plan_id"
                            class="form-select @error('subscription_plan_id') is-invalid @enderror"
                            required>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}"
                                    @selected((int) old('subscription_plan_id', $tenant->currentSubscription?->subscription_plan_id) === (int) $plan->id)>
                                {{ $plan->name }} - Rs {{ number_format($plan->monthly_price_cents / 100, 2) }}/month
                            </option>
                        @endforeach
                    </select>
                    @error('subscription_plan_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status"
                            class="form-select @error('status') is-invalid @enderror"
                            required>
                        @foreach(['active' => 'Active', 'paused' => 'Paused', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}"
                                    @selected(old('status', $tenant->currentSubscription?->status ?? 'active') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Trial Ends At</label>
                    <input type="date"
                           name="trial_ends_at"
                           value="{{ old('trial_ends_at', $tenant->currentSubscription?->trial_ends_at?->format('Y-m-d')) }}"
                           class="form-control @error('trial_ends_at') is-invalid @enderror">
                    @error('trial_ends_at')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Ends At</label>
                    <input type="date"
                           name="ends_at"
                           value="{{ old('ends_at', $tenant->currentSubscription?->ends_at?->format('Y-m-d')) }}"
                           class="form-control @error('ends_at') is-invalid @enderror">
                    @error('ends_at')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <button class="btn btn-primary">
                Update Subscription
            </button>
        </form>
    </div>
</div>
@endsection
