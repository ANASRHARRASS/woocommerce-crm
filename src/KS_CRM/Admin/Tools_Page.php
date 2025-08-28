<?php
/**
 * Tools admin page for data exports and utilities
 */

namespace KS_CRM\Admin;

defined( 'ABSPATH' ) || exit;

class Tools_Page {

    /**
     * Initialize the tools page
     */
    public function init(): void {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_kscrm_export_data', [ $this, 'handle_ajax_export' ] );
    }

    /**
     * Add tools submenu page
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'woocommerce', // Parent slug (under WooCommerce)
            __( 'WooCommerce CRM Tools', 'woocommerce-crm' ),
            __( 'CRM Tools', 'woocommerce-crm' ),
            'manage_woocommerce',
            'kscrm-tools',
            [ $this, 'render_tools_page' ]
        );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook_suffix Current admin page
     */
    public function enqueue_scripts( string $hook_suffix ): void {
        if ( $hook_suffix !== 'woocommerce_page_kscrm-tools' ) {
            return;
        }

        wp_enqueue_script(
            'kscrm-tools',
            WCCRM_PLUGIN_URL . 'assets/js/tools.js',
            [ 'jquery' ],
            WCCRM_VERSION,
            true
        );

        wp_localize_script( 'kscrm-tools', 'kscrm_tools', [
            'nonce' => wp_create_nonce( 'kscrm_tools_nonce' ),
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'strings' => [
                'export_started' => __( 'Export started...', 'woocommerce-crm' ),
                'export_completed' => __( 'Export completed!', 'woocommerce-crm' ),
                'export_error' => __( 'Export failed. Please try again.', 'woocommerce-crm' ),
                'confirm_clear_cache' => __( 'Are you sure you want to clear all cache?', 'woocommerce-crm' ),
            ]
        ] );

        wp_enqueue_style(
            'kscrm-tools',
            WCCRM_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WCCRM_VERSION
        );
    }

