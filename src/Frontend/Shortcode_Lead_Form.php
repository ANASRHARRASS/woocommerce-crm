<?php
namespace KS_CRM\Frontend;

defined( 'ABSPATH' ) || exit;

class Shortcode_Lead_Form {
    
    private $namespace = 'ks-crm/v1';
    
    public function __construct() {
        add_shortcode( 'kscrm_lead_form', [ $this, 'render_shortcode' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_scripts' ] );
    }
    
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'title' => __( 'Get in Touch', 'woocommerce-crm' ),
            'submit_text' => __( 'Submit', 'woocommerce-crm' ),
            'success_message' => __( 'Thank you! We\'ll be in touch soon.', 'woocommerce-crm' ),
            'show_phone' => 'yes',
            'show_message' => 'yes',
            'css_class' => '',
            'source' => '',
        ], $atts, 'kscrm_lead_form' );
        
        // Flag that we need to enqueue scripts
        global $ks_crm_lead_form_loaded;
        $ks_crm_lead_form_loaded = true;
        
        // Extract source from URL parameters if not specified
        $source = $atts['source'];
        if ( empty( $source ) ) {
            $source = $this->get_source_from_url();
        }
        
        $form_id = 'ks-lead-form-' . uniqid();
        
        ob_start();
        ?>
        <div class="ks-lead-form-container <?php echo esc_attr( $atts['css_class'] ); ?>">
            <?php if ( ! empty( $atts['title'] ) ) : ?>
                <h3 class="ks-lead-form-title"><?php echo esc_html( $atts['title'] ); ?></h3>
            <?php endif; ?>
            
            <form id="<?php echo esc_attr( $form_id ); ?>" class="ks-lead-form" data-source="<?php echo esc_attr( $source ); ?>">
                <?php wp_nonce_field( 'ks_lead_form_nonce', 'ks_nonce' ); ?>
                
                <div class="ks-form-messages" style="display: none;"></div>
                
                <div class="ks-form-row">
                    <label for="<?php echo esc_attr( $form_id ); ?>_name" class="ks-form-label">
                        <?php esc_html_e( 'Name', 'woocommerce-crm' ); ?> <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="<?php echo esc_attr( $form_id ); ?>_name" 
                        name="name" 
                        class="ks-form-input" 
                        required 
                        autocomplete="name"
                    >
                </div>
                
                <div class="ks-form-row">
                    <label for="<?php echo esc_attr( $form_id ); ?>_email" class="ks-form-label">
                        <?php esc_html_e( 'Email', 'woocommerce-crm' ); ?> <span class="required">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="<?php echo esc_attr( $form_id ); ?>_email" 
                        name="email" 
                        class="ks-form-input" 
                        required 
                        autocomplete="email"
                    >
                </div>
                
                <?php if ( $atts['show_phone'] === 'yes' ) : ?>
                <div class="ks-form-row">
                    <label for="<?php echo esc_attr( $form_id ); ?>_phone" class="ks-form-label">
                        <?php esc_html_e( 'Phone', 'woocommerce-crm' ); ?>
                    </label>
                    <input 
                        type="tel" 
                        id="<?php echo esc_attr( $form_id ); ?>_phone" 
                        name="phone" 
                        class="ks-form-input" 
                        autocomplete="tel"
                    >
                </div>
                <?php endif; ?>
                
                <?php if ( $atts['show_message'] === 'yes' ) : ?>
                <div class="ks-form-row">
                    <label for="<?php echo esc_attr( $form_id ); ?>_message" class="ks-form-label">
                        <?php esc_html_e( 'Message', 'woocommerce-crm' ); ?>
                    </label>
                    <textarea 
                        id="<?php echo esc_attr( $form_id ); ?>_message" 
                        name="message" 
                        class="ks-form-textarea" 
                        rows="4"
                    ></textarea>
                </div>
                <?php endif; ?>
                
                <input type="hidden" name="source" value="<?php echo esc_attr( $source ); ?>">
                
                <div class="ks-form-row">
                    <button type="submit" class="ks-form-submit">
                        <?php echo esc_html( $atts['submit_text'] ); ?>
                    </button>
                </div>
            </form>
            
