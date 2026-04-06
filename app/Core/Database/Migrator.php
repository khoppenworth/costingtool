<?php
declare(strict_types=1);

namespace App\Core\Database;

class Migrator
{
    public function __construct(private DB $db)
    {
    }

    public function run(string $migrationPath): void
    {
        $this->db->statement('CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at DATETIME NOT NULL
        )');

        $executed = array_column($this->db->all('SELECT migration_name FROM schema_migrations'), 'migration_name');
        $files = glob($migrationPath . '/*');
        sort($files);

        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $executed, true)) {
                continue;
            }

            if (str_ends_with($file, '.sql')) {
                $this->db->pdo()->exec(file_get_contents($file));
            } else {
                $migration = require $file;
                $migration->up($this->db);
            }

            $this->db->insert('schema_migrations', [
                'migration_name' => $name,
                'executed_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
