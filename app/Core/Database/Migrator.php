<?php
declare(strict_types=1);

namespace App\Core\Database;

use RuntimeException;
use Throwable;

class Migrator
{
    public function __construct(private DB $db)
    {
    }

    public function run(string $migrationPath): void
    {
        if (!is_dir($migrationPath)) {
            throw new RuntimeException("Migration path does not exist: {$migrationPath}");
        }

        $this->db->statement('CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at DATETIME NOT NULL
        )');

        $executed = array_column($this->db->all('SELECT migration_name FROM schema_migrations'), 'migration_name');
        $files = glob($migrationPath . '/*.{php,sql}', GLOB_BRACE);
        sort($files);

        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $executed, true)) {
                continue;
            }

            $pdo = $this->db->pdo();
            $pdo->beginTransaction();
            try {
                if (str_ends_with($file, '.sql')) {
                    $sql = file_get_contents($file);
                    if ($sql === false) {
                        throw new RuntimeException("Unable to read migration file: {$name}");
                    }
                    $pdo->exec($sql);
                } else {
                    $migration = require $file;
                    if (!$migration instanceof Migration) {
                        throw new RuntimeException("Invalid migration [{$name}], expected " . Migration::class);
                    }
                    $migration->up($this->db);
                }

                $this->db->insert('schema_migrations', [
                    'migration_name' => $name,
                    'executed_at' => date('Y-m-d H:i:s'),
                ]);

                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw new RuntimeException("Migration failed [{$name}]: {$e->getMessage()}", 0, $e);
            }
        }
    }
}
