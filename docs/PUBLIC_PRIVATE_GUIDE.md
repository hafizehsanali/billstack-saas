# Public and Private Project Guide

BillStack can keep a public generic codebase while private business deployments hold client-specific configuration, data, and custom modules. Keep these boundaries clear before publishing code or onboarding a real business.

## Public Repository

The public repository may include:

- Generic application code
- Migrations
- Demo seeders
- Tests
- README and setup documentation
- Screenshots or videos that use demo data
- Generic roadmap items

## Private Material

Do not commit:

- `.env` files
- Real database exports
- Real customer, supplier, invoice, payment, or expense records
- API keys, mail credentials, payment gateway credentials, or server passwords
- Client contracts, private pricing, or deployment credentials
- Client-specific custom modules
- Paid/licensed modules

## Demo Data Rules

Seeders must use fake or clearly marked demo data. Demo passwords must never be used in production. Before recording screenshots or videos, confirm that only demo records are visible.

## Commercial Deployment Rules

For real businesses, keep deployment configuration, production backups, credentials, and client customizations in private storage or a private repository. The public project should stay generic and safe to inspect.
