<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages | BillStack</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon-64.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
<header class="border-bottom bg-white">
    <div class="container-xl py-3 d-flex align-items-center justify-content-between gap-3">
        <a href="{{ url('/') }}" class="d-flex align-items-center gap-2 text-dark text-decoration-none">
            <img src="{{ asset('favicon-64.png') }}" alt="" width="36" height="36">
            <span class="h2 mb-0">BillStack</span>
        </a>
        <div class="d-flex gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-secondary">Sign In</a>
            @endauth
        </div>
    </div>
</header>

<main>
    <section class="border-bottom bg-white">
        <div class="container-xl py-5 text-center">
            <div class="text-uppercase text-primary fw-semibold small mb-2">Simple business pricing</div>
            <h1 class="display-5 fw-bold mb-3">Choose the package that fits your store</h1>
            <p class="lead text-muted mx-auto mb-0" style="max-width: 760px;">
                Start with core billing and inventory, then move to advanced checkout,
                team controls, and business insights as your operations grow.
            </p>
        </div>
    </section>

    <section class="container-xl py-5">
        <div class="row g-4 justify-content-center">
            @forelse($plans as $plan)
                @php
                    $isPaid = $plan->monthly_price_cents > 0;
                    $trialDays = $isPaid ? (int) $plan->trial_days : 0;
                    $isCurrentPlan = auth()->check()
                        && auth()->user()->tenant?->activeSubscription?->subscription_plan_id === $plan->id;
                @endphp

                <div class="col-md-6 col-xl-4">
                    <article class="card h-100 {{ $isPaid ? 'border-primary' : '' }}">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h2 class="h2 mb-1">{{ $plan->name }}</h2>
                                    <div class="text-muted">
                                        {{ $plan->user_limit ? 'Up to '.$plan->user_limit.' users' : 'Unlimited users' }}
                                    </div>
                                </div>
                                @if($trialDays > 0)
                                    <span class="badge bg-success text-white">{{ $trialDays }}-day trial</span>
                                @elseif(! $isPaid)
                                    <span class="badge bg-light text-dark border">Free</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                @if($isPaid)
                                    <span class="h1 mb-0">Rs {{ number_format($plan->monthly_price_cents / 100, 0) }}</span>
                                    <span class="text-muted">/month</span>
                                    @if($plan->annual_price_cents > 0)
                                        <div class="text-muted small mt-1">
                                            Rs {{ number_format($plan->annual_price_cents / 100, 0) }} billed annually
                                        </div>
                                    @endif
                                @else
                                    <span class="h1 mb-0">Free</span>
                                    <div class="text-muted small mt-1">
                                        {{ $plan->free_access_days
                                            ? $plan->free_access_days.' days of free access'
                                            : 'Permanent free access' }}
                                    </div>
                                @endif
                            </div>

                            <p class="text-muted">{{ $plan->description }}</p>

                            @if($plan->availableOffers->isNotEmpty())
                                <div class="border rounded p-3 mb-3">
                                    <div class="text-uppercase text-secondary fw-semibold small mb-2">Available Offers</div>
                                    @foreach($plan->availableOffers as $offer)
                                        <div class="{{ ! $loop->last ? 'mb-2' : '' }}">
                                            <span class="badge bg-warning text-dark">{{ $offer->code }}</span>
                                            <span class="small ms-1">
                                                @if($offer->discount_type === 'percent')
                                                    {{ $offer->discount_value }}% off
                                                @else
                                                    Rs {{ number_format($offer->discount_value / 100, 0) }} off
                                                @endif
                                                | {{ $offer->billing_cycle === 'both'
                                                    ? 'monthly or annual'
                                                    : $offer->billing_cycle }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="border-top pt-3 mb-4">
                                <div class="fw-semibold mb-2">Included features</div>
                                <ul class="list-unstyled mb-0">
                                    @forelse($plan->features as $feature)
                                        <li class="d-flex gap-2 mb-2">
                                            <span class="text-success fw-bold" aria-hidden="true">&#10003;</span>
                                            <span>{{ $feature->name }}</span>
                                        </li>
                                    @empty
                                        <li class="text-muted">Core business tools included.</li>
                                    @endforelse
                                </ul>
                            </div>

                            <div class="mt-auto">
                                @if($isCurrentPlan)
                                    <button class="btn btn-outline-secondary w-100" disabled>Current Package</button>
                                @elseif(auth()->check())
                                    <a href="{{ route('billing.index') }}" class="btn btn-primary w-100">
                                        Review Subscription
                                    </a>
                                @else
                                    <a href="{{ route('register', ['plan' => $plan->slug]) }}"
                                       class="btn {{ $isPaid ? 'btn-primary' : 'btn-outline-primary' }} w-100">
                                        {{ $trialDays > 0 ? 'Start Free Trial' : ($isPaid ? 'Select Package' : 'Start Free') }}
                                    </a>
                                @endif

                                @if($isPaid && $trialDays === 0)
                                    <div class="text-muted small text-center mt-2">
                                        Full payment is required before paid access starts.
                                    </div>
                                @elseif(! $isPaid && $plan->free_access_days)
                                    <div class="text-muted small text-center mt-2">
                                        Access ends after {{ $plan->free_access_days }} days. You can then choose a paid package.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-lg-8">
                    <div class="alert alert-info text-center">
                        Public packages are being prepared. Please check again shortly.
                    </div>
                </div>
            @endforelse
        </div>
    </section>
</main>

<footer class="border-top bg-white">
    <div class="container-xl py-4 d-flex flex-wrap justify-content-between gap-2 text-muted small">
        <span>BillStack business management</span>
        <a href="{{ url('/') }}" class="text-muted">Back to home</a>
    </div>
</footer>
</body>
</html>
