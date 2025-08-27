# News Module - Development Notes

This document explains the multi-provider News module architecture, implementation details, and extension guidelines.

## Architecture Overview

The News module is designed to aggregate content from multiple news providers to avoid single API limitations and rate limits. It features:

- **Multi-provider support**: NewsAPI, GNews, Generic RSS
- **Rate limiting**: Per-provider request throttling
- **Caching**: Configurable TTL per provider
- **Credential management**: Secure key resolution
- **Deduplication**: URL-based article deduplication
- **Extensibility**: Easy addition of new providers

## Core Components

### ProviderRegistry
- **File**: `src/News/ProviderRegistry.php`
- **Purpose**: Central registry for news providers
- **Key Methods**:
  - `register(string $key, NewsProviderInterface $provider)`: Register a provider
  - `list_enabled()`: Get all enabled providers
  - `get(string $key)`: Get specific provider

### RateLimiter
- **File**: `src/News/RateLimiter.php`  
- **Purpose**: Prevent API rate limit violations
- **Features**:
  - Per-provider rate tracking
  - Minute-based rate windows
  - Configurable limits per provider
  - Automatic limit reset
- **Storage**: WordPress transients

### Aggregator
- **File**: `src/News/Aggregator.php`
- **Purpose**: Unified news fetching with caching and rate limiting
- **Features**:
  - Multi-provider aggregation
  - Per-provider caching with individual TTLs
  - Rate limit enforcement
  - Article deduplication by URL hash
  - Date-based sorting (newest first)
  - Configurable result limits

### NewsProviderInterface
- **File**: `src/News/Contracts/NewsProviderInterface.php`
- **Required Methods**:
  - `fetch(array $params): array`: Fetch articles
  - `get_key(): string`: Provider identifier
  - `get_name(): string`: Display name
  - `is_enabled(): bool`: Check if configured
  - `get_cache_ttl(): int`: Cache duration in seconds
  - `get_rate_limit(): int`: Requests per minute limit

### Article DTO
- **File**: `src/News/DTO/Article.php`
- **Properties**:
  - `id`: Unique identifier (URL hash)
  - `title`: Article title
  - `url`: Article URL
  - `source`: Source name
  - `published_at`: Publication date
  - `raw`: Original API response data

## Providers

### AbstractHttpProvider
- **File**: `src/News/Providers/AbstractHttpProvider.php`
- **Purpose**: Base class for HTTP-based providers
- **Features**:
  - Common HTTP request handling
  - Error logging and graceful failures
  - JSON response parsing
  - Article normalization framework

### NewsAPI Provider
- **File**: `src/News/Providers/NewsApiProvider.php`
- **Credential Key**: `NEWSAPI_KEY`
- **Rate Limit**: 50 requests/minute (free tier)
- **Cache TTL**: 30 minutes
- **API Endpoint**: `https://newsapi.org/v2/everything`

### GNews Provider
- **File**: `src/News/Providers/GNewsProvider.php`
- **Credential Key**: `GNEWS_KEY`
- **Rate Limit**: 100 requests/minute
- **Cache TTL**: 1 hour
- **API Endpoint**: `https://gnews.io/api/v4/search`

### Generic RSS Provider
- **File**: `src/News/Providers/GenericRssProvider.php`
- **Credential Key**: `RSS_FEED_URL`
- **Rate Limit**: 30 requests/minute (conservative)
- **Cache TTL**: 2 hours
- **Purpose**: Support any RSS/XML feed

## Credential Management

### Credential Keys
All credentials are resolved through the existing `CredentialResolver` with these keys:
- `WCCRM_NEWSAPI_KEY`: NewsAPI API key
- `WCCRM_GNEWS_KEY`: GNews API key  
- `WCCRM_RSS_FEED_URL`: RSS feed URL

### Resolution Order
1. Environment variables (`$_ENV`, `getenv()`)
2. WordPress constants (`WCCRM_NEWSAPI_KEY`)
3. Encrypted options (via `CredentialResolver`)

### Security
- No API keys stored in code
- No sensitive data logged (only generic error messages)
- All credential access goes through `CredentialResolver`

## Caching Strategy

### Per-Provider Caching
- Each provider defines its own TTL via `get_cache_ttl()`
- Cache keys include provider-specific parameters
- Aggregator uses minimum TTL when caching combined results

### Cache Keys
- Format: `wccrm_news_{md5(json_encode($params))}`
- Include query parameters, limits, language settings
- Exclude cache control flags from key generation

