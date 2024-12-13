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

    public function decimal(string $name, int $precision = 10, int $scale = 2, bool $nullable = true, ?string $default = null)
    {
        $column = "$name DECIMAL($precision, $scale)";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT '$default'";
        $this->columns[] = $column;
        return $this;
    }

    public function float(string $name, bool $nullable = true, ?float $default = null)
    {
        $column = "$name FLOAT";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT $default";
        $this->columns[] = $column;
        return $this;
    }

    public function double(string $name, bool $nullable = true, ?float $default = null)
    {
        $column = "$name DOUBLE";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT $default";
        $this->columns[] = $column;
        return $this;
    }

    public function year(string $name, bool $nullable = true, ?int $default = null)
    {
        $column = "$name YEAR";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT $default";
        $this->columns[] = $column;
        return $this;
    }

    public function time(string $name, bool $nullable = true, ?string $default = null)
    {
        $column = "$name TIME";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT '$default'";
        $this->columns[] = $column;
        return $this;
    }

    public function set(string $name, array $values, bool $nullable = true, ?string $default = null)
    {
        $valueList = "'" . implode("', '", $values) . "'";
        $column = "$name SET($valueList)";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT '$default'";
        $this->columns[] = $column;
        return $this;
    }

    public function mediumInt(string $name, bool $unsigned = false, bool $nullable = true, ?int $default = null)
    {
        $column = "$name MEDIUMINT";
        if ($unsigned) $column .= " UNSIGNED";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT $default";
        $this->columns[] = $column;
        return $this;
    }

    public function mediumText(string $name, bool $nullable = true)
    {
        $column = "$name MEDIUMTEXT";
        if (!$nullable) $column .= " NOT NULL";
        $this->columns[] = $column;
        return $this;
    }

    public function longText(string $name, bool $nullable = true)
    {
        $column = "$name LONGTEXT";
        if (!$nullable) $column .= " NOT NULL";
        $this->columns[] = $column;
        return $this;
    }

    public function mediumBlob(string $name, bool $nullable = true)
    {
        $column = "$name MEDIUMBLOB";
        if (!$nullable) $column .= " NOT NULL";
        $this->columns[] = $column;
        return $this;
    }

    public function longBlob(string $name, bool $nullable = true)
    {
        $column = "$name LONGBLOB";
        if (!$nullable) $column .= " NOT NULL";
        $this->columns[] = $column;
        return $this;
    }

    public function binary(string $name, int $length = 255, bool $nullable = true)
    {
        $column = "$name BINARY($length)";
        if (!$nullable) $column .= " NOT NULL";
        $this->columns[] = $column;
        return $this;
    }

    public function varBinary(string $name, int $length = 255, bool $nullable = true)
    {
        $column = "$name VARBINARY($length)";
        if (!$nullable) $column .= " NOT NULL";
        $this->columns[] = $column;
        return $this;
    }

    public function geometry(string $name, bool $nullable = true)
    {
        $column = "$name GEOMETRY";
        if (!$nullable) $column .= " NOT NULL";
        $this->columns[] = $column;
        return $this;
    }

    public function point(string $name, bool $nullable = true)
    {
        $column = "$name POINT";
        if (!$nullable) $column .= " NOT NULL";
        $this->columns[] = $column;
        return $this;
    }

    public function polygon(string $name, bool $nullable = true)
    {
        $column = "$name POLYGON";
        if (!$nullable) $column .= " NOT NULL";
        $this->columns[] = $column;
        return $this;
    }

    public function tinyInt(string $name, int $length = 3, bool $unsigned = false, bool $nullable = true, ?int $default = null)
    {
        $column = "$name TINYINT($length)";
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

    public function timestamp(string $name, bool $nullable = true, ?string $default = null)
    {
        $column = "$name TIMESTAMP";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT $default";
        $this->columns[] = $column;
        return $this;
    }

    public function bigInteger(string $name, bool $unsigned = false, bool $nullable = true, ?int $default = null)
    {
        $column = "$name BIGINT";
        if ($unsigned) $column .= " UNSIGNED";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT $default";
        $this->columns[] = $column;
        return $this;
    }

    public function json(string $name, bool $nullable = true)
    {
        $column = "$name JSON";
        if (!$nullable) $column .= " NOT NULL";
        $this->columns[] = $column;
        return $this;
    }

    public function blob(string $name, bool $nullable = true)
    {
        $column = "$name BLOB";
        if (!$nullable) $column .= " NOT NULL";
        $this->columns[] = $column;
        return $this;
    }

    public function bigId(string $name = 'id')
    {
        $this->columns[] = "$name BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }

    public function date(string $name, bool $nullable = true, ?string $default = null)
    {
        $column = "$name DATE";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT '$default'";
        $this->columns[] = $column;
        return $this;
    }

    public function datetime(string $name, bool $nullable = true, ?string $default = null)
    {
        $column = "$name DATETIME";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT '$default'";
        $this->columns[] = $column;
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

    public function addColumn(string $name, string $type, bool $nullable = true, ?string $default = null, ?string $after = null)
    {
        $column = "$name $type";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT '$default'";
        if ($after !== null) $column .= " AFTER $after";

        $this->columns[] = "ADD $column";
        return $this;
    }

    public function modifyColumn(string $name, string $type, bool $nullable = true, ?string $default = null, ?string $after = null)
    {
        $column = "$name $type";
        if (!$nullable) $column .= " NOT NULL";
        if ($default !== null) $column .= " DEFAULT '$default'";
        if ($after !== null) $column .= " AFTER $after";

        $this->columns[] = "MODIFY $column";
        return $this;
    }


    public function dropColumn(string $name)
    {
        $this->columns[] = "DROP COLUMN $name";
        return $this;
    }

    public function renameColumn(string $oldName, string $newName, string $type)
    {
        $this->columns[] = "CHANGE $oldName $newName $type";
        return $this;
    }

    public function buildAlterQuery()
    {
        $alterations = implode(", ", $this->columns);
        $query = "ALTER TABLE {$this->table} $alterations";
        $this->connection->exec($query);
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
