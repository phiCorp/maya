<?php

namespace Maya\Database\Migrations;

use Exception;
use Maya\Database\Connection\Connection;
use Maya\Disk\Directory;
use Maya\Disk\File;

class DBBuilder
{
    public function up(string $connectionName = 'default')
    {
        try {
            $this->createTables($connectionName);
            return "Migrations ran successfully";
        } catch (\Exception $e) {
            throw new Exception("Migration failed: " . $e->getMessage());
        }
    }

    public function rollbackSteps(string $connectionName = 'default', int $steps = 1)
    {
        $pdoInstance = Connection::getConnection($connectionName);
        $oldMigrations = $this->getOldMigration();

        $batches = array_reverse(array_unique(array_column($oldMigrations, 'batch')));
        $batchesToRollback = array_slice($batches, 0, $steps);

        foreach ($batchesToRollback as $batch) {
            $this->rollbackTables($connectionName, $batch);
        }

        return "Rolled back $steps step(s)";
    }



    public function down(string $connectionName = 'default', ?int $batch = null)
    {
        try {
            $this->rollbackTables($connectionName, $batch);
            return "Rollback successful";
        } catch (\Exception $e) {
            throw new Exception("Rollback failed: " . $e->getMessage());
        }
    }

    private function rollbackTables(string $connectionName = 'default', ?int $batch = null)
    {
        $pdoInstance = Connection::getConnection($connectionName);
        $oldMigrations = $this->getOldMigration();

        if ($batch === null) {
            $batch = !empty($oldMigrations) ? max(array_column($oldMigrations, 'batch')) : 0;
        }

        $migrationsToRollback = array_filter($oldMigrations, fn($migration) => $migration['batch'] === $batch);

        foreach (array_reverse($migrationsToRollback) as $migrationInfo) {
            $filename = appPath('DB/' . $migrationInfo['migration']);
            $migration = require $filename;
            $migration->setConnection($pdoInstance);
            $migration->down();

            $oldMigrations = array_filter($oldMigrations, fn($m) => $m['migration'] !== $migrationInfo['migration']);
        }

        $this->putOldMigration($oldMigrations);

        return true;
    }




    private function getMigrations()
    {
        $oldMigrationsArray = array_column($this->getOldMigration(), 'migration');
        $allMigrationsArray = glob(appPath('DB/*.php'));
        $newMigrationsArray = array_diff($allMigrationsArray, $oldMigrationsArray);

        return $newMigrationsArray;
    }


    private function getOldMigration()
    {
        $filePath = appPath('DB/Data/migrations.json');
        if (!file_exists($filePath)) {
            return [];
        }

        $data = File::read($filePath);
        return empty($data) ? [] : json_decode($data, true);
    }


    private function putOldMigration(array $migrations)
    {
        if (!Directory::exists(appPath('DB/Data'))) {
            Directory::make(appPath('DB/Data'));
        }
        $filePath = appPath('DB/Data/migrations.json');
        File::write($filePath, json_encode($migrations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }


    private function createTables(string $connectionName = 'default')
    {
        $migrations = $this->getMigrations();
        $pdoInstance = Connection::getConnection($connectionName);
        $oldMigrations = $this->getOldMigration();
        $executedMigrations = array_column($oldMigrations, 'migration');
        $newMigrations = array_filter($migrations, function ($migration) use ($executedMigrations) {
            return !in_array(basename($migration), $executedMigrations);
        });

        if (empty($newMigrations)) {
            return "No new migrations to execute.";
        }

        $currentBatch = empty($oldMigrations) ? 1 : max(array_column($oldMigrations, 'batch')) + 1;

        foreach ($newMigrations as $filename) {
            if (!is_string($filename)) {
                throw new Exception("Invalid migration file: " . print_r($filename, true));
            }

            $migration = require $filename;
            $migration->setConnection($pdoInstance);
            $migration->up();

            $oldMigrations[] = ['migration' => basename($filename), 'batch' => $currentBatch];
        }


        $this->putOldMigration($oldMigrations);

        return "Migrations executed successfully.";
    }
}
