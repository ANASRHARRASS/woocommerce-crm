<?php

namespace Anas\WCCRM\Integration;

defined('ABSPATH') || exit;

/**
 * Integration manager for next-level features
 */
class NextStepsIntegrator
{
    private static ?NextStepsIntegrator $instance = null;

    public static function instance(): NextStepsIntegrator
    {
        return self::$instance ??= new self();
    }

    public function __construct()
    {
        add_action('wccrm_after_init', [$this, 'initialize_advanced_features']);
        add_action('init', [$this, 'register_advanced_hooks']);
    }

    /**
     * Initialize advanced features after main plugin loads
     */
    public function initialize_advanced_features(): void
    {
        // Initialize Asset Optimizer
        if (class_exists('\Anas\WCCRM\Assets\AssetOptimizer')) {
            \Anas\WCCRM\Assets\AssetOptimizer::instance();
        }

        // Initialize Rate Limiter for REST API
        $this->setup_rate_limiting();

        // Initialize Task Manager
        $this->setup_task_manager();

        // Initialize Analytics Dashboard
        $this->setup_analytics_dashboard();

        // Setup advanced cron jobs
        $this->setup_advanced_cron_jobs();

        // Initialize performance monitoring
        $this->setup_performance_monitoring();
    }

    /**
     * Register advanced hooks
     */
    public function register_advanced_hooks(): void
    {
        // Add custom cron intervals
        add_filter('cron_schedules', [\Anas\WCCRM\Tasks\TaskManager::class, 'register_cron_intervals']);

        // Add task processing hook
        add_action('wccrm_process_tasks', [\Anas\WCCRM\Tasks\TaskManager::class, 'process_tasks']);

        // Add performance optimization hooks
        add_action('plugins_loaded', [$this, 'optimize_plugin_loading'], 1);
        add_action('wp_loaded', [$this, 'optimize_after_wp_loaded']);
    }

    /**
     * Setup rate limiting for API endpoints
     */
    private function setup_rate_limiting(): void
    {
        if (class_exists('\Anas\WCCRM\Security\RateLimiter')) {
            // Add rate limiting to REST API
            add_filter('rest_pre_dispatch', function ($result, $server, $request) {
                if (strpos($request->get_route(), '/wccrm/') === 0) {
                    $rate_check = \Anas\WCCRM\Security\RateLimiter::rest_api_rate_limit_middleware($request);

                    if ($rate_check instanceof \WP_REST_Response) {
                        return $rate_check; // Rate limit exceeded
                    }
                }

                return $result;
            }, 10, 3);

            // Add rate limit headers to API responses
            add_filter('rest_post_dispatch', function ($response, $server, $request) {
                if (strpos($request->get_route(), '/wccrm/') === 0) {
                    $ip = \Anas\WCCRM\Security\RateLimiter::get_client_ip();
                    $route = str_replace('/wccrm/v1/', '', $request->get_route());
                    $rate_info = \Anas\WCCRM\Security\RateLimiter::get_rate_limit_info("ip_{$ip}_endpoint_{$route}");

                    $response->header('X-RateLimit-Limit', $rate_info['limit']);
                    $response->header('X-RateLimit-Remaining', $rate_info['remaining']);
                    $response->header('X-RateLimit-Reset', $rate_info['reset_time']);
                }

                return $response;
            }, 10, 3);
        }
    }

    /**
     * Setup task manager
     */
    private function setup_task_manager(): void
    {
        if (class_exists('\Anas\WCCRM\Tasks\TaskManager')) {
            // Schedule recurring optimization tasks
            if (!wp_next_scheduled('wccrm_daily_optimization')) {
                wp_schedule_event(time(), 'daily', 'wccrm_daily_optimization');
            }

            add_action('wccrm_daily_optimization', function () {
                // Queue daily optimization tasks
                \Anas\WCCRM\Tasks\TaskManager::queue_task('database_optimize', [], 1);
                \Anas\WCCRM\Tasks\TaskManager::queue_task('cache_warmup', [], 2);
                \Anas\WCCRM\Tasks\TaskManager::queue_task('data_cleanup', ['days_old' => 90], 3);
            });

            // Add admin menu for task management
            add_action('admin_menu', function () {
                add_submenu_page(
                    'crm-dashboard',
                    'Task Manager',
                    'Tasks',
                    'manage_options',
                    'wccrm-tasks',
                    [$this, 'render_task_manager']
                );
            });
        }
    }

