<?php

namespace Anas\WCCRM\Forms;

defined('ABSPATH') || exit;

/**
 * Dynamic Form Builder - HubSpot-like Form System
 * 
 * Creates intelligent forms that adapt based on:
 * - WooCommerce products/categories
 * - User behavior and data
 * - Lead scoring and segmentation
 * - A/B testing requirements
 */
class DynamicFormBuilder
{
    private $form_id;
    private $form_config;
    private $conditional_logic = [];
    private $tracking_enabled = true;

    public function __construct($form_id = null)
    {
        $this->form_id = $form_id ?: $this->generate_form_id();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_form_assets']);
        add_action('wp_ajax_wccrm_save_form', [$this, 'save_form']);
        add_action('wp_ajax_nopriv_wccrm_submit_form', [$this, 'handle_form_submission']);
        add_action('wp_ajax_wccrm_submit_form', [$this, 'handle_form_submission']);
    }

    /**
     * Generate a unique form ID
     */
    private function generate_form_id(): string
    {
        return 'wccrm_form_' . uniqid();
    }

    /**
     * Create a dynamic form based on configuration
     */
    public function create_form(array $config): string
    {
        $this->form_config = wp_parse_args($config, [
            'title' => __('Lead Generation Form', 'woocommerce-crm'),
            'fields' => [],
            'style' => 'modern',
            'conversion_goal' => 'lead',
            'follow_up_action' => 'email',
            'integration' => ['hubspot', 'mailchimp'],
            'conditional_logic' => true,
            'progressive_profiling' => true
        ]);

        return $this->render_form_html();
    }

