<?php

use PHPUnit\Framework\TestCase;
use Anas\WCCRM\Forms\FormSubmissionLinker;
use Anas\WCCRM\Contacts\ContactRepository;
use Anas\WCCRM\Contacts\InterestUpdater;

/**
 * Test FormSubmissionLinker functionality
 * 
 * Note: This is a basic test class for the new source and consent detection features.
 * Full test implementation should include database mocking.
 */
class FormSubmissionLinkerTest extends TestCase
{
    protected $formSubmissionLinker;

    protected function setUp(): void
    {
        // Mock dependencies for basic testing
        $contactRepository = $this->createMock(ContactRepository::class);
        $interestUpdater = $this->createMock(InterestUpdater::class);
        
        $this->formSubmissionLinker = new FormSubmissionLinker($contactRepository, $interestUpdater);
    }

    public function testSourceDetectionFromFields()
    {
        $reflection = new ReflectionClass($this->formSubmissionLinker);
        $method = $reflection->getMethod('detect_source');
        $method->setAccessible(true);

        // Test with explicit source field
        $result = $method->invoke($this->formSubmissionLinker, ['source' => 'facebook'], 'contact_form');
        $this->assertEquals('facebook', $result);

        // Test with lead_source field
        $result = $method->invoke($this->formSubmissionLinker, ['lead_source' => 'google'], 'contact_form');
        $this->assertEquals('google', $result);

        // Test with utm_source field
        $result = $method->invoke($this->formSubmissionLinker, ['utm_source' => 'newsletter'], 'contact_form');
        $this->assertEquals('newsletter', $result);

        // Test fallback to form key
        $result = $method->invoke($this->formSubmissionLinker, [], 'contact_form');
        $this->assertEquals('form:contact_form', $result);
    }

    public function testWhatsAppConsentDetection()
    {
        $reflection = new ReflectionClass($this->formSubmissionLinker);
        $method = $reflection->getMethod('detect_consent_flags');
        $method->setAccessible(true);

        // Test positive consent values
        $positive_values = ['1', 'on', 'yes', 'true', 'accepted'];
        foreach ($positive_values as $value) {
            $result = $method->invoke($this->formSubmissionLinker, ['whatsapp_consent' => $value]);
            $this->assertArrayHasKey('whatsapp', $result);
            $this->assertTrue($result['whatsapp']);
        }

        // Test different field names
        $field_names = ['whatsapp_consent', 'consent_whatsapp', 'whatsapp_optin', 'optin_whatsapp'];
        foreach ($field_names as $field_name) {
            $result = $method->invoke($this->formSubmissionLinker, [$field_name => '1']);
            $this->assertArrayHasKey('whatsapp', $result);
            $this->assertTrue($result['whatsapp']);
        }

        // Test no consent
        $result = $method->invoke($this->formSubmissionLinker, []);
        $this->assertEmpty($result);

        // Test false values
        $result = $method->invoke($this->formSubmissionLinker, ['whatsapp_consent' => '0']);
        $this->assertEmpty($result);
    }
}