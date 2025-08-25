<?php
// filepath: /universal-lead-capture-plugin/universal-lead-capture-plugin/src/forms/fields/field-select.php

class FieldSelect {
    private $name;
    private $label;
    private $options;
    private $selected;

    public function __construct($name, $label, $options = [], $selected = null) {
        $this->name = $name;
        $this->label = $label;
        $this->options = $options;
        $this->selected = $selected;
    }

    public function render() {
        $html = '<label for="' . esc_attr($this->name) . '">' . esc_html($this->label) . '</label>';
        $html .= '<select name="' . esc_attr($this->name) . '" id="' . esc_attr($this->name) . '">';
        
        foreach ($this->options as $value => $text) {
            $isSelected = ($value == $this->selected) ? ' selected' : '';
            $html .= '<option value="' . esc_attr($value) . '"' . $isSelected . '>' . esc_html($text) . '</option>';
        }

        $html .= '</select>';
        return $html;
    }

    public function setSelected($value) {
        $this->selected = $value;
    }
}
?>