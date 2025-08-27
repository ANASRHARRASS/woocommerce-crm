<?php
namespace WCP\Integrations;

defined( 'ABSPATH' ) || exit;

abstract class AbstractIntegration {

    public function get_name(): string {
        return static::class;
    }

    public function is_enabled(): bool {
        $tokens = get_option( 'wcp_tokens', [] );
        return $this->token_key() && ! empty( $tokens[ $this->token_key() ] );
    }

    abstract protected function token_key(): string;

    /**
     * Sync a lead to the external service (stub).
     */
    public function sync_lead( array $lead ): void {
        // Override in concrete integration.
    }

    protected function token(): ?string {
        $tokens = get_option( 'wcp_tokens', [] );
        return $tokens[ $this->token_key() ] ?? null;
    }
}
