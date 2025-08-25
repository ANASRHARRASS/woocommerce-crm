<?php

class FormBuilder {
    private $fields = [];
    private $formAttributes = [];

    public function __construct($attributes = []) {
        $this->formAttributes = $attributes;
    }

    public function addField($field) {
        $this->fields[] = $field;
    }

    public function render() {
        $formHtml = '<form ' . $this->buildAttributes($this->formAttributes) . '>';

        foreach ($this->fields as $field) {
            $formHtml .= $field->render();
        }

        $formHtml .= '<button type="submit">Submit</button>';
        $formHtml .= '</form>';

        return $formHtml;
    }

    private function buildAttributes($attributes) {
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= sprintf('%s="%s" ', $key, esc_attr($value));
        }
        return trim($attrString);
    }
}