    /**
     * Render the form HTML (private method)
     */
    private function render_form_html(): string
    {
        ob_start();
?>
        <div class="wccrm-dynamic-form" data-form-id="<?php echo esc_attr($this->form_id); ?>">
            <?php if ($this->form_config['title']): ?>
                <div class="form-header">
                    <h3 class="form-title"><?php echo esc_html($this->form_config['title']); ?></h3>
                </div>
            <?php endif; ?>

            <form class="wccrm-form wccrm-form-<?php echo esc_attr($this->form_config['style']); ?>"
                method="post"
                data-conversion-goal="<?php echo esc_attr($this->form_config['conversion_goal']); ?>">

                <?php wp_nonce_field('wccrm_form_submit', 'wccrm_nonce'); ?>
                <input type="hidden" name="form_id" value="<?php echo esc_attr($this->form_id); ?>">

                <?php echo $this->render_form_fields(); ?>

                <div class="form-actions">
                    <button type="submit" class="wccrm-btn wccrm-btn-primary">
                        <?php esc_html_e('Submit', 'woocommerce-crm'); ?>
                    </button>
                </div>
            </form>

            <?php if ($this->tracking_enabled): ?>
                <script>
                    // Form tracking and analytics
                    window.wccrmFormTracking = window.wccrmFormTracking || {};
                    window.wccrmFormTracking['<?php echo esc_js($this->form_id); ?>'] = {
                        startTime: Date.now(),
                        interactions: [],
                        goal: '<?php echo esc_js($this->form_config['conversion_goal']); ?>'
                    };
                </script>
            <?php endif; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Render form fields based on configuration
     */
    private function render_form_fields(): string
    {
        $fields_html = '';

        if (empty($this->form_config['fields'])) {
            // Auto-generate fields based on WooCommerce context
            $this->form_config['fields'] = $this->generate_smart_fields();
        }

        foreach ($this->form_config['fields'] as $field) {
            $fields_html .= $this->render_field($field);
        }

        return $fields_html;
    }

    /**
     * Generate smart form fields based on context
     */
    private function generate_smart_fields(): array
    {
        $fields = [];

        // Basic contact fields
        $fields[] = [
            'type' => 'email',
            'name' => 'email',
            'label' => __('Email Address', 'woocommerce-crm'),
            'required' => true,
            'placeholder' => __('Enter your email', 'woocommerce-crm')
        ];

        $fields[] = [
            'type' => 'text',
            'name' => 'first_name',
            'label' => __('First Name', 'woocommerce-crm'),
            'required' => true,
            'placeholder' => __('Enter your first name', 'woocommerce-crm')
        ];

        // Smart fields based on WooCommerce context
        if (is_product()) {
            global $product;
            $fields[] = [
                'type' => 'hidden',
                'name' => 'product_interest',
                'value' => $product->get_id()
            ];

            $fields[] = [
                'type' => 'select',
                'name' => 'interest_level',
                'label' => __('Interest Level', 'woocommerce-crm'),
                'options' => [
                    'high' => __('Very Interested', 'woocommerce-crm'),
                    'medium' => __('Somewhat Interested', 'woocommerce-crm'),
                    'low' => __('Just Browsing', 'woocommerce-crm')
                ]
            ];
        }

        if (is_product_category()) {
            $category = get_queried_object();
            $fields[] = [
                'type' => 'hidden',
                'name' => 'category_interest',
                'value' => $category->term_id
            ];
        }

        return $fields;
    }

    /**
     * Render individual form field
     */
    private function render_field(array $field): string
    {
        $field = wp_parse_args($field, [
            'type' => 'text',
            'name' => '',
            'label' => '',
            'placeholder' => '',
            'required' => false,
            'value' => '',
            'options' => [],
            'conditional' => null
        ]);

        ob_start();
    ?>
        <div class="wccrm-field-group wccrm-field-<?php echo esc_attr($field['type']); ?>"
            <?php if ($field['conditional']): ?>
            data-conditional="<?php echo esc_attr(json_encode($field['conditional'])); ?>"
            <?php endif; ?>>

            <?php if ($field['label'] && $field['type'] !== 'hidden'): ?>
                <label for="<?php echo esc_attr($field['name']); ?>" class="wccrm-field-label">
                    <?php echo esc_html($field['label']); ?>
                    <?php if ($field['required']): ?>
                        <span class="required">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>

            <?php
            switch ($field['type']) {
                case 'text':
                case 'email':
                case 'tel':
                case 'url':
            ?>
                    <input type="<?php echo esc_attr($field['type']); ?>"
                        id="<?php echo esc_attr($field['name']); ?>"
                        name="<?php echo esc_attr($field['name']); ?>"
                        value="<?php echo esc_attr($field['value']); ?>"
                        placeholder="<?php echo esc_attr($field['placeholder']); ?>"
                        <?php echo $field['required'] ? 'required' : ''; ?>
                        class="wccrm-field-input">
                <?php
                    break;

                case 'textarea':
                ?>
                    <textarea id="<?php echo esc_attr($field['name']); ?>"
                        name="<?php echo esc_attr($field['name']); ?>"
                        placeholder="<?php echo esc_attr($field['placeholder']); ?>"
                        <?php echo $field['required'] ? 'required' : ''; ?>
                        class="wccrm-field-textarea"><?php echo esc_textarea($field['value']); ?></textarea>
                <?php
                    break;

                case 'select':
                ?>
                    <select id="<?php echo esc_attr($field['name']); ?>"
                        name="<?php echo esc_attr($field['name']); ?>"
                        <?php echo $field['required'] ? 'required' : ''; ?>
                        class="wccrm-field-select">
                        <option value=""><?php esc_html_e('Select an option', 'woocommerce-crm'); ?></option>
                        <?php foreach ($field['options'] as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>"
                                <?php selected($field['value'], $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php
                    break;

                case 'checkbox':
                ?>
                    <label class="wccrm-checkbox-label">
                        <input type="checkbox"
                            id="<?php echo esc_attr($field['name']); ?>"
                            name="<?php echo esc_attr($field['name']); ?>"
                            value="1"
                            <?php checked($field['value'], 1); ?>
                            <?php echo $field['required'] ? 'required' : ''; ?>
                            class="wccrm-field-checkbox">
                        <?php echo esc_html($field['label']); ?>
                    </label>
                <?php
                    break;

                case 'hidden':
                ?>
                    <input type="hidden"
                        name="<?php echo esc_attr($field['name']); ?>"
                        value="<?php echo esc_attr($field['value']); ?>">
            <?php
                    break;
            }
            ?>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Handle form submission
     */
    public function handle_form_submission(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['wccrm_nonce'] ?? '', 'wccrm_form_submit')) {
            wp_die(__('Security check failed', 'woocommerce-crm'));
        }

        $form_data = $this->sanitize_form_data($_POST);

        // Create or update contact
        $contact_id = $this->process_contact($form_data);

        // Track conversion
        $this->track_conversion($contact_id, $form_data);

        // Execute follow-up actions
        $this->execute_follow_up_actions($contact_id, $form_data);

        // Send response
        if (wp_doing_ajax()) {
            wp_send_json_success([
                'message' => __('Thank you! Your information has been received.', 'woocommerce-crm'),
                'contact_id' => $contact_id
            ]);
        } else {
            // Redirect for non-AJAX submissions
            wp_safe_redirect(add_query_arg('form_submitted', '1', wp_get_referer()));
            exit;
        }
    }

    /**
     * Sanitize form data
     */
    private function sanitize_form_data(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Process contact creation/update
     */
    private function process_contact(array $form_data): int
    {
        global $wpdb;

        $email = $form_data['email'] ?? '';
        if (empty($email)) {
            return 0;
        }

        // Check if contact exists
        $contact_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wccrm_contacts WHERE email = %s",
            $email
        ));

        $contact_data = [
            'email' => $email,
            'first_name' => $form_data['first_name'] ?? '',
            'last_name' => $form_data['last_name'] ?? '',
            'source' => 'dynamic_form',
            'form_id' => $this->form_id,
            'updated_at' => current_time('mysql')
        ];

        if ($contact_id) {
            // Update existing contact
            $wpdb->update(
                $wpdb->prefix . 'wccrm_contacts',
                $contact_data,
                ['id' => $contact_id]
            );
        } else {
            // Create new contact
            $contact_data['created_at'] = current_time('mysql');
            $contact_data['status'] = 'lead';

            $wpdb->insert(
                $wpdb->prefix . 'wccrm_contacts',
                $contact_data
            );

            $contact_id = $wpdb->insert_id;
        }

        return $contact_id;
    }

    /**
     * Track conversion for analytics
     */
    private function track_conversion(int $contact_id, array $form_data): void
    {
        // Implementation for conversion tracking
        do_action('wccrm_form_conversion', $contact_id, $this->form_id, $form_data);
    }

    /**
     * Execute follow-up actions (emails, integrations, etc.)
     */
    private function execute_follow_up_actions(int $contact_id, array $form_data): void
    {
        // Queue follow-up email
        if ($this->form_config['follow_up_action'] === 'email') {
            do_action('wccrm_queue_follow_up_email', $contact_id, $this->form_id);
        }

        // Trigger integrations
        foreach ($this->form_config['integration'] as $integration) {
            do_action('wccrm_integration_sync', $integration, $contact_id, $form_data);
        }
    }

    /**
     * Enqueue form assets
     */
    public function enqueue_form_assets(): void
    {
        wp_enqueue_style(
            'wccrm-dynamic-forms',
            WCCRM_PLUGIN_URL . 'assets/css/dynamic-forms.css',
            [],
            WCCRM_VERSION
        );

        wp_enqueue_script(
            'wccrm-dynamic-forms',
            WCCRM_PLUGIN_URL . 'assets/js/dynamic-forms.js',
            ['jquery'],
            WCCRM_VERSION,
            true
        );

        wp_localize_script('wccrm-dynamic-forms', 'wccrmForms', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wccrm_form_nonce'),
            'strings' => [
                'submitting' => __('Submitting...', 'woocommerce-crm'),
                'error' => __('An error occurred. Please try again.', 'woocommerce-crm')
            ]
        ]);
    }

