<?php
declare(strict_types=1);

use App\Core\Database\DB;
use App\Core\Database\Migrator;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap/helpers.php';

$db = DB::make(config('database'));
$migrator = new Migrator($db);
$migrator->run(base_path('database/migrations'));
$migrator->run(base_path('database/views'));

echo "Migrations complete.\n";
