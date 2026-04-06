<?php
declare(strict_types=1);

namespace App\Core\Upgrade;

class MaintenanceMode
{
    public static function enable(): void
    {
        $file = base_path(env('APP_MAINTENANCE_FILE', 'storage/cache/maintenance.flag'));
        @mkdir(dirname($file), 0777, true);
        file_put_contents($file, (string) time());
    }

    public static function disable(): void
    {
        $file = base_path(env('APP_MAINTENANCE_FILE', 'storage/cache/maintenance.flag'));
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
