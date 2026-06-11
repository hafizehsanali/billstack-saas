<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\SubscriptionLifecycleService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('subscriptions:expire', function (
    SubscriptionLifecycleService $lifecycle
) {
    $count = $lifecycle->expireEndedSubscriptions();

    $this->info("Paused {$count} expired subscription(s).");
})->purpose('Pause subscriptions whose trial or access period has expired');

Schedule::command('subscriptions:expire')
    ->dailyAt('00:10')
    ->withoutOverlapping();
