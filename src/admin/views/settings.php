<?php
// This file contains the view for the admin settings page, allowing users to configure plugin options.

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

?>

<div class="wrap">
    <h1><?php esc_html_e( 'Universal Lead Capture Plugin Settings', 'universal-lead-capture-plugin' ); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields( 'ulc_plugin_options' );
        do_settings_sections( 'ulc_plugin_options' );
        ?>
        
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Enable Lead Capture', 'universal-lead-capture-plugin' ); ?></th>
                <td>
                    <input type="checkbox" name="ulc_enable_lead_capture" value="1" <?php checked( 1, get_option( 'ulc_enable_lead_capture' ) ); ?> />
                    <label for="ulc_enable_lead_capture"><?php esc_html_e( 'Check to enable lead capture functionality.', 'universal-lead-capture-plugin' ); ?></label>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'HubSpot API Key', 'universal-lead-capture-plugin' ); ?></th>
                <td>
                    <input type="text" name="ulc_hubspot_api_key" value="<?php echo esc_attr( get_option( 'ulc_hubspot_api_key' ) ); ?>" />
                    <label for="ulc_hubspot_api_key"><?php esc_html_e( 'Enter your HubSpot API key.', 'universal-lead-capture-plugin' ); ?></label>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Zoho API Key', 'universal-lead-capture-plugin' ); ?></th>
                <td>
                    <input type="text" name="ulc_zoho_api_key" value="<?php echo esc_attr( get_option( 'ulc_zoho_api_key' ) ); ?>" />
                    <label for="ulc_zoho_api_key"><?php esc_html_e( 'Enter your Zoho API key.', 'universal-lead-capture-plugin' ); ?></label>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Google Ads Tracking ID', 'universal-lead-capture-plugin' ); ?></th>
                <td>
                    <input type="text" name="ulc_google_ads_tracking_id" value="<?php echo esc_attr( get_option( 'ulc_google_ads_tracking_id' ) ); ?>" />
                    <label for="ulc_google_ads_tracking_id"><?php esc_html_e( 'Enter your Google Ads Tracking ID.', 'universal-lead-capture-plugin' ); ?></label>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Facebook Ads Pixel ID', 'universal-lead-capture-plugin' ); ?></th>
                <td>
                    <input type="text" name="ulc_facebook_ads_pixel_id" value="<?php echo esc_attr( get_option( 'ulc_facebook_ads_pixel_id' ) ); ?>" />
                    <label for="ulc_facebook_ads_pixel_id"><?php esc_html_e( 'Enter your Facebook Ads Pixel ID.', 'universal-lead-capture-plugin' ); ?></label>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>