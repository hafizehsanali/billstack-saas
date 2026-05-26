@extends('layouts.app')

@section('content')
<div class="container">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Add Supplier Payment</h3>

        <a href="{{ route('suppliers.show', $supplier->id) }}" class="btn btn-secondary">
            Back
        </a>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Payment Form --}}
    <div class="card">
        <div class="card-body">

            <form action="{{ route('supplier-payments.store') }}" method="POST">
                @csrf

                <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">

                <div class="mb-3">
                    <label class="form-label">Supplier</label>
                    <input type="text" class="form-control" value="{{ $supplier->name }}" disabled>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Purchase Invoice</label>
                    <select name="purchase_id" class="form-select">
                        <option value="">Select Purchase</option>
                        @foreach($purchases as $purchase)
                            <option value="{{ $purchase->id }}" {{ isset($selectedPurchase) && $selectedPurchase->id == $purchase->id ? 'selected' : '' }}>
                               {{ $purchase->purchase_no }} - Remaining: Rs {{ number_format($purchase->remaining_amount,2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Date</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment Method</label>

                    <select name="payment_method" class="form-select">
                        <option value="">Select Method</option>
                        <option value="cash">Cash</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="jazzcash">JazzCash</option>
                        <option value="easypaisa">EasyPaisa</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reference No</label>
                    <input type="text" name="reference_no" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea name="notes" rows="3" class="form-control"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    Save Payment
                </button>

            </form>

        </div>
    </div>

</div>
@endsection