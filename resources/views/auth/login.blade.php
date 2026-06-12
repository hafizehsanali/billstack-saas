<x-guest-layout>
    <div class="mb-4">
        <div class="d-inline-flex align-items-center gap-2 text-success fw-semibold small mb-3">
            <i data-lucide="shield-check"></i>
            Secure business access
        </div>
        <h1 class="h2 fw-bold mb-2">Sign in to {{ platform_name() }}</h1>
        <p class="text-muted mb-0">
            Manage inventory, invoices, purchases, reports, and business accounts from one workspace.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <div class="input-group">
                <span class="input-group-text"><i data-lucide="mail"></i></span>
                <input id="email"
                       class="form-control"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="you@business.com"
                       required
                       autofocus
                       autocomplete="username">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label">Password</label>
                @if (Route::has('password.request'))
                    <a class="small" href="{{ route('password.request') }}">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="input-group">
                <span class="input-group-text"><i data-lucide="lock-keyhole"></i></span>
                <input id="password"
                       class="form-control"
                       type="password"
                       name="password"
                       placeholder="Enter your password"
                       required
                       autocomplete="current-password">
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mb-4">
            <label for="remember_me" class="form-check">
                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                <span class="form-check-label">Keep me signed in on this device</span>
            </label>
        </div>

        <button class="btn btn-primary w-100">
            <i data-lucide="log-in"></i>
            Sign In
        </button>

        <div class="text-center text-muted small mt-4">
            New to {{ platform_name() }}?
            <a href="{{ route('plans.index') }}" class="fw-semibold">View packages</a>
        </div>
    </form>
</x-guest-layout>
