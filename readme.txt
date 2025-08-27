=== WooCommerce CRM Plugin ===
Contributors: [Your Name]
Tags: crm, wooCommerce, lead capture, forms, hubspot, zoho, facebook, tiktok, whatsapp, google-drive
Requires at least: 5.0
Tested up to: 6.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Lightweight CRM integrated with WooCommerce — collects leads, syncs with HubSpot & Zoho, ingests social/ads leads, optional Google Drive export, WhatsApp notification stubs.

== Features ==
- Lead & dynamic forms (shortcodes)
- WooCommerce order -> lead conversion
- Admin leads list + pagination
- REST API (wcp/v1/leads) with API key header (X-WCP-Key)
- Integrations (stubs): HubSpot, Zoho, Google Drive (export), WhatsApp (notify), Social ingestion hooks
- Modular integration loader
- Secure option storage (tokens stored in wcp_tokens option)
- API key management (Settings)

== Roadmap ==
- Implement real API calls (current: stubs/placeholders)
- Add OAuth/token management UI
- Queue + retry system for external sync
- Real-time WhatsApp notifications via provider (e.g., Cloud API/Twilio) – not included yet
- Bulk export & import tools
- Advanced reporting dashboard

== Installation ==
1. Upload the plugin folder to /wp-content/plugins/.
2. Activate via Plugins screen.
3. Configure settings in the WooCommerce CRM menu.

== Usage ==
Add shortcodes for forms (see forthcoming docs). Configure API keys via settings page (never hardcode secrets).
REST create lead: POST /wp-json/wcp/v1/leads  Header: X-WCP-Key: (Settings API key)
Body (JSON): {"email":"john@example.com","name":"John","source":"api","payload":{"foo":"bar"}}

== Development Notes ==
- Namespace alignment in progress; ensure src/ matches autoload declaration.
- Do not commit secrets; use .env + wp config constants.
- Add tests in tests/Unit and tests/Integration.

== Changelog ==
= 1.0.0 =
* Initial public structure (forms + integrations scaffolding)

== License ==
GPL-2.0-or-later

== Support ==
Open issues on GitHub repository.