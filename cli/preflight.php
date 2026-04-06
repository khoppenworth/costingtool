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

$skipDb = in_array('--skip-db', $argv, true);
$failures = 0;

$check = function (bool $ok, string $passMessage, string $failMessage) use (&$failures): void {
    if ($ok) {
        echo "[OK] {$passMessage}\n";
        return;
    }

    echo "[FAIL] {$failMessage}\n";
    $failures++;
};

$check(file_exists(base_path('.env')), '.env file exists', '.env file is missing. Create it from .env.example.');

foreach (['pdo', 'pdo_mysql', 'json', 'mbstring', 'fileinfo'] as $extension) {
    $check(extension_loaded($extension), "PHP extension loaded: {$extension}", "Missing PHP extension: {$extension}");
}

foreach (['storage/cache', 'storage/logs', 'storage/uploads', 'storage/upgrades'] as $relativePath) {
    $absolutePath = base_path($relativePath);
    $check(is_dir($absolutePath), "Directory exists: {$relativePath}", "Missing directory: {$relativePath}");
    $check(is_writable($absolutePath), "Directory writable: {$relativePath}", "Directory not writable: {$relativePath}");
}

foreach (['APP_URL', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'] as $envKey) {
    $value = env($envKey, null);
    $check($value !== null && $value !== '', "Env key set: {$envKey}", "Env key is empty or missing: {$envKey}");
}

if ($skipDb) {
    echo "[SKIP] Database connectivity check skipped (--skip-db).\n";
} else {
    try {
        DB::make(config('database'));
        echo "[OK] Database connection successful.\n";
    } catch (Throwable $e) {
        echo "[FAIL] Database connection failed: {$e->getMessage()}\n";
        $failures++;
    }
}

if ($failures > 0) {
    echo "\nPreflight failed with {$failures} issue(s).\n";
    exit(1);
}

echo "\nPreflight passed.\n";
