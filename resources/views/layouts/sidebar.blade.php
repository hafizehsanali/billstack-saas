@php
    $isActive = fn (array $patterns): bool => request()->routeIs(...$patterns);

    $storeLinks = [
        ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'dashboard', 'active' => ['dashboard'], 'permission' => 'dashboard.view'],
        ['label' => 'Products', 'icon' => 'package-search', 'route' => 'products.index', 'active' => ['products.*'], 'permission' => 'products.view', 'action_route' => 'products.create', 'action_permission' => 'products.create', 'action_label' => 'Add product'],
        ['label' => 'Categories', 'icon' => 'tags', 'route' => 'categories.index', 'active' => ['categories.*'], 'permission' => 'products.view'],
        ['label' => 'Brands', 'icon' => 'badge-check', 'route' => 'brands.index', 'active' => ['brands.*'], 'permission' => 'products.view'],
        ['label' => 'Attributes', 'icon' => 'list-filter', 'route' => 'product-attributes.index', 'active' => ['product-attributes.*'], 'permission' => 'products.view'],
        ['label' => 'POS Billing', 'icon' => 'scan-barcode', 'route' => 'invoices.pos', 'active' => ['invoices.pos'], 'permission' => 'sales.create'],
        ['label' => 'Barcode Scanner', 'icon' => 'scan-line', 'route' => 'barcode.index', 'active' => ['barcode.*'], 'feature' => 'pro.barcode', 'permission' => 'sales.create'],
        ['label' => 'Invoices', 'icon' => 'receipt-text', 'route' => 'invoices.index', 'active' => ['invoices.*', 'payments.*'], 'permission' => 'sales.view', 'action_route' => 'invoices.create', 'action_permission' => 'sales.create', 'action_label' => 'Create invoice'],
        ['label' => 'Customers', 'icon' => 'users', 'route' => 'customers.index', 'active' => ['customers.*', 'customer.account'], 'permission' => 'customers.view', 'action_route' => 'customers.create', 'action_permission' => 'customers.create', 'action_label' => 'Add customer'],
    ];

    $financeLinks = [
        ['label' => 'Purchases', 'icon' => 'shopping-cart', 'route' => 'purchases.index', 'active' => ['purchases.*'], 'permission' => 'purchases.view', 'action_route' => 'purchases.create', 'action_permission' => 'purchases.create', 'action_label' => 'Create purchase'],
        ['label' => 'Suppliers', 'icon' => 'truck', 'route' => 'suppliers.index', 'active' => ['suppliers.*', 'supplier.*', 'supplier-payments.*'], 'permission' => 'suppliers.view', 'action_route' => 'suppliers.create', 'action_permission' => 'suppliers.create', 'action_label' => 'Add supplier'],
        ['label' => 'Expenses', 'icon' => 'wallet-cards', 'route' => 'expenses.index', 'active' => ['expenses.*'], 'permission' => 'expenses.view', 'action_route' => 'expenses.create', 'action_permission' => 'expenses.create', 'action_label' => 'Create expense'],
    ];

    $reportLinks = [
        ['label' => 'Daily Sales', 'icon' => 'chart-column', 'route' => 'reports.daily-sales', 'active' => ['reports.daily-sales'], 'permission' => 'reports.view'],
        ['label' => 'Monthly Sales', 'icon' => 'calendar-range', 'route' => 'reports.monthly-sales', 'active' => ['reports.monthly-sales'], 'permission' => 'reports.view'],
        ['label' => 'Stock Report', 'icon' => 'warehouse', 'route' => 'reports.stock', 'active' => ['reports.stock'], 'permission' => 'reports.view'],
        ['label' => 'Low Stock', 'icon' => 'triangle-alert', 'route' => 'reports.low-stock', 'active' => ['reports.low-stock'], 'permission' => 'reports.view'],
        ['label' => 'Profit & Loss', 'icon' => 'chart-no-axes-combined', 'route' => 'reports.profit-loss', 'active' => ['reports.profit-loss'], 'permission' => 'reports.view'],
    ];

    $settingsLinks = [
        ['label' => 'Team Users', 'icon' => 'users-round', 'route' => 'team.index', 'active' => ['team.*'], 'permission' => 'team.manage'],
        ['label' => 'Plan & Billing', 'icon' => 'credit-card', 'route' => 'billing.index', 'active' => ['billing.*'], 'role' => 'owner'],
        ['label' => 'Business Settings', 'icon' => 'settings-2', 'route' => 'settings.business', 'active' => ['settings.*'], 'permission' => 'settings.manage'],
    ];

    $platformLinks = [
        ['label' => 'Platform Admin', 'icon' => 'gauge', 'route' => 'platform.dashboard', 'active' => ['platform.dashboard']],
        ['label' => 'Activity', 'icon' => 'history', 'route' => 'platform.activities.index', 'active' => ['platform.activities.*']],
        ['label' => 'Tenants', 'icon' => 'building-2', 'route' => 'platform.tenants.index', 'active' => ['platform.tenants.*']],
        ['label' => 'Billing', 'icon' => 'landmark', 'route' => 'platform.billing.index', 'active' => ['platform.billing.*']],
        ['label' => 'Payment Reviews', 'icon' => 'badge-check', 'route' => 'platform.payment-submissions.index', 'active' => ['platform.payment-submissions.*']],
        ['label' => 'Plans', 'icon' => 'layers-3', 'route' => 'platform.plans.index', 'active' => ['platform.plans.*']],
        ['label' => 'Features', 'icon' => 'blocks', 'route' => 'platform.features.index', 'active' => ['platform.features.*']],
        ['label' => 'Offers', 'icon' => 'badge-percent', 'route' => 'platform.offers.index', 'active' => ['platform.offers.*']],
        ['label' => 'Settings', 'icon' => 'settings', 'route' => 'platform.settings.edit', 'active' => ['platform.settings.*']],
    ];

    $groupIsActive = fn (array $links): bool => collect($links)
        ->contains(fn (array $link): bool => $isActive($link['active']));
    $canAccess = fn (array $link): bool => isset($link['role'])
        ? auth()->user()->hasRole($link['role'])
        : auth()->user()->can($link['permission']);
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark zephrant-erp-sidebar d-print-none">
    <div class="container-fluid">
        <h1 class="navbar-brand">
            <a href="{{ auth()->user()?->isPlatformAdmin() ? route('platform.dashboard') : route('dashboard') }}"
               class="d-flex align-items-center gap-2 text-white text-decoration-none">
                <img class="brand-logo brand-logo-white" src="{{ platform_logo_white_asset() }}" alt="{{ platform_name() }} logo">
                <span class="brand-copy">
                    <span>{{ platform_name() }}</span>
                    <small>{{ auth()->user()?->isPlatformAdmin() ? 'Platform control' : 'Business workspace' }}</small>
                </span>
            </a>
        </h1>

        <button type="button"
                class="sidebar-rail-toggle"
                data-sidebar-toggle
                aria-label="Toggle compact sidebar"
                title="Collapse or expand sidebar">
            <span class="sidebar-collapse-icon"><i data-lucide="chevron-left"></i></span>
            <span class="sidebar-expand-icon"><i data-lucide="chevron-right"></i></span>
        </button>

        <div class="navbar-collapse">
            <ul class="navbar-nav pt-lg-3">
                @if(auth()->user()?->isPlatformAdmin())
                    <li class="nav-group">
                        <button class="nav-section" type="button"
                                data-sidebar-group-toggle="platform-control-links"
                                aria-expanded="{{ $groupIsActive($platformLinks) ? 'true' : 'false' }}">
                                <span>Platform Control</span>
                                <i data-lucide="chevron-down"></i>
                        </button>
                        <ul class="nav-group-links" id="platform-control-links"
                            @if(! $groupIsActive($platformLinks)) hidden @endif>
                                @foreach($platformLinks as $link)
                                    <li class="nav-item">
                                        <a class="nav-link {{ $isActive($link['active']) ? 'active' : '' }}"
                                           href="{{ route($link['route']) }}"
                                           title="{{ $link['label'] }}">
                                            <span class="nav-link-icon"><i data-lucide="{{ $link['icon'] }}"></i></span>
                                            <span class="nav-link-title">{{ $link['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                        </ul>
                    </li>
                @else
                    <li class="nav-group">
                        <button class="nav-section" type="button"
                                data-sidebar-group-toggle="store-operation-links"
                                aria-expanded="{{ $groupIsActive($storeLinks) ? 'true' : 'false' }}">
                                <span>Store Operations</span>
                                <i data-lucide="chevron-down"></i>
                        </button>
                        <ul class="nav-group-links" id="store-operation-links"
                            @if(! $groupIsActive($storeLinks)) hidden @endif>
                                @foreach($storeLinks as $link)
                                    @if(auth()->user()->can($link['permission']) && (! isset($link['feature']) || app(\App\Services\TenantFeatureService::class)->userHasFeature(auth()->user(), $link['feature'])))
                                        <li class="nav-item">
                                            <a class="nav-link {{ $isActive($link['active']) ? 'active' : '' }}"
                                               href="{{ route($link['route']) }}"
                                               title="{{ $link['label'] }}">
                                                <span class="nav-link-icon"><i data-lucide="{{ $link['icon'] }}"></i></span>
                                                <span class="nav-link-title">{{ $link['label'] }}</span>
                                            </a>
                                            @if(isset($link['action_route']) && auth()->user()->can($link['action_permission']))
                                                <a class="nav-quick-action"
                                                   href="{{ route($link['action_route']).(isset($link['action_fragment']) ? '#'.$link['action_fragment'] : '') }}"
                                                   title="{{ $link['action_label'] }}"
                                                   aria-label="{{ $link['action_label'] }}">
                                                    <i data-lucide="plus"></i>
                                                </a>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                        </ul>
                    </li>

                    @if(collect($financeLinks)->contains(fn ($link) => auth()->user()->can($link['permission'])))
                        <li class="nav-group">
                            <button class="nav-section" type="button"
                                    data-sidebar-group-toggle="finance-links"
                                    aria-expanded="{{ $groupIsActive($financeLinks) ? 'true' : 'false' }}">
                                    <span>Finance</span>
                                    <i data-lucide="chevron-down"></i>
                            </button>
                            <ul class="nav-group-links" id="finance-links"
                                @if(! $groupIsActive($financeLinks)) hidden @endif>
                                    @foreach($financeLinks as $link)
                                        @can($link['permission'])
                                            <li class="nav-item">
                                                <a class="nav-link {{ $isActive($link['active']) ? 'active' : '' }}"
                                                   href="{{ route($link['route']) }}"
                                                   title="{{ $link['label'] }}">
                                                    <span class="nav-link-icon"><i data-lucide="{{ $link['icon'] }}"></i></span>
                                                    <span class="nav-link-title">{{ $link['label'] }}</span>
                                                </a>
                                                @if(isset($link['action_route']) && auth()->user()->can($link['action_permission']))
                                                    <a class="nav-quick-action"
                                                       href="{{ route($link['action_route']) }}"
                                                       title="{{ $link['action_label'] }}"
                                                       aria-label="{{ $link['action_label'] }}">
                                                        <i data-lucide="plus"></i>
                                                    </a>
                                                @endif
                                            </li>
                                        @endcan
                                    @endforeach
                            </ul>
                        </li>
                    @endif

                    @can('reports.view')
                        <li class="nav-group">
                            <button class="nav-section" type="button"
                                    data-sidebar-group-toggle="report-links"
                                    aria-expanded="{{ $groupIsActive($reportLinks) ? 'true' : 'false' }}">
                                    <span>Reports</span>
                                    <i data-lucide="chevron-down"></i>
                            </button>
                            <ul class="nav-group-links" id="report-links"
                                @if(! $groupIsActive($reportLinks)) hidden @endif>
                                    @foreach($reportLinks as $link)
                                        <li class="nav-item">
                                            <a class="nav-link {{ $isActive($link['active']) ? 'active' : '' }}"
                                               href="{{ route($link['route']) }}"
                                               title="{{ $link['label'] }}">
                                                <span class="nav-link-icon"><i data-lucide="{{ $link['icon'] }}"></i></span>
                                                <span class="nav-link-title">{{ $link['label'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                            </ul>
                        </li>
                    @endcan

                    @if(collect($settingsLinks)->contains($canAccess))
                        <li class="nav-group">
                            <button class="nav-section" type="button"
                                    data-sidebar-group-toggle="setting-links"
                                    aria-expanded="{{ $groupIsActive($settingsLinks) ? 'true' : 'false' }}">
                                    <span>Settings</span>
                                    <i data-lucide="chevron-down"></i>
                            </button>
                            <ul class="nav-group-links" id="setting-links"
                                @if(! $groupIsActive($settingsLinks)) hidden @endif>
                                    @foreach($settingsLinks as $link)
                                        @if($canAccess($link))
                                            <li class="nav-item">
                                                <a class="nav-link {{ $isActive($link['active']) ? 'active' : '' }}"
                                                   href="{{ route($link['route']) }}"
                                                   title="{{ $link['label'] }}">
                                                    <span class="nav-link-icon"><i data-lucide="{{ $link['icon'] }}"></i></span>
                                                    <span class="nav-link-title">{{ $link['label'] }}</span>
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                            </ul>
                        </li>
                    @endif
                @endif
            </ul>
        </div>

        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-signout" title="Sign out">
                    <i data-lucide="log-out"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </div>
</aside>
