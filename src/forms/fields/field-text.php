<?php
// filepath: /universal-lead-capture-plugin/universal-lead-capture-plugin/src/forms/fields/field-text.php

class FieldText {
    private $name;
    private $label;
    private $placeholder;
    private $required;

    public function __construct($name, $label, $placeholder = '', $required = false) {
        $this->name = $name;
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->required = $required;
    }

    public function render() {
        $requiredAttribute = $this->required ? 'required' : '';
        return sprintf(
            '<div class="form-field">
                <label for="%s">%s</label>
                <input type="text" name="%s" id="%s" placeholder="%s" %s />
            </div>',
            esc_attr($this->name),
            esc_html($this->label),
            esc_attr($this->name),
            esc_attr($this->name),
            esc_attr($this->placeholder),
            $requiredAttribute
        );
    }

    public function getName() {
        return $this->name;
    }

    public function getLabel() {
        return $this->label;
    }

    public function getPlaceholder() {
        return $this->placeholder;
    }

    public function isRequired() {
        return $this->required;
    }
}