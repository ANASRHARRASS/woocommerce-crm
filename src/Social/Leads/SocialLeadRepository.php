<?php

namespace Anas\WCCRM\Social\Leads;

defined('ABSPATH') || exit;

class SocialLeadRepository
{
    private array $buffer = [];
    public function store(array $lead): int
    {
        $this->buffer[] = $lead;
        return count($this->buffer);
    }
}
