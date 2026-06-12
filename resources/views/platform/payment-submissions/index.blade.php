@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">Payment Reviews</h1>
        <div class="text-muted">Verify full subscription payments submitted by business owners.</div>
    </div>
    <span class="badge {{ $pendingCount > 0 ? 'bg-warning text-dark' : 'bg-success text-white' }}">
        {{ $pendingCount }} pending
    </span>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Invoice</th>
                    <th>Payment Reference</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $submission)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $submission->tenant?->name ?? '-' }}</div>
                            <div class="text-muted small">{{ $submission->submitter?->email }}</div>
                        </td>
                        <td>
                            <a href="{{ route('platform.billing.show', $submission->invoice) }}"
                               class="fw-semibold">
                                {{ $submission->invoice?->invoice_no }}
                            </a>
                            <div class="text-muted small">
                                Full amount: Rs {{ number_format($submission->invoice?->total_cents / 100, 2) }}
                            </div>
                        </td>
                        <td>
                            <div>{{ str($submission->payment_method)->replace('_', ' ')->title() }}</div>
                            <div class="fw-semibold">{{ $submission->reference_no }}</div>
                            <div class="text-muted small">{{ $submission->paid_on?->format('M d, Y') }}</div>
                        </td>
                        <td>
                            <span class="badge {{ match($submission->status) {
                                'approved' => 'bg-success text-white',
                                'rejected' => 'bg-danger text-white',
                                default => 'bg-warning text-dark',
                            } }}">
                                {{ str($submission->status)->title() }}
                            </span>
                            @if($submission->rejection_reason)
                                <div class="text-danger small mt-1">{{ $submission->rejection_reason }}</div>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($submission->status === 'pending')
                                <div class="d-flex justify-content-end gap-2">
                                    <form method="POST"
                                          action="{{ route('platform.payment-submissions.approve', $submission) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success">Approve Full Payment</button>
                                    </form>
                                    <form method="POST"
                                          action="{{ route('platform.payment-submissions.reject', $submission) }}"
                                          class="d-flex gap-2">
                                        @csrf
                                        <input type="text"
                                               name="rejection_reason"
                                               class="form-control form-control-sm"
                                               placeholder="Rejection reason"
                                               required>
                                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-muted small">
                                    Reviewed {{ $submission->reviewed_at?->format('M d, Y') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No subscription payment submissions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $submissions->links() }}</div>
@endsection
