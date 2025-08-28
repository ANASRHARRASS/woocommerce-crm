<?php

namespace KS_CRM\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor integration loader for KSCRM
 * Wraps existing Elementor functionality for namespace consistency
 */
class Loader {

    /**
     * Initialize Elementor integration if Elementor is available
     */
    public static function init(): void {
        if ( did_action( 'elementor/loaded' ) ) {
            self::load_integration();
        } else {
            add_action( 'elementor/loaded', [ __CLASS__, 'load_integration' ] );
        }
    }

    /**
     * Load the actual Elementor integration
     */
    public static function load_integration(): void {
        try {
            // Check if the main WCCRM Elementor loader exists
            if ( class_exists( '\Anas\WCCRM\Elementor\ElementorLoader' ) ) {
                // Get the form repository from the main plugin
                $plugin = function_exists( 'wccrm_get_plugin' ) ? wccrm_get_plugin() : null;
                
                if ( $plugin && method_exists( $plugin, 'get_form_repository' ) ) {
                    $formRepository = $plugin->get_form_repository();
                    new \Anas\WCCRM\Elementor\ElementorLoader( $formRepository );
                } else {
                    // Fallback: Load without form repository if not available
                    error_log( 'KSCRM Elementor: Form repository not available, loading without forms' );
                    self::load_minimal_integration();
                }
            } else {
                error_log( 'KSCRM Elementor: Main ElementorLoader class not found' );
            }
        } catch ( \Exception $e ) {
            error_log( 'KSCRM Elementor: Failed to load integration - ' . $e->getMessage() );
        }
    }

    /**
     * Load minimal integration (News widget only) if form repository is not available
     */
    protected static function load_minimal_integration(): void {
        add_action( 'elementor/widgets/register', [ __CLASS__, 'register_news_widget' ] );
        add_action( 'elementor/elements/categories_registered', [ __CLASS__, 'register_category' ] );
    }

    /**
     * Register KSCRM category for Elementor widgets
     */
    public static function register_category( $elements_manager ): void {
        $elements_manager->add_category( 'kscrm', [
            'title' => __( 'KSCRM', 'woocommerce-crm' ),
            'icon'  => 'fa fa-newspaper-o',
        ] );
    }

    /**
     * Register News widget only (minimal integration)
     */
    public static function register_news_widget( $widgets_manager ): void {
        require_once __DIR__ . '/../Elementor/Widget_News.php';
        $widgets_manager->register( new \Anas\WCCRM\Elementor\Widget_News() );
    }
}