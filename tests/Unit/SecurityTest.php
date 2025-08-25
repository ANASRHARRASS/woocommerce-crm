<?php

namespace WooCommerceCRMPlugin\Tests\Unit;

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    /**
     * Test that the main plugin file prevents direct access
     */
    public function testMainPluginFileHasAbspathCheck()
    {
        $plugin_file = dirname(__DIR__, 2) . '/woocommerce-crm-plugin.php';
        $content = file_get_contents($plugin_file);
        
        $this->assertStringContains("defined( 'ABSPATH' )", $content);
        $this->assertStringContains('exit', $content);
    }

    /**
     * Test that REST API endpoints have proper permission callbacks
     */
    public function testRestApiHasPermissionCallbacks()
    {
        $rest_api_file = dirname(__DIR__, 2) . '/includes/rest-api.php';
        $content = file_get_contents($rest_api_file);
        
        // Should not have __return_true anymore
        $this->assertStringNotContainsString("'permission_callback' => '__return_true'", $content);
        
        // Should have proper permission callback methods
        $this->assertStringContains('check_contact_permissions', $content);
        $this->assertStringContains('check_orders_permissions', $content);
        $this->assertStringContains('check_shipping_permissions', $content);
    }

    /**
     * Test that forms include nonce verification
     */
    public function testFormsHaveNonceVerification()
    {
        $reseller_form_file = dirname(__DIR__, 2) . '/src/Forms/ResellerForm.php';
        $content = file_get_contents($reseller_form_file);
        
        $this->assertStringContains('wp_nonce_field', $content);
        $this->assertStringContains('wp_verify_nonce', $content);
    }

    /**
     * Test that AJAX handlers verify nonces
     */
    public function testAjaxHandlersVerifyNonces()
    {
        $ajax_file = dirname(__DIR__, 2) . '/includes/ajax-handlers.php';
        $content = file_get_contents($ajax_file);
        
        $this->assertStringContains('wp_verify_nonce', $content);
        $this->assertStringContains('wp_send_json_error', $content);
    }

    /**
     * Test that admin pages check user capabilities
     */
    public function testAdminPagesCheckCapabilities()
    {
        $admin_file = dirname(__DIR__, 2) . '/src/Admin/Admin.php';
        $content = file_get_contents($admin_file);
        
        $this->assertStringContains('current_user_can', $content);
        
        $settings_file = dirname(__DIR__, 2) . '/src/Admin/SettingsPage.php';
        $content = file_get_contents($settings_file);
        
        $this->assertStringContains('current_user_can', $content);
    }
}