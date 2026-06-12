@extends('layouts.app')

@section('content')
@php
    $content = match($state) {
        'approved' => [
            'icon' => 'circle-check-big',
            'class' => 'success',
            'title' => 'Subscription activated',
            'message' => 'Your full payment is confirmed and your package is ready to use.',
        ],
        'pending' => [
            'icon' => 'clock-3',
            'class' => 'warning',
            'title' => 'Payment review in progress',
            'message' => 'Your payment reference has been received. Access will start after platform approval.',
        ],
        'rejected' => [
            'icon' => 'circle-x',
            'class' => 'danger',
            'title' => 'Payment needs correction',
            'message' => 'The submitted payment could not be verified. Review the reason and submit corrected details.',
        ],
        'cancelled' => [
            'icon' => 'ban',
            'class' => 'secondary',
            'title' => 'Checkout cancelled',
            'message' => 'No payment was recorded. You can start a new checkout with another cycle or promotion.',
        ],
        'payment_required' => [
            'icon' => 'credit-card',
            'class' => 'secondary',
            'title' => 'Payment details required',
            'message' => 'Your invoice is ready. Complete the full payment and submit its transaction reference.',
        ],
        default => [
            'icon' => 'calendar-x-2',
            'class' => 'warning',
            'title' => 'Subscription access ended',
            'message' => 'Choose an available package to restore access to the business workspace.',
        ],
    };
@endphp

<div class="subscription-outcome">
    <div class="subscription-outcome-icon is-{{ $content['class'] }}">
        <i data-lucide="{{ $content['icon'] }}"></i>
    </div>
    <div class="text-uppercase text-secondary fw-semibold small">{{ platform_name() }} subscription</div>
    <h1>{{ $content['title'] }}</h1>
    <p>{{ $content['message'] }}</p>

    @if($invoice)
        <div class="subscription-outcome-summary">
            <div>
                <span>Invoice</span>
                <strong>{{ $invoice->invoice_no }}</strong>
            </div>
            <div>
                <span>Package</span>
                <strong>{{ $invoice->subscription?->plan?->name ?? '-' }}</strong>
            </div>
            <div>
                <span>Total</span>
                <strong>Rs {{ number_format($invoice->total_cents / 100, 2) }}</strong>
            </div>
            <div>
                <span>Status</span>
                <strong>{{ str($state)->title() }}</strong>
            </div>
        </div>
    @endif

    @if($state === 'rejected' && $submission?->rejection_reason)
        <div class="alert alert-danger text-start">
            <div class="fw-semibold">Review note</div>
            <div>{{ $submission->rejection_reason }}</div>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-center gap-2">
        @if($state === 'approved')
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                Open Dashboard
                <i data-lucide="arrow-right"></i>
            </a>
        @elseif(in_array($state, ['rejected', 'cancelled', 'expired', 'payment_required'], true))
            <a href="{{ $subscription?->plan?->monthly_price_cents > 0 ? route('subscription.checkout') : route('plans.index') }}"
               class="btn btn-primary">
                {{ match($state) {
                    'rejected' => 'Correct Payment Details',
                    'payment_required' => 'Continue to Payment',
                    default => 'Choose Package',
                } }}
            </a>
        @else
            <a href="{{ route('subscription.outcome') }}" class="btn btn-outline-secondary">
                Refresh Status
            </a>
        @endif
        <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary">Compare Packages</a>
    </div>
</div>
@endsection
