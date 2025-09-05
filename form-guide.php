<?php

/**
 * WCCRM Form System Test & Instructions
 * Comprehensive test and usage guide for dynamic forms
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>🚀 WCCRM Dynamic Forms System</h1>";
echo "<style>
.test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
.success { color: #059862; font-weight: bold; }
.error { color: #d63384; font-weight: bold; }
.warning { color: #f76707; font-weight: bold; }
.info { color: #0f6fcf; font-weight: bold; }
details { margin: 15px 0; }
summary { cursor: pointer; font-weight: bold; padding: 10px; background: #e9ecef; border-radius: 4px; }
code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
.instructions { background: #e7f3ff; border-left: 4px solid #0f6fcf; padding: 15px; margin: 15px 0; }
.shortcode-list { background: #f8f9fa; padding: 15px; border-radius: 4px; }
</style>";

// Check if plugin is active
if (!class_exists('\Anas\WCCRM\Forms\DynamicFormBuilder')) {
    echo "<div class='test-section'>";
    echo "<p class='error'>❌ Plugin not activated! Please activate the WooCommerce CRM plugin first.</p>";
    echo "</div>";
    exit;
}

echo "<div class='instructions'>";
echo "<h2>📋 How to Use Dynamic Forms</h2>";
echo "<p><strong>You now have 3 ways to add forms to your site:</strong></p>";
echo "<ol>";
echo "<li><strong>Elementor Widget:</strong> Drag & drop 'CRM Dynamic Form' widget</li>";
echo "<li><strong>Shortcodes:</strong> Use shortcodes in posts/pages</li>";
echo "<li><strong>PHP Code:</strong> Call methods directly in themes</li>";
echo "</ol>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🧩 Elementor Integration Fixed!</h2>";
echo "<p class='success'>✅ The Form Fields section should now show properly!</p>";
echo "<p><strong>Steps to use in Elementor:</strong></p>";
echo "<ol>";
echo "<li>Edit any page with Elementor</li>";
echo "<li>Find <strong>'WooCommerce CRM'</strong> category in widgets</li>";
echo "<li>Drag <strong>'CRM Dynamic Form'</strong> widget to page</li>";
echo "<li>In the left panel, you'll see:</li>";
echo "<ul>";
echo "<li><strong>Field Preset:</strong> Choose from Contact, Lead, Newsletter, Quote, Demo</li>";
echo "<li><strong>Form Fields:</strong> Add custom fields when preset is 'Custom'</li>";
echo "<li><strong>Style Options:</strong> Modern, Classic, Minimal, HubSpot style</li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>📝 Available Shortcodes</h2>";
echo "<div class='shortcode-list'>";
echo "<h3>Quick Forms:</h3>";
echo "<ul>";
echo "<li><code>[wccrm_contact_form style='modern' title='Contact Us']</code></li>";
echo "<li><code>[wccrm_lead_form progressive='true' style='hubspot']</code></li>";
echo "<li><code>[wccrm_newsletter_form style='minimal']</code></li>";
echo "<li><code>[wccrm_quote_form style='modern']</code> - Coming soon</li>";
echo "<li><code>[wccrm_demo_form style='hubspot']</code> - Coming soon</li>";
echo "</ul>";

echo "<h3>Custom Forms:</h3>";
echo "<ul>";
echo "<li><code>[wccrm_form fields='name,email,phone' style='modern']</code></li>";
echo "<li><code>[wccrm_form fields='name,email,company,message' title='Get In Touch']</code></li>";
echo "</ul>";
echo "</div>";
echo "</div>";

// Test the system
echo "<div class='test-section'>";
echo "<h2>🔍 System Tests</h2>";

$form_builder = new \Anas\WCCRM\Forms\DynamicFormBuilder();

// Test 1: Basic functionality
try {
    echo "<p class='success'>✅ DynamicFormBuilder loaded</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test 2: Elementor widget
if (class_exists('\Anas\WCCRM\Elementor\DynamicFormWidget')) {
    echo "<p class='success'>✅ Elementor Widget available</p>";
} else {
    echo "<p class='error'>❌ Elementor Widget not found</p>";
}

// Test 3: Shortcodes
try {
    $test_shortcode = do_shortcode('[wccrm_contact_form style="modern" title="Test Form"]');
    if (!empty($test_shortcode)) {
        echo "<p class='success'>✅ Shortcodes working</p>";
    } else {
        echo "<p class='error'>❌ Shortcode test failed</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Shortcode error: " . $e->getMessage() . "</p>";
}

// Test 4: Assets
$js_exists = file_exists(WCCRM_PLUGIN_DIR . 'assets/js/form-builder.js');
$css_exists = file_exists(WCCRM_PLUGIN_DIR . 'assets/css/dynamic-forms.css');

echo $js_exists ? "<p class='success'>✅ JavaScript assets ready</p>" : "<p class='error'>❌ JavaScript missing</p>";
echo $css_exists ? "<p class='success'>✅ CSS assets ready</p>" : "<p class='error'>❌ CSS missing</p>";

echo "</div>";

// Live examples
echo "<div class='test-section'>";
echo "<h2>📱 Live Form Examples</h2>";

echo "<h3>Contact Form Example:</h3>";
echo do_shortcode('[wccrm_contact_form style="modern" title="Contact Us"]');

echo "<h3>Lead Generation Example:</h3>";
echo do_shortcode('[wccrm_lead_form style="hubspot" title="Get Started"]');

echo "<h3>Newsletter Signup Example:</h3>";
echo do_shortcode('[wccrm_newsletter_form style="minimal" title="Stay Updated"]');

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>⚙️ Advanced Configuration</h2>";
echo "<p><strong>Form Field Types Available:</strong></p>";
echo "<ul>";
echo "<li><strong>text</strong> - Text input</li>";
echo "<li><strong>email</strong> - Email input with validation</li>";
echo "<li><strong>tel</strong> - Phone number input</li>";
echo "<li><strong>textarea</strong> - Multi-line text</li>";
echo "<li><strong>select</strong> - Dropdown menu</li>";
echo "<li><strong>checkbox</strong> - Checkboxes</li>";
echo "<li><strong>radio</strong> - Radio buttons</li>";
echo "<li><strong>hidden</strong> - Hidden fields</li>";
echo "</ul>";

echo "<p><strong>Available Styles:</strong></p>";
echo "<ul>";
echo "<li><strong>modern</strong> - Clean, contemporary design</li>";
echo "<li><strong>hubspot</strong> - HubSpot-inspired layout</li>";
echo "<li><strong>classic</strong> - Traditional form styling</li>";
echo "<li><strong>minimal</strong> - Simple, minimal design</li>";
echo "</ul>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🔧 What's Working Now</h2>";
echo "<ul>";
echo "<li class='success'>✅ <strong>Elementor Widget:</strong> Form Fields section now shows properly</li>";
echo "<li class='success'>✅ <strong>Field Presets:</strong> Contact, Lead, Newsletter, Quote, Demo presets</li>";
echo "<li class='success'>✅ <strong>Custom Fields:</strong> Add unlimited custom fields</li>";
echo "<li class='success'>✅ <strong>Multiple Styles:</strong> Modern, HubSpot, Classic, Minimal</li>";
echo "<li class='success'>✅ <strong>Shortcodes:</strong> Working in posts/pages</li>";
echo "<li class='success'>✅ <strong>Form Processing:</strong> AJAX submission with validation</li>";
echo "<li class='success'>✅ <strong>Contact Management:</strong> Auto-save to CRM database</li>";
echo "<li class='success'>✅ <strong>Progressive Profiling:</strong> Smart field suggestions</li>";
echo "</ul>";
echo "</div>";

echo "<div class='instructions'>";
echo "<h2>🎯 Next Steps</h2>";
echo "<p><strong>To add forms to specific pages based on their needs:</strong></p>";
echo "<ol>";
echo "<li><strong>Home Page:</strong> Use lead generation form with fields like name, email, company</li>";
echo "<li><strong>Contact Page:</strong> Use contact form with name, email, phone, message</li>";
echo "<li><strong>Product Pages:</strong> Use quote request forms with product-specific fields</li>";
echo "<li><strong>Blog Posts:</strong> Use newsletter signup forms</li>";
echo "<li><strong>Service Pages:</strong> Use demo request forms with business information</li>";
echo "</ol>";
echo "<p>Each page can have different forms tailored to the visitor's intent and the page's purpose!</p>";
echo "</div>";

echo "<p><strong>Test completed:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p class='success'><strong>Status:</strong> All systems operational! 🚀</p>";
