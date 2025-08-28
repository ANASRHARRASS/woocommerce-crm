/**
 * WooCommerce CRM Tools Page JavaScript
 * Handles export functionality and UI interactions
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Export button handlers
        $('.kscrm-export-btn').on('click', function(e) {
            e.preventDefault();
            
            const exportType = $(this).data('export');
            const button = $(this);
            
            handleExport(exportType, button);
        });
    });

    /**
     * Handle data export
     */
    function handleExport(exportType, button) {
        const progressContainer = $('#kscrm-export-progress');
        const progressText = progressContainer.find('.kscrm-progress-text');
        const progressFill = progressContainer.find('.kscrm-progress-fill');
        
        // Disable button and show progress
        button.prop('disabled', true);
        progressContainer.show();
        progressText.text(kscrm_tools.strings.export_started);
        progressFill.css('width', '30%');

        // Make AJAX request to get download URL
        $.ajax({
            url: kscrm_tools.ajax_url,
            type: 'POST',
            data: {
                action: 'kscrm_export_data',
                export_type: exportType,
                format: getExportFormat(exportType),
                nonce: kscrm_tools.nonce
            },
            success: function(response) {
                if (response.success) {
                    progressFill.css('width', '100%');
                    progressText.text(kscrm_tools.strings.export_completed);
                    
                    // Trigger download
                    window.location.href = response.data.download_url;
                    
                    setTimeout(function() {
                        progressContainer.hide();
                        button.prop('disabled', false);
                    }, 2000);
                } else {
                    showExportError(response.data?.message || kscrm_tools.strings.export_error);
                    button.prop('disabled', false);
                    progressContainer.hide();
                }
            },
            error: function() {
                showExportError(kscrm_tools.strings.export_error);
                button.prop('disabled', false);
                progressContainer.hide();
            }
        });
    }

    /**
     * Get export format based on type
     */
    function getExportFormat(exportType) {
        return exportType === 'news' ? 'json' : 'csv';
    }

    /**
     * Show export error
     */
    function showExportError(message) {
        const notice = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }

})(jQuery);