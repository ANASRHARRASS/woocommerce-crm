<?php

namespace Anas\WCCRM\Social\Leads;

defined('ABSPATH') || exit;

class SocialLeadNormalizer
{
    public function normalize(array $payload): array
    {
        return ['email' => $payload['email'] ?? null, 'phone' => $payload['phone'] ?? null, 'name' => $payload['name'] ?? null, 'raw' => $payload];
    }
}
