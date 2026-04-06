<?php
declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use PDOStatement;

class DB
{
    private PDO $pdo;

    public static function make(array $config): self
    {
        $instance = new self();
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
        $instance->pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        return $instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function statement(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    public function all(string $sql, array $params = []): array
    {
        return $this->statement($sql, $params)->fetchAll();
    }

    public function one(string $sql, array $params = []): ?array
    {
        $result = $this->statement($sql, $params)->fetch();
        return $result === false ? null : $result;
    }

    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', array_map(fn ($column) => ':' . $column, $columns))
        );
        $this->statement($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): void
    {
        $set = implode(', ', array_map(fn ($column) => $column . '=:' . $column, array_keys($data)));
        $this->statement(
            "UPDATE {$table} SET {$set} WHERE {$where}",
            array_merge($data, $whereParams)
        );
    }
}
