<?php

use PHPUnit\Framework\TestCase;

class ZohoTest extends TestCase
{
    protected $zohoClient;

    protected function setUp(): void
    {
        // Initialize the Zoho client before each test
        $this->zohoClient = new \YourNamespace\Integrations\Zoho\ZohoClient();
    }

    public function testCreateContact()
    {
        // Test creating a contact in Zoho
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
        ];

        $response = $this->zohoClient->createContact($contactData);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($contactData['email'], $response['email']);
    }

    public function testSyncContact()
    {
        // Test syncing a contact with Zoho
        $contactId = '12345';
        $updatedData = [
            'email' => 'john.doe@newdomain.com',
        ];

        $response = $this->zohoClient->syncContact($contactId, $updatedData);
        $this->assertTrue($response['success']);
    }

    public function testGetContact()
    {
        // Test retrieving a contact from Zoho
        $contactId = '12345';
        $response = $this->zohoClient->getContact($contactId);
        $this->assertArrayHasKey('name', $response);
        $this->assertEquals('John Doe', $response['name']);
    }

    public function testDeleteContact()
    {
        // Test deleting a contact in Zoho
        $contactId = '12345';
        $response = $this->zohoClient->deleteContact($contactId);
        $this->assertTrue($response['success']);
    }
}