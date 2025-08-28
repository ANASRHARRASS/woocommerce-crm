<?php
namespace KS_CRM\Admin;

defined( 'ABSPATH' ) || exit;

class Dashboard {
    
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'wp_ajax_ks_create_lead', [ $this, 'ajax_create_lead' ] );
        add_action( 'wp_ajax_ks_search_products', [ $this, 'ajax_search_products' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }
    
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=ks_lead',
            __( 'Dashboard', 'woocommerce-crm' ),
            __( 'Dashboard', 'woocommerce-crm' ),
            'manage_options',
            'ks-crm-dashboard',
            [ $this, 'render_dashboard' ]
        );
    }
    
    public function enqueue_scripts( $hook ) {
        if ( $hook !== 'ks_lead_page_ks-crm-dashboard' ) {
            return;
        }
        
        wp_enqueue_script(
            'ks-admin-dashboard',
            WCCRM_PLUGIN_URL . 'assets/js/admin-dashboard.js',
            [ 'jquery' ],
            WCCRM_VERSION,
            true
        );
        
        wp_enqueue_style(
            'ks-admin-css',
            WCCRM_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WCCRM_VERSION
        );
        
        wp_localize_script( 'ks-admin-dashboard', 'ks_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ks_admin_nonce' ),
            'rest_url' => rest_url( 'ks-crm/v1/' ),
            'rest_nonce' => wp_create_nonce( 'wp_rest' ),
        ] );
    }
    
    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Woo CRM Dashboard', 'woocommerce-crm' ); ?></h1>
            
            <div class="ks-dashboard-container">
                <div class="ks-stats-row">
                    <div class="ks-stat-box">
                        <h3><?php esc_html_e( 'Total Leads', 'woocommerce-crm' ); ?></h3>
                        <div class="ks-stat-number"><?php echo esc_html( $this->get_total_leads() ); ?></div>
                    </div>
                    <div class="ks-stat-box">
                        <h3><?php esc_html_e( 'Leads (7 days)', 'woocommerce-crm' ); ?></h3>
                        <div class="ks-stat-number"><?php echo esc_html( $this->get_leads_7_days() ); ?></div>
                    </div>
                    <div class="ks-stat-box">
                        <h3><?php esc_html_e( 'Orders (7 days)', 'woocommerce-crm' ); ?></h3>
                        <div class="ks-stat-number"><?php echo esc_html( $this->get_orders_7_days() ); ?></div>
                    </div>
                    <div class="ks-stat-box">
                        <h3><?php esc_html_e( 'Orders Today', 'woocommerce-crm' ); ?></h3>
                        <div class="ks-stat-number"><?php echo esc_html( $this->get_orders_today() ); ?></div>
                    </div>
                    <div class="ks-stat-box">
                        <h3><?php esc_html_e( 'Conversion Rate', 'woocommerce-crm' ); ?></h3>
                        <div class="ks-stat-number"><?php echo esc_html( $this->get_conversion_rate() . '%' ); ?></div>
                    </div>
                </div>
                
                <div class="ks-dashboard-panels">
                    <div class="ks-panel">
                        <h2><?php esc_html_e( 'Quick Lead Capture', 'woocommerce-crm' ); ?></h2>
                        <form id="ks-quick-lead-form">
                            <?php wp_nonce_field( 'ks_admin_nonce', 'ks_nonce' ); ?>
                            <table class="form-table">
                                <tr>
                                    <th><label for="lead_name"><?php esc_html_e( 'Name', 'woocommerce-crm' ); ?></label></th>
                                    <td><input type="text" id="lead_name" name="name" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label for="lead_email"><?php esc_html_e( 'Email', 'woocommerce-crm' ); ?></label></th>
                                    <td><input type="email" id="lead_email" name="email" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label for="lead_phone"><?php esc_html_e( 'Phone', 'woocommerce-crm' ); ?></label></th>
                                    <td><input type="tel" id="lead_phone" name="phone" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th><label for="lead_message"><?php esc_html_e( 'Message', 'woocommerce-crm' ); ?></label></th>
                                    <td><textarea id="lead_message" name="message" rows="3" class="large-text"></textarea></td>
                                </tr>
                                <tr>
                                    <th><label for="lead_source"><?php esc_html_e( 'Source', 'woocommerce-crm' ); ?></label></th>
                                    <td>
                                        <select id="lead_source" name="source">
                                            <option value="admin"><?php esc_html_e( 'Admin', 'woocommerce-crm' ); ?></option>
                                            <option value="website"><?php esc_html_e( 'Website', 'woocommerce-crm' ); ?></option>
                                            <option value="facebook"><?php esc_html_e( 'Facebook', 'woocommerce-crm' ); ?></option>
                                            <option value="whatsapp"><?php esc_html_e( 'WhatsApp', 'woocommerce-crm' ); ?></option>
                                            <option value="other"><?php esc_html_e( 'Other', 'woocommerce-crm' ); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <input type="submit" class="button-primary" value="<?php esc_attr_e( 'Create Lead', 'woocommerce-crm' ); ?>">
                            </p>
                        </form>
                    </div>
                    
                    <div class="ks-panel">
                        <h2><?php esc_html_e( 'WhatsApp Order Builder', 'woocommerce-crm' ); ?></h2>
                        <div id="ks-product-search">
                            <input type="text" id="product-search-input" placeholder="<?php esc_attr_e( 'Search products...', 'woocommerce-crm' ); ?>" class="regular-text">
                            <div id="product-search-results"></div>
                        </div>
                        <div id="selected-products"></div>
                        <div id="order-summary">
                            <div class="order-totals">
                                <div class="subtotal"><?php esc_html_e( 'Subtotal: ', 'woocommerce-crm' ); ?><span id="order-subtotal">$0.00</span></div>
                                <div class="shipping"><?php esc_html_e( 'Shipping: ', 'woocommerce-crm' ); ?><span id="order-shipping">TBD</span></div>
                                <div class="total"><?php esc_html_e( 'Total: ', 'woocommerce-crm' ); ?><span id="order-total">$0.00</span></div>
                            </div>
                            <button type="button" id="generate-whatsapp-link" class="button-primary" disabled>
                                <?php esc_html_e( 'Generate WhatsApp Message & Cart Link', 'woocommerce-crm' ); ?>
                            </button>
                        </div>
                        <div id="whatsapp-output" style="display:none;">
                            <h3><?php esc_html_e( 'WhatsApp Message Preview', 'woocommerce-crm' ); ?></h3>
                            <textarea id="whatsapp-message" readonly rows="6" class="large-text"></textarea>
                            <p>
                                <button type="button" id="copy-whatsapp-message" class="button"><?php esc_html_e( 'Copy Message', 'woocommerce-crm' ); ?></button>
                                <a href="#" id="open-whatsapp-link" class="button-primary" target="_blank"><?php esc_html_e( 'Open WhatsApp', 'woocommerce-crm' ); ?></a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="ks-panel">
                    <h2><?php esc_html_e( 'UTM Top Sources', 'woocommerce-crm' ); ?></h2>
                    <div class="ks-utm-sources">
                        <?php $this->render_utm_sources(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function ajax_create_lead() {
        check_ajax_referer( 'ks_admin_nonce', 'ks_nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Permission denied', 'woocommerce-crm' ) );
        }
        
        $name = sanitize_text_field( $_POST['name'] ?? '' );
        $email = sanitize_email( $_POST['email'] ?? '' );
        $phone = sanitize_text_field( $_POST['phone'] ?? '' );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );
        $source = sanitize_text_field( $_POST['source'] ?? 'admin' );
        
        if ( empty( $name ) || empty( $email ) ) {
            wp_send_json_error( __( 'Name and email are required', 'woocommerce-crm' ) );
        }
        
        $lead_id = $this->create_lead( [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'source' => $source,
        ] );
        
        if ( $lead_id ) {
            wp_send_json_success( [
                'message' => __( 'Lead created successfully', 'woocommerce-crm' ),
                'lead_id' => $lead_id,
            ] );
        } else {
            wp_send_json_error( __( 'Failed to create lead', 'woocommerce-crm' ) );
        }
    }
    
    private function create_lead( $data ) {
        global $wpdb;
        
        // Create ks_leads table if it doesn't exist
        $this->maybe_create_leads_table();
        
        $table = $wpdb->prefix . 'ks_leads';
        
        $result = $wpdb->insert( 
            $table,
            [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'message' => $data['message'],
                'source' => $data['source'],
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s' ]
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    private function maybe_create_leads_table() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ks_leads';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT '',
            message text DEFAULT '',
            source varchar(100) DEFAULT 'unknown',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY source (source),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    private function get_total_leads() {
        global $wpdb;
        $table = $wpdb->prefix . 'ks_leads';
        return $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) ?: 0;
    }
    
    private function get_leads_7_days() {
        global $wpdb;
        $table = $wpdb->prefix . 'ks_leads';
        return $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE created_at >= %s",
            date( 'Y-m-d H:i:s', strtotime( '-7 days' ) )
        ) ) ?: 0;
    }
    
    private function get_orders_7_days() {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return 0;
        }
        
        $orders = wc_get_orders( [
            'date_created' => '>=' . ( time() - 7 * DAY_IN_SECONDS ),
            'limit' => -1,
            'return' => 'ids',
        ] );
        
        return count( $orders );
    }
    
    private function get_orders_today() {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return 0;
        }
        
        $orders = wc_get_orders( [
            'date_created' => '>=' . strtotime( 'today' ),
            'limit' => -1,
            'return' => 'ids',
        ] );
        
        return count( $orders );
    }
    
    private function get_conversion_rate() {
        $leads_7_days = $this->get_leads_7_days();
        $orders_7_days = $this->get_orders_7_days();
        
        if ( $leads_7_days == 0 ) {
            return 0;
        }
        
        return round( ( $orders_7_days / $leads_7_days ) * 100, 1 );
    }
    
    private function render_utm_sources() {
        global $wpdb;
        $table = $wpdb->prefix . 'ks_leads';
        
        $sources = $wpdb->get_results(
            "SELECT source, COUNT(*) as count 
             FROM $table 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY source 
             ORDER BY count DESC 
             LIMIT 5"
        );
        
        if ( empty( $sources ) ) {
            echo '<p>' . esc_html__( 'No UTM source data available yet.', 'woocommerce-crm' ) . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>' . esc_html__( 'Source', 'woocommerce-crm' ) . '</th><th>' . esc_html__( 'Leads (30 days)', 'woocommerce-crm' ) . '</th></tr></thead>';
        echo '<tbody>';
        
        foreach ( $sources as $source ) {
            echo '<tr>';
            echo '<td>' . esc_html( $source->source ) . '</td>';
            echo '<td>' . esc_html( $source->count ) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    public function ajax_search_products() {
        check_ajax_referer( 'ks_admin_nonce', 'ks_nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Permission denied', 'woocommerce-crm' ) );
        }
        
        $search_term = sanitize_text_field( $_POST['search'] ?? '' );
        $page = max( 1, intval( $_POST['page'] ?? 1 ) );
        
        if ( empty( $search_term ) ) {
            wp_send_json_error( __( 'Search term is required', 'woocommerce-crm' ) );
        }
        
        // Use the Product_Search_REST class
        if ( class_exists( 'KS_CRM\Products\Product_Search_REST' ) ) {
            $product_search = new \KS_CRM\Products\Product_Search_REST();
            $results = $product_search->search_products_internal( $search_term, $page );
            wp_send_json_success( $results );
        } else {
            wp_send_json_error( __( 'Product search not available', 'woocommerce-crm' ) );
        }
    }
}