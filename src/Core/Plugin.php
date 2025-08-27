<?php

namespace Anas\WCCRM\Core;

use Anas\WCCRM\Database\Installer;
use Anas\WCCRM\Forms\FormRepository;
use Anas\WCCRM\Forms\FormRenderer;
use Anas\WCCRM\Forms\SubmissionHandler;
use Anas\WCCRM\Forms\FormSubmissionLinker;
use Anas\WCCRM\Forms\Versioning\FormVersioningService;
use Anas\WCCRM\Forms\Consent\FormConsentRecorder;
use Anas\WCCRM\Contacts\ContactRepository;
use Anas\WCCRM\Contacts\InterestUpdater;
use Anas\WCCRM\Security\CredentialResolver;
use Anas\WCCRM\Security\AuditLogger;
use Anas\WCCRM\Security\DataRetentionService;
use Anas\WCCRM\Security\ErasureService;
use Anas\WCCRM\Shipping\CarrierRegistry;
use Anas\WCCRM\Shipping\RateService;
use Anas\WCCRM\Shipping\Carriers\ExampleCarrier;
use Anas\WCCRM\News\ProviderRegistry;
use Anas\WCCRM\News\Aggregator;
use Anas\WCCRM\News\RateLimiter;
use Anas\WCCRM\News\Providers\NewsApiProvider;
use Anas\WCCRM\News\Providers\GNewsProvider;
use Anas\WCCRM\News\Providers\GenericRssProvider;
use Anas\WCCRM\Orders\Phase2A\OrderSyncService;
use Anas\WCCRM\Orders\Phase2A\OrderMetricsUpdater;
use Anas\WCCRM\Orders\Backfill\OrderBackfillManager;
use Anas\WCCRM\Messaging\Templates\TemplateRepository;
use Anas\WCCRM\Messaging\Consent\MessagingConsentManager;
use Anas\WCCRM\Messaging\Dispatch\MessageDispatcher;
use Anas\WCCRM\Messaging\MessagingManager;
use Anas\WCCRM\Social\Inbound\SocialWebhookController;
use Anas\WCCRM\Social\Leads\SocialLeadNormalizer;
use Anas\WCCRM\Social\Leads\SocialLeadRepository;
use Anas\WCCRM\COD\CodVerificationService;
use Anas\WCCRM\COD\CodWorkflowService;
use Anas\WCCRM\Automation\AutomationRepository;
use Anas\WCCRM\Automation\Conditions\ConditionEvaluator;
use Anas\WCCRM\Automation\Executor\ActionExecutor;
use Anas\WCCRM\Automation\AutomationRunner;
use Anas\WCCRM\Reporting\MetricsAggregator;
use Anas\WCCRM\Reporting\DashboardController;

defined( 'ABSPATH' ) || exit;

/**
 * Main Plugin service container and bootstrap
 * Enhanced with Phase 2 architectural skeleton
 */
class Plugin {

    private static ?Plugin $instance = null;
    
    // Core services
    protected FormRepository $formRepository;
    protected ContactRepository $contactRepository;
    protected InterestUpdater $interestUpdater;
    protected CredentialResolver $credentialResolver;
    protected CarrierRegistry $carrierRegistry;
    protected RateService $rateService;
    
    // News services
    protected ProviderRegistry $providerRegistry;
    protected RateLimiter $rateLimiter;
    protected Aggregator $newsAggregator;
    
    // Phase 2A - Orders
    protected OrderSyncService $orderSyncService;
    protected OrderMetricsUpdater $orderMetricsUpdater;
    protected OrderBackfillManager $orderBackfillManager;
    
    // Phase 2B - Forms/Consent
    protected FormVersioningService $formVersioningService;
    protected FormConsentRecorder $formConsentRecorder;
    
    // Phase 2C - Messaging
    protected TemplateRepository $templateRepository;
    protected MessagingConsentManager $messagingConsentManager;
    protected MessageDispatcher $messageDispatcher;
    protected MessagingManager $messagingManager;
    
    // Phase 2D - Social Leads
    protected SocialWebhookController $socialWebhookController;
    protected SocialLeadNormalizer $socialLeadNormalizer;
    protected SocialLeadRepository $socialLeadRepository;
    
