@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card">
                <div class="card-header">
                    <h4>Edit Supplier</h4>
                </div>

                <div class="card-body">

                    <form method="POST" action="{{ route('suppliers.update', $supplier->id) }}">
                        @csrf
                        @method('PUT')

                        {{-- Name --}}
                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $supplier->name) }}"
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email"
                                   name="email"
                                   value="{{ old('email', $supplier->email) }}"
                                   class="form-control">
                        </div>

                        {{-- Phone --}}
                        <div class="mb-3">
                            <label>Phone</label>
                            <input type="text"
                                   name="phone"
                                   value="{{ old('phone', $supplier->phone) }}"
                                   class="form-control">
                        </div>

                        {{-- Address --}}
                        <div class="mb-3">
                            <label>Address</label>
                            <textarea name="address" class="form-control">{{ old('address', $supplier->address) }}</textarea>
                        </div>

                        {{-- Opening Balance --}}
                        <div class="mb-3">
                            <label>Opening Balance</label>
                            <input type="number"
                                   step="0.01"
                                   name="opening_balance"
                                   value="{{ old('opening_balance', $supplier->opening_balance) }}"
                                   class="form-control">
                        </div>

                        {{-- Buttons --}}
                        <button class="btn btn-primary">Update Supplier</button>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Back</a>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection