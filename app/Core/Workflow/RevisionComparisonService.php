<?php
declare(strict_types=1);

namespace App\Core\Workflow;

use App\Core\Database\DB;

class RevisionComparisonService
{
    public function __construct(private DB $db)
    {
    }

    public function compare(int $assessmentId, int $fromRevision, int $toRevision): array
    {
        $rows = $this->db->all(
            'SELECT module_name, field_name, old_value, new_value, revision_number, created_at
             FROM change_logs
             WHERE assessment_id = :assessment_id
               AND (revision_number = :from_revision OR revision_number = :to_revision)
             ORDER BY module_name, field_name, revision_number, created_at',
            [
                'assessment_id' => $assessmentId,
                'from_revision' => $fromRevision,
                'to_revision' => $toRevision,
            ]
        );

        $grouped = [];
        foreach ($rows as $row) {
            $key = $row['module_name'] . ':' . $row['field_name'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'module_name' => $row['module_name'],
                    'field_name' => $row['field_name'],
                    'from_value' => null,
                    'to_value' => null,
                ];
            }

            if ((int) $row['revision_number'] === $fromRevision) {
                $grouped[$key]['from_value'] = $row['new_value'];
            }
            if ((int) $row['revision_number'] === $toRevision) {
                $grouped[$key]['to_value'] = $row['new_value'];
            }
        }

        return array_values($grouped);
    }
}
