<?php
namespace WCP\Social;

use WCP\Leads\LeadManager;

defined( 'ABSPATH' ) || exit;

/**
 * Generic ingestion adapter for social platform payloads.
 * Real webhooks would map to ingest() with platform identifiers.
 */
class SocialLeadIngestor {

    protected LeadManager $leads;

    public function __construct( LeadManager $leads ) {
        $this->leads = $leads;
        add_action( 'wcp_ingest_social_lead', [ $this, 'ingest' ], 10, 2 );
    }

    public function ingest( string $platform, array $payload ): int {
        $data = [
            'source' => 'social_' . sanitize_key( $platform ),
            'email'  => $payload['email'] ?? null,
            'phone'  => $payload['phone'] ?? null,
            'name'   => $payload['name'] ?? null,
            'payload'=> $payload,
        ];
        return $this->leads->create_lead( $data );
    }
}
