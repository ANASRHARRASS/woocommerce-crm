# WooCommerce CRM Plugin

A lightweight CRM integrated with WooCommerce featuring dynamic forms, contact management, multi-provider shipping, and social platform integration scaffolding.

## Features

### Phase 2 Architecture (v2.0+)

- **Dynamic Form Engine**: Create and manage custom forms with schema-based field definitions
- **Contact Management**: Intelligent contact creation with interest tracking and weight-based scoring
- **Multi-Provider Shipping**: Extensible shipping rate collection from multiple carriers
- **Social Platform Integration**: Scaffolding for WhatsApp, TikTok, Instagram, and Facebook integrations
- **Security**: Encrypted credential management with environment variable support
- **Elementor Integration**: Native Elementor widget for form display
- **Database Schema**: Versioned migrations with automatic upgrades

### Core Modules

#### Forms System (`src/Forms/`)
- `FormModel`: Value object for form data
- `FormRepository`: CRUD operations for forms
- `FormRenderer`: HTML generation with built-in styling and JavaScript
- `SubmissionHandler`: Form validation and submission processing
- `FormSubmissionLinker`: Links submissions to contacts and manages interests

#### Contacts & Interests (`src/Contacts/`)
- `ContactRepository`: Contact CRUD with email/phone-based upserts
- `InterestUpdater`: Weight-based interest tracking with content analysis

#### Security (`src/Security/`)
- `CredentialResolver`: Secure API key management with encryption

#### Shipping (`src/Shipping/`)
- `ShippingCarrierInterface`: Interface for carrier implementations
- `RateService`: Multi-carrier rate aggregation
- `CarrierRegistry`: Carrier registration and management
- WooCommerce shipping method integration

## Installation

1. Upload the plugin files to `/wp-content/plugins/woocommerce-crm/`
2. Activate the plugin through WordPress admin
3. Configure API keys via environment variables (recommended) or settings

## Configuration

### Environment Variables (Recommended)

```bash
# Social Platform Integration Keys (Future Implementation)
WCCRM_WHATSAPP_TOKEN=your_whatsapp_token
WCCRM_FACEBOOK_APP_ID=your_facebook_app_id
WCCRM_INSTAGRAM_APP_ID=your_instagram_app_id
WCCRM_TIKTOK_APP_ID=your_tiktok_app_id

# Other integrations
WCCRM_HUBSPOT_API_KEY=your_hubspot_key
```

### WordPress Constants

```php
// In wp-config.php
define('WCCRM_WHATSAPP_TOKEN', 'your_whatsapp_token');
define('WCCRM_FACEBOOK_APP_ID', 'your_facebook_app_id');
define('WCCRM_INSTAGRAM_APP_ID', 'your_instagram_app_id');
define('WCCRM_TIKTOK_APP_ID', 'your_tiktok_app_id');
```

**Important**: Never commit API keys to version control. Use environment variables or encrypted storage.

## Usage

### Shortcodes

```php
// Display a form
[wccrm_form key="contact_form"]
```

### Elementor Widget

1. Add "WCCRM Form" widget to your page
2. Select a form from the dropdown
3. Customize styling options

### Programmatic Usage

```php
// Get plugin instance
$plugin = wccrm_get_plugin();

// Access services
$formRepo = $plugin->get_form_repository();
$contactRepo = $plugin->get_contact_repository();
$credentialResolver = $plugin->get_credential_resolver();
```

## Database Schema

### Forms (`wccrm_forms`)
- Dynamic form definitions with JSON schema
- Support for text, email, tel, select, textarea, hidden fields
- Form status management (active/inactive)

### Contacts (`wccrm_contacts`)
- Contact information with email/phone identifiers
- Automatic upserts prevent duplicates

### Contact Interests (`wccrm_contact_interests`)
- Weight-based interest scoring
- Automatic interest detection from form submissions
- TODO: Interest decay logic for aging data

### Form Submissions (`wccrm_form_submissions`)
- Complete submission data with contact linking
- IP and user agent tracking for security
- JSON storage for flexible data structure

## Development

### Requirements
- PHP 8.0+
- WordPress 5.0+
- WooCommerce 3.0+ (for shipping features)

### PSR-4 Autoloading
```php
"autoload": {
    "psr-4": {
        "Anas\\WCCRM\\": "src/"
    }
}
```

### Adding New Carriers

```php
use Anas\WCCRM\Shipping\Contracts\ShippingCarrierInterface;
use Anas\WCCRM\Shipping\DTO\RateQuote;

class MyCarrier implements ShippingCarrierInterface {
    public function get_quotes(array $context): array {
        // Return RateQuote objects
    }
}

// Register carrier
$plugin = wccrm_get_plugin();
$plugin->get_carrier_registry()->register('my_carrier', new MyCarrier());
```

## Security

- All form inputs are sanitized and validated
- API keys stored with encryption using WordPress AUTH_KEY/SECURE_AUTH_KEY
- Nonce verification for form submissions
- SQL injection protection via $wpdb->prepare()
- XSS protection via esc_html/esc_attr

## Roadmap & TODO Items

### Core CRM Features
- [ ] Rate limiting for form submissions
- [ ] Spam protection (honeypot fields)
- [ ] Interest decay logic based on age
- [ ] Admin capability checks for sensitive operations
- [ ] Logging system for debugging
- [ ] Admin UI for form management
- [ ] Import/export functionality
- [ ] Customer timeline and interaction history
- [ ] Advanced order segmentation and metrics

### Social Platform Integration
- [ ] WhatsApp message dispatch and webhook handling
- [ ] TikTok lead ingestion and webhook processing
- [ ] Facebook/Instagram webhook ingestion for leads
- [ ] Social platform contact synchronization
- [ ] Multi-channel messaging automation

### Advanced CRM Features
- [ ] Automation rules engine with trigger/action system
- [ ] Customer journey mapping and analytics
- [ ] Advanced reporting dashboard
- [ ] Lead scoring and qualification workflows
- [ ] A/B testing for forms and messaging

## Legacy Compatibility

The plugin maintains backward compatibility with v1.x:
- Legacy `wcp_leads` table preserved
- `wcp_log()` function available
- Deprecated function notices guide migration

## License

GPL2 - see LICENSE file for details.