    /**
     * Setup analytics dashboard
     */
    private function setup_analytics_dashboard(): void
    {
        if (class_exists('\Anas\WCCRM\Analytics\AnalyticsDashboard')) {
            $analytics = new \Anas\WCCRM\Analytics\AnalyticsDashboard();

            // Add admin menu for analytics
            add_action('admin_menu', function () use ($analytics) {
                add_submenu_page(
                    'crm-dashboard',
                    'Analytics',
                    'Analytics',
                    'manage_options',
                    'wccrm-analytics',
                    [$analytics, 'render_dashboard']
                );
            });
        }
    }

    /**
     * Setup advanced cron jobs
     */
    private function setup_advanced_cron_jobs(): void
    {
        // Performance monitoring
        if (!wp_next_scheduled('wccrm_performance_log')) {
            wp_schedule_event(time(), 'hourly', 'wccrm_performance_log');
        }

        add_action('wccrm_performance_log', function () {
            if (class_exists('\Anas\WCCRM\Performance\PerformanceMonitor')) {
                \Anas\WCCRM\Performance\PerformanceMonitor::log_metrics();
            }
        });

        // Cache warming
        if (!wp_next_scheduled('wccrm_cache_warmup')) {
            wp_schedule_event(time() + 300, 'twicedaily', 'wccrm_cache_warmup');
        }

        add_action('wccrm_cache_warmup', function () {
            if (class_exists('\Anas\WCCRM\Cache\CacheManager')) {
                \Anas\WCCRM\Cache\CacheManager::warm_cache();
            }
        });

        // Database optimization
        if (!wp_next_scheduled('wccrm_database_optimize')) {
            wp_schedule_event(time() + 600, 'weekly', 'wccrm_database_optimize');
        }

        add_action('wccrm_database_optimize', function () {
            if (class_exists('\Anas\WCCRM\Database\IndexOptimizer')) {
                \Anas\WCCRM\Database\IndexOptimizer::optimize_indexes();
            }
        });
    }

