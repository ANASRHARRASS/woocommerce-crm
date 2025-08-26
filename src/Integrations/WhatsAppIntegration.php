<?php
namespace WCP\Integrations;

class WhatsAppIntegration extends AbstractIntegration {
    protected function token_key(): string { return 'whatsapp'; }

    public function sync_lead( array $lead ): void {
        do_action( 'wcp_debug', 'whatsapp_notify_stub', $lead['id'] ?? null );
    }
}
