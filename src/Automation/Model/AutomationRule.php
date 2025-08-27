<?php

namespace Anas\WCCRM\Automation\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Automation rule model for Phase 2F
 */
class AutomationRule {

    public int $id;
    public string $name;
    public string $trigger_event;
    public array $conditions;
    public array $actions;
    public bool $is_active;
    public int $priority;
    public array $metadata;

    public function __construct( array $data = [] ) {
        $this->id = (int) ( $data['id'] ?? 0 );
        $this->name = sanitize_text_field( $data['name'] ?? '' );
        $this->trigger_event = sanitize_text_field( $data['trigger_event'] ?? '' );
        $this->conditions = $data['conditions'] ?? [];
        $this->actions = $data['actions'] ?? [];
        $this->is_active = (bool) ( $data['is_active'] ?? true );
        $this->priority = (int) ( $data['priority'] ?? 10 );
        $this->metadata = $data['metadata'] ?? [];
    }

    /**
     * Convert to array for storage
     */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'trigger_event' => $this->trigger_event,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'metadata' => $this->metadata,
        ];
    }
}