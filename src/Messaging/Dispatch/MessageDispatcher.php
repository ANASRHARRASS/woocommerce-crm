<?php

namespace Anas\WCCRM\Messaging\Dispatch;

use Anas\WCCRM\Messaging\Templates\TemplateRepository;
use Anas\WCCRM\Messaging\Consent\MessagingConsentManager;
use Anas\WCCRM\Messaging\Queue\MessageQueue;

defined('ABSPATH') || exit;

class MessageDispatcher
{
    private TemplateRepository $templates;
    private MessagingConsentManager $consent;
    private ?MessageQueue $queue;
    public function __construct(TemplateRepository $templates, MessagingConsentManager $consent, MessageQueue $queue = null)
    {
        $this->templates = $templates;
        $this->consent = $consent;
        $this->queue = $queue;
    }
    public function process_queue(): void
    {
        if (!$this->queue) return;
        $batch = $this->queue->due();
        foreach ($batch as $item) {
            $ok = false;
            try {
                $ok = $this->deliver_item($item);
            } catch (\Throwable $e) {
                $this->queue->mark_failed($item['id'], $e->getMessage());
                continue;
            }
            if ($ok) {
                $this->queue->mark_sent($item['id']);
            }
        }
    }
    private function deliver_item(array $item): bool
    {
        if (!$this->consent->has_consent((int)$item['contact_id'], $item['channel'])) return false;
        if (!empty($item['template_key'])) {
            $tpl = $this->templates->get($item['template_key']);
            if (!$tpl) return false;
        }
        // Placeholder: actually send via wp_mail or API.
        return true;
    }
    public function send(int $contact_id, string $template_key, array $vars = []): bool
    {
        if (!$this->consent->has_consent($contact_id, 'email')) return false;
        $tpl = $this->templates->get($template_key);
        return (bool)$tpl;
    }
}
