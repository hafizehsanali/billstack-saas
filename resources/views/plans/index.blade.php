@extends('layouts.public')

@section('title', 'Packages')
@section('meta_description', 'Choose a '.platform_name().' package for billing, inventory, accounts, and reports.')

@section('content')
<section class="pricing-hero">
    <div class="container-xl">
        <div class="public-kicker">Simple business pricing</div>
        <h1>Choose the package that fits your store.</h1>
        <p>Start with core billing and inventory, then add team controls, advanced checkout, and business insights as you grow.</p>
        <div class="pricing-assurances">
            <span><i data-lucide="check"></i> Clear usage limits</span>
            <span><i data-lucide="check"></i> Full price shown before checkout</span>
            <span><i data-lucide="check"></i> Upgrade when you are ready</span>
        </div>
        <div class="pricing-cycle-control" role="group" aria-label="Price billing cycle">
            <button type="button" class="active" data-pricing-cycle="monthly">Monthly</button>
            <button type="button" data-pricing-cycle="annual">Annual</button>
        </div>
    </div>
</section>

<section class="pricing-section">
    <div class="container-xl">
        <div class="pricing-grid">
            @forelse($plans as $plan)
                @php
                    $isPaid = $plan->monthly_price_cents > 0;
                    $trialDays = $isPaid ? (int) $plan->trial_days : 0;
                    $currentSubscription = auth()->check()
                        ? auth()->user()->tenant?->currentSubscription
                        : null;
                    $isCurrentPlan = $currentSubscription?->subscription_plan_id === $plan->id;
                    $isCurrentTrial = $isCurrentPlan
                        && $currentSubscription?->status === 'active'
                        && $currentSubscription?->trial_ends_at?->isFuture()
                        && $isPaid;
                    $activePrice = auth()->check()
                        ? (auth()->user()->tenant?->activeSubscription?->plan?->monthly_price_cents ?? 0)
                        : 0;
                    $signedInAction = match(true) {
                        ! $isPaid => 'Downgrade to Free',
                        $plan->monthly_price_cents > $activePrice => 'Upgrade Package',
                        $plan->monthly_price_cents < $activePrice => 'Downgrade Package',
                        default => 'Switch Package',
                    };
                @endphp

                <article class="pricing-card {{ $isPaid ? 'featured' : '' }}">
                    <div class="pricing-card-top">
                        <div>
                            <h2>{{ $plan->name }}</h2>
                            <span>{{ $plan->user_limit ? 'Up to '.$plan->user_limit.' users' : 'Unlimited users' }}</span>
                        </div>
                        @if($trialDays > 0)
                            <span class="pricing-label">{{ $trialDays }}-day trial</span>
                        @elseif(! $isPaid)
                            <span class="pricing-label neutral">Starter</span>
                        @endif
                    </div>

                    <div class="pricing-price"
                         data-monthly-price="{{ $plan->monthly_price_cents }}"
                         data-annual-price="{{ $plan->annual_price_cents }}">
                        @if($isPaid)
                            <strong data-price-value>Rs {{ number_format($plan->monthly_price_cents / 100, 0) }}</strong>
                            <span data-price-period>/month</span>
                            @if($plan->annual_price_cents > 0)
                                <small data-price-note>
                                    Rs {{ number_format($plan->annual_price_cents / 100, 0) }} billed annually
                                </small>
                            @endif
                        @else
                            <strong>Free</strong>
                            <small>{{ $plan->free_access_days ? $plan->free_access_days.' days of free access' : 'Permanent free access' }}</small>
                        @endif
                    </div>

                    <p class="pricing-description">{{ $plan->description }}</p>

                    @if($plan->availableOffers->isNotEmpty())
                        <div class="pricing-offers">
                            <div class="pricing-offers-title"><i data-lucide="badge-percent"></i> Available Offers</div>
                            @foreach($plan->availableOffers as $offer)
                                <div class="pricing-offer">
                                    <strong>{{ $offer->code }}</strong>
                                    <span>
                                        {{ $offer->discount_type === 'percent'
                                            ? $offer->discount_value.'% off'
                                            : 'Rs '.number_format($offer->discount_value / 100, 0).' off' }}
                                        | {{ $offer->billing_cycle === 'both' ? 'monthly or annual' : $offer->billing_cycle }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="pricing-features">
                        <div class="pricing-features-title">What is included</div>
                        <ul>
                            <li><i data-lucide="check"></i><span>{{ $plan->product_limit ?? 'Unlimited' }} products</span></li>
                            <li><i data-lucide="check"></i><span>{{ $plan->monthly_invoice_limit ?? 'Unlimited' }} invoices per month</span></li>
                            @forelse($plan->features as $feature)
                                <li><i data-lucide="check"></i><span>{{ $feature->name }}</span></li>
                            @empty
                                <li><i data-lucide="check"></i><span>Core business tools included</span></li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="pricing-action">
                        @if($isCurrentTrial)
                            <a href="{{ route('subscription.checkout') }}" class="btn btn-primary w-100">
                                Pay Early
                                <i data-lucide="credit-card"></i>
                            </a>
                        @elseif($isCurrentPlan && $currentSubscription->status === 'active')
                            <button class="btn btn-outline-secondary w-100" disabled>Current Package</button>
                        @elseif($isCurrentPlan)
                            <a href="{{ route('subscription.checkout') }}" class="btn btn-primary w-100">
                                Continue Checkout
                                <i data-lucide="arrow-right"></i>
                            </a>
                        @elseif(auth()->check())
                            <form method="POST" action="{{ route('subscription.plans.select', $plan) }}">
                                @csrf
                                <input type="hidden" name="billing_cycle" value="monthly" data-cycle-input>
                                <button class="btn {{ $isPaid ? 'btn-primary' : 'btn-outline-primary' }} w-100">
                                    {{ $signedInAction }}
                                    <i data-lucide="arrow-right"></i>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('register', ['plan' => $plan->slug, 'cycle' => 'monthly']) }}"
                               data-plan-url="{{ route('register', ['plan' => $plan->slug]) }}"
                               class="btn {{ $isPaid ? 'btn-primary' : 'btn-outline-primary' }} w-100">
                                {{ $trialDays > 0 ? 'Start Free Trial' : ($isPaid ? 'Select Package' : 'Start Free') }}
                                <i data-lucide="arrow-right"></i>
                            </a>
                        @endif

                        @if($isPaid && $trialDays === 0)
                            <small>Full payment is required before paid access starts.</small>
                        @elseif(! $isPaid && $plan->free_access_days)
                            <small>Access ends after {{ $plan->free_access_days }} days. You can then choose a paid package.</small>
                        @endif
                    </div>
                </article>
            @empty
                <div class="pricing-empty">
                    <i data-lucide="package-open"></i>
                    <h2>Packages are being prepared</h2>
                    <p>Please check again shortly or contact platform support.</p>
                </div>
            @endforelse
        </div>

        @if($plans->isNotEmpty())
            <section class="pricing-comparison" aria-labelledby="package-comparison-title">
                <div class="public-section-heading">
                    <div>
                        <div class="public-kicker">Compare packages</div>
                        <h2 id="package-comparison-title">See limits and capabilities side by side.</h2>
                    </div>
                    <p>Swipe horizontally on smaller screens to compare every package without compressed text.</p>
                </div>

                <div class="pricing-comparison-scroll">
                    <table class="pricing-comparison-table">
                        <thead>
                            <tr>
                                <th scope="col">Capability</th>
                                @foreach($plans as $plan)
                                    <th scope="col">{{ $plan->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([
                                'Users' => 'user_limit',
                                'Products' => 'product_limit',
                                'Monthly invoices' => 'monthly_invoice_limit',
                            ] as $label => $attribute)
                                <tr>
                                    <th scope="row">{{ $label }}</th>
                                    @foreach($plans as $plan)
                                        <td>{{ $plan->{$attribute} ?? 'Unlimited' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            @foreach($comparisonFeatures as $feature)
                                <tr>
                                    <th scope="row">{{ $feature->name }}</th>
                                    @foreach($plans as $plan)
                                        <td>
                                            @if($plan->features->contains('id', $feature->id))
                                                <i data-lucide="check" class="comparison-yes" aria-label="Included"></i>
                                            @else
                                                <span class="comparison-no" aria-label="Not included">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</section>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const buttons = document.querySelectorAll('[data-pricing-cycle]');
        const formatter = new Intl.NumberFormat('en-PK', { maximumFractionDigits: 0 });

        const setCycle = cycle => {
            buttons.forEach(button => button.classList.toggle('active', button.dataset.pricingCycle === cycle));

            document.querySelectorAll('.pricing-price[data-monthly-price]').forEach(price => {
                const monthly = Number(price.dataset.monthlyPrice);
                const annual = Number(price.dataset.annualPrice);

                if (monthly === 0 || (cycle === 'annual' && annual === 0)) {
                    return;
                }

                const amount = cycle === 'annual' ? annual : monthly;
                price.querySelector('[data-price-value]').textContent = `Rs ${formatter.format(amount / 100)}`;
                price.querySelector('[data-price-period]').textContent = cycle === 'annual' ? '/year' : '/month';

                const note = price.querySelector('[data-price-note]');
                if (note) {
                    const annualSaving = Math.max((monthly * 12) - annual, 0);
                    note.textContent = cycle === 'annual' && annualSaving > 0
                        ? `Save Rs ${formatter.format(annualSaving / 100)} per year`
                        : `Rs ${formatter.format(annual / 100)} billed annually`;
                }
            });

            document.querySelectorAll('[data-plan-url]').forEach(link => {
                link.href = `${link.dataset.planUrl}?cycle=${cycle}`;
            });
            document.querySelectorAll('[data-cycle-input]').forEach(input => {
                input.value = cycle;
            });
        };

        buttons.forEach(button => button.addEventListener('click', () => setCycle(button.dataset.pricingCycle)));
    });
</script>
@endsection
