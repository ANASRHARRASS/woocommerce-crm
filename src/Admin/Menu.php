<?php

namespace Anas\WCCRM\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Admin menu bootstrap class
 * Registers top-level CRM Suite menu and submenus
 */
class Menu {

    const MAIN_SLUG = 'wccrm';

    public function __construct() {
        if ( is_admin() ) {
            $this->init();
        }
    }

    protected function init(): void {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_post_wccrm_set_stage', [ $this, 'handle_set_stage' ] );
    }

    public function register_menus(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Top-level menu: CRM Suite
        add_menu_page(
            __( 'CRM Suite', 'wccrm' ),
            __( 'CRM Suite', 'wccrm' ),
            'manage_options',
            self::MAIN_SLUG,
            [ $this, 'render_dashboard' ],
            'dashicons-groups',
            56
        );

        // Submenus
        add_submenu_page(
            self::MAIN_SLUG,
            __( 'Dashboard', 'wccrm' ),
            __( 'Dashboard', 'wccrm' ),
            'manage_options',
            self::MAIN_SLUG,
            [ $this, 'render_dashboard' ]
        );

        add_submenu_page(
            self::MAIN_SLUG,
            __( 'Contacts', 'wccrm' ),
            __( 'Contacts', 'wccrm' ),
            'manage_options',
            self::MAIN_SLUG . '-contacts',
            [ $this, 'render_contacts' ]
        );

        add_submenu_page(
            self::MAIN_SLUG,
            __( 'News Feeds', 'wccrm' ),
            __( 'News Feeds', 'wccrm' ),
            'manage_options',
            self::MAIN_SLUG . '-news',
            [ $this, 'render_news_feeds' ]
        );

        add_submenu_page(
            self::MAIN_SLUG,
            __( 'Shipping Rates', 'wccrm' ),
            __( 'Shipping Rates', 'wccrm' ),
            'manage_options',
            self::MAIN_SLUG . '-shipping',
            [ $this, 'render_shipping_rates' ]
        );

        add_submenu_page(
            self::MAIN_SLUG,
            __( 'Integrations', 'wccrm' ),
            __( 'Integrations', 'wccrm' ),
            'manage_options',
            self::MAIN_SLUG . '-integrations',
            [ $this, 'render_integrations' ]
        );

        add_submenu_page(
            self::MAIN_SLUG,
            __( 'Settings', 'wccrm' ),
            __( 'Settings', 'wccrm' ),
            'manage_options',
            self::MAIN_SLUG . '-settings',
            [ $this, 'render_settings' ]
        );

        // Hidden page for contact detail (no direct menu link)
        add_submenu_page(
            null, // Hidden from menu
            __( 'Contact Detail', 'wccrm' ),
            __( 'Contact Detail', 'wccrm' ),
            'manage_options',
            self::MAIN_SLUG . '-contact',
            [ $this, 'render_contact_detail' ]
        );
    }

    public function render_dashboard(): void {
        $page = new Placeholders\DashboardPage();
        $page->render();
    }

    public function render_contacts(): void {
        $page = new ContactsPage();
        $page->render();
    }

    public function render_contact_detail(): void {
        $page = new ContactDetailPage();
        $page->render();
    }

    public function render_news_feeds(): void {
        $page = new Placeholders\NewsFeedsPage();
        $page->render();
    }

    public function render_shipping_rates(): void {
        $page = new Placeholders\ShippingRatesPage();
        $page->render();
    }

    public function render_integrations(): void {
        $page = new Placeholders\IntegrationsPage();
        $page->render();
    }

    public function render_settings(): void {
        // Link to existing settings page if present
        if ( class_exists( 'WCP\\Admin\\Settings' ) ) {
            wp_redirect( admin_url( 'admin.php?page=wcp-settings' ) );
            exit;
        }
        
        // Otherwise show placeholder
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'CRM Settings', 'wccrm' ) . '</h1>';
        echo '<p>' . esc_html__( 'Settings functionality will be integrated here.', 'wccrm' ) . '</p>';
        echo '</div>';
    }

    public function handle_set_stage(): void {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['wccrm_set_stage_nonce'] ?? '', 'wccrm_set_stage_action' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'wccrm' ) );
        }

        // Verify capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'wccrm' ) );
        }

        $contact_id = absint( $_POST['contact_id'] ?? 0 );
        $stage = intval( $_POST['stage'] ?? 0 );
        $reason = sanitize_text_field( $_POST['reason'] ?? '' );

        if ( ! $contact_id ) {
            $redirect_url = add_query_arg( 
                [ 'wccrm_notice' => 'error', 'message' => urlencode( __( 'Invalid contact ID.', 'wccrm' ) ) ],
                admin_url( 'admin.php?page=' . self::MAIN_SLUG . '-contacts' )
            );
            wp_redirect( $redirect_url );
            exit;
        }

        // Note: This will work once Step 1 is merged with enhanced ContactRepository
        // For now, we'll just show a placeholder response
        try {
            $contact_repository = new \Anas\WCCRM\Contacts\ContactRepository();
            
            // Check if contact exists
            $contact = $contact_repository->find_by_id( $contact_id );
            if ( ! $contact ) {
                throw new \Exception( __( 'Contact not found.', 'wccrm' ) );
            }

            // TODO: Once Step 1 merges, replace this with:
            // $contact_repository->set_stage( $contact_id, $stage, $reason, [] );
            
            // For now, just simulate success
            $redirect_url = add_query_arg( 
                [ 
                    'wccrm_notice' => 'updated',
                    'message' => urlencode( __( 'Stage updated successfully.', 'wccrm' ) )
                ],
                admin_url( 'admin.php?page=' . self::MAIN_SLUG . '-contact&contact_id=' . $contact_id )
            );
        } catch ( \Exception $e ) {
            $redirect_url = add_query_arg( 
                [ 
                    'wccrm_notice' => 'error',
                    'message' => urlencode( $e->getMessage() )
                ],
                admin_url( 'admin.php?page=' . self::MAIN_SLUG . '-contact&contact_id=' . $contact_id )
            );
        }

        wp_redirect( $redirect_url );
        exit;
    }
}