    // Phase 2E - COD Workflow
    protected CodVerificationService $codVerificationService;
    protected CodWorkflowService $codWorkflowService;
    
    // Phase 2F - Automation
    protected AutomationRepository $automationRepository;
    protected ConditionEvaluator $conditionEvaluator;
    protected ActionExecutor $actionExecutor;
    protected AutomationRunner $automationRunner;
    
    // Phase 2G - Security/Compliance
    protected AuditLogger $auditLogger;
    protected DataRetentionService $dataRetentionService;
    protected ErasureService $erasureService;
    
    // Phase 2H - Reporting
    protected MetricsAggregator $metricsAggregator;
    protected DashboardController $dashboardController;

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
        
        // Shipping services
        $this->carrierRegistry = new CarrierRegistry();
        $this->rateService = new RateService( $this->carrierRegistry );
        
        // Register example carrier
        $this->carrierRegistry->register( 'example', new ExampleCarrier() );
        
        // News services with enhanced rate limiting
        $this->providerRegistry = new ProviderRegistry();
        $this->rateLimiter = new RateLimiter();
        $this->newsAggregator = new Aggregator( $this->providerRegistry, $this->rateLimiter );
        
        // Register news providers with proper credential keys
        $this->providerRegistry->register( 'newsapi', new NewsApiProvider( $this->credentialResolver ) );
        $this->providerRegistry->register( 'gnews', new GNewsProvider( $this->credentialResolver ) );
        $this->providerRegistry->register( 'generic_rss', new GenericRssProvider( $this->credentialResolver ) );
        
        // Phase 2A - Orders
        $this->orderSyncService = new OrderSyncService();
        $this->orderMetricsUpdater = new OrderMetricsUpdater();
        $this->orderBackfillManager = new OrderBackfillManager();
        
        // Phase 2B - Forms/Consent
        $this->formVersioningService = new FormVersioningService();
        $this->formConsentRecorder = new FormConsentRecorder();
        
        // Phase 2C - Messaging
        $this->templateRepository = new TemplateRepository();
        $this->messagingConsentManager = new MessagingConsentManager();
        $this->messageDispatcher = new MessageDispatcher();
        $this->messagingManager = new MessagingManager(
            $this->templateRepository,
            $this->messagingConsentManager,
            $this->messageDispatcher
        );
        
        // Phase 2D - Social Leads
        $this->socialWebhookController = new SocialWebhookController();
        $this->socialLeadNormalizer = new SocialLeadNormalizer();
        $this->socialLeadRepository = new SocialLeadRepository();
        
        // Phase 2E - COD Workflow
        $this->codVerificationService = new CodVerificationService();
        $this->codWorkflowService = new CodWorkflowService();
        
        // Phase 2F - Automation
        $this->automationRepository = new AutomationRepository();
        $this->conditionEvaluator = new ConditionEvaluator();
        $this->actionExecutor = new ActionExecutor();
        $this->automationRunner = new AutomationRunner(
            $this->automationRepository,
            $this->conditionEvaluator,
            $this->actionExecutor
        );
        
        // Phase 2G - Security/Compliance
        $this->auditLogger = new AuditLogger();
        $this->dataRetentionService = new DataRetentionService();
        $this->erasureService = new ErasureService();
        
        // Phase 2H - Reporting
        $this->metricsAggregator = new MetricsAggregator();
        $this->dashboardController = new DashboardController();
        
