<?php
declare(strict_types=1);

namespace App\Core\Database;

interface Migration
{
    public function up(DB $db): void;
}
