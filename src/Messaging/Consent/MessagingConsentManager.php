<?php

namespace Anas\WCCRM\Messaging\Consent;

defined('ABSPATH') || exit;

class MessagingConsentManager
{
    public function has_consent(int $contact_id, string $channel): bool
    {
        return apply_filters('wccrm_has_consent', true, $contact_id, $channel);
    }
}
