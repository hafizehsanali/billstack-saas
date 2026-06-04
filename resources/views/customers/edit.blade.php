@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">
        <h3 class="card-title">
            Edit Customer
        </h3>

        <a href="{{ route('customers.index') }}"
           class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>

    <div class="card-body">

        <form method="POST"
              action="{{ route('customers.update', $customer) }}">

            @csrf
            @method('PUT')

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Customer Name
                    </label>

                    <input type="text"
                           name="name"
                           value="{{ old('name', $customer->name) }}"
                           class="form-control"
                           required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Phone
                    </label>

                    <input type="text"
                           name="phone"
                           value="{{ old('phone', $customer->phone) }}"
                           class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Email
                    </label>

                    <input type="email"
                           name="email"
                           value="{{ old('email', $customer->email) }}"
                           class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Opening Balance / Previous Due
                    </label>

                    <input type="number"
                           step="0.01"
                           name="opening_balance"
                           value="{{ old('opening_balance', $customer->opening_balance) }}"
                           class="form-control">
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">
                        Address
                    </label>

                    <textarea name="address"
                              class="form-control">{{ old('address', $customer->address) }}</textarea>
                </div>

            </div>

            <button class="btn btn-primary">
                Update Customer
            </button>

        </form>

    </div>

</div>

@endsection
