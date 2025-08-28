<?php
namespace KS_CRM\Admin;

defined( 'ABSPATH' ) || exit;

class Lead_Columns {
    
    public function __construct() {
        // Custom post type for leads
        add_action( 'init', [ $this, 'register_lead_post_type' ] );
        
        // Add custom columns to leads admin listing
        add_filter( 'manage_ks_lead_posts_columns', [ $this, 'add_custom_columns' ] );
        add_action( 'manage_ks_lead_posts_custom_column', [ $this, 'render_custom_columns' ], 10, 2 );
        add_filter( 'manage_edit-ks_lead_sortable_columns', [ $this, 'make_columns_sortable' ] );
        
        // Add filter by source
        add_action( 'restrict_manage_posts', [ $this, 'add_source_filter' ] );
        add_filter( 'parse_query', [ $this, 'filter_by_source' ] );
        
        // Handle sorting
        add_action( 'pre_get_posts', [ $this, 'handle_sorting' ] );
        
        // Sync from ks_leads table to posts
        add_action( 'admin_init', [ $this, 'sync_leads_to_posts' ] );
    }
    
    public function register_lead_post_type() {
        $args = [
            'label' => __( 'Leads', 'woocommerce-crm' ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 56,
            'menu_icon' => 'dashicons-groups',
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => [ 'title', 'custom-fields' ],
            'labels' => [
                'name' => __( 'Leads', 'woocommerce-crm' ),
                'singular_name' => __( 'Lead', 'woocommerce-crm' ),
                'menu_name' => __( 'Woo CRM', 'woocommerce-crm' ),
                'add_new' => __( 'Add New Lead', 'woocommerce-crm' ),
                'add_new_item' => __( 'Add New Lead', 'woocommerce-crm' ),
                'edit' => __( 'Edit', 'woocommerce-crm' ),
                'edit_item' => __( 'Edit Lead', 'woocommerce-crm' ),
                'new_item' => __( 'New Lead', 'woocommerce-crm' ),
                'view' => __( 'View Lead', 'woocommerce-crm' ),
                'view_item' => __( 'View Lead', 'woocommerce-crm' ),
                'search_items' => __( 'Search Leads', 'woocommerce-crm' ),
                'not_found' => __( 'No Leads Found', 'woocommerce-crm' ),
                'not_found_in_trash' => __( 'No Leads Found in Trash', 'woocommerce-crm' ),
            ],
        ];
        
        register_post_type( 'ks_lead', $args );
    }
    
    public function add_custom_columns( $columns ) {
        // Remove default columns we don't need
        unset( $columns['date'] );
        
        // Add our custom columns
        $new_columns = [
            'cb' => $columns['cb'],
            'title' => __( 'Name', 'woocommerce-crm' ),
            'email' => __( 'Email', 'woocommerce-crm' ),
            'phone' => __( 'Phone', 'woocommerce-crm' ),
            'source' => __( 'Source', 'woocommerce-crm' ),
            'created' => __( 'Created', 'woocommerce-crm' ),
        ];
        
        return $new_columns;
    }
    
    public function render_custom_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'email':
                $email = get_post_meta( $post_id, '_ks_lead_email', true );
                if ( $email ) {
                    echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
                } else {
                    echo '—';
                }
                break;
                
            case 'phone':
                $phone = get_post_meta( $post_id, '_ks_lead_phone', true );
                if ( $phone ) {
                    echo '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>';
                } else {
                    echo '—';
                }
                break;
                
            case 'source':
                $source = get_post_meta( $post_id, '_ks_lead_source', true );
                if ( $source ) {
                    $source_formatted = ucfirst( str_replace( '_', ' ', $source ) );
                    echo '<span class="ks-source-' . esc_attr( $source ) . '">' . esc_html( $source_formatted ) . '</span>';
                } else {
                    echo '—';
                }
                break;
                
            case 'created':
                $created = get_post_meta( $post_id, '_ks_lead_created_at', true );
                if ( $created ) {
                    echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $created ) ) );
                } else {
                    echo get_the_date( '', $post_id );
                }
                break;
        }
    }
    
    public function make_columns_sortable( $columns ) {
        $columns['source'] = 'source';
        $columns['created'] = 'created';
        $columns['email'] = 'email';
        return $columns;
    }
    
    public function add_source_filter() {
        global $typenow;
        
        if ( $typenow !== 'ks_lead' ) {
            return;
        }
        
        $selected = $_GET['source_filter'] ?? '';
        
        echo '<select name="source_filter" id="source_filter">';
        echo '<option value="">' . esc_html__( 'All Sources', 'woocommerce-crm' ) . '</option>';
        
        $sources = $this->get_available_sources();
        foreach ( $sources as $source ) {
            echo '<option value="' . esc_attr( $source ) . '"' . selected( $selected, $source, false ) . '>';
            echo esc_html( ucfirst( str_replace( '_', ' ', $source ) ) );
            echo '</option>';
        }
        
        echo '</select>';
    }
    
    public function filter_by_source( $query ) {
        global $pagenow;
        
        if ( ! is_admin() || $pagenow !== 'edit.php' ) {
            return;
        }
        
        if ( ! isset( $_GET['post_type'] ) || $_GET['post_type'] !== 'ks_lead' ) {
            return;
        }
        
        if ( ! isset( $_GET['source_filter'] ) || empty( $_GET['source_filter'] ) ) {
            return;
        }
        
        $source = sanitize_text_field( $_GET['source_filter'] );
        $query->query_vars['meta_key'] = '_ks_lead_source';
        $query->query_vars['meta_value'] = $source;
    }
    
    public function handle_sorting( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }
        
        if ( $query->get( 'post_type' ) !== 'ks_lead' ) {
            return;
        }
        
        $orderby = $query->get( 'orderby' );
        
        switch ( $orderby ) {
            case 'source':
                $query->set( 'meta_key', '_ks_lead_source' );
                $query->set( 'orderby', 'meta_value' );
                break;
                
            case 'created':
                $query->set( 'meta_key', '_ks_lead_created_at' );
                $query->set( 'orderby', 'meta_value' );
                break;
                
            case 'email':
                $query->set( 'meta_key', '_ks_lead_email' );
                $query->set( 'orderby', 'meta_value' );
                break;
        }
    }
    
    private function get_available_sources() {
        global $wpdb;
        
        $results = $wpdb->get_col( "
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_ks_lead_source' 
            AND meta_value != '' 
            ORDER BY meta_value
        " );
        
        return $results ?: [ 'admin', 'website', 'facebook', 'whatsapp', 'other' ];
    }
    
    public function sync_leads_to_posts() {
        // Only run this once per day to avoid performance issues
        $last_sync = get_option( 'ks_leads_last_sync', 0 );
        if ( ( time() - $last_sync ) < DAY_IN_SECONDS ) {
            return;
        }
        
        global $wpdb;
        $leads_table = $wpdb->prefix . 'ks_leads';
        
        // Check if the table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$leads_table'" ) !== $leads_table ) {
            return;
        }
        
        // Get leads that don't have corresponding posts
        $leads = $wpdb->get_results( "
            SELECT l.* 
            FROM $leads_table l 
            LEFT JOIN {$wpdb->postmeta} pm ON pm.meta_value = l.id AND pm.meta_key = '_ks_lead_id'
            WHERE pm.meta_id IS NULL
            ORDER BY l.created_at DESC
            LIMIT 100
        " );
        
        foreach ( $leads as $lead ) {
            $this->create_lead_post( $lead );
        }
        
        update_option( 'ks_leads_last_sync', time() );
    }
    
    private function create_lead_post( $lead ) {
        $post_data = [
            'post_title' => $lead->name ?: ( $lead->email ?: 'Lead #' . $lead->id ),
            'post_type' => 'ks_lead',
            'post_status' => 'publish',
            'post_date' => $lead->created_at,
            'meta_input' => [
                '_ks_lead_id' => $lead->id,
                '_ks_lead_email' => $lead->email,
                '_ks_lead_phone' => $lead->phone,
                '_ks_lead_source' => $lead->source,
                '_ks_lead_message' => $lead->message,
                '_ks_lead_created_at' => $lead->created_at,
            ],
        ];
        
        $post_id = wp_insert_post( $post_data );
        
        if ( is_wp_error( $post_id ) ) {
            error_log( 'Failed to create lead post: ' . $post_id->get_error_message() );
        }
        
        return $post_id;
    }
}