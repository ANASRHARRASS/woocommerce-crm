<?php

namespace Anas\WCCRM\Security;

defined('ABSPATH') || exit;

class DataRetentionService
{
    public function run_cycle(): void
    {
        do_action('wccrm_debug_log', 'Retention:cycle');
    }
}
