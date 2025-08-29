<?php

namespace Anas\WCCRM\Contacts;

defined('ABSPATH') || exit;

/**
 * Higher level logic for deriving tags from submission/context.
 */
class ContactTagService
{
    private TagRepository $repo;
    public function __construct(TagRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Derive basic tags from submission data & form key.
     */
    public function derive_tags(string $form_key, array $submission): array
    {
        $tags = [];
        // Form key itself becomes a tag (namespaced)
        if ($form_key) $tags[] = 'form_' . sanitize_key($form_key);
        // Channel inference - if phone provided => whatsapp_lead, if email => email_lead
        if (!empty($submission['phone'] ?? '')) $tags[] = 'channel_phone';
        if (!empty($submission['email'] ?? '')) $tags[] = 'channel_email';
        // Interest detection rudimentary: look for product_id / product / sku field
        foreach (['product_id','product','sku'] as $p) {
            if (!empty($submission[$p])) { $tags[] = 'interest_product'; break; }
        }
        // Country detection if present
        if (!empty($submission['country'])) $tags[] = 'geo_' . sanitize_key($submission['country']);
        return array_unique($tags);
    }

    public function assign_tags(int $contact_id, array $tag_keys): void
    {
        $this->repo->assign($contact_id, $tag_keys);
    }
}