        // Hook up form submission processing
        add_action( 'wccrm_form_submitted', [ $submissionLinker, 'process_submission' ], 10, 2 );
    }

    protected function register_hooks(): void {
        add_action( 'init', [ $this, 'maybe_upgrade' ] );
        add_action( 'wp_ajax_wccrm_form_submit', [ $this, 'handle_form_submission' ] );
        add_action( 'wp_ajax_nopriv_wccrm_form_submit', [ $this, 'handle_form_submission' ] );
        
        // Debug action for news aggregator
        add_action( 'wccrm_debug_fetch_news', [ $this, 'debug_fetch_news' ] );
        
        // Phase 2 hooks - TODO: Implement actual hook handlers
        // WooCommerce order events
        add_action( 'woocommerce_new_order', [ $this, 'handle_new_order' ], 10, 1 );
        add_action( 'woocommerce_order_status_changed', [ $this, 'handle_order_status_change' ], 10, 4 );
        
        // Social webhook registration
        add_action( 'init', [ $this->socialWebhookController, 'register_endpoints' ] );
        
        // Automation triggers
        add_action( 'wccrm_automation_trigger', [ $this, 'handle_automation_trigger' ], 10, 2 );
        
        // Scheduled tasks - TODO: Implement cron jobs
        add_action( 'wccrm_process_message_queue', [ $this->messageDispatcher, 'process_queue' ] );
        add_action( 'wccrm_data_retention_cleanup', [ $this->dataRetentionService, 'apply_retention_policies' ] );
        
        // AJAX endpoints for dashboard
        add_action( 'wp_ajax_wccrm_dashboard_data', [ $this, 'handle_dashboard_ajax' ] );
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

    // Phase 2 hook handlers - TODO: Implement full functionality
    
    public function handle_new_order( int $order_id ): void {
        // TODO: Implement new order handling
        // - Trigger order sync
        // - Update metrics
        // - Process automation triggers
    }

    public function handle_order_status_change( int $order_id, string $old_status, string $new_status, \WC_Order $order ): void {
        // TODO: Implement order status change handling
        // - Update order sync status
        // - Trigger COD verification if needed
        // - Process automation triggers
    }

    public function handle_automation_trigger( string $trigger_event, array $event_data ): void {
        // TODO: Implement automation trigger handling
        $this->automationRunner->process_trigger( $trigger_event, $event_data );
    }

    public function handle_dashboard_ajax(): void {
        // TODO: Implement dashboard AJAX handler
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        
        $data = $this->dashboardController->get_dashboard_data();
        wp_send_json_success( $data );
    }

    // Enhanced getters for all services
    
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

    // Phase 2A getters
    public function get_order_sync_service(): OrderSyncService {
        return $this->orderSyncService;
    }

    public function get_order_metrics_updater(): OrderMetricsUpdater {
        return $this->orderMetricsUpdater;
    }

    public function get_order_backfill_manager(): OrderBackfillManager {
        return $this->orderBackfillManager;
    }

    // Phase 2B getters
    public function get_form_versioning_service(): FormVersioningService {
        return $this->formVersioningService;
    }

    public function get_form_consent_recorder(): FormConsentRecorder {
        return $this->formConsentRecorder;
    }

    // Phase 2C getters
    public function get_template_repository(): TemplateRepository {
        return $this->templateRepository;
    }

    public function get_messaging_consent_manager(): MessagingConsentManager {
        return $this->messagingConsentManager;
    }

    public function get_message_dispatcher(): MessageDispatcher {
        return $this->messageDispatcher;
    }

    public function get_messaging_manager(): MessagingManager {
        return $this->messagingManager;
    }

    // Phase 2D getters
    public function get_social_webhook_controller(): SocialWebhookController {
        return $this->socialWebhookController;
    }

    public function get_social_lead_normalizer(): SocialLeadNormalizer {
        return $this->socialLeadNormalizer;
    }

    public function get_social_lead_repository(): SocialLeadRepository {
        return $this->socialLeadRepository;
    }

    // Phase 2E getters
    public function get_cod_verification_service(): CodVerificationService {
        return $this->codVerificationService;
    }

    public function get_cod_workflow_service(): CodWorkflowService {
        return $this->codWorkflowService;
    }

    // Phase 2F getters
    public function get_automation_repository(): AutomationRepository {
        return $this->automationRepository;
    }

    public function get_automation_runner(): AutomationRunner {
        return $this->automationRunner;
    }

    // Phase 2G getters
    public function get_audit_logger(): AuditLogger {
        return $this->auditLogger;
    }

    public function get_data_retention_service(): DataRetentionService {
        return $this->dataRetentionService;
    }

    public function get_erasure_service(): ErasureService {
        return $this->erasureService;
    }

    // Phase 2H getters
    public function get_metrics_aggregator(): MetricsAggregator {
        return $this->metricsAggregator;
    }

    public function get_dashboard_controller(): DashboardController {
        return $this->dashboardController;
    }
}