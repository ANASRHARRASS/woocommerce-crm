<?php

namespace WCP;

use WCP\Leads\LeadManager;
use WCP\Integrations\HubSpotIntegration;
use WCP\Integrations\ZohoIntegration;
use WCP\Integrations\GoogleDriveIntegration;
use WCP\Integrations\WhatsAppIntegration;
use WCP\WooCommerce\OrderSync;
use WCP\Social\SocialLeadIngestor;

defined( 'ABSPATH' ) || exit;

class Core {

    const VERSION = '1.0.0';

    protected LeadManager $leads;
    protected array $integrations = [];

    public function init(): void {
        $this->register_hooks();
        $this->boot_services();
        do_action( 'wcp_after_init', $this );
    }

    protected function register_hooks(): void {
        add_action( 'init', [ $this, 'maybe_upgrade' ] );
    }

    protected function boot_services(): void {
        $this->leads = new LeadManager();
        $this->load_integrations();
        $this->load_woocommerce();
        $this->load_social_ingestor();
    }

    protected function load_integrations(): void {
        $instances = [
            new HubSpotIntegration(),
            new ZohoIntegration(),
            new GoogleDriveIntegration(),
            new WhatsAppIntegration(),
        ];
        foreach ( $instances as $integration ) {
            if ( $integration->is_enabled() ) {
                $this->integrations[ $integration->get_name() ] = $integration;
            }
        }
        $this->leads->set_integrations( $this->integrations );
    }

    protected function load_woocommerce(): void {
        if ( class_exists( 'WooCommerce' ) ) {
            new OrderSync( $this->leads );
        }
    }

    protected function load_social_ingestor(): void {
        new SocialLeadIngestor( $this->leads );
    }

    public function maybe_upgrade(): void {
        $stored = get_option( 'wcp_version' );
        if ( $stored && version_compare( $stored, self::VERSION, '<' ) ) {
            // Future upgrade routines.
            update_option( 'wcp_version', self::VERSION );
        }
    }

    public function get_lead_manager(): LeadManager {
        return $this->leads;
    }
}