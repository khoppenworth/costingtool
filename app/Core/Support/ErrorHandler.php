<?php
declare(strict_types=1);

namespace App\Core\Support;

use Throwable;

class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler(static function (Throwable $e): void {
            self::report($e);
            http_response_code(500);
            echo config('app.debug') ? '<pre>' . e($e->getMessage() . "\n\n" . $e->getTraceAsString()) . '</pre>' : 'Application error';
        });
    }

    public static function report(Throwable $e): void
    {
        Logger::error($e->getMessage(), [
            'type' => $e::class,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ]);
    }
}
