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
        'platform_name' => 'BillStack',
        'currency_code' => 'PKR',
        'allow_registration' => true,
    ]);
}

function platform_name(): string
{
    return platform_settings()->platform_name ?: 'BillStack';
}
