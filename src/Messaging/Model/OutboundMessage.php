<?php

namespace Anas\WCCRM\Messaging\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Outbound message model for Phase 2C
 */
class OutboundMessage {

    public int $id;
    public string $type;
    public string $channel;
    public array $recipient;
    public array $content;
    public string $status;
    public string $priority;
    public array $metadata;
    public ?string $scheduled_at;
    public ?string $sent_at;
    public ?string $delivered_at;
    public ?string $failed_at;
    public ?string $error_message;
    public int $retry_count;
    public int $max_retries;

    public function __construct( array $data = [] ) {
        $this->id = (int) ( $data['id'] ?? 0 );
        $this->type = sanitize_text_field( $data['type'] ?? 'notification' );
        $this->channel = sanitize_text_field( $data['channel'] ?? 'email' );
        $this->recipient = $data['recipient'] ?? [];
        $this->content = $data['content'] ?? [];
        $this->status = sanitize_text_field( $data['status'] ?? 'pending' );
        $this->priority = sanitize_text_field( $data['priority'] ?? 'normal' );
        $this->metadata = $data['metadata'] ?? [];
        $this->scheduled_at = $data['scheduled_at'] ?? null;
        $this->sent_at = $data['sent_at'] ?? null;
        $this->delivered_at = $data['delivered_at'] ?? null;
        $this->failed_at = $data['failed_at'] ?? null;
        $this->error_message = $data['error_message'] ?? null;
        $this->retry_count = (int) ( $data['retry_count'] ?? 0 );
        $this->max_retries = (int) ( $data['max_retries'] ?? 3 );
    }

    /**
     * Check if message can be sent now
     */
    public function can_send(): bool {
        if ( $this->status !== 'pending' && $this->status !== 'retrying' ) {
            return false;
        }

        if ( $this->scheduled_at && strtotime( $this->scheduled_at ) > time() ) {
            return false;
        }

        return true;
    }

    /**
     * Check if message can be retried
     */
    public function can_retry(): bool {
        return $this->status === 'failed' && $this->retry_count < $this->max_retries;
    }

    /**
     * Mark message as sent
     */
    public function mark_sent(): void {
        $this->status = 'sent';
        $this->sent_at = current_time( 'mysql' );
    }

    /**
     * Mark message as delivered
     */
    public function mark_delivered(): void {
        $this->status = 'delivered';
        $this->delivered_at = current_time( 'mysql' );
    }

    /**
     * Mark message as failed
     */
    public function mark_failed( string $error_message = '' ): void {
        $this->status = 'failed';
        $this->failed_at = current_time( 'mysql' );
        $this->error_message = $error_message;
        $this->retry_count++;
    }

    /**
     * Reset for retry
     */
    public function prepare_retry(): void {
        $this->status = 'retrying';
        $this->error_message = null;
        $this->failed_at = null;
    }

    /**
     * Convert to array for storage
     */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'content' => $this->content,
            'status' => $this->status,
            'priority' => $this->priority,
            'metadata' => $this->metadata,
            'scheduled_at' => $this->scheduled_at,
            'sent_at' => $this->sent_at,
            'delivered_at' => $this->delivered_at,
            'failed_at' => $this->failed_at,
            'error_message' => $this->error_message,
            'retry_count' => $this->retry_count,
            'max_retries' => $this->max_retries,
        ];
    }
}