<?php

namespace Anas\WCCRM\Reporting;

defined('ABSPATH') || exit;

class DashboardController
{
    private MetricsAggregator $metrics;
    public function __construct(MetricsAggregator $m)
    {
        $this->metrics = $m;
    }
    public function ajax_fetch_metrics(): void
    {
        wp_send_json_success($this->metrics->collect());
    }
}
