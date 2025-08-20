<?php

namespace WooCommerceCRMPlugin\Forms;

class ContactForm {
    private $fields;

    public function __construct() {
        $this->fields = [];
    }

    public function addField($name, $type, $options = []) {
        $this->fields[$name] = [
            'type' => $type,
            'options' => $options,
        ];
    }

    public function render() {
        $output = '<form method="post" action="">';
        foreach ($this->fields as $name => $field) {
            $output .= $this->renderField($name, $field);
        }
        $output .= '<input type="submit" value="Submit">';
        $output .= '</form>';
        return $output;
    }

    private function renderField($name, $field) {
        $output = '';
        switch ($field['type']) {
            case 'text':
                $output .= '<label for="' . esc_attr($name) . '">' . esc_html(ucfirst($name)) . '</label>';
                $output .= '<input type="text" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '">';
                break;
            case 'select':
                $output .= '<label for="' . esc_attr($name) . '">' . esc_html(ucfirst($name)) . '</label>';
                $output .= '<select name="' . esc_attr($name) . '" id="' . esc_attr($name) . '">';
                foreach ($field['options'] as $value => $label) {
                    $output .= '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                }
                $output .= '</select>';
                break;
            // Add more field types as needed
        }
        return $output;
    }
}