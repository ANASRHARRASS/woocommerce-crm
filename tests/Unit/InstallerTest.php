<?php

use PHPUnit\Framework\TestCase;
use Anas\WCCRM\Database\Installer;

/**
 * Test Database Installer functionality
 * 
 * Note: This is a basic test class for schema version verification.
 * Full test implementation should include database mocking.
 */
class InstallerTest extends TestCase
{
    protected $installer;

    protected function setUp(): void
    {
        $this->installer = new Installer();
    }

    public function testSchemaVersionConstant()
    {
        $reflection = new ReflectionClass($this->installer);
        $constant = $reflection->getConstant('CURRENT_SCHEMA_VERSION');
        
        $this->assertEquals('2.1.0', $constant);
    }

    public function testInstallerHasRequiredMethods()
    {
        $this->assertTrue(method_exists($this->installer, 'maybe_upgrade'));
        
        // Test that run_migrations method exists (protected)
        $reflection = new ReflectionClass($this->installer);
        $this->assertTrue($reflection->hasMethod('run_migrations'));
        
        // Test that create_lead_journal_table method exists (private)
        $this->assertTrue($reflection->hasMethod('create_lead_journal_table'));
    }

    public function testCreateLeadJournalTableMethodStructure()
    {
        $reflection = new ReflectionClass($this->installer);
        $method = $reflection->getMethod('create_lead_journal_table');
        
        // Verify method exists and is private
        $this->assertTrue($method->isPrivate());
        
        // Verify method has one parameter (charset_collate)
        $this->assertEquals(1, $method->getNumberOfParameters());
    }
}