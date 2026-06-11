<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BillStack</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon-64.png') }}">

    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-light">

<main class="container py-5">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <div class="mb-3 text-uppercase text-primary fw-semibold small">
                Inventory and billing SaaS
            </div>

            <h1 class="display-4 fw-bold mb-3">
                BillStack
            </h1>

            <p class="lead text-muted mb-4">
                A business management system for general stores, hardware shops, pharmacies, wholesalers, and service-retail teams.
            </p>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="badge bg-primary text-white">Inventory</span>
                <span class="badge bg-success text-white">POS Billing</span>
                <span class="badge bg-warning text-dark">Purchases</span>
                <span class="badge bg-info text-dark">Accounts</span>
                <span class="badge bg-secondary text-white">Reports</span>
            </div>

            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                    Go to Dashboard
                </a>
            @else
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                        Sign In
                    </a>

                    <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary btn-lg">
                        View Packages
                    </a>
                </div>
            @endauth
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h4 mb-3">Built for real shop workflows</h2>

                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <strong>Sell and receive payments</strong>
                            <div class="text-muted small">Create invoices, POS bills, customer payments, and sales returns.</div>
                        </li>
                        <li class="mb-3">
                            <strong>Buy and pay suppliers</strong>
                            <div class="text-muted small">Track purchases, supplier payments, supplier returns, and payable balances.</div>
                        </li>
                        <li class="mb-3">
                            <strong>Understand stock movement</strong>
                            <div class="text-muted small">Review stock ledgers, low-stock alerts, and inventory value.</div>
                        </li>
                        <li>
                            <strong>Read business reports</strong>
                            <div class="text-muted small">Use sales, stock, low-stock, and profit/loss reports for decisions.</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

</body>
</html>
