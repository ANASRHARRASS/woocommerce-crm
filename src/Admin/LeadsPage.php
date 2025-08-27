<?php
namespace WCP\Admin;

use WCP\Leads\LeadManager;

defined( 'ABSPATH' ) || exit;

class LeadsPage {

    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'submenu' ] );
    }

    public static function submenu(): void {
        add_submenu_page(
            'wcp-settings',
            __( 'Leads', 'wcp' ),
            __( 'Leads', 'wcp' ),
            'manage_options',
            'wcp-leads',
            [ __CLASS__, 'render' ]
        );
    }

    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Access denied', 'wcp' ) );
        $page = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
        $manager = self::manager();
        $data = $manager->list( $page, 20 );

        echo '<div class="wrap"><h1>' . esc_html__( 'Leads', 'wcp' ) . '</h1>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>ID</th><th>Source</th><th>Name</th><th>Email</th><th>Phone</th><th>Date</th></tr></thead><tbody>';
        if ( empty( $data['items'] ) ) {
            echo '<tr><td colspan="6">' . esc_html__( 'No leads found.', 'wcp' ) . '</td></tr>';
        } else {
            foreach ( $data['items'] as $row ) {
                echo '<tr>';
                printf(
                    '<td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>',
                    $row['id'],
                    esc_html( $row['source'] ),
                    esc_html( $row['name'] ?? '' ),
                    esc_html( $row['email'] ?? '' ),
                    esc_html( $row['phone'] ?? '' ),
                    esc_html( $row['created_at'] )
                );
                echo '</tr>';
            }
        }
        echo '</tbody></table>';

        $total_pages = max( 1, (int) ceil( $data['total'] / 20 ) );
        if ( $total_pages > 1 ) {
            $base = admin_url( 'admin.php?page=wcp-leads&paged=%#%' );
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo paginate_links( [
                'base'      => $base,
                'format'    => '',
                'current'   => $page,
                'total'     => $total_pages,
                'prev_text' => '«',
                'next_text' => '»',
            ] );
            echo '</div></div>';
        }

        echo '</div>';
    }

    protected static function manager(): LeadManager {
        // Access via global Core instance hook or instantiate minimal manager (stateless listing).
        static $mgr;
        if ( ! $mgr ) {
            $mgr = new LeadManager();
        }
        return $mgr;
    }
}
