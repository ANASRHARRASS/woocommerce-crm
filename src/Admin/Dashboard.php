<?php

namespace Anas\WCCRM\Admin;

use Anas\WCCRM\Core\Plugin;

defined('ABSPATH') || exit;

class Dashboard
{
    private Plugin $plugin;
    private string $cap = 'manage_options';
    private string $slug = 'woocommerce-crm';

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('wp_ajax_wccrm_dashboard_metrics', [$this, 'ajax_metrics']);
        add_action('wp_ajax_wccrm_contacts_search', [$this, 'ajax_contacts_search']);
        add_action('wp_ajax_wccrm_activity_feed', [$this, 'ajax_activity_feed']);
        add_action('wp_ajax_wccrm_templates_list', [$this, 'ajax_templates_list']);
        add_action('wp_ajax_wccrm_template_save', [$this, 'ajax_template_save']);
        add_action('wp_ajax_wccrm_template_delete', [$this, 'ajax_template_delete']);
        add_action('wp_ajax_wccrm_queue_stats', [$this, 'ajax_queue_stats']);
    }

    public function register_menu(): void
    {
        add_menu_page(
            __('WooCommerce CRM', 'woocommerce-crm'),
            __('WooCommerce CRM', 'woocommerce-crm'),
            $this->cap,
            $this->slug,
            [$this, 'render'],
            'dashicons-chart-line',
            56
        );
    }

    public function assets($hook): void
    {
        if (strpos($hook, $this->slug) === false) {
            return;
        }
        wp_enqueue_style('wccrm-admin', WCCRM_PLUGIN_URL . 'assets/css/admin.css', [], WCCRM_VERSION);
        wp_enqueue_script('wccrm-admin', WCCRM_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], WCCRM_VERSION, true);
        wp_localize_script('wccrm-admin', 'wccrmDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('wccrm_dashboard')
        ]);
    }

    public function render(): void
    {
        echo '<div class="wrap"><h1>WooCommerce CRM</h1>';
        echo '<h2 class="nav-tab-wrapper" id="wccrm-tabs">';
        $tabs = [
            'overview' => __('Overview', 'woocommerce-crm'),
            'activity' => __('Activity', 'woocommerce-crm'),
            'contacts' => __('Contacts', 'woocommerce-crm'),
            'templates' => __('Templates', 'woocommerce-crm'),
            'queue' => __('Queue', 'woocommerce-crm'),
        ];
        foreach ($tabs as $k => $label) {
            echo '<a href="#" class="nav-tab" data-tab="' . esc_attr($k) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';
        // Panels
        echo '<div id="wccrm-panel-overview" class="wccrm-panel">';
        echo '<div id="wccrm-metrics" class="wccrm-cards" data-wccrm-metrics-loading="1">' . $this->render_metrics_placeholder() . '</div>';
        echo '</div>';
        echo '<div id="wccrm-panel-activity" class="wccrm-panel" style="display:none"><ul id="wccrm-activity-list"><li>Loading…</li></ul></div>';
        echo '<div id="wccrm-panel-contacts" class="wccrm-panel" style="display:none"><input type="text" id="wccrm-contact-search" placeholder="Search contacts" /> <ul id="wccrm-contact-results"></ul></div>';
        echo '<div id="wccrm-panel-templates" class="wccrm-panel" style="display:none">';
        echo '<div id="wccrm-templates"></div><h3>' . esc_html__('Create / Edit Template', 'woocommerce-crm') . '</h3><form id="wccrm-template-form"><input type="hidden" name="template_key" id="wccrm-tpl-key" /><p><label>' . esc_html__('Key', 'woocommerce-crm') . '<br/><input type="text" name="key" id="wccrm-tpl-key-input" required /></label></p><p><label>' . esc_html__('Channel', 'woocommerce-crm') . '<br/><select name="channel" id="wccrm-tpl-channel"><option value="email">Email</option><option value="sms">SMS</option></select></label></p><p><label>' . esc_html__('Subject', 'woocommerce-crm') . '<br/><input type="text" name="subject" id="wccrm-tpl-subject" /></label></p><p><label>' . esc_html__('Body', 'woocommerce-crm') . '<br/><textarea name="body" id="wccrm-tpl-body" rows="5"></textarea></label></p><p><button class="button button-primary">' . esc_html__('Save Template', 'woocommerce-crm') . '</button></p></form></div>';
        echo '<div id="wccrm-panel-queue" class="wccrm-panel" style="display:none"><div id="wccrm-queue-stats">Loading…</div></div>';
        $script = <<<'JS'
(function($){
 function esc(s){return (''+s).replace(/[<>&]/g,function(c){return {'<':'&lt;','>':'&gt;','&':'&amp;'}[c]||c;});}
 function loadMetrics(){ $.post(wccrmDashboard.ajaxUrl,{action:'wccrm_dashboard_metrics',_ajax_nonce:wccrmDashboard.nonce},function(r){ if(r&&r.success){ var root=$('#wccrm-metrics').empty(); var map={'Contacts':r.data.contacts,'Orders':r.data.orders,'Avg Order Value':r.data.avg_order_value,'Queued':r.data.messages_queued}; for(var k in map){ root.append('<div class="wccrm-card"><h3>'+esc(k)+'</h3><strong>'+esc(map[k])+'</strong></div>'); } } }); }
 function loadActivity(){ $.post(wccrmDashboard.ajaxUrl,{action:'wccrm_activity_feed',_ajax_nonce:wccrmDashboard.nonce},function(r){ var ul=$('#wccrm-activity-list').empty(); if(r.success){ (r.data||[]).forEach(function(a){ ul.append('<li><strong>'+esc(a.type)+'</strong> '+esc(a.message)+' <em>'+esc(a.created_at)+'</em></li>'); }); } else { ul.append('<li>None</li>'); } }); }
 function searchContacts(q){ $.post(wccrmDashboard.ajaxUrl,{action:'wccrm_contacts_search',q:q,_ajax_nonce:wccrmDashboard.nonce},function(r){ var ul=$('#wccrm-contact-results').empty(); if(r.success){ (r.data||[]).forEach(function(c){ ul.append('<li data-id="'+c.id+'">'+esc(c.name||'(no name)')+' &lt;'+esc(c.email||'')+'&gt;</li>'); }); } }); }
 function loadTemplates(){ $.post(wccrmDashboard.ajaxUrl,{action:'wccrm_templates_list',_ajax_nonce:wccrmDashboard.nonce},function(r){ var wrap=$('#wccrm-templates').empty(); if(r.success){ if(!(r.data||[]).length){ wrap.append('<p>No templates.</p>'); } (r.data||[]).forEach(function(t){ wrap.append('<div class="wccrm-template" data-key="'+esc(t.template_key)+'"><code>'+esc(t.template_key)+'</code> ['+esc(t.channel)+'] '+esc(t.subject||'')+' <button class="button link wccrm-edit-template" data-key="'+esc(t.template_key)+'">Edit</button> <button class="button link wccrm-delete-template" data-key="'+esc(t.template_key)+'">Delete</button></div>'); }); } }); }
 function loadQueue(){ $.post(wccrmDashboard.ajaxUrl,{action:'wccrm_queue_stats',_ajax_nonce:wccrmDashboard.nonce},function(r){ if(r.success){ $('#wccrm-queue-stats').text('Pending: '+r.data.pending); } }); }
 $('#wccrm-tabs').on('click','.nav-tab',function(e){ e.preventDefault(); var tab=$(this).data('tab'); $('.nav-tab').removeClass('nav-tab-active'); $(this).addClass('nav-tab-active'); $('.wccrm-panel').hide(); $('#wccrm-panel-'+tab).show(); if(tab==='overview'){loadMetrics();} else if(tab==='activity'){loadActivity();} else if(tab==='templates'){loadTemplates();} else if(tab==='queue'){loadQueue();} });
 $('#wccrm-contact-search').on('input',function(){ var v=$(this).val(); if(v.length>1){ searchContacts(v); } });
 $('#wccrm-templates').on('click','.wccrm-edit-template',function(){ var key=$(this).data('key'); $.post(wccrmDashboard.ajaxUrl,{action:'wccrm_templates_list',_ajax_nonce:wccrmDashboard.nonce},function(r){ if(r.success){ var t=(r.data||[]).find(function(x){return x.template_key===key;}); if(t){ $('#wccrm-tpl-key').val(t.template_key); $('#wccrm-tpl-key-input').val(t.template_key); $('#wccrm-tpl-channel').val(t.channel); $('#wccrm-tpl-subject').val(t.subject); $('#wccrm-tpl-body').val(t.body); } } }); });
 $('#wccrm-templates').on('click','.wccrm-delete-template',function(){ if(!confirm('Delete template?')) return; var key=$(this).data('key'); $.post(wccrmDashboard.ajaxUrl,{action:'wccrm_template_delete',key:key,_ajax_nonce:wccrmDashboard.nonce},function(){ loadTemplates(); }); });
 $('#wccrm-template-form').on('submit',function(e){ e.preventDefault(); var data=$(this).serializeArray(); var payload={}; data.forEach(function(x){payload[x.name]=x.value;}); $.post(wccrmDashboard.ajaxUrl,$.extend({action:'wccrm_template_save',_ajax_nonce:wccrmDashboard.nonce},payload),function(r){ if(r.success){ $('#wccrm-template-form')[0].reset(); loadTemplates(); } else { alert('Error saving'); } }); });
 $('#wccrm-tabs .nav-tab').first().trigger('click');
})(jQuery);
JS;
        echo '<script>' . $script . '</script>';
        echo '</div>';
    }

    private function render_metrics_placeholder(): string
    {
        $metrics = [
            'Contacts' => '…',
            'Orders' => '…',
            'Avg Order Value' => '…',
            'Queued' => '…'
        ];
        $html = '';
        foreach ($metrics as $label => $val) {
            $html .= '<div class="wccrm-card"><h3>' . esc_html($label) . '</h3><strong>' . esc_html($val) . '</strong></div>';
        }
        return $html;
    }

    public function ajax_metrics(): void
    {
        check_ajax_referer('wccrm_dashboard');
        $agg_file = WCCRM_PLUGIN_DIR . 'src/Reporting/MetricsAggregator.php';
        if (file_exists($agg_file)) {
            require_once $agg_file;
        }
        if (class_exists('Anas\\WCCRM\\Reporting\\MetricsAggregator')) {
            $agg = new \Anas\WCCRM\Reporting\MetricsAggregator();
            wp_send_json_success($agg->collect());
        }
        wp_send_json_error(['message' => 'metrics unavailable']);
    }

    public function ajax_contacts_search(): void
    {
        check_ajax_referer('wccrm_dashboard');
        $q = sanitize_text_field($_POST['q'] ?? '');
        $repo = $this->plugin->get_contacts();
        wp_send_json_success($repo->search($q));
    }

    public function ajax_activity_feed(): void
    {
        check_ajax_referer('wccrm_dashboard');
        $ar = $this->plugin->get_activity_repository();
        if (!$ar) {
            wp_send_json_success([]);
        }
        global $wpdb; // fetch recent 25
        $table = $wpdb->prefix . 'wccrm_contact_activity';
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC LIMIT 25", ARRAY_A) ?: [];
        wp_send_json_success($rows);
    }

    public function ajax_templates_list(): void
    {
        check_ajax_referer('wccrm_dashboard');
        $repo = $this->plugin->get_message_dispatcher();
        if (!method_exists($repo, 'process_queue')) {
        } // silence
        $tplRepoFile = WCCRM_PLUGIN_DIR . 'src/Messaging/Templates/TemplateRepository.php';
        if (file_exists($tplRepoFile)) require_once $tplRepoFile;
        if (class_exists('Anas\\WCCRM\\Messaging\\Templates\\TemplateRepository')) {
            $tr = new \Anas\WCCRM\Messaging\Templates\TemplateRepository();
            wp_send_json_success($tr->all());
        }
        wp_send_json_success([]);
    }
    public function ajax_template_save(): void
    {
        check_ajax_referer('wccrm_dashboard');
        $key = sanitize_key($_POST['key'] ?? '');
        if (!$key) {
            wp_send_json_error(['message' => 'key required']);
        }
        $channel = sanitize_text_field($_POST['channel'] ?? 'email');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $body = wp_kses_post($_POST['body'] ?? '');
        $repoFile = WCCRM_PLUGIN_DIR . 'src/Messaging/Templates/TemplateRepository.php';
        if (file_exists($repoFile)) require_once $repoFile;
        if (class_exists('Anas\\WCCRM\\Messaging\\Templates\\TemplateRepository')) {
            $tr = new \Anas\WCCRM\Messaging\Templates\TemplateRepository();
            $ok = $tr->save($key, $channel, $subject, $body);
            if ($ok && class_exists('Anas\\WCCRM\\Reporting\\MetricsAggregator')) {
                \Anas\WCCRM\Reporting\MetricsAggregator::invalidate_cache();
            }
            if ($ok) {
                wp_send_json_success(true);
            }
        }
        wp_send_json_error(['message' => 'save failed']);
    }
    public function ajax_template_delete(): void
    {
        check_ajax_referer('wccrm_dashboard');
        $key = sanitize_key($_POST['key'] ?? '');
        if (!$key) {
            wp_send_json_error(['message' => 'key required']);
        }
        $repoFile = WCCRM_PLUGIN_DIR . 'src/Messaging/Templates/TemplateRepository.php';
        if (file_exists($repoFile)) require_once $repoFile;
        if (class_exists('Anas\\WCCRM\\Messaging\\Templates\\TemplateRepository')) {
            $tr = new \Anas\WCCRM\Messaging\Templates\TemplateRepository();
            $tr->delete($key);
            \Anas\WCCRM\Reporting\MetricsAggregator::invalidate_cache();
            wp_send_json_success(true);
        }
        wp_send_json_error(['message' => 'delete failed']);
    }
    public function ajax_queue_stats(): void
    {
        check_ajax_referer('wccrm_dashboard');
        $queue = $this->plugin->get_message_queue();
        if (!$queue) {
            wp_send_json_success(['pending' => 0]);
        }
        wp_send_json_success(['pending' => $queue->count_pending()]);
    }
}
