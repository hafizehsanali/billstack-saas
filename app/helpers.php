<?php

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Schema;

function tenant()
{
    return auth()->user()?->tenant;
}

function platform_settings(): PlatformSetting
{
    try {
        if (Schema::hasTable('platform_settings')) {
            return PlatformSetting::current();
        }
    } catch (Throwable) {
        // Commands that run before the database is ready use the defaults below.
    }

    return new PlatformSetting([
        'platform_name' => 'Zephrant ERP',
        'currency_code' => 'PKR',
        'allow_registration' => true,
    ]);
}

function platform_name(): string
{
    return platform_settings()->platform_name ?: 'Zephrant ERP';
}

function platform_company_name(): string
{
    return 'Zephrant Technologies';
}

function platform_parent_company_name(): string
{
    return 'Zephrant Group';
}

function platform_domain(): string
{
    return 'zephrant.com';
}

function platform_primary_email(): string
{
    return 'hello@zephrant.com';
}

function platform_general_email(): string
{
    return 'info@zephrant.com';
}

function platform_tagline(): string
{
    return 'Technology That Drives Growth';
}

function platform_logo_asset(): string
{
    return asset('assets/brand/logo.png');
}

function platform_logo_white_asset(): string
{
    return asset('assets/brand/logo-white.png');
}

function platform_icon_asset(): string
{
    return asset('assets/brand/icon.png');
}

function platform_favicon_asset(): string
{
    return asset('assets/brand/favicon.png');
}
