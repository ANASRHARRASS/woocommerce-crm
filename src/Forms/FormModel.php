<?php

namespace Anas\WCCRM\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Form model value object
 */
class FormModel {

    public int $id;
    public string $form_key;
    public string $name;
    public array $schema;
    public string $status;
    public string $created_at;
    public string $updated_at;

    public function __construct( array $data ) {
        $this->id = (int) ( $data['id'] ?? 0 );
        $this->form_key = sanitize_key( $data['form_key'] ?? '' );
        $this->name = sanitize_text_field( $data['name'] ?? '' );
        $this->schema = $this->parse_schema( $data['schema_json'] ?? $data['schema'] ?? [] );
        $this->status = sanitize_text_field( $data['status'] ?? 'active' );
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? '';
    }

    public function get_fields(): array {
        return $this->schema['fields'] ?? [];
    }

    public function get_settings(): array {
        return $this->schema['settings'] ?? [];
    }

    public function is_active(): bool {
        return $this->status === 'active';
    }

    private function parse_schema( $schema ): array {
        if ( is_string( $schema ) ) {
            $decoded = json_decode( $schema, true );
            return is_array( $decoded ) ? $decoded : [];
        }
        
        return is_array( $schema ) ? $schema : [];
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'form_key' => $this->form_key,
            'name' => $this->name,
            'schema_json' => wp_json_encode( $this->schema ),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}