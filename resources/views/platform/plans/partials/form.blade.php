<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Plan Name <span class="text-danger">*</span></label>
        <input type="text"
               name="name"
               value="{{ old('name', $plan?->name) }}"
               class="form-control @error('name') is-invalid @enderror"
               required>
        @error('name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Slug</label>
        <input type="text"
               name="slug"
               value="{{ old('slug', $plan?->slug) }}"
               class="form-control @error('slug') is-invalid @enderror">
        @error('slug')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Monthly Price <span class="text-danger">*</span></label>
        <input type="number"
               step="0.01"
               min="0"
               name="monthly_price"
               value="{{ old('monthly_price', $plan ? number_format($plan->monthly_price_cents / 100, 2, '.', '') : '0.00') }}"
               class="form-control @error('monthly_price') is-invalid @enderror"
               required>
        @error('monthly_price')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Annual Price <span class="text-danger">*</span></label>
        <input type="number"
               step="0.01"
               min="0"
               name="annual_price"
               value="{{ old('annual_price', $plan ? number_format($plan->annual_price_cents / 100, 2, '.', '') : '0.00') }}"
               class="form-control @error('annual_price') is-invalid @enderror"
               required>
        @error('annual_price')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Active User Limit</label>
        <input type="number"
               min="1"
               name="user_limit"
               value="{{ old('user_limit', $plan?->user_limit) }}"
               class="form-control @error('user_limit') is-invalid @enderror">
        @error('user_limit')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Trial Days <span class="text-danger">*</span></label>
        <input type="number"
               min="0"
               max="365"
               name="trial_days"
               value="{{ old('trial_days', $plan?->trial_days ?? 0) }}"
               class="form-control @error('trial_days') is-invalid @enderror"
               required>
        <div class="text-muted small mt-1">Paid plans only. Free plans always use 0.</div>
        @error('trial_days')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Free Access Days</label>
        <input type="number"
               min="1"
               max="3650"
               name="free_access_days"
               value="{{ old('free_access_days', $plan?->free_access_days) }}"
               class="form-control @error('free_access_days') is-invalid @enderror">
        <div class="text-muted small mt-1">Free plans only. Leave blank for permanent access.</div>
        @error('free_access_days')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Product Limit</label>
        <input type="number"
               min="1"
               name="product_limit"
               value="{{ old('product_limit', $plan?->product_limit) }}"
               class="form-control @error('product_limit') is-invalid @enderror">
        <div class="text-muted small mt-1">Leave blank for unlimited products.</div>
        @error('product_limit')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Monthly Invoice Limit</label>
        <input type="number"
               min="1"
               name="monthly_invoice_limit"
               value="{{ old('monthly_invoice_limit', $plan?->monthly_invoice_limit) }}"
               class="form-control @error('monthly_invoice_limit') is-invalid @enderror">
        <div class="text-muted small mt-1">Leave blank for unlimited invoices.</div>
        @error('monthly_invoice_limit')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Description</label>
        <textarea name="description"
                  rows="3"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $plan?->description) }}</textarea>
        @error('description')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-check">
            <input type="hidden" name="is_public" value="0">
            <input type="checkbox"
                   name="is_public"
                   value="1"
                   class="form-check-input"
                   @checked(old('is_public', $plan?->is_public ?? true))>
            <span class="form-check-label">Show this plan publicly</span>
        </label>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox"
                   name="is_active"
                   value="1"
                   class="form-check-input"
                   @checked(old('is_active', $plan?->is_active ?? true))>
            <span class="form-check-label">Plan is active</span>
        </label>
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Included Features</label>
        <div class="row">
            @forelse($features as $feature)
                <div class="col-md-6 col-lg-4 mb-2">
                    <label class="form-check">
                        <input type="checkbox"
                               name="features[]"
                               value="{{ $feature->id }}"
                               class="form-check-input"
                               @checked(in_array($feature->id, old('features', $selectedFeatures), true))>
                        <span class="form-check-label">
                            {{ $feature->name }}
                            @if($feature->is_paid)
                                <span class="badge bg-primary text-white ms-1">Paid</span>
                            @endif
                        </span>
                    </label>
                </div>
            @empty
                <div class="col-12 text-muted">
                    Create features first, then attach them to this plan.
                </div>
            @endforelse
        </div>
        @error('features')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
</div>
