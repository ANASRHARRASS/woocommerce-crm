<!-- Removed duplicate developer doc. Use readme.txt only. -->

Public-facing plugin description lives in readme.txt.

Key directories:
- Bootstrap: woocommerce-crm-plugin.php
- Core loader: src/Core.php
- Admin UI: src/Admin
- Public/forms: src/Public, src/Forms, templates/
- Integrations: src/Integrations
- Orders/Shipping: src/Orders, src/Shipping
- Utilities: src/Utils
- REST/AJAX/Cron: includes/
- Tests: tests/

Current tasks:
- Flesh out Core service loading (admin/public/integrations)
- Add secure form handling (nonce, sanitize, escape)
- Implement custom tables (if needed) with upgrade routine
- Add REST routes with permission callbacks
- Increase test coverage (init, forms, integration stubs)

Security checklist (quick):
- Always capability check admin postbacks
- Nonce verify form + AJAX
- Escape output (esc_html/attr/url)
- Sanitize all input before DB writes
- Use prepared statements or WP wrappers

See CLEANUP_NOTES.md for recent maintenance actions.

