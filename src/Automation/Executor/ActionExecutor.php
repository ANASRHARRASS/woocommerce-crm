<?php

namespace Anas\WCCRM\Automation\Executor;

use Anas\WCCRM\Contacts\ContactRepository;
use Anas\WCCRM\Messaging\Dispatch\MessageDispatcher;
use Anas\WCCRM\Orders\OrderMetricsUpdater;

defined('ABSPATH') || exit;

class ActionExecutor
{
    private ContactRepository $contacts;
    private MessageDispatcher $dispatcher;
    private OrderMetricsUpdater $metrics;
    public function __construct(ContactRepository $c, MessageDispatcher $d, OrderMetricsUpdater $m)
    {
        $this->contacts = $c;
        $this->dispatcher = $d;
        $this->metrics = $m;
    }
    public function execute(array $action, array $context): void
    {
        do_action('wccrm_debug_log', 'ActionExecutor:execute', $action);
    }
}
