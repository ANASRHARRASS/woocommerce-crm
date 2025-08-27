<?php

namespace Anas\WCCRM\Admin\Placeholders;

defined( 'ABSPATH' ) || exit;

/**
 * Shipping Rates placeholder page
 */
class ShippingRatesPage {

    public function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied', 'wccrm' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Shipping Rates', 'wccrm' ) . '</h1>';
        
        echo '<p>' . esc_html__( 'Manage shipping rates from multiple carriers and provide customers with competitive shipping options.', 'wccrm' ) . '</p>';
        
        // API credentials note
        echo '<div class="notice notice-info">';
        echo '<p><strong>' . esc_html__( 'Security Note:', 'wccrm' ) . '</strong> ';
        echo esc_html__( 'API credentials are resolved via environment variables or stored encrypted options; do not hard-code keys in source.', 'wccrm' );
        echo '</p>';
        echo '</div>';
        
        // Supported carriers
        echo '<h2>' . esc_html__( 'Supported Shipping Carriers', 'wccrm' ) . '</h2>';
        echo '<div class="card-container" style="display: flex; gap: 20px; flex-wrap: wrap;">';
        
        $carriers = [
            [
                'name' => 'UPS',
                'description' => __( 'United Parcel Service - Ground, Air, and International shipping.', 'wccrm' ),
                'status' => 'pending_configuration'
            ],
            [
                'name' => 'FedEx',
                'description' => __( 'Federal Express - Express, Ground, and Freight services.', 'wccrm' ),
                'status' => 'pending_configuration'
            ],
            [
                'name' => 'USPS',
                'description' => __( 'United States Postal Service - Priority, Express, and Standard shipping.', 'wccrm' ),
                'status' => 'pending_configuration'
            ],
            [
                'name' => 'DHL',
                'description' => __( 'DHL Express - International shipping specialist.', 'wccrm' ),
                'status' => 'pending_configuration'
            ],
            [
                'name' => 'Example Carrier',
                'description' => __( 'Example implementation for testing and development.', 'wccrm' ),
                'status' => 'available'
            ],
        ];
        
        foreach ( $carriers as $carrier ) {
            echo '<div class="card" style="max-width: 300px; padding: 15px; border: 1px solid #ccd0d4; background: #fff;">';
            echo '<h3>' . esc_html( $carrier['name'] ) . '</h3>';
            echo '<p>' . esc_html( $carrier['description'] ) . '</p>';
            
            if ( $carrier['status'] === 'available' ) {
                echo '<p><span class="dashicons dashicons-yes-alt" style="color: green;"></span> ' . esc_html__( 'Available', 'wccrm' ) . '</p>';
            } else {
                echo '<p><span class="dashicons dashicons-clock" style="color: orange;"></span> ' . esc_html__( 'Pending Configuration', 'wccrm' ) . '</p>';
            }
            echo '</div>';
        }
        
        echo '</div>';
        
        // Rate comparison tool
        echo '<h2>' . esc_html__( 'Rate Comparison Tool', 'wccrm' ) . '</h2>';
        echo '<form method="post" style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd;">';
        echo '<h3>' . esc_html__( 'Get Shipping Quotes', 'wccrm' ) . '</h3>';
        
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th scope="row"><label for="origin_zip">' . esc_html__( 'Origin ZIP Code', 'wccrm' ) . '</label></th>';
        echo '<td><input type="text" id="origin_zip" name="origin_zip" class="regular-text" placeholder="12345"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="dest_zip">' . esc_html__( 'Destination ZIP Code', 'wccrm' ) . '</label></th>';
        echo '<td><input type="text" id="dest_zip" name="dest_zip" class="regular-text" placeholder="67890"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="weight">' . esc_html__( 'Package Weight (lbs)', 'wccrm' ) . '</label></th>';
        echo '<td><input type="number" id="weight" name="weight" class="regular-text" placeholder="5" step="0.1" min="0.1"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="dimensions">' . esc_html__( 'Dimensions (L×W×H inches)', 'wccrm' ) . '</label></th>';
        echo '<td><input type="text" id="dimensions" name="dimensions" class="regular-text" placeholder="12×8×6"></td>';
        echo '</tr>';
        
        echo '</table>';
        
        echo '<p><button type="submit" class="button button-primary" disabled>' . esc_html__( 'Get Rates', 'wccrm' ) . '</button></p>';
        echo '<p><em>' . esc_html__( 'Rate comparison will be available once carrier APIs are configured.', 'wccrm' ) . '</em></p>';
        echo '</form>';
        
        // Sample rates table
        echo '<h3>' . esc_html__( 'Sample Rate Comparison', 'wccrm' ) . '</h3>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th scope="col">' . esc_html__( 'Carrier', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Service', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Transit Time', 'wccrm' ) . '</th>';
        echo '<th scope="col">' . esc_html__( 'Rate', 'wccrm' ) . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $sample_rates = [
            ['carrier' => 'UPS', 'service' => 'Ground', 'transit' => '3-5 business days', 'rate' => '$8.50'],
            ['carrier' => 'FedEx', 'service' => 'Home Delivery', 'transit' => '3-5 business days', 'rate' => '$9.25'],
            ['carrier' => 'USPS', 'service' => 'Priority Mail', 'transit' => '2-3 business days', 'rate' => '$7.90'],
            ['carrier' => 'UPS', 'service' => '2nd Day Air', 'transit' => '2 business days', 'rate' => '$18.75'],
            ['carrier' => 'FedEx', 'service' => 'Express Saver', 'transit' => '3 business days', 'rate' => '$16.40'],
        ];
        
        foreach ( $sample_rates as $rate ) {
            echo '<tr>';
            echo '<td><strong>' . esc_html( $rate['carrier'] ) . '</strong></td>';
            echo '<td>' . esc_html( $rate['service'] ) . '</td>';
            echo '<td>' . esc_html( $rate['transit'] ) . '</td>';
            echo '<td><strong>' . esc_html( $rate['rate'] ) . '</strong></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '<p><em>' . esc_html__( 'This is sample data. Real rates will be fetched from carrier APIs once configured.', 'wccrm' ) . '</em></p>';
        
        // Configuration instructions
        echo '<h2>' . esc_html__( 'Configuration', 'wccrm' ) . '</h2>';
        echo '<p>' . esc_html__( 'To enable shipping rate calculations, you will need to:', 'wccrm' ) . '</p>';
        echo '<ol>';
        echo '<li>' . esc_html__( 'Obtain API credentials from shipping carriers', 'wccrm' ) . '</li>';
        echo '<li>' . esc_html__( 'Configure credentials via environment variables or encrypted options', 'wccrm' ) . '</li>';
        echo '<li>' . esc_html__( 'Test connectivity through the', 'wccrm' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=wccrm-integrations' ) ) . '">' . esc_html__( 'Integrations page', 'wccrm' ) . '</a></li>';
        echo '</ol>';
        
        echo '<div class="notice notice-warning">';
        echo '<p><strong>' . esc_html__( 'Important:', 'wccrm' ) . '</strong> ';
        echo esc_html__( 'Carrier APIs typically require business accounts and may have usage fees. Review each carrier\'s pricing and terms before implementation.', 'wccrm' );
        echo '</p>';
        echo '</div>';
        
        echo '</div>';
    }
}