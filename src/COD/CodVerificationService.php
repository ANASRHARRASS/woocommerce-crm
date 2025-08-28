<?php

namespace Anas\WCCRM\COD;

defined('ABSPATH') || exit;

class CodVerificationService
{
    public function create_token(int $order_id): string
    {
        return wp_generate_password(8, false);
    }
    public function expire_pending_tokens(): void {}
}
