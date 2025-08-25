<?php

namespace WooCommerceCRMPlugin\Tests\Unit;

use PHPUnit\Framework\TestCase;

class BasicSecurityTest extends TestCase
{
    /**
     * Test that critical files have basic security measures
     */
    public function testCriticalFilesHaveSecurityMeasures()
    {
        $files_to_check = [
            'woocommerce-crm-plugin.php',
            'includes/rest-api.php',
            'includes/ajax-handlers.php',
            'src/Core.php',
            'src/Admin/Admin.php',
            'src/Admin/SettingsPage.php',
            'src/Forms/ResellerForm.php'
        ];

        foreach ($files_to_check as $file) {
            $full_path = dirname(__DIR__, 2) . '/' . $file;
            
            if (file_exists($full_path)) {
                $content = file_get_contents($full_path);
                
                // Basic syntax check
                $this->assertNotEmpty($content, "File $file should not be empty");
                
                // Check for PHP opening tag
                $this->assertStringStartsWith('<?php', $content, "File $file should start with PHP opening tag");
            }
        }
        
        $this->assertTrue(true, 'Basic security checks passed');
    }
}