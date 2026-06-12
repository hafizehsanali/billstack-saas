<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProductionReadinessCheck extends Command
{
    protected $signature = 'app:production-check';

    protected $description = 'Check whether the application configuration is safe for production deployment';

    public function handle(): int
    {
        $checks = [
            ['Production environment', config('app.env') === 'production', 'Set APP_ENV=production.'],
            ['Debug mode disabled', ! config('app.debug'), 'Set APP_DEBUG=false.'],
            ['Application key configured', filled(config('app.key')), 'Run php artisan key:generate.'],
            [
                'HTTPS application URL',
                str_starts_with((string) config('app.url'), 'https://'),
                'Set APP_URL to the public HTTPS address.',
            ],
            [
                'Production mail transport',
                ! in_array(config('mail.default'), ['log', 'array'], true),
                'Configure SMTP, Resend, Postmark, SES, or another production mailer.',
            ],
            [
                'Real sender address',
                filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)
                    && ! str_ends_with((string) config('mail.from.address'), '@example.com'),
                'Set MAIL_FROM_ADDRESS to an address on the production domain.',
            ],
            [
                'Production database',
                config('database.default') !== 'sqlite',
                'Use MySQL or PostgreSQL for the hosted multi-tenant service.',
            ],
            [
                'Secure session cookie',
                config('session.secure') === true,
                'Set SESSION_SECURE_COOKIE=true when HTTPS is enabled.',
            ],
            [
                'Persistent queue',
                config('queue.default') !== 'sync',
                'Use the database or Redis queue and run a queue worker.',
            ],
            [
                'Persistent cache',
                ! in_array(config('cache.default'), ['array', 'null'], true),
                'Use the database or Redis cache.',
            ],
        ];

        $rows = collect($checks)->map(fn (array $check) => [
            $check[0],
            $check[1] ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
            $check[1] ? '' : $check[2],
        ])->all();

        $this->table(['Check', 'Status', 'Required action'], $rows);
        $this->newLine();
        $this->line('Runtime requirements: run a queue worker and php artisan schedule:run every minute.');

        $failureCount = collect($checks)->filter(fn (array $check) => ! $check[1])->count();

        if ($failureCount > 0) {
            $this->error("Production readiness failed with {$failureCount} issue(s).");

            return self::FAILURE;
        }

        $this->info('Production configuration checks passed.');

        return self::SUCCESS;
    }
}
