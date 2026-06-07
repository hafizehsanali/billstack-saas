# BillStack

BillStack is a Laravel-based inventory, billing, and business account management system for small and medium businesses such as general stores, hardware shops, pharmacies, wholesalers, and service-retail businesses.

The public codebase is kept generic and demo-safe. Real client data, deployment secrets, paid modules, and client-specific customizations should stay private.

## Current Features

- Multi-tenant business data separation
- Authentication and role-based access foundation
- Dashboard analytics with sales, profit, expense, invoice, and stock indicators
- Product and category management
- Product stock ledger with sale, purchase, return, and adjustment movements
- Purchase workflow with supplier payments
- Purchase return workflow with supplier ledger credits
- Invoice workflow with customer payments
- POS billing screen
- Sales return workflow with stock restoration
- Customer statements and account payment allocation
- Supplier account ledger and payment allocation
- Expense management
- Alerts Center, currently focused on low-stock alerts
- Sales, stock, low-stock, and profit/loss reports
- Invoice PDF download
- Business settings for invoice branding and company details
- Feature tests for key accounting and inventory flows

## Tech Stack

- PHP 8.3+
- Laravel 13
- Laravel Breeze
- Spatie Laravel Permission
- Tabler UI
- Vite
- ApexCharts
- DomPDF
- PHPUnit

## Local Setup

Clone the repository and install dependencies:

```bash
composer install
npm install
```

Create the environment file:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Set up the database. SQLite is the simplest local option:

```powershell
New-Item database\database.sqlite -ItemType File -Force
php artisan migrate --seed
```

For MySQL/XAMPP, update `.env` with your database name, username, and password, then run:

```bash
php artisan migrate --seed
```

Create the public storage link used by uploaded business assets:

```bash
php artisan storage:link
```

Build frontend assets:

```bash
npm run build
```

Run the app:

```bash
php artisan serve
```

## Demo Login

Seeded demo users:

- `owner@test.com`
- `alpha@example.com`
- `beta@example.com`

Password for seeded demo users:

- `password`

Use these credentials only for local/demo environments. Never use them in production.

## Verification Commands

Run these before committing major changes:

```bash
php artisan route:list --except-vendor
php artisan view:cache
php artisan test --do-not-cache-result
npm run build
```

For production, set `APP_ENV=production`, `APP_DEBUG=false`, a strong generated
`APP_KEY`, the correct `APP_URL`, and the business timezone in `APP_TIMEZONE`.

## Public vs Private Usage

Safe for the public repository:

- Generic application source code
- Migrations and demo seeders
- Tests
- Public README and setup notes
- Screenshots using demo data

Keep private:

- `.env` files
- Real customer, supplier, invoice, or financial data
- API keys, mail credentials, payment keys, and server credentials
- Client-specific custom modules
- Production deployment notes
- Paid modules, licensing, or subscription logic

## Commercial Roadmap

Planned business-ready improvements:

- Customer and supplier ageing reports
- Payment reminders in Alerts Center
- Batch/expiry support for pharmacy workflows
- Receipt and statement print polish
- Audit logs for sensitive changes
- Role permission polish per module
- Deployment and onboarding documentation

## License

This project is currently maintained as a business product foundation. Confirm licensing and commercial usage terms before using it for a client deployment.
