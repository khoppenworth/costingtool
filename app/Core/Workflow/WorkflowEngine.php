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

    public function editableStatuses(): array
    {
        return ['draft', 'returned'];
    }

    public function canEdit(string $status): bool
    {
        return in_array($status, $this->editableStatuses(), true);
    }
}
