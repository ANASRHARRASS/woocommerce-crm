<?php

namespace WooCommerceCRMPlugin\Utils;

class Helpers {
    public static function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }

    public static function formatCurrency($amount, $currencySymbol = '$') {
        return $currencySymbol . number_format($amount, 2);
    }

    public static function generateNonce($action) {
        return wp_create_nonce($action);
    }

    public static function checkNonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }

    public static function getCurrentUser() {
        return wp_get_current_user();
    }

    public static function isUserLoggedIn() {
        return is_user_logged_in();
    }

    public static function redirect($url) {
        wp_redirect($url);
        exit;
    }

    public static function getWooCommerceProductAttributes($productId) {
        $product = wc_get_product($productId);
        return $product ? $product->get_attributes() : [];
    }
}