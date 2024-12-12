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
        $this->connection->exec("DROP TABLE $table");
    }
    protected function dropIfExists(string $table)
    {
        $this->connection->exec("DROP TABLE IF EXISTS $table");
    }

    protected function rename(string $oldTable, string $newTable)
    {
        $this->connection->exec("RENAME TABLE `$oldTable` TO `$newTable`");
    }

    protected function truncate(string $table)
    {
        $this->connection->exec("TRUNCATE TABLE `$table`");
    }

    protected function addIndex(string $table, string $indexName, array $columns, string $type = 'INDEX')
    {
        $columnsList = implode('`, `', $columns);
        $this->connection->exec("ALTER TABLE `$table` ADD $type `$indexName` (`$columnsList`)");
    }

    protected function dropIndex(string $table, string $indexName)
    {
        $this->connection->exec("ALTER TABLE `$table` DROP INDEX `$indexName`");
    }

    protected function addForeignKey(string $table, string $keyName, array $columns, string $referencedTable, array $referencedColumns, string $onDelete = 'CASCADE', string $onUpdate = 'CASCADE')
    {
        $columnsList = implode('`, `', $columns);
        $referencedList = implode('`, `', $referencedColumns);
        $this->connection->exec(
            "ALTER TABLE `$table` ADD CONSTRAINT `$keyName` FOREIGN KEY (`$columnsList`) REFERENCES `$referencedTable` (`$referencedList`) ON DELETE $onDelete ON UPDATE $onUpdate"
        );
    }

    protected function dropForeignKey(string $table, string $keyName)
    {
        $this->connection->exec("ALTER TABLE `$table` DROP FOREIGN KEY `$keyName`");
    }

    protected function alter(string $table, callable $callback)
    {
        $schema = new Schema($this->connection, $table);
        $callback($schema);
        $schema->buildAlterQuery();
    }

    abstract public function up();

    abstract public function down();
}
