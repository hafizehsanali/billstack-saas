@php
    $isActive = fn (array $patterns): bool => request()->routeIs(...$patterns);

    $storeLinks = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => ['dashboard']],
        ['label' => 'Alerts', 'route' => 'alerts.index', 'active' => ['alerts.*']],
        ['label' => 'Products', 'route' => 'products.index', 'active' => ['products.index', 'products.edit', 'products.stock-ledger']],
        ['label' => 'Add Product', 'route' => 'products.create', 'active' => ['products.create']],
        ['label' => 'Categories', 'route' => 'categories.index', 'active' => ['categories.*']],
        ['label' => 'POS Billing', 'route' => 'invoices.pos', 'active' => ['invoices.pos']],
        ['label' => 'Invoices', 'route' => 'invoices.index', 'active' => ['invoices.index', 'invoices.show', 'payments.*']],
        ['label' => 'Create Invoice', 'route' => 'invoices.create', 'active' => ['invoices.create']],
        ['label' => 'Customers', 'route' => 'customers.index', 'active' => ['customers.index', 'customers.statement', 'customer.account']],
        ['label' => 'Add Customer', 'route' => 'customers.create', 'active' => ['customers.create']],
    ];

    $financeLinks = [
        ['label' => 'Purchases', 'route' => 'purchases.index', 'active' => ['purchases.index', 'purchases.show', 'purchases.edit']],
        ['label' => 'Create Purchase', 'route' => 'purchases.create', 'active' => ['purchases.create']],
        ['label' => 'Suppliers', 'route' => 'suppliers.index', 'active' => ['suppliers.index', 'suppliers.show', 'suppliers.edit', 'supplier.*', 'supplier-payments.*']],
        ['label' => 'Add Supplier', 'route' => 'suppliers.create', 'active' => ['suppliers.create']],
        ['label' => 'Expenses', 'route' => 'expenses.index', 'active' => ['expenses.index', 'expenses.edit']],
        ['label' => 'Create Expense', 'route' => 'expenses.create', 'active' => ['expenses.create']],
    ];

    $reportLinks = [
        ['label' => 'Daily Sales', 'route' => 'reports.daily-sales', 'active' => ['reports.daily-sales']],
        ['label' => 'Monthly Sales', 'route' => 'reports.monthly-sales', 'active' => ['reports.monthly-sales']],
        ['label' => 'Stock Report', 'route' => 'reports.stock', 'active' => ['reports.stock']],
        ['label' => 'Low Stock', 'route' => 'reports.low-stock', 'active' => ['reports.low-stock']],
        ['label' => 'Profit & Loss', 'route' => 'reports.profit-loss', 'active' => ['reports.profit-loss']],
    ];

    $settingsLinks = [
        ['label' => 'Team Users', 'route' => 'team.index', 'active' => ['team.*']],
        ['label' => 'Business Settings', 'route' => 'settings.business', 'active' => ['settings.*']],
    ];

    $platformLinks = [
        ['label' => 'Platform Admin', 'route' => 'platform.dashboard', 'active' => ['platform.dashboard']],
        ['label' => 'Tenants', 'route' => 'platform.tenants.index', 'active' => ['platform.tenants.*']],
        ['label' => 'Plans', 'route' => 'platform.plans.index', 'active' => ['platform.plans.*']],
        ['label' => 'Features', 'route' => 'platform.features.index', 'active' => ['platform.features.*']],
        ['label' => 'Offers', 'route' => 'platform.offers.index', 'active' => ['platform.offers.*']],
    ];
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark bg-dark d-print-none">
    <div class="container-fluid">
        <h1 class="navbar-brand">
            <a href="{{ route('dashboard') }}" class="text-white text-decoration-none">
                BillStack
            </a>
        </h1>

        <div class="navbar-collapse">
            <ul class="navbar-nav pt-lg-3">
                <li class="nav-item mb-1">
                    <span class="nav-link disabled text-uppercase text-white-50 small">
                        Store Operations
                    </span>
                </li>

                @foreach($storeLinks as $link)
                    <li class="nav-item">
                        <a class="nav-link text-white {{ $isActive($link['active']) ? 'active' : '' }}"
                           href="{{ route($link['route']) }}">
                            <span class="nav-link-title">{{ $link['label'] }}</span>
                        </a>
                    </li>
                @endforeach

                @hasanyrole('owner|accountant')
                    <li class="nav-item mt-3 mb-1">
                        <span class="nav-link disabled text-uppercase text-white-50 small">
                            Finance
                        </span>
                    </li>

                    @foreach($financeLinks as $link)
                        <li class="nav-item">
                            <a class="nav-link text-white {{ $isActive($link['active']) ? 'active' : '' }}"
                               href="{{ route($link['route']) }}">
                                <span class="nav-link-title">{{ $link['label'] }}</span>
                            </a>
                        </li>
                    @endforeach

                    <li class="nav-item mt-3 mb-1">
                        <span class="nav-link disabled text-uppercase text-white-50 small">
                            Reports
                        </span>
                    </li>

                    @foreach($reportLinks as $link)
                        <li class="nav-item">
                            <a class="nav-link text-white {{ $isActive($link['active']) ? 'active' : '' }}"
                               href="{{ route($link['route']) }}">
                                <span class="nav-link-title">{{ $link['label'] }}</span>
                            </a>
                        </li>
                    @endforeach

                    <li class="nav-item mt-3 mb-1">
                        <span class="nav-link disabled text-uppercase text-white-50 small">
                            Settings
                        </span>
                    </li>

                    @foreach($settingsLinks as $link)
                        <li class="nav-item">
                            <a class="nav-link text-white {{ $isActive($link['active']) ? 'active' : '' }}"
                               href="{{ route($link['route']) }}">
                                <span class="nav-link-title">{{ $link['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                @endhasanyrole

                @if(auth()->user()?->isPlatformAdmin())
                    <li class="nav-item mt-3 mb-1">
                        <span class="nav-link disabled text-uppercase text-white-50 small">
                            SaaS Control
                        </span>
                    </li>

                    @foreach($platformLinks as $link)
                        <li class="nav-item">
                            <a class="nav-link text-white {{ $isActive($link['active']) ? 'active' : '' }}"
                               href="{{ route($link['route']) }}">
                                <span class="nav-link-title">{{ $link['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>
    </div>
</aside>
