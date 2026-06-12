@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">Purchase Subscription</h1>
        <div class="text-muted">Activate {{ $subscription->plan->name }} for {{ $tenant->name }}.</div>
    </div>
    <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary">Compare Packages</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Package Summary</h2>
            </div>
            <div class="card-body">
                <div class="h2 mb-1">{{ $subscription->plan->name }}</div>
                <div class="text-muted mb-4">{{ $subscription->plan->description }}</div>

                <dl class="row mb-0">
                    <dt class="col-6">Monthly Price</dt>
                    <dd class="col-6 text-end fw-bold">
                        Rs {{ number_format($subscription->plan->monthly_price_cents / 100, 2) }}
                    </dd>

                    <dt class="col-6">Users</dt>
                    <dd class="col-6 text-end">{{ $subscription->plan->user_limit ?? 'Unlimited' }}</dd>

                    <dt class="col-6">Annual Price</dt>
                    <dd class="col-6 text-end fw-bold">
                        Rs {{ number_format($subscription->plan->annual_price_cents / 100, 2) }}
                    </dd>

                    <dt class="col-6">Payment</dt>
                    <dd class="col-6 text-end">Full payment</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Payment Status</h2>
            </div>
            <div class="card-body">
                @if($invoice)
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <div class="text-muted">Invoice</div>
                            <div class="fw-bold">{{ $invoice->invoice_no }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Billing Cycle</div>
                            <div>{{ str($invoice->billing_cycle)->title() }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Issued</div>
                            <div>{{ $invoice->issued_on?->format('M d, Y') }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Due</div>
                            <div>{{ $invoice->due_on?->format('M d, Y') }}</div>
                        </div>
                    </div>

                    <div class="border rounded p-3 mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Package Price</span>
                            <span>Rs {{ number_format($invoice->subtotal_cents / 100, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">
                                Coupon Discount
                                @if($invoice->offer_code)
                                    <span class="badge bg-warning text-dark ms-1">{{ $invoice->offer_code }}</span>
                                @endif
                            </span>
                            <span class="{{ $invoice->discount_cents > 0 ? 'text-success' : 'text-muted' }}">
                                - Rs {{ number_format($invoice->discount_cents / 100, 2) }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <span class="fw-semibold">Total Payable After Coupon</span>
                            <span class="h2 mb-0 text-danger">
                                Rs {{ number_format($invoice->total_cents / 100, 2) }}
                            </span>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        <div class="fw-semibold mb-1">Full payment instructions</div>
                        <div>
                            {{ $platformSettings->payment_instructions
                                ?: 'Contact platform support to complete the full subscription payment.' }}
                        </div>
                        @if($platformSettings->support_email || $platformSettings->support_phone)
                            <div class="mt-2 small">
                                Support:
                                {{ collect([
                                    $platformSettings->support_email,
                                    $platformSettings->support_phone,
                                ])->filter()->join(' | ') }}
                            </div>
                        @endif
                    </div>

                    @php
                        $paymentSubmission = $invoice->paymentSubmission;
                    @endphp

                    @if($paymentSubmission?->status === 'pending')
                        <div class="alert alert-warning mt-3 mb-0">
                            <div class="fw-semibold">Payment is waiting for review</div>
                            <div class="small mt-1">
                                Reference {{ $paymentSubmission->reference_no }} was submitted on
                                {{ $paymentSubmission->paid_on?->format('M d, Y') }}.
                            </div>
                        </div>
                    @else
                        @if($paymentSubmission?->status === 'rejected')
                            <div class="alert alert-danger mt-3">
                                <div class="fw-semibold">Previous payment submission was rejected</div>
                                <div class="small mt-1">{{ $paymentSubmission->rejection_reason }}</div>
                            </div>
                        @endif

                        <form method="POST"
                              action="{{ route('subscription.payment-submissions.store', $invoice) }}"
                              class="border rounded p-3 mt-3">
                            @csrf
                            <div class="fw-semibold mb-1">Submit Full Payment Reference</div>
                            <div class="text-muted small mb-3">
                                Submit details only after paying the complete
                                Rs {{ number_format($invoice->total_cents / 100, 2) }}.
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                    <select name="payment_method"
                                            class="form-select @error('payment_method') is-invalid @enderror"
                                            required>
                                        @foreach([
                                            'bank_transfer' => 'Bank Transfer',
                                            'card' => 'Card',
                                            'mobile_wallet' => 'Mobile Wallet',
                                            'cash_deposit' => 'Cash Deposit',
                                        ] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('payment_method') === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Transaction Reference <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="reference_no"
                                           value="{{ old('reference_no') }}"
                                           class="form-control @error('reference_no') is-invalid @enderror"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date"
                                           name="paid_on"
                                           value="{{ old('paid_on', today()->toDateString()) }}"
                                           max="{{ today()->toDateString() }}"
                                           class="form-control @error('paid_on') is-invalid @enderror"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Notes</label>
                                    <input type="text"
                                           name="notes"
                                           value="{{ old('notes') }}"
                                           class="form-control @error('notes') is-invalid @enderror">
                                </div>
                            </div>

                            <button class="btn btn-primary mt-3">Submit Payment for Review</button>
                        </form>
                    @endif
                @else
                    <p class="text-muted">
                        Create the subscription invoice to begin the purchase process.
                        An eligible promotion code will be applied before the amount is finalized.
                    </p>
                    <form method="POST" action="{{ route('subscription.checkout.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Billing Cycle <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group" aria-label="Billing cycle">
                                <input type="radio"
                                       class="btn-check"
                                       name="billing_cycle"
                                       id="billing_monthly"
                                       value="monthly"
                                       @checked(old('billing_cycle', 'monthly') === 'monthly')>
                                <label class="btn btn-outline-primary" for="billing_monthly">
                                    Monthly - Rs {{ number_format($subscription->plan->monthly_price_cents / 100, 0) }}
                                </label>

                                <input type="radio"
                                       class="btn-check"
                                       name="billing_cycle"
                                       id="billing_annual"
                                       value="annual"
                                       @checked(old('billing_cycle') === 'annual')>
                                <label class="btn btn-outline-primary" for="billing_annual">
                                    Annual - Rs {{ number_format($subscription->plan->annual_price_cents / 100, 0) }}
                                </label>
                            </div>
                            @error('billing_cycle')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="promo_code" class="form-label">Promotion Code</label>
                            <div class="input-group">
                                <input id="promo_code"
                                       type="text"
                                       name="promo_code"
                                       value="{{ old('promo_code') }}"
                                       class="form-control text-uppercase @error('promo_code') is-invalid @enderror"
                                       placeholder="Optional">
                                @error('promo_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if($offers->isNotEmpty())
                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    @foreach($offers as $offer)
                                        <span class="badge bg-light text-dark border">
                                            {{ $offer->code }}:
                                            {{ $offer->discount_type === 'percent'
                                                ? $offer->discount_value.'% off'
                                                : 'Rs '.number_format($offer->discount_value / 100, 0).' off' }}
                                            ({{ $offer->billing_cycle === 'both' ? 'monthly/annual' : $offer->billing_cycle }})
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="border rounded p-3 mb-3" id="payment-estimate">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Package Price</span>
                                <span id="estimate-subtotal">Rs 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Coupon Discount</span>
                                <span id="estimate-discount" class="text-success">- Rs 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                <span class="fw-semibold">Estimated Total Payable</span>
                                <span id="estimate-total" class="h2 mb-0 text-danger">Rs 0.00</span>
                            </div>
                            <div id="estimate-note" class="text-muted small mt-2">
                                The final amount is confirmed when the purchase invoice is created.
                            </div>
                        </div>

                        <button class="btn btn-primary">Create Purchase Invoice</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if(! $invoice)
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const planPrices = {
                monthly: {{ (int) $subscription->plan->monthly_price_cents }},
                annual: {{ (int) $subscription->plan->annual_price_cents }},
            };
            const offers = @json($offerPreviews);
            const promoInput = document.getElementById('promo_code');
            const currency = new Intl.NumberFormat('en-PK', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const formatAmount = cents => `Rs ${currency.format(cents / 100)}`;

            const updateEstimate = () => {
                const cycle = document.querySelector('input[name="billing_cycle"]:checked')?.value ?? 'monthly';
                const subtotal = planPrices[cycle] ?? 0;
                const code = promoInput.value.trim().toUpperCase();
                const offer = offers.find(item =>
                    item.code === code && (item.cycle === 'both' || item.cycle === cycle)
                );
                let discount = 0;

                if (offer) {
                    discount = offer.type === 'percent'
                        ? Math.round(subtotal * (offer.value / 100))
                        : offer.value;
                    discount = Math.min(discount, subtotal);
                }

                document.getElementById('estimate-subtotal').textContent = formatAmount(subtotal);
                document.getElementById('estimate-discount').textContent = `- ${formatAmount(discount)}`;
                document.getElementById('estimate-total').textContent = formatAmount(subtotal - discount);
            };

            document.querySelectorAll('input[name="billing_cycle"]')
                .forEach(input => input.addEventListener('change', updateEstimate));
            promoInput.addEventListener('input', updateEstimate);
            updateEstimate();
        });
    </script>
@endif
@endsection
