<?php

namespace Maya\Database\Model;

class MonitorQuery
{
    protected static array $queryLogs = [];

    public static function logQueryToArray(string $query, array $bindValues, $connection, $executionTime): void
    {
        self::$queryLogs[] = [
            'Query took' => $executionTime,
            'Query' => $query,
            'Connection' => $connection,
            'Bindings' => $bindValues,
        ];
    }

    public static function getQueryLogs(): array
    {
        return self::$queryLogs;
    }
}
