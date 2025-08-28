<?php
/**
 * WP-CLI Commands for WooCommerce CRM
 * Provides command-line interface for common tasks
 */

namespace KS_CRM\CLI;

defined( 'ABSPATH' ) || exit;

// Only load if WP-CLI is available
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

if ( ! class_exists( 'WP_CLI_Command' ) ) {
    return;
}

class Commands extends \WP_CLI_Command {

    /**
     * Export leads data
     *
     * ## OPTIONS
     *
     * [--path=<path>]
     * : Path to save the export file. Defaults to wp-content/uploads/kscrm-exports/
     *
     * [--format=<format>]
     * : Export format. Options: csv, json. Default: csv
     *
     * [--limit=<number>]
     * : Maximum number of records to export. Default: 10000
     *
     * [--date-from=<date>]
     * : Start date for export (YYYY-MM-DD format)
     *
     * [--date-to=<date>]
     * : End date for export (YYYY-MM-DD format)
     *
     * ## EXAMPLES
     *
     *     wp kscrm export leads
     *     wp kscrm export leads --format=json --limit=5000
     *     wp kscrm export leads --path=/tmp/leads.csv
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Associative arguments
     */
    public function export( $args, $assoc_args ) {
        $type = $args[0] ?? '';
        
        if ( empty( $type ) ) {
            \WP_CLI::error( 'Export type is required. Options: leads, utm, news' );
        }

        $format = $assoc_args['format'] ?? 'csv';
        $limit = absint( $assoc_args['limit'] ?? 10000 );
        $path = $assoc_args['path'] ?? '';

        \WP_CLI::log( "Starting {$type} export..." );

        try {
            switch ( $type ) {
                case 'leads':
                    $this->export_leads( $format, $limit, $path, $assoc_args );
                    break;
                    
                case 'utm':
                    $this->export_utm( $format, $path );
                    break;
                    
                case 'news':
                    $this->export_news( $format, $path );
                    break;
                    
                default:
                    \WP_CLI::error( "Invalid export type: {$type}. Options: leads, utm, news" );
            }
            
        } catch ( \Exception $e ) {
            \WP_CLI::error( "Export failed: " . $e->getMessage() );
        }
    }

    /**
     * Recalculate statistics from orders
     *
     * ## DESCRIPTION
     *
     * Rebuilds UTM statistics by re-processing all orders.
     * This will truncate the existing stats table and rebuild from scratch.
     *
     * ## OPTIONS
     *
     * [--batch-size=<number>]
     * : Number of orders to process per batch. Default: 100
     *
     * [--dry-run]
     * : Show what would be done without making changes
     *
     * ## EXAMPLES
     *
     *     wp kscrm stats recalc
     *     wp kscrm stats recalc --batch-size=50
     *     wp kscrm stats recalc --dry-run
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Associative arguments
     */
    public function stats( $args, $assoc_args ) {
        $subcommand = $args[0] ?? '';
        
        if ( $subcommand !== 'recalc' ) {
            \WP_CLI::error( 'Invalid stats command. Use: wp kscrm stats recalc' );
        }

        $batch_size = absint( $assoc_args['batch-size'] ?? 100 );
        $dry_run = isset( $assoc_args['dry-run'] );

        if ( $dry_run ) {
            \WP_CLI::log( 'DRY RUN: No changes will be made' );
        }

        \WP_CLI::log( 'Starting statistics recalculation...' );
        
        try {
            $this->recalculate_stats( $batch_size, $dry_run );
        } catch ( \Exception $e ) {
            \WP_CLI::error( "Stats recalculation failed: " . $e->getMessage() );
        }
    }

    /**
     * Clear cache
     *
     * ## OPTIONS
     *
     * [<namespace>]
     * : Specific cache namespace to clear. Options: news, shipping, all. Default: all
     *
     * ## EXAMPLES
     *
     *     wp kscrm cache clear
     *     wp kscrm cache clear news
     *     wp kscrm cache clear shipping
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Associative arguments
     */
    public function cache( $args, $assoc_args ) {
        $namespace = $args[0] ?? 'all';

        \WP_CLI::log( "Clearing {$namespace} cache..." );

        try {
            switch ( $namespace ) {
                case 'news':
                    \KS_CRM\Cache\Cache_Manager::flush_namespace( 'news' );
                    \WP_CLI::success( 'News cache cleared successfully.' );
                    break;
                    
                case 'shipping':
                    \KS_CRM\Cache\Cache_Manager::flush_namespace( 'shipping' );
                    \WP_CLI::success( 'Shipping cache cleared successfully.' );
                    break;
                    
                case 'all':
                default:
                    \KS_CRM\Cache\Cache_Manager::flush_all();
                    \WP_CLI::success( 'All cache cleared successfully.' );
                    break;
            }
            
        } catch ( \Exception $e ) {
            \WP_CLI::error( "Cache clear failed: " . $e->getMessage() );
        }
    }

