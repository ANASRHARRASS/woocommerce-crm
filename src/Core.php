<?php

namespace WCP;

use WCP\Leads\LeadManager;
use WCP\REST\LeadsController;
use WCP\Integrations\HubSpotIntegration;

defined( 'ABSPATH' ) || exit;

class Core {

    protected LeadManager $leads;
    protected array $integrations = [];

    public function init(): void {
        $this->leads = new LeadManager();
        $this->load_integrations();
        $this->register_hooks();
        ( new LeadsController( $this->leads ) )->register_routes();
        do_action( 'wcp_after_init', $this );
    }

    protected function load_integrations(): void {
        $candidates = [ new HubSpotIntegration() /* add others */ ];
        foreach ( $candidates as $integration ) {
            if ( $integration->is_enabled() ) {
                $this->integrations[ $integration->get_name() ] = $integration;
            }
        }
        $this->leads->set_integrations( $this->integrations );
    }

    protected function register_hooks(): void {
        add_action( 'init', [ $this, 'maybe_upgrade' ] );
    }

    public function maybe_upgrade(): void {
        $stored = get_option( 'wcp_version' );
        if ( $stored && version_compare( $stored, WCP_VERSION, '<' ) ) {
            update_option( 'wcp_version', WCP_VERSION );
            // Future migrations here.
        }
    }

    public function leads(): LeadManager {
        return $this->leads;
    }
}