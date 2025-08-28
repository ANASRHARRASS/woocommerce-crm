<?php
/**
 * Data Retention Manager
 * Handles automatic cleanup of old data based on retention policies
 */

namespace KS_CRM\Retention;

defined( 'ABSPATH' ) || exit;

class Retention_Manager {

    private const HOOK_NAME = 'kscrm_daily_retention_cleanup';

    /**
     * Initialize retention manager
     */
    public function init(): void {
        // Schedule daily cleanup if not already scheduled
        if ( ! wp_next_scheduled( self::HOOK_NAME ) ) {
            wp_schedule_event( time(), 'daily', self::HOOK_NAME );
        }

        // Hook the cleanup function
        add_action( self::HOOK_NAME, [ $this, 'run_cleanup' ] );
        
        // Add manual cleanup action for CLI
        add_action( 'kscrm_manual_retention_cleanup', [ $this, 'run_cleanup' ] );
    }

    /**
     * Run retention cleanup
     */
    public function run_cleanup(): void {
        $start_time = microtime( true );
        
        try {
            $results = [
                'leads_deleted' => $this->cleanup_old_leads(),
                'utm_deleted' => $this->cleanup_old_utm_data(),
                'cache_cleared' => $this->cleanup_expired_cache(),
            ];

            $execution_time = round( microtime( true ) - $start_time, 2 );
            
            // Log cleanup results
            error_log( sprintf(
                'KSCRM Retention Cleanup completed in %ss. Results: %s',
                $execution_time,
                wp_json_encode( $results )
            ) );

            // Store last cleanup timestamp
            update_option( 'kscrm_last_retention_cleanup', time() );
            
        } catch ( \Exception $e ) {
            error_log( 'KSCRM Retention Cleanup failed: ' . $e->getMessage() );
        }
    }

    /**
     * Cleanup old leads data
     *
     * @return int Number of leads deleted
     */
    public function cleanup_old_leads(): int {
        global $wpdb;

        $retention_months = absint( get_option( 'kscrm_leads_retention_months', 24 ) );
        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$retention_months} months" ) );

        $table_name = $wpdb->prefix . 'kscrm_leads';
        
