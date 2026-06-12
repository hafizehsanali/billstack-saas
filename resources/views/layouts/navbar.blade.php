@php
    $navbarSubscription = auth()->user()?->isPlatformAdmin()
        ? null
        : auth()->user()?->tenant?->currentSubscription?->loadMissing('plan');
    $navbarPlan = $navbarSubscription?->plan;
    $subscriptionExpired = $navbarSubscription
        && (
            ($navbarSubscription->trial_ends_at?->isPast() ?? false)
            || ($navbarSubscription->ends_at?->isPast() ?? false)
        );
    $trialIsActive = $navbarSubscription?->status === 'active'
        && $navbarSubscription?->trial_ends_at?->isFuture();
    $trialDaysRemaining = $trialIsActive
        ? max(1, (int) ceil(now()->diffInDays($navbarSubscription->trial_ends_at)))
        : null;
    $subscriptionState = match (true) {
        ! $navbarSubscription || ! $navbarPlan => ['label' => 'No package', 'class' => 'neutral', 'icon' => 'package'],
        $trialIsActive => [
            'label' => $trialDaysRemaining.' '.($trialDaysRemaining === 1 ? 'day' : 'days').' left',
            'class' => 'trial',
            'icon' => 'clock-3',
        ],
        in_array($navbarSubscription->status, ['paused', 'pending'], true)
            && $navbarPlan->monthly_price_cents > 0 => [
                'label' => 'Payment due',
                'class' => 'warning',
                'icon' => 'credit-card',
            ],
        $subscriptionExpired || $navbarSubscription->status === 'cancelled' => [
            'label' => 'Expired',
            'class' => 'danger',
            'icon' => 'calendar-x-2',
        ],
        $navbarSubscription->status === 'active' => ['label' => 'Active', 'class' => 'active', 'icon' => 'circle-check'],
        default => ['label' => str($navbarSubscription->status)->title(), 'class' => 'neutral', 'icon' => 'package'],
    };
@endphp

<header class="navbar navbar-expand-md billstack-topbar d-print-none">
    <div class="container-xl">
        <div class="topbar-identity">
            <strong>
                {{ auth()->user()?->isPlatformAdmin() ? 'Platform workspace' : auth()->user()?->tenant?->name }}
            </strong>
            <span class="topbar-separator d-none d-sm-inline" aria-hidden="true"></span>
            <span class="topbar-context d-none d-sm-inline">
                {{ now()->format('D, M d, Y') }}
            </span>
        </div>

        <div class="navbar-nav flex-row align-items-center gap-1 ms-auto">
            @unless(auth()->user()?->isPlatformAdmin())
                <a href="{{ route('billing.index') }}"
                   class="subscription-navbar-badge is-{{ $subscriptionState['class'] }} {{ request()->routeIs('billing.*', 'subscription.*') ? 'active' : '' }}"
                   title="{{ $navbarPlan?->name ?? 'No package' }} - {{ $subscriptionState['label'] }}">
                    <i data-lucide="{{ $subscriptionState['icon'] }}"></i>
                    <span class="subscription-navbar-plan">{{ $navbarPlan?->name ?? 'No package' }}</span>
                    <span class="subscription-navbar-status">
                        {{ $trialIsActive ? 'Trial: '.$subscriptionState['label'] : $subscriptionState['label'] }}
                    </span>
                </a>

                @can('dashboard.view')
                    <a href="{{ route('alerts.index') }}"
                       class="topbar-icon-button {{ request()->routeIs('alerts.*') ? 'active' : '' }}"
                       title="Open alerts"
                       aria-label="Open alerts">
                        <i data-lucide="bell"></i>
                    </a>
                @endcan
            @endunless

            <div class="dropdown">
                <button class="user-menu-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                    <span class="user-avatar">
                        {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
                    </span>
                    <span class="user-menu-copy d-none d-sm-flex">
                        <strong>{{ auth()->user()->name }}</strong>
                    </span>
                    <i data-lucide="chevron-down" class="user-menu-chevron"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-end user-menu-dropdown">
                    <div class="user-menu-summary">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span>{{ auth()->user()->email }}</span>
                        <small>
                            {{ str(auth()->user()->roles->first()?->name ?? (auth()->user()->isPlatformAdmin() ? 'Platform Admin' : 'User'))->replace('_', ' ')->title() }}
                        </small>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                        <i data-lucide="user-cog"></i>
                        Profile Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i data-lucide="log-out"></i>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
