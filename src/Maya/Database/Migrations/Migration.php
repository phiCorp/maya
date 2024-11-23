<?php

namespace Maya\Database\Migrations;

use PDO;

abstract class Migration
{
    protected PDO $connection;

    public function setConnection(PDO $connection)
    {
        $this->connection = $connection;
    }

    protected function create(string $table, callable $callback)
    {
        $schema = new Schema($this->connection, $table);
        $callback($schema);
        $this->connection->exec($schema->buildCreateQuery());
    }

    protected function drop(string $table)
    {
        $this->connection->exec("DROP TABLE IF EXISTS $table");
    }

    abstract public function up();

    abstract public function down();
}
