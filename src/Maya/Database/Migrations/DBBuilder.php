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

    public function down(string $connectionName = 'default', array $migrationFiles = [])
    {
        try {
            $this->rollbackTables($connectionName, $migrationFiles);
            return "Rollback successful";
        } catch (\Exception $e) {
            throw new Exception("Rollback failed: " . $e->getMessage());
        }
    }

    private function rollbackTables(string $connectionName = 'default', array $migrationFiles = [])
    {
        $pdoInstance = Connection::getConnection($connectionName);
        $oldMigrationsBackup = $this->getOldMigration();

        try {
            if ($migrationFiles) {
                foreach ($migrationFiles as $migrationFile) {
                    if (in_array($migrationFile, $oldMigrationsBackup)) {
                        $migration = require $migrationFile;
                        $migration->setConnection($pdoInstance);
                        $migration->down();
                        $oldMigrationsBackup = array_diff($oldMigrationsBackup, [$migrationFile]);
                    }
                }
            } else {
                foreach (array_reverse($oldMigrationsBackup) as $filename) {
                    $migration = require $filename;
                    $migration->setConnection($pdoInstance);
                    $migration->down();
                    array_pop($oldMigrationsBackup);
                }
            }

            $this->putOldMigration($oldMigrationsBackup);
        } catch (\Exception $e) {
            $this->putOldMigration($oldMigrationsBackup);
            throw $e;
        }

        return true;
    }


    private function getMigrations()
    {
        $oldMigrationsArray = $this->getOldMigration();
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

    private function putOldMigration($value)
    {
        if (!Directory::exists(appPath('DB/Data'))) {
            Directory::make(appPath('DB/Data'));
        }
        $filePath = appPath('DB/Data/migrations.json');
        File::write($filePath, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function createTables(string $connectionName = 'default')
    {
        $migrations = $this->getMigrations();
        $pdoInstance = Connection::getConnection($connectionName);
        $oldMigrationsBackup = $this->getOldMigration();

        try {
            foreach ($migrations as $filename) {
                $migration = require $filename;
                $migration->setConnection($pdoInstance);
                $migration->up();
                $oldMigrationsBackup[] = $filename;
            }

            $this->putOldMigration($oldMigrationsBackup);
        } catch (\Exception $e) {
            $this->putOldMigration($oldMigrationsBackup);
            throw $e;
        }

        return true;
    }
}
