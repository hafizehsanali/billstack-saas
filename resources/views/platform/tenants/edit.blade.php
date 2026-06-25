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
                    <label class="form-label">Business Type</label>
                    <select name="business_preset_id"
                            class="form-select @error('business_preset_id') is-invalid @enderror">
                        <option value="">No preset selected</option>
                        @foreach($businessPresets as $preset)
                            <option value="{{ $preset->id }}"
                                    @selected((int) old('business_preset_id', $tenant->business_preset_id) === $preset->id)>
                                {{ $preset->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('business_preset_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                    <div class="form-text">The preset controls the default modules and product workflow shown to this business.</div>
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

            @php
                $presetModuleIds = $tenant->businessPreset?->modules->pluck('id')->all() ?? [];
                $overrideMap = $tenant->businessModuleOverrides->keyBy('business_module_id');
            @endphp

            <div class="border rounded p-3 mb-3">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <h4 class="h6 mb-1">Business Modules</h4>
                        <div class="text-muted small">Checked modules will be available for this tenant. Preset modules are selected by default and can be adjusted per customer.</div>
                    </div>
                </div>

                <div class="row">
                    @foreach($businessModules->groupBy('category') as $category => $modules)
                        <div class="col-lg-6 mb-3">
                            <div class="text-uppercase text-muted small fw-semibold mb-2">{{ str($category)->headline() }}</div>

                            @foreach($modules as $module)
                                @php
                                    $override = $overrideMap->get($module->id);
                                    $isEnabled = old(
                                        'enabled_module_ids',
                                        null
                                    ) !== null
                                        ? in_array($module->id, array_map('intval', old('enabled_module_ids', [])), true)
                                        : ($override ? $override->is_enabled : in_array($module->id, $presetModuleIds, true));
                                @endphp

                                <label class="d-flex align-items-start gap-2 border rounded p-2 mb-2">
                                    <input type="checkbox"
                                           name="enabled_module_ids[]"
                                           value="{{ $module->id }}"
                                           class="form-check-input mt-1"
                                           @checked($isEnabled)>
                                    <span>
                                        <span class="fw-semibold">{{ $module->name }}</span>
                                        @if($module->is_core)
                                            <span class="badge bg-light text-dark border ms-1">Core</span>
                                        @endif
                                        <span class="d-block text-muted small">{{ $module->description }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <button class="btn btn-primary">
                Update Subscription
            </button>
        </form>
    </div>
</div>
@endsection
