<?php

namespace Maya\Database\Migrations;

use PDO;

class Schema
{
    protected string $table;
    protected array $columns = [];
    protected array $constraints = [];
    protected PDO $connection;
    protected string $engine = 'InnoDB';
    protected string $charset = 'utf8mb4';

    public function __construct(PDO $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    public function id(string $name = 'id')
    {
        $this->columns[] = "$name INT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }

    public function string(string $name, int $length = 255, bool $nullable = true, ?string $default = null)
    {
        $column = "$name VARCHAR($length)";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT '$default'";
        $this->columns[] = $column;
        return $this;
    }

    public function integer(string $name, bool $unsigned = false, bool $nullable = true, ?int $default = null)
    {
        $column = "$name INT";
        if ($unsigned) $column .= " UNSIGNED";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT $default";
        $this->columns[] = $column;
        return $this;
    }

    public function boolean(string $name, bool $nullable = true, ?bool $default = null)
    {
        $column = "$name BOOLEAN";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT " . ($default ? 'TRUE' : 'FALSE');
        $this->columns[] = $column;
        return $this;
    }

    public function text(string $name, bool $nullable = true)
    {
        $column = "$name TEXT";
        if (!$nullable) $column .= " NOT NULL";
        $this->columns[] = $column;
        return $this;
    }

    public function timestamps()
    {
        $this->columns[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP";
        return $this;
    }

    public function unique(string $column)
    {
        $this->constraints[] = "UNIQUE ($column)";
        return $this;
    }

    public function foreign(string $column, string $referencedTable, string $referencedColumn, string $onDelete = 'CASCADE', string $onUpdate = 'CASCADE')
    {
        $this->constraints[] = "FOREIGN KEY ($column) REFERENCES $referencedTable($referencedColumn) ON DELETE $onDelete ON UPDATE $onUpdate";
        return $this;
    }

    public function custom(string $definition)
    {
        $this->columns[] = $definition;
        return $this;
    }

    public function enum(string $name, array $values, bool $nullable = true, ?string $default = null)
    {
        $valueList = "'" . implode("', '", $values) . "'";
        $column = "$name ENUM($valueList)";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT '$default'";
        $this->columns[] = $column;
        return $this;
    }

    public function index(string $column, string $name = null)
    {
        $name = $name ?? "{$this->table}_{$column}_index";
        $this->constraints[] = "INDEX $name ($column)";
        return $this;
    }

    public function check(string $expression)
    {
        $this->constraints[] = "CHECK ($expression)";
        return $this;
    }

    public function engine(string $engine)
    {
        $this->engine = $engine;
        return $this;
    }

    public function charset(string $charset)
    {
        $this->charset = $charset;
        return $this;
    }

    public function buildCreateQuery(): string
    {
        $columns = implode(", ", $this->columns);
        $constraints = implode(", ", $this->constraints);
        $all = $columns;
        if (!empty($constraints)) {
            $all .= ", " . $constraints;
        }
        return "CREATE TABLE IF NOT EXISTS {$this->table} ($all) ENGINE={$this->engine} CHARSET={$this->charset}";
    }

    public function create()
    {
        $query = $this->buildCreateQuery();
        $this->connection->exec($query);
    }

    public function drop()
    {
        $query = "DROP TABLE IF EXISTS {$this->table}";
        $this->connection->exec($query);
    }

    public function addColumn(string $definition)
    {
        $query = "ALTER TABLE {$this->table} ADD $definition";
        $this->connection->exec($query);
        return $this;
    }

    public function dropColumn(string $column)
    {
        $query = "ALTER TABLE {$this->table} DROP COLUMN $column";
        $this->connection->exec($query);
        return $this;
    }

    public function renameColumn(string $oldName, string $newName, string $type)
    {
        $query = "ALTER TABLE {$this->table} CHANGE $oldName $newName $type";
        $this->connection->exec($query);
        return $this;
    }

    public function rename(string $newName)
    {
        $query = "RENAME TABLE {$this->table} TO $newName";
        $this->connection->exec($query);
        $this->table = $newName;
        return $this;
    }

    public function tableExists(): bool
    {
        $query = $this->connection->query("SHOW TABLES LIKE '{$this->table}'");
        return $query->rowCount() > 0;
    }

    public function describe(): array
    {
        $query = $this->connection->query("DESCRIBE {$this->table}");
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function dropConstraint(string $constraintName)
    {
        $query = "ALTER TABLE {$this->table} DROP CONSTRAINT $constraintName";
        $this->connection->exec($query);
        return $this;
    }
}
