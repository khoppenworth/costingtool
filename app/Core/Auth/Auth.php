<?php
declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Database\DB;

class Auth
{
    public function __construct(private DB $db)
    {
    }

    public function attempt(string $identity, string $password): bool
    {
        $user = $this->db->one(
            'SELECT * FROM users WHERE (email = :identity OR username = :identity) AND is_active = 1 LIMIT 1',
            ['identity' => $identity]
        );

        if (!$user || !Passwords::verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = (int) $user['id'];
        return true;
    }

    public function user(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return $this->db->one('SELECT * FROM users WHERE id = :id', ['id' => $_SESSION['user_id']]);
    }

    public function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }

    public function logout(): void
    {
        unset($_SESSION['user_id']);
    }
}
