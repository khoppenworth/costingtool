<?php
declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Database\DB;

class PermissionService
{
    private array $cache = [];

    public function __construct(private DB $db)
    {
    }

    public function has(int $userId, string $permission): bool
    {
        return in_array($permission, $this->permissionsForUser($userId), true);
    }

    public function permissionsForUser(int $userId): array
    {
        if (array_key_exists($userId, $this->cache)) {
            return $this->cache[$userId];
        }

        $rows = $this->db->all(
            'SELECT DISTINCT p.permission_key
             FROM user_role_assignments ura
             JOIN role_permissions rp ON rp.role_id = ura.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE ura.user_id = :user_id',
            ['user_id' => $userId]
        );

        return $this->cache[$userId] = array_column($rows, 'permission_key');
    }
}
