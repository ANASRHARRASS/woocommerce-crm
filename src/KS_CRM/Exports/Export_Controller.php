<?php
/**
 * Export Controller for REST API endpoints
 * Handles data exports with proper permissions and rate limiting
 */

namespace KS_CRM\Exports;

use KS_CRM\Rate_Limiter\Rate_Limiter;

defined( 'ABSPATH' ) || exit;

class Export_Controller {

    /**
     * Register REST API routes
     */
    public function register_routes(): void {
        register_rest_route( 'ks-crm/v1', '/export/leads', [
            'methods' => 'GET',
            'callback' => [ $this, 'export_leads' ],
            'permission_callback' => [ $this, 'check_export_permissions' ],
            'args' => [
                'format' => [
                    'default' => 'csv',
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => function( $param ) {
                        return in_array( $param, [ 'csv', 'json' ], true );
                    }
                ],
                'limit' => [
                    'default' => 1000,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function( $param ) {
                        return $param >= 1 && $param <= 10000;
                    }
                ],
                'date_from' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'date_to' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ]
        ] );

        register_rest_route( 'ks-crm/v1', '/export/utm', [
            'methods' => 'GET',
            'callback' => [ $this, 'export_utm_analytics' ],
            'permission_callback' => [ $this, 'check_export_permissions' ],
            'args' => [
                'format' => [
                    'default' => 'csv',
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => function( $param ) {
                        return in_array( $param, [ 'csv', 'json' ], true );
                    }
                ],
            ]
        ] );

        register_rest_route( 'ks-crm/v1', '/export/news', [
            'methods' => 'GET',
            'callback' => [ $this, 'export_news_snapshot' ],
            'permission_callback' => [ $this, 'check_export_permissions' ],
            'args' => [
                'format' => [
                    'default' => 'json',
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => function( $param ) {
                        return in_array( $param, [ 'csv', 'json' ], true );
                    }
                ],
            ]
        ] );
    }

    /**
     * Export leads data
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error Response or error
     */
    public function export_leads( \WP_REST_Request $request ) {
        // Rate limiting check
        if ( ! Rate_Limiter::is_allowed( 'export_leads', 10, 3600 ) ) {
            return Rate_Limiter::create_error( 'export_leads' );
        }

        $format = $request->get_param( 'format' );
        $limit = $request->get_param( 'limit' );
        $date_from = $request->get_param( 'date_from' );
        $date_to = $request->get_param( 'date_to' );

        try {
            $leads_data = $this->get_leads_data( $limit, $date_from, $date_to );

            if ( $format === 'csv' ) {
                $this->export_as_csv( $leads_data, 'leads', $this->get_leads_headers() );
                return; // CSV streams directly
            }

            return new \WP_REST_Response( [
                'success' => true,
                'data' => $leads_data,
                'count' => count( $leads_data ),
                'exported_at' => current_time( 'c' ),
            ] );

        } catch ( \Exception $e ) {
            return new \WP_Error( 'export_failed', $e->getMessage(), [ 'status' => 500 ] );
        }
    }

    /**
     * Export UTM analytics data
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error Response or error
     */
    public function export_utm_analytics( \WP_REST_Request $request ) {
        // Rate limiting check
        if ( ! Rate_Limiter::is_allowed( 'export_utm', 5, 3600 ) ) {
            return Rate_Limiter::create_error( 'export_utm' );
        }

        $format = $request->get_param( 'format' );

        try {
            $utm_data = $this->get_utm_analytics_data();

            if ( $format === 'csv' ) {
                $this->export_as_csv( $utm_data, 'utm_analytics', $this->get_utm_headers() );
                return; // CSV streams directly
            }

            return new \WP_REST_Response( [
                'success' => true,
                'data' => $utm_data,
                'count' => count( $utm_data ),
                'exported_at' => current_time( 'c' ),
            ] );

        } catch ( \Exception $e ) {
            return new \WP_Error( 'export_failed', $e->getMessage(), [ 'status' => 500 ] );
        }
    }

