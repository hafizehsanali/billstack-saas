@extends('layouts.app')

@section('content')

<div class="container mt-4">
    <style>
@media print {
    aside,
    .navbar,
    .sidebar,
    nav {
        display: none !important;
    }

    body {
        margin: 0;
        padding: 0;
        background: white;
    }

    .page-wrapper {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>

    <div class="card">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="mb-1">
                        Purchase #{{ $purchase->purchase_no }}
                    </h2>

                    <p class="text-muted mb-2">
                        Purchase Date:
                        {{ \Carbon\Carbon::parse($purchase->purchase_date)?->format('d M Y') }}
                    </p>

                    <h5>
                        <span class="badge
                            @if($purchase->status == 'paid') bg-success
                            @elseif($purchase->status == 'partial') bg-info
                            @elseif($purchase->status == 'cancelled') bg-danger
                            @elseif($purchase->status == 'returned') bg-secondary
                            @else bg-warning
                            @endif">
                            {{ ucfirst($purchase->status) }}
                        </span>
                    </h5>
                </div>

                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    @if($purchase->canBeEdited())
                        <a href="{{ route('purchases.edit', $purchase) }}"
                           class="btn btn-outline-secondary">
                            Edit
                        </a>
                    @endif

                    <a href="{{ route('supplier.account', $purchase->supplier_id) }}"
                       class="btn btn-dark">
                        Supplier Ledger
                    </a>

                    <button onclick="window.print()"
                            class="btn btn-primary">
                        Print
                    </button>

                    @if($purchase->status !== 'cancelled')
                        <a href="{{ route('purchases.pdf', $purchase) }}"
                           class="btn btn-outline-primary">
                            PDF
                        </a>
                    @endif

                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('purchases.index') }}"
                       class="btn btn-secondary">
                        Back
                    </a>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-6">
                    <h5>Supplier:</h5>
                    <p>
                        {{ $purchase->supplier->name }} <br>
                        {{ $purchase->supplier->phone }}
                    </p>
                </div>
            </div>

            <hr>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Returned</th>
                        <th>Returnable</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($purchase->items as $item)
                        <tr>
                            <td>{{ $item->variant?->display_name ?? $item->product?->name ?? 'Deleted product' }}</td>
                            <td>{{ number_format($item->purchase_price, 2) }}</td>
                            <td>{{ $item->quantity }} {{ $item->unit?->symbol }}</td>
                            <td>{{ $item->returnedQuantity() }}</td>
                            <td>{{ $item->returnableQuantity() }}</td>
                            <td>{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @php
                $paid = $purchase->payments->sum('amount');
                $returnedAmount = $purchase->returns->sum('total_amount');
                $remaining = max($purchase->total - $paid, 0);
                $supplierCreditDue = max($paid - $purchase->total, 0);
            @endphp

            <div class="row text-center mb-3">
                <div class="col-md-3">
                    <div class="border rounded p-3">
                        <small class="text-muted">Subtotal</small>
                        <h5 class="mt-2">
                            Rs {{ number_format($purchase->subtotal, 2) }}
                        </h5>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="border rounded p-3">
                        <small class="text-muted">Purchase Total</small>
                        <h5 class="mt-2">
                            Rs {{ number_format($purchase->total, 2) }}
                        </h5>
                    </div>
                </div>

                @if($returnedAmount > 0)
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <small class="text-muted">Items Returned to Supplier</small>
                            <h5 class="mt-2 text-warning">
                                Rs {{ number_format($returnedAmount, 2) }}
                            </h5>
                        </div>
                    </div>
                @endif

                @if($purchase->status !== 'cancelled')
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <small class="text-muted">Already Paid to Supplier</small>
                            <h5 class="mt-2 text-success">
                                Rs {{ number_format($paid, 2) }}
                            </h5>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <small class="text-muted">Still Payable to Supplier</small>
                            <h5 class="mt-2 text-danger">
                                Rs {{ number_format($remaining, 2) }}
                            </h5>
                        </div>
                    </div>

                    @if($supplierCreditDue > 0)
                        <div class="col-md-3">
                            <div class="border rounded p-3">
                                <small class="text-muted">Supplier Credit Due</small>
                                <h5 class="mt-2 text-primary">
                                    Rs {{ number_format($supplierCreditDue, 2) }}
                                </h5>
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            @if($supplierCreditDue > 0)
                <div class="alert alert-primary">
                    Supplier has Rs {{ number_format($supplierCreditDue, 2) }} available as credit or refund due after returns.
                </div>
            @endif

            <hr>

            @if($purchase->status === 'cancelled')
                <div class="alert alert-danger">
                    This purchase has been cancelled. No further supplier payments can be recorded.
                </div>
            @endif

            @if(in_array($purchase->status, ['unpaid', 'partial']))
                <h4>Record Supplier Payment</h4>

                <div class="alert alert-info">
                    <strong>Paid to supplier:</strong>
                    {{ number_format($paid, 2) }}
                    <br>
                    <strong>Still payable to supplier:</strong>
                    {{ number_format($remaining, 2) }}
                </div>

                <form action="{{ route('supplier-payments.store') }}" method="POST">
                    @csrf

                    <input type="hidden" name="supplier_id" value="{{ $purchase->supplier_id }}">
                    <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">
                    <input type="hidden" name="source" value="purchase_detail">

                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label mb-1">Amount</label>
                            <input type="number"
                                   step="0.01"
                                   name="amount"
                                   value="{{ old('amount') }}"
                                   class="form-control"
                                   placeholder="0.00"
                                   required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label mb-1">Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash" @selected(old('payment_method') === 'cash')>Cash</option>
                                <option value="bank" @selected(old('payment_method') === 'bank')>Bank</option>
                                <option value="card" @selected(old('payment_method') === 'card')>Card</option>
                                <option value="jazzcash" @selected(old('payment_method') === 'jazzcash')>JazzCash</option>
                                <option value="easypaisa" @selected(old('payment_method') === 'easypaisa')>EasyPaisa</option>
                                <option value="cheque" @selected(old('payment_method') === 'cheque')>Cheque</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label mb-1">Payment Date</label>
                            <input type="datetime-local"
                                   name="payment_date"
                                   value="{{ old('payment_date', now()->format('Y-m-d\TH:i')) }}"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label mb-1">Reference No</label>
                            <input type="text"
                                   name="reference_no"
                                   value="{{ old('reference_no') }}"
                                   class="form-control"
                                   placeholder="Cheque / Txn / Ref">
                        </div>

                        <div class="col-md-6 mt-2">
                            <label class="form-label mb-1">Note</label>
                            <input type="text"
                                   name="notes"
                                   value="{{ old('notes') }}"
                                   class="form-control"
                                   placeholder="Optional note">
                        </div>

                        <div class="col-md-6 mt-2">
                            <button type="submit" class="btn btn-success w-100">
                                Save Payment
                            </button>
                        </div>
                    </div>
                </form>
            @endif

            <hr>

            @if(! in_array($purchase->status, ['cancelled', 'returned']))
                <h4>Supplier Return</h4>

                <form method="POST" action="{{ route('purchase-returns.store', $purchase) }}">
                    @csrf

                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-3">
                            <label class="form-label mb-1">Return Date</label>
                            <input type="date"
                                   name="return_date"
                                   value="{{ old('return_date', now()->format('Y-m-d')) }}"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-md-9">
                            <label class="form-label mb-1">Return Notes</label>
                            <input type="text"
                                   name="notes"
                                   value="{{ old('notes') }}"
                                   class="form-control"
                                   placeholder="Reason or reference">
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Purchased</th>
                                    <th class="text-end">Already Returned</th>
                                    <th class="text-end">Returnable</th>
                                    <th width="180">Return Qty</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($purchase->items as $item)
                                    @php
                                        $returnableQuantity = $item->returnableQuantity();
                                    @endphp

                                    <tr>
                                        <td>{{ $item->variant?->display_name ?? $item->product?->name ?? 'Deleted product' }}</td>
                                        <td class="text-end">{{ $item->quantity }} {{ $item->unit?->symbol }}</td>
                                        <td class="text-end">{{ $item->returnedQuantity() }}</td>
                                        <td class="text-end">{{ $returnableQuantity }}</td>
                                        <td>
                                            <input type="number"
                                                   name="items[{{ $item->id }}][quantity]"
                                                   min="0"
                                                   max="{{ $returnableQuantity }}"
                                                   value="{{ old('items.'.$item->id.'.quantity', 0) }}"
                                                   class="form-control"
                                                   {{ $returnableQuantity === 0 ? 'disabled' : '' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button class="btn btn-warning">
                        Record Supplier Return
                    </button>
                </form>

                <hr>
            @endif

            <h4 class="mt-4">Supplier Return History</h4>

            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Return No</th>
                        <th>Items</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($purchase->returns as $purchaseReturn)
                        <tr>
                            <td>{{ $purchaseReturn->return_date?->format('d M Y') }}</td>
                            <td>{{ $purchaseReturn->return_no }}</td>
                            <td>
                                @foreach($purchaseReturn->items as $returnItem)
                                    <div>
                                        {{ $returnItem->product?->name ?? 'Deleted product' }}
                                        x {{ $returnItem->quantity }}
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-end text-danger fw-bold">
                                Rs {{ number_format($purchaseReturn->total_amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                No supplier returns recorded
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <hr>

            <h4 class="mt-4">Payment History</h4>

            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($purchase->payments as $payment)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($payment->payment_date)?->format('d M Y') }}</td>
                            <td>{{ ucfirst($payment->payment_method) }}</td>
                            <td>{{ $payment->reference_no ?? '-' }}</td>
                            <td class="text-end text-success fw-bold">
                                Rs {{ number_format($payment->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                No payments recorded
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="d-flex gap-2">
                @if($purchase->canBeCancelled())
                    <form method="POST" action="{{ route('purchases.cancel', $purchase) }}">
                        @csrf

                        <button class="btn btn-danger">
                            Cancel Purchase
                        </button>
                    </form>
                @endif

                @if($purchase->status !== 'cancelled')
                    <a href="{{ route('purchases.pdf', $purchase) }}"
                       class="btn btn-primary">
                        Download PDF
                    </a>
                @endif
            </div>

        </div>
    </div>
</div>

@endsection