    /**
     * Render the tools page
     */
    public function render_tools_page(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        // Handle form submissions
        if ( isset( $_POST['action'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'kscrm_tools_nonce' ) ) {
            $this->handle_form_submission();
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WooCommerce CRM Tools', 'woocommerce-crm' ); ?></h1>
            
            <div class="kscrm-tools-container">
                
                <!-- Data Export Section -->
                <div class="kscrm-tools-section">
                    <h2><?php esc_html_e( 'Data Export', 'woocommerce-crm' ); ?></h2>
                    <p><?php esc_html_e( 'Export your CRM data for backup or analysis.', 'woocommerce-crm' ); ?></p>
                    
                    <div class="kscrm-export-buttons">
                        <button type="button" class="button button-primary kscrm-export-btn" data-export="leads">
                            <?php esc_html_e( 'Export Leads (CSV)', 'woocommerce-crm' ); ?>
                        </button>
                        
                        <button type="button" class="button button-primary kscrm-export-btn" data-export="utm">
                            <?php esc_html_e( 'Export UTM Analytics (CSV)', 'woocommerce-crm' ); ?>
                        </button>
                        
                        <button type="button" class="button button-primary kscrm-export-btn" data-export="news">
                            <?php esc_html_e( 'Export News Snapshot (JSON)', 'woocommerce-crm' ); ?>
                        </button>
                    </div>
                    
                    <div id="kscrm-export-progress" class="kscrm-progress" style="display: none;">
                        <div class="kscrm-progress-bar">
                            <div class="kscrm-progress-fill"></div>
                        </div>
                        <div class="kscrm-progress-text"></div>
                    </div>
                </div>

                <!-- Cache Management Section -->
                <div class="kscrm-tools-section">
                    <h2><?php esc_html_e( 'Cache Management', 'woocommerce-crm' ); ?></h2>
                    <p><?php esc_html_e( 'Manage cached data to improve performance or troubleshoot issues.', 'woocommerce-crm' ); ?></p>
                    
                    <form method="post">
                        <?php wp_nonce_field( 'kscrm_tools_nonce' ); ?>
                        <input type="hidden" name="action" value="clear_cache">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Cache Actions', 'woocommerce-crm' ); ?></th>
                                <td>
                                    <button type="submit" name="cache_action" value="clear_news" class="button">
                                        <?php esc_html_e( 'Clear News Cache', 'woocommerce-crm' ); ?>
                                    </button>
                                    
                                    <button type="submit" name="cache_action" value="clear_shipping" class="button">
                                        <?php esc_html_e( 'Clear Shipping Cache', 'woocommerce-crm' ); ?>
                                    </button>
                                    
                                    <button type="submit" name="cache_action" value="clear_all" class="button button-secondary" 
                                            onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all cache?', 'woocommerce-crm' ); ?>')">
                                        <?php esc_html_e( 'Clear All Cache', 'woocommerce-crm' ); ?>
                                    </button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>

                <!-- Data Retention Section -->
                <div class="kscrm-tools-section">
                    <h2><?php esc_html_e( 'Data Retention', 'woocommerce-crm' ); ?></h2>
                    <p><?php esc_html_e( 'Configure how long to keep different types of data.', 'woocommerce-crm' ); ?></p>
                    
                    <form method="post">
                        <?php wp_nonce_field( 'kscrm_tools_nonce' ); ?>
                        <input type="hidden" name="action" value="update_retention">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="leads_retention_months">
                                        <?php esc_html_e( 'Retain Leads (months)', 'woocommerce-crm' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="number" id="leads_retention_months" name="leads_retention_months" 
                                           value="<?php echo esc_attr( get_option( 'kscrm_leads_retention_months', 24 ) ); ?>" 
                                           min="1" max="120" class="small-text">
                                    <p class="description">
                                        <?php esc_html_e( 'How many months to keep lead data before automatic cleanup.', 'woocommerce-crm' ); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="utm_retention_days">
                                        <?php esc_html_e( 'Retain UTM Stats (days)', 'woocommerce-crm' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="number" id="utm_retention_days" name="utm_retention_days" 
                                           value="<?php echo esc_attr( get_option( 'kscrm_utm_retention_days', 365 ) ); ?>" 
                                           min="30" max="3650" class="small-text">
                                    <p class="description">
                                        <?php esc_html_e( 'How many days to keep UTM analytics data.', 'woocommerce-crm' ); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button( __( 'Update Retention Settings', 'woocommerce-crm' ) ); ?>
                    </form>
                </div>

                <!-- System Information Section -->
                <div class="kscrm-tools-section">
                    <h2><?php esc_html_e( 'System Information', 'woocommerce-crm' ); ?></h2>
                    <p><?php esc_html_e( 'System status and configuration information.', 'woocommerce-crm' ); ?></p>
                    
                    <?php $this->render_system_info(); ?>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Handle form submissions
     */
    private function handle_form_submission(): void {
        $action = sanitize_key( $_POST['action'] );

        switch ( $action ) {
            case 'clear_cache':
                $this->handle_cache_clear();
                break;
                
            case 'update_retention':
                $this->handle_retention_update();
                break;
        }
    }

    /**
     * Handle cache clearing
     */
    private function handle_cache_clear(): void {
        $cache_action = sanitize_key( $_POST['cache_action'] ?? '' );

        switch ( $cache_action ) {
            case 'clear_news':
                \KS_CRM\Cache\Cache_Manager::flush_namespace( 'news' );
                $this->add_admin_notice( __( 'News cache cleared successfully.', 'woocommerce-crm' ), 'success' );
                break;
                
            case 'clear_shipping':
                \KS_CRM\Cache\Cache_Manager::flush_namespace( 'shipping' );
                $this->add_admin_notice( __( 'Shipping cache cleared successfully.', 'woocommerce-crm' ), 'success' );
                break;
                
            case 'clear_all':
                \KS_CRM\Cache\Cache_Manager::flush_all();
                $this->add_admin_notice( __( 'All cache cleared successfully.', 'woocommerce-crm' ), 'success' );
                break;
        }
    }

    /**
     * Handle retention settings update
     */
    private function handle_retention_update(): void {
        $leads_retention = absint( $_POST['leads_retention_months'] ?? 24 );
        $utm_retention = absint( $_POST['utm_retention_days'] ?? 365 );

        // Validate ranges
        $leads_retention = max( 1, min( 120, $leads_retention ) );
        $utm_retention = max( 30, min( 3650, $utm_retention ) );

        update_option( 'kscrm_leads_retention_months', $leads_retention );
        update_option( 'kscrm_utm_retention_days', $utm_retention );

        $this->add_admin_notice( __( 'Retention settings updated successfully.', 'woocommerce-crm' ), 'success' );
    }

    /**
     * Handle AJAX export requests
     */
    public function handle_ajax_export(): void {
        check_ajax_referer( 'kscrm_tools_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( __( 'Insufficient permissions.' ), 403 );
        }

        $export_type = sanitize_key( $_POST['export_type'] ?? '' );
        $format = sanitize_key( $_POST['format'] ?? 'csv' );

        $base_url = rest_url( "ks-crm/v1/export/{$export_type}" );
        $export_url = add_query_arg( [ 'format' => $format ], $base_url );

        wp_send_json_success( [
            'download_url' => $export_url,
            'message' => __( 'Export ready for download.', 'woocommerce-crm' ),
        ] );
    }

    /**
     * Render system information
     */
    private function render_system_info(): void {
        $info = [
            'WooCommerce CRM Version' => WCCRM_VERSION,
            'WordPress Version' => get_bloginfo( 'version' ),
            'WooCommerce Version' => class_exists( 'WooCommerce' ) ? WC()->version : 'Not installed',
            'PHP Version' => PHP_VERSION,
            'Memory Limit' => ini_get( 'memory_limit' ),
            'Max Execution Time' => ini_get( 'max_execution_time' ) . 's',
            'Database Version' => $GLOBALS['wpdb']->db_version(),
        ];

        echo '<table class="widefat striped">';
        echo '<tbody>';
        
        foreach ( $info as $label => $value ) {
            echo '<tr>';
            echo '<td><strong>' . esc_html( $label ) . '</strong></td>';
            echo '<td>' . esc_html( $value ) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Add admin notice
     *
     * @param string $message Notice message
     * @param string $type Notice type (success, error, warning, info)
     */
    private function add_admin_notice( string $message, string $type = 'info' ): void {
        add_action( 'admin_notices', function() use ( $message, $type ) {
            echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible">';
            echo '<p>' . esc_html( $message ) . '</p>';
            echo '</div>';
        } );
    }
}