<?php
declare(strict_types=1);

namespace App\Core\Database;

interface Seeder
{
    public function run(DB $db): void;
}
