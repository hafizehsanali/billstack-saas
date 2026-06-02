@extends('layouts.app')

@section('content')
<div class="container">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Add Supplier Payment</h3>

        <a href="{{ route('supplier.account', $supplier) }}" class="btn btn-secondary">
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
                        <option value="">Auto allocate to oldest purchases</option>
                        @foreach($purchases as $purchase)
                            <option value="{{ $purchase->id }}"
                                    @selected((string) old('purchase_id', $selectedPurchase?->id) === (string) $purchase->id)>
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
                    <input type="number"
                           step="0.01"
                           name="amount"
                           value="{{ old('amount') }}"
                           class="form-control"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment Method</label>

                    <select name="payment_method" class="form-select">
                        <option value="">Select Method</option>
                        <option value="cash" @selected(old('payment_method') === 'cash')>Cash</option>
                        <option value="bank" @selected(old('payment_method') === 'bank')>Bank Transfer</option>
                        <option value="jazzcash" @selected(old('payment_method') === 'jazzcash')>JazzCash</option>
                        <option value="easypaisa" @selected(old('payment_method') === 'easypaisa')>EasyPaisa</option>
                        <option value="cheque" @selected(old('payment_method') === 'cheque')>Cheque</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reference No</label>
                    <input type="text"
                           name="reference_no"
                           value="{{ old('reference_no') }}"
                           class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea name="notes"
                              rows="3"
                              class="form-control">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    Save Payment
                </button>

            </form>

        </div>
    </div>

</div>
@endsection
