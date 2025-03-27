<?php

namespace Maya\Database\Traits;

use Maya\Database\Model\MonitorQuery;
use PDOStatement;

trait HasQueryBuilder
{

    private $sql = '';
    protected $where = [];
    private $orderBy = [];
    private $limit = [];
    private $bindValues = [];
    private $ignoreDeletedAt = false;

    protected function setSql($query): void
    {
        $this->sql = $query;
    }

    protected function getSql(): string
    {
        return $this->sql;
    }

    protected function resetSql(): void
    {
        $this->sql = '';
    }

    protected function ignoreDeletedAt(): void
    {
        $this->ignoreDeletedAt = true;
    }

    protected function setWhere($operator, $condition): void
    {
        if ($this->ignoreDeletedAt) {
            if (strpos($condition, $this->getAttributeName($this->deletedAt) . ' IS NULL ') !== false) {
                return;
            }
        }
        $this->where[] = ['operator' => $operator, 'condition' => $condition];
    }

    protected function resetWhere(): void
    {
        $this->where = [];
    }

    protected function setOrderBy($name, $expression): void
    {
        $this->orderBy[] = $this->getAttributeName($name) . ' ' . $expression;
    }

    protected function resetOrderBy(): void
    {
        $this->orderBy = [];
    }

    protected function setLimit($from, $number): void
    {
        $driver = config("DATABASE.{$this->connection}.DRIVER");
        if ($driver === 'sqlsrv') {
            $this->limit['offset'] = (int)$from;
            $this->limit['fetch'] = (int)$number;
        } else {
            $this->limit['from'] = (int)$from;
            $this->limit['number'] = (int)$number;
        }
    }

    protected function resetLimit(): void
    {
        $driver = config("DATABASE.{$this->connection}.DRIVER");
        if ($driver === 'sqlsrv') {
            unset($this->limit['offset']);
            unset($this->limit['fetch']);
        } else {
            unset($this->limit['from']);
            unset($this->limit['number']);
        }
    }

    protected function addValue($value): void
    {
        $this->bindValues[] = $value;
    }

    protected function removeValues(): void
    {
        $this->bindValues = [];
    }

    protected function resetQuery(): void
    {
        $this->resetSql();
        $this->resetWhere();
        $this->resetOrderBy();
        $this->resetLimit();
        $this->removeValues();
        $this->ignoreDeletedAt = false;
    }

    protected function executeQuery(): bool|PDOStatement
    {
        $startTime = microtime(true);
        $query = $this->sql;

        if (!empty($this->where)) {
            $whereString = '';
            foreach ($this->where as $where) {
                $whereString == ''
                    ? $whereString .= $where['condition']
                    : $whereString .= ' ' . $where['operator'] . ' ' . $where['condition'];
            }
            $query .= ' WHERE ' . $whereString;
        }

        if (!empty($this->orderBy)) {
            $query .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        $driver = config("DATABASE.{$this->connection}.DRIVER");

        if (!empty($this->limit)) {
            if ($driver === 'sqlsrv') {
                if (empty($this->orderBy)) {
                    $query .= ' ORDER BY (SELECT NULL)';
                }
                $offset = $this->limit['offset'] ?? 0;
                $fetch = $this->limit['fetch'] ?? 0;
                $query .= " OFFSET {$offset} ROWS FETCH NEXT {$fetch} ROWS ONLY";
            } else {
                $from = $this->limit['from'] ?? 0;
                $number = $this->limit['number'] ?? 0;
                $query .= " LIMIT {$from}, {$number}";
            }
        }

        $query .= ' ;';
        $query = str_replace(['( OR', '( AND'], '( ', $query);
        $pdoInstance = $this->getConnection();
        $statement = $pdoInstance->prepare($query);
        sizeof($this->bindValues) > 0 ? $statement->execute($this->bindValues) : $statement->execute();
        $executionTime = microtime(true) - $startTime;
        $formattedTime = $executionTime < 1
            ? round($executionTime * 1000) . 'MS'
            : round($executionTime, 3) . 'S';
        MonitorQuery::logQueryToArray($query, $this->bindValues, $this->connection, $formattedTime);
        return $statement;
    }

    protected function getTableName(): string
    {
        $driver = config("DATABASE.{$this->connection}.DRIVER");
        return match ($driver) {
            'sqlsrv' => "[{$this->table}]",
            default => "`{$this->table}`",
        };
    }

    protected function getAttributeName($attribute): string
    {
        $driver = config("DATABASE.{$this->connection}.DRIVER");
        return match ($driver) {
            'sqlsrv' => "[{$this->table}].[{$attribute}]",
            default => "`{$this->table}`.`{$attribute}`",
        };
    }
}
