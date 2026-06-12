<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Account | BillStack</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon-64.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white">
<main class="min-vh-100">
    <div class="row g-0 min-vh-100">
        <section class="col-lg-5 bg-dark text-white d-flex">
            <div class="p-4 p-md-5 d-flex flex-column w-100">
                <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none">
                    <img src="{{ asset('favicon-64.png') }}" alt="" width="40" height="40">
                    <span class="h2 mb-0">BillStack</span>
                </a>

                <div class="my-auto py-5 d-none d-lg-block">
                    <div class="text-uppercase text-white-50 fw-semibold small mb-2">Business workspace</div>
                    <h1 class="display-5 fw-bold text-white mb-3">Set up your store operations.</h1>
                    <p class="fs-3 text-white-50 mb-4">
                        Manage billing, stock, customers, suppliers, expenses, and reports from one account.
                    </p>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="border-top border-secondary pt-3">
                                <div class="fw-semibold">Sales & POS</div>
                                <div class="small text-white-50">Invoices, payments, and returns</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border-top border-secondary pt-3">
                                <div class="fw-semibold">Inventory</div>
                                <div class="small text-white-50">Stock movement and alerts</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border-top border-secondary pt-3">
                                <div class="fw-semibold">Accounts</div>
                                <div class="small text-white-50">Customer and supplier balances</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border-top border-secondary pt-3">
                                <div class="fw-semibold">Reports</div>
                                <div class="small text-white-50">Sales, stock, and profit</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="small text-white-50 d-none d-lg-block">BillStack business management</div>
            </div>
        </section>

        <section class="col-lg-7 d-flex align-items-center">
            <div class="w-100 px-4 py-5 px-md-5">
                <div class="mx-auto" style="max-width: 680px;">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                        <div>
                            <h2 class="display-6 fw-bold mb-1">Create your account</h2>
                            <p class="text-muted mb-0">Enter the owner and business details to continue.</p>
                        </div>
                        <a href="{{ route('login') }}" class="btn btn-outline-secondary">Sign In</a>
                    </div>

                    @if($selectedPlan)
                        @php
                            $isPaidPlan = $selectedPlan->monthly_price_cents > 0;
                            $trialDays = $isPaidPlan ? (int) $selectedPlan->trial_days : 0;
                        @endphp
                        <div class="border rounded p-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <div class="text-uppercase text-secondary fw-semibold small">Selected package</div>
                                <div class="h3 mb-0">{{ $selectedPlan->name }}</div>
                                <div class="text-muted small">
                                    {{ $selectedPlan->user_limit ? $selectedPlan->user_limit.' users' : 'Unlimited users' }}
                                    @if($trialDays > 0)
                                        | {{ $trialDays }}-day trial
                                    @elseif(! $isPaidPlan && $selectedPlan->free_access_days)
                                        | {{ $selectedPlan->free_access_days }} days access
                                    @endif
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="h3 mb-0">
                                    {{ $isPaidPlan ? 'Rs '.number_format($selectedPlan->monthly_price_cents / 100, 0) : 'Free' }}
                                </div>
                                @if($isPaidPlan)
                                    <div class="text-muted small">per month</div>
                                @endif
                                <a href="{{ route('plans.index') }}" class="small">Change package</a>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                            <span>No package selected. Starter will be assigned by default.</span>
                            <a href="{{ route('plans.index') }}" class="btn btn-sm btn-outline-primary">View Packages</a>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        @if($selectedPlan)
                            <input type="hidden" name="plan" value="{{ $selectedPlan->slug }}">
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Owner Name <span class="text-danger">*</span></label>
                                <input id="name"
                                       type="text"
                                       name="name"
                                       value="{{ old('name') }}"
                                       class="form-control @error('name') is-invalid @enderror"
                                       autocomplete="name"
                                       autofocus
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="business_name" class="form-label">Business Name <span class="text-danger">*</span></label>
                                <input id="business_name"
                                       type="text"
                                       name="business_name"
                                       value="{{ old('business_name') }}"
                                       class="form-control @error('business_name') is-invalid @enderror"
                                       autocomplete="organization"
                                       required>
                                @error('business_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input id="email"
                                       type="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       class="form-control @error('email') is-invalid @enderror"
                                       autocomplete="username"
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input id="password"
                                       type="password"
                                       name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       autocomplete="new-password"
                                       required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input id="password_confirmation"
                                       type="password"
                                       name="password_confirmation"
                                       class="form-control"
                                       autocomplete="new-password"
                                       required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-check">
                                <input type="checkbox"
                                       name="terms"
                                       value="1"
                                       class="form-check-input @error('terms') is-invalid @enderror"
                                       @checked(old('terms'))
                                       required>
                                <span class="form-check-label">
                                    I agree to the
                                    <a href="{{ route('legal.terms') }}" target="_blank">Terms of Service</a>,
                                    <a href="{{ route('legal.privacy') }}" target="_blank">Privacy Policy</a>,
                                    and
                                    <a href="{{ route('legal.refunds') }}" target="_blank">Refund Policy</a>.
                                </span>
                            </label>
                            @error('terms')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4 pt-3 border-top">
                            <a href="{{ route('plans.index') }}" class="text-secondary">Back to packages</a>
                            <button type="submit" class="btn btn-primary px-4">Create Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</main>
</body>
</html>
