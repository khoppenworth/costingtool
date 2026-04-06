<?php
declare(strict_types=1);

namespace App\Core\Workflow;

class WorkflowEngine
{
    public function canTransition(string $from, string $to): bool
    {
        $transitions = config('workflow.transitions', []);
        return in_array($to, $transitions[$from] ?? [], true);
    }
}
