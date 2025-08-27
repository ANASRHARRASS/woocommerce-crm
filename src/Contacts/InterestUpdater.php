<?php

namespace Anas\WCCRM\Contacts;

defined( 'ABSPATH' ) || exit;

/**
 * Contact interest updater with weight-based scoring
 * TODO: Add interest decay logic for aging interests
 */
class InterestUpdater {

    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wccrm_contact_interests';
    }

    /**
     * Add interest to contact with optional weight delta
     */
    public function add_interest( int $contact_id, string $interest_key, int $weight_delta = 1 ): bool {
        global $wpdb;

        $interest_key = sanitize_key( $interest_key );
        if ( empty( $interest_key ) || $contact_id <= 0 ) {
            return false;
        }

        // Check if interest already exists
        $existing_weight = $this->get_interest_weight( $contact_id, $interest_key );

        if ( $existing_weight !== null ) {
            // Update existing interest
            $new_weight = max( 1, $existing_weight + $weight_delta );
            
            $result = $wpdb->update(
                $this->table_name,
                [
                    'weight' => $new_weight,
                    'updated_at' => current_time( 'mysql' ),
                ],
                [
                    'contact_id' => $contact_id,
                    'interest_key' => $interest_key,
                ],
                [ '%d', '%s' ],
                [ '%d', '%s' ]
            );

            return $result !== false;
        } else {
            // Insert new interest
            $result = $wpdb->insert(
                $this->table_name,
                [
                    'contact_id' => $contact_id,
                    'interest_key' => $interest_key,
                    'weight' => max( 1, $weight_delta ),
                    'updated_at' => current_time( 'mysql' ),
                ],
                [ '%d', '%s', '%d', '%s' ]
            );

            return $result !== false;
        }
    }

    /**
     * Get all interests for a contact
     */
    public function get_interests( int $contact_id ): array {
        global $wpdb;

        if ( $contact_id <= 0 ) {
            return [];
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT interest_key, weight, updated_at FROM {$this->table_name} WHERE contact_id = %d ORDER BY weight DESC, updated_at DESC",
                $contact_id
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }

    /**
     * Get specific interest weight for a contact
     */
    public function get_interest_weight( int $contact_id, string $interest_key ): ?int {
        global $wpdb;

        $interest_key = sanitize_key( $interest_key );
        if ( empty( $interest_key ) || $contact_id <= 0 ) {
            return null;
        }

        $weight = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT weight FROM {$this->table_name} WHERE contact_id = %d AND interest_key = %s",
                $contact_id,
                $interest_key
            )
        );

        return $weight !== null ? (int) $weight : null;
    }

    /**
     * Remove interest from contact
     */
    public function remove_interest( int $contact_id, string $interest_key ): bool {
        global $wpdb;

        $interest_key = sanitize_key( $interest_key );
        if ( empty( $interest_key ) || $contact_id <= 0 ) {
            return false;
        }

        $result = $wpdb->delete(
            $this->table_name,
            [
                'contact_id' => $contact_id,
                'interest_key' => $interest_key,
            ],
            [ '%d', '%s' ]
        );

        return $result !== false;
    }

    /**
     * Get top interests across all contacts
     */
    public function get_top_interests( int $limit = 10 ): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT interest_key, SUM(weight) as total_weight, COUNT(*) as contact_count 
                 FROM {$this->table_name} 
                 GROUP BY interest_key 
                 ORDER BY total_weight DESC, contact_count DESC 
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }

    /**
     * Get contacts by interest
     */
    public function get_contacts_by_interest( string $interest_key, int $min_weight = 1, int $limit = 100 ): array {
        global $wpdb;

        $interest_key = sanitize_key( $interest_key );
        if ( empty( $interest_key ) ) {
            return [];
        }

        $contacts_table = $wpdb->prefix . 'wccrm_contacts';
        
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.*, ci.weight, ci.updated_at as interest_updated_at 
                 FROM {$contacts_table} c 
                 INNER JOIN {$this->table_name} ci ON c.id = ci.contact_id 
                 WHERE ci.interest_key = %s AND ci.weight >= %d 
                 ORDER BY ci.weight DESC, ci.updated_at DESC 
                 LIMIT %d",
                $interest_key,
                $min_weight,
                $limit
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }

    /**
     * Bulk add interests from form submission
     * TODO: Make interest keys configurable per form
     */
    public function add_interests_from_submission( int $contact_id, array $submission_data, string $form_key = '' ): void {
        // Add general form submission interest
        $this->add_interest( $contact_id, 'form_submission', 1 );
        
        // Add form-specific interest if form key provided
        if ( ! empty( $form_key ) ) {
            $this->add_interest( $contact_id, 'form_' . sanitize_key( $form_key ), 2 );
        }
        
        // Add interest based on submission content
        $this->add_interests_from_content( $contact_id, $submission_data );
    }

    /**
     * Add interests based on submission content analysis
     * TODO: Implement more sophisticated content analysis
     */
    protected function add_interests_from_content( int $contact_id, array $submission_data ): void {
        // Simple keyword-based interest detection
        $interest_keywords = [
            'newsletter' => [ 'newsletter', 'updates', 'news' ],
            'product_info' => [ 'product', 'service', 'pricing', 'demo' ],
            'support' => [ 'help', 'support', 'issue', 'problem' ],
            'sales' => [ 'buy', 'purchase', 'quote', 'price' ],
        ];

        $content = strtolower( implode( ' ', array_values( $submission_data ) ) );

        foreach ( $interest_keywords as $interest => $keywords ) {
            foreach ( $keywords as $keyword ) {
                if ( strpos( $content, $keyword ) !== false ) {
                    $this->add_interest( $contact_id, $interest, 1 );
                    break; // Only add each interest once per submission
                }
            }
        }
    }

    /**
     * Clean up old interests (placeholder for decay logic)
     * TODO: Implement interest decay based on age and activity
     */
    public function cleanup_old_interests( int $days_old = 365 ): int {
        global $wpdb;

        // For now, just remove very old low-weight interests
        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days_old} days" ) );

        $result = $wpdb->delete(
            $this->table_name,
            [
                'weight' => 1,
                'updated_at <' => $cutoff_date,
            ],
            [ '%d', '%s' ]
        );

        return $result !== false ? $result : 0;
    }
}