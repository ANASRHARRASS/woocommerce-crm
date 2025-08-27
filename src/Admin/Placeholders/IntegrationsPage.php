<?php

namespace Anas\WCCRM\Admin\Placeholders;

defined( 'ABSPATH' ) || exit;

/**
 * Integrations placeholder page
 */
class IntegrationsPage {

    public function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied', 'wccrm' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Integrations', 'wccrm' ) . '</h1>';
        
        echo '<p>' . esc_html__( 'Manage external service integrations and API connections for your CRM system.', 'wccrm' ) . '</p>';
        
        // Security notice
        echo '<div class="notice notice-info">';
        echo '<p><strong>' . esc_html__( 'Security First:', 'wccrm' ) . '</strong> ';
        echo esc_html__( 'API credentials are resolved via environment variables or stored encrypted options; do not hard-code keys in source code.', 'wccrm' );
        echo '</p>';
        echo '</div>';
        
        // Link to existing settings if available
        if ( class_exists( 'WCP\\Admin\\Settings' ) ) {
            echo '<div class="notice notice-success">';
            echo '<p>';
            echo esc_html__( 'Existing settings page detected. ', 'wccrm' );
            echo '<a href="' . esc_url( admin_url( 'admin.php?page=wcp-settings' ) ) . '" class="button">' . esc_html__( 'Access Current Settings', 'wccrm' ) . '</a>';
            echo '</p>';
            echo '</div>';
        }
        
        // Integration categories
        echo '<h2>' . esc_html__( 'Available Integrations', 'wccrm' ) . '</h2>';
        
        $integration_categories = [
            'crm' => [
                'title' => __( 'CRM & Lead Management', 'wccrm' ),
                'integrations' => [
                    ['name' => 'HubSpot', 'status' => 'available', 'description' => __( 'Sync leads and contacts with HubSpot CRM.', 'wccrm' )],
                    ['name' => 'Zoho CRM', 'status' => 'available', 'description' => __( 'Connect with Zoho CRM for lead management.', 'wccrm' )],
                    ['name' => 'Salesforce', 'status' => 'pending', 'description' => __( 'Enterprise CRM integration (planned).', 'wccrm' )],
                ]
            ],
            'marketing' => [
                'title' => __( 'Marketing & Advertising', 'wccrm' ),
                'integrations' => [
                    ['name' => 'Google Ads', 'status' => 'available', 'description' => __( 'Track conversions and optimize ad campaigns.', 'wccrm' )],
                    ['name' => 'Facebook Ads', 'status' => 'available', 'description' => __( 'Sync leads from Facebook advertising.', 'wccrm' )],
                    ['name' => 'Mailchimp', 'status' => 'pending', 'description' => __( 'Email marketing automation (planned).', 'wccrm' )],
                ]
            ],
            'social' => [
                'title' => __( 'Social Media', 'wccrm' ),
                'integrations' => [
                    ['name' => 'Facebook Pages', 'status' => 'available', 'description' => __( 'Capture leads from Facebook business pages.', 'wccrm' )],
                    ['name' => 'Instagram Business', 'status' => 'pending', 'description' => __( 'Instagram lead generation (planned).', 'wccrm' )],
                    ['name' => 'LinkedIn', 'status' => 'pending', 'description' => __( 'B2B lead capture from LinkedIn (planned).', 'wccrm' )],
                ]
            ],
            'news' => [
                'title' => __( 'News & Content', 'wccrm' ),
                'integrations' => [
                    ['name' => 'NewsAPI', 'status' => 'available', 'description' => __( 'Professional news aggregation service.', 'wccrm' )],
                    ['name' => 'Google News', 'status' => 'available', 'description' => __( 'Google News API integration.', 'wccrm' )],
                    ['name' => 'RSS Feeds', 'status' => 'available', 'description' => __( 'Generic RSS/Atom feed support.', 'wccrm' )],
                ]
            ],
            'shipping' => [
                'title' => __( 'Shipping & Logistics', 'wccrm' ),
                'integrations' => [
                    ['name' => 'UPS', 'status' => 'pending', 'description' => __( 'UPS shipping rates and tracking.', 'wccrm' )],
                    ['name' => 'FedEx', 'status' => 'pending', 'description' => __( 'FedEx shipping services.', 'wccrm' )],
                    ['name' => 'USPS', 'status' => 'pending', 'description' => __( 'US Postal Service integration.', 'wccrm' )],
                ]
            ]
        ];
        
        foreach ( $integration_categories as $category_key => $category ) {
            echo '<h3>' . esc_html( $category['title'] ) . '</h3>';
            echo '<div class="integration-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-bottom: 30px;">';
            
            foreach ( $category['integrations'] as $integration ) {
                $status_class = $integration['status'] === 'available' ? 'available' : 'pending';
                $status_color = $integration['status'] === 'available' ? 'green' : 'orange';
                $status_icon = $integration['status'] === 'available' ? 'dashicons-yes-alt' : 'dashicons-clock';
                $status_text = $integration['status'] === 'available' ? __( 'Available', 'wccrm' ) : __( 'Planned', 'wccrm' );
                
                echo '<div class="integration-card" style="padding: 15px; border: 1px solid #ccd0d4; background: #fff; border-radius: 3px;">';
                echo '<h4 style="margin: 0 0 10px 0;">' . esc_html( $integration['name'] ) . '</h4>';
                echo '<p style="margin: 0 0 10px 0; font-size: 13px; color: #666;">' . esc_html( $integration['description'] ) . '</p>';
                echo '<p style="margin: 0;"><span class="dashicons ' . esc_attr( $status_icon ) . '" style="color: ' . esc_attr( $status_color ) . ';"></span> ' . esc_html( $status_text ) . '</p>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        // Configuration methods
        echo '<h2>' . esc_html__( 'Configuration Methods', 'wccrm' ) . '</h2>';
        echo '<div class="config-methods" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">';
        
        // Environment variables
        echo '<div class="config-method" style="padding: 20px; border: 1px solid #ccd0d4; background: #f9f9f9;">';
        echo '<h3><span class="dashicons dashicons-admin-settings"></span> ' . esc_html__( 'Environment Variables', 'wccrm' ) . '</h3>';
        echo '<p>' . esc_html__( 'Recommended for production environments. Store credentials securely outside the web root.', 'wccrm' ) . '</p>';
        echo '<code>WCCRM_HUBSPOT_TOKEN=your_token_here</code><br>';
        echo '<code>WCCRM_NEWSAPI_KEY=your_key_here</code>';
        echo '<p><em>' . esc_html__( 'Most secure method', 'wccrm' ) . '</em></p>';
        echo '</div>';
        
        // WordPress options
        echo '<div class="config-method" style="padding: 20px; border: 1px solid #ccd0d4; background: #f9f9f9;">';
        echo '<h3><span class="dashicons dashicons-admin-tools"></span> ' . esc_html__( 'WordPress Options', 'wccrm' ) . '</h3>';
        echo '<p>' . esc_html__( 'Store encrypted credentials in WordPress database. Good for shared hosting.', 'wccrm' ) . '</p>';
        echo '<p>' . esc_html__( 'Credentials are automatically encrypted before storage and decrypted when needed.', 'wccrm' ) . '</p>';
        echo '<p><em>' . esc_html__( 'Good security with convenience', 'wccrm' ) . '</em></p>';
        echo '</div>';
        
        // Legacy settings
        echo '<div class="config-method" style="padding: 20px; border: 1px solid #ccd0d4; background: #f9f9f9;">';
        echo '<h3><span class="dashicons dashicons-admin-generic"></span> ' . esc_html__( 'Legacy Settings', 'wccrm' ) . '</h3>';
        echo '<p>' . esc_html__( 'Existing integrations may use the legacy settings interface.', 'wccrm' ) . '</p>';
        if ( class_exists( 'WCP\\Admin\\Settings' ) ) {
            echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=wcp-settings' ) ) . '" class="button">' . esc_html__( 'Access Legacy Settings', 'wccrm' ) . '</a></p>';
        }
        echo '<p><em>' . esc_html__( 'Backward compatibility', 'wccrm' ) . '</em></p>';
        echo '</div>';
        
        echo '</div>';
        
        // API status check
        echo '<h2>' . esc_html__( 'Integration Status', 'wccrm' ) . '</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th scope="col">' . esc_html__( 'Service', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Status', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Last Check', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Actions', 'wccrm' ) . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $status_checks = [
            ['service' => 'HubSpot', 'status' => 'not_configured', 'last_check' => 'Never'],
            ['service' => 'Zoho CRM', 'status' => 'not_configured', 'last_check' => 'Never'],
            ['service' => 'Google Ads', 'status' => 'not_configured', 'last_check' => 'Never'],
            ['service' => 'Facebook Ads', 'status' => 'not_configured', 'last_check' => 'Never'],
            ['service' => 'NewsAPI', 'status' => 'not_configured', 'last_check' => 'Never'],
        ];
        
        foreach ( $status_checks as $check ) {
            echo '<tr>';
            echo '<td><strong>' . esc_html( $check['service'] ) . '</strong></td>';
            
            if ( $check['status'] === 'not_configured' ) {
                echo '<td><span style="color: #d63638;">' . esc_html__( 'Not Configured', 'wccrm' ) . '</span></td>';
            }
            
            echo '<td>' . esc_html( $check['last_check'] ) . '</td>';
            echo '<td><button class="button button-small" disabled>' . esc_html__( 'Test Connection', 'wccrm' ) . '</button></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        // Documentation
        echo '<h2>' . esc_html__( 'Documentation', 'wccrm' ) . '</h2>';
        echo '<p>' . esc_html__( 'For detailed integration setup instructions, please refer to:', 'wccrm' ) . '</p>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Plugin documentation (coming soon)', 'wccrm' ) . '</li>';
        echo '<li>' . esc_html__( 'Individual service provider API documentation', 'wccrm' ) . '</li>';
        echo '<li>' . esc_html__( 'Environment variable configuration guides', 'wccrm' ) . '</li>';
        echo '</ul>';
        
        echo '</div>';
    }
}