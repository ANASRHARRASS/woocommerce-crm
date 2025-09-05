<?php

namespace Anas\WCCRM\Forms;

defined('ABSPATH') || exit;

/**
 * Repository for category (taxonomy term) specific form variant schemas.
 * Supports merging multiple category overrides deterministically.
 */
class FormCategoryVariantRepository
{
    private string $table;
    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'wccrm_form_category_variants';
    }

    /**
     * Get merged variant fields for a form across an ordered list of term contexts.
     * @param int $form_id
     * @param array<int,array{term_id:int,taxonomy:string}> $terms Ordered (earlier lower priority, later overrides) 
     */
    public function get_variant_fields(int $form_id, array $terms): array
    {
        if ($form_id <= 0 || empty($terms)) return [];
        global $wpdb;
        $accumulated = [];
        $seenNames = [];
        foreach ($terms as $ctx) {
            $tid = (int)($ctx['term_id'] ?? 0);
            $tax = $ctx['taxonomy'] ?? 'product_cat';
            if ($tid <= 0) continue;
            $row = $wpdb->get_row($wpdb->prepare("SELECT variant_schema_json FROM {$this->table} WHERE form_id=%d AND term_id=%d AND taxonomy=%s", $form_id, $tid, $tax), ARRAY_A);
            if (!$row) continue;
            $decoded = json_decode($row['variant_schema_json'] ?? '[]', true);
            if (!is_array($decoded)) continue;
            $fields = $decoded['fields'] ?? [];
            if (!is_array($fields)) continue;
            // merge overlay semantics like product variant merge
            foreach ($fields as $f) {
                $name = $f['name'] ?? '';
                if (!$name) continue;
                $accumulated[$name] = array_merge($accumulated[$name] ?? [], $f);
                $seenNames[$name] = true;
            }
        }
        if (!$accumulated) return [];
        // Return in natural position order using provided order in last term override precedence
        return array_values($accumulated);
    }

    /**
     * Merge helper (base + categoryVariant + productVariant) similar to FormVariantRepository::merge_fields
     */
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
