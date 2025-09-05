<?php

namespace Anas\WCCRM\Utils;

defined('ABSPATH') || exit;

class WebhookAuth
{
    public static function secret(): ?string
    {
        if (\defined('WCCRM_WEBHOOK_SECRET')) {
            $val = constant('WCCRM_WEBHOOK_SECRET');
            if ($val) return $val;
        }
        $opt = get_option('wccrm_webhook_secret');
        return $opt ?: null;
    }

    public static function verify(string $raw_body, array $headers): bool
    {
        $secret = self::secret();
        if (!$secret) {
            return true; // no secret configured, accept (can enforce later)
        }
        $sigHeader = self::find_header($headers, 'x-wccrm-signature');
        $tsHeader  = self::find_header($headers, 'x-wccrm-timestamp');
        if (!$sigHeader || !$tsHeader) {
            return false;
        }
        $ts = (int) $tsHeader;
        if (abs(time() - $ts) > 300) { // 5 min window
            return false;
        }
        $expected = hash_hmac('sha256', $ts . '.' . $raw_body, $secret);
        return hash_equals($expected, $sigHeader);
    }

    private static function find_header(array $headers, string $key): ?string
    {
        $lk = strtolower($key);
        foreach ($headers as $k => $v) {
            if (strtolower($k) === $lk) return is_array($v) ? (string)reset($v) : (string)$v;
        }
        // Fallback to apache_request_headers if available
        if (function_exists('apache_request_headers')) {
            foreach (apache_request_headers() as $k => $v) {
                if (strtolower($k) === $lk) return (string)$v;
            }
        }
        return null;
    }
}
