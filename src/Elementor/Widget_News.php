<?php

namespace Anas\WCCRM\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * KSCRM News Elementor Widget
 * Wraps the [kscrm_news] shortcode for consistency
 */
class Widget_News extends Widget_Base {

    public function get_name(): string {
        return 'kscrm_news';
    }

    public function get_title(): string {
        return __( 'KSCRM News', 'woocommerce-crm' );
    }

    public function get_icon(): string {
        return 'eicon-posts-grid';
    }

    public function get_categories(): array {
        return [ 'wccrm' ];
    }

    public function get_keywords(): array {
        return [ 'news', 'articles', 'kscrm', 'crm' ];
    }

    protected function register_controls(): void {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'News Settings', 'woocommerce-crm' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'limit',
            [
                'label'   => __( 'Number of Articles', 'woocommerce-crm' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 5,
                'min'     => 1,
                'max'     => 50,
                'step'    => 1,
            ]
        );

        $this->add_control(
            'layout',
            [
                'label'   => __( 'Layout', 'woocommerce-crm' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'list',
                'options' => [
                    'list'  => __( 'List', 'woocommerce-crm' ),
                    'cards' => __( 'Cards', 'woocommerce-crm' ),
                ],
            ]
        );

        $this->add_control(
            'show_date',
            [
                'label'        => __( 'Show Date', 'woocommerce-crm' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'woocommerce-crm' ),
                'label_off'    => __( 'Hide', 'woocommerce-crm' ),
                'return_value' => 'true',
                'default'      => 'true',
            ]
        );

        $this->add_control(
            'show_source',
            [
                'label'        => __( 'Show Source', 'woocommerce-crm' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'woocommerce-crm' ),
                'label_off'    => __( 'Hide', 'woocommerce-crm' ),
                'return_value' => 'true',
                'default'      => 'true',
            ]
        );

        $this->add_control(
            'show_excerpt',
            [
                'label'        => __( 'Show Excerpt', 'woocommerce-crm' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'woocommerce-crm' ),
                'label_off'    => __( 'Hide', 'woocommerce-crm' ),
                'return_value' => 'true',
                'default'      => 'true',
            ]
        );

        $this->add_control(
            'excerpt_length',
            [
                'label'     => __( 'Excerpt Length', 'woocommerce-crm' ),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 150,
                'min'       => 50,
                'max'       => 500,
                'step'      => 10,
                'condition' => [
                    'show_excerpt' => 'true',
                ],
            ]
        );

        $this->add_control(
            'cache_duration',
            [
                'label'       => __( 'Cache Duration (seconds)', 'woocommerce-crm' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 3600,
                'min'         => 300,
                'max'         => 86400,
                'step'        => 300,
                'description' => __( 'How long to cache news articles (300 seconds minimum)', 'woocommerce-crm' ),
            ]
        );

        $this->end_controls_section();

        // Style controls
        $this->start_controls_section(
            'style_section',
            [
                'label' => __( 'Style', 'woocommerce-crm' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => __( 'Title Color', 'woocommerce-crm' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kscrm-news-title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'excerpt_color',
            [
                'label'     => __( 'Excerpt Color', 'woocommerce-crm' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kscrm-news-excerpt' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'meta_color',
            [
                'label'     => __( 'Meta Color', 'woocommerce-crm' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kscrm-news-meta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'spacing',
            [
                'label'      => __( 'Item Spacing', 'woocommerce-crm' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .kscrm-news-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        // Build shortcode attributes
        $atts = [
            'limit'          => intval( $settings['limit'] ),
            'layout'         => sanitize_text_field( $settings['layout'] ),
            'show_date'      => $settings['show_date'] === 'true' ? 'true' : 'false',
            'show_source'    => $settings['show_source'] === 'true' ? 'true' : 'false',
            'show_excerpt'   => $settings['show_excerpt'] === 'true' ? 'true' : 'false',
            'excerpt_length' => intval( $settings['excerpt_length'] ),
            'cache_duration' => intval( $settings['cache_duration'] ),
        ];

        // Build shortcode string
        $shortcode = '[kscrm_news';
        foreach ( $atts as $key => $value ) {
            $shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
        }
        $shortcode .= ']';

        // Render via shortcode for consistency
        echo do_shortcode( $shortcode );
    }

    protected function content_template(): void {
        ?>
        <#
        var limit = settings.limit || 5;
        var layout = settings.layout || 'list';
        var showDate = settings.show_date === 'true';
        var showSource = settings.show_source === 'true';
        var showExcerpt = settings.show_excerpt === 'true';
        #>
        
        <div class="kscrm-news kscrm-news-{{ layout }}">
            <div class="kscrm-news-preview" style="padding: 20px; text-align: center; border: 2px dashed #0073aa; background: #f0f8ff;">
                <h4 style="margin: 0 0 10px 0; color: #0073aa;">
                    <?php echo esc_js( __( 'KSCRM News Widget', 'woocommerce-crm' ) ); ?>
                </h4>
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <?php echo esc_js( __( 'Showing {{ limit }} articles in {{ layout }} layout.', 'woocommerce-crm' ) ); ?>
                </p>
                <# if ( showExcerpt ) { #>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 12px;">
                    <?php echo esc_js( __( 'With excerpts, dates, and source information.', 'woocommerce-crm' ) ); ?>
                </p>
                <# } #>
                <p style="margin: 10px 0 0 0; color: #999; font-size: 12px;">
                    <?php echo esc_js( __( 'News will be rendered on the frontend.', 'woocommerce-crm' ) ); ?>
                </p>
            </div>
        </div>
        <?php
    }
}