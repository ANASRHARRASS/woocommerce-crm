<?php

namespace Anas\WCCRM\Admin;

use Anas\WCCRM\Contacts\ContactRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Contact detail page
 */
class ContactDetailPage {

    protected ContactRepository $contact_repository;

    public function __construct() {
        $this->contact_repository = new ContactRepository();
    }

    public function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied', 'wccrm' ) );
        }

        $contact_id = absint( $_GET['contact_id'] ?? 0 );
        
        if ( ! $contact_id ) {
            wp_die( esc_html__( 'Contact ID is required.', 'wccrm' ) );
        }

        $contact = $this->contact_repository->find_by_id( $contact_id );
        
        if ( ! $contact ) {
            wp_die( esc_html__( 'Contact not found.', 'wccrm' ) );
        }

        echo '<div class="wrap">';
        
        $name = trim( ( $contact['first_name'] ?? '' ) . ' ' . ( $contact['last_name'] ?? '' ) );
        if ( empty( $name ) ) {
            $name = esc_html__( 'Contact Details', 'wccrm' );
        }
        
        printf( '<h1>%s</h1>', esc_html( $name ) );

        // Back link
        $back_url = admin_url( 'admin.php?page=' . Menu::MAIN_SLUG . '-contacts' );
        printf(
            '<p><a href="%s">← %s</a></p>',
            esc_url( $back_url ),
            esc_html__( 'Back to Contacts', 'wccrm' )
        );

        // Contact information
        $this->render_contact_info( $contact );

        // Stage management
        $this->render_stage_management( $contact );

        // Journal section
        $this->render_journal_section( $contact_id );

        echo '</div>';
    }

    protected function render_contact_info( array $contact ): void {
        echo '<h2>' . esc_html__( 'Contact Information', 'wccrm' ) . '</h2>';
        echo '<table class="form-table">';
        
        // Basic fields
        $this->render_info_row( __( 'First Name', 'wccrm' ), $contact['first_name'] ?? '' );
        $this->render_info_row( __( 'Last Name', 'wccrm' ), $contact['last_name'] ?? '' );
        $this->render_info_row( __( 'Email', 'wccrm' ), $contact['email'] ?? '' );
        $this->render_info_row( __( 'Phone', 'wccrm' ), $contact['phone'] ?? '' );
        $this->render_info_row( __( 'Status', 'wccrm' ), $contact['status'] ?? 'active' );
        
        // Enhanced fields (available once Step 1 is merged)
        if ( isset( $contact['stage'] ) ) {
            $stage_label = $this->get_stage_label( intval( $contact['stage'] ) );
            $this->render_info_row( __( 'Stage', 'wccrm' ), $stage_label );
        } else {
            $this->render_info_row( __( 'Stage', 'wccrm' ), '<em>' . esc_html__( '(Pending Step 1)', 'wccrm' ) . '</em>', false );
        }
        
        if ( isset( $contact['source'] ) ) {
            $this->render_info_row( __( 'Source', 'wccrm' ), $contact['source'] );
        } else {
            $this->render_info_row( __( 'Source', 'wccrm' ), '<em>' . esc_html__( '(Pending Step 1)', 'wccrm' ) . '</em>', false );
        }
        
        if ( isset( $contact['consent_flags'] ) ) {
            $consent = $this->decode_consent_flags( $contact['consent_flags'] );
            $this->render_info_row( __( 'Consent Flags', 'wccrm' ), $consent, false );
        } else {
            $this->render_info_row( __( 'Consent Flags', 'wccrm' ), '<em>' . esc_html__( '(Pending Step 1)', 'wccrm' ) . '</em>', false );
        }
        
        if ( isset( $contact['last_order_id'] ) ) {
            $order_link = $contact['last_order_id'] ? sprintf(
                '<a href="%s">#%d</a>',
                admin_url( 'post.php?post=' . absint( $contact['last_order_id'] ) . '&action=edit' ),
                absint( $contact['last_order_id'] )
            ) : esc_html__( 'None', 'wccrm' );
            $this->render_info_row( __( 'Last Order', 'wccrm' ), $order_link, false );
        } else {
            $this->render_info_row( __( 'Last Order', 'wccrm' ), '<em>' . esc_html__( '(Pending Step 1)', 'wccrm' ) . '</em>', false );
        }
        
        if ( isset( $contact['meta_json'] ) ) {
            $meta = $this->format_meta_json( $contact['meta_json'] );
            $this->render_info_row( __( 'Meta Data', 'wccrm' ), '<pre style="font-size: 11px; background: #f1f1f1; padding: 10px; overflow: auto; max-height: 200px;">' . esc_html( $meta ) . '</pre>', false );
        } else {
            $this->render_info_row( __( 'Meta Data', 'wccrm' ), '<em>' . esc_html__( '(Pending Step 1)', 'wccrm' ) . '</em>', false );
        }
        
        // Timestamps
        $created = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $contact['created_at'] );
        $this->render_info_row( __( 'Created', 'wccrm' ), $created );
        
        $updated = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $contact['updated_at'] );
        $this->render_info_row( __( 'Updated', 'wccrm' ), $updated );
        
        echo '</table>';
    }

    protected function render_info_row( string $label, string $value, bool $escape = true ): void {
        printf(
            '<tr><th scope="row">%s</th><td>%s</td></tr>',
            esc_html( $label ),
            $escape ? esc_html( $value ) : $value
        );
    }

    protected function render_stage_management( array $contact ): void {
        echo '<h2 id="stage">' . esc_html__( 'Stage Management', 'wccrm' ) . '</h2>';
        
        if ( ! isset( $contact['stage'] ) ) {
            echo '<p><em>' . esc_html__( 'Stage management will be available once Step 1 is merged.', 'wccrm' ) . '</em></p>';
            return;
        }

        $current_stage = intval( $contact['stage'] );
        $current_stage_label = $this->get_stage_label( $current_stage );
        
        printf(
            '<p>' . esc_html__( 'Current stage: %s', 'wccrm' ) . '</p>',
            '<strong>' . esc_html( $current_stage_label ) . '</strong>'
        );
        
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'wccrm_set_stage_action', 'wccrm_set_stage_nonce' );
        echo '<input type="hidden" name="action" value="wccrm_set_stage">';
        echo '<input type="hidden" name="contact_id" value="' . esc_attr( $contact['id'] ) . '">';
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th scope="row"><label for="stage">' . esc_html__( 'New Stage', 'wccrm' ) . '</label></th>';
        echo '<td>';
        echo '<select name="stage" id="stage" required>';
        
        foreach ( $this->get_stage_options() as $value => $label ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $value ),
                selected( $current_stage, $value, false ),
                esc_html( $label )
            );
        }
        
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="reason">' . esc_html__( 'Reason (Optional)', 'wccrm' ) . '</label></th>';
        echo '<td>';
        echo '<input type="text" name="reason" id="reason" class="regular-text" placeholder="' . esc_attr__( 'Why is the stage changing?', 'wccrm' ) . '">';
        echo '</td>';
        echo '</tr>';
        
        echo '</table>';
        
        submit_button( __( 'Update Stage', 'wccrm' ) );
        echo '</form>';
    }

    protected function render_journal_section( int $contact_id ): void {
        echo '<h2>' . esc_html__( 'Activity Journal', 'wccrm' ) . '</h2>';
        
        // Note: This will work once Step 1 is merged with wccrm_lead_journal table
        // For now, show placeholder
        
        echo '<p><em>' . esc_html__( 'Journal entries will be available once Step 1 is merged with the wccrm_lead_journal table.', 'wccrm' ) . '</em></p>';
        
        // Placeholder table structure
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th scope="col">' . esc_html__( 'Date/Time', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Event', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Message', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Ref ID', 'wccrm' ) . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        echo '<tr><td colspan="4"><em>' . esc_html__( 'No journal entries yet (pending Step 1).', 'wccrm' ) . '</em></td></tr>';
        echo '</tbody>';
        echo '</table>';
        
        // TODO: Once Step 1 is merged, replace above with:
        /*
        $page = max( 1, absint( $_GET['journal_page'] ?? 1 ) );
        $journal_entries = $this->contact_repository->get_journal( $contact_id, $page, 50 );
        
        if ( empty( $journal_entries ) ) {
            echo '<p>' . esc_html__( 'No journal entries yet.', 'wccrm' ) . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th scope="col">' . esc_html__( 'Date/Time', 'wccrm' ) . '</th>';
            echo '<th scope="col">' . esc_html__( 'Event', 'wccrm' ) . '</th>';
            echo '<th scope="col">' . esc_html__( 'Message', 'wccrm' ) . '</th>';
            echo '<th scope="col">' . esc_html__( 'Ref ID', 'wccrm' ) . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ( $journal_entries as $entry ) {
                echo '<tr>';
                $datetime = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $entry['created_at'] );
                echo '<td>' . esc_html( $datetime ) . '</td>';
                echo '<td>' . esc_html( $entry['event'] ) . '</td>';
                echo '<td>' . esc_html( $entry['message'] ) . '</td>';
                echo '<td>' . esc_html( $entry['ref_id'] ?? '' ) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        }
        */
    }

    protected function get_stage_options(): array {
        // Note: These will be replaced with actual stage constants once Step 1 is merged
        return [
            1 => __( 'Lead', 'wccrm' ),
            2 => __( 'Prospect', 'wccrm' ),
            3 => __( 'Customer', 'wccrm' ),
        ];
    }

    protected function get_stage_label( int $stage ): string {
        $stages = $this->get_stage_options();
        return $stages[ $stage ] ?? __( 'Unknown', 'wccrm' );
    }

    protected function decode_consent_flags( string $json ): string {
        $flags = json_decode( $json, true );
        if ( ! is_array( $flags ) ) {
            return esc_html__( 'Invalid JSON', 'wccrm' );
        }
        
        $decoded = [];
        foreach ( $flags as $key => $value ) {
            $decoded[] = sprintf( '%s: %s', $key, $value ? 'Yes' : 'No' );
        }
        
        return implode( ', ', $decoded );
    }

    protected function format_meta_json( string $json ): string {
        $data = json_decode( $json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return $json; // Return raw if not valid JSON
        }
        
        return wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }
}