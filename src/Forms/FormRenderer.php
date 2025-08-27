<?php

namespace Anas\WCCRM\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Form renderer for generating HTML output
 */
class FormRenderer {

    public function render( FormModel $form ): string {
        if ( ! $form->is_active() ) {
            return '<div class="wccrm-error">Form is not active.</div>';
        }

        $fields = $form->get_fields();
        if ( empty( $fields ) ) {
            return '<div class="wccrm-error">Form has no fields configured.</div>';
        }

        $settings = $form->get_settings();
        $form_class = 'wccrm-form wccrm-form-' . esc_attr( $form->form_key );
        
        $html = '<form class="' . esc_attr( $form_class ) . '" method="post" data-form-key="' . esc_attr( $form->form_key ) . '">';
        
        // Add nonce for security
        $html .= wp_nonce_field( 'wccrm_form_' . $form->form_key, '_wpnonce', true, false );
        
        // Hidden form key field
        $html .= '<input type="hidden" name="__wccrm_form_key" value="' . esc_attr( $form->form_key ) . '" />';
        
        // Render fields
        foreach ( $fields as $field ) {
            $html .= $this->render_field( $field );
        }
        
        // Submit button
        $submit_text = esc_html( $settings['submit_text'] ?? 'Submit' );
        $html .= '<div class="wccrm-field-group">';
        $html .= '<button type="submit" class="wccrm-submit-btn">' . $submit_text . '</button>';
        $html .= '</div>';
        
        $html .= '</form>';
        
        // Add basic styling and JavaScript
        $html .= $this->get_form_styles();
        $html .= $this->get_form_scripts();
        
        return $html;
    }

    protected function render_field( array $field ): string {
        $type = $field['type'] ?? 'text';
        $name = $field['name'] ?? '';
        $label = $field['label'] ?? '';
        $required = ! empty( $field['required'] );
        $placeholder = $field['placeholder'] ?? '';
        
        if ( empty( $name ) ) {
            return '<!-- Invalid field: missing name -->';
        }
        
        $field_id = 'wccrm_field_' . sanitize_key( $name );
        $field_class = 'wccrm-field wccrm-field-' . esc_attr( $type );
        if ( $required ) {
            $field_class .= ' wccrm-field-required';
        }
        
        $html = '<div class="wccrm-field-group">';
        
        // Label
        if ( ! empty( $label ) ) {
            $html .= '<label for="' . esc_attr( $field_id ) . '" class="wccrm-field-label">';
            $html .= esc_html( $label );
            if ( $required ) {
                $html .= ' <span class="wccrm-required">*</span>';
            }
            $html .= '</label>';
        }
        
        // Field input
        switch ( $type ) {
            case 'email':
                $html .= '<input type="email" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $field_class ) . '"';
                if ( $placeholder ) $html .= ' placeholder="' . esc_attr( $placeholder ) . '"';
                if ( $required ) $html .= ' required';
                $html .= ' />';
                break;
                
            case 'tel':
                $html .= '<input type="tel" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $field_class ) . '"';
                if ( $placeholder ) $html .= ' placeholder="' . esc_attr( $placeholder ) . '"';
                if ( $required ) $html .= ' required';
                $html .= ' />';
                break;
                
            case 'textarea':
                $rows = (int) ( $field['rows'] ?? 4 );
                $html .= '<textarea id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $field_class ) . '" rows="' . $rows . '"';
                if ( $placeholder ) $html .= ' placeholder="' . esc_attr( $placeholder ) . '"';
                if ( $required ) $html .= ' required';
                $html .= '></textarea>';
                break;
                
            case 'select':
                $options = $field['options'] ?? [];
                $html .= '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $field_class ) . '"';
                if ( $required ) $html .= ' required';
                $html .= '>';
                
                if ( $placeholder ) {
                    $html .= '<option value="">' . esc_html( $placeholder ) . '</option>';
                }
                
                foreach ( $options as $option ) {
                    $value = $option['value'] ?? '';
                    $label = $option['label'] ?? $value;
                    $html .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
                }
                $html .= '</select>';
                break;
                
            case 'hidden':
                $value = $field['value'] ?? '';
                $html .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
                break;
                
            default: // 'text'
                $html .= '<input type="text" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $field_class ) . '"';
                if ( $placeholder ) $html .= ' placeholder="' . esc_attr( $placeholder ) . '"';
                if ( $required ) $html .= ' required';
                $html .= ' />';
                break;
        }
        
        $html .= '</div>';
        
        return $html;
    }

    protected function get_form_styles(): string {
        return '
        <style>
        .wccrm-form { max-width: 600px; margin: 20px 0; }
        .wccrm-field-group { margin-bottom: 15px; }
        .wccrm-field-label { display: block; margin-bottom: 5px; font-weight: bold; }
        .wccrm-required { color: red; }
        .wccrm-field { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .wccrm-field:focus { outline: none; border-color: #0073aa; box-shadow: 0 0 0 2px rgba(0,115,170,0.1); }
        .wccrm-submit-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .wccrm-submit-btn:hover { background: #005a87; }
        .wccrm-error { color: red; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .wccrm-success { color: green; background: #e8f5e8; padding: 10px; border-radius: 4px; margin: 10px 0; }
        </style>';
    }

    protected function get_form_scripts(): string {
        return '
        <script>
        (function() {
            document.addEventListener("DOMContentLoaded", function() {
                const forms = document.querySelectorAll(".wccrm-form");
                forms.forEach(function(form) {
                    form.addEventListener("submit", function(e) {
                        e.preventDefault();
                        
                        const formData = new FormData(form);
                        formData.append("action", "wccrm_form_submit");
                        
                        fetch(ajaxurl || "/wp-admin/admin-ajax.php", {
                            method: "POST",
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                form.innerHTML = "<div class=\"wccrm-success\">" + (data.data.message || "Thank you for your submission!") + "</div>";
                            } else {
                                alert("Error: " + (data.data.message || "Submission failed"));
                            }
                        })
                        .catch(error => {
                            alert("Error: " + error.message);
                        });
                    });
                });
            });
        })();
        </script>';
    }
}