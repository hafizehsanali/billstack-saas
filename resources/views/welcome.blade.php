<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ platform_name() }} brings billing, inventory, purchases, customer accounts, supplier balances, and reports into one business workspace.">
    <title>{{ platform_name() }} | Business Management Made Clear</title>
    <link rel="icon" type="image/png" href="{{ platform_favicon_asset() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-site">
<header class="public-header">
    <div class="container-xl public-header-inner">
        <a href="{{ url('/') }}" class="public-brand" aria-label="{{ platform_name() }} home">
            <img class="brand-logo" src="{{ platform_logo_asset() }}" alt="{{ platform_name() }} logo">
            <span>{{ platform_name() }}</span>
        </a>

        <nav class="public-nav d-none d-md-flex" aria-label="Main navigation">
            <a href="#capabilities">Capabilities</a>
            <a href="#industries">Industries</a>
            <a href="#workflow">How it works</a>
            <a href="{{ route('plans.index') }}">Pricing</a>
        </nav>

        <div class="public-header-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                    <i data-lucide="layout-dashboard"></i>
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-link d-none d-sm-inline-flex">Sign In</a>
                <a href="{{ route('plans.index') }}" class="btn btn-primary">
                    View Packages
                    <i data-lucide="arrow-right"></i>
                </a>
            @endauth
        </div>
    </div>
</header>

