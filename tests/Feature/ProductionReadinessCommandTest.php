<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionReadinessCommandTest extends TestCase
{
    public function test_production_check_fails_for_unsafe_configuration(): void
    {
        config([
            'app.env' => 'local',
            'app.debug' => true,
            'app.key' => null,
            'app.url' => 'http://localhost',
            'mail.default' => 'log',
            'mail.from.address' => 'no-reply@example.com',
            'database.default' => 'sqlite',
            'session.secure' => false,
            'queue.default' => 'sync',
            'cache.default' => 'array',
        ]);

        $this->artisan('app:production-check')
            ->expectsOutputToContain('Production readiness failed')
            ->assertFailed();
    }

    public function test_production_check_passes_for_safe_configuration(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'app.url' => 'https://app.billstack.example',
            'mail.default' => 'smtp',
            'mail.from.address' => 'billing@billstack.example',
            'database.default' => 'mysql',
            'session.secure' => true,
            'queue.default' => 'database',
            'cache.default' => 'database',
        ]);

        $this->artisan('app:production-check')
            ->expectsOutputToContain('Production configuration checks passed.')
            ->assertSuccessful();
    }
}
