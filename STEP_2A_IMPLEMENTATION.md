# Step 2A Implementation - WooCommerce Order Synchronization

This document describes the implementation of Step 2A which extends the CRM with WooCommerce order synchronization and foundational shipping rates framework.

## Schema Changes (2.2.0)

### Database Migrations
- **Schema version bumped** from `2.0.0` to `2.2.0` in `src/Database/Installer.php`
- **New columns added** to `wccrm_contacts` table:
  - `stage` VARCHAR(40) DEFAULT 'lead' - Contact stage (lead, prospect, customer, etc.)
  - `last_order_id` BIGINT(20) UNSIGNED NULL - Reference to most recent order
  - `total_spent` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 - Total order amount
  - `order_count` INT UNSIGNED DEFAULT 0 - Number of completed orders
  - INDEX on `stage` column added

### New Tables
- **`wccrm_lead_journal`** - Event tracking table with:
  - `contact_id` - Foreign key to contacts
  - `event_type` - Event classification (order_created, order_paid, etc.)
  - `message` - Human-readable event description
  - `meta_data` - JSON metadata
  - `ref_id` - Reference to related entity (order ID, etc.)
  - Indexes on contact_id, event_type, ref_id

## Order Synchronization System

### Core Components

#### OrderSyncService (`src/Orders/OrderSyncService.php`)
- **Contact Linking**: Links orders to contacts via billing email (normalized) or phone
- **Order Processing**: Handles order lifecycle events
- **Contact Updates**: Updates total_spent, order_count, and last_order_id
- **Stage Management**: Auto-promotes leads to customers on first paid order
- **Journal Logging**: Records all order events with metadata

#### OrderHooks (`src/Orders/OrderHooks.php`)
- **WooCommerce Integration**: Hooks into WC order events
- **Event Handlers**:
  - `woocommerce_checkout_order_processed` → order creation
  - `woocommerce_order_status_changed` → status transitions  
  - `woocommerce_order_refunded` → refund processing

#### BackfillRunner (`src/Orders/BackfillRunner.php`)
- **Batch Processing**: Processes existing orders in configurable batches (default 50)
- **Progress Tracking**: Maintains last processed order ID
- **Duplicate Prevention**: Checks journal for existing entries
- **Idempotent**: Safe to run multiple times

#### ToolsPage (`src/Admin/ToolsPage.php`)
- **Admin Interface**: Manual backfill controls
- **Progress Display**: Shows completion status and remaining orders
- **Nonce Protection**: CSRF protection for admin actions

### Journal Events

| Event Type | Trigger | Description |
|------------|---------|-------------|
| `order_created` | Order placement | "Order #123 created – status pending" |
| `order_paid` | Status → processing/completed | "Order #123 paid – status processing" |
| `order_refunded` | Refund processed | "Order #123 refunded amount 10.00" |
| `stage_auto_promote` | First paid order | Auto-promotion to customer stage |

## Shipping Rates Framework

### Architecture

#### Interfaces & DTOs
- **`CarrierInterface`** - Standard carrier contract
- **`ShipmentRequest`** - Request DTO with origin/destination/dimensions
- **`Rate`** - Rate response DTO with pricing and transit time

#### Sample Implementation
- **`SampleCarrier`** - Deterministic test carrier with 3 service levels:
  - Standard: base rate, 5 days transit
  - Express: 2.2x rate, 2 days transit  
  - Overnight: 4.5x rate, 1 day (≤10 lbs only)

#### Services
- **`ShippingCarrierRegistry`** - Carrier registration and discovery
- **`QuoteService`** - Rate aggregation with caching (30min TTL)
- **`ShippingRatesPage`** - Admin testing interface

### Caching Strategy
- **Transient-based**: `_transient_wccrm_shipping_quote_{hash}`
- **TTL**: 1800 seconds (configurable via `wccrm_shipping_quote_ttl` filter)
- **Cache Key**: MD5 hash of serialized request + carrier ID

## Security & Configuration

### CredentialResolver Enhancements
- **Multi-source**: Environment → Options → Filter fallback
- **Service/Key Pattern**: `get($service, $key, $mask)` 
- **Masking**: Shows first 4 chars + "***" when `$mask = true`
- **Example Usage**: `getenv('WCCRM_FEDEX_KEY')` for future integrations

### No Hard-coded Keys
- All carrier implementations reference environment variables
- Example patterns documented in code comments
- Ready for production credential injection

## Admin Integration

### Menu Structure
```
WC CRM (main menu)
├── Dashboard
├── Tools (wccrm-tools)
└── Shipping (wccrm-shipping-rates)
```

### Features
- **Tools Page**: Order backfill with progress tracking
- **Shipping Page**: Live rate testing with form interface
- **Notices**: Success/error feedback for admin actions

## Contact Repository Updates

### Enhanced Methods
- **create()**: Supports new columns (stage, totals, last_order_id)
- **update()**: Dynamic format handling for different data types
- **Backward Compatible**: Existing code continues to work

### Stage Workflow
1. **Lead** (default) - Initial contact creation
2. **Customer** (auto-promoted) - After first paid order
3. **Extensible** - Additional stages can be added

## Internationalization

All user-facing strings wrapped in `__()` and `esc_html__()` functions with 'wccrm' text domain.

## Performance Considerations

- **Prepared Statements**: All database queries use proper escaping
- **Batch Processing**: Configurable batch sizes via `wccrm_backfill_batch_size` filter
- **Caching**: Shipping quotes cached to reduce API calls
- **Indexes**: Strategic database indexes for common queries

## Testing Verification

✅ **Schema Migration**: Version 2.2.0 upgrade path  
✅ **Order Processing**: Contact linking and total calculations  
✅ **Journal Logging**: Event tracking with metadata  
✅ **Shipping Quotes**: Sample carrier returns 3 rates  
✅ **Admin Interface**: Tools and shipping pages render  
✅ **Security**: Credential masking and no hard-coded keys  

## Future Enhancements (Out of Scope)

- Real carrier API integrations (UPS, FedEx, DHL)
- News ingestion system
- Advanced automation rules
- REST API endpoints
- CLI commands

## File Structure

```
src/
├── Admin/
│   ├── ShippingRatesPage.php (NEW)
│   └── ToolsPage.php (NEW)
├── Orders/
│   ├── BackfillRunner.php (NEW)
│   ├── OrderHooks.php (NEW)
│   └── OrderSyncService.php (NEW)
├── Shipping/
│   ├── CarrierInterface.php (NEW)
│   ├── Carriers/SampleCarrier.php (NEW)
│   ├── DTO/Rate.php (NEW)
│   ├── DTO/ShipmentRequest.php (NEW)
│   ├── QuoteService.php (NEW)
│   └── ShippingCarrierRegistry.php (NEW)
├── Contacts/ContactRepository.php (UPDATED)
├── Core/Plugin.php (UPDATED)
├── Database/Installer.php (UPDATED)
└── Security/CredentialResolver.php (UPDATED)
```

Implementation complete and ready for production testing.