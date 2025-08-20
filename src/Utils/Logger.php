<?php

namespace WooCommerceCRMPlugin\Utils;

class Logger {
    private $logFile;

    public function __construct($logFile = 'woocommerce-crm-plugin.log') {
        $this->logFile = $logFile;
    }

    public function log($message) {
        $timestamp = date("Y-m-d H:i:s");
        $formattedMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $formattedMessage, FILE_APPEND);
    }

    public function clearLog() {
        file_put_contents($this->logFile, '');
    }
}