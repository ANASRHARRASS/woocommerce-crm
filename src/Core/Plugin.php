<?php

namespace Anas\WCCRM\Core;

use Anas\WCCRM\Database\Installer;
use Anas\WCCRM\Forms\FormRepository;
use Anas\WCCRM\Forms\FormRenderer;
use Anas\WCCRM\Forms\SubmissionHandler;
use Anas\WCCRM\Forms\FormSubmissionLinker;
use Anas\WCCRM\Contacts\ContactRepository;
use Anas\WCCRM\Contacts\InterestUpdater;
use Anas\WCCRM\Security\CredentialResolver;
use Anas\WCCRM\Shipping\CarrierRegistry;
use Anas\WCCRM\Shipping\RateService;
use Anas\WCCRM\Shipping\Carriers\ExampleCarrier;
use Anas\WCCRM\News\ProviderRegistry;
use Anas\WCCRM\News\Aggregator;
use Anas\WCCRM\News\Providers\NewsApiProvider;
use Anas\WCCRM\News\Providers\GNewsProvider;
use Anas\WCCRM\News\Providers\GenericRssProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Main Plugin service container and bootstrap
 */
class Plugin {

    private static ?Plugin $instance = null;
    
    protected FormRepository $formRepository;
    protected ContactRepository $contactRepository;
    protected InterestUpdater $interestUpdater;
    protected CredentialResolver $credentialResolver;
    protected CarrierRegistry $carrierRegistry;
    protected RateService $rateService;
    protected ProviderRegistry $providerRegistry;
    protected Aggregator $newsAggregator;

    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Prevent direct instantiation
    }

    public function init(): void {
        $this->init_services();
        $this->register_hooks();
        $this->register_shortcodes();
        $this->init_elementor();
        $this->init_shipping();
        
        do_action( 'wccrm_after_init', $this );
    }

    protected function init_services(): void {
        // Core services
        $this->credentialResolver = new CredentialResolver();
        $this->formRepository = new FormRepository();
        $this->contactRepository = new ContactRepository();
        $this->interestUpdater = new InterestUpdater();
        
        // Form services
        $formRenderer = new FormRenderer();
        $submissionHandler = new SubmissionHandler( $this->formRepository );
        $submissionLinker = new FormSubmissionLinker( $this->contactRepository, $this->interestUpdater );
        
        // Order sync services
        if ( class_exists( 'WooCommerce' ) ) {
            new \Anas\WCCRM\Orders\OrderHooks( $this->contactRepository );
        }
        
        // Shipping services
        $this->carrierRegistry = new CarrierRegistry();
        $this->rateService = new RateService( $this->carrierRegistry );
        
        // Register example carrier
        $this->carrierRegistry->register( 'example', new ExampleCarrier() );
        
        // Enhanced shipping services
        $shippingRegistry = new \Anas\WCCRM\Shipping\ShippingCarrierRegistry();
        $shippingQuoteService = new \Anas\WCCRM\Shipping\QuoteService( $shippingRegistry );
        
        // Register sample carrier
        $shippingRegistry->register( 'sample', new \Anas\WCCRM\Shipping\Carriers\SampleCarrier() );
        $shippingRegistry->load_carriers(); // Load carriers via filter
        
        // News services
        $this->providerRegistry = new ProviderRegistry();
        $this->newsAggregator = new Aggregator( $this->providerRegistry );
        
        // Register news providers (stub implementations)
        $this->providerRegistry->register( 'newsapi', new NewsApiProvider( $this->credentialResolver ) );
        $this->providerRegistry->register( 'gnews', new GNewsProvider( $this->credentialResolver ) );
        $this->providerRegistry->register( 'generic_rss', new GenericRssProvider( $this->credentialResolver ) );
        
        // Hook up form submission processing
        add_action( 'wccrm_form_submitted', [ $submissionLinker, 'process_submission' ], 10, 2 );
        
        // Initialize admin if in admin context
        if ( is_admin() ) {
            $this->init_admin( $shippingQuoteService, $shippingRegistry );
        }
    }

    protected function register_hooks(): void {
        add_action( 'init', [ $this, 'maybe_upgrade' ] );
        add_action( 'wp_ajax_wccrm_form_submit', [ $this, 'handle_form_submission' ] );
        add_action( 'wp_ajax_nopriv_wccrm_form_submit', [ $this, 'handle_form_submission' ] );
        
        // Debug action for news aggregator
        add_action( 'wccrm_debug_fetch_news', [ $this, 'debug_fetch_news' ] );
    }

    protected function register_shortcodes(): void {
        add_shortcode( 'wccrm_form', [ $this, 'render_form_shortcode' ] );
    }

    protected function init_elementor(): void {
        if ( did_action( 'elementor/loaded' ) ) {
            require_once WCCRM_PLUGIN_DIR . 'src/Elementor/ElementorLoader.php';
            new \Anas\WCCRM\Elementor\ElementorLoader( $this->formRepository );
        }
    }

    protected function init_shipping(): void {
        add_filter( 'woocommerce_shipping_methods', [ $this, 'register_shipping_method' ] );
    }

    protected function init_admin( $quoteService, $shippingRegistry ): void {
        // Initialize admin pages
        new \Anas\WCCRM\Admin\ToolsPage( $this->contactRepository );
        new \Anas\WCCRM\Admin\ShippingRatesPage( $quoteService, $shippingRegistry );
        
        // Hook into admin menu
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
    }

    public function register_admin_menu(): void {
        // Main menu
        add_menu_page(
            __( 'WooCommerce CRM', 'wccrm' ),
            __( 'WC CRM', 'wccrm' ),
            'manage_options',
            'wccrm',
            [ $this, 'render_dashboard_page' ],
            'dashicons-groups',
            30
        );

        // Submenu pages
        add_submenu_page(
            'wccrm',
            __( 'Maintenance Tools', 'wccrm' ),
            __( 'Tools', 'wccrm' ),
            'manage_options',
            'wccrm-tools',
            [ $this, 'render_tools_page' ]
        );

        add_submenu_page(
            'wccrm',
            __( 'Shipping Rates', 'wccrm' ),
            __( 'Shipping', 'wccrm' ),
            'manage_options',
            'wccrm-shipping-rates',
            [ $this, 'render_shipping_page' ]
        );
    }

    public function render_dashboard_page(): void {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'WooCommerce CRM Dashboard', 'wccrm' ) . '</h1>';
        echo '<p>' . esc_html__( 'Welcome to WooCommerce CRM. Use the menu items to access different features.', 'wccrm' ) . '</p>';
        echo '</div>';
    }

    public function render_tools_page(): void {
        $toolsPage = new \Anas\WCCRM\Admin\ToolsPage( $this->contactRepository );
        $toolsPage->render();
    }

    public function render_shipping_page(): void {
        // Get shipping services - we'll need to access the services initialized in init_services
        static $shippingQuoteService, $shippingRegistry;
        
        if ( ! $shippingQuoteService ) {
            $shippingRegistry = new \Anas\WCCRM\Shipping\ShippingCarrierRegistry();
            $shippingQuoteService = new \Anas\WCCRM\Shipping\QuoteService( $shippingRegistry );
            $shippingRegistry->register( 'sample', new \Anas\WCCRM\Shipping\Carriers\SampleCarrier() );
            $shippingRegistry->load_carriers();
        }
        
        $shippingPage = new \Anas\WCCRM\Admin\ShippingRatesPage( $shippingQuoteService, $shippingRegistry );
        $shippingPage->render();
    }

    public function maybe_upgrade(): void {
        $installer = new Installer();
        $installer->maybe_upgrade();
    }

    public function render_form_shortcode( array $atts ): string {
        $atts = shortcode_atts( [ 'key' => '' ], $atts );
        
        if ( empty( $atts['key'] ) ) {
            return '<div class="wccrm-error">Form key is required.</div>';
        }
        
        $form = $this->formRepository->load_by_key( $atts['key'] );
        if ( ! $form ) {
            return '<div class="wccrm-error">Form not found.</div>';
        }
        
        $renderer = new FormRenderer();
        return $renderer->render( $form );
    }

    public function handle_form_submission(): void {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'wccrm_form_' . ( $_POST['__wccrm_form_key'] ?? '' ) ) ) {
            wp_die( 'Security check failed' );
        }
        
        $submissionHandler = new SubmissionHandler( $this->formRepository );
        $result = $submissionHandler->handle_submission( $_POST );
        
        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    public function register_shipping_method( array $methods ): array {
        require_once WCCRM_PLUGIN_DIR . 'src/Shipping/WCCRM_Shipping_Method.php';
        $methods['wccrm_shipping'] = 'WCCRM_Shipping_Method';
        return $methods;
    }

    public function debug_fetch_news(): void {
        $articles = $this->newsAggregator->fetch( [ 'limit' => 5 ] );
        error_log( 'WCCRM Debug: Fetched ' . count( $articles ) . ' news articles (stub)' );
    }

    // Getters for services
    public function get_form_repository(): FormRepository {
        return $this->formRepository;
    }

    public function get_contact_repository(): ContactRepository {
        return $this->contactRepository;
    }

    public function get_interest_updater(): InterestUpdater {
        return $this->interestUpdater;
    }

    public function get_credential_resolver(): CredentialResolver {
        return $this->credentialResolver;
    }

    public function get_carrier_registry(): CarrierRegistry {
        return $this->carrierRegistry;
    }

    public function get_rate_service(): RateService {
        return $this->rateService;
    }

    public function get_news_aggregator(): Aggregator {
        return $this->newsAggregator;
    }
}