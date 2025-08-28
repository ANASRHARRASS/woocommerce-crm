<?php
/**
 * Main Plugin bootstrap (Phase 2 skeleton - News module removed per user request for now)
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
use Anas\WCCRM\News\Aggregator;
use Anas\WCCRM\News\ProviderRegistry;

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
    
    // News aggregation
    protected ProviderRegistry $newsProviderRegistry;
    protected Aggregator $newsAggregator;

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

        // News aggregation
        $this->newsProviderRegistry = new ProviderRegistry();
        $this->newsAggregator = new Aggregator($this->newsProviderRegistry);

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
        
        // Enhanced Leads REST endpoint with spam protection
        if (class_exists('\\KS_CRM\\Leads\\Leads_REST')) {
            new \KS_CRM\Leads\Leads_REST();
        }

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

    protected function register_shortcodes(): void { 
        add_shortcode('wccrm_form', [$this, 'render_form_shortcode']); 
        add_shortcode('kscrm_news', [$this, 'render_news_shortcode']); 
    }

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

    public function render_news_shortcode(array $atts): string {
        $atts = shortcode_atts([
            'limit' => 5,
            'layout' => 'list', // 'list' or 'cards'
            'show_date' => true,
            'show_source' => true,
            'show_excerpt' => true,
            'excerpt_length' => 150,
            'cache_duration' => 3600,
        ], $atts);

        $limit = max(1, min(50, intval($atts['limit'])));
        $cache_duration = max(300, intval($atts['cache_duration'])); // Min 5 minutes

        try {
            $articles = $this->newsAggregator->get_cached(['limit' => $limit], $cache_duration);
            
            if (empty($articles)) {
                return '<div class="kscrm-news-empty">No news articles available at the moment.</div>';
            }

            return $this->render_news_articles($articles, $atts);
            
        } catch (\Exception $e) {
            error_log('KSCRM News shortcode error: ' . $e->getMessage());
            return '<div class="kscrm-news-error">Unable to load news at this time.</div>';
        }
    }

    protected function render_news_articles(array $articles, array $atts): string {
        $layout = sanitize_text_field($atts['layout']);
        $show_date = filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN);
        $show_source = filter_var($atts['show_source'], FILTER_VALIDATE_BOOLEAN);
        $show_excerpt = filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN);
        $excerpt_length = max(50, min(500, intval($atts['excerpt_length'])));

        $html = '<div class="kscrm-news kscrm-news-' . esc_attr($layout) . '">';

        foreach ($articles as $article) {
            if (is_array($article)) {
                // Convert cached array back to Article object
                $article = new \Anas\WCCRM\News\DTO\Article($article);
            }

            $html .= '<article class="kscrm-news-item">';
            
            if ($layout === 'cards' && $article->get_image_url()) {
                $html .= '<div class="kscrm-news-image">';
                $html .= '<img src="' . esc_url($article->get_image_url()) . '" alt="' . esc_attr($article->title) . '" loading="lazy">';
                $html .= '</div>';
            }
            
            $html .= '<div class="kscrm-news-content">';
            $html .= '<h3 class="kscrm-news-title">';
            $html .= '<a href="' . esc_url($article->url) . '" target="_blank" rel="noopener">';
            $html .= esc_html($article->title);
            $html .= '</a>';
            $html .= '</h3>';

            if ($show_excerpt && $article->get_excerpt($excerpt_length)) {
                $html .= '<p class="kscrm-news-excerpt">' . esc_html($article->get_excerpt($excerpt_length)) . '</p>';
            }

            $html .= '<div class="kscrm-news-meta">';
            if ($show_source && $article->source) {
                $html .= '<span class="kscrm-news-source">' . esc_html($article->source) . '</span>';
            }
            if ($show_date && $article->published_at) {
                $formatted_date = date('F j, Y', strtotime($article->published_at));
                $html .= '<time class="kscrm-news-date" datetime="' . esc_attr($article->published_at) . '">' . esc_html($formatted_date) . '</time>';
            }
            $html .= '</div>';
            
            $html .= '</div>'; // .kscrm-news-content
            $html .= '</article>';
        }

        $html .= '</div>';

        // Add basic styles if not already present
        $html .= $this->get_news_styles();

        return $html;
    }

    protected function get_news_styles(): string {
        static $styles_added = false;
        
        if ($styles_added) {
            return '';
        }
        
        $styles_added = true;
        
        return '<style>
        .kscrm-news { margin: 20px 0; }
        .kscrm-news-item { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .kscrm-news-item:last-child { border-bottom: none; }
        .kscrm-news-title { margin: 0 0 8px 0; font-size: 1.1em; }
        .kscrm-news-title a { text-decoration: none; color: #0073aa; }
        .kscrm-news-title a:hover { text-decoration: underline; }
        .kscrm-news-excerpt { margin: 8px 0; color: #666; line-height: 1.5; }
        .kscrm-news-meta { font-size: 0.9em; color: #999; }
        .kscrm-news-source::after { content: " • "; }
        .kscrm-news-cards .kscrm-news-item { display: flex; align-items: flex-start; gap: 15px; }
        .kscrm-news-cards .kscrm-news-image { flex-shrink: 0; width: 120px; }
        .kscrm-news-cards .kscrm-news-image img { width: 100%; height: auto; border-radius: 4px; }
        .kscrm-news-error, .kscrm-news-empty { padding: 15px; background: #f8f9fa; border-left: 4px solid #ddd; color: #666; }
        </style>';
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
    public function get_form_repository(): FormRepository { return $this->formRepository; }
    public function get_news_aggregator(): Aggregator { return $this->newsAggregator; }
}