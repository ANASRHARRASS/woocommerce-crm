<?php

namespace Anas\WCCRM\Contacts;

defined( 'ABSPATH' ) || exit;

/**
 * Contact repository for managing contact records
 */
class ContactRepository {

    // Stage constants
    public const STAGE_PENDING = 0;
    public const STAGE_QUALIFIED = 1;
    public const STAGE_CUSTOMER = 2;
    public const STAGE_LOST = 3;

    private string $table_name;
    private string $journal_table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wccrm_contacts';
        $this->journal_table_name = $wpdb->prefix . 'wccrm_lead_journal';
    }

    /**
     * Get stage labels
     */
    public static function get_stage_labels(): array {
        return [
            self::STAGE_PENDING => 'Pending',
            self::STAGE_QUALIFIED => 'Qualified',
            self::STAGE_CUSTOMER => 'Customer',
            self::STAGE_LOST => 'Lost',
        ];
    }

    /**
     * Upsert contact by email or phone, returning contact ID
     */
    public function upsert_by_email_or_phone( array $data ): ?int {
        $email = sanitize_email( $data['email'] ?? '' );
        $phone = sanitize_text_field( $data['phone'] ?? '' );
        
        if ( empty( $email ) && empty( $phone ) ) {
            return null;
        }
        
        // Try to find existing contact
        $existing_id = $this->find_by_email_or_phone( $email, $phone );
        
        if ( $existing_id ) {
            // Update existing contact
            if ( $this->update( $existing_id, $data ) ) {
                return $existing_id;
            }
            return null;
        } else {
            // Create new contact
            return $this->create( $data );
        }
    }

    public function create( array $data ): ?int {
        global $wpdb;

        $insert_data = [
            'email' => sanitize_email( $data['email'] ?? '' ) ?: null,
            'phone' => sanitize_text_field( $data['phone'] ?? '' ) ?: null,
            'first_name' => sanitize_text_field( $data['first_name'] ?? '' ) ?: null,
            'last_name' => sanitize_text_field( $data['last_name'] ?? '' ) ?: null,
            'status' => sanitize_text_field( $data['status'] ?? 'active' ),
            'stage' => isset( $data['stage'] ) ? (int) $data['stage'] : self::STAGE_PENDING,
            'source' => ! empty( $data['source'] ) ? sanitize_text_field( $data['source'] ) : null,
            'consent_flags' => ! empty( $data['consent_flags'] ) ? wp_json_encode( $data['consent_flags'] ) : null,
            'last_order_id' => ! empty( $data['last_order_id'] ) ? (int) $data['last_order_id'] : null,
            'meta_json' => ! empty( $data['meta_json'] ) ? wp_json_encode( $data['meta_json'] ) : null,
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' ),
        ];

        $result = $wpdb->insert(
            $this->table_name,
            $insert_data,
            [ '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s' ]
        );

        return $result !== false ? (int) $wpdb->insert_id : null;
    }

    public function update( int $id, array $data ): bool {
        global $wpdb;

        $update_data = [
            'updated_at' => current_time( 'mysql' ),
        ];

        if ( isset( $data['email'] ) ) {
            $update_data['email'] = sanitize_email( $data['email'] ) ?: null;
        }

        if ( isset( $data['phone'] ) ) {
            $update_data['phone'] = sanitize_text_field( $data['phone'] ) ?: null;
        }

        if ( isset( $data['first_name'] ) ) {
            $update_data['first_name'] = sanitize_text_field( $data['first_name'] ) ?: null;
        }

        if ( isset( $data['last_name'] ) ) {
            $update_data['last_name'] = sanitize_text_field( $data['last_name'] ) ?: null;
        }

        if ( isset( $data['status'] ) ) {
            $update_data['status'] = sanitize_text_field( $data['status'] );
        }

        if ( isset( $data['stage'] ) ) {
            $update_data['stage'] = (int) $data['stage'];
        }

        if ( isset( $data['source'] ) ) {
            $update_data['source'] = ! empty( $data['source'] ) ? sanitize_text_field( $data['source'] ) : null;
        }

        if ( isset( $data['consent_flags'] ) ) {
            $update_data['consent_flags'] = ! empty( $data['consent_flags'] ) ? wp_json_encode( $data['consent_flags'] ) : null;
        }

        if ( isset( $data['last_order_id'] ) ) {
            $update_data['last_order_id'] = ! empty( $data['last_order_id'] ) ? (int) $data['last_order_id'] : null;
        }

        if ( isset( $data['meta_json'] ) ) {
            $update_data['meta_json'] = ! empty( $data['meta_json'] ) ? wp_json_encode( $data['meta_json'] ) : null;
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            [ 'id' => $id ],
            array_fill( 0, count( $update_data ), '%s' ),
            [ '%d' ]
        );

        return $result !== false;
    }

    public function find_by_id( int $id ): ?array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function find_by_email( string $email ): ?array {
        global $wpdb;

        if ( empty( $email ) ) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE email = %s",
                sanitize_email( $email )
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function find_by_phone( string $phone ): ?array {
        global $wpdb;

        if ( empty( $phone ) ) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE phone = %s",
                sanitize_text_field( $phone )
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    protected function find_by_email_or_phone( string $email, string $phone ): ?int {
        global $wpdb;

        $conditions = [];
        $values = [];

        if ( ! empty( $email ) ) {
            $conditions[] = 'email = %s';
            $values[] = sanitize_email( $email );
        }

        if ( ! empty( $phone ) ) {
            $conditions[] = 'phone = %s';
            $values[] = sanitize_text_field( $phone );
        }

        if ( empty( $conditions ) ) {
            return null;
        }

        $where_clause = implode( ' OR ', $conditions );
        
        $id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE {$where_clause} LIMIT 1",
                ...$values
            )
        );

        return $id ? (int) $id : null;
    }

    public function list_contacts( int $page = 1, int $per_page = 20, array $filters = [] ): array {
        global $wpdb;

        $offset = max( 0, ( $page - 1 ) * $per_page );
        $where_conditions = [ '1=1' ];
        $where_values = [];

        // Apply filters
        if ( ! empty( $filters['status'] ) ) {
            $where_conditions[] = 'status = %s';
            $where_values[] = sanitize_text_field( $filters['status'] );
        }

        if ( isset( $filters['stage'] ) && $filters['stage'] !== '' ) {
            $where_conditions[] = 'stage = %d';
            $where_values[] = (int) $filters['stage'];
        }

        if ( ! empty( $filters['source'] ) ) {
            $where_conditions[] = 'source = %s';
            $where_values[] = sanitize_text_field( $filters['source'] );
        }

        if ( ! empty( $filters['search'] ) ) {
            $search = '%' . $wpdb->esc_like( sanitize_text_field( $filters['search'] ) ) . '%';
            $where_conditions[] = '(email LIKE %s OR phone LIKE %s OR first_name LIKE %s OR last_name LIKE %s)';
            $where_values = array_merge( $where_values, [ $search, $search, $search, $search ] );
        }

        $where_clause = implode( ' AND ', $where_conditions );

        // Get items
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $prepare_values = array_merge( $where_values, [ $per_page, $offset ] );

        $rows = $wpdb->get_results(
            $wpdb->prepare( $query, ...$prepare_values ),
            ARRAY_A
        );

        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        $total = (int) $wpdb->get_var(
            empty( $where_values ) ? $count_query : $wpdb->prepare( $count_query, ...$where_values )
        );

        return [
            'items' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
        ];
    }

    /**
     * Add journal entry for a contact
     */
    public function add_journal( int $contact_id, string $event_type, ?string $message = null, ?array $meta = null, ?int $ref_id = null ): ?int {
        global $wpdb;

        if ( $contact_id <= 0 || empty( $event_type ) ) {
            return null;
        }

        $insert_data = [
            'contact_id' => $contact_id,
            'event_type' => sanitize_text_field( $event_type ),
            'message' => ! empty( $message ) ? sanitize_text_field( $message ) : null,
            'ref_id' => ! empty( $ref_id ) ? (int) $ref_id : null,
            'meta_json' => ! empty( $meta ) ? wp_json_encode( $meta ) : null,
            'created_at' => current_time( 'mysql' ),
        ];

        $result = $wpdb->insert(
            $this->journal_table_name,
            $insert_data,
            [ '%d', '%s', '%s', '%d', '%s', '%s' ]
        );

        return $result !== false ? (int) $wpdb->insert_id : null;
    }

    /**
     * Set contact stage and log to journal
     */
    public function set_stage( int $id, int $stage, ?string $reason = null, ?array $meta = null, ?int $ref_id = null ): bool {
        global $wpdb;

        if ( $id <= 0 ) {
            return false;
        }

        // Get current contact to check for changes
        $contact = $this->find_by_id( $id );
        if ( ! $contact ) {
            return false;
        }

        $old_stage = (int) ( $contact['stage'] ?? self::STAGE_PENDING );
        
        // Update the stage
        $result = $this->update( $id, [ 'stage' => $stage ] );
        
        if ( $result && $old_stage !== $stage ) {
            // Add journal entry for stage change
            $stage_labels = self::get_stage_labels();
            $old_label = $stage_labels[ $old_stage ] ?? 'Unknown';
            $new_label = $stage_labels[ $stage ] ?? 'Unknown';
            
            $message = $reason ? sprintf( 'Stage changed from %s to %s: %s', $old_label, $new_label, $reason ) 
                              : sprintf( 'Stage changed from %s to %s', $old_label, $new_label );
            
            $journal_meta = array_merge( $meta ?? [], [
                'old_stage' => $old_stage,
                'new_stage' => $stage,
                'old_stage_label' => $old_label,
                'new_stage_label' => $new_label,
            ] );

            $this->add_journal( $id, 'stage_changed', $message, $journal_meta, $ref_id );

            // Fire action
            do_action( 'wccrm_contact_stage_changed', $id, $stage, $old_stage, $reason, $meta, $ref_id );
        }

        return $result;
    }

    /**
     * Get journal entries for a contact
     */
    public function get_journal( int $contact_id, int $limit = 50, int $offset = 0 ): array {
        global $wpdb;

        if ( $contact_id <= 0 ) {
            return [];
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->journal_table_name} WHERE contact_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $contact_id,
                $limit,
                $offset
            ),
            ARRAY_A
        );

        // Parse meta JSON
        foreach ( $rows as &$row ) {
            $row['meta'] = ! empty( $row['meta_json'] ) ? json_decode( $row['meta_json'], true ) : [];
        }

        return $rows ?: [];
    }
}