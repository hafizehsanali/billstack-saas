@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="mb-0">Purchases</h2>
            <small class="text-muted">
                Manage supplier purchases
            </small>
        </div>

        <a href="{{ route('purchases.create') }}"
           class="btn btn-primary">

            Add Purchase
        </a>
    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                    <tr>
                        <th>#</th>
                        <th>Purchase No</th>
                        <th>Supplier</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>

                    </thead>

                    <tbody>

                    @forelse($purchases as $purchase)

                        <tr>

                            <td>{{ $loop->iteration }}</td>

                            <td>
                                {{ $purchase->purchase_no }}
                            </td>

                            <td>
                                {{ $purchase->supplier->name }}
                            </td>

                            <td>
                                Rs {{ number_format($purchase->total, 2) }}
                            </td>

                            <td>
                                Rs {{ number_format($purchase->paid_amount, 2) }}
                            </td>

                            <td class="fw-bold text-danger">
                                Rs {{ number_format($purchase->remaining_amount, 2) }}
                            </td>

                            <td>

                                @if($purchase->status === 'paid')

                                    <span class="badge bg-success">
                                        Paid
                                    </span>

                                @elseif($purchase->status === 'partial')

                                    <span class="badge bg-warning text-dark">
                                        Partial
                                    </span>

                                @else

                                    <span class="badge bg-danger">
                                        Unpaid
                                    </span>

                                @endif

                            </td>

                            <td>
                                {{ $purchase->purchase_date->format('d M Y') }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8" class="text-center py-4">
                                No purchases found.
                            </td>
                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <div class="mt-3">

        {{ $purchases->links() }}

    </div>

</div>

@endsection