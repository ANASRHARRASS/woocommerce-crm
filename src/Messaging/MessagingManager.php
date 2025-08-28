<?php

namespace Anas\WCCRM\Messaging;

use Anas\WCCRM\Messaging\Dispatch\MessageDispatcher;

defined('ABSPATH') || exit;

class MessagingManager
{
    private MessageDispatcher $dispatcher;
    public function __construct(MessageDispatcher $d)
    {
        $this->dispatcher = $d;
    }
}
