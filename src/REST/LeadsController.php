<?php
namespace WCP\REST;

use WCP\Leads\LeadManager;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

class LeadsController {

    protected LeadManager $leads;
    const NS = 'wcp/v1';

    public function __construct( LeadManager $leads ) {
        $this->leads = $leads;
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        register_rest_route( self::NS, '/leads', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'list' ],
                'permission_callback' => function () { return current_user_can( 'manage_options' ); },
                'args'                => [
                    'page' => [ 'validate_callback' => 'is_numeric', 'default' => 1 ]
                ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create' ],
                'permission_callback' => [ $this, 'ingest_permission' ],
            ],
        ] );
    }

    public function list( WP_REST_Request $req ): WP_REST_Response {
        $page = max( 1, (int) $req->get_param( 'page' ) );
        $data = $this->leads->list( $page, 20 );
        return new WP_REST_Response( $data, 200 );
    }

    public function create( WP_REST_Request $req ): WP_REST_Response {
        $body = $req->get_json_params() ?: [];
        $lead = [
            'email'   => $body['email'] ?? null,
            'phone'   => $body['phone'] ?? null,
            'name'    => $body['name'] ?? null,
            'source'  => $body['source'] ?? 'api',
            'payload' => $body['payload'] ?? [],
        ];
        $id = $this->leads->create( $lead );
        if ( ! $id ) {
            return new WP_REST_Response( [ 'error' => 'create_failed' ], 500 );
        }
        return new WP_REST_Response( [ 'id' => $id ], 201 );
    }

    public function ingest_permission( WP_REST_Request $req ): bool {
        $header_key = $req->get_header( 'X-WCP-Key' );
        $stored = get_option( 'wcp_api_key' );
        return $stored && hash_equals( $stored, (string) $header_key );
    }
}
