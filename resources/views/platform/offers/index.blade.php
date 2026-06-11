@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">Offers</h1>
        <div class="text-muted">Manage discounts and promotional codes for subscription plans.</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">
            Platform
        </a>
        <a href="{{ route('platform.offers.create') }}" class="btn btn-primary">
            Add Offer
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Offer</th>
                    <th>Discount</th>
                    <th>Validity</th>
                    <th>Usage</th>
                    <th>Plans</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($offers as $offer)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $offer->name }}</div>
                            <code>{{ $offer->code }}</code>
                        </td>
                        <td>
                            @if($offer->discount_type === 'percent')
                                {{ $offer->discount_value }}%
                            @else
                                Rs {{ number_format($offer->discount_value / 100, 2) }}
                            @endif

                        </td>
                        <td>
                            <div>{{ $offer->starts_at?->format('M d, Y') ?? 'Immediately' }}</div>
                            <div class="text-muted small">to {{ $offer->ends_at?->format('M d, Y') ?? 'No expiry' }}</div>
                        </td>
                        <td>
                            {{ $offer->redeemed_count }} / {{ $offer->redemption_limit ?? 'Unlimited' }}
                        </td>
                        <td>{{ $offer->plans_count ?: 'All' }}</td>
                        <td>
                            <span class="badge {{ $offer->isCurrentlyAvailable() ? 'bg-success text-white' : 'bg-light text-dark border' }}">
                                {{ $offer->isCurrentlyAvailable() ? 'Available' : 'Unavailable' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('platform.offers.edit', $offer) }}"
                               class="btn btn-sm btn-outline-secondary">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No offers created yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $offers->links() }}
</div>
@endsection
