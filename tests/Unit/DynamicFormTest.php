<?php

use PHPUnit\Framework\TestCase;
use YourNamespace\Forms\DynamicForm;

class DynamicFormTest extends TestCase
{
    protected $dynamicForm;

    protected function setUp(): void
    {
        $this->dynamicForm = new DynamicForm();
    }

    public function testGenerateFieldsWithValidAttributes()
    {
        $attributes = [
            'color' => ['red', 'blue', 'green'],
            'size' => ['small', 'medium', 'large'],
        ];

        $fields = $this->dynamicForm->generateFields($attributes);
        
        $this->assertIsArray($fields);
        $this->assertCount(2, $fields);
        $this->assertArrayHasKey('color', $fields);
        $this->assertArrayHasKey('size', $fields);
    }

    public function testGenerateFieldsWithEmptyAttributes()
    {
        $attributes = [];

        $fields = $this->dynamicForm->generateFields($attributes);
        
        $this->assertIsArray($fields);
        $this->assertEmpty($fields);
    }

    public function testGenerateFieldsWithInvalidAttributes()
    {
        $this->expectException(InvalidArgumentException::class);
        
        $attributes = null;
        $this->dynamicForm->generateFields($attributes);
    }
}