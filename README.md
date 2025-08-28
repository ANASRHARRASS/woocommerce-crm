# WooCommerce CRM Plugin

A lightweight CRM integrated with WooCommerce featuring dynamic forms, contact management, multi-provider shipping, and news aggregation.

## Features

### Phase 2 Architecture (v2.0+)

- **Dynamic Form Engine**: Create and manage custom forms with schema-based field definitions
- **Contact Management**: Intelligent contact creation with interest tracking and weight-based scoring
- **Multi-Provider Shipping**: Extensible shipping rate collection from multiple carriers
- **News Aggregation**: Multi-provider news feed aggregation (scaffold for NewsAPI, GNews, RSS)
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

## v0.5.0 Enhanced Features

### Provider Framework
- **Unified Provider Architecture**: Abstract base classes for News and Shipping providers with normalized response formats
- **Provider Registry**: Central service managing both news and shipping providers
- **Normalized Data**: Guaranteed response keys (id, title, url, source, published_at for news; provider, service, cost, currency, eta for shipping)

### Secrets Management
- **KS_CRM\Config\Secrets**: Central credential lookup without storing secrets
- **Multiple Sources**: Checks defined constants, environment variables, and filter hooks
- **Admin Notices**: Dismissible notices for missing API keys per user session

### Caching System
- **Namespace Support**: `KS_CRM\Cache\Cache_Manager::remember()` with namespace-specific TTL
- **Filter Integration**: `kscrm_cache_ttl_news`, `kscrm_cache_ttl_shipping` filters
- **Management Tools**: Clear cache by namespace or globally

### Rate Limiting
- **Public Endpoint Protection**: IP-based throttling (default: 30 requests / 10 minutes)
- **Transient Storage**: Uses `kscrm_rl_{endpoint}_{hash}` pattern
- **Configurable**: Adjustable limits per endpoint

### Data Export & Tools
- **REST Endpoints**: `/ks-crm/v1/export/{leads,utm,news}` with `manage_woocommerce` permission
- **CSV Export**: Memory-safe streaming with `KS_CRM\Exports\CSV_Writer`
- **Tools Admin Page**: New "Woo CRM > Tools" submenu for exports and cache management
- **Multiple Formats**: CSV and JSON export options

### Data Retention
- **Configurable Cleanup**: Settings for leads (months, default 24) and UTM stats (days, default 365)
- **Automated Purging**: Daily wp_cron event for old data cleanup
- **Manual Control**: CLI commands and tools page controls

### CLI Commands (wp-cli)
```bash
wp kscrm export leads [--path=<file>] [--format=csv|json] [--limit=<num>]
wp kscrm export utm [--format=csv|json]
wp kscrm export news [--format=json]
wp kscrm stats recalc [--batch-size=<num>] [--dry-run]
wp kscrm cache clear [<namespace>]
wp kscrm retention run|status|config [--leads-months=<num>] [--utm-days=<num>]
```

### Dashboard Enhancements
- **Date Range Selector**: 7/30/90 days with user preference persistence
- **New Metrics**: AOV, Repeat Purchase Rate, Top Product, Returning Customers %
- **Additional Charts**: Revenue vs AOV line chart, Returning vs New Customers stacked bar
- **Real-time Updates**: AJAX-powered dashboard refresh

### Security Features
- **Uninstall Safeguards**: Data removal only with `KSCRM_REMOVE_ALL_DATA` constant
- **Honeypot Protection**: Hidden fields + timestamp validation (min 3 seconds)
- **Form Security**: Nonces, rate limiting, and spam detection
- **Input Validation**: All new inputs escaped, sanitized, and validated

### WhatsApp Integration
- **Template System**: Filterable templates for common scenarios
- **Available Filters**:
  - `kscrm_whatsapp_template_cart_share`
  - `kscrm_whatsapp_template_order_confirm`
  - `kscrm_whatsapp_template_product_inquiry`
  - `kscrm_whatsapp_template_shipping_update`
  - `kscrm_whatsapp_template_abandoned_cart`
  - `kscrm_whatsapp_template_promotional`
- **URL Generation**: `wa.me` and `web.whatsapp.com` link creation

### Configuration Constants
```php
define( 'KSCRM_CACHE_DEFAULT_TTL', 3600 ); // Default cache TTL
define( 'KSCRM_REMOVE_ALL_DATA', true );   // Enable data removal on uninstall
define( 'NEWSAPI_KEY', 'your-key' );       // API keys as constants
define( 'SHIPPING_API_KEY', 'your-key' );  // Provider credentials
```

### Filter Hooks
```php
// Cache TTL per namespace
add_filter( 'kscrm_cache_ttl_news', function() { return 7200; } );
add_filter( 'kscrm_cache_ttl_shipping', function() { return 1800; } );

// Secret lookup override
add_filter( 'kscrm_secret_lookup', function( $value, $key ) {
    return $your_custom_lookup_logic;
}, 10, 2 );

// WhatsApp template customization
add_filter( 'kscrm_whatsapp_template_cart_share', function( $template ) {
    return 'Your custom cart share message: {cart_items}';
} );

// Rate limiting IP whitelist
add_filter( 'kscrm_honeypot_ip_whitelist', function( $ips ) {
    $ips[] = '192.168.1.100';
    return $ips;
} );
```

### API Integration Examples
```php
// Use secrets helper
$api_key = \KS_CRM\Config\Secrets::get( 'NEWSAPI_KEY' );

// Cache with namespace
$data = \KS_CRM\Cache\Cache_Manager::remember( 'news', 'latest', 3600, function() {
    return fetch_news_from_api();
} );

// Export data programmatically
$export_url = rest_url( 'ks-crm/v1/export/leads?format=csv' );

// WhatsApp message generation
$message = \KS_CRM\WhatsApp\Templates::get_order_confirm_template( [
    'order_number' => '1234',
    'order_total' => '$99.99',
    'order_status' => 'Processing'
] );
```

## Legacy Compatibility

The plugin maintains backward compatibility with v1.x:
- Legacy `wcp_leads` table preserved
- `wcp_log()` function available
- Deprecated function notices guide migration

## License

GPL2 - see LICENSE file for details.
