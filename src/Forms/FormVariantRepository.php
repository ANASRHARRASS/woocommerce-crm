<?php

namespace Anas\WCCRM\Forms;

defined('ABSPATH') || exit;

/**
 * Repository for product-specific form variant schemas.
 */
class FormVariantRepository
{
    private string $table;
    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'wccrm_form_product_variants';
    }
    public function get_variant_fields(int $form_id, int $product_id): array
    {
        if ($form_id <= 0 || $product_id <= 0) return [];
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT variant_schema_json FROM {$this->table} WHERE form_id=%d AND product_id=%d", $form_id, $product_id), ARRAY_A);
        if (!$row) return [];
        $decoded = json_decode($row['variant_schema_json'] ?? '[]', true);
        if (!is_array($decoded)) return [];
        return $decoded['fields'] ?? [];
    }
    public function merge_fields(array $base, array $variant): array
    {
        if (!$variant) return $base;
        $map = [];
        foreach ($base as $f) {
            if (!empty($f['name'])) $map[$f['name']] = $f;
        }
        foreach ($variant as $vf) {
            if (!empty($vf['name']) && isset($map[$vf['name']])) {
                $map[$vf['name']] = array_merge($map[$vf['name']], $vf);
            } elseif (!empty($vf['name'])) {
                $map[$vf['name']] = $vf;
            }
        }
        $ordered = [];
        $seen = [];
        foreach ($base as $f) {
            $n = $f['name'] ?? '';
            if ($n && isset($map[$n])) {
                $ordered[] = $map[$n];
                $seen[$n] = true;
            }
        }
        foreach ($map as $k => $f) {
            if (empty($seen[$k])) $ordered[] = $f;
        }
        return $ordered;
    }
}
