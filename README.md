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

## Overview
Lightweight CRM integrated with WooCommerce. Collects leads, syncs with HubSpot/Zoho, manages orders and shipping rates, provides REST endpoints and shortcodes.

## Where to look (entry points)
- Plugin bootstrap: woocommerce-crm-plugin.php
- Core wiring: src/Core.php
- Admin UI: src/Admin/
- Public shortcodes/forms: src/Public/, templates/public/
- Integrations: src/Integrations/
- Includes: includes/rest-api.php, includes/ajax-handlers.php, includes/cron-jobs.php

## Tested environments
- WordPress: specify version (e.g. 6.x)
- WooCommerce: specify version
- PHP: specify version(s), e.g. 8.1

## Development notes
- Never commit API keys — use environment variables.
- Tests: see tests/ and phpunit.xml

## Tasks for AI/reviewer
1. Security audit (DB writes, nonce, sanitization/escaping, capability checks).  
2. Bug fixes and code style suggestions.  
3. Add/verify unit & integration tests.  
4. Performance and WP best-practices recommendations.

## Contributing
- Use feature branches, open PRs to main
- Run tests and linters locally before PR
- Add unit/integration tests for new features

## License
GPL-2.0-or-later (see LICENSE)

## Contact
Author: ANASRHARRASS

