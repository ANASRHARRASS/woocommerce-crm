<?php
/**
 * Main Plugin bootstrap for WooCommerce CRM core functionality
 */

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

// Phase 2 services (skeletons only)
use Anas\WCCRM\Orders\OrderSyncService;
use Anas\WCCRM\Orders\OrderMetricsUpdater;
use Anas\WCCRM\Orders\Backfill\OrderBackfillManager;
use Anas\WCCRM\Forms\Versioning\FormVersioningService;
use Anas\WCCRM\Forms\Consent\FormConsentRecorder;
use Anas\WCCRM\Messaging\MessagingManager;
use Anas\WCCRM\Messaging\Dispatch\MessageDispatcher;
use Anas\WCCRM\Messaging\Templates\TemplateRepository;
use Anas\WCCRM\Messaging\Consent\MessagingConsentManager;
use Anas\WCCRM\Social\Inbound\SocialWebhookController;
use Anas\WCCRM\Social\Leads\SocialLeadNormalizer;
use Anas\WCCRM\Social\Leads\SocialLeadRepository;
use Anas\WCCRM\COD\CodWorkflowService;
use Anas\WCCRM\COD\CodVerificationService;
use Anas\WCCRM\Automation\AutomationRunner;
use Anas\WCCRM\Automation\AutomationRepository;
use Anas\WCCRM\Automation\Executor\ActionExecutor;
use Anas\WCCRM\Automation\Conditions\ConditionEvaluator;
use Anas\WCCRM\Security\AuditLogger;
use Anas\WCCRM\Security\DataRetentionService;
use Anas\WCCRM\Security\ErasureService;
use Anas\WCCRM\Reporting\MetricsAggregator;
use Anas\WCCRM\Reporting\DashboardController;

defined('ABSPATH') || exit;

class Plugin {
    private static ?Plugin $instance = null;

    // Core
    protected CredentialResolver $credentialResolver;
    protected FormRepository $formRepository;
    protected ContactRepository $contactRepository;
    protected InterestUpdater $interestUpdater;
    protected CarrierRegistry $carrierRegistry;
    protected RateService $rateService;

    // Phase 2 services
    protected OrderSyncService $orderSyncService;
    protected OrderMetricsUpdater $orderMetricsUpdater;
    protected OrderBackfillManager $orderBackfillManager;
    protected FormVersioningService $formVersioningService;
    protected FormConsentRecorder $formConsentRecorder;
    protected MessagingManager $messagingManager;
    protected MessageDispatcher $messageDispatcher;
    protected TemplateRepository $templateRepository;
    protected MessagingConsentManager $messagingConsentManager;
    protected SocialWebhookController $socialWebhookController;
    protected SocialLeadNormalizer $socialLeadNormalizer;
    protected SocialLeadRepository $socialLeadRepository;
    protected CodWorkflowService $codWorkflowService;
    protected CodVerificationService $codVerificationService;
    protected AutomationRunner $automationRunner;
    protected AutomationRepository $automationRepository;
    protected ConditionEvaluator $conditionEvaluator;
    protected ActionExecutor $actionExecutor;
    protected AuditLogger $auditLogger;
    protected DataRetentionService $dataRetentionService;
    protected ErasureService $erasureService;
    protected MetricsAggregator $metricsAggregator;
    protected DashboardController $dashboardController;

    public static function instance(): Plugin { return self::$instance ??= new self(); }
    private function __construct() {}

    public function init(): void {
        $this->init_services();
        $this->register_core_hooks();
        $this->register_phase_hooks();
        $this->register_shortcodes();
        $this->init_elementor();
        $this->init_shipping();
        do_action('wccrm_after_init', $this);
    }

