<?php

namespace Anas\WCCRM\Forms;

use Anas\WCCRM\Contacts\ContactRepository;
use Anas\WCCRM\Contacts\InterestUpdater;

defined( 'ABSPATH' ) || exit;

/**
 * Links form submissions to contacts and manages interests
 */
class FormSubmissionLinker {

    private ContactRepository $contactRepository;
    private InterestUpdater $interestUpdater;

    public function __construct( ContactRepository $contactRepository, InterestUpdater $interestUpdater ) {
        $this->contactRepository = $contactRepository;
        $this->interestUpdater = $interestUpdater;
    }

    /**
     * Process form submission: create/update contact and link submission
     */
    public function process_submission( int $submission_id, array $submission_data ): void {
        if ( $submission_id <= 0 ) {
            return;
        }

        // Get the submission record
        $submission = $this->get_submission_record( $submission_id );
        if ( ! $submission ) {
            return;
        }

        // Extract contact information from submission data
        $contact_data = $this->extract_contact_data( $submission_data );
        
        if ( empty( $contact_data['email'] ) && empty( $contact_data['phone'] ) ) {
            // No identifiable contact information
            return;
        }

        // Create or update contact
        $contact_id = $this->contactRepository->upsert_by_email_or_phone( $contact_data );
        
        if ( ! $contact_id ) {
            error_log( 'WCCRM: Failed to create/update contact for submission ' . $submission_id );
            return;
        }

        // Update submission with contact ID
        $this->link_submission_to_contact( $submission_id, $contact_id );

        // Add interests based on submission
        $this->interestUpdater->add_interests_from_submission( 
            $contact_id, 
            $submission_data, 
            $submission['form_key'] 
        );

        do_action( 'wccrm_submission_linked_to_contact', $submission_id, $contact_id, $submission_data );
    }

    /**
     * Extract contact data from submission data
     */
    protected function extract_contact_data( array $submission_data ): array {
        $contact_data = [];

        // Map common field names to contact fields
        $field_mappings = [
            'email' => [ 'email', 'email_address', 'user_email' ],
            'phone' => [ 'phone', 'phone_number', 'telephone', 'tel', 'mobile' ],
            'first_name' => [ 'first_name', 'fname', 'given_name' ],
            'last_name' => [ 'last_name', 'lname', 'family_name', 'surname' ],
        ];

        foreach ( $field_mappings as $contact_field => $possible_keys ) {
            foreach ( $possible_keys as $key ) {
                if ( ! empty( $submission_data[ $key ] ) ) {
                    $contact_data[ $contact_field ] = $submission_data[ $key ];
                    break;
                }
            }
        }

        // Try to extract name from a single 'name' field
        if ( empty( $contact_data['first_name'] ) && empty( $contact_data['last_name'] ) ) {
            $name_fields = [ 'name', 'full_name', 'your_name' ];
            foreach ( $name_fields as $name_field ) {
                if ( ! empty( $submission_data[ $name_field ] ) ) {
                    $name_parts = $this->parse_full_name( $submission_data[ $name_field ] );
                    $contact_data = array_merge( $contact_data, $name_parts );
                    break;
                }
            }
        }

        return $contact_data;
    }

    /**
     * Parse full name into first and last name
     */
    protected function parse_full_name( string $full_name ): array {
        $full_name = trim( $full_name );
        if ( empty( $full_name ) ) {
            return [];
        }

        $parts = explode( ' ', $full_name, 2 );
        
        return [
            'first_name' => $parts[0],
            'last_name' => isset( $parts[1] ) ? $parts[1] : '',
        ];
    }

    /**
     * Get submission record from database
     */
    protected function get_submission_record( int $submission_id ): ?array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wccrm_form_submissions';
        
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $submission_id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Link submission to contact in database
     */
    protected function link_submission_to_contact( int $submission_id, int $contact_id ): bool {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wccrm_form_submissions';
        
        $result = $wpdb->update(
            $table_name,
            [ 'contact_id' => $contact_id ],
            [ 'id' => $submission_id ],
            [ '%d' ],
            [ '%d' ]
        );

        return $result !== false;
    }

    /**
     * Get submissions for a contact
     */
    public function get_contact_submissions( int $contact_id, int $limit = 50 ): array {
        global $wpdb;

        if ( $contact_id <= 0 ) {
            return [];
        }

        $table_name = $wpdb->prefix . 'wccrm_form_submissions';
        
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE contact_id = %d ORDER BY created_at DESC LIMIT %d",
                $contact_id,
                $limit
            ),
            ARRAY_A
        );

        // Parse submission JSON
        foreach ( $rows as &$row ) {
            $row['submission_data'] = json_decode( $row['submission_json'], true ) ?: [];
        }

        return $rows ?: [];
    }

    /**
     * Get form submissions by form key
     */
    public function get_form_submissions( string $form_key, int $page = 1, int $per_page = 20 ): array {
        global $wpdb;

        if ( empty( $form_key ) ) {
            return [];
        }

        $table_name = $wpdb->prefix . 'wccrm_form_submissions';
        $contacts_table = $wpdb->prefix . 'wccrm_contacts';
        $offset = max( 0, ( $page - 1 ) * $per_page );

        // Get submissions with contact data
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*, c.email, c.phone, c.first_name, c.last_name 
                 FROM {$table_name} s 
                 LEFT JOIN {$contacts_table} c ON s.contact_id = c.id 
                 WHERE s.form_key = %s 
                 ORDER BY s.created_at DESC 
                 LIMIT %d OFFSET %d",
                $form_key,
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        $total = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE form_key = %s",
                $form_key
            )
        );

        // Parse submission JSON
        foreach ( $rows as &$row ) {
            $row['submission_data'] = json_decode( $row['submission_json'], true ) ?: [];
        }

        return [
            'items' => $rows ?: [],
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
        ];
    }
}