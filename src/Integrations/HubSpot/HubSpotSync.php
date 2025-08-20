<?php

namespace WooCommerceCRM\Integrations\HubSpot;

use WooCommerceCRM\Integrations\HubSpot\HubSpotClient;

class HubSpotSync {
    protected $client;

    public function __construct(HubSpotClient $client) {
        $this->client = $client;
    }

    public function syncContact($contactData) {
        // Logic to sync contact data with HubSpot
        $response = $this->client->createOrUpdateContact($contactData);
        return $response;
    }

    public function syncOrder($orderData) {
        // Logic to sync order data with HubSpot
        $response = $this->client->createOrUpdateOrder($orderData);
        return $response;
    }

    public function syncLead($leadData) {
        // Logic to sync lead data with HubSpot
        $response = $this->client->createOrUpdateLead($leadData);
        return $response;
    }

    public function fetchContacts($filters = []) {
        // Logic to fetch contacts from HubSpot
        $contacts = $this->client->getContacts($filters);
        return $contacts;
    }

    public function fetchOrders($filters = []) {
        // Logic to fetch orders from HubSpot
        $orders = $this->client->getOrders($filters);
        return $orders;
    }
}