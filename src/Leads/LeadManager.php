<?php
namespace WCP\Leads;

defined( 'ABSPATH' ) || exit;

class LeadManager {

    protected array $integrations = [];

    public function set_integrations( array $integrations ): void {
        $this->integrations = $integrations;
    }

    public function create( array $data ): int {
        global $wpdb;
        $table = $wpdb->prefix . 'wcp_leads';

        $insert = [
            'source' => sanitize_text_field( $data['source'] ?? 'unknown' ),
            'email'  => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : null,
            'phone'  => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : null,
            'name'   => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : null,
            'payload'=> isset( $data['payload'] ) ? wp_json_encode( $data['payload'] ) : null,
        ];

        $wpdb->insert( $table, $insert, [ '%s','%s','%s','%s','%s' ] );
        $id = (int) $wpdb->insert_id;
        if ( $id ) {
            $lead = array_merge( $data, [ 'id' => $id ] );
            $this->sync_integrations( $lead );
            do_action( 'wcp_lead_created', $lead );
        }
        return $id;
    }

    public function list( int $page = 1, int $per_page = 20 ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'wcp_leads';
        $offset = max( 0, ( $page - 1 ) * $per_page );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ), ARRAY_A );
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        return [ 'items' => $rows, 'total' => $total ];
    }

    protected function sync_integrations( array $lead ): void {
        foreach ( $this->integrations as $integration ) {
            try {
                $integration->sync_lead( $lead );
            } catch ( \Throwable $e ) {
                do_action( 'wcp_integration_error', $integration->get_name(), $e->getMessage(), $lead );
            }
        }
    }
}
