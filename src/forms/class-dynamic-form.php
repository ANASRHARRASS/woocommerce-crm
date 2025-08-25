<?php

class DynamicForm {
    private $form_id;
    private $fields = [];
    private $form_action;
    private $method;

    public function __construct($form_id, $form_action = '', $method = 'POST') {
        $this->form_id = $form_id;
        $this->form_action = $form_action;
        $this->method = strtoupper($method);
    }

    public function addField($field) {
        $this->fields[] = $field;
    }

    public function render() {
        $form_html = '<form id="' . esc_attr($this->form_id) . '" action="' . esc_url($this->form_action) . '" method="' . esc_attr($this->method) . '">';
        
        foreach ($this->fields as $field) {
            $form_html .= $field->render();
        }

        $form_html .= '<button type="submit">Submit</button>';
        $form_html .= '</form>';

        return $form_html;
    }

    public function handleSubmission() {
        if ($_SERVER['REQUEST_METHOD'] === $this->method) {
            // Process form submission logic here
            // Capture leads and integrate with HubSpot, Zoho, etc.
        }
    }
}