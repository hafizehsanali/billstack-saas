@extends('layouts.app')

@section('content')
<div class="container">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Supplier Payments</h3>

        <a href="{{ route('supplier-payments.create', $supplier->id) }}" class="btn btn-primary">
            Add Payment
        </a>
    </div>

    {{-- Supplier Info --}}
    <div class="card mb-3">
        <div class="card-body">
            <h5>{{ $supplier->name }}</h5>
            <small>{{ $supplier->phone }}</small>
        </div>
    </div>

    {{-- Payments Table --}}
    <div class="card">
        <div class="card-body table-responsive">

            <table class="table table-bordered align-middle">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Notes</th>
                        <th width="100">Action</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($payments as $payment)

                        <tr>
                            <td>{{ $payment->created_at->format('Y-m-d') }}</td>

                            <td>
                                Rs {{ number_format($payment->amount, 2) }}
                            </td>

                            <td>{{ ucfirst($payment->payment_method) }}</td>

                            <td>{{ $payment->reference_no }}</td>

                            <td>{{ $payment->notes }}</td>

                            <td>

                                <form action="{{ route('supplier-payments.destroy', $payment->id) }}"
                                      method="POST">

                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete payment?')">

                                        Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="text-center">
                                No payments found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>
    </div>

</div>
@endsection