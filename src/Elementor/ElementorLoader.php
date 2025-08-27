<?php

namespace Anas\WCCRM\Elementor;

use Anas\WCCRM\Forms\FormRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor integration loader
 */
class ElementorLoader {

    private FormRepository $formRepository;

    public function __construct( FormRepository $formRepository ) {
        $this->formRepository = $formRepository;
        $this->init();
    }

    private function init(): void {
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
    }

    public function register_category( $elements_manager ): void {
        $elements_manager->add_category( 'wccrm', [
            'title' => __( 'WCCRM', 'woocommerce-crm' ),
            'icon' => 'fa fa-plug',
        ] );
    }

    public function register_widgets( $widgets_manager ): void {
        require_once __DIR__ . '/WidgetForm.php';
        $widgets_manager->register( new WidgetForm( $this->formRepository ) );
    }
}