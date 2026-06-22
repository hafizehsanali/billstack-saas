<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', platform_name().' business management platform')">
    <title>@yield('title') | {{ platform_name() }}</title>
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

        <nav class="public-nav d-none d-md-flex" aria-label="Public navigation">
            <a href="{{ url('/#capabilities') }}">Capabilities</a>
            <a href="{{ url('/#industries') }}">Industries</a>
            <a href="{{ route('plans.index') }}">Packages</a>
        </nav>

        <div class="public-header-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
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
    @yield('content')
</main>

<footer class="public-footer">
    <div class="container-xl public-footer-inner">
        <div>
            <a href="{{ url('/') }}" class="public-brand">
                <img class="brand-logo" src="{{ platform_logo_asset() }}" alt="{{ platform_name() }} logo">
                <span>{{ platform_name() }}</span>
            </a>
            <p>{{ platform_tagline() }}.</p>
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
@yield('scripts')
</body>
</html>
