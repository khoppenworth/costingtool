<?php
declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Database\DB;

class PermissionService
{
    public function __construct(private DB $db)
    {
    }

    public function has(int $userId, string $permission): bool
    {
        $row = $this->db->one(
            'SELECT 1
             FROM user_role_assignments ura
             JOIN role_permissions rp ON rp.role_id = ura.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE ura.user_id = :user_id AND p.permission_key = :permission
             LIMIT 1',
            ['user_id' => $userId, 'permission' => $permission]
        );
        return $row !== null;
    }
}