    protected function init_services(): void {
        // Core services
        $this->credentialResolver = new CredentialResolver();
        $this->formRepository     = new FormRepository();
        $this->contactRepository  = new ContactRepository();
        $this->interestUpdater    = new InterestUpdater();

        // Orders (Phase 2A #8)
        $this->orderMetricsUpdater  = new OrderMetricsUpdater($this->contactRepository); // TODO implement metrics logic
        $this->orderSyncService     = new OrderSyncService($this->orderMetricsUpdater, $this->contactRepository);
        $this->orderBackfillManager = new OrderBackfillManager($this->orderSyncService, $this->contactRepository);

        // Forms (Phase 2B #9)
        $this->formVersioningService = new FormVersioningService($this->formRepository); // TODO implement version snapshots
        $this->formConsentRecorder   = new FormConsentRecorder(); // TODO implement consent persistence

        // Messaging (Phase 2C #10)
        $this->templateRepository      = new TemplateRepository(); // TODO DB storage of templates
        $this->messagingConsentManager = new MessagingConsentManager(); // TODO consent logic
        $this->messageDispatcher       = new MessageDispatcher($this->templateRepository, $this->messagingConsentManager);
        $this->messagingManager        = new MessagingManager($this->messageDispatcher);

        // Social Leads (Phase 2D #11)
        $this->socialLeadRepository    = new SocialLeadRepository(); // TODO persistence layer
        $this->socialLeadNormalizer    = new SocialLeadNormalizer();
        $this->socialWebhookController = new SocialWebhookController(
            $this->socialLeadNormalizer,
            $this->socialLeadRepository,
            $this->contactRepository
        );

        // COD Workflow (Phase 2E #12)
        $this->codVerificationService = new CodVerificationService(); // TODO token storage & validation
        $this->codWorkflowService     = new CodWorkflowService($this->codVerificationService);

        // Automation (Phase 2F #13)
        $this->automationRepository = new AutomationRepository(); // TODO rules storage
        $this->conditionEvaluator   = new ConditionEvaluator();
        $this->actionExecutor       = new ActionExecutor(
            $this->contactRepository,
            $this->messageDispatcher,
            $this->orderMetricsUpdater
        );
        $this->automationRunner     = new AutomationRunner(
            $this->automationRepository,
            $this->conditionEvaluator,
            $this->actionExecutor
        );

        // Security & Compliance (Phase 2G #14)
        $this->auditLogger          = new AuditLogger(); // TODO audit log table
        $this->dataRetentionService = new DataRetentionService(); // TODO retention policy engine
        $this->erasureService       = new ErasureService($this->contactRepository, $this->auditLogger); // TODO anonymization logic

        // Reporting (Phase 2H #15)
        $this->metricsAggregator   = new MetricsAggregator(); // TODO aggregation queries
        $this->dashboardController = new DashboardController($this->metricsAggregator);

        // Shipping (existing functionality preserved)
        $this->carrierRegistry = new CarrierRegistry();
        $this->rateService     = new RateService($this->carrierRegistry);
        $this->carrierRegistry->register('example', new ExampleCarrier());

        // Form submission linking
        $submissionLinker = new FormSubmissionLinker($this->contactRepository, $this->interestUpdater);
        add_action('wccrm_form_submitted', [$submissionLinker, 'process_submission'], 10, 2);

        do_action('wccrm_services_initialized', $this);
    }

    protected function register_core_hooks(): void {
        add_action('init', [$this, 'maybe_upgrade']);
        add_action('wp_ajax_wccrm_form_submit', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_wccrm_form_submit', [$this, 'handle_form_submission']);
    }

    protected function register_phase_hooks(): void {
        // Orders hooks
        add_action('woocommerce_new_order', [$this->orderSyncService, 'handle_new_order']);
        add_action('woocommerce_order_status_changed', [$this->orderSyncService, 'handle_status_change'], 10, 4);
        add_action('wccrm_orders_backfill_batch', [$this->orderBackfillManager, 'process_next_batch']);

        // Messaging queue cron placeholder
        add_action('wccrm_dispatch_outbound_queue', [$this->messageDispatcher, 'process_queue']);

        // Social webhooks REST
        add_action('rest_api_init', [$this->socialWebhookController, 'register_routes']);

        // COD token expiry cron
        add_action('wccrm_cod_verification_expiry_check', [$this->codWorkflowService, 'expire_pending_tokens']);

        // Automation triggers
        add_action('wccrm_contact_updated', [$this->automationRunner, 'on_generic_event'], 10, 2);
        add_action('wccrm_order_event', [$this->automationRunner, 'on_generic_event'], 10, 2);

        // Data retention cron
        add_action('wccrm_data_retention_cycle', [$this->dataRetentionService, 'run_cycle']);

        // Reporting metrics AJAX (admin)
        add_action('wp_ajax_wccrm_fetch_metrics', [$this->dashboardController, 'ajax_fetch_metrics']);
    }

    protected function register_shortcodes(): void { add_shortcode('wccrm_form', [$this, 'render_form_shortcode']); }

    protected function init_elementor(): void {
        if (did_action('elementor/loaded')) {
            require_once WCCRM_PLUGIN_DIR . 'src/Elementor/ElementorLoader.php';
            new \Anas\WCCRM\Elementor\ElementorLoader($this->formRepository);
        }
    }

    protected function init_shipping(): void { add_filter('woocommerce_shipping_methods', [$this, 'register_shipping_method']); }

    public function maybe_upgrade(): void { (new Installer())->maybe_upgrade(); }

    public function render_form_shortcode(array $atts): string {
        $atts = shortcode_atts(['key' => ''], $atts);
        if (empty($atts['key'])) {
            return '<div class="wccrm-error">Form key is required.</div>';
        }
        $form = $this->formRepository->load_by_key($atts['key']);
        if (!$form) {
            return '<div class="wccrm-error">Form not found.</div>';
        }
        $renderer = new FormRenderer();
        return $renderer->render($form);
    }

    public function handle_form_submission(): void {
        $formKey = $_POST['__wccrm_form_key'] ?? '';
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'wccrm_form_' . $formKey)) {
            wp_die('Security check failed');
        }
        $submissionHandler = new SubmissionHandler($this->formRepository);
        $result            = $submissionHandler->handle_submission($_POST);
        if ($result['success']) {
            wp_send_json_success($result);
        }
        wp_send_json_error($result);
    }

    public function register_shipping_method(array $methods): array {
        require_once WCCRM_PLUGIN_DIR . 'src/Shipping/WCCRM_Shipping_Method.php';
        $methods['wccrm_shipping'] = 'WCCRM_Shipping_Method';
        return $methods;
    }

    // Getters (extend as required later)
    public function get_order_sync_service(): OrderSyncService { return $this->orderSyncService; }
    public function get_message_dispatcher(): MessageDispatcher { return $this->messageDispatcher; }
    public function get_automation_runner(): AutomationRunner { return $this->automationRunner; }
}