<main>
    <section class="public-hero"
             style="--public-hero-image: url('{{ asset('images/zephrant-erp-home-hero.png') }}');">
        <div class="container-xl public-hero-content">
            <div class="public-hero-copy">
                <div class="public-eyebrow">
                    <span></span>
                    {{ platform_tagline() }}
                </div>
                <h1>{{ platform_name() }}</h1>
                <p class="public-hero-lead">
                    Billing, inventory, purchases, customer accounts, supplier balances,
                    and reports in one clear system.
                </p>
                <p class="public-hero-detail">
                    Spend less time matching notebooks and spreadsheets. Keep sales, stock,
                    payments, and business decisions connected from the first transaction.
                </p>

                <div class="public-hero-actions">
                    <a href="{{ route('plans.index') }}" class="btn btn-primary btn-lg">
                        Explore Packages
                        <i data-lucide="arrow-up-right"></i>
                    </a>
                    <a href="#capabilities" class="btn btn-outline-light btn-lg">
                        See What It Covers
                    </a>
                </div>

                <div class="public-hero-trust">
                    <span><i data-lucide="check"></i> Start with a free package</span>
                    <span><i data-lucide="check"></i> Upgrade as your business grows</span>
                </div>
            </div>
        </div>
    </section>

    <section class="public-proof-band" aria-label="Core business areas">
        <div class="container-xl public-proof-grid">
            <div><i data-lucide="scan-barcode"></i><span>POS & Invoicing</span></div>
            <div><i data-lucide="boxes"></i><span>Live Inventory</span></div>
            <div><i data-lucide="book-open"></i><span>Account Ledgers</span></div>
            <div><i data-lucide="chart-no-axes-combined"></i><span>Business Reports</span></div>
        </div>
    </section>

    <section class="public-section" id="capabilities">
        <div class="container-xl">
            <div class="public-section-heading">
                <div>
                    <div class="public-kicker">One connected workspace</div>
                    <h2>Know what is selling, what is due, and what needs attention.</h2>
                </div>
                <p>
                    {{ platform_name() }} connects the daily work at the counter with the financial picture
                    behind the business, so owners and teams work from the same records.
                </p>
            </div>

            <div class="public-capability-grid">
                <article class="public-capability">
                    <span class="public-capability-icon"><i data-lucide="receipt-text"></i></span>
                    <h3>Sell with confidence</h3>
                    <p>Create POS bills and invoices, receive payments, manage returns, and keep customer balances accurate.</p>
                    <a href="{{ route('plans.index') }}">Billing features <i data-lucide="arrow-right"></i></a>
                </article>
                <article class="public-capability">
                    <span class="public-capability-icon blue"><i data-lucide="package-search"></i></span>
                    <h3>Control every stock movement</h3>
                    <p>Track purchases, sales, returns, adjustments, inventory value, and low-stock items from one product record.</p>
                    <a href="{{ route('plans.index') }}">Inventory features <i data-lucide="arrow-right"></i></a>
                </article>
                <article class="public-capability">
                    <span class="public-capability-icon gold"><i data-lucide="landmark"></i></span>
                    <h3>Understand who owes whom</h3>
                    <p>See customer receivables, supplier payables, payment history, and account statements without manual calculation.</p>
                    <a href="{{ route('plans.index') }}">Account features <i data-lucide="arrow-right"></i></a>
                </article>
                <article class="public-capability">
                    <span class="public-capability-icon red"><i data-lucide="bell-ring"></i></span>
                    <h3>Act before issues grow</h3>
                    <p>Use low-stock, payment-due, sales, stock, and profit reports to focus attention where the business needs it.</p>
                    <a href="{{ route('plans.index') }}">Reporting features <i data-lucide="arrow-right"></i></a>
                </article>
            </div>
        </div>
    </section>

    <section class="public-section public-section-muted" id="industries">
        <div class="container-xl">
            <div class="public-section-heading">
                <div>
                    <div class="public-kicker">Flexible by design</div>
                    <h2>A practical foundation for different kinds of businesses.</h2>
                </div>
                <p>
                    Start with retail and trading operations today. The same platform foundation
                    can support specialized service modules as {{ platform_name() }} expands.
                </p>
            </div>

            <div class="public-industry-grid">
                <article><i data-lucide="store"></i><strong>General Stores</strong><span>Fast checkout and daily stock control</span></article>
                <article><i data-lucide="hammer"></i><strong>Hardware Shops</strong><span>Large catalogs, purchases, and supplier dues</span></article>
                <article><i data-lucide="pill"></i><strong>Pharmacies</strong><span>Organized products, billing, and stock alerts</span></article>
                <article><i data-lucide="warehouse"></i><strong>Wholesalers</strong><span>Volume sales and customer account tracking</span></article>
            </div>

            <div class="public-roadmap-note">
                <span class="public-roadmap-icon"><i data-lucide="blocks"></i></span>
                <div>
                    <strong>Built to grow beyond retail</strong>
                    <p>Hospital, hotel, and other industry-specific management services can be introduced as dedicated modules without changing the core customer experience.</p>
                </div>
                <span class="badge">Planned expansion</span>
            </div>
        </div>
    </section>

    <section class="public-section" id="workflow">
        <div class="container-xl">
            <div class="public-section-heading compact">
                <div>
                    <div class="public-kicker">A clearer working day</div>
                    <h2>From transaction to decision in three steps.</h2>
                </div>
            </div>

            <div class="public-steps">
                <article>
                    <span>01</span>
                    <div><h3>Record the work</h3><p>Create sales, purchases, payments, expenses, and returns as they happen.</p></div>
                </article>
                <article>
                    <span>02</span>
                    <div><h3>Let accounts update</h3><p>Stock, balances, payment status, and ledgers stay connected automatically.</p></div>
                </article>
                <article>
                    <span>03</span>
                    <div><h3>Read the business</h3><p>Use alerts and reports to plan purchases, collect dues, and protect profit.</p></div>
                </article>
            </div>
        </div>
    </section>

    <section class="public-cta">
        <div class="container-xl public-cta-inner">
            <div>
                <div class="public-kicker">Ready for a clearer system?</div>
                <h2>Choose a package and put your business records to work.</h2>
            </div>
            <div class="public-cta-actions">
                <a href="{{ route('plans.index') }}" class="btn btn-warning btn-lg">
                    View Packages
                    <i data-lucide="arrow-right"></i>
                </a>
                @guest
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">Sign In</a>
                @endguest
            </div>
        </div>
    </section>
</main>

<footer class="public-footer">
    <div class="container-xl public-footer-inner">
        <div>
            <a href="{{ url('/') }}" class="public-brand">
                <img class="brand-logo" src="{{ platform_logo_asset() }}" alt="{{ platform_name() }} logo">
                <span>{{ platform_name() }}</span>
            </a>
            <p>{{ platform_company_name() }} builds connected operations for ambitious businesses.</p>
            <small>&copy; {{ now()->year }} {{ platform_company_name() }}. All rights reserved.</small>
        </div>
        <div class="public-footer-links">
            <a href="{{ route('plans.index') }}">Packages</a>
            <a href="{{ route('legal.terms') }}">Terms</a>
            <a href="{{ route('legal.privacy') }}">Privacy</a>
            <a href="{{ route('legal.refunds') }}">Refunds</a>
        </div>
    </div>
</footer>
</body>
</html>