            <div class="ks-success-message" style="display: none;">
                <p><?php echo esc_html( $atts['success_message'] ); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function maybe_enqueue_scripts() {
        global $ks_crm_lead_form_loaded;
        
        if ( ! $ks_crm_lead_form_loaded ) {
            return;
        }
        
        wp_enqueue_script(
            'ks-lead-form',
            WCCRM_PLUGIN_URL . 'assets/js/lead-form.js',
            [ 'jquery' ],
            WCCRM_VERSION,
            true
        );
        
        wp_enqueue_style(
            'ks-frontend-css',
            WCCRM_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            WCCRM_VERSION
        );
        
        wp_localize_script( 'ks-lead-form', 'ks_lead_form', [
            'rest_url' => rest_url( $this->namespace . '/leads' ),
            'rest_nonce' => wp_create_nonce( 'wp_rest' ),
            'messages' => [
                'error' => __( 'Something went wrong. Please try again.', 'woocommerce-crm' ),
                'required' => __( 'This field is required.', 'woocommerce-crm' ),
                'invalid_email' => __( 'Please enter a valid email address.', 'woocommerce-crm' ),
                'submitting' => __( 'Submitting...', 'woocommerce-crm' ),
            ],
        ] );
    }
    
    public function register_rest_routes() {
        register_rest_route( $this->namespace, '/leads', [
            'methods' => 'POST',
            'callback' => [ $this, 'create_lead' ],
            'permission_callback' => [ $this, 'permission_callback' ],
            'args' => [
                'name' => [
                    'required' => true,
                    'validate_callback' => function( $param ) {
                        return is_string( $param ) && strlen( trim( $param ) ) >= 2;
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'email' => [
                    'required' => true,
                    'validate_callback' => function( $param ) {
                        return is_email( $param );
                    },
                    'sanitize_callback' => 'sanitize_email',
                ],
                'phone' => [
                    'required' => false,
                    'validate_callback' => function( $param ) {
                        return empty( $param ) || ( is_string( $param ) && strlen( $param ) <= 50 );
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'message' => [
                    'required' => false,
                    'validate_callback' => function( $param ) {
                        return empty( $param ) || ( is_string( $param ) && strlen( $param ) <= 2000 );
                    },
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'source' => [
                    'required' => false,
                    'validate_callback' => function( $param ) {
                        return empty( $param ) || ( is_string( $param ) && strlen( $param ) <= 100 );
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );
    }
    
    public function permission_callback( $request ) {
        // Apply rate limiting
        $this->apply_rate_limiting();
        
        // Check nonce for security
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new \WP_Error( 'invalid_nonce', __( 'Invalid security token.', 'woocommerce-crm' ), [ 'status' => 403 ] );
        }
        
        return true;
    }
    
    public function create_lead( $request ) {
        $name = $request->get_param( 'name' );
        $email = $request->get_param( 'email' );
        $phone = $request->get_param( 'phone' );
        $message = $request->get_param( 'message' );
        $source = $request->get_param( 'source' ) ?: 'website';
        
        // Create the lead
        $lead_id = $this->save_lead( [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'source' => $source,
        ] );
        
        if ( ! $lead_id ) {
            return new \WP_Error( 'create_failed', __( 'Failed to create lead.', 'woocommerce-crm' ), [ 'status' => 500 ] );
        }
        
        // Fire action for integrations
        do_action( 'ks_crm_lead_created', $lead_id, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'source' => $source,
        ] );
        
        return [
            'success' => true,
            'message' => __( 'Lead created successfully.', 'woocommerce-crm' ),
            'lead_id' => $lead_id,
        ];
    }
    
    private function save_lead( $data ) {
        global $wpdb;
        
        // Ensure table exists
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
    
    private function get_source_from_url() {
        // Check for various source parameters
        $source_params = [ 'source', 'utm_source', 'ref', 'campaign' ];
        
        foreach ( $source_params as $param ) {
            if ( ! empty( $_GET[ $param ] ) ) {
                return sanitize_text_field( $_GET[ $param ] );
            }
        }
        
        // Default source
        return 'website';
    }
    
    private function apply_rate_limiting() {
        $client_ip = $this->get_client_ip();
        $rate_limit_key = 'ks_crm_lead_rate_limit_' . md5( $client_ip );
        
        $submissions = get_transient( $rate_limit_key );
        
        if ( false === $submissions ) {
            // First submission in this time window
            set_transient( $rate_limit_key, 1, 5 * MINUTE_IN_SECONDS );
        } else {
            $submissions = intval( $submissions );
            
            // Allow up to 5 submissions per 5 minutes
            if ( $submissions >= 5 ) {
                wp_die( 
                    __( 'Too many submissions. Please wait before submitting again.', 'woocommerce-crm' ),
                    __( 'Rate Limit Exceeded', 'woocommerce-crm' ),
                    [ 'response' => 429 ]
                );
            }
            
            set_transient( $rate_limit_key, $submissions + 1, 5 * MINUTE_IN_SECONDS );
        }
    }
    
    private function get_client_ip() {
        $ip_keys = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ];
        
        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = $_SERVER[ $key ];
                
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = explode( ',', $ip )[0];
                }
                
                $ip = trim( $ip );
                
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }
        }
        
        return '127.0.0.1';
    }
}