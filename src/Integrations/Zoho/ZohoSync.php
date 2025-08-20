<?php

namespace WooCommerceCRMPlugin\Integrations\Zoho;

use WooCommerceCRMPlugin\Integrations\Zoho\ZohoClient;

class ZohoSync {
    protected $zohoClient;

    public function __construct(ZohoClient $zohoClient) {
        $this->zohoClient = $zohoClient;
    }

    public function syncContacts($contacts) {
        foreach ($contacts as $contact) {
            // Logic to sync contact with Zoho
            $this->zohoClient->createOrUpdateContact($contact);
        }
    }

    public function syncOrders($orders) {
        foreach ($orders as $order) {
            // Logic to sync order with Zoho
            $this->zohoClient->createOrUpdateOrder($order);
        }
    }

    public function syncShippingMethods($shippingMethods) {
        foreach ($shippingMethods as $method) {
            // Logic to sync shipping methods with Zoho
            $this->zohoClient->createOrUpdateShippingMethod($method);
        }
    }

    public function captureLeadFromSocialMedia($leadData) {
        // Logic to capture leads from social media platforms
        $this->zohoClient->createLead($leadData);
    }
}