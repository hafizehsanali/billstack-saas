<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ platform_name() }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/png" href="{{ asset('favicon-64.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <main class="guest-shell">
            <section class="guest-brand-panel">
                <a href="/" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none position-relative" style="z-index: 1;">
                    <span class="brand-mark">{{ str(platform_name())->substr(0, 2)->upper() }}</span>
                    <span class="fs-2 fw-bold">{{ platform_name() }}</span>
                </a>

                <div class="guest-brand-content">
                    <div class="text-uppercase fw-semibold small mb-3" style="color: #f0c95c;">Business operations, organized</div>
                    <h1>Run the store. Know the numbers.</h1>
                    <p class="mt-4">
                        Billing, stock, purchasing, customer accounts, supplier balances, and reports in one focused workspace.
                    </p>
                    <div class="guest-feature-list">
                        <div class="guest-feature">
                            <i data-lucide="scan-line"></i>
                            <div><strong>Fast billing</strong><div class="small text-white-50">POS and invoice workflows</div></div>
                        </div>
                        <div class="guest-feature">
                            <i data-lucide="boxes"></i>
                            <div><strong>Clear inventory</strong><div class="small text-white-50">Stock movement and alerts</div></div>
                        </div>
                        <div class="guest-feature">
                            <i data-lucide="book-open"></i>
                            <div><strong>Account ledgers</strong><div class="small text-white-50">Customer and supplier balances</div></div>
                        </div>
                        <div class="guest-feature">
                            <i data-lucide="chart-no-axes-combined"></i>
                            <div><strong>Useful reports</strong><div class="small text-white-50">Sales, stock, and profit</div></div>
                        </div>
                    </div>
                </div>

                <div class="small text-white-50 position-relative" style="z-index: 1;">
                    Built for stores, pharmacies, hardware shops, and wholesalers
                </div>
            </section>

            <section class="guest-form-panel">
                <div class="guest-form-wrap">
                    <div class="auth-form-card">
                        {{ $slot }}
                    </div>
                    <div class="d-flex justify-content-center gap-3 mt-4 small">
                        <a href="{{ route('legal.privacy') }}">Privacy</a>
                        <a href="{{ route('legal.terms') }}">Terms</a>
                        <a href="{{ route('legal.refunds') }}">Refunds</a>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