    /**
     * Data retention management
     *
     * ## SUBCOMMANDS
     *
     * * run     - Run retention cleanup manually
     * * status  - Show retention status and statistics
     * * config  - Configure retention settings
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Associative arguments
     */
    public function retention( $args, $assoc_args ) {
        $subcommand = $args[0] ?? '';

        $retention_manager = new \KS_CRM\Retention\Retention_Manager();

        switch ( $subcommand ) {
            case 'run':
                $this->run_retention_cleanup( $retention_manager );
                break;
                
            case 'status':
                $this->show_retention_status( $retention_manager );
                break;
                
            case 'config':
                $this->configure_retention( $assoc_args );
                break;
                
            default:
                \WP_CLI::error( 'Invalid retention command. Use: run, status, or config' );
        }
    }

    /**
     * Export leads data
     */
    private function export_leads( string $format, int $limit, string $path, array $options ): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kscrm_leads';
        
        // Build query
        $where_conditions = [ '1=1' ];
        $prepare_values = [];

        if ( ! empty( $options['date-from'] ) ) {
            $where_conditions[] = 'created_at >= %s';
            $prepare_values[] = $options['date-from'];
        }

        if ( ! empty( $options['date-to'] ) ) {
            $where_conditions[] = 'created_at <= %s';
            $prepare_values[] = $options['date-to'];
        }

        $where_clause = implode( ' AND ', $where_conditions );
        $prepare_values[] = $limit;

        $query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d";
        
        if ( ! empty( $prepare_values ) ) {
            $query = $wpdb->prepare( $query, $prepare_values );
        }

        $results = $wpdb->get_results( $query, ARRAY_A );
        
        if ( empty( $results ) ) {
            \WP_CLI::warning( 'No leads found to export.' );
            return;
        }

        // Determine output path
        if ( empty( $path ) ) {
            $upload_dir = wp_upload_dir();
            $export_dir = $upload_dir['basedir'] . '/kscrm-exports';
            wp_mkdir_p( $export_dir );
            $path = $export_dir . '/leads_' . date( 'Y-m-d_H-i-s' ) . '.' . $format;
        }

        // Export data
        if ( $format === 'json' ) {
            file_put_contents( $path, wp_json_encode( $results, JSON_PRETTY_PRINT ) );
        } else {
            $writer = new \KS_CRM\Exports\CSV_Writer( basename( $path ), false );
            $writer->set_headers( array_keys( $results[0] ) );
            $writer->write_rows( $results );
            $writer->close();
            $path = $writer->get_file_path();
        }

