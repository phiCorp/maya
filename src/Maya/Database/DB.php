<?php

namespace Maya\Database;

use Maya\Database\Connection\Connection;
use Maya\Database\Model\Model;
use PDO;


class DB extends Model
{
    protected string $table = "";
    public static function tableExist($name): bool
    {
        $stmt = Connection::getConnection()->prepare("SHOW TABLES LIKE :table_name");
        $stmt->execute([':table_name' => $name]);
        return ($stmt->rowCount() == 1);
    }

    public static function lastInsertID(): bool|string
    {
        return Connection::lastInsertID();
    }

    public static function table($tableName): static
    {
        $instance = new static();
        $instance->table = $tableName;
        return $instance;
    }

    public static function dropTable(string $tableName): bool
    {
        $stmt = Connection::getConnection()->prepare("DROP TABLE IF EXISTS `$tableName`");
        return $stmt->execute();
    }

    public static function createTable(string $tableName, array $columns): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS $tableName (";
        foreach ($columns as $columnName => $columnDef) {
            $sql .= "$columnName $columnDef, ";
        }
        $sql = rtrim($sql, ', ');
        $sql .= ") COLLATE utf8_general_ci";
        $stmt = Connection::getConnection()->prepare($sql);
        return $stmt->execute();
    }

    public static function dropAllTables(): bool
    {
        $stmt = Connection::getConnection()->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            Connection::getConnection()->exec("DROP TABLE $table");
        }

        return true;
    }

    public static function addColumn(string $tableName, string $columnName, string $columnDef, string $afterColumn = ''): bool
    {
        $sql = "ALTER TABLE $tableName ADD COLUMN IF NOT EXISTS $columnName $columnDef";
        if ($afterColumn !== '') {
            $sql .= " AFTER $afterColumn";
        }
        $stmt = Connection::getConnection()->prepare($sql);
        return $stmt->execute();
    }
}
