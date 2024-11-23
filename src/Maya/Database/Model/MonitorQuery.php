<?php

namespace Maya\Database\Model;

class MonitorQuery
{
    protected static array $queryLogs = [];

    public static function logQueryToArray(string $query, array $bindValues, $connection): void
    {
        self::$queryLogs[] = [
            'connection' => $connection,
            'query' => $query,
            'bindings' => $bindValues,
        ];
    }
    public static function getQueryLogs(): array
    {
        return self::$queryLogs;
    }
}
