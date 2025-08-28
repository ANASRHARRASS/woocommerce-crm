<?php

namespace Anas\WCCRM\Automation;

use Anas\WCCRM\Automation\Executor\ActionExecutor;
use Anas\WCCRM\Automation\Conditions\ConditionEvaluator;

defined('ABSPATH') || exit;

class AutomationRunner
{
    private AutomationRepository $repo;
    private ConditionEvaluator $conditions;
    private ActionExecutor $actions;
    public function __construct(AutomationRepository $r, ConditionEvaluator $c, ActionExecutor $a)
    {
        $this->repo = $r;
        $this->conditions = $c;
        $this->actions = $a;
    }
    public function on_generic_event($event_type, $payload): void
    {
        foreach ($this->repo->all() as $rule) {
            if ($this->conditions->evaluate($rule['conditions'] ?? [], ['event' => $event_type, 'payload' => $payload])) {
                foreach ($rule['actions'] ?? [] as $action) {
                    $this->actions->execute($action, ['event' => $event_type, 'payload' => $payload]);
                }
            }
        }
    }
}
