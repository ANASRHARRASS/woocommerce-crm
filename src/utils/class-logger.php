<?php

class Logger {
    private $log_file;

    public function __construct($log_file = 'plugin.log') {
        $this->log_file = $log_file;
    }

    public function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $formatted_message = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->log_file, $formatted_message, FILE_APPEND);
    }

    public function clear() {
        file_put_contents($this->log_file, '');
    }

    public function get_logs() {
        return file_exists($this->log_file) ? file_get_contents($this->log_file) : '';
    }
}