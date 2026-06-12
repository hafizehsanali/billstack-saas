@php
    $isActive = fn (array $patterns): bool => request()->routeIs(...$patterns);

    $storeLinks = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => ['dashboard'], 'permission' => 'dashboard.view'],
        ['label' => 'Alerts', 'route' => 'alerts.index', 'active' => ['alerts.*'], 'permission' => 'dashboard.view'],
        ['label' => 'Products', 'route' => 'products.index', 'active' => ['products.index', 'products.edit', 'products.stock-ledger'], 'permission' => 'products.view'],
        ['label' => 'Add Product', 'route' => 'products.create', 'active' => ['products.create'], 'permission' => 'products.create'],
        ['label' => 'Categories', 'route' => 'categories.index', 'active' => ['categories.*'], 'permission' => 'products.view'],
        ['label' => 'POS Billing', 'route' => 'invoices.pos', 'active' => ['invoices.pos'], 'permission' => 'sales.create'],
        ['label' => 'Barcode Scanner', 'route' => 'barcode.index', 'active' => ['barcode.*'], 'feature' => 'pro.barcode', 'permission' => 'sales.create'],
        ['label' => 'Invoices', 'route' => 'invoices.index', 'active' => ['invoices.index', 'invoices.show', 'payments.*'], 'permission' => 'sales.view'],
        ['label' => 'Create Invoice', 'route' => 'invoices.create', 'active' => ['invoices.create'], 'permission' => 'sales.create'],
        ['label' => 'Customers', 'route' => 'customers.index', 'active' => ['customers.index', 'customers.statement', 'customer.account'], 'permission' => 'customers.view'],
        ['label' => 'Add Customer', 'route' => 'customers.create', 'active' => ['customers.create'], 'permission' => 'customers.create'],
    ];

    $financeLinks = [
        ['label' => 'Purchases', 'route' => 'purchases.index', 'active' => ['purchases.index', 'purchases.show', 'purchases.edit'], 'permission' => 'purchases.view'],
        ['label' => 'Create Purchase', 'route' => 'purchases.create', 'active' => ['purchases.create'], 'permission' => 'purchases.create'],
        ['label' => 'Suppliers', 'route' => 'suppliers.index', 'active' => ['suppliers.index', 'suppliers.show', 'suppliers.edit', 'supplier.*', 'supplier-payments.*'], 'permission' => 'suppliers.view'],
        ['label' => 'Add Supplier', 'route' => 'suppliers.create', 'active' => ['suppliers.create'], 'permission' => 'suppliers.create'],
        ['label' => 'Expenses', 'route' => 'expenses.index', 'active' => ['expenses.index', 'expenses.edit'], 'permission' => 'expenses.view'],
        ['label' => 'Create Expense', 'route' => 'expenses.create', 'active' => ['expenses.create'], 'permission' => 'expenses.create'],
    ];

    $reportLinks = [
        ['label' => 'Daily Sales', 'route' => 'reports.daily-sales', 'active' => ['reports.daily-sales'], 'permission' => 'reports.view'],
        ['label' => 'Monthly Sales', 'route' => 'reports.monthly-sales', 'active' => ['reports.monthly-sales'], 'permission' => 'reports.view'],
        ['label' => 'Stock Report', 'route' => 'reports.stock', 'active' => ['reports.stock'], 'permission' => 'reports.view'],
        ['label' => 'Low Stock', 'route' => 'reports.low-stock', 'active' => ['reports.low-stock'], 'permission' => 'reports.view'],
        ['label' => 'Profit & Loss', 'route' => 'reports.profit-loss', 'active' => ['reports.profit-loss'], 'permission' => 'reports.view'],
    ];

    $settingsLinks = [
        ['label' => 'Team Users', 'route' => 'team.index', 'active' => ['team.*'], 'permission' => 'team.manage'],
        ['label' => 'Plan & Billing', 'route' => 'billing.index', 'active' => ['billing.*'], 'permission' => 'payments.view'],
        ['label' => 'Business Settings', 'route' => 'settings.business', 'active' => ['settings.*'], 'permission' => 'settings.manage'],
    ];

    $platformLinks = [
        ['label' => 'Platform Admin', 'route' => 'platform.dashboard', 'active' => ['platform.dashboard']],
        ['label' => 'Activity', 'route' => 'platform.activities.index', 'active' => ['platform.activities.*']],
        ['label' => 'Tenants', 'route' => 'platform.tenants.index', 'active' => ['platform.tenants.*']],
        ['label' => 'Billing', 'route' => 'platform.billing.index', 'active' => ['platform.billing.*']],
        ['label' => 'Payment Reviews', 'route' => 'platform.payment-submissions.index', 'active' => ['platform.payment-submissions.*']],
        ['label' => 'Plans', 'route' => 'platform.plans.index', 'active' => ['platform.plans.*']],
        ['label' => 'Features', 'route' => 'platform.features.index', 'active' => ['platform.features.*']],
        ['label' => 'Offers', 'route' => 'platform.offers.index', 'active' => ['platform.offers.*']],
        ['label' => 'Settings', 'route' => 'platform.settings.edit', 'active' => ['platform.settings.*']],
    ];
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark bg-dark d-print-none">
    <div class="container-fluid">
        <h1 class="navbar-brand">
            <a href="{{ auth()->user()?->isPlatformAdmin() ? route('platform.dashboard') : route('dashboard') }}"
               class="text-white text-decoration-none">
                BillStack
            </a>
        </h1>

        <div class="navbar-collapse">
            <ul class="navbar-nav pt-lg-3">
                @if(auth()->user()?->isPlatformAdmin())
                    <li class="nav-item mb-1">
                        <span class="nav-link disabled text-uppercase text-white-50 small">
                            Platform Control
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
                @else
                    <li class="nav-item mb-1">
                        <span class="nav-link disabled text-uppercase text-white-50 small">
                            Store Operations
                        </span>
                    </li>

                    @foreach($storeLinks as $link)
                        @if(auth()->user()->can($link['permission']) && (! isset($link['feature']) || app(\App\Services\TenantFeatureService::class)->userHasFeature(auth()->user(), $link['feature'])))
                            <li class="nav-item">
                                <a class="nav-link text-white {{ $isActive($link['active']) ? 'active' : '' }}"
                                   href="{{ route($link['route']) }}">
                                    <span class="nav-link-title">{{ $link['label'] }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach

                    @if(collect($financeLinks)->contains(fn ($link) => auth()->user()->can($link['permission'])))
                        <li class="nav-item mt-3 mb-1">
                            <span class="nav-link disabled text-uppercase text-white-50 small">
                                Finance
                            </span>
                        </li>

                        @foreach($financeLinks as $link)
                            @can($link['permission'])
                                <li class="nav-item">
                                    <a class="nav-link text-white {{ $isActive($link['active']) ? 'active' : '' }}"
                                       href="{{ route($link['route']) }}">
                                        <span class="nav-link-title">{{ $link['label'] }}</span>
                                    </a>
                                </li>
                            @endcan
                        @endforeach
                    @endif

                    @can('reports.view')
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
                    @endcan

                    @if(collect($settingsLinks)->contains(fn ($link) => auth()->user()->can($link['permission'])))
                        <li class="nav-item mt-3 mb-1">
                            <span class="nav-link disabled text-uppercase text-white-50 small">
                                Settings
                            </span>
                        </li>

                        @foreach($settingsLinks as $link)
                            @can($link['permission'])
                                <li class="nav-item">
                                    <a class="nav-link text-white {{ $isActive($link['active']) ? 'active' : '' }}"
                                       href="{{ route($link['route']) }}">
                                        <span class="nav-link-title">{{ $link['label'] }}</span>
                                    </a>
                                </li>
                            @endcan
                        @endforeach
                    @endif
                @endif
            </ul>
        </div>
    </div>
</aside>
