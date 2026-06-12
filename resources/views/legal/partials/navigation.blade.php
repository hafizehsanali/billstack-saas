<aside class="legal-navigation">
    <div class="legal-navigation-title">Legal documents</div>
    <a href="{{ route('legal.terms') }}" class="{{ request()->routeIs('legal.terms') ? 'active' : '' }}">
        <i data-lucide="file-text"></i> Terms of Service
    </a>
    <a href="{{ route('legal.privacy') }}" class="{{ request()->routeIs('legal.privacy') ? 'active' : '' }}">
        <i data-lucide="shield-check"></i> Privacy Policy
    </a>
    <a href="{{ route('legal.refunds') }}" class="{{ request()->routeIs('legal.refunds') ? 'active' : '' }}">
        <i data-lucide="rotate-ccw"></i> Refund Policy
    </a>
    @if(platform_settings()->support_email || platform_settings()->support_phone)
        <div class="legal-support">
            <strong>Need help?</strong>
            @if(platform_settings()->support_email)<span>{{ platform_settings()->support_email }}</span>@endif
            @if(platform_settings()->support_phone)<span>{{ platform_settings()->support_phone }}</span>@endif
        </div>
    @endif
</aside>
