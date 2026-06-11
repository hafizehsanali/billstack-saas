@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-1">Create Subscription Invoice</h3>
            <div class="text-muted small">Issue a platform charge to a subscribed business.</div>
        </div>
        <a href="{{ route('platform.billing.index') }}" class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('platform.billing.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Business <span class="text-danger">*</span></label>
                    <select name="tenant_id" class="form-select @error('tenant_id') is-invalid @enderror" required>
                        <option value="">Select business</option>
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected((int) old('tenant_id') === $tenant->id)>
                                {{ $tenant->name }} - {{ $tenant->currentSubscription?->plan?->name ?? 'No plan' }}
                            </option>
                        @endforeach
                    </select>
                    @error('tenant_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Billing Period <span class="text-danger">*</span></label>
                    <input type="text"
                           name="billing_period"
                           class="form-control @error('billing_period') is-invalid @enderror"
                           value="{{ old('billing_period', now()->format('F Y')) }}"
                           required>
                    @error('billing_period')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @foreach([
                    ['name' => 'subtotal', 'label' => 'Subtotal', 'value' => '', 'required' => true],
                    ['name' => 'discount', 'label' => 'Discount', 'value' => '0.00', 'required' => false],
                    ['name' => 'tax', 'label' => 'Tax', 'value' => '0.00', 'required' => false],
                ] as $field)
                    <div class="col-md-4">
                        <label class="form-label">
                            {{ $field['label'] }}
                            @if($field['required']) <span class="text-danger">*</span> @endif
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Rs</span>
                            <input type="number"
                                   name="{{ $field['name'] }}"
                                   class="form-control @error($field['name']) is-invalid @enderror"
                                   value="{{ old($field['name'], $field['value']) }}"
                                   min="{{ $field['required'] ? '0.01' : '0' }}"
                                   step="0.01"
                                   @required($field['required'])>
                            @error($field['name'])
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @endforeach

                <div class="col-md-6">
                    <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                    <input type="date"
                           name="issued_on"
                           class="form-control @error('issued_on') is-invalid @enderror"
                           value="{{ old('issued_on', now()->toDateString()) }}"
                           required>
                    @error('issued_on')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Due Date</label>
                    <input type="date"
                           name="due_on"
                           class="form-control @error('due_on') is-invalid @enderror"
                           value="{{ old('due_on', now()->addDays(10)->toDateString()) }}">
                    @error('due_on')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes"
                              rows="3"
                              class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <button class="btn btn-primary mt-3">Create Invoice</button>
        </form>
    </div>
</div>
@endsection
