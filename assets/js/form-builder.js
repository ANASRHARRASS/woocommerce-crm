/**
 * WooCommerce CRM Dynamic Forms
 * HubSpot-like form functionality with progressive profiling and smart fields
 */

(function ($) {
    'use strict';

    class WCCRMDynamicForms {
        constructor() {
            this.forms = {};
            this.trackingData = {};
            this.init();
        }

        init() {
            this.bindEvents();
            this.initProgressiveFields();
            this.setupFormTracking();
            this.initConditionalLogic();
        }

        bindEvents() {
            // Form submission
            $(document).on('submit', '.wccrm-form', this.handleFormSubmit.bind(this));

            // Field interactions for tracking
            $(document).on('focus', '.wccrm-field-input, .wccrm-field-textarea, .wccrm-field-select', this.trackFieldInteraction.bind(this));

            // Conditional field changes
            $(document).on('change', '[data-conditional]', this.handleConditionalFields.bind(this));

            // Progressive profiling
            $(document).on('blur', '.wccrm-field-input[type="email"]', this.checkProgressiveProfiling.bind(this));
        }

        handleFormSubmit(e) {
            e.preventDefault();

            const $form = $(e.target);
            const formId = $form.closest('.wccrm-dynamic-form').data('form-id');
            const submitButton = $form.find('[type="submit"]');

            // Disable button and show loading
            submitButton.prop('disabled', true).text(wccrmForms.strings.submitting);

            // Validate form
            if (!this.validateForm($form)) {
                submitButton.prop('disabled', false).text('Submit');
                return;
            }

            // Prepare form data
            const formData = new FormData($form[0]);
            formData.append('action', 'wccrm_submit_form');
            formData.append('nonce', wccrmForms.nonce);

            // Add tracking data
            if (this.trackingData[formId]) {
                formData.append('tracking_data', JSON.stringify(this.trackingData[formId]));
            }

            // Submit via AJAX
            $.ajax({
                url: wccrmForms.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showSuccessMessage($form, response.data.message);
                        this.trackConversion(formId, response.data.contact_id);
                        $form[0].reset();
                    } else {
                        this.showErrorMessage($form, response.data.message || wccrmForms.strings.error);
                    }
                },
                error: () => {
                    this.showErrorMessage($form, wccrmForms.strings.error);
                },
                complete: () => {
                    submitButton.prop('disabled', false).text('Submit');
                }
            });
        }

        validateForm($form) {
            let isValid = true;

            // Clear previous errors
            $form.find('.wccrm-field-error').remove();
            $form.find('.wccrm-field-input, .wccrm-field-textarea, .wccrm-field-select').removeClass('error');

            // Validate required fields
            $form.find('[required]').each(function () {
                const $field = $(this);
                const value = $field.val().trim();

                if (!value) {
                    isValid = false;
                    $field.addClass('error');
                    $field.closest('.wccrm-field-group').append(
                        '<span class="wccrm-field-error">This field is required</span>'
                    );
                }
            });

            // Validate email fields
            $form.find('[type="email"]').each(function () {
                const $field = $(this);
                const email = $field.val().trim();

                if (email && !this.isValidEmail(email)) {
                    isValid = false;
                    $field.addClass('error');
                    $field.closest('.wccrm-field-group').append(
                        '<span class="wccrm-field-error">Please enter a valid email address</span>'
                    );
                }
            }.bind(this));

            return isValid;
        }

        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        showSuccessMessage($form, message) {
            const $container = $form.closest('.wccrm-dynamic-form');

            // Remove existing messages
            $container.find('.wccrm-message').remove();

            // Add success message
            $container.prepend(`
                <div class="wccrm-message wccrm-success">
                    <p>${message}</p>
                </div>
            `);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                $container.find('.wccrm-message').fadeOut();
            }, 5000);
        }

        showErrorMessage($form, message) {
            const $container = $form.closest('.wccrm-dynamic-form');

            // Remove existing messages
            $container.find('.wccrm-message').remove();

            // Add error message
            $container.prepend(`
                <div class="wccrm-message wccrm-error">
                    <p>${message}</p>
                </div>
            `);
        }

        // Progressive Profiling
        checkProgressiveProfiling(e) {
            const $emailField = $(e.target);
            const email = $emailField.val().trim();

            if (!this.isValidEmail(email)) return;

            const $form = $emailField.closest('.wccrm-form');
            const formId = $form.closest('.wccrm-dynamic-form').data('form-id');

            // Check if this email exists in CRM
            $.ajax({
                url: wccrmForms.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wccrm_check_progressive_fields',
                    email: email,
                    form_id: formId,
                    nonce: wccrmForms.nonce
                },
                success: (response) => {
                    if (response.success && response.data.progressive_fields) {
                        this.addProgressiveFields($form, response.data.progressive_fields);
                    }
                }
            });
        }

        addProgressiveFields($form, fields) {
            // Add additional fields for returning visitors
            fields.forEach(field => {
                if (!$form.find(`[name="${field.name}"]`).length) {
                    const fieldHtml = this.generateFieldHtml(field);
                    $form.find('.form-actions').before(fieldHtml);
                }
            });
        }

        generateFieldHtml(field) {
            return `
                <div class="wccrm-field-group wccrm-field-${field.type} wccrm-progressive-field">
                    <label for="${field.name}" class="wccrm-field-label">
                        ${field.label}
                        ${field.required ? '<span class="required">*</span>' : ''}
                    </label>
                    ${this.generateFieldInput(field)}
                </div>
            `;
        }

        generateFieldInput(field) {
            switch (field.type) {
                case 'text':
                case 'email':
                case 'tel':
                    return `<input type="${field.type}" id="${field.name}" name="${field.name}" 
                            placeholder="${field.placeholder || ''}" 
                            ${field.required ? 'required' : ''} class="wccrm-field-input">`;

                case 'textarea':
                    return `<textarea id="${field.name}" name="${field.name}" 
                            placeholder="${field.placeholder || ''}" 
                            ${field.required ? 'required' : ''} class="wccrm-field-textarea"></textarea>`;

                case 'select':
                    let options = '<option value="">Select an option</option>';
                    if (field.options) {
                        Object.entries(field.options).forEach(([value, label]) => {
                            options += `<option value="${value}">${label}</option>`;
                        });
                    }
                    return `<select id="${field.name}" name="${field.name}" 
                            ${field.required ? 'required' : ''} class="wccrm-field-select">${options}</select>`;

                default:
                    return `<input type="text" id="${field.name}" name="${field.name}" 
                            placeholder="${field.placeholder || ''}" 
                            ${field.required ? 'required' : ''} class="wccrm-field-input">`;
            }
        }

        // Conditional Logic
        initConditionalLogic() {
            $('.wccrm-field-group[data-conditional]').each(function () {
                const $field = $(this);
                const conditions = JSON.parse($field.data('conditional'));

                // Initially hide conditional fields
                $field.hide();

                // Check conditions on page load
                this.checkConditionalField($field, conditions);
            }.bind(this));
        }

        handleConditionalFields(e) {
            const $changedField = $(e.target);
            const fieldName = $changedField.attr('name');

            // Find all conditional fields that depend on this field
            $(`.wccrm-field-group[data-conditional*="${fieldName}"]`).each(function () {
                const $field = $(this);
                const conditions = JSON.parse($field.data('conditional'));
                this.checkConditionalField($field, conditions);
            }.bind(this));
        }

        checkConditionalField($field, conditions) {
            let showField = true;

            conditions.forEach(condition => {
                const $dependentField = $(`[name="${condition.field}"]`);
                const dependentValue = $dependentField.val();

                switch (condition.operator) {
                    case 'equals':
                        if (dependentValue !== condition.value) showField = false;
                        break;
                    case 'not_equals':
                        if (dependentValue === condition.value) showField = false;
                        break;
                    case 'contains':
                        if (!dependentValue.includes(condition.value)) showField = false;
                        break;
                }
            });

            if (showField) {
                $field.slideDown();
            } else {
                $field.slideUp();
                // Clear field value when hidden
                $field.find('input, textarea, select').val('');
            }
        }

        // Form Tracking
        setupFormTracking() {
            $('.wccrm-dynamic-form').each(function () {
                const formId = $(this).data('form-id');
                if (window.wccrmFormTracking && window.wccrmFormTracking[formId]) {
                    this.trackingData[formId] = window.wccrmFormTracking[formId];
                }
            }.bind(this));
        }

        trackFieldInteraction(e) {
            const $field = $(e.target);
            const $form = $field.closest('.wccrm-dynamic-form');
            const formId = $form.data('form-id');

            if (!this.trackingData[formId]) {
                this.trackingData[formId] = { interactions: [] };
            }

            this.trackingData[formId].interactions.push({
                field: $field.attr('name'),
                timestamp: Date.now(),
                action: 'focus'
            });
        }

        trackConversion(formId, contactId) {
            // Track successful form conversion
            if (typeof gtag !== 'undefined') {
                gtag('event', 'form_submit', {
                    event_category: 'CRM',
                    event_label: formId,
                    value: contactId
                });
            }

            // Custom tracking event
            $(document).trigger('wccrm_form_converted', [formId, contactId]);
        }

        // Smart Fields based on page context
        initProgressiveFields() {
            if (typeof woocommerce !== 'undefined') {
                this.addWooCommerceContextFields();
            }
        }

        addWooCommerceContextFields() {
            // Add product-specific fields on product pages
            if ($('body').hasClass('single-product')) {
                const productId = this.getCurrentProductId();
                if (productId) {
                    $('.wccrm-form').each(function () {
                        if (!$(this).find('[name="product_interest"]').length) {
                            $(this).prepend(`<input type="hidden" name="product_interest" value="${productId}">`);
                        }
                    });
                }
            }
        }

        getCurrentProductId() {
            // Try to get product ID from various sources
            const productIdMeta = $('meta[property="product:id"]').attr('content');
            if (productIdMeta) return productIdMeta;

            const productIdData = $('.single-product').data('product-id');
            if (productIdData) return productIdData;

            return null;
        }
    }

    // Initialize when document is ready
    $(document).ready(function () {
        new WCCRMDynamicForms();
    });

})(jQuery);