<?php

namespace App\Providers;

use App\Services\TenantFeatureService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::if('feature', function (string $featureKey): bool {
            return app(TenantFeatureService::class)->userHasFeature(auth()->user(), $featureKey);
        });
    }
}