    /**
     * Setup performance monitoring
     */
    private function setup_performance_monitoring(): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG && class_exists('\Anas\WCCRM\Performance\PerformanceMonitor')) {
            // Start monitoring on plugin load
            \Anas\WCCRM\Performance\PerformanceMonitor::start();

            // Monitor specific operations
            add_action('wccrm_social_lead_contact_linked', function () {
                \Anas\WCCRM\Performance\PerformanceMonitor::start_timer('lead_processing');
            });

            add_action('wccrm_form_submitted', function () {
                $metrics = \Anas\WCCRM\Performance\PerformanceMonitor::end_timer('lead_processing');
                if ($metrics && $metrics['duration'] > 2.0) {
                    error_log('WCCRM: Slow lead processing detected: ' . $metrics['duration'] . 's');
                }
            });
        }
    }

    /**
     * Optimize plugin loading
     */
    public function optimize_plugin_loading(): void
    {
        // Disable unnecessary WordPress features in admin for WCCRM pages
        if (is_admin() && isset($_GET['page']) && strpos($_GET['page'], 'crm') === 0) {
            // Disable WordPress admin bar for CRM pages
            add_filter('show_admin_bar', '__return_false');

            // Remove unnecessary admin scripts
            add_action('admin_enqueue_scripts', function () {
                wp_dequeue_script('heartbeat');
                wp_dequeue_script('wp-embed');
                wp_dequeue_script('comment-reply');
            }, 999);
        }
    }

    /**
     * Optimize after WordPress loads
     */
    public function optimize_after_wp_loaded(): void
    {
        // Optimize database queries
        if (function_exists('wp_cache_get')) {
            // Cache frequently accessed options
            $options_to_cache = [
                'wccrm_webhook_secret',
                'wccrm_performance_metrics',
                'wccrm_task_queue'
            ];

            foreach ($options_to_cache as $option) {
                wp_cache_get($option, 'options');
            }
        }
    }

    /**
     * Render task manager page
     */
    public function render_task_manager(): void
    {
        if (!class_exists('\Anas\WCCRM\Tasks\TaskManager')) {
            echo '<div class="notice notice-error"><p>Task Manager not available</p></div>';
            return;
        }

        $stats = \Anas\WCCRM\Tasks\TaskManager::get_queue_stats();
?>
        <div class="wrap">
            <h1><?php _e('Task Manager', 'woocommerce-crm'); ?></h1>

            <div class="wccrm-task-stats">
                <div class="wccrm-stat-grid">
                    <div class="wccrm-stat-item">
                        <h3><?php _e('Total Tasks', 'woocommerce-crm'); ?></h3>
                        <span class="wccrm-stat-number"><?php echo $stats['total']; ?></span>
                    </div>
                    <div class="wccrm-stat-item">
                        <h3><?php _e('Pending', 'woocommerce-crm'); ?></h3>
                        <span class="wccrm-stat-number"><?php echo $stats['pending']; ?></span>
                    </div>
                    <div class="wccrm-stat-item">
                        <h3><?php _e('Processing', 'woocommerce-crm'); ?></h3>
                        <span class="wccrm-stat-number"><?php echo $stats['processing']; ?></span>
                    </div>
                    <div class="wccrm-stat-item">
                        <h3><?php _e('Completed', 'woocommerce-crm'); ?></h3>
                        <span class="wccrm-stat-number"><?php echo $stats['completed']; ?></span>
                    </div>
                    <div class="wccrm-stat-item">
                        <h3><?php _e('Failed', 'woocommerce-crm'); ?></h3>
                        <span class="wccrm-stat-number"><?php echo $stats['failed']; ?></span>
                    </div>
                </div>
            </div>

            <div class="wccrm-task-actions">
                <button id="wccrm-queue-optimization" class="button button-primary">
                    <?php _e('Queue Optimization Tasks', 'woocommerce-crm'); ?>
                </button>
                <button id="wccrm-process-tasks" class="button">
                    <?php _e('Process Tasks Now', 'woocommerce-crm'); ?>
                </button>
            </div>

            <div id="wccrm-task-result"></div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#wccrm-queue-optimization').click(function() {
                    $(this).prop('disabled', true).text('Queueing...');

                    $.post(ajaxurl, {
                            action: 'wccrm_queue_optimization_tasks',
                            nonce: '<?php echo wp_create_nonce('wccrm_admin'); ?>'
                        })
                        .done(function(response) {
                            $('#wccrm-task-result').html('<div class="notice notice-success"><p>Optimization tasks queued successfully!</p></div>');
                        })
                        .fail(function() {
                            $('#wccrm-task-result').html('<div class="notice notice-error"><p>Failed to queue tasks</p></div>');
                        })
                        .always(function() {
                            $('#wccrm-queue-optimization').prop('disabled', false).text('Queue Optimization Tasks');
                        });
                });

                $('#wccrm-process-tasks').click(function() {
                    $(this).prop('disabled', true).text('Processing...');

                    $.post(ajaxurl, {
                            action: 'wccrm_process_tasks_now',
                            nonce: '<?php echo wp_create_nonce('wccrm_admin'); ?>'
                        })
                        .done(function(response) {
                            $('#wccrm-task-result').html('<div class="notice notice-success"><p>Tasks processed!</p></div>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        })
                        .fail(function() {
                            $('#wccrm-task-result').html('<div class="notice notice-error"><p>Failed to process tasks</p></div>');
                        })
                        .always(function() {
                            $('#wccrm-process-tasks').prop('disabled', false).text('Process Tasks Now');
                        });
                });
            });
        </script>

        <style>
            .wccrm-stat-grid {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 20px;
                margin: 20px 0;
            }

            .wccrm-stat-item {
                background: #fff;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-align: center;
            }

            .wccrm-stat-number {
                font-size: 2em;
                font-weight: bold;
                color: #0073aa;
                display: block;
            }

            .wccrm-task-actions {
                margin: 20px 0;
            }

            .wccrm-task-actions button {
                margin-right: 10px;
            }
        </style>
<?php

        // Add AJAX handlers
        add_action('wp_ajax_wccrm_queue_optimization_tasks', function () {
            check_ajax_referer('wccrm_admin', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            \Anas\WCCRM\Tasks\TaskManager::queue_task('database_optimize', [], 1);
            \Anas\WCCRM\Tasks\TaskManager::queue_task('cache_warmup', [], 2);
            \Anas\WCCRM\Tasks\TaskManager::queue_task('data_cleanup', ['days_old' => 30], 3);

            wp_send_json_success();
        });

        add_action('wp_ajax_wccrm_process_tasks_now', function () {
            check_ajax_referer('wccrm_admin', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            \Anas\WCCRM\Tasks\TaskManager::process_tasks();
            wp_send_json_success();
        });
    }

    /**
     * Get next steps recommendations
     */
    public static function get_next_steps_recommendations(): array
    {
        return [
            'immediate' => [
                'Enable object cache (Redis/Memcached) for maximum performance',
                'Configure CDN for static assets',
                'Set up monitoring alerts for performance thresholds',
                'Review and optimize database queries in analytics'
            ],
            'short_term' => [
                'Implement advanced email campaigns',
                'Add A/B testing for forms',
                'Integrate with more external services (Mailchimp, Salesforce, etc.)',
                'Add advanced reporting and dashboards'
            ],
            'long_term' => [
                'Machine learning for lead scoring',
                'Advanced automation workflows',
                'Multi-site/network support',
                'Mobile app integration'
            ]
        ];
    }
}
