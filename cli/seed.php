<?php
declare(strict_types=1);

use App\Core\Database\DB;

$root = dirname(__DIR__);
$vendorAutoload = $root . '/vendor/autoload.php';

if (!file_exists($vendorAutoload)) {
    fwrite(STDERR, "[FAIL] Missing vendor/autoload.php. Run: composer install\n");
    exit(1);
}

require $vendorAutoload;
require $root . '/bootstrap/helpers.php';

if (!file_exists(base_path('.env'))) {
    fwrite(STDERR, "[FAIL] Missing .env file. Create it from .env.example first.\n");
    exit(1);
}

try {
    $db = DB::make(config('database'));
    $seeder = require base_path('database/seeders/DatabaseSeeder.php');
    $seeder->run($db);
} catch (Throwable $e) {
    fwrite(STDERR, "[FAIL] Seeder failed: {$e->getMessage()}\n");
    exit(1);
}

echo "[OK] Seed complete.\n";
