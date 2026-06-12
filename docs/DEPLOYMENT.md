# Production Deployment

## Required Environment

Use PHP 8.3+, MySQL or PostgreSQL, HTTPS, a production mail provider, and a
process manager for the queue worker.

Set these values before the first deployment:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_TIMEZONE=Asia/Karachi

DB_CONNECTION=mysql

MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=billing@your-domain.example

SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Never copy a local `APP_KEY` to production. Generate the production key once
and keep it unchanged after users and encrypted data exist.

## Deploy Commands

Run these commands from the release directory:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan app:production-check
php artisan app:backup-database
php artisan mail:test owner@your-domain.example
```

Do not run `db:seed` against a live database.

## Required Processes

Run the queue worker under Supervisor, systemd, or the hosting provider:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Configure cron to run the scheduler every minute:

```cron
* * * * * cd /path/to/billstack && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler pauses expired subscriptions. Staff invitations and password
reset messages require correctly configured mail delivery. It also creates a
database backup daily at 01:00 and retains backups according to
`BACKUP_RETENTION_DAYS`.

Store backups outside the public web directory and copy them to separate
storage. For critical error alerts, add `slack` to `LOG_STACK` and configure
`LOG_SLACK_WEBHOOK_URL`, or connect the same log channel to your monitoring
provider.

## Verification

After deployment:

```bash
php artisan app:production-check
php artisan migrate:status
php artisan schedule:list
php artisan queue:monitor default:100
```

Confirm that `/up` returns HTTP 200, then test registration, subscription
checkout, staff invitation email, invoice creation, and PDF download.

## Rollback

Keep the previous release directory and database backup until verification is
complete. Roll application code back first. Roll back a migration only after
reviewing whether it removes or transforms production data.
