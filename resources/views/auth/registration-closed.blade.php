@extends('layouts.public')

@section('title', 'Registration Unavailable')
@section('meta_description', 'New business registration is temporarily paused on '.platform_name().'.')

@section('content')
<section class="registration-closed-section">
    <div class="container-xl">
        <div class="registration-closed-layout">
            <div class="registration-closed-copy">
                <div class="registration-status">
                    <span class="registration-status-icon"><i data-lucide="clock-3"></i></span>
                    Registration paused
                </div>

                <h1>New registrations are temporarily unavailable</h1>
                <p class="registration-closed-lead">
                    Existing businesses can continue working normally. New account creation
                    will return when registration access is reopened.
                </p>

                <div class="registration-closed-actions">
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                        <i data-lucide="log-in"></i>
                        Sign In
                    </a>
                    <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary btn-lg">
                        View Packages
                    </a>
                </div>

                <a href="{{ url('/') }}" class="registration-home-link">
                    <i data-lucide="arrow-left"></i>
                    Return to home
                </a>
            </div>

            <aside class="registration-support-panel">
                <div class="registration-support-icon"><i data-lucide="life-buoy"></i></div>
                <h2>Need a new business account?</h2>
                <p>Contact the platform team for availability, onboarding help, or an update about registration access.</p>

                <div class="registration-support-options">
                    @if($settings->support_email)
                        <a href="mailto:{{ $settings->support_email }}">
                            <i data-lucide="mail"></i>
                            <span><small>Email support</small><strong>{{ $settings->support_email }}</strong></span>
                        </a>
                    @endif

                    @if($settings->support_phone)
                        <a href="tel:{{ preg_replace('/\s+/', '', $settings->support_phone) }}">
                            <i data-lucide="phone"></i>
                            <span><small>Call support</small><strong>{{ $settings->support_phone }}</strong></span>
                        </a>
                    @endif
                </div>

                @unless($settings->support_email || $settings->support_phone)
                    <div class="registration-support-unavailable">
                        Support contact details will be published here when available.
                    </div>
                @endunless
            </aside>
        </div>
    </div>
</section>
@endsection
