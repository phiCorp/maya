<?php

namespace Maya\Database\Connection;

use InvalidArgumentException;
use PDO;
use PDOException;

class Connection
{
    private static array $connections = [];

    private function __construct() {}

    public static function getConnection(string $connectionName = 'default'): PDO
    {
        if (!isset(self::$connections[$connectionName])) {
            self::$connections[$connectionName] = (new Connection)->dbConnection($connectionName);
        }
        return self::$connections[$connectionName];
    }

    private function dbConnection(string $connectionName): PDO
    {
        $config = config("DATABASE.$connectionName");

        if (!$config) {
            throw new InvalidArgumentException("Database configuration '$connectionName' not found.");
        }

        $driver = $config['DRIVER'] ?? 'mysql';
        $dsn = match ($driver) {
            'mysql' => "mysql:host={$config['SERVER_NAME']};dbname={$config['DB_NAME']};charset=utf8",
            'sqlsrv' => "sqlsrv:Server={$config['SERVER_NAME']};Database={$config['DB_NAME']}",
            default => throw new InvalidArgumentException("Unsupported database driver '$driver'.")
        };

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        if ($driver === 'mysql' && !empty($config['APPLY_TIMEZONE']) && $config['APPLY_TIMEZONE'] === true) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES UTF8;SET time_zone = '{$config['TIMEZONE']}'";
        }

        try {
            return new PDO($dsn, $config['USERNAME'], $config['PASSWORD'], $options);
        } catch (PDOException $e) {
            throw new PDOException("Error connecting to database '$connectionName': " . $e->getMessage());
        }
    }

    public static function lastInsertID(string $connectionName = 'default'): string
    {
        if (!isset(self::$connections[$connectionName])) {
            throw new InvalidArgumentException("Connection '$connectionName' is not established.");
        }

        return self::$connections[$connectionName]->lastInsertId();
    }


    public static function closeConnection(string $connectionName = 'default'): void
    {
        if (isset(self::$connections[$connectionName])) {
            self::$connections[$connectionName] = null;
        }
    }

    public static function closeAllConnections(): void
    {
        self::$connections = [];
    }
}
