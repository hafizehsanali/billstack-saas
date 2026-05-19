@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">
        <h3 class="card-title">
            Create Customer
        </h3>
    </div>

    <div class="card-body">

        <form method="POST"
              action="{{ route('customers.store') }}">

            @csrf

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Customer Name
                    </label>

                    <input type="text"
                           name="name"
                           class="form-control">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Phone
                    </label>

                    <input type="text"
                           name="phone"
                           class="form-control">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Email
                    </label>

                    <input type="email"
                           name="email"
                           class="form-control">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Opening Balance
                    </label>

                    <input type="number"
                           step="0.01"
                           name="opening_balance"
                           value="0"
                           class="form-control">

                </div>

                <div class="col-md-12 mb-3">

                    <label class="form-label">
                        Address
                    </label>

                    <textarea name="address"
                              class="form-control"></textarea>

                </div>

            </div>

            <button class="btn btn-primary">
                Save Customer
            </button>

        </form>

    </div>

</div>

@endsection