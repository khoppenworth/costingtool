<?php
declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Database\DB;

class ScopeService
{
    public function __construct(private DB $db)
    {
    }

    public function isSuperAdmin(int $userId): bool
    {
        $role = $this->db->one(
            'SELECT 1
             FROM user_role_assignments ura
             JOIN roles r ON r.id = ura.role_id
             WHERE ura.user_id = :user_id AND r.name = :name
             LIMIT 1',
            ['user_id' => $userId, 'name' => 'Super Admin']
        );
        return $role !== null;
    }

    public function organizationIdsForUser(int $userId): array
    {
        if ($this->isSuperAdmin($userId)) {
            return array_map(
                static fn (array $row): int => (int) $row['id'],
                $this->db->all('SELECT id FROM organizations WHERE is_active = 1')
            );
        }

        $rows = $this->db->all(
            'SELECT organization_id
             FROM user_organization_scopes
             WHERE user_id = :user_id AND is_active = 1',
            ['user_id' => $userId]
        );

        return array_map(static fn (array $row): int => (int) $row['organization_id'], $rows);
    }

    public function canAccessAssessment(int $userId, int $assessmentId): bool
    {
        if ($this->isSuperAdmin($userId)) {
            return true;
        }

        $assessment = $this->db->one(
            'SELECT organization_id FROM assessments WHERE id = :id LIMIT 1',
            ['id' => $assessmentId]
        );
        if (!$assessment) {
            return false;
        }

        return in_array((int) $assessment['organization_id'], $this->organizationIdsForUser($userId), true);
    }
}
