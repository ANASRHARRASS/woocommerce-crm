<?php

namespace Anas\WCCRM\Social\Inbound;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use Anas\WCCRM\Social\Leads\SocialLeadNormalizer;
use Anas\WCCRM\Social\Leads\SocialLeadRepository;
use Anas\WCCRM\Contacts\ContactRepository;

defined('ABSPATH') || exit;

class SocialWebhookController
{
    private SocialLeadNormalizer $normalizer;
    private SocialLeadRepository $repo;
    private ContactRepository $contacts;
    public function __construct(SocialLeadNormalizer $n, SocialLeadRepository $r, ContactRepository $c)
    {
        $this->normalizer = $n;
        $this->repo = $r;
        $this->contacts = $c;
    }
    public function register_routes(): void
    {
        register_rest_route('wccrm/v1', '/social/webhook', ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'ingest']]);
    }
    public function ingest(WP_REST_Request $req): WP_REST_Response
    {
        $lead = $this->normalizer->normalize($req->get_json_params() ?? []);
        $id = $this->repo->store($lead);
        return new WP_REST_Response(['stored' => $id], 200);
    }
}