        // Check if table exists
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
            return 0;
        }

        // Delete old leads in batches to avoid timeout
        $batch_size = 1000;
        $total_deleted = 0;

        do {
            $deleted = $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < %s LIMIT %d",
                $cutoff_date,
                $batch_size
            ) );

            $total_deleted += $deleted;
            
            // Prevent infinite loop
            if ( $deleted === false ) {
                break;
            }
            
        } while ( $deleted > 0 );

        return $total_deleted;
    }

    /**
     * Cleanup old UTM analytics data
     *
     * @return int Number of UTM records deleted
     */
    public function cleanup_old_utm_data(): int {
        global $wpdb;

        $retention_days = absint( get_option( 'kscrm_utm_retention_days', 365 ) );
        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

        $table_name = $wpdb->prefix . 'kscrm_utm_stats';
        
        // Check if table exists
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
            return 0;
        }

        // Delete old UTM data in batches
        $batch_size = 1000;
        $total_deleted = 0;

        do {
            $deleted = $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE recorded_at < %s LIMIT %d",
                $cutoff_date,
                $batch_size
            ) );

            $total_deleted += $deleted;
            
            if ( $deleted === false ) {
                break;
            }
            
        } while ( $deleted > 0 );

        return $total_deleted;
    }

    /**
     * Cleanup expired cache entries
     *
     * @return int Number of cache entries cleaned
     */
    public function cleanup_expired_cache(): int {
        global $wpdb;

        // Clean up expired transients
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_kscrm_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );

        // Clean up orphaned transient data
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_kscrm_%' 
             AND option_name NOT IN (
                 SELECT REPLACE(option_name, '_transient_timeout_', '_transient_') 
                 FROM {$wpdb->options} AS o2 
                 WHERE o2.option_name LIKE '_transient_timeout_kscrm_%'
             )"
        );

        return intval( $deleted );
    }

    /**
     * Get retention status and statistics
     *
     * @return array Retention status information
     */
    public function get_status(): array {
        global $wpdb;

        $leads_table = $wpdb->prefix . 'kscrm_leads';
        $utm_table = $wpdb->prefix . 'kscrm_utm_stats';

        // Count records that would be deleted
        $retention_months = absint( get_option( 'kscrm_leads_retention_months', 24 ) );
        $retention_days = absint( get_option( 'kscrm_utm_retention_days', 365 ) );
        
        $leads_cutoff = date( 'Y-m-d H:i:s', strtotime( "-{$retention_months} months" ) );
        $utm_cutoff = date( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

        $status = [
            'last_cleanup' => get_option( 'kscrm_last_retention_cleanup', 0 ),
            'next_cleanup' => wp_next_scheduled( self::HOOK_NAME ),
            'settings' => [
                'leads_retention_months' => $retention_months,
                'utm_retention_days' => $retention_days,
            ],
            'statistics' => [
                'leads_eligible_for_cleanup' => 0,
                'utm_eligible_for_cleanup' => 0,
                'total_leads' => 0,
                'total_utm_records' => 0,
            ]
        ];

        // Get statistics if tables exist
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $leads_table ) ) === $leads_table ) {
            $status['statistics']['total_leads'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$leads_table}" );
            $status['statistics']['leads_eligible_for_cleanup'] = (int) $wpdb->get_var( 
                $wpdb->prepare( "SELECT COUNT(*) FROM {$leads_table} WHERE created_at < %s", $leads_cutoff )
            );
        }

        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $utm_table ) ) === $utm_table ) {
            $status['statistics']['total_utm_records'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$utm_table}" );
            $status['statistics']['utm_eligible_for_cleanup'] = (int) $wpdb->get_var( 
                $wpdb->prepare( "SELECT COUNT(*) FROM {$utm_table} WHERE recorded_at < %s", $utm_cutoff )
            );
        }

        return $status;
    }

    /**
     * Manually trigger cleanup (for CLI and testing)
     *
     * @return array Cleanup results
     */
    public function manual_cleanup(): array {
        $start_time = microtime( true );
        
        $results = [
            'leads_deleted' => $this->cleanup_old_leads(),
            'utm_deleted' => $this->cleanup_old_utm_data(),
            'cache_cleared' => $this->cleanup_expired_cache(),
            'execution_time' => round( microtime( true ) - $start_time, 2 ),
        ];

        // Update last cleanup timestamp
        update_option( 'kscrm_last_retention_cleanup', time() );

        return $results;
    }

    /**
     * Unschedule retention cleanup (for deactivation)
     */
    public static function unschedule(): void {
        wp_clear_scheduled_hook( self::HOOK_NAME );
    }

    /**
     * Check if cleanup is needed based on last run time
     *
     * @return bool True if cleanup is needed
     */
    public function is_cleanup_needed(): bool {
        $last_cleanup = get_option( 'kscrm_last_retention_cleanup', 0 );
        $hours_since_last = ( time() - $last_cleanup ) / 3600;
        
        // Run cleanup if it's been more than 25 hours (allowing for some flexibility)
        return $hours_since_last > 25;
    }

    /**
     * Estimate cleanup impact
     *
     * @return array Impact estimation
     */
    public function estimate_cleanup_impact(): array {
        global $wpdb;

        $retention_months = absint( get_option( 'kscrm_leads_retention_months', 24 ) );
        $retention_days = absint( get_option( 'kscrm_utm_retention_days', 365 ) );
        
        $leads_cutoff = date( 'Y-m-d H:i:s', strtotime( "-{$retention_months} months" ) );
        $utm_cutoff = date( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

        $impact = [
            'leads_to_delete' => 0,
            'utm_to_delete' => 0,
            'estimated_space_freed' => 0, // in MB
        ];

        $leads_table = $wpdb->prefix . 'kscrm_leads';
        $utm_table = $wpdb->prefix . 'kscrm_utm_stats';

        // Count records to be deleted
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $leads_table ) ) === $leads_table ) {
            $impact['leads_to_delete'] = (int) $wpdb->get_var( 
                $wpdb->prepare( "SELECT COUNT(*) FROM {$leads_table} WHERE created_at < %s", $leads_cutoff )
            );
        }

        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $utm_table ) ) === $utm_table ) {
            $impact['utm_to_delete'] = (int) $wpdb->get_var( 
                $wpdb->prepare( "SELECT COUNT(*) FROM {$utm_table} WHERE recorded_at < %s", $utm_cutoff )
            );
        }

        // Rough estimation: 1KB per lead record, 0.5KB per UTM record
        $impact['estimated_space_freed'] = round( 
            ( $impact['leads_to_delete'] * 1 + $impact['utm_to_delete'] * 0.5 ) / 1024, 
            2 
        );

        return $impact;
    }
}