    /**
     * Get form shortcode
     */
    public function get_shortcode(): string
    {
        return "[wccrm_dynamic_form id=\"{$this->form_id}\"]";
    }

    /**
     * Render a complete form based on configuration (public method for shortcodes)
     */
    public function render_form(array $config): string
    {
        // Set form configuration
        $this->form_config = array_merge([
            'id' => 'wccrm-form',
            'style' => 'modern',
            'fields' => ['name', 'email'],
            'title' => 'Contact Form',
            'description' => '',
            'submit_text' => 'Submit',
            'progressive_profiling' => false,
            'conditional_logic' => false,
            'hubspot_form_id' => '',
            'mailchimp_list_id' => ''
        ], $config);

        $this->form_id = $this->form_config['id'];

        // Generate fields based on configuration
        $fields = $this->generate_fields_from_config($this->form_config['fields']);

        // Update form config with generated fields
        $this->form_config['fields'] = $fields;

        // Use the existing create_form method
        return $this->create_form($this->form_config);
    }

    /**
     * Generate fields from configuration array
     */
    private function generate_fields_from_config(array $field_names): array
    {
        $fields = [];

        foreach ($field_names as $field_name) {
            switch ($field_name) {
                case 'name':
                    $fields[] = [
                        'type' => 'text',
                        'name' => 'wccrm_name',
                        'label' => __('Full Name', 'woocommerce-crm'),
                        'required' => true,
                        'placeholder' => __('Enter your full name', 'woocommerce-crm')
                    ];
                    break;

                case 'email':
                    $fields[] = [
                        'type' => 'email',
                        'name' => 'wccrm_email',
                        'label' => __('Email Address', 'woocommerce-crm'),
                        'required' => true,
                        'placeholder' => __('Enter your email address', 'woocommerce-crm')
                    ];
                    break;

                case 'phone':
                    $fields[] = [
                        'type' => 'tel',
                        'name' => 'wccrm_phone',
                        'label' => __('Phone Number', 'woocommerce-crm'),
                        'required' => false,
                        'placeholder' => __('Enter your phone number', 'woocommerce-crm')
                    ];
                    break;

                case 'company':
                    $fields[] = [
                        'type' => 'text',
                        'name' => 'wccrm_company',
                        'label' => __('Company', 'woocommerce-crm'),
                        'required' => false,
                        'placeholder' => __('Enter your company name', 'woocommerce-crm')
                    ];
                    break;

                case 'message':
                    $fields[] = [
                        'type' => 'textarea',
                        'name' => 'wccrm_message',
                        'label' => __('Message', 'woocommerce-crm'),
                        'required' => false,
                        'placeholder' => __('Enter your message', 'woocommerce-crm'),
                        'rows' => 4
                    ];
                    break;

                case 'industry':
                    $fields[] = [
                        'type' => 'select',
                        'name' => 'wccrm_industry',
                        'label' => __('Industry', 'woocommerce-crm'),
                        'required' => false,
                        'options' => [
                            '' => __('Select Industry', 'woocommerce-crm'),
                            'technology' => __('Technology', 'woocommerce-crm'),
                            'healthcare' => __('Healthcare', 'woocommerce-crm'),
                            'finance' => __('Finance', 'woocommerce-crm'),
                            'retail' => __('Retail', 'woocommerce-crm'),
                            'manufacturing' => __('Manufacturing', 'woocommerce-crm'),
                            'other' => __('Other', 'woocommerce-crm')
                        ]
                    ];
                    break;

                case 'budget':
                    $fields[] = [
                        'type' => 'select',
                        'name' => 'wccrm_budget',
                        'label' => __('Budget Range', 'woocommerce-crm'),
                        'required' => false,
                        'options' => [
                            '' => __('Select Budget', 'woocommerce-crm'),
                            'under-10k' => __('Under $10,000', 'woocommerce-crm'),
                            '10k-50k' => __('$10,000 - $50,000', 'woocommerce-crm'),
                            '50k-100k' => __('$50,000 - $100,000', 'woocommerce-crm'),
                            'over-100k' => __('Over $100,000', 'woocommerce-crm')
                        ]
                    ];
                    break;

                case 'timeline':
                    $fields[] = [
                        'type' => 'select',
                        'name' => 'wccrm_timeline',
                        'label' => __('Timeline', 'woocommerce-crm'),
                        'required' => false,
                        'options' => [
                            '' => __('Select Timeline', 'woocommerce-crm'),
                            'asap' => __('ASAP', 'woocommerce-crm'),
                            '1-3-months' => __('1-3 months', 'woocommerce-crm'),
                            '3-6-months' => __('3-6 months', 'woocommerce-crm'),
                            '6-12-months' => __('6-12 months', 'woocommerce-crm'),
                            'exploring' => __('Just exploring', 'woocommerce-crm')
                        ]
                    ];
                    break;
            }
        }

        return $fields;
    }

