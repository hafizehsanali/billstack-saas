<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Feature Name <span class="text-danger">*</span></label>
        <input type="text"
               name="name"
               value="{{ old('name', $feature?->name) }}"
               class="form-control @error('name') is-invalid @enderror"
               required>
        @error('name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Feature Key</label>
        <input type="text"
               name="key"
               value="{{ old('key', $feature?->key) }}"
               class="form-control @error('key') is-invalid @enderror">
        @error('key')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Description</label>
        <textarea name="description"
                  rows="3"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $feature?->description) }}</textarea>
        @error('description')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-check">
            <input type="hidden" name="is_paid" value="0">
            <input type="checkbox"
                   name="is_paid"
                   value="1"
                   class="form-check-input"
                   @checked(old('is_paid', $feature?->is_paid ?? false))>
            <span class="form-check-label">This is a paid feature</span>
        </label>
    </div>
</div>
