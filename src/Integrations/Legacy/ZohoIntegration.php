<?php
namespace WCP\Integrations;

class ZohoIntegration extends AbstractIntegration {
    protected function token_key(): string { return 'zoho'; }
    public function sync_lead( array $lead ): void {
        do_action( 'wcp_debug', 'zoho_sync_stub', $lead['id'] ?? null );
    }
}
