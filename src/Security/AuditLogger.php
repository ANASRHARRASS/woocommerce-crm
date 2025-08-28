<?php

namespace Anas\WCCRM\Security;

defined('ABSPATH') || exit;

class AuditLogger
{
    public function log(string $action, array $context = []): void
    {
        do_action('wccrm_debug_log', 'Audit', $action, $context);
    }
}
