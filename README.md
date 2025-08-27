# WooCommerce CRM Plugin

A lightweight CRM integrated with WooCommerce featuring dynamic forms, contact management, multi-provider shipping, and news aggregation.

## Features

### Phase 2 Architecture (v2.0+) - Enhanced Scaffolding

**Phase 2A - Orders**: Order synchronization, metrics calculation, and historical backfill management
**Phase 2B - Forms/Consent**: Form versioning and GDPR-compliant consent tracking  
**Phase 2C - Messaging**: Multi-channel messaging (email, SMS, WhatsApp) with templates and consent management
**Phase 2D - Social Leads**: Social media platform webhook integration and lead normalization
**Phase 2E - COD Workflow**: Cash-on-delivery verification and workflow management
**Phase 2F - Automation**: Rule-based automation engine with conditions and actions
**Phase 2G - Security/Compliance**: Audit logging, data retention, and GDPR erasure services
**Phase 2H - Reporting**: Metrics aggregation and dashboard functionality

- **Dynamic Form Engine**: Create and manage custom forms with schema-based field definitions
- **Contact Management**: Intelligent contact creation with interest tracking and weight-based scoring
- **Multi-Provider Shipping**: Extensible shipping rate collection from multiple carriers
- **Enhanced News Aggregation**: Multi-provider news feed aggregation with rate limiting and caching (NewsAPI, GNews, RSS)
- **Security**: Encrypted credential management with environment variable support - NO API KEYS IN CODE
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

#### News (`src/News/`)
- `NewsProviderInterface`: Interface for news providers
- `Aggregator`: Multi-provider article collection with deduplication
- Stub providers for NewsAPI, GNews, and Generic RSS

## Installation

1. Upload the plugin files to `/wp-content/plugins/woocommerce-crm/`
2. Activate the plugin through WordPress admin
3. Configure API keys via environment variables (recommended) or settings

## Configuration

### Environment Variables (Recommended)

```bash
# News API Keys
WCCRM_NEWSAPI_KEY=your_newsapi_key
WCCRM_GNEWS_KEY=your_gnews_key

# RSS Feed URL
WCCRM_RSS_FEED_URL=https://example.com/feed.xml

# Other integrations
WCCRM_HUBSPOT_API_KEY=your_hubspot_key
```

### WordPress Constants

```php
// In wp-config.php
define('WCCRM_NEWSAPI_KEY', 'your_newsapi_key');
define('WCCRM_GNEWS_KEY', 'your_gnews_key');
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

// Debug news aggregation
do_action('wccrm_debug_fetch_news');
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

### Adding New News Providers

```php
use Anas\WCCRM\News\Contracts\NewsProviderInterface;
use Anas\WCCRM\News\DTO\Article;

class MyNewsProvider implements NewsProviderInterface {
    public function fetch(array $params): array {
        // Return Article objects
    }
}

// Register provider
$plugin = wccrm_get_plugin();
$plugin->get_news_aggregator()->getProviderRegistry()->register('my_provider', new MyNewsProvider());
```

## Security

- All form inputs are sanitized and validated
- API keys stored with encryption using WordPress AUTH_KEY/SECURE_AUTH_KEY
- Nonce verification for form submissions
- SQL injection protection via $wpdb->prepare()
- XSS protection via esc_html/esc_attr

## TODO Items

- [ ] Rate limiting for form submissions
- [ ] Spam protection (honeypot fields)
- [ ] Interest decay logic based on age
- [ ] Admin capability checks for sensitive operations
- [ ] News article caching for performance
- [ ] Logging system for debugging
- [ ] Admin UI for form management
- [ ] Import/export functionality

## Legacy Compatibility

The plugin maintains backward compatibility with v1.x:
- Legacy `wcp_leads` table preserved
- `wcp_log()` function available
- Deprecated function notices guide migration

## License

GPL2 - see LICENSE file for details.
