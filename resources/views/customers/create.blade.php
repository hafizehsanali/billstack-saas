@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Create Customer</h3>

        <a href="{{ route('customers.index') }}"
           class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('customers.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror"
                           required>
                    @error('name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text"
                           name="phone"
                           value="{{ old('phone') }}"
                           class="form-control @error('phone') is-invalid @enderror">
                    @error('phone')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror">
                    @error('email')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Opening Balance / Previous Due</label>
                    <input type="number"
                           step="0.01"
                           name="opening_balance"
                           value="{{ old('opening_balance', 0) }}"
                           class="form-control @error('opening_balance') is-invalid @enderror">
                    @error('opening_balance')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address"
                              rows="3"
                              class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                    @error('address')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <button class="btn btn-primary">
                Save Customer
            </button>
        </form>
    </div>
</div>

@endsection
