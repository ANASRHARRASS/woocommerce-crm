<?php

namespace Anas\WCCRM\COD;

defined('ABSPATH') || exit;

class CodWorkflowService
{
    private CodVerificationService $verifier;
    public function __construct(CodVerificationService $v)
    {
        $this->verifier = $v;
    }
    public function expire_pending_tokens(): void
    {
        $this->verifier->expire_pending_tokens();
    }
}
