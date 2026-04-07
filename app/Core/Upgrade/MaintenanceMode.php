<?php
declare(strict_types=1);

namespace App\Core\Upgrade;

class MaintenanceMode
{
    public static function filePath(): string
    {
        return base_path((string) env('APP_MAINTENANCE_FILE', 'storage/cache/maintenance.flag'));
    }

    public static function enable(): void
    {
        $file = self::filePath();
        @mkdir(dirname($file), 0777, true);
        file_put_contents($file, (string) time());
    }

    public static function disable(): void
    {
        $file = self::filePath();
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public static function enabled(): bool
    {
        return file_exists(self::filePath());
    }
}
