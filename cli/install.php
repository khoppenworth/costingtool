<?php
declare(strict_types=1);

use App\Core\Database\DB;
use App\Core\Database\Migrator;

$root = dirname(__DIR__);
$vendorAutoload = $root . '/vendor/autoload.php';

if (!file_exists($vendorAutoload)) {
    fwrite(STDERR, "[FAIL] Missing vendor/autoload.php. Run: composer install\n");
    exit(1);
}

require $vendorAutoload;
require $root . '/bootstrap/helpers.php';

$skipSeed = in_array('--skip-seed', $argv, true);

if (!file_exists(base_path('.env'))) {
    fwrite(STDERR, "[FAIL] Missing .env file. Create it from .env.example first.\n");
    exit(1);
}

foreach (['pdo', 'pdo_mysql', 'json', 'mbstring', 'fileinfo'] as $extension) {
    if (!extension_loaded($extension)) {
        fwrite(STDERR, "[FAIL] Missing required PHP extension: {$extension}\n");
        exit(1);
    }
}

try {
    $db = DB::make(config('database'));
} catch (Throwable $e) {
    fwrite(STDERR, "[FAIL] Unable to connect to database: {$e->getMessage()}\n");
    exit(1);
}

echo "[OK] Database connection successful.\n";

$migrator = new Migrator($db);
$migrator->run(base_path('database/migrations'));
$migrator->run(base_path('database/views'));

echo "[OK] Migrations complete.\n";

if ($skipSeed) {
    echo "[SKIP] Seeder skipped (--skip-seed).\n";
    exit(0);
}

$seeder = require base_path('database/seeders/DatabaseSeeder.php');
$seeder->run($db);

echo "[OK] Seed complete.\n";
