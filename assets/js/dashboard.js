/**
 * Dashboard JavaScript enhancements for v0.5.0
 * Handles date range selector and new metrics
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize dashboard enhancements
        initDateRangeSelector();
        initMetricsRefresh();
        initChartUpdates();
    });

    /**
     * Initialize date range selector
     */
    function initDateRangeSelector() {
        $('#kscrm-date-range').on('change', function() {
            const selectedRange = $(this).val();
            
            // Save selection
            saveUserPreference('dashboard_date_range', selectedRange);
            
            // Update dashboard metrics
            updateDashboardMetrics(selectedRange);
        });

        // Load saved preference
        const savedRange = getUserPreference('dashboard_date_range', '30');
        $('#kscrm-date-range').val(savedRange);
    }

    /**
     * Initialize metrics refresh functionality
     */
    function initMetricsRefresh() {
        $('.kscrm-refresh-metrics').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            button.prop('disabled', true).text('Refreshing...');
            
            const dateRange = $('#kscrm-date-range').val();
            updateDashboardMetrics(dateRange, function() {
                button.prop('disabled', false).text('Refresh');
            });
        });
    }

    /**
     * Initialize chart updates
     */
    function initChartUpdates() {
        // This would integrate with Chart.js or similar library
        // Placeholder for chart initialization
        $('.kscrm-chart-container').each(function() {
            const chartType = $(this).data('chart-type');
            const chartId = $(this).attr('id');
            
            // Initialize chart based on type
            if (typeof Chart !== 'undefined') {
                initChart(chartId, chartType);
            }
        });
    }

    /**
     * Update dashboard metrics for selected date range
     */
    function updateDashboardMetrics(dateRange, callback) {
        $('.kscrm-dashboard-container').addClass('kscrm-loading');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kscrm_get_dashboard_metrics',
                date_range: dateRange,
                nonce: kscrm_dashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateMetricsDisplay(response.data);
                    updateCharts(response.data);
                }
            },
            error: function() {
                console.error('Failed to update dashboard metrics');
            },
            complete: function() {
                $('.kscrm-dashboard-container').removeClass('kscrm-loading');
                if (callback) callback();
            }
        });
    }

    /**
     * Update metrics display
     */
    function updateMetricsDisplay(data) {
        // Update AOV
        $('.kscrm-metric[data-metric="aov"] .kscrm-metric-value').text(data.aov || '0');
        
        // Update Repeat Purchase Rate
        $('.kscrm-metric[data-metric="repeat_rate"] .kscrm-metric-value').text(data.repeat_rate || '0%');
        
        // Update Top Product
        $('.kscrm-metric[data-metric="top_product"] .kscrm-metric-value').text(data.top_product || 'N/A');
        
        // Update Returning Customers
        $('.kscrm-metric[data-metric="returning_customers"] .kscrm-metric-value').text(data.returning_customers || '0');
        
        // Update percentage changes
        updateMetricChanges(data);
    }

    /**
     * Update metric change indicators
     */
    function updateMetricChanges(data) {
        $('.kscrm-metric-change').each(function() {
            const metric = $(this).closest('.kscrm-metric').data('metric');
            const change = data.changes && data.changes[metric];
            
            if (change !== undefined) {
                const changeText = change > 0 ? '+' + change + '%' : change + '%';
                const changeClass = change > 0 ? 'positive' : (change < 0 ? 'negative' : '');
                
                $(this).text(changeText).removeClass('positive negative').addClass(changeClass);
            }
        });
    }

    /**
     * Update charts with new data
     */
    function updateCharts(data) {
        // Revenue vs AOV chart
        if (window.revenueAovChart && data.revenue_aov_data) {
            window.revenueAovChart.data = data.revenue_aov_data;
            window.revenueAovChart.update();
        }
        
        // Returning vs New Customers chart
        if (window.customersChart && data.customers_data) {
            window.customersChart.data = data.customers_data;
            window.customersChart.update();
        }
    }

    /**
     * Initialize a chart
     */
    function initChart(chartId, chartType) {
        const ctx = document.getElementById(chartId);
        if (!ctx) return;
        
        // Basic chart configuration
        const config = {
            type: chartType,
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        };
        
        // Store chart instance globally for updates
        window[chartId + 'Chart'] = new Chart(ctx, config);
    }

    /**
     * Save user preference
     */
    function saveUserPreference(key, value) {
        $.post(ajaxurl, {
            action: 'kscrm_save_user_preference',
            key: key,
            value: value,
            nonce: kscrm_dashboard.nonce
        });
    }

    /**
     * Get user preference
     */
    function getUserPreference(key, defaultValue) {
        // This would typically be passed from PHP
        const preferences = window.kscrm_user_preferences || {};
        return preferences[key] || defaultValue;
    }

    /**
     * Format currency values
     */
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    /**
     * Format percentage values
     */
    function formatPercentage(value) {
        return parseFloat(value).toFixed(1) + '%';
    }

})(jQuery);