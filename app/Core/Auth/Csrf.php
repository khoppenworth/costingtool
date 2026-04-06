<?php
declare(strict_types=1);

namespace App\Core\Auth;

use RuntimeException;

class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function validate(?string $token): void
    {
        if (!$token || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
            throw new RuntimeException('Invalid CSRF token.');
        }
    }
}
