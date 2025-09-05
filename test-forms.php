<?php

/**
 * WCCRM Form System Test
 * Quick test to verify forms are working properly
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if our classes are loaded
if (!class_exists('\Anas\WCCRM\Forms\DynamicFormBuilder')) {
    die('DynamicFormBuilder class not found. Plugin may not be activated.');
}

if (!class_exists('\Anas\WCCRM\Forms\FormShortcodes')) {
    die('FormShortcodes class not found. Plugin may not be activated.');
}

echo "<h1>WCCRM Form System Test</h1>";

// Test 1: Create form builder instance
try {
    $form_builder = new \Anas\WCCRM\Forms\DynamicFormBuilder();
    echo "<p>✅ DynamicFormBuilder instance created successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error creating DynamicFormBuilder: " . $e->getMessage() . "</p>";
}

// Test 2: Test shortcode processing
try {
    echo "<h2>Test Shortcode Processing</h2>";

    // Test basic contact form shortcode
    $shortcode_output = do_shortcode('[wccrm_contact_form style="modern" title="Test Contact Form"]');

    if (!empty($shortcode_output)) {
        echo "<p>✅ Contact form shortcode processed successfully</p>";
        echo "<details><summary>View Form HTML</summary><pre>" . htmlspecialchars($shortcode_output) . "</pre></details>";
    } else {
        echo "<p>❌ Contact form shortcode returned empty output</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error processing shortcode: " . $e->getMessage() . "</p>";
}

// Test 3: Test form rendering
try {
    echo "<h2>Test Form Rendering</h2>";

    $form_config = [
        'id' => 'test-form',
        'title' => 'Test Dynamic Form',
        'fields' => ['name', 'email', 'phone'],
        'style' => 'modern'
    ];

    $rendered_form = $form_builder->render_form($form_config);

    if (!empty($rendered_form)) {
        echo "<p>✅ Form rendering successful</p>";
        echo "<details><summary>View Rendered Form</summary><div>" . $rendered_form . "</div></details>";
    } else {
        echo "<p>❌ Form rendering returned empty output</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error rendering form: " . $e->getMessage() . "</p>";
}

// Test 4: Check if form assets exist
echo "<h2>Test Form Assets</h2>";

$assets_dir = WCCRM_PLUGIN_DIR . 'assets/';
$js_file = $assets_dir . 'js/form-builder.js';
$css_file = $assets_dir . 'css/dynamic-forms.css';

if (file_exists($js_file)) {
    echo "<p>✅ JavaScript file exists: " . basename($js_file) . "</p>";
} else {
    echo "<p>❌ JavaScript file missing: " . $js_file . "</p>";
}

if (file_exists($css_file)) {
    echo "<p>✅ CSS file exists: " . basename($css_file) . "</p>";
} else {
    echo "<p>❌ CSS file missing: " . $css_file . "</p>";
}

echo "<h2>Plugin Constants</h2>";
echo "<ul>";
echo "<li>WCCRM_PLUGIN_DIR: " . (defined('WCCRM_PLUGIN_DIR') ? WCCRM_PLUGIN_DIR : 'Not defined') . "</li>";
echo "<li>WCCRM_PLUGIN_URL: " . (defined('WCCRM_PLUGIN_URL') ? WCCRM_PLUGIN_URL : 'Not defined') . "</li>";
echo "<li>WCCRM_VERSION: " . (defined('WCCRM_VERSION') ? WCCRM_VERSION : 'Not defined') . "</li>";
echo "</ul>";

echo "<h2>WordPress Check</h2>";
echo "<p>WordPress version: " . get_bloginfo('version') . "</p>";
echo "<p>Active theme: " . get_option('stylesheet') . "</p>";

// Check if WooCommerce is active
if (class_exists('WooCommerce')) {
    echo "<p>✅ WooCommerce is active</p>";
} else {
    echo "<p>⚠️ WooCommerce is not active</p>";
}

echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
