<?php
declare(strict_types=1);

namespace App\Core\Support;

class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $logPath = storage_path('logs/app.log');
        @mkdir(dirname($logPath), 0777, true);
        $payload = [
            'time' => date('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        file_put_contents($logPath, json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
