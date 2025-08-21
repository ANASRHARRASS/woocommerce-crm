# Architecture

## Components
- Bootstrap: woocommerce-crm-plugin.php -> initializes src/Core.php
- Core: registers hooks, loads admin/public, integrations
- Admin: settings page, dashboard (src/Admin)
- Public: shortcodes, forms, templates (src/Public, templates/public)
- Integrations: HubSpot, Zoho, Social (src/Integrations)
- Background: cron jobs & scheduled syncs (includes/cron-jobs.php)
- API: REST endpoints (includes/rest-api.php)

## Data flow
1. User submits form -> src/Forms/* validates -> stores lead -> optionally syncs to integrations via src/Integrations/*
2. Orders handled in src/Orders/* and optionally sent to CRM/providers

## Notes
- Where secrets live: .env (ignored)
- DB schema / custom tables: document if any