    /**
     * Export news snapshot
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error Response or error
     */
    public function export_news_snapshot( \WP_REST_Request $request ) {
        // Rate limiting check
        if ( ! Rate_Limiter::is_allowed( 'export_news', 5, 3600 ) ) {
            return Rate_Limiter::create_error( 'export_news' );
        }

        $format = $request->get_param( 'format' );

        try {
            $news_data = $this->get_news_snapshot_data();

            if ( $format === 'csv' ) {
                $this->export_as_csv( $news_data, 'news_snapshot', $this->get_news_headers() );
                return; // CSV streams directly
            }

            return new \WP_REST_Response( [
                'success' => true,
                'data' => $news_data,
                'count' => count( $news_data ),
                'exported_at' => current_time( 'c' ),
            ] );

        } catch ( \Exception $e ) {
            return new \WP_Error( 'export_failed', $e->getMessage(), [ 'status' => 500 ] );
        }
    }

    /**
     * Check export permissions
     *
     * @return bool True if user can export
     */
    public function check_export_permissions(): bool {
        return current_user_can( 'manage_woocommerce' );
    }

    /**
     * Get leads data for export
     *
     * @param int $limit Maximum records
     * @param string|null $date_from Start date
     * @param string|null $date_to End date
     * @return array Leads data
     */
    private function get_leads_data( int $limit, ?string $date_from, ?string $date_to ): array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kscrm_leads'; // Assuming this table exists
        
        $where_conditions = [ '1=1' ];
        $prepare_values = [];

        if ( $date_from ) {
            $where_conditions[] = 'created_at >= %s';
            $prepare_values[] = $date_from;
        }

        if ( $date_to ) {
            $where_conditions[] = 'created_at <= %s';
            $prepare_values[] = $date_to;
        }

        $where_clause = implode( ' AND ', $where_conditions );
        $prepare_values[] = $limit;

        $query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d";

        if ( ! empty( $prepare_values ) ) {
            $query = $wpdb->prepare( $query, $prepare_values );
        }

        $results = $wpdb->get_results( $query, ARRAY_A );
        
        return $results ?: [];
    }

    /**
     * Get UTM analytics data
     *
     * @return array UTM analytics data
     */
    private function get_utm_analytics_data(): array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kscrm_utm_stats'; // Assuming this table exists
        
        $query = "SELECT * FROM {$table_name} ORDER BY recorded_at DESC LIMIT 5000";
        $results = $wpdb->get_results( $query, ARRAY_A );
        
        return $results ?: [];
    }

    /**
     * Get news snapshot data
     *
     * @return array News snapshot data
     */
    private function get_news_snapshot_data(): array {
        // Get recent cached news data
        $transient_keys = [
            'kscrm_news_general',
            'kscrm_news_business',
            'kscrm_news_technology',
        ];

        $news_data = [];
        
        foreach ( $transient_keys as $key ) {
            $cached_data = get_transient( $key );
            if ( $cached_data && is_array( $cached_data ) ) {
                $news_data = array_merge( $news_data, $cached_data );
            }
        }

        return $news_data;
    }

    /**
     * Export data as CSV
     *
     * @param array $data Data to export
     * @param string $filename_prefix Filename prefix
     * @param array $headers CSV headers
     */
    private function export_as_csv( array $data, string $filename_prefix, array $headers ): void {
        $filename = $filename_prefix . '_' . date( 'Y-m-d_H-i-s' ) . '.csv';
        
        $writer = new CSV_Writer( $filename, true );
        $writer->set_headers( $headers );
        $writer->write_rows( $data );
        $writer->close();
    }

    /**
     * Get leads CSV headers
     *
     * @return array Headers
     */
    private function get_leads_headers(): array {
        return [
            'id',
            'name',
            'email',
            'phone',
            'source',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'created_at',
            'status',
        ];
    }

    /**
     * Get UTM analytics CSV headers
     *
     * @return array Headers
     */
    private function get_utm_headers(): array {
        return [
            'id',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'page_url',
            'referrer',
            'visitor_ip',
            'recorded_at',
        ];
    }

    /**
     * Get news CSV headers
     *
     * @return array Headers
     */
    private function get_news_headers(): array {
        return [
            'id',
            'title',
            'url',
            'source',
            'published_at',
            'description',
            'image_url',
        ];
    }
}