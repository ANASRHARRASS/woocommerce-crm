# woocommerce-crm
crm system  interated with woocommerce 
# WooCommerce CRM Plugin

Lightweight CRM integrated with WooCommerce — collects leads, syncs with third‑party CRMs (HubSpot, Zoho), manages orders and shipping rates, and exposes REST endpoints and shortcodes.

## Features
- Contact & dynamic forms (public shortcodes)
- Admin dashboard + settings
- Integrations: HubSpot, Zoho, Facebook, Instagram, TikTok
- Order management & tracking helpers
- Shipping rates and shipping manager
- REST API endpoints and AJAX handlers
- PHPUnit tests (unit + integration)

## Architecture / Key files
- woocommerce-crm-plugin.php — plugin bootstrap
- src/Core.php — plugin initialization and wiring
- src/Admin/ — admin UI and settings
- src/Public/ — public hooks and shortcodes
- src/Forms/ — Contact, Dynamic and Reseller forms
- src/Integrations/ — HubSpot, Zoho, Social integrations
- src/Orders/, src/Shipping/ — order and shipping logic
- src/Utils/ — helpers and logging
- includes/ — AJAX handlers, cron jobs, REST API compatibility helpers
- assets/ — CSS & JS (admin + public)
- templates/ — PHP templates for admin and public views
- tests/ — PHPUnit tests (bootstrap, unit, integration)

## Development
1. Clone and set remote:
   git clone https://github.com/ANASRHARRASS/woocommerce-crm.git
2. Install dev deps:
   composer install
3. Run tests:
   vendor/bin/phpunit --configuration phpunit.xml

## Contributing
- Use feature branches, open PRs to main
- Run tests and linters locally before PR
- Add unit/integration tests for new features

## License
GPL-2.0-or-later (see LICENSE)

## Contact
Author: ANASRHARRASS