    /**
     * Process form submission
     */
    public function process_form_submission(string $form_id, array $form_data): array
    {
        try {
            // Validate required fields
            $validation_result = $this->validate_form_data($form_data);
            if (!$validation_result['valid']) {
                return [
                    'success' => false,
                    'message' => 'Please correct the errors below.',
                    'errors' => $validation_result['errors']
                ];
            }

            // Create or update contact
            global $wpdb;
            $contacts_table = $wpdb->prefix . 'wccrm_contacts';

            $contact_data = [
                'email' => $form_data['email'] ?? '',
                'name' => $form_data['name'] ?? '',
                'phone' => $form_data['phone'] ?? '',
                'company' => $form_data['company'] ?? '',
                'source' => 'form_' . $form_id,
                'status' => 'new',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];

            // Check if contact exists
            $existing_contact = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$contacts_table} WHERE email = %s",
                $contact_data['email']
            ));

            if ($existing_contact) {
                // Update existing contact
                $wpdb->update(
                    $contacts_table,
                    array_merge($contact_data, ['updated_at' => current_time('mysql')]),
                    ['id' => $existing_contact->id],
                    ['%s', '%s', '%s', '%s', '%s', '%s', '%s'],
                    ['%d']
                );
                $contact_id = $existing_contact->id;
            } else {
                // Create new contact
                $wpdb->insert($contacts_table, $contact_data);
                $contact_id = $wpdb->insert_id;
            }

            // Store additional form data as meta
            foreach ($form_data as $key => $value) {
                if (!in_array($key, ['email', 'name', 'phone', 'company']) && !empty($value)) {
                    $this->save_contact_meta($contact_id, $key, $value);
                }
            }

            // Trigger actions for integrations
            do_action('wccrm_form_submitted', $form_id, $form_data, $contact_id);

            return [
                'success' => true,
                'message' => 'Form submitted successfully!',
                'contact_id' => $contact_id,
                'redirect_url' => $this->form_config['redirect_url'] ?? ''
            ];
        } catch (\Exception $e) {
            error_log('WCCRM Form Processing Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while processing your submission. Please try again.'
            ];
        }
    }

    /**
     * Validate form data
     */
    private function validate_form_data(array $data): array
    {
        $errors = [];
        $required_fields = ['email'];

        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = sprintf(__('%s is required.', 'woocommerce-crm'), ucfirst($field));
            }
        }

        // Validate email format
        if (!empty($data['email']) && !is_email($data['email'])) {
            $errors['email'] = __('Please enter a valid email address.', 'woocommerce-crm');
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get progressive profiling fields for a contact
     */
    public function get_progressive_fields(string $email, string $form_id): array
    {
        global $wpdb;

        // Get existing contact data
        $contacts_table = $wpdb->prefix . 'wccrm_contacts';
        $contact = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$contacts_table} WHERE email = %s",
            $email
        ));

        if (!$contact) {
            // Return basic fields for new contacts
            return [
                ['name' => 'wccrm_name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'wccrm_phone', 'label' => 'Phone', 'type' => 'tel', 'required' => false]
            ];
        }

        // Get contact meta to see what we already have
        $existing_meta = $this->get_contact_meta($contact->id);

        // Define progressive field priority
        $progressive_fields = [
            'company' => ['label' => 'Company', 'type' => 'text'],
            'industry' => ['label' => 'Industry', 'type' => 'select', 'options' => [
                'technology' => 'Technology',
                'healthcare' => 'Healthcare',
                'finance' => 'Finance',
                'retail' => 'Retail'
            ]],
            'budget' => ['label' => 'Budget Range', 'type' => 'select', 'options' => [
                'under-10k' => 'Under $10,000',
                '10k-50k' => '$10,000 - $50,000',
                '50k-100k' => '$50,000 - $100,000'
            ]],
            'timeline' => ['label' => 'Timeline', 'type' => 'select', 'options' => [
                'asap' => 'ASAP',
                '1-3-months' => '1-3 months',
                '3-6-months' => '3-6 months'
            ]]
        ];

        // Return fields we don't have yet
        $missing_fields = [];
        foreach ($progressive_fields as $field_name => $field_config) {
            if (!isset($existing_meta[$field_name]) || empty($existing_meta[$field_name])) {
                $missing_fields[] = array_merge([
                    'name' => 'wccrm_' . $field_name,
                    'required' => false
                ], $field_config);

                // Limit to 2-3 fields per interaction
                if (count($missing_fields) >= 2) {
                    break;
                }
            }
        }

        return $missing_fields;
    }

    /**
     * Save contact meta data
     */
    private function save_contact_meta(int $contact_id, string $meta_key, $meta_value): void
    {
        global $wpdb;

        $meta_table = $wpdb->prefix . 'wccrm_contact_meta';

        // Check if meta exists
        $existing_meta = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_id FROM {$meta_table} WHERE contact_id = %d AND meta_key = %s",
            $contact_id,
            $meta_key
        ));

        if ($existing_meta) {
            // Update existing meta
            $wpdb->update(
                $meta_table,
                ['meta_value' => maybe_serialize($meta_value)],
                ['meta_id' => $existing_meta],
                ['%s'],
                ['%d']
            );
        } else {
            // Insert new meta
            $wpdb->insert(
                $meta_table,
                [
                    'contact_id' => $contact_id,
                    'meta_key' => $meta_key,
                    'meta_value' => maybe_serialize($meta_value)
                ],
                ['%d', '%s', '%s']
            );
        }
    }

    /**
     * Get contact meta data
     */
    private function get_contact_meta(int $contact_id): array
    {
        global $wpdb;

        $meta_table = $wpdb->prefix . 'wccrm_contact_meta';
        $meta_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$meta_table} WHERE contact_id = %d",
            $contact_id
        ));

        $meta = [];
        foreach ($meta_rows as $row) {
            $meta[$row->meta_key] = maybe_unserialize($row->meta_value);
        }

        return $meta;
    }
}
