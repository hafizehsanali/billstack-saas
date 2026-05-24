@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h3>Purchases</h3>

        <a href="{{ route('purchases.create') }}"
           class="btn btn-primary">

            Create Purchase
        </a>

    </div>

    <div class="card">

        <div class="card-body table-responsive">

            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th>Purchase No</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($purchases as $purchase)

                        <tr>

                            <td>
                                {{ $purchase->purchase_no }}
                            </td>

                            <td>
                                {{ $purchase->purchase_date->format('d M Y') }}
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

                            <td>
                                <strong class="text-danger">
                                    Rs {{ number_format($purchase->remaining_amount, 2) }}
                                </strong>
                            </td>

                            <td>

                                @if($purchase->status == 'paid')

                                    <span class="badge bg-success">
                                        Paid
                                    </span>

                                @elseif($purchase->status == 'partial')

                                    <span class="badge bg-warning">
                                        Partial
                                    </span>

                                @else

                                    <span class="badge bg-danger">
                                        Unpaid
                                    </span>

                                @endif

                            </td>
                            <td>

                                <a href="{{ route('purchases.show', $purchase) }}"
                                class="btn btn-sm btn-primary">

                                    View
                                </a>

                            </td>
                            <form action="{{ route('purchases.cancel', $purchase) }}"
                                method="POST"
                                class="d-inline">

                                @csrf

                                <button type="submit"
                                        class="btn btn-warning btn-sm"
                                        onclick="return confirm('Cancel this purchase?')">

                                    Cancel

                                </button>

                            </form>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7"
                                class="text-center">

                                No purchases found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

            {{ $purchases->links() }}

        </div>

    </div>

</div>

@endsection