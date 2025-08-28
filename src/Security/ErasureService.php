<?php

namespace Anas\WCCRM\Security;

use Anas\WCCRM\Contacts\ContactRepository;

defined('ABSPATH') || exit;

class ErasureService
{
    private ContactRepository $contacts;
    private AuditLogger $audit;
    public function __construct(ContactRepository $c, AuditLogger $a)
    {
        $this->contacts = $c;
        $this->audit = $a;
    }
    public function erase(int $contact_id): bool
    {
        $this->audit->log('erase', ['contact_id' => $contact_id]);
        return true;
    }
}
