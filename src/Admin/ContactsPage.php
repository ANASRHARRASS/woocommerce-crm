<?php

namespace Anas\WCCRM\Admin;

use Anas\WCCRM\Contacts\ContactRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Contacts list page
 */
class ContactsPage {

    protected ContactRepository $contact_repository;

    public function __construct() {
        $this->contact_repository = new ContactRepository();
    }

    public function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied', 'wccrm' ) );
        }

        // Get filters and pagination
        $page = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $per_page = max( 1, min( 100, absint( $_GET['per_page'] ?? 20 ) ) );
        $search = sanitize_text_field( $_GET['q'] ?? '' );
        $stage = sanitize_text_field( $_GET['stage'] ?? '' );
        $source = sanitize_text_field( $_GET['source'] ?? '' );

        $filters = [];
        if ( $search ) {
            $filters['search'] = $search;
        }
        // Note: stage and source filters will work once Step 1 is merged
        
        $data = $this->contact_repository->list_contacts( $page, $per_page, $filters );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Contacts', 'wccrm' ) . '</h1>';

        // Filters form
        $this->render_filters( $search, $stage, $source );

        // Results table
        $this->render_table( $data );

        // Pagination
        $this->render_pagination( $data, $page );

        echo '</div>';
    }

    protected function render_filters( string $search, string $stage, string $source ): void {
        $current_url = admin_url( 'admin.php?page=' . Menu::MAIN_SLUG . '-contacts' );
        
        echo '<form method="get" action="' . esc_url( $current_url ) . '" style="margin-bottom: 20px;">';
        echo '<input type="hidden" name="page" value="' . esc_attr( Menu::MAIN_SLUG . '-contacts' ) . '">';
        
        echo '<p class="search-box">';
        echo '<label class="screen-reader-text" for="contact-search-input">' . esc_html__( 'Search Contacts:', 'wccrm' ) . '</label>';
        echo '<input type="search" id="contact-search-input" name="q" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Search contacts...', 'wccrm' ) . '">';
        
        echo '<label for="stage-filter" style="margin-left: 10px;">' . esc_html__( 'Stage:', 'wccrm' ) . '</label>';
        echo '<select name="stage" id="stage-filter">';
        echo '<option value="">' . esc_html__( 'All Stages', 'wccrm' ) . '</option>';
        // Note: Stage options will be populated once Step 1 is merged
        $stages = $this->get_stage_options();
        foreach ( $stages as $value => $label ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $value ),
                selected( $stage, $value, false ),
                esc_html( $label )
            );
        }
        echo '</select>';
        
        echo '<label for="source-filter" style="margin-left: 10px;">' . esc_html__( 'Source:', 'wccrm' ) . '</label>';
        echo '<input type="text" id="source-filter" name="source" value="' . esc_attr( $source ) . '" placeholder="' . esc_attr__( 'Filter by source...', 'wccrm' ) . '">';
        
        echo '<label for="per-page" style="margin-left: 10px;">' . esc_html__( 'Per page:', 'wccrm' ) . '</label>';
        echo '<select name="per_page" id="per-page">';
        $per_page_current = absint( $_GET['per_page'] ?? 20 );
        foreach ( [ 10, 20, 50, 100 ] as $option ) {
            printf(
                '<option value="%d"%s>%d</option>',
                $option,
                selected( $per_page_current, $option, false ),
                $option
            );
        }
        echo '</select>';
        
        submit_button( __( 'Filter', 'wccrm' ), 'secondary', 'submit', false );
        echo '</p>';
        echo '</form>';
    }

    protected function render_table( array $data ): void {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th scope="col">' . esc_html__( 'Name', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Email', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Phone', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Stage', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Source', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Created', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Actions', 'wccrm' ) . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        if ( empty( $data['items'] ) ) {
            echo '<tr><td colspan="7">' . esc_html__( 'No contacts found.', 'wccrm' ) . '</td></tr>';
        } else {
            foreach ( $data['items'] as $contact ) {
                $this->render_contact_row( $contact );
            }
        }

        echo '</tbody>';
        echo '</table>';
    }

    protected function render_contact_row( array $contact ): void {
        $contact_id = absint( $contact['id'] );
        $name = trim( ( $contact['first_name'] ?? '' ) . ' ' . ( $contact['last_name'] ?? '' ) );
        if ( empty( $name ) ) {
            $name = esc_html__( '(No name)', 'wccrm' );
        }

        $detail_url = admin_url( 'admin.php?page=' . Menu::MAIN_SLUG . '-contact&contact_id=' . $contact_id );

        echo '<tr>';
        
        // Name
        printf(
            '<td><strong><a href="%s">%s</a></strong></td>',
            esc_url( $detail_url ),
            esc_html( $name )
        );
        
        // Email
        echo '<td>' . esc_html( $contact['email'] ?? '' ) . '</td>';
        
        // Phone
        echo '<td>' . esc_html( $contact['phone'] ?? '' ) . '</td>';
        
        // Stage (placeholder until Step 1 is merged)
        $stage = $contact['stage'] ?? null;
        if ( $stage !== null ) {
            $stage_label = $this->get_stage_label( intval( $stage ) );
            $change_link = sprintf(
                ' <a href="%s">%s</a>',
                esc_url( $detail_url . '#stage' ),
                esc_html__( 'Change', 'wccrm' )
            );
            echo '<td>' . esc_html( $stage_label ) . $change_link . '</td>';
        } else {
            echo '<td><em>' . esc_html__( '(Stage data pending Step 1)', 'wccrm' ) . '</em></td>';
        }
        
        // Source (placeholder until Step 1 is merged)
        $source = $contact['source'] ?? null;
        if ( $source !== null ) {
            echo '<td>' . esc_html( $source ) . '</td>';
        } else {
            echo '<td><em>' . esc_html__( '(Source data pending Step 1)', 'wccrm' ) . '</em></td>';
        }
        
        // Created
        $created = mysql2date( get_option( 'date_format' ), $contact['created_at'] );
        echo '<td>' . esc_html( $created ) . '</td>';
        
        // Actions
        printf(
            '<td><a href="%s" class="button">%s</a></td>',
            esc_url( $detail_url ),
            esc_html__( 'View', 'wccrm' )
        );
        
        echo '</tr>';
    }

    protected function render_pagination( array $data, int $current_page ): void {
        $total_pages = max( 1, ceil( $data['total'] / $data['per_page'] ) );
        
        if ( $total_pages <= 1 ) {
            return;
        }

        $base_url = admin_url( 'admin.php' );
        $args = $_GET;
        $args['page'] = Menu::MAIN_SLUG . '-contacts';
        unset( $args['paged'] );

        echo '<div class="tablenav"><div class="tablenav-pages">';
        
        printf(
            '<span class="displaying-num">%s</span>',
            /* translators: %d: Number of items */
            sprintf( _n( '%d item', '%d items', $data['total'], 'wccrm' ), $data['total'] )
        );

        $pagination_args = [
            'base' => add_query_arg( $args, $base_url ) . '%_%',
            'format' => '&paged=%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'prev_text' => '‹ ' . esc_html__( 'Previous', 'wccrm' ),
            'next_text' => esc_html__( 'Next', 'wccrm' ) . ' ›',
        ];

        echo paginate_links( $pagination_args );
        echo '</div></div>';
    }

    protected function get_stage_options(): array {
        // Note: These will be replaced with actual stage constants once Step 1 is merged
        return [
            '1' => __( 'Lead', 'wccrm' ),
            '2' => __( 'Prospect', 'wccrm' ),
            '3' => __( 'Customer', 'wccrm' ),
        ];
    }

    protected function get_stage_label( int $stage ): string {
        $stages = $this->get_stage_options();
        return $stages[ (string) $stage ] ?? __( 'Unknown', 'wccrm' );
    }
}