### Cache Storage
- Uses WordPress transients (`get_transient()`, `set_transient()`)
- Automatic expiration handling
- Manual cache clearing via `Aggregator::clear_cache()`

## Rate Limiting

### Implementation
- Tracks requests per provider per minute
- Uses sliding window approach
- Stores in WordPress transients with 60-second expiration
- Automatically resets at minute boundaries

### Configuration
- Each provider defines its rate limit via `get_rate_limit()`
- Limits are enforced before API requests
- Failed rate limit checks are logged but don't stop other providers

### Monitoring
- `get_remaining_requests()` shows current quota
- `clear_provider_limits()` for manual reset
- Error logging for rate limit violations

## Error Handling

### Graceful Degradation
- Provider failures don't stop other providers
- Empty results returned on errors
- Comprehensive error logging without exposing credentials

### Error Types
- Network/HTTP errors
- API response errors
- JSON parsing errors
- Rate limit violations
- Authentication failures

### Logging
- All errors logged via `error_log()`
- Generic messages to avoid credential exposure
- Include provider name and error type
- No raw API responses in logs

## Usage Examples

### Basic Aggregation
```php
$plugin = \Anas\WCCRM\Core\Plugin::instance();
$aggregator = $plugin->get_news_aggregator();

$articles = $aggregator->fetch([
    'query' => 'technology',
    'limit' => 20,
    'language' => 'en'
]);
```

### Provider-Specific Fetching
```php
$articles = $aggregator->fetch_from_provider('newsapi', [
    'query' => 'business',
    'limit' => 10
]);
```

### Cached Fetching
```php
$articles = $aggregator->fetch([
    'query' => 'sports',
    'limit' => 15,
    'use_cache' => true
]);
```

## Extension Guide

### Adding New Providers

1. **Create Provider Class**:
```php
class CustomNewsProvider extends AbstractHttpProvider {
    public function fetch(array $params): array {
        // Implementation
    }
    
    public function get_key(): string {
        return 'custom_news';
    }
    
    public function get_name(): string {
        return 'Custom News API';
    }
    
    public function is_enabled(): bool {
        return !empty($this->credentialResolver->get('CUSTOM_NEWS_KEY'));
    }
}
```

2. **Register Provider**:
```php
$plugin->get_news_aggregator()
    ->get_provider_registry()
    ->register('custom_news', new CustomNewsProvider($credentialResolver));
```

3. **Add Credential Key**:
- Add `WCCRM_CUSTOM_NEWS_KEY` to credential resolution
- Update environment/constant documentation

### Custom Parameters
- Extend `normalize_article_data()` for custom fields
- Override `get_cache_ttl()` and `get_rate_limit()` as needed
- Add custom validation in `validate_message()` if needed

### Testing Providers
- Use `test_connection()` method for connectivity checks
- Implement provider-specific validation
- Test rate limiting and caching behavior

## Performance Considerations

### Optimization
- Parallel provider requests (future enhancement)
- Database caching for high-traffic sites
- CDN integration for article images
- Background refresh for cache warming

### Monitoring
- Track provider response times
- Monitor cache hit rates
- Alert on rate limit violations
- Track error rates by provider

### Scalability
- Consider Redis for caching at scale
- Implement circuit breakers for failing providers
- Add provider health checks
- Use background jobs for bulk operations

## Configuration Options

### Provider Settings
- Enable/disable individual providers
- Custom rate limits per provider
- Provider-specific parameters
- Failover priorities

### Caching Settings
- Global cache enable/disable
- Custom TTL overrides
- Cache size limits
- Cache warming schedules

### Content Filtering
- Keyword filtering
- Source blacklisting
- Content quality scoring
- Duplicate detection sensitivity

## Future Enhancements

### Planned Features
- Real-time news notifications
- Content categorization/tagging
- Sentiment analysis integration
- Multi-language support improvements
- Advanced search capabilities

### Integration Opportunities
- WordPress post auto-creation
- Social media sharing
- Email newsletter integration
- Analytics and reporting
- Content recommendation engine

## Troubleshooting

### Common Issues
1. **No articles returned**: Check provider credentials and API limits
2. **Rate limit errors**: Review request frequency and limits
3. **Cache issues**: Clear cache and check transient storage
4. **Provider errors**: Check API status and credential validity

### Debug Tools
- Enable WordPress debug logging
- Use `debug_fetch_news` action for testing
- Check provider `is_enabled()` status
- Monitor rate limiter state

### Support Resources
- Provider API documentation
- WordPress transient debugging
- Error log analysis
- Performance profiling tools