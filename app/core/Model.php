<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base model - provides PDO connection and common DB helpers.
 * Concrete models extend this and define table name and logic.
 */
abstract class Model
{
    protected static ?\PDO $connection = null;

    /**
     * Get PDO instance (lazy connection via Database class).
     */
    protected static function db(): \PDO
    {
        if (self::$connection === null) {
            self::$connection = Database::getInstance()->getConnection();
        }
        return self::$connection;
    }

    /**
     * Override in child class to set table name.
     */
    abstract protected static function tableName(): string;

    /**
     * Fetch all rows from the model's table (optional WHERE).
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function fetchAll(string $where = '', array $params = []): array
    {
        $sql = 'SELECT * FROM ' . static::tableName();
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result !== false ? $result : [];
    }

    /**
     * Fetch one row by primary key (id).
     *
     * @return array<string, mixed>|null
     */
    protected static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM ' . static::tableName() . ' WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }
}
