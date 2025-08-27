<?php
namespace WCP\Integrations;

class HubSpotIntegration extends AbstractIntegration {
    protected function token_key(): string { return 'hubspot'; }

    public function sync_lead( array $lead ): void {
        do_action( 'wcp_debug', 'hubspot_stub', $lead['id'] ?? null );
    }
}
