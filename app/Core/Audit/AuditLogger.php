<?php
declare(strict_types=1);

namespace App\Core\Audit;

use App\Core\Database\DB;

class AuditLogger
{
    public function __construct(private DB $db)
    {
    }

    public function log(?int $userId, string $action, string $entityType, ?int $entityId, array $metadata = []): void
    {
        $this->db->insert('audit_logs', [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata_json' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
