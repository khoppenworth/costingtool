<?php
declare(strict_types=1);

namespace App\Core\Audit;

use App\Core\Database\DB;

class ChangeTracker
{
    public function __construct(private DB $db)
    {
    }

    public function track(int $assessmentId, string $module, string $fieldName, mixed $oldValue, mixed $newValue, int $userId, int $revisionNumber = 1, ?string $reason = null): void
    {
        $this->db->insert('change_logs', [
            'assessment_id' => $assessmentId,
            'module_name' => $module,
            'field_name' => $fieldName,
            'old_value' => (string) ($oldValue ?? ''),
            'new_value' => (string) ($newValue ?? ''),
            'user_id' => $userId,
            'revision_number' => $revisionNumber,
            'reason' => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
