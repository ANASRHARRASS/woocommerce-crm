<?php

namespace Anas\WCCRM\Admin\Placeholders;

defined( 'ABSPATH' ) || exit;

/**
 * News Feeds placeholder page
 */
class NewsFeedsPage {

    public function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied', 'wccrm' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'News Feeds', 'wccrm' ) . '</h1>';
        
        echo '<p>' . esc_html__( 'Aggregate news from multiple sources to stay informed about industry trends and customer interests.', 'wccrm' ) . '</p>';
        
        // Multi-API architecture note
        echo '<div class="notice notice-info">';
        echo '<p><strong>' . esc_html__( 'Multi-API Design:', 'wccrm' ) . '</strong> ';
        echo esc_html__( 'API credentials are resolved via environment variables or stored encrypted options; API keys are never hard-coded in source code.', 'wccrm' );
        echo '</p>';
        echo '</div>';
        
        // Supported providers
        echo '<h2>' . esc_html__( 'Supported News Providers', 'wccrm' ) . '</h2>';
        echo '<div class="card-container" style="display: flex; gap: 20px; flex-wrap: wrap;">';
        
        $providers = [
            [
                'name' => 'NewsAPI',
                'description' => __( 'Professional news aggregation service with extensive filtering options.', 'wccrm' ),
                'status' => 'pending_configuration'
            ],
            [
                'name' => 'GNews',
                'description' => __( 'Google News API for comprehensive news coverage.', 'wccrm' ),
                'status' => 'pending_configuration'
            ],
            [
                'name' => 'Generic RSS',
                'description' => __( 'Support for custom RSS/Atom feeds from any source.', 'wccrm' ),
                'status' => 'available'
            ],
        ];
        
        foreach ( $providers as $provider ) {
            echo '<div class="card" style="max-width: 300px; padding: 15px; border: 1px solid #ccd0d4; background: #fff;">';
            echo '<h3>' . esc_html( $provider['name'] ) . '</h3>';
            echo '<p>' . esc_html( $provider['description'] ) . '</p>';
            
            if ( $provider['status'] === 'available' ) {
                echo '<p><span class="dashicons dashicons-yes-alt" style="color: green;"></span> ' . esc_html__( 'Available', 'wccrm' ) . '</p>';
            } else {
                echo '<p><span class="dashicons dashicons-clock" style="color: orange;"></span> ' . esc_html__( 'Pending Configuration', 'wccrm' ) . '</p>';
            }
            echo '</div>';
        }
        
        echo '</div>';
        
        // Placeholder news feed
        echo '<h2>' . esc_html__( 'Recent News', 'wccrm' ) . '</h2>';
        echo '<div class="news-feed-placeholder">';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th scope="col">' . esc_html__( 'Title', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Source', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Published', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Actions', 'wccrm' ) . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        // Sample placeholder articles
        $placeholder_articles = [
            [
                'title' => 'E-commerce Trends for 2024',
                'source' => 'Industry News',
                'date' => '2 hours ago',
                'url' => '#'
            ],
            [
                'title' => 'Customer Retention Strategies',
                'source' => 'Business Weekly',
                'date' => '4 hours ago', 
                'url' => '#'
            ],
            [
                'title' => 'WooCommerce Updates and Features',
                'source' => 'Tech Blog',
                'date' => '1 day ago',
                'url' => '#'
            ],
        ];
        
        foreach ( $placeholder_articles as $article ) {
            echo '<tr>';
            echo '<td><strong>' . esc_html( $article['title'] ) . '</strong></td>';
            echo '<td>' . esc_html( $article['source'] ) . '</td>';
            echo '<td>' . esc_html( $article['date'] ) . '</td>';
            echo '<td><a href="' . esc_url( $article['url'] ) . '" class="button button-small" disabled>' . esc_html__( 'Read', 'wccrm' ) . '</a></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '<p><em>' . esc_html__( 'This is placeholder content. Real news feeds will be available once API credentials are configured.', 'wccrm' ) . '</em></p>';
        echo '</div>';
        
        // Configuration hint
        echo '<h2>' . esc_html__( 'Configuration', 'wccrm' ) . '</h2>';
        echo '<p>' . esc_html__( 'To enable news feeds, configure API credentials through:', 'wccrm' ) . '</p>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Environment variables (recommended for production)', 'wccrm' ) . '</li>';
        echo '<li>' . esc_html__( 'WordPress options (encrypted storage)', 'wccrm' ) . '</li>';
        echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=wccrm-integrations' ) ) . '">' . esc_html__( 'Integrations page', 'wccrm' ) . '</a></li>';
        echo '</ul>';
        
        echo '</div>';
    }
}