@extends('layouts.app')

@section('content')

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Add Supplier</h3>

        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">
            Back
        </a>
    </div>

    <div class="card">
        <div class="card-body">

            <form action="{{ route('suppliers.store') }}" method="POST">
                @csrf

                {{-- Supplier Name --}}
                <div class="mb-3">
                    <label class="form-label">Supplier Name <span class="text-danger">*</span></label>

                    <input
                        type="text"
                        name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                        required
                    >

                    @error('name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Phone --}}
                <div class="mb-3">
                    <label class="form-label">Phone</label>

                    <input
                        type="text"
                        name="phone"
                        class="form-control @error('phone') is-invalid @enderror"
                        value="{{ old('phone') }}"
                    >

                    @error('phone')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label class="form-label">Email</label>

                    <input
                        type="email"
                        name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                    >

                    @error('email')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Opening Balance --}}
                <div class="mb-3">
                    <label class="form-label">Opening Balance</label>

                    <input
                        type="number"
                        step="0.01"
                        name="opening_balance"
                        class="form-control @error('opening_balance') is-invalid @enderror"
                        value="{{ old('opening_balance', 0) }}"
                    >

                    @error('opening_balance')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Address --}}
                <div class="mb-3">
                    <label class="form-label">Address</label>

                    <textarea
                        name="address"
                        rows="3"
                        class="form-control @error('address') is-invalid @enderror"
                    >{{ old('address') }}</textarea>

                    @error('address')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn btn-primary">
                    Save Supplier
                </button>

            </form>
        </div>
    </div>
</div>

@endsection