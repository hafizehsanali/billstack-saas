@extends('layouts.app')

@section('content')

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Business Settings
                </h3>
            </div>

            <div class="card-body">
                <form method="POST"
                      action="{{ route('settings.business.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Business Name</label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $tenant->name) }}"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email"
                                   name="email"
                                   value="{{ old('email', $tenant->email) }}"
                                   class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text"
                                   name="phone"
                                   value="{{ old('phone', $tenant->phone) }}"
                                   class="form-control">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address"
                                      rows="3"
                                      class="form-control">{{ old('address', $tenant->address) }}</textarea>
                        </div>
                    </div>

                    <button class="btn btn-primary">
                        Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Invoice Header Preview
                </h3>
            </div>

            <div class="card-body">
                <h2 class="mb-2">{{ $tenant->name }}</h2>

                <div class="text-muted">
                    {{ $tenant->email ?? 'Email not set' }}<br>
                    {{ $tenant->phone ?? 'Phone not set' }}<br>
                    {{ $tenant->address ?? 'Address not set' }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
