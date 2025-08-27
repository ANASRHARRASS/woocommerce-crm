<?php

namespace Anas\WCCRM\Admin\Placeholders;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard placeholder page
 */
class DashboardPage {

    public function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied', 'wccrm' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'CRM Suite Dashboard', 'wccrm' ) . '</h1>';
        
        echo '<div class="welcome-panel">';
        echo '<div class="welcome-panel-content">';
        echo '<h2>' . esc_html__( 'Welcome to WooCommerce CRM Suite', 'wccrm' ) . '</h2>';
        echo '<p class="about-description">' . esc_html__( 'Your unified customer relationship management platform.', 'wccrm' ) . '</p>';
        
        echo '<div class="welcome-panel-column-container">';
        
        // Quick stats
        echo '<div class="welcome-panel-column">';
        echo '<h3>' . esc_html__( 'Quick Stats', 'wccrm' ) . '</h3>';
        echo '<ul>';
        echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=wccrm-contacts' ) ) . '">' . esc_html__( 'View Contacts', 'wccrm' ) . '</a></li>';
        echo '<li>' . esc_html__( 'Total Contacts: Loading...', 'wccrm' ) . '</li>';
        echo '<li>' . esc_html__( 'Active Leads: Loading...', 'wccrm' ) . '</li>';
        echo '</ul>';
        echo '</div>';
        
        // Quick actions
        echo '<div class="welcome-panel-column">';
        echo '<h3>' . esc_html__( 'Quick Actions', 'wccrm' ) . '</h3>';
        echo '<ul>';
        echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=wccrm-contacts' ) ) . '" class="button">' . esc_html__( 'Manage Contacts', 'wccrm' ) . '</a></li>';
        echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=wccrm-news' ) ) . '" class="button">' . esc_html__( 'View News Feeds', 'wccrm' ) . '</a></li>';
        echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=wccrm-settings' ) ) . '" class="button">' . esc_html__( 'Settings', 'wccrm' ) . '</a></li>';
        echo '</ul>';
        echo '</div>';
        
        // System status
        echo '<div class="welcome-panel-column welcome-panel-last">';
        echo '<h3>' . esc_html__( 'System Status', 'wccrm' ) . '</h3>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Plugin Version:', 'wccrm' ) . ' ' . ( defined( 'WCCRM_VERSION' ) ? WCCRM_VERSION : '2.0.0' ) . '</li>';
        echo '<li>' . esc_html__( 'Database:', 'wccrm' ) . ' ' . '<span style="color: green;">' . esc_html__( 'Connected', 'wccrm' ) . '</span>' . '</li>';
        
        if ( class_exists( 'WooCommerce' ) ) {
            echo '<li>' . esc_html__( 'WooCommerce:', 'wccrm' ) . ' ' . '<span style="color: green;">' . esc_html__( 'Active', 'wccrm' ) . '</span>' . '</li>';
        } else {
            echo '<li>' . esc_html__( 'WooCommerce:', 'wccrm' ) . ' ' . '<span style="color: orange;">' . esc_html__( 'Not active', 'wccrm' ) . '</span>' . '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
        
        echo '</div>'; // column-container
        echo '</div>'; // welcome-panel-content
        echo '</div>'; // welcome-panel
        
        // Recent activity placeholder
        echo '<h2>' . esc_html__( 'Recent Activity', 'wccrm' ) . '</h2>';
        echo '<p><em>' . esc_html__( 'Activity tracking will be implemented in future updates.', 'wccrm' ) . '</em></p>';
        
        echo '</div>'; // wrap
    }
}