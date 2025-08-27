<?php
namespace WCP\Integrations;

class GoogleDriveIntegration extends AbstractIntegration {
    protected function token_key(): string { return 'google_drive'; }
    public function sync_lead( array $lead ): void {
        do_action( 'wcp_debug', 'gdrive_export_stub', $lead['id'] ?? null );
    }
}