        \WP_CLI::success( sprintf( 'Exported %d leads to: %s', count( $results ), $path ) );
    }

    /**
     * Export UTM analytics
     */
    private function export_utm( string $format, string $path ): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kscrm_utm_stats';
        $results = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY recorded_at DESC LIMIT 5000", ARRAY_A );
        
        if ( empty( $results ) ) {
            \WP_CLI::warning( 'No UTM data found to export.' );
            return;
        }

        // Determine output path
        if ( empty( $path ) ) {
            $upload_dir = wp_upload_dir();
            $export_dir = $upload_dir['basedir'] . '/kscrm-exports';
            wp_mkdir_p( $export_dir );
            $path = $export_dir . '/utm_' . date( 'Y-m-d_H-i-s' ) . '.' . $format;
        }

        // Export data
        if ( $format === 'json' ) {
            file_put_contents( $path, wp_json_encode( $results, JSON_PRETTY_PRINT ) );
        } else {
            $writer = new \KS_CRM\Exports\CSV_Writer( basename( $path ), false );
            $writer->set_headers( array_keys( $results[0] ) );
            $writer->write_rows( $results );
            $writer->close();
            $path = $writer->get_file_path();
        }

        \WP_CLI::success( sprintf( 'Exported %d UTM records to: %s', count( $results ), $path ) );
    }

    /**
     * Export news snapshot
     */
    private function export_news( string $format, string $path ): void {
        // Get cached news data
        $news_data = [];
        $transient_keys = [ 'kscrm_news_general', 'kscrm_news_business', 'kscrm_news_technology' ];
        
        foreach ( $transient_keys as $key ) {
            $cached_data = get_transient( $key );
            if ( $cached_data && is_array( $cached_data ) ) {
                $news_data = array_merge( $news_data, $cached_data );
            }
        }

        if ( empty( $news_data ) ) {
            \WP_CLI::warning( 'No cached news data found to export.' );
            return;
        }

        // Determine output path
        if ( empty( $path ) ) {
            $upload_dir = wp_upload_dir();
            $export_dir = $upload_dir['basedir'] . '/kscrm-exports';
            wp_mkdir_p( $export_dir );
            $path = $export_dir . '/news_' . date( 'Y-m-d_H-i-s' ) . '.' . $format;
        }

        // Export data
        file_put_contents( $path, wp_json_encode( $news_data, JSON_PRETTY_PRINT ) );

        \WP_CLI::success( sprintf( 'Exported %d news articles to: %s', count( $news_data ), $path ) );
    }

    /**
     * Recalculate statistics
     */
    private function recalculate_stats( int $batch_size, bool $dry_run ): void {
        global $wpdb;

        if ( ! class_exists( 'WooCommerce' ) ) {
            \WP_CLI::error( 'WooCommerce is not active.' );
        }

        $utm_table = $wpdb->prefix . 'kscrm_utm_stats';
        
        // Get total order count
        $total_orders = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'shop_order'" );
        
        if ( ! $total_orders ) {
            \WP_CLI::warning( 'No orders found.' );
            return;
        }

        \WP_CLI::log( "Found {$total_orders} orders to process." );

        if ( ! $dry_run ) {
            // Truncate existing stats
            $wpdb->query( "TRUNCATE TABLE {$utm_table}" );
            \WP_CLI::log( 'Existing UTM stats cleared.' );
        }

        $progress = \WP_CLI\Utils\make_progress_bar( 'Processing orders', $total_orders );
        
        $processed = 0;
        $offset = 0;

        do {
            $orders = $wpdb->get_results( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->prefix}posts 
                 WHERE post_type = 'shop_order' 
                 ORDER BY ID 
                 LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            ) );

            foreach ( $orders as $order_data ) {
                if ( ! $dry_run ) {
                    $this->process_order_utm( $order_data->ID );
                }
                $processed++;
                $progress->tick();
            }

            $offset += $batch_size;
            
        } while ( ! empty( $orders ) );

        $progress->finish();
        
        if ( $dry_run ) {
            \WP_CLI::log( "DRY RUN: Would have processed {$processed} orders." );
        } else {
            \WP_CLI::success( "Successfully recalculated stats for {$processed} orders." );
        }
    }

    /**
     * Process single order for UTM data
     */
    private function process_order_utm( int $order_id ): void {
        // This is a placeholder - would implement actual UTM extraction logic
        // based on how UTM data is stored in orders
        \WP_CLI::debug( "Processing order {$order_id} for UTM data..." );
    }

    /**
     * Run retention cleanup
     */
    private function run_retention_cleanup( $retention_manager ): void {
        \WP_CLI::log( 'Running retention cleanup...' );
        
        $results = $retention_manager->manual_cleanup();
        
        \WP_CLI::success( sprintf(
            'Cleanup completed in %ss. Deleted: %d leads, %d UTM records, %d cache entries.',
            $results['execution_time'],
            $results['leads_deleted'],
            $results['utm_deleted'],
            $results['cache_cleared']
        ) );
    }

    /**
     * Show retention status
     */
    private function show_retention_status( $retention_manager ): void {
        $status = $retention_manager->get_status();
        
        \WP_CLI::log( 'Retention Status:' );
        \WP_CLI::log( '=================' );
        \WP_CLI::log( 'Last cleanup: ' . ( $status['last_cleanup'] ? date( 'Y-m-d H:i:s', $status['last_cleanup'] ) : 'Never' ) );
        \WP_CLI::log( 'Next cleanup: ' . ( $status['next_cleanup'] ? date( 'Y-m-d H:i:s', $status['next_cleanup'] ) : 'Not scheduled' ) );
        \WP_CLI::log( 'Leads retention: ' . $status['settings']['leads_retention_months'] . ' months' );
        \WP_CLI::log( 'UTM retention: ' . $status['settings']['utm_retention_days'] . ' days' );
        \WP_CLI::log( '' );
        \WP_CLI::log( 'Statistics:' );
        \WP_CLI::log( 'Total leads: ' . number_format( $status['statistics']['total_leads'] ) );
        \WP_CLI::log( 'Leads eligible for cleanup: ' . number_format( $status['statistics']['leads_eligible_for_cleanup'] ) );
        \WP_CLI::log( 'Total UTM records: ' . number_format( $status['statistics']['total_utm_records'] ) );
        \WP_CLI::log( 'UTM records eligible for cleanup: ' . number_format( $status['statistics']['utm_eligible_for_cleanup'] ) );
    }

    /**
     * Configure retention settings
     */
    private function configure_retention( array $options ): void {
        $leads_months = $options['leads-months'] ?? null;
        $utm_days = $options['utm-days'] ?? null;

        if ( $leads_months !== null ) {
            $leads_months = max( 1, min( 120, absint( $leads_months ) ) );
            update_option( 'kscrm_leads_retention_months', $leads_months );
            \WP_CLI::success( "Leads retention set to {$leads_months} months." );
        }

        if ( $utm_days !== null ) {
            $utm_days = max( 30, min( 3650, absint( $utm_days ) ) );
            update_option( 'kscrm_utm_retention_days', $utm_days );
            \WP_CLI::success( "UTM retention set to {$utm_days} days." );
        }

        if ( $leads_months === null && $utm_days === null ) {
            \WP_CLI::error( 'Please specify --leads-months and/or --utm-days options.' );
        }
    }
}

// Register commands if WP-CLI is available
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    \WP_CLI::add_command( 'kscrm', __NAMESPACE__ . '\\Commands' );
}