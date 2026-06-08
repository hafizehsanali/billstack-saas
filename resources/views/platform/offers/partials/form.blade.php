<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Offer Name <span class="text-danger">*</span></label>
        <input type="text"
               name="name"
               value="{{ old('name', $offer?->name) }}"
               class="form-control @error('name') is-invalid @enderror"
               required>
        @error('name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Promotion Code <span class="text-danger">*</span></label>
        <input type="text"
               name="code"
               value="{{ old('code', $offer?->code) }}"
               class="form-control text-uppercase @error('code') is-invalid @enderror"
               required>
        @error('code')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Discount Type <span class="text-danger">*</span></label>
        <select name="discount_type"
                class="form-select @error('discount_type') is-invalid @enderror"
                required>
            <option value="percent" @selected(old('discount_type', $offer?->discount_type ?? 'percent') === 'percent')>
                Percentage
            </option>
            <option value="fixed" @selected(old('discount_type', $offer?->discount_type) === 'fixed')>
                Fixed Amount
            </option>
        </select>
        @error('discount_type')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Discount Value <span class="text-danger">*</span></label>
        <input type="number"
               name="discount_value"
               step="0.01"
               min="0"
               value="{{ old('discount_value', $offer ? ($offer->discount_type === 'fixed' ? number_format($offer->discount_value / 100, 2, '.', '') : $offer->discount_value) : 0) }}"
               class="form-control @error('discount_value') is-invalid @enderror"
               required>
        @error('discount_value')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Extra Trial Days</label>
        <input type="number"
               name="trial_days"
               min="0"
               value="{{ old('trial_days', $offer?->trial_days ?? 0) }}"
               class="form-control @error('trial_days') is-invalid @enderror">
        @error('trial_days')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Redemption Limit</label>
        <input type="number"
               name="redemption_limit"
               min="1"
               value="{{ old('redemption_limit', $offer?->redemption_limit) }}"
               class="form-control @error('redemption_limit') is-invalid @enderror">
        @error('redemption_limit')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-check mt-4">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox"
                   name="is_active"
                   value="1"
                   class="form-check-input"
                   @checked(old('is_active', $offer?->is_active ?? true))>
            <span class="form-check-label">Offer is active</span>
        </label>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Starts At</label>
        <input type="datetime-local"
               name="starts_at"
               value="{{ old('starts_at', $offer?->starts_at?->format('Y-m-d\TH:i')) }}"
               class="form-control @error('starts_at') is-invalid @enderror">
        @error('starts_at')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Ends At</label>
        <input type="datetime-local"
               name="ends_at"
               value="{{ old('ends_at', $offer?->ends_at?->format('Y-m-d\TH:i')) }}"
               class="form-control @error('ends_at') is-invalid @enderror">
        @error('ends_at')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Description</label>
        <textarea name="description"
                  rows="3"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $offer?->description) }}</textarea>
        @error('description')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Eligible Plans</label>
        <div class="row">
            @forelse($plans as $plan)
                <div class="col-md-6 col-lg-4 mb-2">
                    <label class="form-check">
                        <input type="checkbox"
                               name="plans[]"
                               value="{{ $plan->id }}"
                               class="form-check-input"
                               @checked(in_array($plan->id, old('plans', $selectedPlans), true))>
                        <span class="form-check-label">{{ $plan->name }}</span>
                    </label>
                </div>
            @empty
                <div class="col-12 text-muted">Create an active plan before targeting an offer.</div>
            @endforelse
        </div>
        <div class="text-muted small mt-1">Leave all plans unchecked to make the offer available to every plan.</div>
    </div>
</div>
