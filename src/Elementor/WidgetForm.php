<?php

namespace Anas\WCCRM\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Anas\WCCRM\Forms\FormRepository;
use Anas\WCCRM\Forms\FormRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor form widget
 */
class WidgetForm extends Widget_Base {

    private FormRepository $formRepository;

    public function __construct( FormRepository $formRepository, $data = [], $args = null ) {
        $this->formRepository = $formRepository;
        parent::__construct( $data, $args );
    }

    public function get_name(): string {
        return 'wccrm_form';
    }

    public function get_title(): string {
        return __( 'WCCRM Form', 'woocommerce-crm' );
    }

    public function get_icon(): string {
        return 'eicon-form-horizontal';
    }

    public function get_categories(): array {
        return [ 'wccrm' ];
    }

    public function get_keywords(): array {
        return [ 'form', 'contact', 'lead', 'wccrm' ];
    }

    protected function register_controls(): void {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Form Settings', 'woocommerce-crm' ),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'form_key',
            [
                'label' => __( 'Select Form', 'woocommerce-crm' ),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_form_options(),
                'default' => '',
                'description' => __( 'Choose which form to display. Forms are managed in the WCCRM settings.', 'woocommerce-crm' ),
            ]
        );

        $this->add_control(
            'refresh_forms',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<div style="text-align: center; margin: 10px 0;">
                    <button type="button" onclick="this.closest(\'.elementor-control\').querySelector(\'select\').dispatchEvent(new Event(\'change\'));" 
                            style="padding: 5px 15px; background: #0073aa; color: white; border: none; border-radius: 3px; cursor: pointer;">
                        ' . __( 'Refresh Form List', 'woocommerce-crm' ) . '
                    </button>
                </div>',
                'content_classes' => 'wccrm-refresh-forms',
            ]
        );

        $this->end_controls_section();

        // Style controls
        $this->start_controls_section(
            'style_section',
            [
                'label' => __( 'Form Style', 'woocommerce-crm' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'form_max_width',
            [
                'label' => __( 'Max Width', 'woocommerce-crm' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [
                        'min' => 300,
                        'max' => 1200,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 100,
                ],
                'selectors' => [
                    '{{WRAPPER}} .wccrm-form' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'field_spacing',
            [
                'label' => __( 'Field Spacing', 'woocommerce-crm' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 5,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 15,
                ],
                'selectors' => [
                    '{{WRAPPER}} .wccrm-field-group' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $form_key = $settings['form_key'] ?? '';

        if ( empty( $form_key ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<div class="wccrm-elementor-placeholder" style="padding: 20px; text-align: center; border: 2px dashed #ccc; background: #f9f9f9;">';
                echo '<p style="margin: 0; color: #666;">' . esc_html__( 'Please select a form to display.', 'woocommerce-crm' ) . '</p>';
                echo '<p style="margin: 5px 0 0 0; font-size: 12px; color: #999;">' . esc_html__( 'This message is only visible in the editor.', 'woocommerce-crm' ) . '</p>';
                echo '</div>';
            }
            return;
        }

        $form = $this->formRepository->load_by_key( $form_key );
        if ( ! $form ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<div class="wccrm-elementor-error" style="padding: 20px; text-align: center; border: 2px dashed #d32f2f; background: #ffebee; color: #d32f2f;">';
                echo '<p style="margin: 0;">' . esc_html__( 'Form not found. Please select a valid form.', 'woocommerce-crm' ) . '</p>';
                echo '</div>';
            }
            return;
        }

        $renderer = new FormRenderer();
        echo $renderer->render( $form );
    }

    protected function content_template(): void {
        ?>
        <#
        if ( settings.form_key ) {
            #>
            <div class="wccrm-elementor-preview" style="padding: 20px; text-align: center; border: 2px dashed #0073aa; background: #f0f8ff;">
                <p style="margin: 0; color: #0073aa;">
                    <?php echo esc_js( __( 'WCCRM Form: "{{ settings.form_key }}"', 'woocommerce-crm' ) ); ?>
                </p>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
                    <?php echo esc_js( __( 'Form will be rendered on the frontend.', 'woocommerce-crm' ) ); ?>
                </p>
            </div>
            <#
        } else {
            #>
            <div class="wccrm-elementor-placeholder" style="padding: 20px; text-align: center; border: 2px dashed #ccc; background: #f9f9f9;">
                <p style="margin: 0; color: #666;">
                    <?php echo esc_js( __( 'Please select a form to display.', 'woocommerce-crm' ) ); ?>
                </p>
            </div>
            <#
        }
        #>
        <?php
    }

    private function get_form_options(): array {
        try {
            $forms = $this->formRepository->list_active();
            $options = [ '' => __( 'Select a form...', 'woocommerce-crm' ) ];

            foreach ( $forms as $form ) {
                $options[ $form->form_key ] = $form->name;
            }

            return $options;
        } catch ( \Exception $e ) {
            error_log( 'WCCRM Elementor: Error loading forms - ' . $e->getMessage() );
            return [ '' => __( 'No forms available', 'woocommerce-crm' ) ];
        }
    }
}