<?php
declare(strict_types=1);

use App\Core\Database\DB;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap/helpers.php';

$db = DB::make(config('database'));
$seeder = require base_path('database/seeders/DatabaseSeeder.php');
$seeder->run($db);

echo "Seed complete.\n";
